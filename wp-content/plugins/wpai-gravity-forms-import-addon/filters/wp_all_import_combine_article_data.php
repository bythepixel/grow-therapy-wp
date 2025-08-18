<?php

function pmgi_wp_all_import_combine_article_data($articleData, $custom_type, $import_id, $index) {
	if ( $custom_type == 'gf_entries' ) {
		$import = new PMXI_Import_Record();
		$import->getById($import_id);
		if (!$import->isEmpty()) {
			$form_id = \wpai_gravityforms_add_on\gf\GravityFormsService::get_form_by_id_title($import->options['gravity_form_title']);
			if (!empty($form_id)) {
				$articleData['form_id'] = $form_id;
			}
		}
	}
	return $articleData;
}