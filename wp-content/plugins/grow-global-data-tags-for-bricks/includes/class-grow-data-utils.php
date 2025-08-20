<?php
declare(strict_types=1);

/**
 * Utility functions for the plugin
 */
class Grow_Data_Utils {
	
	/**
	 * Debug logger (enabled when WP_DEBUG true).
	 */
	public function log( $msg ) {
		if ( GROW_DATA_DEBUG ) {
			error_log( '[GROW_DATA] ' . ( is_string( $msg ) ? $msg : print_r( $msg, true ) ) );
		}
	}
	
	/**
	 * Returns true for "empty" values while allowing 0 / "0".
	 */
	public function is_effectively_empty( $value ) {
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
	
	/**
	 * Lowercase slug with underscores (a-z0-9_).
	 */
	public function slugify_title( $title ) {
		$slug = strtolower( $title );
		$slug = preg_replace( '/[^a-z0-9]+/i', '_', $slug );
		$slug = preg_replace( '/_+/', '_', $slug );
		$slug = trim( $slug, '_' );
		return $slug;
	}
	
	/**
	 * Output sanitizer aware of Bricks context (text/url/attr/html).
	 */
	public function sanitize_output( $value, $context = 'text' ) {
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
	
	/**
	 * Readable group label for a post's type (used in Bricks picker).
	 */
	public function group_label_for_post( $post_id ) {
		$post_type = get_post_type( $post_id );
		$pto       = $post_type ? get_post_type_object( $post_type ) : null;
		$label     = $pto && isset( $pto->labels->singular_name ) ? $pto->labels->singular_name : $post_type;
		if ( ! $label ) {
			$label = GROW_DATA_TAG_GROUP_DEFAULT;
		}
		return $label;
	}
	
	/**
	 * First available taxonomy term slug for a post; 'none' if not found.
	 */
	public function first_taxonomy_slug( $post_id ) {
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
	
	/**
	 * Build a token from parts.
	 */
	public function build_token( $field_name, $post_slug, $tax_slug, $post_id ) {
		return '{' . $field_name . GROW_DATA_TOKEN_DELIM . $post_slug . '_' . $tax_slug . '_' . $post_id . '}';
	}
	
	/**
	 * Parse token into parts.
	 * @return array|null [ field_name, post_slug, tax_slug, post_id ]
	 */
	public function parse_token( $token ) {
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
	
	/**
	 * Replace tokens inside an arbitrary string content.
	 */
	public function replace_tokens_in_string( $content, $context = 'text' ) {
		if ( ! is_string( $content ) || strpos( $content, GROW_DATA_TOKEN_DELIM ) === false ) {
			return $content;
		}
		return preg_replace_callback(
			'/\{([^{}]+)\}/',
			function( $m ) use ( $context ) {
				$parts = $this->parse_token( '{' . $m[1] . '}' );
				if ( ! $parts ) { return $m[0]; }
				list( $field, $_slug, $_tax, $post_id ) = $parts;
				$value = grow_data_get_value( $field, $post_id );
				if ( $this->is_effectively_empty( $value ) ) {
					return '';
				}
				return $this->sanitize_output( $value, $context );
			},
			$content
		);
	}
}
