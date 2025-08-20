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
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
// Utilities
// ============================================================================

if ( ! function_exists( 'grow_data_log' ) ) {
	/** Debug logger (enabled when WP_DEBUG true). */
	function grow_data_log( $msg ) {
		if ( GROW_DATA_DEBUG ) {
			error_log( '[GROW_DATA] ' . ( is_string( $msg ) ? $msg : print_r( $msg, true ) ) );
		}
	}
}

/** Returns true for "empty" values while allowing 0 / "0". */
if ( ! function_exists( 'grow_data_is_effectively_empty' ) ) {
	function grow_data_is_effectively_empty( $value ) {
		if ( is_array( $value ) ) {
			return count( $value ) === 0;
		}
		if ( is_object( $value ) ) {
			return empty( (array) $value );
		}
		if ( is_null( $value ) ) {
			return true;
		}
		if ( $value === 0 || $value === '0' ) {
			return false;
		}
		return trim( (string) $value ) === '';
	}
}

/** Lowercase slug with underscores (a-z0-9_). */
if ( ! function_exists( 'grow_data_slugify_title' ) ) {
	function grow_data_slugify_title( $title ) {
		$slug = strtolower( $title );
		$slug = preg_replace( '/[^a-z0-9]+/i', '_', $slug );
		$slug = preg_replace( '/_+/', '_', $slug );
		$slug = trim( $slug, '_' );
		return $slug;
	}
}

/** Output sanitizer aware of Bricks context (text/url/attr/html). */
if ( ! function_exists( 'grow_data_sanitize_output' ) ) {
	function grow_data_sanitize_output( $value, $context = 'text' ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			} else {
				$value = wp_json_encode( $value );
			}
		}
		$value = (string) $value;
		switch ( $context ) {
			case 'url':
				return esc_url( $value );
			case 'attr':
				return esc_attr( $value );
			case 'html':
			case 'text':
			default:
				return wp_kses_post( $value );
		}
	}
}

/** Supported post types (filterable; defaults to all public non-attachment). */
if ( ! function_exists( 'grow_data_get_supported_post_types' ) ) {
	function grow_data_get_supported_post_types() {
		$types = get_post_types( [ 'public' => true ], 'names' );
		unset( $types['attachment'] );
		/**
		 * Filter the list of post types included when generating tags.
		 * @param array $types
		 */
		return apply_filters( 'grow_data_supported_post_types', array_values( $types ) );
	}
}

/** Readable group label for a post's type (used in Bricks picker). */
if ( ! function_exists( 'grow_data_group_label_for_post' ) ) {
	function grow_data_group_label_for_post( $post_id ) {
		$post_type = get_post_type( $post_id );
		$pto       = $post_type ? get_post_type_object( $post_type ) : null;
		$label     = $pto && isset( $pto->labels->singular_name ) ? $pto->labels->singular_name : $post_type;
		if ( ! $label ) {
			$label = GROW_DATA_TAG_GROUP_DEFAULT;
		}
		return $label;
	}
}

/** First available taxonomy term slug for a post; 'none' if not found. */
if ( ! function_exists( 'grow_data_first_taxonomy_slug' ) ) {
	function grow_data_first_taxonomy_slug( $post_id ) {
		$taxes = get_object_taxonomies( get_post_type( $post_id ), 'names' );
		if ( is_array( $taxes ) ) {
			/**
			 * Allow changing taxonomy priority (e.g., ["data-type","category","post_tag"]).
			 * @param string[] $taxes
			 * @param int      $post_id
			 */
			$taxes = apply_filters( 'grow_data_taxonomy_priority', $taxes, $post_id );
			foreach ( $taxes as $tx ) {
				$terms = get_the_terms( $post_id, $tx );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					return sanitize_title( $terms[0]->slug );
				}
			}
		}
		return 'none';
	}
}

/** Build a token from parts. */
if ( ! function_exists( 'grow_data_build_token' ) ) {
	function grow_data_build_token( $field_name, $post_slug, $tax_slug, $post_id ) {
		return '{' . $field_name . GROW_DATA_TOKEN_DELIM . $post_slug . '_' . $tax_slug . '_' . $post_id . '}';
	}
}

