# Feather Slider for Elementor

Feather Slider is a responsive hero slider widget for Elementor Free. It uses WordPress media attachments, CSS transitions and vanilla JavaScript.

## Install

Run `python scripts/package.py` from the repository root. Upload `dist/feather-slider.zip` in **Plugins → Add New → Upload Plugin** and activate it. In the Elementor editor, add **Feather Slider** and configure **Slides**, **Playback and navigation**, and the **Style** sections. Use standard WordPress image alt text for images that convey information.

## Connect GitHub to staging

1. Set up a standalone WordPress installation with its own database at an HTTPS staging subdomain. Set `WP_ENVIRONMENT_TYPE` to `staging` in its configuration. Install and activate Elementor Free.
2. Create the GitHub Actions environment **staging** and allow deployments from `main`. Grant the SSH user write access to the staging plugin directory. SSH and SFTP access, WP-CLI, Bash, curl, zip, unzip and flock must be available on the host.
3. Add the following environment variables and secrets. Use a staging-only SSH key and get the expected host key from the hosting provider or administrator through a trusted channel.

| Name | Location | Value |
| --- | --- | --- |
| `STAGING_URL` | Variable | WordPress home URL, such as `https://staging.example.com` |
| `STAGING_WP_PATH` | Variable | Absolute server path to the WordPress installation |
| `STAGING_SSH_HOST` | Variable | Hosting SSH hostname or IP |
| `STAGING_SSH_USER` | Variable | Staging SSH user |
| `STAGING_SSH_PORT` | Optional variable | Defaults to 22 |
| `STAGING_SSH_KEY` | Secret | Private key for the staging SSH user |
| `STAGING_KNOWN_HOSTS` | Secret | Verified OpenSSH known_hosts entry for that hostname and port |
| `STAGING_HTTP_USER`, `STAGING_HTTP_PASSWORD` | Optional secrets | HTTP Basic Auth credentials if staging uses password protection. Use a username and password without whitespace, quotes or backslashes |

Pull requests targeting `main` run checks and produce a downloadable ZIP. A push to `main` deploys the checked ZIP once `STAGING_URL` is configured. On the first deploy, the workflow creates a three-slide Elementor preview with sample images. The run summary links to the preview. You can edit the demo in WordPress. Keep it published with exactly three slides so the automated check can still use it. Configure any staging page cache to bypass or honor the smoke-check query parameters.

After a successful staging deploy, run the workflow manually from `main` with **Exercise rollback** enabled. This run deliberately fails before accepting the candidate, restores the previous plugin, and verifies its build through the preview. Check the log for **Rollback verified**.

## Verify in Elementor

- Reorder and edit slides in the staging preview. Compare desktop and mobile sizing and the image choice on either side of a custom mobile breakpoint. The second and third demo slides use the desktop image at all sizes.
- Try all five transitions, navigation positions, the glass style and its fallback, autoplay pause with hover and focus, loop off, keyboard controls, and reduced motion.
- Add a second widget and check that each instance responds independently. Remove all but one slide in an instance and check that its navigation is hidden. Check the network panel on a page without the widget to confirm that the plugin's CSS and JavaScript are absent.
