project_name := "devture-nagadmin"

# A small throwaway image used for preparing `var/` directory ownership.
alpine_container_image := "docker.io/alpine:3.23.4"

# The uid:gid that the php and nagios containers run as (the nagios user inside the
# manios/nagios image). The php container must own the `var/` directories it writes
# to (the Twig cache and the generated Nagios configuration).
container_user := "100:101"

# show help by default
default:
	@{{ just_executable() }} --list --justfile {{ justfile() }}

# Runs all components (in the foreground). Defaults to the dev environment;
# pass `prod` (e.g. `just run prod`) to run the production configuration.
run env='dev': _prepare_deps _prepare_run (docker-compose env "up")

# Runs all components (in the background)
run-bg env='dev': _prepare_deps _prepare_run (docker-compose env "up -d")

# Stops all components
stop env='dev': (docker-compose env "down")

# Installs PHP dependencies via composer
composer-install:
	./bin/composer install --optimize-autoloader --ignore-platform-req=php

# Updates PHP dependencies via composer
composer-update:
	./bin/composer update --optimize-autoloader --ignore-platform-req=php

# Initializes the MongoDB database (initial data-set import and indexes creation)
init-database: _var-mongodb-io
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/app/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c time_period --jsonArray --file=/db-import/time_period.json
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/app/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c command --jsonArray --file=/db-import/command.json
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/app/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c host --jsonArray --file=/db-import/host.json
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/app/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c service --jsonArray --file=/db-import/service.json
	./bin/container-console init-database

# Performs initial installation and Nagios configuration deployment
install:
	./bin/container-console install

# Starts a MongoDB shell
mongodb-shell env='dev': (docker-compose env "exec mongodb sh -c 'mongosh nagadmin'")

# Creates a new gzipped MongoDB database dump (stored in `var/mongodb-io/latest-dump`)
mongodb-dump env='dev': _var-mongodb-io (docker-compose env "exec -T mongodb sh -c 'rm -rf /mongodb-io/latest-dump > /dev/null 2>&1 && mkdir /mongodb-io/latest-dump'") (docker-compose env "exec -T mongodb sh -c 'mongodump --quiet -d nagadmin --gzip -o /mongodb-io/latest-dump'")

# Imports a gzipped MongoDB database dump (found in `var/mongodb-io/import`)
mongodb-import env='dev': _var-mongodb-io (docker-compose env "exec -T mongodb sh -c 'mongorestore --gzip --dir=/mongodb-io/import'")

# Upgrades MongoDB to the version specified in compose.yml by doing a dump and re-import
mongodb-upgrade: _var-mongodb-io
	{{ justfile_directory() }}/bin/mongodb-upgrade.sh {{ justfile_directory() }} {{ just_executable() }}

# Internal (not meant to be called directly, but are part of the dependency setup chain)

# Internal - runs a `docker compose` command for the given environment (dev|prod),
# combining the shared compose.yml with the matching compose.<env>.yml override.
docker-compose env *extra_args: _require_root_env_file_or_fail
	docker compose -f compose.yml -f compose.{{ env }}.yml -p {{ project_name }} {{ extra_args }}

# Internal - fails early (with guidance) if the repository-root `.env` is missing.
_require_root_env_file_or_fail:
	#!/bin/sh
	if [ ! -f "{{ justfile_directory() }}/.env" ]; then
		echo "Error: missing .env file at {{ justfile_directory() }}/.env" >&2
		echo "Copy .env.dist to .env and adjust the values before running this command." >&2
		exit 1
	fi

# Internal - makes sure PHP dependencies are installed
_prepare_deps:
	#!/bin/sh
	if [ ! -f app/vendor/autoload.php ]; then
		{{ just_executable() }} --justfile {{ justfile() }} composer-install
	fi

# Internal - makes sure the runtime directories exist and have the correct ownership
_prepare_run: _var-cache _var-mongodb-io _var-container-data-mongodb _var-nagadmin-generated-config _var-nagios-var _var-container-data-nagios-etc

# The Twig cache, written by the php container (runs as {{ container_user }}).
_var-cache: (_ensure_dir_prepared_recursive "var/cache")

# Used for MongoDB dump/import I/O; written by the mongodb container (runs as root).
_var-mongodb-io: (_ensure_dir_created "var/mongodb-io")

# The MongoDB data directory; written by the mongodb container (runs as root).
_var-container-data-mongodb: (_ensure_dir_created "var/container-data/mongodb")

# The generated Nagios configuration, written by the php container (runs as {{ container_user }}).
# The php container writes both into this directory (resource.cfg) and its `configuration/`
# subdirectory, so the whole tree (including this parent dir) must be owned by it.
_var-nagadmin-generated-config: (_ensure_dir_created "var/container-data/nagadmin-generated-config/configuration")
	#!/bin/sh
	if [ ! -f var/container-data/nagadmin-generated-config/resource.cfg ]; then
		touch var/container-data/nagadmin-generated-config/resource.cfg
	fi
	{{ just_executable() }} --justfile {{ justfile() }} _ensure_dir_prepared_recursive "var/container-data/nagadmin-generated-config"

# The Nagios var directory (state/retention); written by the nagios container.
_var-nagios-var: (_ensure_dir_prepared_recursive "var/container-data/nagios/var")

# The live Nagios base config dir mounted at /opt/nagios/etc. Seeded from the `.dist`
# templates in etc/services/nagios/etc/ on first run (only copying a file if it's missing,
# so the container's runtime changes — use_timezone, htpasswd.users — are preserved across
# runs). The whole dir is owned by {{ container_user }}, as the container writes into it.
_var-container-data-nagios-etc: (_ensure_dir_created "var/container-data/nagios/etc")
	#!/bin/sh
	for f in nagios.cfg cgi.cfg; do
		if [ ! -f "var/container-data/nagios/etc/$f" ]; then
			cp "etc/services/nagios/etc/$f.dist" "var/container-data/nagios/etc/$f"
		fi
	done
	{{ just_executable() }} --justfile {{ justfile() }} _ensure_dir_prepared_recursive "var/container-data/nagios/etc"

# Internal - ensures a directory exists (created if missing).
_ensure_dir_created path:
	#!/bin/sh
	if [ ! -d {{ path }} ]; then
		mkdir -p {{ path }}
	fi

# Internal - ensures a directory exists and (re-)asserts its ownership recursively.
# Runs on every `_prepare_run`, so a pre-existing mis-owned directory gets corrected.
_ensure_dir_prepared_recursive path: (_ensure_dir_created path)
	@docker run \
	--rm \
	--mount type=bind,src={{ justfile_directory() }},dst=/justfile_directory \
	{{ alpine_container_image }} \
	/bin/sh -c 'chown -R {{ container_user }} /justfile_directory/{{ path }}'
