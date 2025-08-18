<?php

namespace wpai_gravityforms_add_on\gf\fields;

use GF_Field;

/**
 * Class fieldConsent
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class fieldConsent extends Field {

    /**
     *  Field type key
     */
    public $type = 'consent';

	/**
	 * @var bool
	 */
	public $supportedMultiple = TRUE;

	/**
     * Parse field data.
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse( $xpath, $parsingData, $args = array() ) {

	    parent::parse( $xpath, $parsingData, $args );

	    switch ($this->getOption('is_multiple_field')){
		    case 'yes':
			    $values = array_fill(0, $this->getOption('count'), $this->getOption('multiple_value'));
			    if (is_array($this->getOption('multiple_value'))){
				    $this->setOption('is_multiple', TRUE);
			    }
			    break;
		    default:
		    	$inputs = $this->getGField()->get_entry_inputs();
		    	$values = [];
			    $consent = $this->getByXPath($this->getOption('xpath'));
			    foreach ( $consent as $key => $value) {
			    	foreach ( $inputs as $input ) {
			    		if ( empty($input['isHidden']) && ($value === 'yes' || intval($value) === 1)) {
						    $values[$key] = [
							    $input['id'] => 1,
							    str_replace(".1", ".2", $input['id']) => $this->getGField()->checkboxLabel,
							    str_replace(".1", ".3", $input['id']) => 1
						    ];
					    }
				    }
			    }
			    $this->setOption('is_multiple', TRUE);
			    break;
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