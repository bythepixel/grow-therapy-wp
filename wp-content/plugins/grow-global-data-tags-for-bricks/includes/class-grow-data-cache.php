<?php
declare(strict_types=1);

/**
 * Handles caching for ACF field values
 */
class Grow_Data_Cache {
	
	private const FIELD_CACHE_TTL = 15 * MINUTE_IN_SECONDS;
	
	/**
	 * Fetch ACF value for a field/post, with group subfield fallback and improved caching.
	 */
	public function get_field_value( $field_name, $post_id ) {
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

		wp_cache_set($cache_key, $value, '', self::FIELD_CACHE_TTL);
		return $value;
	}
}
