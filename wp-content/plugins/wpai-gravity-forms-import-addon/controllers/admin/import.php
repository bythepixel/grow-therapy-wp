<?php

use wpai_gravityforms_add_on\gf\forms\Form;

/**
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */
class PMGI_Admin_Import extends PMGI_Controller_Admin {

	/**
	 * @param string $post_type
	 * @param $post
	 */
	public function index( $post_type, $post ) {

		$this->data['post'] =& $post;

		switch ( $post['custom_type'] ) {

			case 'gf_entries':

				$import = new PMXI_Import_Record();

				if (!empty($_GET['id'])) {
					$import->getById($_GET['id']);
				}

				$is_loaded_template = (!empty(PMXI_Plugin::$session->is_loaded_template)) ? PMXI_Plugin::$session->is_loaded_template : FALSE;

				if ($is_loaded_template) {
					$default = PMGI_Plugin::get_default_import_options();
					$template = new PMXI_Template_Record();
					if (!$template->getById($is_loaded_template)->isEmpty()) {
						$options = (!empty($template->options) ? $template->options : array()) + $default;
						if (PMXI_Plugin::$session->first_step['gravity_form_title']) {
							$options['gravity_form_title'] = PMXI_Plugin::$session->first_step['gravity_form_title'];
						}
						if (!$import->isEmpty() && !empty($import->options['gravity_form_title'])) {
							$options['gravity_form_title'] = $import->options['gravity_form_title'];
						}
					}
				} elseif (!$import->isEmpty()) {
					$options = $import->options;
				} else {
					$options = PMXI_Plugin::$session->options;
				}

				$gravity_form_title = FALSE;
				if (!empty(PMXI_Plugin::$session->first_step['gravity_form_title'])) {
					$gravity_form_title = PMXI_Plugin::$session->first_step['gravity_form_title'];
				} elseif ( ! empty($options['gravity_form_title']) ) {
					$gravity_form_title = $options['gravity_form_title'];
				}

				if ( ! empty($gravity_form_title) ) {
					$form_id = \wpai_gravityforms_add_on\gf\GravityFormsService::get_form_by_id_title($gravity_form_title);
					if ($form_id) {
						$form_data = GFAPI::get_form($form_id);
						$this->data['form'] = new Form($form_data, $options);
					}
				}

				$this->render('admin/import/entries/index');
				break;
			default:
				# code...
				break;
		}
	}

	/**
	 * @param false $isWizard
	 * @param array $post
	 */
	public function options( $isWizard = false, $post = array() ) {

		$this->data['isWizard'] = $isWizard;

		$this->data['post'] =& $post;

		$gravity_form_title = FALSE;
		if ( ! empty($post['gravity_form_title']) ) {
			$gravity_form_title = $post['gravity_form_title'];
		}

		if ( ! empty($gravity_form_title) ) {
			$form_id = \wpai_gravityforms_add_on\gf\GravityFormsService::get_form_by_id_title($gravity_form_title);
			if ($form_id) {
				$form_data = GFAPI::get_form($form_id);
				$this->data['form'] = new Form($form_data, $post);
			}
		}

		$this->data['existing_meta_keys'] = [];

		$this->render();
	}

	/**
	 * @param false $isWizard
	 * @param array $post
	 */
	public function confirm( $isWizard = false, $post = array() ) {

		$this->data['isWizard'] = $isWizard;

		$this->data['post'] =& $post;

		$this->render();
	}
}
