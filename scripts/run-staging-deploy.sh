#!/usr/bin/env bash
set -euo pipefail
umask 077

: "${STAGING_URL:?Set STAGING_URL on the GitHub staging environment.}"
: "${STAGING_WP_PATH:?Set STAGING_WP_PATH on the GitHub staging environment.}"
: "${STAGING_SSH_HOST:?Set STAGING_SSH_HOST on the GitHub staging environment.}"
: "${STAGING_SSH_USER:?Set STAGING_SSH_USER on the GitHub staging environment.}"
: "${STAGING_SSH_KEY:?Set the STAGING_SSH_KEY secret.}"
: "${STAGING_KNOWN_HOSTS:?Set the verified STAGING_KNOWN_HOSTS secret.}"

staging_url=${STAGING_URL%/}
port=${STAGING_SSH_PORT:-22}
[[ $staging_url =~ ^https://([a-zA-Z0-9.-]+)(:[0-9]+)?$ ]]
preview_host=${BASH_REMATCH[1]}
[[ $STAGING_SSH_HOST =~ ^[a-zA-Z0-9.:-]+$ && $STAGING_SSH_HOST != -* ]]
[[ $STAGING_SSH_USER =~ ^[a-zA-Z0-9_][a-zA-Z0-9_-]*$ && $port =~ ^[0-9]{1,5}$ ]]
[[ $STAGING_WP_PATH == /* && $GITHUB_SHA =~ ^[0-9a-f]{40}$ ]]

local_dir=$(mktemp -d)
remote_dir=''
remote_received_bundle=false
target="$STAGING_SSH_USER@$STAGING_SSH_HOST"
options=(-i "$local_dir/key" -o "UserKnownHostsFile=$local_dir/known_hosts" -o StrictHostKeyChecking=yes -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=15)

cleanup() {
	if [[ -n $remote_dir ]] && ! $remote_received_bundle; then
		printf -v cleanup_command 'rm -rf -- %q' "$remote_dir"
		ssh -p "$port" "${options[@]}" "$target" "$cleanup_command" || true
	fi
	rm -rf -- "$local_dir"
}
trap cleanup EXIT

printf '%s\n' "$STAGING_SSH_KEY" > "$local_dir/key"
printf '%s\n' "$STAGING_KNOWN_HOSTS" > "$local_dir/known_hosts"
unset STAGING_SSH_KEY STAGING_KNOWN_HOSTS
files=(dist/feather-slider.zip scripts/deploy-staging.sh scripts/seed-preview.php)

if [[ -n ${STAGING_HTTP_USER:-} || -n ${STAGING_HTTP_PASSWORD:-} ]]; then
	[[ ${STAGING_HTTP_USER:-} =~ ^[^[:space:]\"\\]+$ && ${STAGING_HTTP_PASSWORD:-} =~ ^[^[:space:]\"\\]+$ ]] ||
		{ echo 'Set both staging HTTP credentials without whitespace, quotes or backslashes.' >&2; exit 1; }
	printf 'machine %s login %s password %s\n' "$preview_host" "$STAGING_HTTP_USER" "$STAGING_HTTP_PASSWORD" > "$local_dir/preview.netrc"
	files+=("$local_dir/preview.netrc")
fi
unset STAGING_HTTP_USER STAGING_HTTP_PASSWORD

remote_temp=$(ssh -p "$port" "${options[@]}" "$target" 'umask 077; mktemp -d')
[[ $remote_temp =~ ^/[a-zA-Z0-9_./-]+/tmp\.[a-zA-Z0-9]+$ && $remote_temp != *'/../'* ]]
remote_dir=$remote_temp
scp -P "$port" "${options[@]}" "${files[@]}" "$target:$remote_dir/"
printf -v deploy_command 'bash %q %q %q %q %q %q' "$remote_dir/deploy-staging.sh" "$remote_dir" "$STAGING_WP_PATH" "$staging_url" "$GITHUB_SHA" "${TEST_ROLLBACK:-false}"
remote_received_bundle=true
result=0
ssh -p "$port" "${options[@]}" "$target" "$deploy_command" | tee "$local_dir/deploy.log" || result=$?
preview=$(sed -n 's/^PREVIEW_URL=//p' "$local_dir/deploy.log" | tail -n 1)
if [[ $preview == "$staging_url/"* && $preview != *[[:space:]]* ]]; then
	printf 'preview_url=%s\n' "$preview" >> "$GITHUB_OUTPUT"
fi
exit "$result"
