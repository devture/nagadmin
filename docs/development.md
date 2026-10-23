# Development

This page covers the developer tooling. For running the stack itself, see the
[README](../README.md). All recipes below are listed by `just` (run `just` with
no arguments to see them).

## Static analysis (PHPStan)

[PHPStan](https://phpstan.org/) checks the PHP code for type errors and other
bugs without running it. It is a `require-dev` dependency and runs inside the
`php` container (which has the `mongodb` extension the analysis needs):

```sh
just php-analyze
```

The configuration lives in [`app/phpstan.neon`](../app/phpstan.neon). The code
is kept passing at **level 8** (PHPStan's strictest level).

## PHP code formatting (PHP CS Fixer)

[PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) checks the PHP code
for consistent formatting and style. Like PHPStan it is a `require-dev`
dependency and runs inside the `php` container:

```sh
just php-format-check
```

To apply the fixes automatically:

```sh
just php-format
```

The ruleset lives in
[`app/.php-cs-fixer.dist.php`](../app/.php-cs-fixer.dist.php) (tab indentation
plus a curated set of whitespace, casing, import and trailing-comma rules — not
a full reformat preset).

## Twig templates (Twig CS Fixer)

[Twig CS Fixer](https://github.com/VincentLanglet/Twig-CS-Fixer) checks the Twig
templates for consistent formatting and style. Like PHPStan it is a `require-dev`
dependency and runs inside the `php` container:

```sh
just twig-format-check
```

To apply the fixes automatically:

```sh
just twig-format
```

The ruleset lives in
[`app/.twig-cs-fixer.dist.php`](../app/.twig-cs-fixer.dist.php) (tab indentation
plus delimiter/operator/punctuation spacing and trailing-whitespace rules).

## Front-end code (React/TypeScript)

The live-status UI is built from the TypeScript/React sources in
[`app/frontend/`](../app/frontend/), bundled by [esbuild](https://esbuild.github.io/)
into `app/public/assets/frontend/` (generated; not committed). Everything runs
in an ephemeral Node.js container (`bin/node`), so there are no host dependencies.

```sh
just js-build
```

`just run` builds the assets automatically on first use and when files under
`app/frontend/` or the Node Dockerfile are newer than the last successful build.
The `app/public/assets/frontend/completed` marker prevents unnecessary rebuilds.
Use `just js-build` to rebuild immediately without starting the stack.

While developing, rebuild on every change:

```sh
just js-build-continuously
```

Linting (ESLint) and type-checking (strict TypeScript) together:

```sh
just js-analyze
```

`just js-autofix` applies the auto-fixable ESLint fixes, and
`just js-pull-dependencies` populates `app/frontend/node_modules` on the host
so IDE autocompletion works.

## Dependency maintenance

Renovate tracks Composer, npm, Dockerfiles, Compose images and mise. Custom
extractors also track the Composer helper image, the Alpine image in `justfile`,
and the MongoDB PHP extension built in the PHP Dockerfile. The PHP image name in
Compose is a local build tag, so it is excluded from registry lookups.
The mail relay uses the maintained `ghcr.io/devture/exim-relay` registry;
explicit version rules track its release/build suffixes and Nagios's `build-N` tags.
The main `php85` APK package is pinned so Renovate detects PHP patch and package
revision updates even when the Alpine image tag stays unchanged. Keep its APK
registry branch in `renovate.json` aligned with the PHP Dockerfile's Alpine base.
The `PHP_VERSION` argument selects the extension package names and must match the
pinned PHP package's major/minor series. Node's version rule also tracks its
Alpine suffix, which Docker's default compatibility rules would otherwise retain.
Runtime image and PHP package updates take priority in Renovate's PR queue.
PR branches are refreshed after base-branch changes so new CI workflows and
compatibility fixes become available for ArtiDezi assessments.
ArtiDezi owns automatic merging after review and successful GitHub checks;
Renovate itself does not merge updates. Weekly lockfile maintenance refreshes
transitive dependencies within the declared ranges.

Commit both `app/composer.lock` and `app/frontend/package-lock.json` after
updates. Symfony components are grouped in Renovate; their `composer.json`
constraints define the release line without a second Flex `extra.symfony.require`
constraint blocking upgrades. Composer's `platform.ext-mongodb` declares the
minimum driver version for resolution in the Composer container. It does not
install the driver: the PHP image builds the pinned official extension, and
`platform-check` verifies required extensions when the application loads.

React/React DOM, Bootstrap, jQuery, Select2 and Font Awesome are npm dependencies.
`just js-build` copies their browser assets to the ignored
`app/public/assets/frontend/vendor/` directory and bundles the official React
packages into one shared runtime for comploader. No manually downloaded copies
of these libraries need updating. `just run` rebuilds changed assets; use
`just js-build` to rebuild them explicitly. Recreate the PHP container after rebuilding its image with
`docker compose -f compose.yml build php` when its driver or base image changes.

The GitHub `Check` workflow installs and audits both lockfiles, builds both
development images, and runs PHPStan, PHP/Twig style checks, ESLint, TypeScript
and the asset build. It does not start the monitoring stack or send notifications.
For equivalent local checks, run:

```sh
just composer-install
./bin/composer validate --strict
./bin/composer audit --locked
docker compose -f compose.yml build php
just php-analyze php-format-check twig-format-check js-analyze js-build
```

## Pre-commit hooks (prek)

[prek](https://github.com/j178/prek) runs a set of quick checks before each
commit. The hooks are defined in
[`.pre-commit-config.yaml`](../.pre-commit-config.yaml): built-in checks
(trailing whitespace, final newlines, YAML/JSON validity, merge-conflict
markers, accidentally-committed large files or private keys) plus the PHPStan
analysis, PHP CS Fixer, Twig CS Fixer and JS/TS analysis checks above.

prek itself is pinned and provided through [mise](https://mise.jdx.dev/), so the
only prerequisite is having `mise` installed; the recipes install prek into a
project-local data directory (`var/mise`) on first use.

Install the git hook so the checks run automatically on `git commit`:

```sh
just prek-install-git-pre-commit-hook
```

Some hooks fix issues in place (e.g. trailing whitespace). When that happens the
commit is aborted so you can review and stage the fixes, then commit again.

Run the hooks manually anytime:

```sh
just prek-run-on-staged   # only the staged files
just prek-run-on-all      # the whole repository
```

## Logs

Logging goes through [Monolog](https://github.com/Seldaek/monolog) and is written
to the container's **stderr** — never to log files — so it flows to
systemd-journald (via the `devture-nagadmin` service) like every other container's
output, and the journal handles rotation/retention.

In **dev** the application logs live at `notice` level, so a `docker compose logs
-f php` tail shows warnings, errors and uncaught exceptions as they happen without
the per-request router/security debug chatter. In **prod** the logs are buffered
and only flushed when an error occurs (so a clean request logs nothing), then the
full debug trail for that request is emitted. Tune both in
[`app/config/packages/monolog.yaml`](../app/config/packages/monolog.yaml).

The php-fpm pool sets `catch_workers_output` so the application's stderr is
emitted on the php container (not bounced back through nginx as `[error]`).
