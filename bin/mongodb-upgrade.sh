#!/bin/bash
set -euo pipefail

# MongoDB Upgrade Script
# Upgrades MongoDB to the version specified in compose.yml by doing a dump and re-import.
# This is the safe way to cross MongoDB major versions (the on-disk format is not
# compatible across multiple major-version jumps, but a logical dump/restore is).

print_usage() {
	cat <<EOF
Usage: $0 <base_directory> <justfile_binary_path>

Arguments:
  base_directory         The project root directory (where the justfile lives)
  justfile_binary_path   The path to the just executable

Example:
  $0 /srv/http/nagadmin /usr/local/bin/just
EOF
}

if [ $# -ne 2 ]; then
	echo "Error: Missing required arguments" >&2
	echo "" >&2
	print_usage >&2
	exit 1
fi

base_directory="$1"
justfile_binary_path="$2"

if [ ! -d "${base_directory}" ]; then
	echo "Error: Base directory '${base_directory}' does not exist" >&2
	exit 1
fi

if [ ! -x "${justfile_binary_path}" ]; then
	echo "Error: Just executable '${justfile_binary_path}' does not exist or is not executable" >&2
	exit 1
fi

justfile_path="${base_directory}/justfile"
var_mongodb_io_path="${base_directory}/var/mongodb-io"
mongodb_data_path="${base_directory}/var/container-data/mongodb"
mongodb_data_backup_path="${base_directory}/var/container-data/mongodb-backup"

# The uid:gid the mongodb container's data directory should be owned by.
# MongoDB runs as root inside the container, so root-owned data is correct.
mongodb_data_user="0:0"

# A small throwaway image, used to run privileged filesystem ops (mkdir/chown of the
# root-owned MongoDB data directory) without requiring the invoking user to be root.
alpine_container_image="docker.io/alpine:3.23.5"

if [ ! -f "${justfile_path}" ]; then
	echo "Error: Justfile not found at '${justfile_path}'" >&2
	exit 1
fi

run_just() {
	"${justfile_binary_path}" --justfile "${justfile_path}" "$@"
}

echo "🔎 Abort if MongoDB data path (${mongodb_data_path}) not found"
test -d "${mongodb_data_path}" || exit 1

echo "🔎 Abort if old MongoDB backup directory (${mongodb_data_backup_path}) already exists"
test ! -d "${mongodb_data_backup_path}" || exit 1

echo "🔎 Abort if the MongoDB container is not running"
# `ps -q <service>` prints the running container's id (and nothing if it's not running).
# This is robust across docker-compose versions, unlike grepping the STATUS column text
# (which differs between versions: "Up ..." vs "running").
test -n "$(run_just docker-compose "ps -q mongodb")" || exit 1

echo "🔃 Ensure the (new) MongoDB container image is pulled..."
run_just docker-compose "pull mongodb"

if [ -d "${var_mongodb_io_path}/import" ]; then
	echo "🧹 Removing old ${var_mongodb_io_path}/import directory..."
	rm -rf "${var_mongodb_io_path}/import"
fi

echo "🛑 Stopping PHP container (so nothing writes to MongoDB during the upgrade)..."
run_just docker-compose "stop php"

echo "⬇️ Dumping MongoDB (from the OLD version, still running) and relocating to ${var_mongodb_io_path}/import..."
run_just mongodb-dump
mv "${var_mongodb_io_path}/latest-dump" "${var_mongodb_io_path}/import"

# The MongoDB data directory is owned by root (mongo runs as root in the container), so the
# backup move and the fresh-directory creation/chown are done inside a throwaway root container.
echo "🛟 Backing up old MongoDB data directory (${mongodb_data_path} -> ${mongodb_data_backup_path})..."
docker run --rm --mount "type=bind,src=${base_directory},dst=/base" "${alpine_container_image}" \
	mv "/base/var/container-data/mongodb" "/base/var/container-data/mongodb-backup"
echo "🛟 In case of failure, restore it by moving ${mongodb_data_backup_path} back to ${mongodb_data_path}."

echo "🔨 Preparing a fresh, empty MongoDB data directory (${mongodb_data_path})..."
docker run --rm --mount "type=bind,src=${base_directory},dst=/base" "${alpine_container_image}" \
	sh -c "mkdir -p /base/var/container-data/mongodb && chown ${mongodb_data_user} /base/var/container-data/mongodb"

echo "▶️ Starting the (new) MongoDB container against the empty data directory..."
run_just docker-compose "up --force-recreate --detach --wait mongodb"

echo "⬆️ Importing the dump into the new MongoDB version..."
run_just mongodb-import

echo "▶️ Starting the PHP container again..."
run_just docker-compose "up --detach --wait php"

echo "✅ Upgrade finished. Once you've verified everything works, clean up with:"
echo "   rm -rf ${var_mongodb_io_path}/import ${mongodb_data_backup_path}"
