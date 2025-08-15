<?php

namespace wpai_gravityforms_add_on\gf\fields;

use GF_Field;

/**
 * Class FieldFactory.
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
final class FieldFactory {

    /**
     *
     * An array of fields which are doesn't have any functionality
     *
     * @var array
     */
    public static $hiddenFields = array('section', 'captcha', 'html', 'page');

	/**
	 * @param GF_Field $fieldData
	 * @param $form
	 * @param $post
	 * @param string $fieldName
	 * @param bool $fieldParent
	 *
	 * @return bool|FieldEmpty
	 */
    public static function create(GF_Field $fieldData, $form, $post, $fieldName = "", $fieldParent = false) {
        $field = FALSE;
        if ( $fieldData->type ) {
	        $class = '\\wpai_gravityforms_add_on\\gf\\fields\\Field' . str_replace(" ", "", ucwords(str_replace("_", " ", $fieldData->type)));
	        if ( in_array($fieldData->type, self::$hiddenFields) ) {
		        $field = new FieldEmpty($fieldData, $form, $post, $fieldName);
	        } elseif ( class_exists($class) ) {
		        $field = new $class($fieldData, $form, $post, $fieldName, $fieldParent);
	        }
        }
        if ( empty($field) ) {
            $field = new FieldNotSupported($fieldData, $form, $post);
        }
        return $field;
    }
}