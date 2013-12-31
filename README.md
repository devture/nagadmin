# Nagadmin: web-configurator and frontend for Nagios

Nagadmin is a web-configurator for configuring a [Nagios](http://nagios.com/) installation.
It also serves as a frontend - a place where you can see the status of your services.

It's not meant to support all Nagios features.
It may force a certain workflow upon you, which may or may not be to your taste.
The reason it does this is to optimize for the common monitoring use-case and make things simpler for it.

If your requirements are complicated, you may need to look into some other solutions or fork-and-improve this one.


--------------------


## Why Nagadmin instead of [some other solution]?

There are lots of web-configurator systems that aim to make Nagios installations easy to configure from the web.
Editing Nagios configuration files may be inconvenient (requires terminal access),
and more importantly does not show a good overview of what's really configured.

None of the existing Nagios configurator systems seemed to achieve the goals of:

- providing a simple user-interface that's easier than "ssh to the nagios host -> edit raw config file"
- providing a beautiful and pleasant user-interface that also works on mobile devices
- providing both a configuration and frontend tool in one
- providing advanced access control, so that many users can see and potentially do different things
- optimizing for the common use-case, by hiding certain complex Nagios features
- giving you a good overview of the current configuration and status


--------------------


## Installation

The instructions below assume you're installing on a modern linux distribution (like Archlinux),
which uses systemd and the http user is `http`.

Installing on other distros only requires minor changes.


### Get all the prerequisites

- [Nagios](http://nagios.com/)

- The Nagios plugins

- [MongoDB](http://www.mongodb.org/)

- sudo

- PHP 5.3+ (5.5+ is recommended and will make your life much easier)

- The php-mongo/php-pecl-mongo extension - for connecting to MongoDB

- [git](http://git-scm.com/) - for getting the source code

- [composer](http://getcomposer.org/) - for installing PHP libraries


### Download the source code and go into the main directory

	cd /srv/http
	git clone <repository url> <your vhost name>
	cd <your vhost name>


### Install the dependencies with the help of composer

	php composer.phar install


### Configure

Start by copying the sample configuration parameters file:

	cp config/parameters.json.dist config/parameters.json

You may need to adjust some paths, as the Nagios status/command/log files are in a different directory for diferent distributions.
init.d-based systems need some command changes as well.

Do not worry about permissions or the existence of paths yet - we handle that below.
If you don't understand something, leave the default value and carry on.

But **be sure to edit the file** - some things need to be adjusted in all cases:

	vim config/parameters.json


### Hook with Nagios

Edit the main Nagios configuration file: `/etc/nagios/nagios.cfg`.

Comment all existing `cfg_file` directives and add a new `cfg_dir` directive:

	cfg_dir=/etc/nagios/nagadmin-generated/configuration/

Also comment the existing `resource_file` directive and add a new one:

	resource_file=/etc/nagios/nagadmin-generated/resource.cfg


### Import the database

First the initial data set:

	mongoimport -d nagadmin -c time_period --jsonArray < src/Devture/Bundle/NagiosBundle/Resources/database/time_period.json
	mongoimport -d nagadmin -c command --jsonArray < src/Devture/Bundle/NagiosBundle/Resources/database/command.json
	mongoimport -d nagadmin -c host --jsonArray < src/Devture/Bundle/NagiosBundle/Resources/database/host.json
	mongoimport -d nagadmin -c service --jsonArray < src/Devture/Bundle/NagiosBundle/Resources/database/service.json

Then run the installer, which customizes it a bit for your setup:

	php console.php install


### Fix permissions

#### Allow Nagios to access this system

The `nagios` user needs to access this application's directory in order to be able to send notifications.
Running `php console.php` with the `nagios` user should not result in an error.
A way to do that is to add the `http` group as a supplementary group for the `nagios` user:

	usermod -a http nagios


#### Allow the http user to write a new configuration

Create the path defined in `config/parameters.json` as `NagiosBundle.deployment_handler.path` and fix its permissions:

	mkdir /etc/nagios/nagadmin-generated
	chown http:nagios /etc/nagios/nagadmin-generated
	chmod 750 /etc/nagios/nagadmin-generated

The web interface should now be able to save the Nagios configuration files that it generates.


#### Allow the http user to reload the Nagios daemon

Now that the `http` can write a new configuration, we need to have it be able to tell Nagios to reload itself, so it can start using it.
Make sure the `http` user can execute the command defined in `config/parameters.json` as `NagiosBundle.deployment_handler.post_deployment_command`.
Run `visudo` and add some new rules:

	http ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nagios
	Defaults:http   !requiretty


### Set up the web server

Create a new administrator user account for you:

	php console.php devture-user:add "username-here" "email-here"

Make the cache directory writable by the web server user:

	chown http:http cache

Set up your web server vhost (see the resources/webserver directory for examples).


### Deploy the initial configuration

Open the web interface, log in with your account, go to Configuration and hit the Deploy button.
This should create the initial configuration in `/etc/nagios/nagadmin-generated`


### Restart Nagios and make sure it starts with the system

On a systemd-based system:

	/usr/bin/systemctl restart nagios
	/usr/bin/sytemctl enable nagios


### Verify that it all works

Run the check command to see if things are running correctly:

	php console.php check:status


--------------------


## FAQ


### Does this support all kinds of esoteric Nagios features?

No - read the introduction above.


### Does this provide a frontend where I can view the status of my services?

Yes. Nagadmin is both a web configuration tool for Nagios and a frontend.
It can be used as a simple alternative to the default Nagios CGI interface.


### Can I import my existing Nagios configuration files into Nagadmin?

Not yet, but hopefully soon.
That shouldn't stop you from giving Nagadmin a spin.


### What are the plans for Nagadmin's future?

The source code will always be available.
Developing additional features and complicating it much further is not the aim of this project.
Community members are free to make improvements to the existing codebase.


### What is Nagadmin written in?

Nagadmin is written in [PHP](http://php.net/) and uses the [Silex microframework](http://silex.sensiolabs.org/).


### What are the system requirements?

You need Nagios 3.x or 4.x to consume the generated configuration files and PHP 5.3+ to power the web-configurator.


### Can I install the web-configurator on another machine (not the one running Nagios)?

Probably. But that's more complicated and hasn't been tested:

1. You need to also install Nagios on the machine that runs the web-configurator
(the `nagios` executable is needed to verify the generated configuration files, before deploying)

2. You need to set up a post-deployment command (`NagiosBundle.deployment_handler.post_deployment_command` in `config/parameters.json`)
to move the locally generated files to the actual Nagios machine and reload/restart the remote Nagios daemon.

3. You need to set up a way (NFS?) to access the Nagios status, command and log files specified in `config/parameters.json`.


### I'm not running Nagios, but a compatible system (Icinga, Shinken, Centreon). Can I use this?

Probably. Give it a try. If/when it fails somewhere, tell us about it and we can work on a fix.


### I need to check thousands of services. Can I use this?

Not right now, or at least it won't work so well. Nagadmin hasn't been optimized for your use-case (yet) - read the introduction above.
It targets smaller installations, which don't have quite that many services.


--------------------



## Limitations

Limitations listed below are either caused by features not being implemented (yet)
or by conscious design decisions to omit them (and potentially replace them) with something else.


### Host checks and notifications are not supported

Because of this, all hosts are (internally) forced to have an OK state.
We do this to allow service checks to run for them.


### Service groups are not supported

They seem to be yet another thing that the administrator is asked to enter, complicating the workflow and not adding too much value.


### Defining templates for timeperiods/contacts/hosts/services is not supported

This keeps things simple, by removing the complex inheritance model.


### Service dependencies are not supported

That's an advanced feature.

Nagadmin supports automatic service dependencies though.

A service that has a name "ping" or "host-alive" (case-insensitive) is automatically made a parent service of all other
services on the same host.
This allows you to easily define an "important" service, which all other services depend on.
It makes up for the missing "Host checks and notifications" feature mentioned above.

When such an important/parent service is down, individual notifications for all of its children will be suppressed.


### Service escalations are not supported

That's considered an advanced feature, outside the scope of what Nagadmin aims to provide (at least at this point in time).


### Event handlers are not supported

That's considered an advanced feature, outside the scope of what Nagadmin aims to provide (at least at this point in time).


### Existing configuration files cannot be imported

This is definitely something we wish to improve upon.
It would make it much easier to get started with Nagadmin if you've been doing things manually until now,
or to let you migrate from another system.


### The system doesn't play well with thousands of services

Nagadin targets a simpler use-case and smaller installations (at this time at least).
The code and user interface are done in a way that doesn't currently scale to thousands of services.
If you have to monitor thousands of services, it may also appear a bit limiting and simple to you anyway.
