<?php

namespace wpai_gravityforms_add_on\gf\fields;

/**
 * Class fieldText
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class fieldText extends Field {

    /**
     *  Field type key
     */
    public $type = 'text';

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
        return $this->getFieldValue();
    }
}