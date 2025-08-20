<?php
declare(strict_types=1);

/**
 * Handles Bricks Builder integration
 */
class Grow_Data_Bricks {
	
	private $registry;
	private $cache;
	private $utils;
	
	public function __construct( $registry, $cache, $utils ) {
		$this->registry = $registry;
		$this->cache = $cache;
		$this->utils = $utils;
		$this->init_hooks();
	}
	
	private function init_hooks() {
		add_filter( 'bricks/dynamic_tags_list', [ $this, 'populate_dynamic_tags' ], 10, 1 );
		add_filter( 'bricks/dynamic_data/render_tag', [ $this, 'render_tag' ], 20, 3 );
		add_filter( 'bricks/dynamic_data/render_content', [ $this, 'render_content' ], 20, 3 );
		add_filter( 'bricks/frontend/render_data', [ $this, 'render_frontend_content' ], 20, 2 );
	}
	
	/**
	 * Populate the Dynamic Data picker with our tokens.
	 * Grouped by post type label for easier navigation.
	 */
	public function populate_dynamic_tags( $tags ) {
		$registry = $this->registry->build_registry();
		$this->utils->log( 'dynamic_tags_list fired. posts in registry: ' . count( $registry ) );

		if ( empty( $registry ) ) {
			return $tags;
		}

		foreach ( $registry as $post_id => $entry ) {
			$post_title = $entry['post_title'];
			$group_lbl  = $entry['group'];

			foreach ( $entry['fields'] as $field_name => $meta ) {
				$display_label = sprintf( __( '%1$s: %2$s', 'grow-global-data-tags' ), $post_title, $meta['label'] );

				$tags[] = [
					'name'  => $meta['token'],
					'label' => $display_label,
					'group' => $group_lbl,
				];
			}
		}
		return $tags;
	}
	
	/**
	 * Render a single tag when Bricks asks for a tag value.
	 */
	public function render_tag( $tag, $post, $context = 'text' ) {
		$parts = $this->utils->parse_token( $tag );
		if ( ! $parts ) {
			return $tag;
		}
		list( $field, $_slug, $_tax, $post_id ) = $parts;
		$value = $this->cache->get_field_value( $field, $post_id );
		if ( $this->utils->is_effectively_empty( $value ) ) {
			return '';
		}
		return $this->utils->sanitize_output( $value, $context );
	}
	
	/**
	 * Render any tokens that still exist inside a content string in the builder.
	 */
	public function render_content( $content, $post, $context = 'text' ) {
		return $this->utils->replace_tokens_in_string( $content, $context );
	}
	
	/**
	 * Render any tokens that still exist inside a content string on the frontend.
	 */
	public function render_frontend_content( $content, $post_id = null ) {
		$context = 'text';
		return $this->utils->replace_tokens_in_string( $content, $context );
	}
}
