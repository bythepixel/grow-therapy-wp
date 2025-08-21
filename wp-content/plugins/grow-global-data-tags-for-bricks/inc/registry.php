<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Build & cache the registry of available tokens (uses batching).
 * Structure:
 * [
 *   post_id => [
 *     'post_title' => '...',
 *     'group'      => 'Post'|'Page'|... (post type label),
 *     'fields'     => [
 *       field_name => [ 'label' => 'Metric Value', 'token' => '{metric_value_grow_data_slug_tax_123}' ],
 *     ],
 *   ],
 * ]
 */
if ( ! function_exists( 'grow_data_build_registry' ) ) {
	function grow_data_build_registry( $force = false ) {
		$cached = get_transient( GROW_DATA_TRANSIENT );
		if ( $cached && ! $force ) { return $cached; }

		$registry   = [];
		$post_types = grow_data_get_supported_post_types();

		$base_args = apply_filters( 'grow_data_query_args', [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$start_time = microtime(true);
		$post_ids = grow_data_iter_post_ids( $base_args );
		$end_time = microtime(true);
		grow_data_log( 'Found posts (total across batches): ' . count( $post_ids ) . ' in ' . round(($end_time - $start_time) * 1000, 2) . 'ms' );

		foreach ( $post_ids as $post_id ) {
			// Safety check for valid post ID
			if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
				continue;
			}
			
			$title      = get_the_title( $post_id );
			$slug       = grow_data_slugify_title( $title );
			$group_lbl  = grow_data_group_label_for_post( $post_id );
			$fields_out = [];

			if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
				$field_groups = acf_get_field_groups( [ 'post_id' => $post_id ] );

				foreach ( (array) $field_groups as $fg ) {
					$key = isset( $fg['key'] ) ? $fg['key'] : '';
					if ( ! $key ) { continue; }
					$fields = acf_get_fields( $key );
					if ( ! is_array( $fields ) ) { continue; }

					foreach ( $fields as $field ) {
						// Group field → iterate sub_fields
						if ( isset( $field['type'] ) && $field['type'] === 'group' && ! empty( $field['sub_fields'] ) ) {
							$group_name = isset( $field['name'] ) ? $field['name'] : '';
							$group_val  = $group_name ? get_field( $group_name, $post_id ) : null;
							foreach ( $field['sub_fields'] as $sub ) {
								$field_name  = isset( $sub['name'] ) ? $sub['name'] : '';
								$field_label = isset( $sub['label'] ) ? $sub['label'] : $field_name;
								if ( ! $field_name ) { continue; }
								$value = ( is_array( $group_val ) && array_key_exists( $field_name, $group_val ) ) ? $group_val[ $field_name ] : null;
								if ( ! GROW_DATA_INCLUDE_EMPTY && grow_data_is_effectively_empty( $value ) ) { continue; }
								$tax_slug = grow_data_first_taxonomy_slug( $post_id );
								$token    = grow_data_build_token( $field_name, $slug, $tax_slug, $post_id );
								$fields_out[ $field_name ] = [ 'label' => $field_label, 'token' => $token ];
							}
						}
						// Non-group field (supported)
						else {
							$field_name  = isset( $field['name'] ) ? $field['name'] : '';
							$field_label = isset( $field['label'] ) ? $field['label'] : $field_name;
							if ( ! $field_name ) { continue; }
							$value = function_exists( 'get_field' ) ? get_field( $field_name, $post_id ) : null;
							if ( ! GROW_DATA_INCLUDE_EMPTY && grow_data_is_effectively_empty( $value ) ) { continue; }
							$tax_slug = grow_data_first_taxonomy_slug( $post_id );
							$token    = grow_data_build_token( $field_name, $slug, $tax_slug, $post_id );
							$fields_out[ $field_name ] = [ 'label' => $field_label, 'token' => $token ];
						}
					}
				}
			}

			$registry[ $post_id ] = [
				'post_title' => $title,
				'group'      => $group_lbl,
				'fields'     => $fields_out,
			];
		}

		$registry = array_filter( $registry, function( $entry ) {
			return GROW_DATA_INCLUDE_EMPTY ? true : ! empty( $entry['fields'] );
		} );

		set_transient( GROW_DATA_TRANSIENT, $registry, GROW_DATA_CACHE_TTL );
		return $registry;
	}
}

/** Cache invalidation */
add_action( 'save_post', function( $post_id, $post, $update ) {
	$type = get_post_type( $post_id );
	if ( $type && in_array( $type, grow_data_get_supported_post_types(), true ) ) {
		delete_transient( GROW_DATA_TRANSIENT );
	}
}, 10, 3 );

add_action( 'acf/save_post', function( $post_id ) {
	if ( is_numeric( $post_id ) ) {
		$type = get_post_type( intval( $post_id ) );
		if ( $type && in_array( $type, grow_data_get_supported_post_types(), true ) ) {
			delete_transient( GROW_DATA_TRANSIENT );
		}
	}
}, 10, 1 );
