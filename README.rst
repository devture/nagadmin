Nagadmin
========

Nagadmin is a web-configurator for configuring a `Nagios <http://nagios.com/>`_ installation.

It's not meant to support all Nagios features.
It may force a certain workflow upon you, which may or may not be to your taste.
The reason it does this is to optimize for the common monitoring use-case and make things simpler for it.

If your requirements are complicated, you may need to look into some other solutions.



Why Nagadmin instead of X?
==========================

There are lots of web-configurator systems that aim to make Nagios installations easy to configure from the web.
Editing Nagios configuration files may be inconvenient (requires terminal access),
and more importantly does not show a good overview of what's really configured.

None of the existing Nagios configurator systems seemed to achieve the goals of:

	- providing a beautiful and pleasant to use user-interface
	- providing a simple user-interface that's easier than "ssh -> edit raw config file"
	- optimizing for the common use-case, by hiding certain complex Nagios features
	- giving you a good overview of the current configuration


Installation
============

Server Prerequisites
--------------------

- Nagios

- `MongoDB <http://www.mongodb.org/>`_ (for storing the configuration data)

- PHP 5.3+ with the `php-mongo <http://www.mongodb.org/downloads>`_ extension

- `git <http://git-scm.com/>`_ and `composer <http://getcomposer.org/>`_


Commands
--------

Here's everything you need to do to install it::

	# Download the source code and go into the main directory

	# Install the depenedencies with the help of composer
	composer update

	# Make sure you have a running instance of MongoDB

	# Copy the parameters file template and set it up according to your needs
	cp config/parameters.json.dist config/parameters.json
	vim config/parameters.json

	# Run the install command
	php console.php install

	# Create the initial database structure
	cd src/Devture/Bundle/NagiosBundle/Resources/database
	mongoimport -d nagadmin -c time_period --jsonArray < time_period.json
	mongoimport -d nagadmin -c command --jsonArray < command.json
	mongoimport -d nagadmin -c host --jsonArray < host.json
	mongoimport -d nagadmin -c service --jsonArray < service.json

	# Fix permissions so that the `nagios` user can access this application's directory
	# Running `php console.php` with the `nagios` user should not result in an error

	# Make sure the http user can execute the command defined in config/parameters.json
	# as `NagiosBundle.deployment_handler.post_deployment_command`
	# You may wish to run visudo and add some new rules:
	http ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart nagios
	Defaults:http   !requiretty

	# Create the path defined in config/parameters.json as `NagiosBundle.deployment_handler.path` and fix its permissions
	mkdir /etc/nagios/nagadmin-generated
	chown http:nagios /etc/nagios/nagadmin-generated
	chmod 750 /etc/nagios/nagadmin-generated

	# Edit the main nagios configuration file (/etc/nagios/nagios.cfg)
	# Comment all cfg_file directives and add a new cfg_dir directive:
	cfg_dir=/etc/nagios/nagadmin-generated/configuration/

	# Also comment the resource_file directive and add a new one:
	resource_file=/etc/nagios/nagadmin-generated/resource.cfg


FAQ
===

Does this support all kinds of esoteric Nagios features?
--------------------------------------------------------
No (read above).


Does this provide a frontend where I can view the status of my services (like the default Nagios CGI)?
------------------------------------------------------------------------------------------------------
No. Nagadmin is meant for the system administrator to configure services and deploy a configuration to be used by Nagios.
For a frontend, check out `Thruk <http://thruk.org/>`_ or the Nagios CGI interface.


Can I import my existing Nagios configuration files and start managing them with Nagadmin?
------------------------------------------------------------------------------------------
Not yet, but hopefully soon.
That shouldn't stop you from giving Nagadmin a spin.


What are the plans for Nagadmin's future?
-----------------------------------------
The source code will always be available.
Developing additional features and complicating it much further is not the aim of this project.
Community members are free to make improvements to the existing codebase.


What is Nagadmin written in?
----------------------------
Nagadmin is written in `PHP <http://php.net/>`_ and uses the `Silex microframework <http://silex.sensiolabs.org/>`_.


What are the system requirements?
---------------------------------
You need Nagios 3.x to consume the generated configuration files and PHP 5.3+ to power the web-configurator.


Can I install the web-configurator on another machine (not the one running Nagios)?
-----------------------------------------------------------------------------------
Yes, but that's slightly more complicated.

1. You need to install Nagios on the machine that runs the web-configurator
(the `nagios` executable is needed to verify that the generated configuration files)

2. You need to setup a post-deployment command to move the locally generated files
to the actual Nagios machine and reload/restart the remote Nagios daemon.


I'm not running Nagios, but a compatible system (Icinga, Shinken, Centreon). Can I use this?
--------------------------------------------------------------------------------------------
Probably. Give it a try. If it fails somewhere, tell us about it and we can work on a fix then.



Limitations
===========

Limitations listed below are either caused by features not being implemented (yet)
or by conscious design decisions to omit them (and potentially replace them) with something else.


Host checks and notifications are not supported
------------------------------------------------
Because of this, all hosts are forced to an OK state (this allows service checks to run for them).


Service groups are not supported
-------------------------------------------------------
They seem to be yet another thing that the administrator is asked to enter, complicating the workflow and not adding too much value.


Defining templates for timeperiods/contacts/hosts/services is not supported
----------------------------------------------------------------------------
This keeps things simple, by removing the complex inheritance model.


Service dependencies are not supported
-------------------------------------------------------------------------------------------------------------------------
That's an advanced feature.

Nagadmin supports automatic service dependencies though.

A service that has a name "ping" or "host-alive" (case-insensitive) is automatically made a parent service of all other
services on the same host.
This allows you to easily define an "important" service, which all other services depend on.
It makes up for the missing "Host checks and notifications" feature mentioned above.


Service escalations are not supported
-------------------------------------
That's considered and advanced feature, outside the scope of what Nagadmin aims to provide (at least at this point in time).


Event handlers are not supported
--------------------------------
That's considered and advanced feature, outside the scope of what Nagadmin aims to provide (at least at this point in time).


Existing configuration files cannot be imported
-----------------------------------------------
This is definitely something we wish to improve upon.
It would make it much easier to get started with Nagadmin if you've been doing things manually until now,
or to let you migrate from another system.