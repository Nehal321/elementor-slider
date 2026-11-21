<?php
/**
 * Plugin Name: Feather Slider for Elementor
 * Description: A lightweight, responsive hero slider for Elementor Free.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: feather-slider
 * License: GPL-2.0-or-later
 */

namespace FeatherSlider;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';
const BUILD = 'development';
const HANDLE = 'feather-slider';

add_action( 'elementor/widgets/register', static function ( $manager ) {
	require_once __DIR__ . '/includes/class-slider-widget.php';
	$manager->register( new Slider_Widget() );
} );

add_action( 'wp_enqueue_scripts', static function () {
	$version = 'development' === BUILD ? VERSION : BUILD;
	wp_register_style( HANDLE, plugins_url( 'assets/slider.css', __FILE__ ), [], $version );
	wp_register_script( HANDLE, plugins_url( 'assets/slider.js', __FILE__ ), [], $version, true );
} );

add_action( 'admin_notices', static function () {
	if ( did_action( 'elementor/loaded' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Feather Slider requires Elementor. Install and activate the free Elementor plugin to use the widget.', 'feather-slider' )
	);
} );
