<?php

function pmgi_pmxi_visible_confirm_sections( $sections, $post_type ) {

	// render order's template only for bundle and import with WP All Import featured
	if ( 'gf_entries' == $post_type ) return array();
	
	return $sections;
}