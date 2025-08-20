<?php
declare(strict_types=1);

/**
 * Handles building and caching the field registry
 */
class Grow_Data_Registry {
	
	private const CACHE_KEY = 'grow_global_data_registry_v1';
	private const CACHE_TTL = 5 * MINUTE_IN_SECONDS;
	private $utils;
	
	public function __construct( $utils ) {
		$this->utils = $utils;
	}
	
	/**
	 * Build & cache the registry of available tags.
	 * Structure:
	 * [
	 *   post_id => [
	 *     'post_title' => '...',
	 *     'group'      => 'Post'|'Page'|... (post type label),
	 *     'fields'     => [
	 *       field_name => [
	 *         'label' => 'Metric Value',
	 *         'token' => '{metric_value_grow_data_slug_tax_123}',
	 *       ],
	 *     ],
	 *   ],
	 * ]
	 */
	public function build_registry( $force = false ) {
		try {
			$cached = get_transient( self::CACHE_KEY );
			if ( $cached && ! $force ) {
				return $cached;
			}

			$registry   = [];
			$post_types = $this->get_supported_post_types();

			if (count($post_types) > 10) {
				$this->utils->log('Too many post types detected, limiting to first 10');
				$post_types = array_slice($post_types, 0, 10);
			}

			$args = apply_filters( 'grow_data_query_args', [
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => false,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			] );

			$page = 1;
			do {
				$args['paged'] = $page;
				$query = new WP_Query($args);
				$post_ids = $query->posts;
				
				if (empty($post_ids)) {
					break;
				}

				$this->utils->log( 'Processing page ' . $page . ' with ' . count( $post_ids ) . ' posts' );

				foreach ( $post_ids as $post_id ) {
					$title      = get_the_title( $post_id );
								$slug       = $this->utils->slugify_title( $title );
			$group_lbl  = $this->utils->group_label_for_post( $post_id );
					$fields_out = [];

					if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
						$field_groups = acf_get_field_groups( [ 'post_id' => $post_id ] );
						$this->utils->log( "Post {$post_id} ('{$title}') has field groups: " . count( $field_groups ) );

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
										if ( ! GROW_DATA_INCLUDE_EMPTY && $this->utils->is_effectively_empty( $value ) ) {
											continue;
										}
										$tax_slug = $this->utils->first_taxonomy_slug( $post_id );
										$token    = $this->utils->build_token( $field_name, $slug, $tax_slug, $post_id );

										$fields_out[ $field_name ] = [
											'label' => $field_label,
											'token' => $token,
										];
									}
								}
								// Non-group field (supported)
								else {
									$field_name  = isset( $field['name'] ) ? $field['name'] : '';
									$field_label = isset( $field['label'] ) ? $field['label'] : $field_name;
									if ( ! $field_name ) { continue; }
									$value = function_exists( 'get_field' ) ? get_field( $field_name, $post_id ) : null;
									if ( ! GROW_DATA_INCLUDE_EMPTY && $this->utils->is_effectively_empty( $value ) ) {
										continue;
									}
																	$tax_slug = $this->utils->first_taxonomy_slug( $post_id );
								$token    = $this->utils->build_token( $field_name, $slug, $tax_slug, $post_id );

									$fields_out[ $field_name ] = [
										'label' => $field_label,
										'token' => $token,
									];
								}
							}
						}
					}

					$registry[ $post_id ] = [
						'post_title' => $title,
						'group'      => $group_lbl,
						'fields'     => $fields_out,
					];

					$this->utils->log( "Post {$post_id} collected fields: " . count( $fields_out ) );
				}

				$page++;
			} while ($query->max_num_pages >= $page);

			// Hide entries without fields unless INCLUDE_EMPTY
			$registry = array_filter( $registry, function( $entry ) {
				return GROW_DATA_INCLUDE_EMPTY ? true : ! empty( $entry['fields'] );
			});

			$this->utils->log( 'Registry posts after filter: ' . count( $registry ) );
			set_transient( self::CACHE_KEY, $registry, self::CACHE_TTL );
			return $registry;
			
		} catch (Exception $e) {
			$this->utils->log('Error building registry: ' . $e->getMessage());
			return [];
		}
	}
	
	/**
	 * Supported post types (filterable; defaults to all public non-attachment).
	 */
	private function get_supported_post_types() {
		$types = get_post_types( [ 'public' => true ], 'names' );
		unset( $types['attachment'] );
		/**
		 * Filter the list of post types included when generating tags.
		 * @param array $types
		 */
		return apply_filters( 'grow_data_supported_post_types', array_values( $types ) );
	}
}
