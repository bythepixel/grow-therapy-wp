<?php

namespace wpai_gravityforms_add_on\gf\fields;

use GF_Field;

require_once( __DIR__ . '/fieldInterface.php');

define('PMGI_FIELDS_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));

/**
 * Class Field
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
abstract class Field implements FieldInterface {

    /**
     * field type
     */
    public $type;

    /**
     * @var array
     */
    public $data;

    /**
     * @var bool
     */
    public $supportedMultiple = false;

    /**
     * @var array
     */
    public $parsingData;

    /**
     * @var array
     */
    public $importData;

    /**
     * @var array
     */
    public $options = array();

    /**
     * @var Field
     */
    public $parent;

    /**
     * @var array
     */
    public $subFields = array();

	/**
	 * @var string
	 */
	public $tooltip;

	/**
	 * Field constructor.
	 *
	 * @param GF_Field $field
	 * @param $form
	 * @param $post
	 * @param string $field_name
	 * @param bool $parent_field
	 */
    public function __construct( GF_Field $field, $form, $post, $field_name = "", $parent_field = false ) {
        $this->data = [
	        'field'      => $field,
	        'form'       => $form,
	        'post'       => $post,
	        'field_name' => $field_name
        ];
        $this->setParent($parent_field);
        $this->data = array_merge($this->data, $this->getFieldData());
        $this->initSubFields();
    }

	/**
	 * @return string
	 */
	public function get_tooltip() {
		return $this->tooltip;
	}

	/**
	 * @return GF_Field
	 */
	public function getGField() {
    	return $this->data['field'];
    }

    /**
     *  Create sub field instances
     */
    public function initSubFields() {
        // Get sub fields configuration.
	    $this->subFields = [];
    }

    /**
     * @return array
     */
    protected function getFieldData() {

        $data = [];

        $field = $this->getGField();
        $post  = $this->getData('post');
        $form  = $this->getData('form');

        $inputs = $field->get_entry_inputs();
        if (!empty($inputs) && ! in_array( $this->type, [ 'checkbox', 'consent' ] )) {
        	foreach ($inputs as $input) {
		        $data['current_field'][$input['id']] = empty($post['pmgi']['fields'][$input['id']]) ? false : $post['pmgi']['fields'][$input['id']];
	        }
        } else {
	        $data['current_field'] = empty($post['pmgi']['fields'][$field->id]) ? false : $post['pmgi']['fields'][$field->id];
        }

	    $data['current_is_multiple_field_value'] = isset($field->id) && isset($post['pmgi']['is_multiple_field_value'][$field->id]) ? $post['pmgi']['is_multiple_field_value'][$field->id] : false;

	    if ( ! empty( $inputs ) ) {
		    foreach ( $inputs as $input ) {
			    $data['current_multiple_value'][$input['id']] = isset($field->id) && isset($post['pmgi']['multiple_value'][$input['id']]) ? $post['pmgi']['multiple_value'][$input['id']] : false;
		    }
	    } else {
		    $data['current_multiple_value'] = isset($field->id) && isset($post['pmgi']['multiple_value'][$field->id]) ? $post['pmgi']['multiple_value'][$field->id] : false;
	    }

        return $data;
    }

    /**
     * @param $xpath
     * @param $parsingData
     * @param array $args
     * @return void
     */
    public function parse($xpath, $parsingData, $args = array()) {

        $this->parsingData = $parsingData;

        $defaults = [
	        'field_path'            => '',
	        'xpath_suffix'          => '',
	        'repeater_count_rows'   => 0,
	        'inside_repeater'       => false
        ];

        $args = array_merge($defaults, $args);

        $field = $this->getGField();
	    $form  = $this->getData('form');

        $isMultipleField = (isset($parsingData['import']->options['pmgi']['is_multiple_field_value'][$field->id])) ? $parsingData['import']->options['pmgi']['is_multiple_field_value'][$field->id] : FALSE;

	    $inputs = $field->get_entry_inputs();
	    $multipleValue = FALSE;
        if ($isMultipleField) {
	        if ( ! empty($inputs) ) {
		        $multipleValue = [];
		        foreach ($inputs as $input) {
			        $multipleValue[$input['id']] = empty($parsingData['import']->options['pmgi']['multiple_value'][$input['id']]) ? false : $parsingData['import']->options['pmgi']['multiple_value'][$input['id']];
		        }
	        } else {
		        $multipleValue = (isset($parsingData['import']->options['pmgi']['multiple_value'][$field->id])) ? $parsingData['import']->options['pmgi']['multiple_value'][$field->id] : FALSE;
	        }
        } else {
	        if ( ! empty($inputs) ) {
		        $xpath = [];
		        foreach ($inputs as $input) {
			        $xpath[$input['id']] = empty($parsingData['import']->options['pmgi']['fields'][$input['id']]) ? false : $parsingData['import']->options['pmgi']['fields'][$input['id']];
		        }
	        } else {
		        $xpath = (isset($parsingData['import']->options['pmgi']['fields'][$field->id])) ? $parsingData['import']->options['pmgi']['fields'][$field->id] : FALSE;
	        }
        }

        $this->setOption('base_xpath', $parsingData['xpath_prefix'] . $parsingData['import']->xpath . $args['xpath_suffix']);
        $this->setOption('xpath', $xpath);
        $this->setOption('is_multiple_field', $isMultipleField);
        $this->setOption('multiple_value', $multipleValue);
        $this->setOption('count', ($args['repeater_count_rows']) ? $args['repeater_count_rows'] : $parsingData['count']);
        $this->setOption('values', array_fill(0, $this->getOption('count'), ""));
        $this->setOption('field_path', $args['field_path']);
    }

    /**
     * @param $importData
     * @param array $args
     * @return bool
     */
    public function import($importData, $args = array()) {

        $defaults = [
	        'container_name'  => '',
	        'parent_repeater' => ''
        ];

        $field = $this->getGField();

        $args = array_merge($defaults, $args);

        $this->importData = array_merge($importData, $args);

        // If update is not allowed.
        if ( ! empty($this->importData['articleData']['ID']) && ! \pmgi_is_gf_update_allowed($field, $this->parsingData['import']->options) ) {
            throw new fieldSkipException();
        }

	    $this->parsingData['logger'] and call_user_func( $this->parsingData['logger'], sprintf(__('- Importing field `%s`', 'wp_all_import_gf_add_on'), $this->getFieldName()) );

        return TRUE;
    }

    /**
     * @param $importData
     */
    public function saved_post( $importData ) {}

    /**
     *  Render field
     */
    public function view() {
	    $this->data['tooltip'] = $this->get_tooltip();
        $this->renderHeader();
        extract($this->data);
        $fields = $this->getSubFields();
        if ( $this->supportedMultiple ) {
	        $fieldDir = PMGI_FIELDS_ROOT_DIR . '/views/'. $this->type;
	        $filePath = $fieldDir . DIRECTORY_SEPARATOR . $this->type . '.php';
	        if (is_file($filePath)) {
		        // Render field header.
		        $header = $fieldDir . DIRECTORY_SEPARATOR . 'header.php';
		        if (file_exists($header) && is_readable($header)) {
			        include $header;
		        }
		        // Render field.
		        include $filePath;
		        // Render field footer.
		        $footer = $fieldDir . DIRECTORY_SEPARATOR . 'footer.php';
		        if (file_exists($footer) && is_readable($footer)) {
			        include $footer;
		        }
	        }
        } else {
	        $filePath = __DIR__ . '/views/'. $this->type .'.php';
	        if (is_file($filePath)) {
		        include $filePath;
	        }
        }
        $this->renderFooter();
    }

        /**
         *  Render field header
         */
        protected function renderHeader(){
            $filePath = __DIR__ . '/templates/header.php';
            if (is_file($filePath)) {
                extract($this->data);
                include $filePath;
            }
        }

        /**
         *  Render field footer
         */
        protected function renderFooter(){
            $filePath = __DIR__ . '/templates/footer.php';
            if (is_file($filePath)) {
                include $filePath;
            }
        }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return Field|bool
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @param Field|bool $parent
     */
    public function setParent($parent) {
        $this->parent = $parent;
    }

    /**
     * @param $option
     * @return bool|mixed
     */
    public function getData($option){
        return $this->data[ $option ] ?? false;
    }

    /**
     * @param $option
     * @param $value
     */
    public function setData($option, $value){
        $this->data[$option] = $value;
    }

    /**
     * @param $option
     * @return bool|mixed
     */
    public function getOption($option){
        return $this->options[ $option ] ?? false;
    }

    /**
     * @param $option
     * @param $value
     */
    public function setOption($option, $value){
        $this->options[$option] = $value;
    }

	/**
	 * @param $xpath
	 * @param string $suffix
	 *
	 * @return array
	 * @throws \XmlImportException
	 */
    public function getByXPath($xpath, $suffix = '') {
        $values = array_fill(0, $this->getOption('count'), "");
        if ( is_array( $xpath ) ) {
	        foreach ( $values as $value_key => $value ) {
		        $values[$value_key] = [];
	        }
        	foreach ( $xpath as $item_key => $xpath_item ) {
		        $file = false;
		        $item = array_fill(0, $this->getOption('count'), "");
		        if ( $xpath_item != "" ) {
			        $item = \XmlImportParser::factory($this->parsingData['xml'], $this->getOption('base_xpath') . $suffix, $xpath_item, $file)->parse();
		        }
		        @unlink($file);
		        foreach ( $values as $value_key => $value ) {
		        	$values[$value_key][$item_key] = $item[$value_key];
		        }
	        }
        } elseif ($xpath != "") {
            $file = false;
            $values = \XmlImportParser::factory($this->parsingData['xml'], $this->getOption('base_xpath') . $suffix, $xpath, $file)->parse();
            @unlink($file);
        }
        return $values;
    }

    /**
     * @return mixed
     */
    public function getParsingData() {
        return $this->parsingData;
    }

    /**
     * @return mixed
     */
    public function getImportData() {
        return $this->importData;
    }

    /**
     * @return mixed
     */
    public function getPostIndex(){
        return $this->importData['i'];
    }

    /**
     * @return mixed
     */
    public function getPostID(){
        return $this->importData['pid'];
    }

    /**
     * @return string
     */
    public function getFieldName() {
        return $this->getGField()->label;
    }

    /**
     * @param $fieldName
     */
    public function setFieldInputName($fieldName){
        $this->data['field_name'] = $fieldName;
        $this->data = array_merge($this->data, $this->getFieldData());
    }

    /**
     * @return string
     */
    public function getFieldKey(){
        return $this->data['field']->id;
    }

    /**
     * @return string
     */
    public function getFieldLabel(){
        return $this->data['field']->label;
    }

    /**
     * @return mixed
     */
    public function getFieldValue() {

	    $values = $this->options['values'];

	    if ( isset( $this->options['is_multiple_field']) && $this->options['is_multiple_field'] == 'yes' ) {
		    $value = array_shift($values);
	    } else {
		    $value = isset($values[$this->getPostIndex()]) ? $values[$this->getPostIndex()] : '';
	    }

        return is_array($value) ? array_map('trim', $value) : trim($value);
    }

    /**
     * @param $option
     * @return null|mixed
     */
    public function getFieldOption($option){
        return $this->data['field'][ $option ] ?? null;
    }

    /**
     * @param $option
     * @return null|mixed
     */
    public function getImportOption($option){
        $importData = $this->getImportData();
        return $importData['import']->options[ $option ] ?? null;
    }

    /**
     * @return mixed
     */
    public function getImportType(){
        $importData = $this->getImportData();
        return $importData['import']->options['custom_type'];
    }

    /**
     * @return mixed
     */
    public function getTaxonomyType(){
        $importData = $this->getImportData();
        return $importData['import']->options['taxonomy_type'];
    }

    /**
     * @return mixed
     */
    public function getLogger(){
        return $this->parsingData['logger'];
    }

    /**
     * @return array
     */
    public function getSubFields(){
        return $this->subFields;
    }

    /**
     * @return bool
     */
    public function isNotEmpty() {
        return (bool) $this->getCountValues();
    }

    /**
     * @return int
     */
    public function getCountValues(){
        $parents = $this->getParents();
        $value = $this->getOriginalFieldValueAsString();
        if (!empty($parents) && !$this->isEmptyValue($value) && !is_array($value)){
            $parentIndex = false;
            foreach ($parents as $key => $parent) {
                if ($parentIndex !== false){
                    $value = $value[$parentIndex];
                }
                if ($parent['delimiter'] !== FALSE) {
                    $value = explode($parent['delimiter'], $value);
                    if (is_array($value)) {
                        $value = array_filter($value);
                    }
                    $parentIndex = $parent['index'];
                }
            }
        }
        return is_array($value) ? count($value) : !$this->isEmptyValue($value);
    }

    /**
     *
     * Helper function to detect is provided field is empty or not
     *
     * @param $value
     * @return bool
     */
    protected function isEmptyValue($value){
        return ( is_null($value) || $value === false || $value === "");
    }

    /**
     * @return mixed
     */
    public function getOriginalFieldValueAsString(){
        $values = $this->options['values'];
        return isset($values[$this->getPostIndex()]) ? $values[$this->getPostIndex()] : '';
    }

    /**
     * @return array
     */
    protected function getParents(){
        $field = $this;
        $parents = array();
        do{
            $parent = $field->getParent();
            if ($parent){
                switch ($parent->type){
                    case 'repeater':
                        if ($parent->getMode() == 'fixed' || $parent->getMode() == 'csv' && $parent->getDelimiter()){
                            $parents[] = array(
                                'delimiter' => $parent->getDelimiter(),
                                'index'     => $parent->getRowIndex()
                            );
                        }
                        break;
                    default:
                        break;
                }
                $field = $parent;
            }
        }
        while($parent);

        return array_reverse($parents);
    }

    /**
     * @return array
     */
    public function getParsedData() {
        $field = $this->getData('field');
        return [
	        'type' => $field->get_input_type(),
	        'name' => $field->label,
	        'values' => $this->getOption('values'),
	        'is_multiple' => $this->getOption('is_multiple'),
	        'is_ignore_empties' => $this->getOption('is_ignore_empties'),
	        'xpath' => $this->getOption('xpath'),
	        'id' => $field->id
        ];
    }
}