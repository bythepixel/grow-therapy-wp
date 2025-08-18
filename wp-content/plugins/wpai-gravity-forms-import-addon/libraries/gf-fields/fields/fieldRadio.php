<?php

namespace wpai_gravityforms_add_on\gf\fields;

/**
 * Class FieldRadio.
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class FieldRadio extends Field {

	/**
	 * @var bool
	 */
	public $supportedMultiple = TRUE;

    /**
     *  Field type key
     */
    public $type = 'radio';

    /**
     *
     * Parse field data
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse($xpath, $parsingData, $args = array()) {
        parent::parse($xpath, $parsingData, $args);
        if ("yes" == $this->getOption('is_multiple_field')) {
            $value = $this->getOption('multiple_value');
            if (!is_array($value)) {
                $values = array_fill(0, $this->getOption('count'), $value);
            } else {
                $values = array();
                foreach ($value as $single_value) {
                    $values[] = array_fill(0, $this->getOption('count'), $single_value);
                }
                $this->setOption('is_multiple', TRUE);
            }
        } else {
            $values = $this->getByXPath($xpath);
        }
        $this->setOption('values', $values);
    }

    /**
     * @param $importData
     * @param array $args
     * @return mixed
     */
    public function import($importData, $args = array()) {
        $isUpdated = parent::import($importData, $args);
        if ( ! $isUpdated ) {
            return FALSE;
        }
	    return $this->getFieldValue();
    }

    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue() {

        $value = parent::getFieldValue();

        $parsedData = $this->getParsedData();

        if ($parsedData['is_multiple']) {
            $value = (!empty($value) && is_array($value)) ? $value : array();
        }
        return $value;
    }

    /**
     *
     * If radio field is not set with XPath then it means it has empty value
     * in case it is inside repeater field.
     *
     * @return int
     */
    public function getCountValues() {
        $count = 0;
        if ("yes" !== $this->getOption('is_multiple_field')){
            $count = parent::getCountValues();
        }
        return $count;
    }
}