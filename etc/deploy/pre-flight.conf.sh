# Project-specific settings for bin/deploy/pre-flight.sh (`just deploy-pre-flight`), which sources this file
# from the commit being deployed.
#
# Configuration files rendered on the host (e.g. by Ansible), each checked against its template in that commit,
# as "<template>:<host file>" pairs (paths relative to the project directory).

ENV_FILES=(.env.dist:.env app/.env.dist:app/.env)
JSON_FILES=()

# Names (variables or JSON key paths like System/Timezone) the templates have but the host's files may lack,
# e.g. development-only settings, or ones only some deployments need (trusted proxies, behind a reverse proxy only).
OPTIONAL_NAMES=(NGINX_HTTP_BIND_ADDRESS NAGIOS_HTTP_BIND_ADDRESS MAILCRAB_HTTP_BIND_ADDRESS SYMFONY_TRUSTED_PROXIES)