/**
 * Parse token into parts.
 * @return array|null [ field_name, post_slug, tax_slug, post_id ]
 */
if ( ! function_exists( 'grow_data_parse_token' ) ) {
	function grow_data_parse_token( $token ) {
		if ( ! is_string( $token ) || strlen( $token ) < 3 || $token[0] !== '{' ) {
			return null;
		}
		$inner = trim( $token, '{}' );
		$pos   = strpos( $inner, GROW_DATA_TOKEN_DELIM );
		if ( $pos === false ) {
			return null;
		}
		$field = substr( $inner, 0, $pos );
		$rest  = substr( $inner, $pos + strlen( GROW_DATA_TOKEN_DELIM ) );
		$parts = explode( '_', $rest );
		if ( count( $parts ) < 2 ) {
			return null;
		}
		$post_id = intval( array_pop( $parts ) );
		$tax     = ( count( $parts ) >= 1 ) ? array_pop( $parts ) : 'none';
		$slug    = implode( '_', $parts ); // may be empty; not used for resolution
		return [ $field, $slug, $tax, $post_id ];
	}
}

/** Fetch ACF value for a field/post, with group subfield fallback and caching */
if ( ! function_exists( 'grow_data_get_value' ) ) {
	function grow_data_get_value( $field_name, $post_id ) {
		$cache_key = "grow_field_{$post_id}_{$field_name}";
		$cached = wp_cache_get($cache_key);
		
		if ($cached !== false) {
			return $cached;
		}

		$value = function_exists( 'get_field' ) ? get_field( $field_name, $post_id ) : null;

		// Fallback: if empty, search group fields for this subfield.
		if ( grow_data_is_effectively_empty( $value ) && function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
			$field_groups = acf_get_field_groups( [ 'post_id' => $post_id ] );
			foreach ( (array) $field_groups as $fg ) {
				$fields = acf_get_fields( isset( $fg['key'] ) ? $fg['key'] : '' );
				if ( ! is_array( $fields ) ) continue;
				foreach ( $fields as $field ) {
					if ( isset( $field['type'] ) && $field['type'] === 'group' && ! empty( $field['sub_fields'] ) ) {
						$gname = isset( $field['name'] ) ? $field['name'] : '';
						if ( ! $gname ) continue;
						$gval = get_field( $gname, $post_id );
						if ( is_array( $gval ) && array_key_exists( $field_name, $gval ) ) {
							$value = $gval[ $field_name ];
							break 2;
						}
					}
				}
			}
		}

		wp_cache_set($cache_key, $value, '', 15 * MINUTE_IN_SECONDS);
		return $value;
	}
}

/** Replace tokens inside an arbitrary string content. */
if ( ! function_exists( 'grow_data_replace_tokens_in_string' ) ) {
	function grow_data_replace_tokens_in_string( $content, $context = 'text' ) {
		if ( ! is_string( $content ) || strpos( $content, GROW_DATA_TOKEN_DELIM ) === false ) {
			return $content;
		}
		return preg_replace_callback(
			'/\{([^{}]+)\}/',
			function( $m ) use ( $context ) {
				$parts = grow_data_parse_token( '{' . $m[1] . '}' );
				if ( ! $parts ) { return $m[0]; }
				list( $field, $_slug, $_tax, $post_id ) = $parts;
				$value = grow_data_get_value( $field, $post_id );
				if ( grow_data_is_effectively_empty( $value ) ) {
					return '';
				}
				return grow_data_sanitize_output( $value, $context );
			},
			$content
		);
	}
}

