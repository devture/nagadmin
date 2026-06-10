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

## Pre-commit hooks (prek)

[prek](https://github.com/j178/prek) runs a set of quick checks before each
commit. The hooks are defined in
[`.pre-commit-config.yaml`](../.pre-commit-config.yaml): built-in checks
(trailing whitespace, final newlines, YAML/JSON validity, merge-conflict
markers, accidentally-committed large files or private keys) plus the PHPStan
analysis, PHP CS Fixer and Twig CS Fixer checks above.

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
