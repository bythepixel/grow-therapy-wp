<?php

/**
 * RapidAddon
 *
 * @package     WP All Export RapidAddon
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version     1.0.0
 */

if (!class_exists('RapidExportAddon')) {

    class RapidExportAddon
    {
        const VERSION = '1.0.0';
        
        private $records_definition;

        private $data_elements = [];

        private $export_post_type;

        private $sub_post_type;

        private $query;

        private $prefix;

        private $handler;

        private $sections = [];

        private $meta_table = [];

        private $slug;

        private $name;

        private $post_name;

        private $filters_disabled = false;

        private $main_table;

        private $main_table_record_id_column;

        private $record_date_modified_column = false;

        private $wpdb;

        private $related_table;

        private $removed_post_types = [];

        private $bundles_enabled = true;

        private $filters_enabled = true;

        private $pmxe_installed = true;

        private $min_pmxe_version;

        private $notices = [];

        public function __construct($name, $slug, $post_name = false, $min_pmxe_version = '1.6.5')
        {
            global $wpdb;

            $this->wpdb = $wpdb;
            $this->name = $name;
            $this->slug = $slug;
            $this->prefix = $wpdb->prefix;
            $this->min_pmxe_version = $min_pmxe_version;

            if (class_exists('PMXE_Handler')) {
                $this->handler = new PMXE_Handler();
            } else {
                $this->pmxe_installed = false;
            }
            
            add_action('admin_notices', [$this, 'handle_admin_notices']);
        }

        public function handle_admin_notices()
        {

            if ( ! class_exists( 'GFAPI' ) ) {
                ?>
                <div class="error"><p>
                        <?php
                            echo wp_kses_post('<b>' . $this->name . ' Plugin</b>: The Gravity Forms Plugin must be installed and activated.', 'wpae-gf-addon');
                         ?>
                    </p></div>
                <?php

                deactivate_plugins( dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->slug . '.php');
                return;

            }

            if ( ! class_exists( 'PMXE_Plugin' ) ) {
                ?>
                <div class="error"><p>
                        <?php printf(
                            wp_kses_post(__('<b>%s Plugin</b>: WP All Export must be installed and activated.', 'wpae-rapid-addon')),
                            $this->name
                        ) ?>
                    </p></div>
                <?php

                deactivate_plugins( dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->slug . '.php');
                return;

            }

            if ( class_exists( 'PMXE_Plugin' ) and ( version_compare(PMXE_VERSION, '1.7.3') < 0 and PMXE_EDITION == 'paid') ) {
                ?>
                <div class="error"><p>
                        <?php printf(
                            wp_kses_post(__('<b>%s Plugin</b>: Please update your WP All Export Pro to the latest version', 'wpae-rapid-addon')),
                            $this->name
                        ) ?>
                    </p></div>
                <?php

                deactivate_plugins( dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->slug .'.php');
            }

            if ( class_exists( 'PMXE_Plugin' ) and ( version_compare(PMXE_VERSION, '1.3.3') < 0 and PMXE_EDITION != 'paid') ) {
                ?>
                <div class="error"><p>
                        <?php printf(
                            wp_kses_post(__('<b>%s Plugin</b>: Please update your WP All Export to the latest version', 'wpae-rapid-addon')),
                            $this->name
                        ) ?>
                    </p></div>
                <?php

                deactivate_plugins( dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->slug .'.php');
            }
        }

        public function disable_bundles()
        {
            $this->bundles_enabled = false;
        }

        public function disable_filters()
        {
            $this->filters_enabled = false;
        }

        public function add_notice($notice)
        {
            $this->notices[] = $notice;
        }

        public function get_sub_post_type()
        {

            if (!$this->pmxe_installed || !empty($this->notices)) {
                return;
            }

            if ($this->sub_post_type) {
                return $this->sub_post_type;
            }

            if (isset(XmlExportEngine::$exportOptions['sub_post_type_to_export']) && !empty(XmlExportEngine::$exportOptions['sub_post_type_to_export'])) {
                $this->sub_post_type = intval(XmlExportEngine::$exportOptions['sub_post_type_to_export']);
                return $this->sub_post_type;
            }

            if ($this->handler->get('sub_post_type_to_export')) {
                $this->sub_post_type = intval($this->handler->get('sub_post_type_to_export'));
                return $this->sub_post_type;
            }

            $input = new PMXE_Input();
            $post_data = $input->post('data', false);

            if (!is_array($post_data)) {
                $values = array();
                parse_str($post_data, $values);
                $post_data = $values;
            }

            if (isset($post_data['sub_post_type_to_export'])) {
                $this->sub_post_type = intval($post_data['sub_post_type_to_export']);
            }

            if(!$this->sub_post_type) {

                $export_id = intval($input->get('id', 0));

                if(!$export_id) {
                    $export_id = intval($input->get('export_id', 0));
                }

                if (empty($export_id)) {
                    $export_id = (!empty(PMXE_Plugin::$session->update_previous)) ? PMXE_Plugin::$session->update_previous : 0;
                }

                $export = new PMXE_Export_Record();

                $export->getById($export_id);

                if ($export->isEmpty()) {
                    return false;
                }

                if(!is_array($export->options)) {
                    return;
                }
                $exportOptions = $export->options + PMXE_Plugin::get_default_import_options();

                $this->sub_post_type = intval($exportOptions['sub_post_type_to_export']);

            }

            return $this->sub_post_type;

        }

        public function get_post_name()
        {
            return $this->post_name;
        }

        public function define_records($records_definition)
        {
            if (!isset($records_definition['record_source'])) {
                $this->error(esc_html__('A record source must be defined when defining records', 'wpae-rapid-addon'));
            }

            if (!isset($records_definition['record_table'])) {
                $this->error(esc_html__('A record table must be set', 'wpae-rapid-addon'));
            }

            $this->records_definition = $records_definition;

            $this->record_date_modified_column = $records_definition['record_date_modified_column'];

            if (is_array($this->records_definition['related_tables'])) {
                foreach ($this->records_definition['related_tables'] as $record_definition) {
                    if (isset($record_definition['is_meta']) && $record_definition['is_meta']) {
                        $this->meta_table = $record_definition;
                    } else {
                        $this->related_table = $record_definition;
                    }
                }
            }
        }

        public function define_data_element($data_element)
        {
            if (!isset($data_element['section'])) {
                $this->error('Please specify ' . $data_element['element_label'] . 'section');
            }

            if(!isset($data_element['element_label'])) {
                $this->error('The element label is mandatory');
            }

            if (!in_array($data_element['section'], $this->sections)) {
                $this->sections[] = $data_element['section'];
            }

            $slug = $this->get_element_slug($data_element);

            foreach($this->data_elements as $existing_data_element) {
                if($existing_data_element['slug'] == $slug) {
                    $slug .= '_' . md5($slug);
                }
            }

            $data_element['slug'] = $slug;

            $this->data_elements[] = $data_element;
        }

        public function remove_post_type($post_type)
        {
            $this->removed_post_types[] = $post_type;
        }

        public function add_export_post_type($post_type)
        {
            $this->export_post_type = $post_type;

            add_filter('wpallexport_custom_types', function ($post_types) use ($post_type) {

                foreach ($post_types as $slug => $existing_post_type) {
                    foreach ($this->removed_post_types as $removed_post_type) {
                        if ($slug == $removed_post_type) {
                            unset($post_types[$slug]);
                        }
                    }
                }

                $post_slug = 'custom_' . $this->slug;
                $post_types[$post_slug] = new stdClass();
                $post_types[$post_slug]->labels = new stdClass();
                $post_types[$post_slug]->labels->name = __($post_type['title']);
                $post_types[$post_slug]->labels->name = __($post_type['title']);

                if (isset($post_type['custom_icon'])) {
                    $post_types[$post_slug]->custom_icon = $post_type['custom_icon'];
                }

                return $post_types;
            });
        }

        public function get_post_type_name()
        {
            return $this->export_post_type['title'];
        }

        public function get_query($offset = 0, $limit = 50, $filter_args = [])
        {

            $this->build_query($offset, $limit, $filter_args);

            return $this->query;
        }

        public function run()
        {

            $this->disable_features();

            add_action('pmxe_custom_record_export', function () {
            add_filter("wp_all_export_init_fields", array(&$this, "filter_init_fields"), 10, 1);

            add_filter('wp_all_export_available_data', [$this, 'init_available_data']);
            add_filter('wp_all_export_available_sections', [$this, 'filter_available_sections']);

            add_filter('wp_all_export_disable_acf', function ($val) {
                return true;
            });

            });
            $this->handle_sub_types();
        }

        public function filter_init_fields($init_fields)
        {

            if(XmlExportEngine::$is_custom_addon_export) {
                $init_fields = [];

                foreach ($this->data_elements as $data_element) {

                    $element_name = $data_element['element_label'];

                    $item['label'] = $data_element['slug'];
                    $item['name'] = $element_name;
                    $item['type'] = $data_element['slug'];
                    $item['auto'] = true;

                    $available_data[$this->slugify($data_element['section']) . '_fields'][] = $item;

                    if (isset($data_element['default']) && $data_element['default']) {
                        $init_fields[] = $item;
                    }
                }
            }

            return $init_fields;
        }


        function init_available_data($available_data)
        {
            if(XmlExportEngine::$is_custom_addon_export) {

                foreach ($this->data_elements as $data_element) {

                    $element_name = $data_element['element_label'];

                    $item['label'] = $data_element['slug'];
                    $item['name'] = $element_name;
                    $item['type'] = $data_element['slug'];
                    $item['auto'] = true;

                    $available_data[$this->slugify($data_element['section']) . '_fields'][] = $item;

                    if (isset($data_element['default']) && $data_element['default']) {
                        $this->init_fields[] = $item;
                    }
                }
            }
            return $available_data;
        }


        public function filter_available_sections($available_sections)
        {
            if(XmlExportEngine::$is_custom_addon_export) {

                unset($available_sections['media']);
                unset($available_sections['other']);
                unset($available_sections['default']);
                unset($available_sections['cf']);

                foreach ($this->sections as $section) {
                    $custom_data[$this->slugify($section)] = [
                        'title' => $section,
                        'content' => $this->slugify($section) . '_fields'
                    ];
                }

                if (isset($custom_data) && !empty($custom_data)) {
                    $available_sections = array_merge($available_sections, $custom_data);
                }
            }
            return $available_sections;
        }

        public function handle_element(&$article, &$element_name, $element_value, $record, $field_snippet, $preview)
        {
            foreach ($this->data_elements as $data_element) {

                // Get the current element
                if ($data_element['slug'] === $element_value) {

                    if ($element_name === 'ID' && (XmlExportEngine::$exportOptions['export_to_sheet'] === 'xlsx' || XmlExportEngine::$exportOptions['export_to_sheet'] === 'xls')) {
                        $element_name = 'id';
                    }

                    //Null function
                    $callable_function = function ($param, $id) {
                        return $param;
                    };

                    if (isset($data_element['callable_function']) && is_callable($data_element['callable_function'])) {
                        $callable_function = $data_element['callable_function'];
                    }

                    if (isset($data_element['element_value'])) {

                        $val = pmxe_filter(call_user_func($callable_function, $data_element['element_value'], $record->id), $field_snippet);

                        wp_all_export_write_article($article, $element_name, ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val);
                        return;
                    }

                    // A column is set, and no location ,it means it's in the main table
                    if (isset($data_element['element_column']) && !isset($data_element['element_location'])) {

                        $element_value = $record->{$data_element['element_column']};

                        $val = pmxe_filter(call_user_func($callable_function, $element_value, $record->id), $field_snippet);
                        wp_all_export_write_article($article, $element_name, ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val);
                        return;
                    }

                    if (isset($data_element['element_meta_key']) && isset($data_element['element_location']) && $data_element['element_location'] == $this->meta_table['element_table']) {

                        if (!$this->meta_table) {
                            throw new \Exception('You added an element with meta key, but haven\'t defined a meta table');
                        }

                        $meta_table_name = $this->wpdb->prefix . $this->meta_table['element_table'];

                        $sql = "SELECT meta_value FROM {$meta_table_name} WHERE {$meta_table_name}.meta_key = {$data_element['element_meta_key']} AND {$meta_table_name}.{$this->meta_table['record_id_column']} = " . $record->{$this->main_table_record_id_column};

                        $meta_data_value = $this->wpdb->get_var($sql);

                        $val = pmxe_filter(call_user_func($callable_function, $meta_data_value, $record->id), $field_snippet);
                        wp_all_export_write_article($article, $element_name, ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val);
                        return;
                    }

                    if (isset($data_element['element_column']) && $data_element['element_location'] != $this->meta_table['element_table']) {

                        $related_table_sql = "SELECT {$data_element['element_column']} FROM {$this->wpdb->prefix}{$data_element['element_location']} 
WHERE {$this->wpdb->prefix}{$data_element['element_location']}.{$this->related_table['record_id_column']} = " . $record->{$this->main_table_record_id_column} . " ORDER BY {$this->related_table['element_id_column']}";

                        $results = $this->wpdb->get_results($related_table_sql);

                        $values = [];

                        foreach ($results as $result) {
                            $values[] = $result->{$data_element['element_column']};
                        }

                        $val = pmxe_filter(call_user_func($callable_function, implode('|', $values), $record->id), $field_snippet);
                        wp_all_export_write_article($article, $element_name, ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val);
                        return;
                    }

                    $val = pmxe_filter(call_user_func($callable_function, '', $record->id), $field_snippet);

                    wp_all_export_write_article($article, $element_name, ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val);

                }
            }
        }

        public function get_filter_data()
        {
            $filter_data = [];

            foreach($this->data_elements as $data_element) {

                if(!isset($filter_data[$data_element['section']])) {

                    $filter_data[$data_element['section']] =
                        [
                            'title' => $data_element['section']
                        ];
                }

                if(isset($data_element['filterable']) && $data_element['filterable']) {
                    $filter_data[$data_element['section']]['meta'][$data_element['slug']] = $data_element['element_label'];
                }

            }

            return $filter_data;
        }

        public function get_data_element_by_slug($field_slug) {
            foreach($this->data_elements as $data_element) {
                if($data_element['slug'] === $field_slug) {
                    return $data_element;
                }
            }
        }

        public function get_element_location($field_slug) {

            $field_data = $this->get_data_element_by_slug($field_slug);

            if(isset($field_data['element_location'])) {
                if (strpos($field_data['element_location'], 'meta') !== false) {
                    return 'meta';
                } else if (isset($field_data['element_location'])) {
                    return 'related_table';
                }
            } else {
                return 'main_table';
            }

            return 'main_table';
        }

        private function build_query($offset, $limit, $filter_args)
        {

            if($this->get_sub_post_type()) {

                $this->main_table = $this->get_main_table();
                $this->main_table_record_id_column = $this->records_definition['record_id_column'];

                $where = $this->main_table . "." . $this->records_definition['sub_post_column'] . ' = ' . $this->get_sub_post_type();

                $tables = [$this->main_table];

                $from = implode(',', $tables);

                $select = implode(',', array_map(function ($val) {
                    return $val . '.*';
                }, $tables));

                $where = $this->check_new_stuff($where);

                $where = $this->apply_filters($where, $filter_args);

                $joins = XmlExportEngine::$exportOptions['joinclause'];
                $join = implode(' ', array_unique($joins));
                $from .= $join;

                $limit_query = "";

                if (isset($offset) && isset($limit) && $limit) {
                    $limit_query = " LIMIT $offset, $limit ";
                }

                $sql = "SELECT $select FROM  $from WHERE $where GROUP BY {$this->get_main_table()}.{$this->main_table_record_id_column} ORDER BY {$this->main_table_record_id_column} $limit_query";

                $this->query = new WPAE_Query($sql);
            }
        }

        private function error($message)
        {
            throw new \Exception($message);
        }

        private function slugify($text)
        {
            return str_replace('-', '_', sanitize_title($text));
        }

        private function handle_sub_types()
        {
            if (isset($this->export_post_type['sub_types'])) {

                add_action('pmxe_addons_html', function () {
                    $has_sub_types = false;

                    $disableFiltering = '';
                    $enableFiltering = '';

                    if (count($this->export_post_type['sub_types'])) {
                        $has_sub_types = true;
                    }

                    if ($has_sub_types) {
                        $disableFiltering = 'window.wpaeDisableFiltering();';
                        $enableFiltering = 'window.wpaeEnableFiltering();';
                    }

                    $js = <<<JSCRIPT
<script type="text/javascript"> 
jQuery(document).ready(function(){

// disable filtering when selecting a post type
jQuery('input[name=cpt]').bind("change" , function() {
   var selected_cpt = jQuery(this).val();
   if(selected_cpt === 'custom_wpae-gf-addon') {
       jQuery('.sub_post_type_to_export_wrapper').show();
       
       var selectedValue = jQuery('#sub_post_to_export').find('.dd-selected-value').val();
       
       jQuery('#sub_post_to_export').find('.dd-option-value[value="'+selectedValue+'"]').trigger('click');
       
       jQuery('.wpallexport-filtering-wrapper').slideUp();
       jQuery('.wpallexport-submit-buttons .wp_all_export_btn_with_note').slideUp();
       jQuery('.wpallexport-upload-resource-step-two').slideUp();
       
       $disableFiltering;
   } else {
       jQuery('.sub_post_type_to_export_wrapper').hide();
       $enableFiltering;
   }
});

JSCRIPT;
                    $js .= 'jQuery("#sub_post_to_export").append(jQuery("<option></option>").attr("value", "").text("' . esc_html__('Select the form to export entries from', 'wpae-rapid-addon') . '"));';

                    if ($has_sub_types) {

                        foreach ($this->export_post_type['sub_types'] as $id => $sub_type) {
                            $js .= 'jQuery("#sub_post_to_export").append(jQuery("<option></option>").attr("value", "' . $id . '").text("' . $sub_type . '"));';

                        }

                    }
                    $js .= "

    		jQuery('#sub_post_to_export').ddslick({
			width: 600,
			onSelected: function(selectedData){

				if (selectedData.selectedData.value != \"\"){

					jQuery('#sub_post_to_export').find('.dd-selected').css({'color':'#555'});
					jQuery('input[name=sub_post_type_to_export]').val(selectedData.selectedData.value);
					window.wpaeEnableFiltering();
					window.wpae_filtering(jQuery('input[name=cpt]').val());
				}
				else{
					jQuery('#sub_post_to_export').find('.dd-selected').css({'color':'#cfceca'});
					jQuery('.wpallexport-choose-file').find('.wpallexport-filtering-wrapper').slideUp();
					jQuery('.wpallexport-choose-file').find('.wpallexport-upload-resource-step-two').slideUp();
					jQuery('.wpallexport-choose-file').find('.wpallexport-submit-buttons').hide();
				}
			}
		});

}); </script>";

                    echo $js;

                });
            }
        }


        /**
         * @return string
         */
        public function get_main_table()
        {
            $main_table = $this->prefix . $this->records_definition['record_table'];
            return $main_table;
        }

        public function get_related_table()
        {
            return $this->prefix . 'gf_entry_notes';
        }

        public function get_meta_table() {
            return $this->wpdb->prefix . $this->meta_table['element_table'];
        }


        //TODO: This should be a filtering class
        private function check_new_stuff($where)
        {

            $export_id = $this->get_export_id();

            $export = new \PMXE_Export_Record();
            $export->getById($export_id);

            if (!empty($export)) {

                //If re-run, this export will only include records that have not been previously exported.
                if ($this->is_export_new_stuff()) {

                    if ($export->iteration > 0) {
                        $postsToExclude = array();
                        $postList = new \PMXE_Post_List();

                        $postsToExcludeSql = 'SELECT post_id FROM ' . $postList->getTable() . ' WHERE export_id = %d AND iteration < %d';
                        $results = $this->wpdb->get_results($this->wpdb->prepare($postsToExcludeSql, $export_id, $export->iteration));

                        foreach ($results as $result) {
                            $postsToExclude[] = $result->post_id;
                        }

                        if (count($postsToExclude)) {
                            $where .= $this->get_exclude_query_where($postsToExclude);
                        }
                    }
                }

                if ($this->is_export_modfified_stuff() && !empty($export->registered_on)) {

                    $where .= $this->get_modified_query_where($export);
                }
            }

            return $where;
        }

        private function apply_filters($where, $filter_args) {

            try {
                $filtering_engine = new Wpae\Pro\Filtering\FilteringCustom();
                $filtering_engine->init($filter_args);
                $filtering_engine->parse();

                XmlExportEngine::$exportOptions['whereclause'] = $filtering_engine->get('queryWhere');
                XmlExportEngine::$exportOptions['joinclause']  = $filtering_engine->get('queryJoin');

            } catch (\Wpae\App\Service\Addons\AddonNotFoundException $e) {
                die($e->getMessage());
            }

            $where .= $filtering_engine->get('queryWhere');

            return $where;
        }

        public function get_exclude_query_where($postsToExclude)
        {

            return " AND ({$this->main_table}.{$this->main_table_record_id_column} NOT IN (" . implode(',', $postsToExclude) . "))";

        }

        public function get_modified_query_where($export)
        {
            return " AND {$this->main_table}.{$this->record_date_modified_column} > '" . $export->registered_on . "' ";
        }

        /**
         * @return bool
         */
        protected function is_export_new_stuff()
        {

            $export_id = $this->get_export_id();

            return (!empty(\XmlExportEngine::$exportOptions['export_only_new_stuff']) &&
                $export_id);
        }

        /**
         * @return bool
         */
        protected function is_export_modfified_stuff()
        {

            $export_id = $this->get_export_id();

            return (!empty(\XmlExportEngine::$exportOptions['export_only_modified_stuff']) &&
                $export_id);
        }

        private function get_export_id()
        {
            $input = new \PMXE_Input();
            $export_id = $input->get('id', 0);

            if(!$export_id) {
                $export_id = $input->get('export_id', 0);
            }

            return $export_id;
        }

        private function disable_features()
        {
            if(defined('PMXE_EDITION ') && PMXE_EDITION === 'paid') {

                if (!$this->bundles_enabled) {
                    XmlExportEngine::$is_bundle_available = false;
                }

                if (!$this->filters_enabled) {
                    XmlExportEngine::$is_filtering_available = false;
                }
            }
        }

        /**
         * @param $data_element
         * @return string
         */
        private function get_element_slug($data_element)
        {
            if (isset($data_element['element_column'])) {
                $element_slug = $this->slugify($data_element['element_column']);
            } else {
                $element_slug = $this->slugify($data_element['element_label']);
            }
            return $element_slug;
        }

    }
}

class WPAE_Query extends WP_Query
{
    public $found_posts;

    public $post_count;

    public $request;

    public $results;

    private $wpdb;

    private $request_with_limits = '';

    public function __construct($sql)
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        $this->request = $sql;

        $this->results = $result_with_limits = $this->wpdb->get_results($this->request);

        $all_posts = $this->wpdb->get_results($this->request);

        $this->post_count = count($result_with_limits);

        $this->found_posts = count($all_posts);
    }

    public function __sleep()
    {
        return ['found_posts', 'post_count', 'request', 'request_with_limits'];
    }

    public function __wakeup()
    {

        global $wpdb;
        $this->wpdb = $wpdb;

        $this->results = $result_with_limits = $this->wpdb->get_results($this->request_with_limits);
    }
}