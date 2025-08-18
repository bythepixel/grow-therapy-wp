<?php

/**
 * @param GF_Field $field
 * @param $options
 *
 * @return mixed
 */
function pmgi_is_gf_update_allowed( GF_Field $field, $options ) {

    if ( $options['is_keep_former_posts'] == 'yes' ) {
	    return pmgi_return_false($field, $options);
    }

    if ($options['update_all_data'] == 'yes') {
	    return pmgi_return_true($field, $options);
    }

    if (empty($options['pmgi_is_update_entry_fields']) || empty($options['pmgi_is_update_entry_fields_list'][$field->id])) {
	    return pmgi_return_false($field, $options);
    }

    return pmgi_return_true($field, $options);
}

/**
 * @param GF_Field $field
 * @param $options
 *
 * @return mixed|void
 */
function pmgi_return_true(GF_Field $field, $options) {
    return apply_filters('pmgi_is_gf_update_allowed', true, $field, $options);
}

/**
 * @param GF_Field $field
 * @param $options
 *
 * @return mixed|void
 */
function pmgi_return_false(GF_Field $field, $options) {
    return apply_filters('pmgi_is_gf_update_allowed', false, $field, $options);
}