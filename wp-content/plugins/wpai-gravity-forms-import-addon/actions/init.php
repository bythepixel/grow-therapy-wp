<?php

function pmgi_init() {
	// Register a post type for gravity forms entries
	$labels = array(
	    'name' => __('Gravity Forms Entries', 'wp_all_import_gf_add_on'),
	    'singular_name' => __('Gravity Forms Entry', 'wp_all_import_gf_add_on'),
  	);
	$args = array(
	    'labels' => $labels,
	    'public' => false,
	    'publicly_queryable' => true,
	    'show_ui' => true, 
	    'show_in_menu' => false, 
	    'query_var' => true,	    
	    'rewrite' => array( 'slug' => 'gf_entries' ),
	    'capability_type' => 'post',
	    'has_archive' => false, 
	    'hierarchical' => false,
	    'menu_position' => null,
	    'supports' => array( 'title', 'editor', 'custom-fields' ),
	    'taxonomies' => array()
	);
	register_post_type('gf_entries', $args);
}
