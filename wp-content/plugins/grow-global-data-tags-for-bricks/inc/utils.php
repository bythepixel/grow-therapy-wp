<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Debug logger (enabled when WP_DEBUG true). */
if ( ! function_exists( 'grow_data_log' ) ) {
	function grow_data_log( $msg ) {
		if ( GROW_DATA_DEBUG ) {
			error_log( '[GROW_DATA] ' . ( is_string( $msg ) ? $msg : print_r( $msg, true ) ) );
		}
	}
}

/** Returns true for "empty" values while allowing 0 / "0". */
if ( ! function_exists( 'grow_data_is_effectively_empty' ) ) {
	function grow_data_is_effectively_empty( $value ) {
		if ( is_array( $value ) ) return count( $value ) === 0;
		if ( is_object( $value ) ) return empty( (array) $value );
		if ( is_null( $value ) ) return true;
		if ( $value === 0 || $value === '0' ) return false;
		return trim( (string) $value ) === '';
	}
}

/** Lowercase slug with underscores (a-z0-9_). */
if ( ! function_exists( 'grow_data_slugify_title' ) ) {
	function grow_data_slugify_title( $title ) {
		$slug = strtolower( $title );
		$slug = preg_replace( '/[^a-z0-9]+/i', '_', $slug );
		$slug = preg_replace( '/_+/', '_', $slug );
		return trim( $slug, '_' );
	}
}

/** Output sanitizer aware of Bricks context (text/url/attr/html). */
if ( ! function_exists( 'grow_data_sanitize_output' ) ) {
	function grow_data_sanitize_output( $value, $context = 'text' ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : wp_json_encode( $value );
		}
		$value = (string) $value;
		switch ( $context ) {
			case 'url':  return esc_url( $value );
			case 'attr': return esc_attr( $value );
			case 'html':
			case 'text':
			default:     return wp_kses_post( $value );
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
		return $label ? $label : GROW_DATA_TAG_GROUP_DEFAULT;
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
		if ( ! is_string( $token ) || strlen( $token ) < 3 || $token[0] !== '{' ) return null;
		$inner = trim( $token, '{}' );
		$pos   = strpos( $inner, GROW_DATA_TOKEN_DELIM );
		if ( $pos === false ) return null;
		$field = substr( $inner, 0, $pos );
		$rest  = substr( $inner, $pos + strlen( GROW_DATA_TOKEN_DELIM ) );
		$parts = explode( '_', $rest );
		if ( count( $parts ) < 2 ) return null;
		$post_id = intval( array_pop( $parts ) );
		$tax     = ( count( $parts ) >= 1 ) ? array_pop( $parts ) : 'none';
		$slug    = implode( '_', $parts );
		return [ $field, $slug, $tax, $post_id ];
	}
}

/** Fetch ACF value for a field/post, with group subfield fallback and static cache. */
if ( ! function_exists( 'grow_data_get_value' ) ) {
	function grow_data_get_value( $field_name, $post_id ) {
		static $cache = [];
		$key = $post_id . '|' . $field_name;
		if ( array_key_exists( $key, $cache ) ) return $cache[ $key ];

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

		$cache[ $key ] = $value;
		return $value;
	}
}

/** Replace tokens inside an arbitrary string content. */
if ( ! function_exists( 'grow_data_replace_tokens_in_string' ) ) {
	function grow_data_replace_tokens_in_string( $content, $context = 'text' ) {
		if ( ! is_string( $content ) || strpos( $content, GROW_DATA_TOKEN_DELIM ) === false ) return $content;
		return preg_replace_callback(
			'/\{([^{}]+)\}/',
			function( $m ) use ( $context ) {
				$parts = grow_data_parse_token( '{' . $m[1] . '}' );
				if ( ! $parts ) return $m[0];
				list( $field, $_slug, $_tax, $post_id ) = $parts;
				$value = grow_data_get_value( $field, $post_id );
				if ( grow_data_is_effectively_empty( $value ) ) return '';
				return grow_data_sanitize_output( $value, $context );
			},
			$content
		);
	}
}
