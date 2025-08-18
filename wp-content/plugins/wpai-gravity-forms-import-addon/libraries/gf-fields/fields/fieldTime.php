<?php

namespace wpai_gravityforms_add_on\gf\fields;

use GF_Field;

/**
 * Class fieldTime
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class fieldTime extends Field {

    /**
     *  Field type key
     */
    public $type = 'time';

	/**
	 * fieldTime constructor.
	 *
	 * @param GF_Field $field
	 * @param $form
	 * @param $post
	 * @param string $field_name
	 * @param false $parent_field
	 */
	public function __construct( GF_Field $field, $form, $post, $field_name = "", $parent_field = false ) {
		parent::__construct( $field, $form, $post, $field_name, $parent_field );
		$this->tooltip = __('Use any format supported by the PHP strtotime function. That means pretty much any human-readable time will work.', 'wp_all_import_gf_add_on');
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
	    foreach ($values as $i => $d) {
		    if ($d == 'now') {
			    $d = current_time('mysql');
		    } // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
		    $time = strtotime($d);
		    if (FALSE === $time) {
			    $values[$i] = $d;
		    } else {
			    $values[$i] = date('h:i A', $time);
		    }
	    }
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
        return $this->getFieldValue();
    }
}