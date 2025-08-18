<?php 
function pmgi_pmxi_options_tab( $isWizard, $post ){

	if ( $post['custom_type'] == 'gf_entries' ):

		$controller = new PMGI_Admin_Import();

		$controller->options( $isWizard, $post );

	endif;

}
