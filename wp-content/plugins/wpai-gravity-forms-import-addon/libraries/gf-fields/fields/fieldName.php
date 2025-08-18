<?php

namespace wpai_gravityforms_add_on\gf\fields;

/**
 * Class fieldList
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class fieldName extends Field {

    /**
     *  Field type key
     */
    public $type = 'name';

    /**
     * Parse field data.
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse( $xpath, $parsingData, $args = array() ) {

	    parent::parse( $xpath, $parsingData, $args );

	    $this->setOption('values', $this->getByXPath($this->getOption('xpath')));
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