<?php

namespace wpai_gravityforms_add_on\gf\fields;

/**
 * Class FieldCheckbox
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class FieldCheckbox extends Field {

	/**
	 * @var bool
	 */
	public $supportedMultiple = TRUE;

    /**
     *  Field type key
     */
    public $type = 'checkbox';

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

        switch ($this->getOption('is_multiple_field')){
            case 'yes':
                $values = array_fill(0, $this->getOption('count'), $this->getOption('multiple_value'));
                if (is_array($this->getOption('multiple_value'))){
                    $this->setOption('is_multiple', TRUE);
                }
                break;
            default:
                $values = $this->getByXPath($this->getOption('xpath'));
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

        if ($parsedData['is_multiple'] && $value !== '') {
            $value = is_array($value) ? $value : explode(',', $value);
            $value = array_map('trim', $value);
        }
        return $value;
    }
}