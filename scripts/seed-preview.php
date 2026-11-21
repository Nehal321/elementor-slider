<?php
/**
 * Provision the staging-only Elementor demo. Run with WP-CLI after activation.
 */

namespace FeatherSlider\Staging;

defined( 'WP_CLI' ) && WP_CLI || exit;

function png_chunk( string $type, string $content ): string {
	return pack( 'N', strlen( $content ) ) . $type . $content . pack( 'N', crc32( $type . $content ) );
}

function preview_image( string $key, array $dark, array $light, bool $mobile = false ): array {
	$slug = 'feather-preview-' . $key . ( $mobile ? '-mobile' : '' );
	$existing = get_page_by_path( $slug, OBJECT, 'attachment' );
	if ( $existing && wp_get_attachment_image_url( $existing->ID, 'full' ) ) {
		return [ 'id' => $existing->ID, 'url' => wp_get_attachment_image_url( $existing->ID, 'full' ) ];
	}

	$width  = $mobile ? 360 : 960;
	$height = $mobile ? 480 : 540;
	$origin = $mobile ? .54 : .81;
	$raw = '';
	for ( $y = 0; $y < $height; ++$y ) {
		$vertical = $y / $height;
		$raw .= "\0";
		for ( $x = 0; $x < $width; ++$x ) {
			$horizontal = $x / $width;
			$distance = hypot( ( $horizontal - $origin ) * $width / $height, $vertical - .42 );
			$glow = max( 0, 1 - $distance * 1.15 );
			$wave = ( sin( 15 * $vertical + 4 * sin( 3 * $horizontal ) ) + 1 ) / 2;
			$blend = min( 1, .12 * $wave + .84 * $glow * $glow );
			$raw .= pack(
				'CCC',
				(int) ( $dark[0] + ( $light[0] - $dark[0] ) * $blend ),
				(int) ( $dark[1] + ( $light[1] - $dark[1] ) * $blend ),
				(int) ( $dark[2] + ( $light[2] - $dark[2] ) * $blend )
			);
		}
	}

	$png  = "\x89PNG\r\n\x1a\n";
	$png .= png_chunk( 'IHDR', pack( 'NNCCCCC', $width, $height, 8, 2, 0, 0, 0 ) );
	$png .= png_chunk( 'IDAT', gzcompress( $raw ) );
	$png .= png_chunk( 'IEND', '' );

	$upload = wp_upload_bits( $slug . '.png', null, $png );
	if ( $upload['error'] ) {
		\WP_CLI::error( $upload['error'] );
	}
	$attachment = wp_insert_attachment( [
		'post_title'     => 'Feather Slider preview artwork',
		'post_name'      => $slug,
		'post_status'    => 'inherit',
		'post_mime_type' => 'image/png',
	], $upload['file'], 0, true );
	if ( is_wp_error( $attachment ) || ! $attachment ) {
		wp_delete_file( $upload['file'] );
		\WP_CLI::error( 'Could not register the preview image.' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, $upload['file'] ) );

	return [ 'id' => $attachment, 'url' => $upload['url'] ];
}

function preview_slide( string $id, string $title, string $description, array $dark, array $light ): array {
	return [
		'_id'           => $id,
		'desktop_image' => preview_image( $id, $dark, $light ),
		'heading'       => $title,
		'description'   => $description,
	];
}

if ( ! in_array( wp_get_environment_type(), [ 'staging', 'local' ], true ) || is_multisite() ) {
	\WP_CLI::error( 'The Feather Slider preview requires a standalone staging or local WordPress installation.' );
}

$slug = 'feather-slider-preview';
$existing_page = get_page_by_path( $slug );
if ( $existing_page ) {
	if ( 'publish' !== $existing_page->post_status || '1' !== get_post_meta( $existing_page->ID, '_feather_slider_preview', true ) ) {
		\WP_CLI::error( 'The preview slug is already in use. Free the slug or restore the original demo page.' );
	}
	\WP_CLI::line( get_permalink( $existing_page->ID ) );
	return;
}

$first = preview_slide( 'a31b47e', 'Blue Hour', 'A quiet blue horizon as the evening light fades.', [ 8, 14, 36 ], [ 45, 132, 245 ] );
$first['mobile_image'] = preview_image( 'a31b47e', [ 8, 14, 36 ], [ 86, 159, 252 ], true );
$first['button_label'] = 'Explore';
$first['button_link'] = [ 'url' => home_url( '/' ) ];
$second = preview_slide( 'e452ccd', 'Coastal Light', 'Sea-green tones and soft lines inspired by the coast.', [ 5, 29, 36 ], [ 17, 206, 169 ] );
$second['content_horizontal'] = 'center';
$second['text_alignment'] = 'center';
$third = preview_slide( 'cb61a2f', 'Night Garden', 'A violet glow settles into the landscape after dark.', [ 33, 12, 46 ], [ 183, 82, 221 ] );
$third['content_vertical'] = 'end';
$third['content_horizontal'] = 'right';

$elements = [[
	'id'       => 'd6f523a',
	'elType'   => 'container',
	'isInner'  => false,
	'settings' => [
		'content_width' => 'full',
		'padding'       => [ 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ],
	],
	'elements' => [[
		'id'         => 'd3b21dc',
		'elType'     => 'widget',
		'widgetType' => 'feather-slider',
		'elements'   => [],
		'settings'   => [
			'slides'       => [ $first, $second, $third ],
			'ratio_desktop' => '21/9',
			'ratio_mobile'  => '4/5',
		],
	]],
]];
$admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
$post = wp_insert_post( [
	'post_author' => $admins[0] ?? 0,
	'post_title'  => 'Feather Slider Preview',
	'post_name'   => $slug,
	'post_type'   => 'page',
	'post_status' => 'publish',
	'meta_input'  => [
		'_feather_slider_preview'  => '1',
		'_wp_page_template'        => 'elementor_canvas',
		'_elementor_edit_mode'     => 'builder',
		'_elementor_template_type' => 'wp-page',
		'_elementor_version'       => ELEMENTOR_VERSION,
		'_elementor_data'          => wp_slash( wp_json_encode( $elements ) ),
	],
], true );
if ( is_wp_error( $post ) || ! $post ) {
	\WP_CLI::error( 'Could not create the Feather Slider demo page.' );
}
\WP_CLI::line( get_permalink( $post ) );
