#!/bin/bash
set -euo pipefail

# Checks to run before checking out the commit to deploy: pre-flight.sh <commit>
# This is still the old tree: the new commit's files are read with `git show`.
# A failure means the deploy should not go ahead, leaving the old version running.
#
# It checks that the host's configuration files (rendered by Ansible) define everything the new commit's templates do:
# variable names for .env-style files, key paths for JSON files. Values are not checked; an empty one still counts as set.
# Names the host has but the template no longer does only produce a warning.
# Names listed as optional (e.g. development-only settings) may be missing on the host.
#
# The files to check are listed in the new commit's etc/deploy/pre-flight.conf.sh,
# so a commit adding a configuration file also adds its check.
# Needs bash, git, coreutils and jq.

commit="${1:?Usage: $0 <commit>}"
cd "$(dirname "$0")/../.."

# `comm` needs input sorted the way it compares. Byte order, so that `sort` and `comm` agree in any locale and
# implementation (Ubuntu 26.04's uutils coreutils disagree with each other on mixed-case keys in en_US.UTF-8).
export LC_ALL=C

config_path=etc/deploy/pre-flight.conf.sh

# Set by the configuration file: "<template>:<host file>" pairs, and names (variables or key paths) the host may lack.
ENV_FILES=()
JSON_FILES=()
OPTIONAL_NAMES=()

git cat-file -e "${commit}:${config_path}" || { echo "❌ ${commit} has no ${config_path}" >&2; exit 1; }
# shellcheck source=/dev/null # (read from the commit, not the file system)
source <(git show "${commit}:${config_path}")

# Variable names in a .env-style file (grep's exit code 1 only means there are none).
env_names() { { grep -oE '^[A-Za-z_][A-Za-z0-9_]*=' || [ $? -eq 1 ]; } | tr -d '=' | sort -u; }

# Key paths (e.g. Mailer/Dsn) of the leaves in a JSON file: values that are not objects, arrays included as a whole.
# Empty objects yield nothing, so a template's `{}` placeholder demands nothing.
json_paths() {
	jq -r 'def leaves: to_entries[] | .key as $k | .value | if type == "object" then leaves | "\($k)/\(.)" else $k end; leaves' | sort -u
}

failed=0

# Compares the names a template defines with those a host file defines: check <kind> <names function> <template:file>
check() {
	local kind="$1" names="$2" template="${3%%:*}" file="${3#*:}"
	local wanted present stale missing

	if ! git cat-file -e "${commit}:${template}"; then
		echo "❌ ${commit} has no ${template}, which ${config_path} lists" >&2
		failed=1
		return
	fi
	if [ ! -f "${file}" ]; then
		echo "❌ ${file} is missing" >&2
		failed=1
		return
	fi

	wanted=$(git show "${commit}:${template}" | "${names}") || { echo "❌ Cannot read ${template} of ${commit}" >&2; failed=1; return; }
	present=$("${names}" < "${file}") || { echo "❌ Cannot read ${file}" >&2; failed=1; return; }

	stale=$(comm -13 <(echo "${wanted}") <(echo "${present}"))
	if [ -n "${stale}" ]; then
		echo "⚠️ ${file} has ${kind} that ${commit} no longer uses: ${stale//$'\n'/ }"
	fi

	missing=$(comm -23 <(echo "${wanted}") <(echo "${present}") | { grep -vxF -f <(printf '%s\n' "${OPTIONAL_NAMES[@]}") || [ $? -eq 1 ]; })
	if [ -n "${missing}" ]; then
		echo "❌ ${commit} needs ${kind} that ${file} lacks: ${missing//$'\n'/ }. Add them, then deploy again." >&2
		failed=1
	fi
}

for pair in "${ENV_FILES[@]}"; do
	check variables env_names "${pair}"
done
if [ "${#JSON_FILES[@]}" -gt 0 ] && ! command -v jq > /dev/null; then
	echo "❌ jq is needed to check ${JSON_FILES[*]}, but is not installed" >&2
	failed=1
else
	for pair in "${JSON_FILES[@]}"; do
		check keys json_paths "${pair}"
	done
fi

if [ "${failed}" -ne 0 ]; then
	exit 1
fi

echo "✅ Pre-flight passed"
