<?php
/**
 * Plugin Name: Grow Global Data Tags for Bricks
 * Description: Exposes ACF-backed values from any public post type as Bricks dynamic data tags.
 * Version: 1.6.0
 * Author: Grow Therapy
 * License: GPL-2.0+
 *
 * Token format (field-first, taxonomy-aware):
 *   {<field_name>_grow_data_<post_title_slug>_<taxonomy_slug>_<post_id>}
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Paths */
define( 'GROW_DATA_PLUGIN_FILE', __FILE__ );
define( 'GROW_DATA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'GROW_DATA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/** Includes */
require_once GROW_DATA_PLUGIN_DIR . 'inc/config.php';
require_once GROW_DATA_PLUGIN_DIR . 'inc/utils.php';
require_once GROW_DATA_PLUGIN_DIR . 'inc/batching.php';
require_once GROW_DATA_PLUGIN_DIR . 'inc/registry.php';
require_once GROW_DATA_PLUGIN_DIR . 'inc/bricks.php';
