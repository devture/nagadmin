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
is kept passing at **level 5**. Higher levels are reachable over time by adding
the missing type declarations (parameter, return and property types) they
require — bump the `level` in the config and work through what it reports.

## Pre-commit hooks (prek)

[prek](https://github.com/j178/prek) runs a set of quick checks before each
commit. The hooks are defined in
[`.pre-commit-config.yaml`](../.pre-commit-config.yaml): built-in checks
(trailing whitespace, final newlines, YAML/JSON validity, merge-conflict
markers, accidentally-committed large files or private keys) plus the PHPStan
analysis above.

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
