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

# Runs all components (in the foreground)
run: _prepare_deps _prepare_run (docker-compose "up")

# Runs all components (in the background)
run-bg: _prepare_deps _prepare_run (docker-compose "up -d")

# Stops all components
stop: (docker-compose "down")

# Installs PHP dependencies via composer
composer-install:
	./bin/composer install --optimize-autoloader --ignore-platform-req=php

# Updates PHP dependencies via composer
composer-update:
	./bin/composer update --optimize-autoloader --ignore-platform-req=php

# Initializes the MongoDB database (initial data-set import and indexes creation)
init-database: _var-mongodb-io
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c time_period --jsonArray --file=/db-import/time_period.json
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c command --jsonArray --file=/db-import/command.json
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c host --jsonArray --file=/db-import/host.json
	docker compose -f compose.yml -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c service --jsonArray --file=/db-import/service.json
	./bin/container-console init-database

# Performs initial installation and Nagios configuration deployment
install:
	./bin/container-console install

# Creates a new gzipped MongoDB database dump (stored in `var/mongodb-io/latest-dump`)
mongodb-dump: _var-mongodb-io
	@docker compose -f compose.yml -p {{ project_name }} exec -T mongodb sh -c "rm -rf /mongodb-io/latest-dump > /dev/null 2>&1 && mkdir /mongodb-io/latest-dump"
	@docker compose -f compose.yml -p {{ project_name }} exec -T mongodb sh -c "mongodump --quiet -d nagadmin --gzip -o /mongodb-io/latest-dump"

# Imports a gzipped MongoDB database dump (found in `var/mongodb-io/import`)
mongodb-import: _var-mongodb-io
	docker compose -f compose.yml -p {{ project_name }} exec -T mongodb sh -c "mongorestore --gzip --dir=/mongodb-io/import"

# Internal (not meant to be called directly, but are part of the dependency setup chain)

# Internal - runs a `docker compose` command against this project's compose file
docker-compose *extra_args:
	docker compose -f compose.yml -p {{ project_name }} {{ extra_args }}

# Internal - makes sure PHP dependencies are installed
_prepare_deps:
	#!/bin/sh
	if [ ! -f vendor/autoload.php ]; then
		{{ just_executable() }} --justfile {{ justfile() }} composer-install
	fi

# Internal - makes sure the runtime directories exist and have the correct ownership
_prepare_run: _var-cache _var-mongodb-io _var-container-data-mongodb _var-nagadmin-generated-config _var-nagios-var

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
