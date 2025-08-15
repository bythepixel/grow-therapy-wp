<?php

namespace wpai_gravityforms_add_on\gf\forms;

/**
 * Interface FormInterface
 * @package wpai_gravityforms_add_on\gf\forms
 */
interface FormInterface{

    /**
     * @return mixed
     */
    public function initFields();

    /**
     * @return mixed
     */
    public function view();

    /**
     * @param $parsingData
     * @return mixed
     */
    public function parse($parsingData);

    /**
     * @param $importData
     * @return mixed
     */
    public function saved_post($importData);

}