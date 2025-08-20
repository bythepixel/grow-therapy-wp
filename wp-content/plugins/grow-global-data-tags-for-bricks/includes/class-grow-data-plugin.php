<?php
declare(strict_types=1);

/**
 * Main plugin class that orchestrates all components
 */
class Grow_Data_Plugin {
	
	private $registry;
	private $cache;
	private $utils;
	private $bricks;
	
	public function __construct() {
		$this->init_components();
		$this->init_hooks();
	}
	
	private function init_components() {
		$this->utils = new Grow_Data_Utils();
		$this->registry = new Grow_Data_Registry( $this->utils );
		$this->cache = new Grow_Data_Cache();
		$this->bricks = new Grow_Data_Bricks( $this->registry, $this->cache, $this->utils );
	}
	
	private function init_hooks() {
		// Cache invalidation
		add_action( 'save_post', [ $this, 'invalidate_cache' ], 10, 3 );
		add_action( 'acf/save_post', [ $this, 'invalidate_cache' ], 10, 1 );
	}
	
	/**
	 * Clear registry cache whenever supported posts are saved.
	 */
	public function invalidate_cache( $post_id, $post = null, $update = null ) {
		$type = get_post_type( $post_id );
		if ( $type && in_array( $type, $this->get_supported_post_types(), true ) ) {
			delete_transient( 'grow_global_data_registry_v1' );
		}
	}
	
	/**
	 * Get supported post types for cache invalidation
	 */
	private function get_supported_post_types() {
		$types = get_post_types( [ 'public' => true ], 'names' );
		unset( $types['attachment'] );
		return apply_filters( 'grow_data_supported_post_types', array_values( $types ) );
	}
}
