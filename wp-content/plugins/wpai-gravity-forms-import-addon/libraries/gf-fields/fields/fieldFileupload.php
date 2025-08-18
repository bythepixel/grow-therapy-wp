<?php

namespace wpai_gravityforms_add_on\gf\fields;

use GF_Field;
use GFFormsModel;

/**
 * Class fieldFileupload
 *
 * @package wpai_gravityforms_add_on\gf\fields
 */
class fieldFileupload extends Field {

    /**
     *  Field type key
     */
    public $type = 'fileupload';

    public function __construct(GF_Field $field, $form, $post, $field_name = "", $parent_field = false) {
        parent::__construct($field, $form, $post, $field_name, $parent_field);
        $this->tooltip = __('For Multi-File Upload enabled fields multiple file references should be separated by commas.', 'wp_all_import_gf_add_on');
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
        return $this->getFieldValue();
    }

	/**
	 * @return array|mixed
	 */
	public function getFieldValue() {
		$form  = $this->getData('form');
		$value = parent::getFieldValue();
        $is_multiple_files = $this->getGField()->multipleFiles;
        if ($is_multiple_files) {
            $files = explode(",", $value);
        } else {
            $files = [$value];
        }

        // Perform array cleanup before processing.
        $files = array_map('trim', $files);
        $files = array_filter($files);

        if (!empty($files)) {
            $uploaded_files = [];
            foreach ($files as $v) {
                $url = preg_replace('/\?.*/', '', $v);
                $filename = basename($url);
                $target = GFFormsModel::get_file_upload_path( $form['id'], $filename );
                if ( $target ) {
                    if ($this->isSearchInFiles()) {
                        $target['path'] = str_replace(basename($target['path']), $filename, $target['path']);
                        $target['url'] = str_replace(basename($target['url']), $filename, $target['url']);
                    }
                    if ( $this->isSearchInFiles() && @file_exists($target['path']) ) {
                        $uploaded_files[] = $target['url'];
                    } else {
                        $wp_uploads = wp_upload_dir();
                        $tmp_path = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . \PMXI_Plugin::TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $filename;
                        $request = get_file_curl($v, $tmp_path);
                        $get_ctx = stream_context_create(array('http' => array('timeout' => 5)));
                        if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($tmp_path, @file_get_contents($v, false, $get_ctx))) {
                            $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- <b>WARNING</b>: File %s can not be downloaded, response %s', 'wp_all_import_gf_add_on'), $v, maybe_serialize($request)));
                            @unlink($tmp_path); // delete file since failed upload may result in empty file created
                        } else {
                            if ( @copy( $tmp_path, $target['path']) ) {
                                $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- File %s successfully moved to %s', 'wp_all_import_gf_add_on'), $url, $target['path']));
                                @unlink($tmp_path);
                                $uploaded_files[] = $target['url'];
                            } else {
                                $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- <b>WARNING</b>: FAILED (Temporary file %s could not be copied to %s.)', 'wp_all_import_gf_add_on'), $tmp_path, $target['path']));
                            }
                        }
                    }
                } else {
                    $this->getLogger() and call_user_func($this->getLogger(), __('- <b>WARNING</b>: FAILED (Upload folder could not be created.)', 'wp_all_import_gf_add_on'));
                }
            }
            if ($is_multiple_files) {
                return json_encode($uploaded_files);
            }
            return empty($uploaded_files) ? false :  array_shift($uploaded_files);
        }
		return false;
	}

	/**
	 * @return bool
	 */
	public function isSearchInFiles() {
	    $field = $this->getGField();
	    $post  = $this->getData('post');
	    return ! empty($post['pmgi']['search_in_files'][$field->id]);
    }
}
