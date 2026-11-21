#!/bin/bash
set -euo pipefail

# Applies the checked-out commit to the running stack (`just deploy`, after checking out the commit).
# The stack itself keeps running attached (`just run`, e.g. in a systemd unit); this reconciles it underneath.

just="${1:?Usage: $0 <just executable>}"
cd "$(dirname "$0")/../.."

compose() { "${just}" docker-compose "$@"; }

# With Docker's containerd image store, the default provenance attestation gives every build a new image ID, even a fully cached one,
# so `up` would recreate the built services (MongoDB too, when built by compose.override.yml) on every deploy. Without it, an unchanged build keeps its ID.
export BUILDX_NO_DEFAULT_ATTESTATIONS=1

# "9.0" from an image reference like docker.io/mongo:9.0.4 or localhost/example/mongodb:9.0.4-1; nothing if the tag is not a version.
major_minor() {
	local tag="${1##*:}"
	if [[ "${tag}" =~ ^([0-9]+)\.([0-9]+)(\.|$|-) ]]; then
		echo "${BASH_REMATCH[1]}.${BASH_REMATCH[2]}"
	fi
}

# vendor/ is not in the repository, and `just` installs it only when it is missing, so a changed composer.lock needs this.
# Retried when it fails (see bin/deploy/retry.sh): installing from the same composer.lock again is harmless.
echo "📦 Installing PHP dependencies..."
bin/deploy/retry.sh "${just}" composer-install

