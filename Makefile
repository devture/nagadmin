OS := $(shell uname)
PROJECT_NAME := "devture-nagadmin"

help: ## Show this help.
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

run: _prepare_deps _prepare_run ## Runs all components (in the foreground)
	docker-compose -p $(PROJECT_NAME) up

run-bg: _prepare_deps _prepare_run ## Runs all components (in the background)
	docker-compose -p $(PROJECT_NAME) up -d

stop: ## Stops all components
	docker-compose -p $(PROJECT_NAME) down

composer-install: ## Installs PHP dependencies via composer
	./bin/composer install --optimize-autoloader --ignore-platform-req=php

composer-update: ## Updates PHP dependencies via composer
	./bin/composer update --optimize-autoloader --ignore-platform-req=php

init-database: ## Initializes the MongoDB database
	# Import initial data-set
	docker-compose -p $(PROJECT_NAME) run --rm --no-TTY -v $$(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c time_period --jsonArray --file=/db-import/time_period.json
	docker-compose -p $(PROJECT_NAME) run --rm --no-TTY -v $$(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c command --jsonArray --file=/db-import/command.json
	docker-compose -p $(PROJECT_NAME) run --rm --no-TTY -v $$(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c host --jsonArray --file=/db-import/host.json
	docker-compose -p $(PROJECT_NAME) run --rm --no-TTY -v $$(pwd)/src/Devture/Bundle/NagiosBundle/Resources/database:/db-import mongodb /usr/bin/mongoimport -h mongodb -d nagadmin -c service --jsonArray --file=/db-import/service.json

	# Create database indexes
	./bin/container-console init-database

install: ## Performs initial installation and Nagios configuration deployment
	./bin/container-console install

mongodb-dump: var/mongodb-io ## Creates a new gzipped MongoDB database dump (stored in `var/mongodb-io/latest-dump`)
	@docker-compose -p $(PROJECT_NAME) exec -T mongodb sh -c "rm -rf /mongodb-io/latest-dump > /dev/null 2>&1 && mkdir /mongodb-io/latest-dump"
	@docker-compose -p $(PROJECT_NAME) exec -T mongodb sh -c "mongodump --quiet -d nagadmin --gzip -o /mongodb-io/latest-dump"

mongodb-import: var/mongodb-io ## Imports a gzipped MongoDB database dump (found in `var/mongodb-io/import`)
	docker-compose -p $(PROJECT_NAME) exec -T mongodb sh -c "mongorestore --gzip --dir=/mongodb-io/import"

# Internal (not meant to be called directly, but are part of the dependency setup chain)
_prepare_deps: \
	vendor/autoload.php \
	;

_prepare_run: \
	var/cache \
	var/mongodb-io \
	var/nagadmin-generated-config \
	var/nagios/var \
	;

# Internal - makes sure PHP dependencies are installed
vendor/autoload.php:
	make composer-install

var/mongodb-io:
	mkdir -p var/mongodb-io

var/cache:
	mkdir -p var/cache
	docker-compose -p $(PROJECT_NAME) run --rm --no-deps --user=0:0 php chown 100:101 /code/var/cache -R

var/nagadmin-generated-config:
	mkdir -p var/nagadmin-generated-config/configuration
	touch var/nagadmin-generated-config/resource.cfg
	docker-compose -p $(PROJECT_NAME) run --rm --no-deps --user=0:0 php chown 100:101 /code/var/nagadmin-generated-config -R

var/nagios/var:
	mkdir -p var/nagios/var
