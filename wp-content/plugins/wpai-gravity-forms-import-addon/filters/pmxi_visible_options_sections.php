<?php

function pmgi_pmxi_visible_options_sections( $sections, $post_type ) {

	if ( 'gf_entries' == $post_type ) return array('settings');

	return $sections;

}