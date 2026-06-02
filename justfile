project_name := "devture-nagadmin"

# show help by default
default:
	@{{ just_executable() }} --list --justfile {{ justfile() }}

# Runs all components (in the foreground)
run: _prepare_deps _prepare_run
	docker-compose -p {{ project_name }} up

# Runs all components (in the background)
run-bg: _prepare_deps _prepare_run
	docker-compose -p {{ project_name }} up -d

# Stops all components
stop:
	docker-compose -p {{ project_name }} down

# Installs PHP dependencies via composer
composer-install:
	./bin/composer install --optimize-autoloader --ignore-platform-req=php

# Updates PHP dependencies via composer
composer-update:
	./bin/composer update --optimize-autoloader --ignore-platform-req=php

# Initializes the MongoDB database (initial data-set import and indexes creation)
init-database: _var-mongodb-io
	docker-compose -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c time_period --jsonArray --file=/db-import/time_period.json
	docker-compose -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c command --jsonArray --file=/db-import/command.json
	docker-compose -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c host --jsonArray --file=/db-import/host.json
	docker-compose -p {{ project_name }} run --rm --no-TTY -v $(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c service --jsonArray --file=/db-import/service.json
	./bin/container-console init-database

# Performs initial installation and Nagios configuration deployment
install:
	./bin/container-console install

# Creates a new gzipped MongoDB database dump (stored in `var/mongodb-io/latest-dump`)
mongodb-dump: _var-mongodb-io
	@docker-compose -p {{ project_name }} exec -T mongodb sh -c "rm -rf /mongodb-io/latest-dump > /dev/null 2>&1 && mkdir /mongodb-io/latest-dump"
	@docker-compose -p {{ project_name }} exec -T mongodb sh -c "mongodump --quiet -d nagadmin --gzip -o /mongodb-io/latest-dump"

# Imports a gzipped MongoDB database dump (found in `var/mongodb-io/import`)
mongodb-import: _var-mongodb-io
	docker-compose -p {{ project_name }} exec -T mongodb sh -c "mongorestore --gzip --dir=/mongodb-io/import"

# Internal (not meant to be called directly, but are part of the dependency setup chain)

# Internal - makes sure PHP dependencies are installed
_prepare_deps:
	#!/bin/sh
	if [ ! -f vendor/autoload.php ]; then
		{{ just_executable() }} --justfile {{ justfile() }} composer-install
	fi

# Internal - makes sure the runtime directories exist
_prepare_run: _var-cache _var-mongodb-io _var-nagadmin-generated-config _var-nagios-var

_var-mongodb-io:
	mkdir -p var/mongodb-io

_var-cache:
	#!/bin/sh
	if [ ! -d var/cache ]; then
		mkdir -p var/cache
		docker-compose -p {{ project_name }} run --rm --no-deps --user=0:0 php chown 100:101 /code/var/cache -R
	fi

_var-nagadmin-generated-config:
	#!/bin/sh
	if [ ! -d var/nagadmin-generated-config ]; then
		mkdir -p var/nagadmin-generated-config/configuration
		touch var/nagadmin-generated-config/resource.cfg
		docker-compose -p {{ project_name }} run --rm --no-deps --user=0:0 php chown 100:101 /code/var/nagadmin-generated-config -R
	fi

_var-nagios-var:
	mkdir -p var/nagios/var
