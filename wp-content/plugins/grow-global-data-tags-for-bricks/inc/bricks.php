<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Populate the Bricks Dynamic Data picker with our tokens,
 * grouped by post type label (easier navigation).
 */
add_filter( 'bricks/dynamic_tags_list', function( $tags ) {
	$registry = grow_data_build_registry();
	if ( empty( $registry ) ) { return $tags; }

	foreach ( $registry as $post_id => $entry ) {
		$post_title = $entry['post_title'];
		$group_lbl  = $entry['group'];

		foreach ( $entry['fields'] as $field_name => $meta ) {
			$display_label = sprintf( __( '%1$s: %2$s', 'grow-global-data-tags' ), $post_title, $meta['label'] );
			$tags[] = [
				// Clicking inserts this token:
				'name'  => $meta['token'],
				// Shown in the picker:
				'label' => $display_label,
				// Group = post type singular label:
				'group' => $group_lbl,
			];
		}
	}
	return $tags;
}, 10, 1 );

/**
 * Render a single tag when Bricks asks for a tag value.
 */
add_filter( 'bricks/dynamic_data/render_tag', function( $tag, $post, $context = 'text' ) {
	$parts = grow_data_parse_token( $tag );
	if ( ! $parts ) return $tag;
	list( $field, $_slug, $_tax, $post_id ) = $parts;
	$value = grow_data_get_value( $field, $post_id );
	if ( grow_data_is_effectively_empty( $value ) ) return '';
	return grow_data_sanitize_output( $value, $context );
}, 20, 3 );

/**
 * Render any tokens that still exist inside a content string in the builder.
 */
add_filter( 'bricks/dynamic_data/render_content', function( $content, $post, $context = 'text' ) {
	return grow_data_replace_tokens_in_string( $content, $context );
}, 20, 3 );

/**
 * Render any tokens that still exist inside a content string on the frontend.
 */
add_filter( 'bricks/frontend/render_data', function( $content, $post_id = null ) {
	$context = 'text'; // Bricks doesn't pass a context here
	return grow_data_replace_tokens_in_string( $content, $context );
}, 20, 2 );
