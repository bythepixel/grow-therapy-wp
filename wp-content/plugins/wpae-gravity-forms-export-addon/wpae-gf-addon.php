<?php
/*
Plugin Name: WP All Export - Gravity Forms Add-On
Description: Export Gravity Forms entries to CSV or XML, or migrate to another WordPress install.
Version: 1.0.2
*/

include "rapid-export-add-on.php";
include_once "classes/updater.php";

final class GF_Export_Add_On
{

    const VERSION = '1.0.2';
    
    protected static $instance;

    /** @var  RapidExportAddon */
    public $add_on;

    private $form;

    private $default_slugs = ['id', 'date_created', 'user_agent'];

    static public function get_instance()
    {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run()
    {
        // Define the add-on
        $this->add_on = new RapidExportAddon(esc_html__('Gravity Forms Entries Export Add-On', 'wpae-gf-addon'), 'wpae-gf-addon', 'Gravity Forms Entries', '1.7.3');

        if (!class_exists('GFAPI')) {
            return;
        }

        // Where post_type contains the form ID as selected when configuring the export.
        $this->form = GFAPI::get_form($this->add_on->get_sub_post_type());

        // Define the data tables
        $this->add_on->define_records(
            [
                'record_source' => 'custom_table',
                'record_table' => 'gf_entry',
                'record_id_column' => 'id',
                'sub_post_column' => 'form_id',
                'record_date_modified_column' => 'date_updated',
                'related_tables' => [
                    [
                        'element_source' => 'custom_table',
                        'element_table' => 'gf_entry_meta',
                        'element_id_column' => 'id',
                        'record_id_column' => 'entry_id',
                        'is_meta' => true
                    ],
                    [
                        'element_source' => 'custom_table',
                        'element_table' => 'gf_entry_notes',
                        'element_id_column' => 'id',
                        'record_id_column' => 'entry_id',
                    ]
                ]
            ]
        );

        $forms = GFAPI::get_forms(true);

        $sub_export_types = [];

        foreach ($forms as $form) {
            $sub_export_types[$form['id']] = $form['title'];
        }

        $this->add_on->disable_bundles();

        $this->add_on->disable_filters();

        // Remove the default gf_entries
        $this->add_on->remove_post_type('gf_entries');

        // Entry Data
        $this->add_on->add_export_post_type([
            'title' => esc_html__('Gravity Forms Entries', 'wpae-gf-addon'),
            'slug' => 'gf-entries',
            'sub_types' => $sub_export_types
        ]);

        $this->add_entry_data();

        $this->add_entry_notes_data();

        $excluded_types = ['page', 'html', 'section'];

        // Add fields
        if (isset($this->form['fields']) && is_array($this->form['fields'])) {
            foreach ($this->form['fields'] as $field) {

                if (in_array($field['type'], $excluded_types)) {
                    continue;
                }

                if (isset($field['inputs']) && is_array($field['inputs'])) {

                    if ($field['type'] === 'time') {
                        $this->add_on->define_data_element(
                            [
                                'element_location' => 'gf_entry_meta',
                                'record_id' => 'entry_id',
                                'element_meta_key' => $field['id'],
                                'element_label' => $field['label'],
                                'section' => 'Entry Meta',
                                'filterable' => 'date',
                                'default' => true
                            ]
                        );
                    } else if ($field['type'] === 'checkbox') {

                        $this->add_on->define_data_element(
                            [
                                'element_location' => 'gf_entry_meta',
                                'record_id' => 'entry_id',
                                'element_meta_key' => $field['id'],
                                'element_label' => $field['label'],
                                'section' => 'Entry Meta',
                                'default' => true,
                                'filterable' => true,
                                'callable_function' => function ($value, $entry_id) use ($field) {

                                    global $wpdb;

                                    $response_arr = [];

                                    $form_id = $this->add_on->get_sub_post_type();

                                    $values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}gf_entry_meta WHERE meta_key LIKE '{$field['id']}.%' AND entry_id = {$entry_id} AND form_id = {$form_id}");

                                    foreach ($values as $value) {
                                        $response_arr[] = $value->meta_value;
                                    }

                                    $response = implode(',', $response_arr);

                                    return $response;
                                }
                            ]);
                    } elseif ($field['type'] === 'consent') {
                        foreach ($field['inputs'] as $input) {

                            if ($input['label'] === 'Description') {
                                $this->add_on->define_data_element(
                                    [
                                        'element_location' => 'gf_entry_meta',
                                        'record_id' => 'entry_id',
                                        'element_meta_key' => $input['id'],
                                        'element_label' => $field['label'] . ' - ' . $input['label'],
                                        'section' => 'Entry Meta',
                                        'default' => true,
                                        'filterable' => true,
                                        'callable_function' => function ($value, $entry_id) use ($field) {

                                            $form_id = $this->add_on->get_sub_post_type();

                                            $revision_id = GFFormsModel::get_latest_form_revisions_id($form_id);
                                            $consent_field = new GF_Field_Consent(['id' => $field['id'], 'formId' => $form_id]);
                                            $consent_description = $consent_field->get_field_description_from_revision($revision_id);

                                            return $consent_description;
                                        }
                                    ]);

                            } else {

                                $consent_data =  [
                                    'element_location' => 'gf_entry_meta',
                                    'record_id' => 'entry_id',
                                    'element_meta_key' => $input['id'],
                                    'element_label' => $field['label'] . ' - ' . $input['label'],
                                    'section' => 'Entry Meta',
                                    'default' => true,
                                    'filterable' => true
                                ];

                                if($field['label'] === 'Consent' && $input['label'] === 'Consent') {
                                    $consent_data['consent'] = true;
                                }
                                $this->add_on->define_data_element($consent_data);
                            }
                        }
                    } else {
                        foreach ($field['inputs'] as $input) {
                            $this->add_on->define_data_element(
                                [
                                    'element_location' => 'gf_entry_meta',
                                    'record_id' => 'entry_id',
                                    'element_meta_key' => $input['id'],
                                    'element_label' => $field['label'] . ' - ' . $input['label'],
                                    'section' => 'Entry Meta',
                                    'filterable' => true,
                                    'default' => true
                                ]
                            );
                        }

                    }
                } elseif ($field['type'] === 'list') {

                    $this->add_on->define_data_element(
                        [
                            'element_location' => 'gf_entry_meta',
                            'record_id' => 'entry_id',
                            'callable_function' =>
                                function ($element_value, $entry_id) {
                                    $value = maybe_unserialize($element_value);

                                    if (is_array($value) && $this->has_nested_array($value)) {
                                        $value = $element_value;
                                    } elseif (is_array($value)) {
                                        $value = implode(',', $value);
                                    }

                                    return $value;
                                },
                            'element_meta_key' => $field['id'],
                            'element_label' => $field['label'],
                            'section' => 'Entry Meta',
                            'filterable' => true,
                            'default' => true
                        ]);

                } elseif ($field['type'] === 'multiselect') {

                    $this->add_on->define_data_element(
                        [
                            'element_location' => 'gf_entry_meta',
                            'record_id' => 'entry_id',
                            'element_meta_key' => $field['id'],
                            'element_label' => $field['label'],
                            'section' => 'Entry Meta',
                            'default' => true,
                            'filterable' => true,
                            'callable_function' => function ($value, $entry_id) {
                                $val = json_decode($value, true);
                                return is_array($val) ? implode(',', $val) : $value;
                            }
                        ]);

                } else {

                    $this->add_on->define_data_element(
                        [
                            'element_location' => 'gf_entry_meta',
                            'record_id' => 'entry_id',
                            'element_meta_key' => $field['id'],
                            'element_label' => $field['label'],
                            'section' => 'Entry Meta',
                            'filterable' => true,
                            'default' => true

                        ]);
                }
            }
        }

        $this->add_on->run();

        // retrieve our license key from the DB
        $wpae_acf_addon_options = get_option('PMXE_Plugin_Options');


        if (!empty($wpae_acf_addon_options['info_api_url'])){
            // setup the updater
            $updater = new PMGFE_Updater( $wpae_acf_addon_options['info_api_url'], __FILE__, array(
                    'version' 	=> self::VERSION,		// current version number
                    'license' 	=> false, // license key (used get_option above to retrieve from DB)
                    'item_name' => 'gf-add-on-pro', 	// name of this plugin
                    'author' 	=> 'Soflyy'  // author of this plugin
                )
            );
        }
    }

