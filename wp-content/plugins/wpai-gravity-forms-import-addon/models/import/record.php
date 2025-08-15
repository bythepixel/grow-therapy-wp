<?php

use wpai_gravityforms_add_on\gf\forms\Form;

/**
 * Class PMGI_Import_Record
 */
class PMGI_Import_Record extends PMGI_Model_Record {

	/**
	 * @var array
	 */
	public $tmp_files;

	/**
	 * Associative array of data which will be automatically available as variables when template is rendered
	 * @var array
	 */
	public $data = array();

    /**
     * @var Form
     */
    public $form;

    /**
     * Initialize model instance
     *
     * @param array [optional] $data Array of record data to initialize object with
     */
    public function __construct($data = []) {
        parent::__construct($data);
        $this->setTable(PMXI_Plugin::getInstance()
                ->getTablePrefix() . 'imports');
    }

    /**
     * @param array $parsingData [import, count, xml, logger, chunk, xpath_prefix]
     */
    public function parse($parsingData) {

        add_filter('user_has_cap', [
            $this,
            '_filter_has_cap_unfiltered_html'
        ]);
        kses_init(); // do not perform special filtering for imported content

        $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func($parsingData['logger'], __('Composing gravity forms entries...', PMGI_Plugin::TEXT_DOMAIN));

        if ( ! empty($parsingData['import']->options['gravity_form_title']) ) {
	        $form_id = \wpai_gravityforms_add_on\gf\GravityFormsService::get_form_by_id_title($parsingData['import']->options['gravity_form_title']);
	        if ($form_id) {
	        	$form = GFAPI::get_form($form_id);
		        $this->form = new Form($form, $parsingData['import']->options);
		        $this->form->parse($parsingData);
	        }
        }

	    $cxpath = $parsingData['xpath_prefix'] . $parsingData['import']->xpath;

        $this->data = array();
	    $records    = array();
	    $tmp_files  = array();

	    $xml = $parsingData['xml'];

	    if ( ! empty($parsingData['import']->options['pmgi']) ) {

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing created date...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['date_created'] ) ) {
			    $this->data['pmgi_date_created'] = XmlImportParser::factory( $xml, $cxpath, $parsingData['import']->options['pmgi']['date_created'], $file )->parse( $records );
			    $warned = array(); // used to prevent the same notice displaying several times
			    foreach ($this->data['pmgi_date_created'] as $i => $d) {
				    if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
				    $time = strtotime($d);
				    if (FALSE === $time) {
					    in_array($d, $warned) or $parsingData['logger'] and call_user_func($parsingData['logger'], sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'wp_all_import_gf_add_on'), $warned[] = $d));
					    $time = time();
				    }
				    $this->data['pmgi_date_created'][$i] = date('Y-m-d H:i:s', $time);
			    }
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_date_created'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing updated date...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['date_updated'] ) ) {
			    $this->data['pmgi_date_updated'] = XmlImportParser::factory( $xml, $cxpath, $parsingData['import']->options['pmgi']['date_updated'], $file )->parse( $records );
			    $warned = array(); // used to prevent the same notice displaying several times
			    foreach ($this->data['pmgi_date_updated'] as $i => $d) {
				    if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
				    $time = strtotime($d);
				    if (FALSE === $time) {
					    in_array($d, $warned) or $parsingData['logger'] and call_user_func($parsingData['logger'], sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'wp_all_import_gf_add_on'), $warned[] = $d));
					    $time = time();
				    }
				    $this->data['pmgi_date_updated'][$i] = date('Y-m-d H:i:s', $time);
			    }
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_date_updated'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing IP...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['ip'] ) ) {
			    $this->data['pmgi_ip'] = XmlImportParser::factory( $xml, $cxpath, $parsingData['import']->options['pmgi']['ip'], $file )->parse( $records );
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_ip'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing source URL...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['source_url'] ) ) {
			    $this->data['pmgi_source_url'] = XmlImportParser::factory( $xml, $cxpath, $parsingData['import']->options['pmgi']['source_url'], $file )->parse( $records );
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_source_url'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing user agent...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['user_agent'] ) ) {
			    $this->data['pmgi_user_agent'] = XmlImportParser::factory( $xml, $cxpath, $parsingData['import']->options['pmgi']['user_agent'], $file )->parse( $records );
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_user_agent'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing status...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['status'] ) ) {
			    if ( $parsingData['import']->options['pmgi']['status'] == 'xpath' ) {
				    if ( ! empty($parsingData['import']->options['pmgi']['status_xpath']) ) {
					    $this->data['pmgi_status'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['status_xpath'], $file)->parse($records);
					    $tmp_files[] = $file;
				    } else {
					    $parsingData['count'] and $this->data['pmgi_status'] = array_fill(0, $parsingData['count'], '');
				    }
			    } else {
				    $this->data['pmgi_status'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['status'], $file)->parse($records); $tmp_files[] = $file;
			    }
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_status'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing starred flag...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['starred'] ) ) {
			    if ( $parsingData['import']->options['pmgi']['starred'] == 'xpath' ) {
				    if ( ! empty($parsingData['import']->options['pmgi']['starred_xpath']) ) {
					    $this->data['pmgi_starred'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['starred_xpath'], $file)->parse($records);
					    $tmp_files[] = $file;
				    } else {
					    $parsingData['count'] and $this->data['pmgi_starred'] = array_fill(0, $parsingData['count'], '');
				    }
			    } else {
				    $this->data['pmgi_starred'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['starred'], $file)->parse($records); $tmp_files[] = $file;
			    }
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_starred'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func( $parsingData['logger'], __( 'Composing read flag...', 'wp_all_import_gf_add_on' ) );
		    if ( ! empty( $parsingData['import']->options['pmgi']['read'] ) ) {
			    if ( $parsingData['import']->options['pmgi']['read'] == 'xpath' ) {
				    if ( ! empty($parsingData['import']->options['pmgi']['read_xpath']) ) {
					    $this->data['pmgi_read'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['read_xpath'], $file)->parse($records);
					    $tmp_files[] = $file;
				    } else {
					    $parsingData['count'] and $this->data['pmgi_read'] = array_fill(0, $parsingData['count'], '');
				    }
			    } else {
				    $this->data['pmgi_read'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['read'], $file)->parse($records); $tmp_files[] = $file;
			    }
			    $tmp_files[] = $file;
		    } else {
			    $parsingData['count'] and $this->data['pmgi_read'] = array_fill( 0, $parsingData['count'], '' );
		    }

		    $parsingData['chunk'] == 1 and $parsingData['logger'] and call_user_func($parsingData['logger'], __('Composing created by...', 'wp_all_import_gf_add_on'));
		    $this->data['created_by'] = array();
		    $current_user = wp_get_current_user();

		    if (!empty($parsingData['import']->options['pmgi']['created_by'])){
			    $this->data['pmgi_created_by'] = XmlImportParser::factory($xml, $cxpath, $parsingData['import']->options['pmgi']['created_by'], $file)->parse($records); $tmp_files[] = $file;
			    foreach ($this->data['pmgi_created_by'] as $key => $author) {
				    $user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
				    if (!empty($user)) {
					    $this->data['pmgi_created_by'][$key] = $user->ID;
				    } else {
					    if ($current_user->ID){
						    $this->data['pmgi_created_by'][$key] = $current_user->ID;
					    } else {
						    $super_admins = get_users(['role__in' => 'administrator']);
						    if ( ! empty($super_admins) ) {
							    $sauthor = array_shift($super_admins);
							    $this->data['pmgi_created_by'][$key] = (!empty($sauthor)) ? $sauthor->ID : NULL;
						    }
					    }
				    }
			    }
		    } else {
			    if ($current_user->ID) {
				    $parsingData['count'] and $this->data['pmgi_created_by'] = array_fill(0, $parsingData['count'], $current_user->ID);
			    } else {
				    $super_admins = get_users(['role__in' => 'administrator']);
				    if ( ! empty($super_admins) ) {
					    $author = array_shift($super_admins);
					    $parsingData['count'] and $this->data['pmgi_created_by'] = array_fill(0, $parsingData['count'], (!empty($author)) ? $author->ID : NULL);
				    }
			    }
		    }

		    $this->data['pmgi_notes'] = array();
		    switch ($parsingData['import']->options['pmgi']['notes_repeater_mode']) {
			    case 'xml':
				    if (!empty($parsingData['import']->options['pmgi']['notes_repeater_mode_foreach'])) {
					    foreach ($parsingData['import']->options['pmgi']['notes'] as $key => $row) {
						    for ($k = 0; $k < $parsingData['count']; $k++) {
							    $base_xpath = '[' . ($k + 1) . ']/' . ltrim(trim($parsingData['import']->options['pmgi']['notes_repeater_mode_foreach'], '{}!'), '/');
							    $rows = \XmlImportParser::factory($xml, $cxpath . $base_xpath, "{.}", $file)->parse();
							    $this->tmp_files[] = $file;
							    $row_data = $this->parse_item_row($xml, $row, $cxpath . $base_xpath, count($rows));
							    $items = array();
							    if (!empty($row_data)) {
								    for ($j = 0; $j < count($rows); $j++) {
									    foreach ($row_data as $itemkey => $values) {
										    $items[$j][$itemkey] = $values[ $j ] ?? '';
									    }
								    }
							    }
							    $this->data['pmgi_notes'][] = $items;
						    }
						    break;
					    }
				    }
				    break;
			    case 'csv':
				    $separator = $parsingData['import']->options['pmgi']['notes_repeater_mode_separator'];
				    foreach ($parsingData['import']->options['pmgi']['notes'] as $key => $row) {
					    if (empty($separator)) {
						    break;
					    }
					    $row_data = $this->parse_item_row($xml, $row, $cxpath, $parsingData['count'], $separator);
					    for ($k = 0; $k < $parsingData['count']; $k++) {
						    $items = array();
						    $maxCountRows = 0;
						    foreach ($row_data as $itemkey => $values) {
							    $itemIndex = 0;
							    $rows = explode($separator, $values[$k]);
							    if (!empty($rows)) {
								    if (count($rows) > $maxCountRows) {
									    $maxCountRows = count($rows);
								    }
								    if (count($rows) == 1) {
									    for ($j = 0; $j < $maxCountRows; $j++) {
										    $items[$itemIndex][$itemkey] = trim($rows[0]);
										    $itemIndex++;
									    }
								    } else {
									    foreach ($rows as $val) {
										    $items[$itemIndex][$itemkey] = trim($val);
										    $itemIndex++;
									    }
								    }
							    }
						    }
						    $this->data['pmgi_notes'][] = $items;
					    }
					    break;
				    }
				    break;
			    default:
				    $row_data = array();
				    foreach ($parsingData['import']->options['pmgi']['notes'] as $key => $row) {
					    $row_data[] = $this->parse_item_row($xml, $row, $cxpath, $parsingData['count']);
				    }
				    for ($j = 0; $j < $parsingData['count']; $j++) {
					    $items = array();
					    $itemIndex = 0;
					    foreach ($row_data as $k => $item) {
						    foreach ($item as $itemkey => $values) {
							    $items[$itemIndex][$itemkey] = $values[$j];
						    }
						    $itemIndex++;
					    }
					    $this->data['pmgi_notes'][] = $items;
				    }
				    break;
		    }
	    }

        remove_filter('user_has_cap', [
            $this,
            '_filter_has_cap_unfiltered_html'
        ]);
        kses_init(); // return any filtering rules back if they has been disabled for import procedure

	    foreach ($tmp_files as $file) { // remove all temporary files created
	    	if (@file_exists($file)) {
			    @unlink($file);
		    }
	    }

	    return $this->data;
    }

	/**
	 *
	 * Helper method to parse repeated options
	 *
	 * @param $xml
	 * @param $row
	 * @param $cxpath
	 * @param $count
	 *
	 * @param bool $separator
	 *
	 * @return array
	 * @throws XmlImportException
	 */
	protected function parse_item_row($xml, $row, $cxpath, $count, $separator = FALSE) {
		$row_data = array();
		foreach ($row as $opt => $value) {
			switch ($opt) {
				case 'date':
					if (!empty($value)) {
						$dates = \XmlImportParser::factory($xml, $cxpath, $value, $file)->parse();
						$this->tmp_files[] = $file;
						foreach ($dates as $i => $d) {
							$dates[$i] = $separator ? explode($separator, $d) : array($d);
							$dates[$i] = array_map('trim', $dates[$i]);
						}
						$warned = array(); // used to prevent the same notice displaying several times
						foreach ($dates as $i => $date) {
							$times = array();
							foreach ($date as $d) {
								if ($d == 'now') {
									$d = current_time('mysql');
								} // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
								$time = strtotime($d);
								if (FALSE === $time) {
									$time = time();
								}
								$times[] = date('Y-m-d H:i:s', $time);
							}
							$row_data[$opt][$i] = $separator ? implode($separator, $times) : array_shift($times);
						}
					} else {
						$count and $row_data[$opt] = array_fill(0, $count, date('Y-m-d H:i:s'));
					}
					break;
				case 'username':
					if (!empty($value)) {
						$users = \XmlImportParser::factory($xml, $cxpath, $value, $file)->parse();
						foreach ($users as $i => $d) {
							$users[$i] = $separator ? explode($separator, $d) : array($d);
							$users[$i] = array_map('trim', $users[$i]);
						}
						$this->tmp_files[] = $file;
						foreach ($users as $i => $entry_users) {
							$items = array();
							foreach ($entry_users as $user_identifier) {
								$user = get_user_by('login', $user_identifier) or $user = get_user_by('slug', $user_identifier) or $user = get_user_by('email', $user_identifier) or ctype_digit($user_identifier) and $user = get_user_by('id', $user_identifier);
								if (!empty($user)) {
									$items[] = $user->ID;
								} else {
									$items[] = 0;
								}
							}
							$row_data['user_id'][$i] = $separator ? implode($separator, $items) : array_shift($items);
							$row_data[$opt][$i] = $separator ? implode($separator, $entry_users) : array_shift($entry_users);
						}
					} else {
						$count and $row_data['user_id'] = array_fill(0, $count, 0);
						$count and $row_data[$opt] = array_fill(0, $count, '');
					}
					break;
				default:
					if (!empty($value)) {
						$row_data[$opt] = \XmlImportParser::factory($xml, $cxpath, $value, $file)->parse();
						$this->tmp_files[] = $file;
					} else {
						$count and $row_data[$opt] = array_fill(0, $count, $value);
					}
					break;
			}
		}
		// remove all temporary files created
		$this->unlinkTempFiles();
		return $row_data;
	}

	/**
	 * Remove all temporary created files.
	 */
	public function unlinkTempFiles() {
		if (!empty($this->tmp_files)) {
			foreach ($this->tmp_files as $file) { // remove all temporary files created
				if (@file_exists($file)) {
					@unlink($file);
				}
			}
		}
		$this->tmp_files = array();
	}

    /**
     * @param $importData [pid, i, import, articleData, xml, is_cron, xpath_prefix]
     */
    public function import( $importData ) {

    	if ( ! in_array($importData['import']->options['custom_type'], array('gf_entries')) ) return;

	    $entry = GFAPI::get_entry($importData['pid']);

	    if ( $entry ) {
		    $entry_fields = $this->form->import( $importData );
		    if (!empty($entry_fields)) {
		    	foreach ($entry_fields as $entry_field_key => $entry_field_value) {
				    $entry[$entry_field_key] = $entry_field_value;
			    }
		    }

		    $result = GFAPI::update_entry( $entry, $importData['pid'] );
		    if ( is_wp_error( $result ) ) {
			    $importData['logger'] && call_user_func( $importData['logger'], sprintf( __( '- ERROR: `%s`', 'wp_all_import_gf_add_on' ), $result->get_error_message() ) );
		    }
	    }

	    if ( empty($importData['articleData']['ID']) || $importData['import']->options['is_pmgi_update_entry_notes'] ) {
		    $notes = GFAPI::get_notes( [ 'entry_id' => $importData['pid'] ] );
	    	if ($importData['import']->options['pmgi_update_entry_notes_logic'] == 'full_update') {
			    if ( ! empty( $notes ) ) {
				    foreach ( $notes as $note ) {
					    GFAPI::delete_note( $note->id );
				    }
			    }
		    } else {
			    $note_texts = array_column($notes, 'value');
			    if ( ! empty( $this->data['pmgi_notes'][$importData['i']] ) ) {
				    foreach ( $this->data['pmgi_notes'][$importData['i']] as $note_key => $note ) {
					    if ( in_array(trim($note['note_text']), $note_texts)) {
						    unset($this->data['pmgi_notes'][$importData['i']][$note_key]);
					    }
				    }
			    }
		    }
		    // Import entry notes.
		    if ( ! empty( $this->data['pmgi_notes'][$importData['i']] ) ) {
			    foreach ( $this->data['pmgi_notes'][$importData['i']] as $note ) {
				    if (empty($note['note_text'])) {
					    continue;
				    }
				    $username = trim($note['username']);
				    if (!empty($note['user_id'])) {
					    $user = get_userdata($note['user_id']);
					    if (!empty($user)) {
						    if (!empty($user->display_name)) {
							    $username = $user->display_name;
						    }
					    }
				    }
				    if (empty($note['user_id'])) {
					    $note['user_id'] = 0;
				    }
				    if (empty($note['date'])) {
					    $note['date'] = date("Y-m-d H:i:s");
				    }
				    if (empty($note['note_type'])) {
					    $note['note_type'] = empty($note['user_id']) ? 'notification' : 'user';
				    }
				    if (empty($note['note_sub_type'])) {
					    $note['note_sub_type'] = '';
				    }
				    $new_note = GFAPI::add_note($importData['pid'], $note['user_id'], $username, trim($note['note_text']), trim($note['note_type']), trim($note['note_sub_type']));
				    if ( is_wp_error( $new_note )) {
					    $importData['logger'] && call_user_func($importData['logger'], sprintf(__('- ERROR: `%s`', 'wp_all_import_gf_add_on'), $new_note->get_error_message()));
				    } else {
					    GFAPI::update_note( [ 'date_created' => $note['date'] ], $new_note );
					    $importData['logger'] && call_user_func($importData['logger'], sprintf(__('- Note created: `%s`', 'wp_all_import_gf_add_on'), $note['note_text']));
				    }
			    }
		    }
	    }
    }

    /**
     * @param $importData [pid, import, logger, is_update]
     */
    public function saved_post($importData) {

    	if ( ! in_array($importData['import']->options['custom_type'], array('gf_entries')) ) return;

	    $this->form->saved_post($importData);

        $lead = GFAPI::get_entry($importData['pid']);

        do_action( 'gform_pre_handle_confirmation', $lead, $this->form->getForm() );
    }

    /**
     * @param $caps
     * @return mixed
     */
    public function _filter_has_cap_unfiltered_html($caps) {
        $caps['unfiltered_html'] = TRUE;
        return $caps;
    }

    /**
     * @param $var
     * @return bool
     */
    public function filtering($var) {
        return ("" == $var) ? FALSE : TRUE;
    }
}
