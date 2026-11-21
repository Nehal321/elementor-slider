#!/usr/bin/env bash
set -euo pipefail

bundle=$1
wp_path=$2
staging_url=${3%/}
build=$4
rollback_trial=${5:-false}

[[ $bundle =~ ^/[^[:space:]]+/tmp\.[a-zA-Z0-9]+$ || $bundle =~ ^/tmp/tmp\.[a-zA-Z0-9]+$ ]]
[[ $wp_path == /* && $staging_url =~ ^https://[a-zA-Z0-9.-]+(:[0-9]+)?$ ]]
[[ $build =~ ^[0-9a-f]{40}$ ]]

slug=feather-slider
wp_live() { wp --path="$wp_path" "$@"; }
wp_safe() { wp --path="$wp_path" --skip-plugins="$slug" "$@"; }
die() { printf '%s\n' "$*" >&2; exit 1; }

mutated=false
had_plugin=false
was_active=false
plugin_path=''
preview=''
previous_build=''

restore_plugin() {
	if ! $was_active; then
		wp_safe eval 'require_once ABSPATH . "wp-admin/includes/plugin.php"; deactivate_plugins( "feather-slider/feather-slider.php", true );' || return 1
	fi
	if [[ -e $plugin_path ]]; then
		mv -- "$plugin_path" "$bundle/failed-plugin" || return 1
	fi
	if $had_plugin; then
		wp_safe plugin install "$bundle/previous.zip" --force || return 1
	fi
	if $was_active; then
		if ! wp_safe plugin is-active "$slug"; then
			wp_live plugin activate "$slug" || return 1
		fi
		wp_live plugin is-active "$slug" || return 1
		if [[ -n $previous_build && -n $preview ]]; then
			smoke "$previous_build" || return 1
		fi
	fi
}

finish() {
	local status=$?
	trap - EXIT HUP INT TERM
	if (( status != 0 )) && $mutated; then
		echo 'Deployment failed. Restoring the previous plugin state.' >&2
		if restore_plugin; then
			echo 'Rollback verified.' >&2
		else
			chmod 700 "$bundle"
			rm -f -- "$bundle/preview.netrc"
			echo "Rollback failed. Recovery files are in $bundle." >&2
			exit 1
		fi
	fi
	rm -rf -- "$bundle"
	exit "$status"
}

trap finish EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM

wp_safe core is-installed
actual_home=$(wp_safe option get home)
[[ ${actual_home%/} == "$staging_url" ]] || die 'WordPress home does not match STAGING_URL.'
[[ $(wp_safe eval 'echo wp_get_environment_type(), is_multisite() ? ":multisite" : "";') == staging ]] ||
	die 'The target must be a standalone WordPress installation with WP_ENVIRONMENT_TYPE set to staging.'
wp_safe plugin is-active elementor || die 'Install and activate Elementor Free on staging first.'

plugin_root=$(wp_safe eval 'echo WP_PLUGIN_DIR;')
[[ $plugin_root == /* && -d $plugin_root && $plugin_root != / ]] || die 'Could not resolve the WordPress plugin directory.'
plugin_path="$plugin_root/$slug"
[[ ! -L $plugin_path ]] || die 'Feather Slider is a symlink. Use an ordinary plugin directory.'

# GitHub serializes its jobs. This lock also protects the staging target from an overlapping deploy run.
lock_dir="$HOME/.local/state/feather-slider"
mkdir -p -- "$lock_dir"
lock_id=$(printf '%s' "$staging_url" | sha256sum | cut -d' ' -f1)
exec 9>"$lock_dir/$lock_id.lock"
flock -w 45 9 || die 'Another Feather Slider deploy holds the staging lock.'

if [[ -e $plugin_path ]]; then
	[[ -d $plugin_path ]] || die 'The existing plugin path is not a directory.'
	had_plugin=true
	( cd "$plugin_root" && zip -qr "$bundle/previous.zip" "$slug" )
	unzip -tq "$bundle/previous.zip"
	if wp_safe plugin is-active "$slug"; then
		was_active=true
		previous_build=$(wp_live eval 'if ( defined( "FeatherSlider\\BUILD" ) ) { echo constant( "FeatherSlider\\BUILD" ); }' || true)
	fi
fi
if [[ $rollback_trial == true ]] && { ! $was_active || [[ -z $previous_build ]]; }; then
	die 'Run a successful staging deployment before exercising rollback.'
fi

smoke() {
	local expected=$1 attempt query marker_count slide_count
	local curl_auth=()
	[[ ! -f $bundle/preview.netrc ]] || curl_auth=(--netrc-file "$bundle/preview.netrc")
	query='?'
	[[ $preview != *\?* ]] || query='&'
	for attempt in 1 2 3; do
		if curl --fail --silent --show-error --proto '=https' --location --max-redirs 0 --max-time 25 \
			"${curl_auth[@]}" -H 'Cache-Control: no-cache' \
			"${preview}${query}feather_build=${expected}&check=${RANDOM}${attempt}" -o "$bundle/preview.html"; then
			marker_count=$(grep -Fc "data-feather-build=\"$expected\"" "$bundle/preview.html" || true)
			slide_count=$(grep -Fo 'class="fs-slide ' "$bundle/preview.html" | wc -l || true)
			if [[ $marker_count -ge 1 && $slide_count -eq 3 ]]; then
				return 0
			fi
		fi
		sleep 2
	done
	echo 'Preview did not render exactly three slides with the expected build.' >&2
	return 1
}

mutated=true
wp_safe plugin install "$bundle/feather-slider.zip" --force
if ! wp_safe plugin is-active "$slug"; then
	wp_live plugin activate "$slug"
fi
wp_live plugin is-active "$slug"
[[ $(wp_live eval 'echo constant( "FeatherSlider\\BUILD" );') == "$build" ]] ||
	die 'The active plugin does not match the checked build.'

preview=$(wp_live eval-file "$bundle/seed-preview.php")
[[ $preview == "$staging_url/"* && $preview != *[[:space:]]* ]] ||
	die 'The preview URL is outside the configured staging origin.'
printf 'PREVIEW_URL=%s\n' "$preview"
if [[ $rollback_trial == true ]]; then
	die 'Controlled failure before accepting the deployment.'
fi
smoke "$build"
echo 'Staging deploy verified.'
