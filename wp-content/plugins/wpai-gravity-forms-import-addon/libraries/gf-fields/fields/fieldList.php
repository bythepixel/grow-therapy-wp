<?php

namespace wpai_gravityforms_add_on\gf\fields;

use GF_Field;

/**
 * Class fieldList
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class fieldList extends Field {

    /**
     *  Field type key
     */
    public $type = 'list';

	public function __construct( GF_Field $field, $form, $post, $field_name = "", $parent_field = false ) {
		parent::__construct( $field, $form, $post, $field_name, $parent_field );
		$this->tooltip = __('Separate multiple values with commas. <b>Multiple Column Lists are not currently supported.</b>', 'wp_all_import_gf_add_on');
	}

	/**
     * Parse field data.
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse( $xpath, $parsingData, $args = array() ) {
        parent::parse( $xpath, $parsingData, $args );
        $values = $this->getByXPath( $xpath );
        $this->setOption('values', $values);
    }

    /**
     * @param $importData
     * @param array $args
     * @return mixed
     */
    public function import( $importData, $args = array() ) {
        $isUpdated = parent::import($importData, $args);
        if ( ! $isUpdated ) {
            return FALSE;
        }
        $values = $this->getFieldValue();
        if (!empty($values)) {
        	$values = explode(',', $values);
        	$values = array_map('trim', $values);
        }
        return $values;
    }
}