<?php

function pmgi_pmxi_visible_template_sections( $sections, $post_type ) {

	if ( 'gf_entries' == $post_type) return array('main');

	return $sections;

}