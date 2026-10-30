#!/bin/bash
set -euo pipefail

# Runs a command, and runs it again when it fails: retry.sh <command> [<argument>...]
# Meant for deploy steps that only download (images, packages) and leave what is serving alone, so that a brief network failure (a registry, Packagist, a Git host) does not fail the deploy.
# Never use it for steps that change state (database upgrades, `docker compose up`, restarts): a failure there may be a half-done change, not a blip.
#
# Needs bash and sleep.

# Seconds to wait before each retry (3 attempts by default), overridable for tests.
read -r -a delays <<< "${RETRY_DELAYS:-10 30}"
attempts=$((${#delays[@]} + 1))

# Ctrl+C stops for good, instead of only interrupting the current attempt.
trap 'exit 130' INT

for ((attempt = 1; ; attempt++)); do
	status=0
	"$@" || status=$?
	if [ "${status}" -eq 0 ]; then
		exit 0
	fi
	if [ "${attempt}" -ge "${attempts}" ]; then
		echo "❌ Failed ${attempts} times, giving up (exit code ${status}): $*" >&2
		exit "${status}"
	fi
	delay="${delays[attempt - 1]}"
	echo "🔁 Failed (exit code ${status}, attempt ${attempt} of ${attempts}), retrying in ${delay}s: $*" >&2
	sleep "${delay}"
done
