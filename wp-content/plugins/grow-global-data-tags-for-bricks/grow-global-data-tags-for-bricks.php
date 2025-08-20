<?php
/**
 * Plugin Name: Grow Global Data Tags for Bricks
 * Description: Exposes ACF-backed values from any public post type as Bricks dynamic data tags.
 * Version: 1.4.0
 * Author: Grow Therapy
 * License: GPL-2.0+
 *
 * Token format (field-first, taxonomy-aware):
 *   {<field_name>_grow_data_<post_title_slug>_<taxonomy_slug>_<post_id>}
 *
 * Example:
 *   {metric_value_grow_data_average_cost_per_session_insurance_156}
 *
 * Why field-first? It makes parsing unambiguous (we split once on "_grow_data_"),
 * then parse from the right to get <post_id> (and optional taxonomy), leaving the
 * post title slug free to include underscores safely.
 */

// ============================================================================
// Boot guards
// ============================================================================
if ( ! defined( 'ABSPATH' ) ) { 
	exit; 
}

// ============================================================================
// Config & Feature Flags
// ============================================================================
// Constants are guarded so site owners can override via wp-config.php if desired.
if ( ! defined( 'GROW_DATA_TAG_PREFIX' ) ) {
	define( 'GROW_DATA_TAG_PREFIX', 'grow_data_' );
}
if ( ! defined( 'GROW_DATA_TOKEN_DELIM' ) ) {
	define( 'GROW_DATA_TOKEN_DELIM', '_grow_data_' );
}
if ( ! defined( 'GROW_DATA_TAG_GROUP_DEFAULT' ) ) {
	define( 'GROW_DATA_TAG_GROUP_DEFAULT', 'Global Data' );
}
if ( ! defined( 'GROW_DATA_TRANSIENT' ) ) {
	define( 'GROW_DATA_TRANSIENT', 'grow_global_data_registry_v1' );
}
if ( ! defined( 'GROW_DATA_CACHE_TTL' ) ) {
	define( 'GROW_DATA_CACHE_TTL', 5 * MINUTE_IN_SECONDS );
}
if ( ! defined( 'GROW_DATA_DEBUG' ) ) {
	define( 'GROW_DATA_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
}
if ( ! defined( 'GROW_DATA_INCLUDE_EMPTY' ) ) {
	define( 'GROW_DATA_INCLUDE_EMPTY', false );
}

// ============================================================================
// Autoloader
// ============================================================================
spl_autoload_register( function ( $class ) {
	$prefix = 'Grow_Data_';
	$base_dir = __DIR__ . '/includes/';
	
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}
	
	$relative_class = substr( $class, $len );
	$file = $base_dir . 'class-' . str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';
	
	if ( file_exists( $file ) ) {
		require $file;
	}
});

// ============================================================================
// Plugin Initialization
// ============================================================================
add_action( 'plugins_loaded', function() {
	// Check if Bricks is active
	if ( ! class_exists( 'Bricks\Elements' ) ) {
		return;
	}
	
	// Initialize the plugin
	new Grow_Data_Plugin();
}, 10 );