    private function add_entry_data()
    {
        $entry_fields = [
            'id' => 'ID',
            'date_created' => esc_html__('Date Created', 'wpae-gf-addon'),
            'date_updated' => esc_html__('Date Updated', 'wpae-gf-addon'),
            'is_starred' => esc_html__('Starred', 'wpae-gf-addon'),
            'is_read' => esc_html__('Read', 'wpae-gf-addon'),
            'ip' => esc_html__('IP', 'wpae-gf-addon'),
            'source_url' => esc_html__('Source URL', 'wpae-gf-addon'),
            'user_agent' => esc_html__('User Agent', 'wpae-gf-addon'),
            'currency' => esc_html__('Currency', 'wpae-gf-addon'),
            'payment_status' => esc_html__('Payment Status', 'wpae-gf-addon'),
            'payment_date' => esc_html__('Payment Date', 'wpae-gf-addon'),
            'payment_amount' => esc_html__('Payment Amount', 'wpae-gf-addon'),
            'payment_method' => esc_html__('Payment Method', 'wpae-gf-addon'),
            'transaction_id' => esc_html__('Transaction ID', 'wpae-gf-addon'),
            'is_fulfilled' => esc_html__('Is Fulfilled', 'wpae-gf-addon'),
            'created_by' => esc_html__('Created By User ID', 'wpae-gf-addon'),
            'transaction_type' => esc_html__('Transaction Type', 'wpae-gf-addon'),
            'status' => esc_html__('Status', 'wpae-gf-addon')
        ];

        foreach ($entry_fields as $column => $label) {


            $data_array = [
                'element_column' => $column,
                'element_label' => $label,
                'section' => 'Entry Data',
                'filterable' => true
            ];

            if(in_array($column, $this->default_slugs)) {
                $data_array['default'] = true;
            }

            if ($column === 'date_created' || $column === 'date_updated') {
                $data_array['filterable'] = 'date';
            }

            $this->add_on->define_data_element($data_array);
        }

        $this->add_on->define_data_element([

            'element_value' => isset($this->form['title']) ? $this->form['title'] : '',
            'element_meta_key' => 'form_id',
            'element_label' => esc_html__('Form Title', 'wpae-gf-addon'),
            'section' => 'Entry Data',
            'default' => true,
            'filterable' => true
        ]);


        $this->add_on->define_data_element([

            'element_column' => 'created_by',
            'element_label' => esc_html__('Created By Username', 'wpae-gf-addon'),
            'section' => 'Entry Data',
            'callable_function' => function ($user_id) {

                $user = get_user_by('id', $user_id);
                if (is_object($user)) {
                    return $user->user_login;
                } else {
                    return '';
                }

            }
        ]);
    }

