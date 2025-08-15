<?php

function pmgi_pmxi_extend_options_main($post_type, $post){
	if ( $post_type == 'gf_entries' ) {
		$controller = new PMGI_Admin_Import();
		$controller->index( $post_type, $post );
	}
}