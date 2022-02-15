# Nagadmin: web-configurator and frontend for Nagios

Nagadmin is a web-configurator for configuring a [Nagios](https://www.nagios.com) installation.
It also serves as a frontend - a place where you can see the status of your services.

It's not meant to support all Nagios features.
It may force a certain workflow upon you, which may or may not be to your taste.
The reason it does this is to optimize for the common monitoring use-case and make things simpler for it.

If your requirements are complicated, you may need to look into some other solutions or fork-and-improve this one.

![dashboard-partial-screenshot](https://raw.github.com/devture/nagadmin/master/resources/screenshots/dashboard-partial.png)

--------------------


## Why Nagadmin instead of "some other solution"?

There are lots of web-configurator systems that aim to make Nagios installations easy to configure from the web.
Editing Nagios configuration files may be inconvenient (requires terminal access),
and more importantly does not show a good overview of what's really configured.

None of the existing Nagios configurator systems seemed to achieve the goals of:

- providing a simple user-interface that's **easier than "SSH into the Nagios host & edit raw config files"**
- providing a **beautiful and pleasant user-interface** that also works on mobile devices
- providing both a **configuration and frontend tool in one**
- providing **advanced access control**, so that many users can see and potentially do different things
- **optimizing for the common use-case**, by hiding certain complex Nagios features
- giving you a **good overview of the current configuration and status**


--------------------


## Installation


### Prerequisites

- Docker
- Docker Compose (v1 or v2)


### Download the source code and go into the main directory

	cd /srv/http
	git clone <repository url> nagadmin
	cd nagadmin


### Configure

Start by copying the sample configuration parameters file:

	cp config/parameters.json.dist config/parameters.json
	cp .env.dist .env

Now modify `config/parameters.json` and `.env` to your liking.


### Run for the first time

```sh
make run
```

Not all services will run well yet. Nagios will encounter some errors, because it can't find some of its configuration yet.
We resolve this below during the [Installation](#install) step.


### Initialize the database

Run the following command to initialize the database (initial data-set import and databse indexes creation):

```sh
make init-database
```


### Install

Run the following command to set up Resource Variables and install the initial Nagios configuration:

```sh
make install
```

Nagios should now properly start and run.


### Create your first user

Create a new administrator user account for you:

```sh
./bin/container-console devture-user:add USERNAME_HERE EMAIL_ADDRESS_HERE
```

You'll be asked for a password, etc.


### Accessing Nagadmin and Nagios

Use a web browser to access Nagadmin at this URL: http://nagadmin.127.0.0.1.nip.io:20180

You should be able to log in with the user that you created in the previous step.

You can also access Nagios at this URL: http://nagadmin.127.0.0.1.nip.io:20188

You need to authenticate using the username/password you've specified in `.env` (`NAGIOSADMIN_USER` and `NAGIOSADMIN_PASS`).


### Verify that it all works

Run the check command to see if things are running correctly:

```sh
./bin/container-console check:status
```


### Set up a reverse-proxy

See `resources/webserver`. You may also wish to adjust the `%trusted_proxies%` parameter in `config/parameters.json`.

--------------------


## FAQ


### Does this support all kinds of esoteric Nagios features?

No - read the introduction above.


### Does this provide a frontend where I can view the status of my services?

Yes. Nagadmin is both a web configuration tool for Nagios and a frontend.
It can be used as a simple alternative to the default Nagios CGI interface.


### Can I import my existing Nagios configuration files into Nagadmin?

No. You'd need to start from scratch.


### What are the plans for Nagadmin's future?

The source code will always be available.
Developing additional features and complicating it much further is not the aim of this project.
Community members are free to make improvements to the existing codebase.


### What is Nagadmin written in?

Nagadmin is written in [PHP](https://php.net) and uses the [Silex microframework](https://github.com/silexphp/Silex).


### What are the system requirements?

We require an x86-84 (amd64) Linux server with Docker and Docker Compose regardless of the distribution.

All services run in containers.


### Can I install the web-configurator on another machine (not the one running Nagios)?

No. Nagios only runs in a container as part of this setup.


### I'm not running Nagios, but a compatible system (Icinga, Shinken, Centreon). Can I use this?

Nagadmin only works with Nagios. Some of these systems are similar, so you may be able to migrate your existing setup to Nagios (powered by Nagadmin).


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

Nagadmin supports automatic service dependencies though (see the `NagiosBundle.auto_service_dependency.master_service_regexes` parameter in `config/parameters.json`).

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

If you've been using Nagios by configuring it manually, you'd need to replicate all your existing configuration again via Nagadmin's UI.


### The system doesn't play well with thousands of services

Nagadin targets a simpler use-case and smaller installations (at this time at least).
The code and user interface are done in a way that doesn't currently scale to thousands of services.
If you have to monitor thousands of services, it may also appear a bit limiting and simple to you anyway.
