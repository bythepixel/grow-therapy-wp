<?php

namespace wpai_gravityforms_add_on\gf;

use GFFormsModel;

/**
 * Class GravityFormsService
 * @package wpai_gravityforms_add_on\gf
 */
class GravityFormsService {

	/**
	 * Gets a form based on the form title.
	 *
	 * @since  Unknown
	 * @access public
	 * @global $wpdb
	 *
	 * @uses GFFormsModel::get_form_table_name()
	 *
	 * @param int  $form_title The title of the form to get.
	 * @param bool $allow_trash Optional. Set to true to allow trashed results. Defaults to false.
	 *
	 * @return bool
	 */
	public static function get_form_by_id_title( $form_title, $allow_trash = false ) {
		global $wpdb;
		$table_name   = GFFormsModel::get_form_table_name();
		$trash_clause = $allow_trash ? '' : 'AND is_trash = 0';
		$results      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE title=%s {$trash_clause}", $form_title ) );

		return isset( $results[0] ) ? $results[0]->id : false;
	}

}