    private function add_entry_notes_data()
    {
        $note_data = [
            'user_name' => esc_html__('Note Username', 'wpae-gf-addon'),
            'user_id' => esc_html__('Note User ID', 'wpae-gf-addon'),
            'date_created' => esc_html__('Note Date Created', 'wpae-gf-addon'),
            'value' => esc_html__('Note Value', 'wpae-gf-addon'),
            'note_type' => esc_html__('Note Type', 'wpae-gf-addon'),
            'sub_type' => esc_html__('Note Sub Type', 'wpae-gf-addon')
        ];

        foreach ($note_data as $column => $label) {

            $element_data = [
                'element_location' => 'gf_entry_notes',
                'element_column' => $column,
                'element_label' => $label,
                'section' => 'Entry Notes',
            ];

            if(in_array($column, $this->default_slugs) && $column !== 'date_created') {
                $element_data['default'] = true;
            }

            $this->add_on->define_data_element($element_data);
        }
    }

    public function render_filters()
    {

        $sections = $this->add_on->get_filter_data();

        foreach ($sections as $slug => $section) :
            ?>
            <optgroup label="<?php echo $section['title']; ?>">
                <?php
                if(isset($section['meta'])) {
                    foreach ($section['meta'] as $cur_meta_key => $field) {
                        $field_label = is_array($field) ? $field['label'] : $cur_meta_key;
                        $field_name = is_array($field) ? $field['name'] : $field;

                        $field_data = $this->add_on->get_data_element_by_slug($field_label);

                        if (isset($field_data['element_location']) && strpos($field_data['element_location'], 'meta') !== false) {
                            ?>
                            <option value="cf_<?php echo $field_label; ?>"><?php echo $field_name; ?></option>
                            <?php
                        } else if (isset($field_data['element_location'])) {
                            ?>
                            <option value="rt_<?php echo $field_label; ?>"><?php echo $field_name; ?></option>
                            <?php
                        } else {
                            ?>
                            <option value="<?php echo $field_label; ?>"><?php echo $field_name; ?></option>
                            <?php

                        }
                    }
                }
                ?>
            </optgroup>
            <?php

        endforeach;


    }

    private function has_nested_array($a)
    {
        foreach ($a as $v) if (is_array($v)) {
            return true;
        } else {
            return false;
        }

    }
}

add_action('admin_init', function(){
    $add_on = GF_Export_Add_On::get_instance();
    $add_on->run();
}, 9);