// ============================================================================
// Registry (builds the picker list)
// ============================================================================
if ( ! function_exists( 'grow_data_build_registry' ) ) {
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
	function grow_data_build_registry( $force = false ) {
		try {
			$cached = get_transient( GROW_DATA_TRANSIENT );
			if ( $cached && ! $force ) {
				return $cached;
			}

			$registry   = [];
			$post_types = grow_data_get_supported_post_types();

			if (count($post_types) > 10) {
				grow_data_log('Too many post types detected, limiting to first 10');
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

			grow_data_log( 'Processing page ' . $page . ' with ' . count( $post_ids ) . ' posts' );

		foreach ( $post_ids as $post_id ) {
			$title      = get_the_title( $post_id );
			$slug       = grow_data_slugify_title( $title );
			$group_lbl  = grow_data_group_label_for_post( $post_id );
			$fields_out = [];

			if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
				$field_groups = acf_get_field_groups( [ 'post_id' => $post_id ] );
				grow_data_log( "Post {$post_id} ('{$title}') has field groups: " . count( $field_groups ) );

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
								if ( ! GROW_DATA_INCLUDE_EMPTY && grow_data_is_effectively_empty( $value ) ) {
									continue;
								}
								$tax_slug = grow_data_first_taxonomy_slug( $post_id );
								$token    = grow_data_build_token( $field_name, $slug, $tax_slug, $post_id );

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
							if ( ! GROW_DATA_INCLUDE_EMPTY && grow_data_is_effectively_empty( $value ) ) {
								continue;
							}
							$tax_slug = grow_data_first_taxonomy_slug( $post_id );
							$token    = grow_data_build_token( $field_name, $slug, $tax_slug, $post_id );

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

			grow_data_log( "Post {$post_id} collected fields: " . count( $fields_out ) );
		}

		$page++;
	} while ($query->max_num_pages >= $page);

		// Hide entries without fields unless INCLUDE_EMPTY
		$registry = array_filter( $registry, function( $entry ) {
			return GROW_DATA_INCLUDE_EMPTY ? true : ! empty( $entry['fields'] );
		});

		grow_data_log( 'Registry posts after filter: ' . count( $registry ) );
		set_transient( GROW_DATA_TRANSIENT, $registry, GROW_DATA_CACHE_TTL );
		return $registry;
		
		} catch (Exception $e) {
			grow_data_log('Error building registry: ' . $e->getMessage());
			return [];
		}
	}
}

// ============================================================================
// Bricks Integration
// ============================================================================

/**
 * Populate the Dynamic Data picker with our tokens.
 * Grouped by post type label for easier navigation.
 */
add_filter( 'bricks/dynamic_tags_list', function( $tags ) {
	$registry = grow_data_build_registry();
	grow_data_log( 'dynamic_tags_list fired. posts in registry: ' . count( $registry ) );

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
}, 10, 1 );

/**
 * Render a single tag when Bricks asks for a tag value.
 */
add_filter( 'bricks/dynamic_data/render_tag', function( $tag, $post, $context = 'text' ) {
	$parts = grow_data_parse_token( $tag );
	if ( ! $parts ) {
		return $tag;
	}
	list( $field, $_slug, $_tax, $post_id ) = $parts;
	$value = grow_data_get_value( $field, $post_id );
	if ( grow_data_is_effectively_empty( $value ) ) {
		return '';
	}
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
	$context = 'text';
	return grow_data_replace_tokens_in_string( $content, $context );
}, 20, 2 );

// ============================================================================
// Cache Invalidation
// ============================================================================

/** Clear registry cache whenever supported posts are saved. */
add_action( 'save_post', function( $post_id, $post, $update ) {
	$type = get_post_type( $post_id );
	if ( $type && in_array( $type, grow_data_get_supported_post_types(), true ) ) {
		delete_transient( GROW_DATA_TRANSIENT );
	}
}, 10, 3 );

/** Clear registry cache when ACF saves a supported post. */
add_action( 'acf/save_post', function( $post_id ) {
	if ( is_numeric( $post_id ) ) {
		$type = get_post_type( intval( $post_id ) );
		if ( $type && in_array( $type, grow_data_get_supported_post_types(), true ) ) {
			delete_transient( GROW_DATA_TRANSIENT );
		}
	}
}, 10, 1 );
