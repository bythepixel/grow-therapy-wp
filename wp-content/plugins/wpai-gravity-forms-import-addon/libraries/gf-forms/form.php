<?php

namespace wpai_gravityforms_add_on\gf\forms;

use wpai_gravityforms_add_on\gf\fields\Field;
use wpai_gravityforms_add_on\gf\fields\FieldEmpty;
use wpai_gravityforms_add_on\gf\fields\FieldFactory;
use wpai_gravityforms_add_on\gf\fields\FieldNotSupported;
use wpai_gravityforms_add_on\gf\fields\fieldSkipException;

require_once( __DIR__ . '/FormInterface.php');

/**
 * Class Form.
 *
 * @package wpai_gravityforms_add_on\gf\forms
 */
class Form implements FormInterface {

    /**
     * @var
     */
    public $post;

    /**
     * @var
     */
    public $form;

    /**
     * @var array
     */
    public $fields = array();

    /**
     * @var array
     */
    public $fieldsData = array();

	/**
	 * Group constructor.
	 *
	 * @param $form
	 * @param $post
	 */
    public function __construct($form, $post) {
        $this->form = $form;
        $this->post = $post;
        $this->initFields();
    }

    /**
     *  Create field instances
     */
    public function initFields(){
        foreach ($this->getFieldsData() as $fieldData){
            $field = FieldFactory::create($fieldData, $this->form, $this->getPost());
            $this->fields[] = $field;
        }
    }

    /**
     * @return mixed
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * @return array
     */
    public function getFieldsData() {
        return $this->form['fields'];
    }

    /**
     * @return array
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * @return mixed
     */
    public function getPost() {
        return $this->post;
    }

    /**
     *  Render group
     */
    public function view() {
        $this->renderHeader();
        foreach ($this->getFields() as $field){
            $field->view();
        }
        $this->renderFooter();
    }

    /**
     *  Render group header
     */
    protected function renderHeader() {
        $filePath = __DIR__ . '/templates/header.php';
        if (is_file($filePath)) {
            extract($this->form);
            include $filePath;
        }
    }

    /**
     *  Render group footer
     */
    protected function renderFooter() {
        $filePath = __DIR__ . '/templates/footer.php';
        if (is_file($filePath)) {
            include $filePath;
        }
    }

    /**
     * @param $parsingData
     * @return array
     */
    public function parse($parsingData) {
        /** @var Field $field */
        foreach ($this->getFields() as $field){
            $xpath = empty($parsingData['import']->options['pmgi']['fields'][$field->getFieldKey()]) ? "" : $parsingData['import']->options['pmgi']['fields'][$field->getFieldKey()];
            $field->parse($xpath, $parsingData);
        }
    }

    /**
     * @param $importData
     * @param array $args
     */
    public function import($importData, $args = array()) {
    	$entry_fields = [];
        /** @var Field $field */
        foreach ($this->getFields() as $field) {
        	if ($field instanceof FieldNotSupported || $field instanceof FieldEmpty) {
        		continue;
	        }
	        $fieldData = $field->getGField();
        	try {
		        $values = $field->import($importData, $args);
		        switch ($fieldData->type) {
			        case 'list':
				        if ( ! is_array( $values ) ) {
					        $values = [ $values ];
				        }
				        $entry_fields[$field->getFieldKey()] = maybe_serialize($values);
				        break;
			        case 'multiselect':
				        $entry_fields[$field->getFieldKey()] = json_encode($values);
				        break;
			        case 'time':
				        $entry_fields[$field->getFieldKey()] = $values;
				        break;
			        default:
				        $inputs = $fieldData->get_entry_inputs();
				        if ( ! empty( $inputs ) ) {
					        foreach ( $inputs as $key => $input ) {
						        if (isset($values[$input['id']])) {
							        $entry_fields[$input['id']] = $values[$input['id']];
						        } elseif (isset($values[$key])) {
							        $entry_fields[$input['id']] = $values[$key];
						        }
					        }
				        } else {
					        $entry_fields[$field->getFieldKey()] = $values;
				        }
				        break;
		        }
	        } catch (fieldSkipException $e) {
		        $importData['logger'] && call_user_func($importData['logger'], sprintf(__('- Field `%s` is skipped attempted to import options', 'wp_all_import_gf_add_on'), $field->getFieldName()));
	        }
        }
        return $entry_fields;
    }

    /**
     * @param $importData
     */
    public function saved_post($importData){
        /** @var Field $field */
        foreach ($this->getFields() as $field){
            $field->saved_post($importData);
        }
    }
}