# A built image is rebuilt only when its inputs change, not on every deploy: a rebuild without build cache
# (`docker system prune` drops it) gives an unchanged Dockerfile a new image ID, and `up` would then recreate
# its services (MongoDB included, when built by compose.override.yml) for an unrelated change, like a jQuery bump.
#
# The inputs are the build context's content (its git tree) and the build settings Compose resolves (args, target, ...).
# Each built image is also tagged with their hash (e.g. devture/nagadmin-php:inputs-<hash>), so a deploy whose inputs
# match an image already on this host reuses that image (retagging it if needed) instead of building. This depends
# neither on the build cache nor on which commit was deployed before (failed deploys, reverts and retries included).
# A context with uncommitted or ignored files (development) is always built.
# A context outside the checkout (e.g. one that a compose.override.yml adds) has no git tree: its files are hashed instead.
inputs_tag() {
	local service="$1" context="$2" image="$3" status tree build hash
	# Each step is checked on its own: this function runs in a condition, where `set -e` does not apply,
	# and a failure must never yield a tag that matches an existing image.
	if [ "${context}" = .. ] || [[ "${context}" == ../* ]]; then
		# Names, modes, link targets and contents, in a fixed order; nothing the clock or the owner changes.
		tree=$(tar --create --file=- --directory="${context}" --format=gnu --sort=name --mtime=@0 --owner=0 --group=0 --numeric-owner . | sha256sum) || return 1
	else
		# GIT_OPTIONAL_LOCKS=0: `git status` would otherwise rewrite .git/index as whoever runs this (root, in deploys).
		status=$(GIT_OPTIONAL_LOCKS=0 git status --porcelain --ignored -- "${context}") || return 1
		[ -z "${status}" ] || return 1
		tree=$(git rev-parse --verify --quiet "HEAD:${context}") || return 1
	fi
	build=$(jq -ceS --arg service "${service}" '.services[$service].build' <<< "${compose_config}") || return 1
	hash=$(printf '%s\n%s\n' "${tree}" "${build}" | sha256sum) || return 1
	hash="${hash:0:16}"
	[[ "${hash}" =~ ^[0-9a-f]{16}$ ]] || return 1
	echo "${image%:*}:inputs-${hash}"
}

compose_config=$(compose config --format json)
built_services=$(jq -r '.services | to_entries[] | select(.value.build) | [.key, .value.build.context, .value.image] | @tsv' <<< "${compose_config}")
services_to_build=()
declare -A built_service_inputs_tags=()
while IFS=$'\t' read -r service context image; do
	[ -n "${service}" ] || continue
	context=$(realpath --relative-to=. "${context}")
	if ! tag=$(inputs_tag "${service}" "${context}" "${image}"); then
		services_to_build+=("${service}")
	elif docker image inspect "${tag}" > /dev/null 2>&1; then
		docker tag "${tag}" "${image}"
	else
		services_to_build+=("${service}")
		built_service_inputs_tags["${service}"]="${tag} ${image}"
	fi
done <<< "${built_services}"

# Retried when they fail (see bin/deploy/retry.sh), as they change nothing that serves.
echo "🔨 Building and pulling images, while the old ones keep serving..."
if [ "${#services_to_build[@]}" -gt 0 ]; then
	echo "🔨 Building: ${services_to_build[*]}"
	bin/deploy/retry.sh "${just}" docker-compose build "${services_to_build[@]}"
	for service in "${!built_service_inputs_tags[@]}"; do
		read -r tag image <<< "${built_service_inputs_tags[${service}]}"
		docker tag "${image}" "${tag}"
	done
fi
# Only images missing on this host are pulled: re-pulling a tag picks up an upstream re-push of it (official images are
# rebuilt under the same tag), which `up` then applies by recreating the service (MongoDB included) on an unrelated deploy.
# A new image arrives with a commit that changes its reference (a Renovate bump).
bin/deploy/retry.sh "${just}" docker-compose pull --ignore-buildable --policy missing --quiet

containers_data_path=var/container-data
upgrade_marker="${containers_data_path}/mongodb-upgrade-in-progress"

# A failed upgrade leaves its new, possibly half-imported database running on the new image, so a later deploy would see nothing left to upgrade.
if [ -e "${upgrade_marker}" ]; then
	echo "❌ ${upgrade_marker} exists: an earlier MongoDB upgrade did not finish. Check the data (the old one is in ${containers_data_path}/mongodb-backup-*), then remove the marker." >&2
	exit 1
fi

# Patch releases (and revisions of a locally built image) run on the existing data files; anything else (minor, major,
# downgrade, or a tag that is not a version) needs a dump and restore. The effective image counts (compose.override.yml included).
mongodb_container=$(compose ps --all --quiet mongodb)
if [ -z "${mongodb_container}" ]; then
	# Only the container tells which version wrote the data, and `just stop` removes it.
	if [ -n "$(ls -A "${containers_data_path}/mongodb" 2>/dev/null)" ]; then
		echo "❌ There is MongoDB data, but no container to tell which version wrote it. Start the stack (just run), then deploy again." >&2
		exit 1
	fi
else
	running=$(docker inspect --format '{{ .Config.Image }}' "${mongodb_container}")
	wanted=$(compose config --images mongodb)
	if [ "${running}" != "${wanted}" ] && { [ -z "$(major_minor "${running}")" ] || [ "$(major_minor "${running}")" != "$(major_minor "${wanted}")" ]; }; then
		echo "⬆️ MongoDB changes from ${running} to ${wanted}: upgrading with a dump and restore..."
		IMAGES_PREPARED=1 "${just}" mongodb-upgrade
	fi
fi

php_before=$(compose ps --all --quiet php)

echo "▶️ Updating containers..."
# --no-build: the images were prepared above, and Compose must not build a missing one itself.
compose up --detach --remove-orphans --no-build

# nginx's configuration files are single bind-mounted files: a checkout replaces them with new ones, which a running container never sees.
nginx_config_changed() {
	local file mounted
	for file in nginx.conf:/etc/nginx/nginx.conf vhost.conf:/etc/nginx/conf.d/default.conf; do
		mounted=$(compose exec -T nginx cat "${file#*:}") || return 0
		[ "${mounted}" = "$(cat "etc/services/nginx/${file%%:*}")" ] || return 0
	done
	return 1
}

# nginx caches `php`'s address for a few seconds (see vhost.conf's resolver), so a recreated php container gets nginx recreated too.
if [ "$(compose ps --all --quiet php)" != "${php_before}" ] || nginx_config_changed; then
	echo "🔄 Recreating nginx..."
	compose up --detach --force-recreate nginx
fi

# Prod Twig never recompiles changed templates.
echo "🧹 Clearing the Symfony cache..."
"${just}" cache-clear

# Idempotent: deploys the Nagios configuration generated by this version and reloads Nagios.
echo "🛠️ Deploying the Nagios configuration..."
IMAGES_PREPARED=1 "${just}" install

echo "✅ Deployed"
