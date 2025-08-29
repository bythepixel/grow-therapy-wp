<?php
if (!defined('ABSPATH')) exit;

// =============================================================================
// BRICKS FRAMEWORK INTEGRATION
// =============================================================================

class Element_Search_Filter_Form extends \Bricks\Element {
  public $category     = 'custom';
  public $css_selector = '.search-filter-form';
  public $icon         = 'fas fa-search';
  public $name         = 'search-filter-form';
  public $scripts      = ['searchFilterForm'];

  public function get_label() {
    return esc_html__('Search Filter Form', 'bricks');
  }

  public function get_keywords() {
    return [
      'dropdown',
      'filter',
      'form',
      'insurance',
      'location',
      'needs',
      'search',
      'type of care',
    ];
  }

  // =============================================================================
  // CONTROL CONFIGURATION
  // =============================================================================

  public function set_control_groups() {
    $this->control_groups['type_of_care'] = [
      'title' => esc_html__('Type of Care', 'bricks'),
      'tab'   => 'content',
    ];

    $this->control_groups['location'] = [
      'title' => esc_html__('Location', 'bricks'),
      'tab'   => 'content',
    ];

    $this->control_groups['insurance'] = [
      'title' => esc_html__('Insurance', 'bricks'),
      'tab'   => 'content',
    ];

    $this->control_groups['need'] = [
      'title' => esc_html__('Needs', 'bricks'),
      'tab'   => 'content',
    ];

    $this->control_groups['form'] = [
      'title' => esc_html__('Form Settings', 'bricks'),
      'tab'   => 'content',
    ];
  }

  public function set_controls() {
    $this->controls['type_of_care_label'] = [
      'default'     => esc_html__('Type of Care', 'bricks'),
      'description' => esc_html__('This text appears as the dropdown label.', 'bricks'),
      'group'       => 'type_of_care',
      'label'       => esc_html__('Label', 'bricks'),
      'placeholder' => esc_html__('Type of Care', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['type_of_care_modal_heading'] = [
      'default'     => esc_html__('Select your type of care', 'bricks'),
      'description' => esc_html__('This text appears as the modal header label in mobile and in validation messages.', 'bricks'),
      'group'       => 'type_of_care',
      'label'       => esc_html__('Modal Heading', 'bricks'),
      'placeholder' => esc_html__('Select your type of care', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['type_of_care_required'] = [
      'default'     => true,
      'description' => esc_html__('Enable this to make the type of care field required.', 'bricks'),
      'label'       => esc_html__('Required', 'bricks'),
      'group'       => 'type_of_care',
      'tab'         => 'content',
      'type'        => 'checkbox',
    ];

    $this->controls['location_label'] = [
      'default'     => esc_html__('State', 'bricks'),
      'description' => esc_html__('This text appears as the dropdown label.', 'bricks'),
      'group'       => 'location',
      'label'       => esc_html__('Label', 'bricks'),
      'placeholder' => esc_html__('State', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['location_modal_heading'] = [
      'default'     => esc_html__('Select a state', 'bricks'),
      'description' => esc_html__('This text appears as the modal header label in mobile and in validation messages.', 'bricks'),
      'group'       => 'location',
      'label'       => esc_html__('Modal Heading', 'bricks'),
      'placeholder' => esc_html__('Select a state', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['location_required'] = [
      'default'     => true,
      'description' => esc_html__('Enable this to make the location field required.', 'bricks'),
      'label'       => esc_html__('Required', 'bricks'),
      'group'       => 'location',
      'tab'         => 'content',
      'type'        => 'checkbox',
    ];

    $this->controls['insurance_label'] = [
      'default'     => esc_html__('Insurance', 'bricks'),
      'description' => esc_html__('This text appears as the dropdown label.', 'bricks'),
      'group'       => 'insurance',
      'label'       => esc_html__('Label', 'bricks'),
      'placeholder' => esc_html__('Insurance', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['insurance_modal_heading'] = [
      'default'     => esc_html__('Select a carrier', 'bricks'),
      'description' => esc_html__('This text appears as the modal header label in mobile and in validation messages.', 'bricks'),
      'group'       => 'insurance',
      'label'       => esc_html__('Modal Heading', 'bricks'),
      'placeholder' => esc_html__('Select a carrier', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['insurance_required'] = [
      'default'     => true,
      'description' => esc_html__('Enable this to make the insurance field required.', 'bricks'),
      'label'       => esc_html__('Required', 'bricks'),
      'group'       => 'insurance',
      'tab'         => 'content',
      'type'        => 'checkbox',
    ];

    $this->controls['needs_label'] = [
      'default'     => esc_html__('Needs', 'bricks'),
      'description' => esc_html__('This text appears as the dropdown label.', 'bricks'),
      'group'       => 'need',
      'label'       => esc_html__('Label', 'bricks'),
      'placeholder' => esc_html__('Needs', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['needs_modal_heading'] = [
      'default'     => esc_html__('Select your needs', 'bricks'),
      'description' => esc_html__('This text appears as the modal header label in mobile and in validation messages.', 'bricks'),
      'group'       => 'need',
      'label'       => esc_html__('Modal Heading', 'bricks'),
      'placeholder' => esc_html__('Select your needs', 'bricks'),
      'tab'         => 'content',
      'type'        => 'text',
    ];

    $this->controls['needs_required'] = [
      'default'     => false,
      'description' => esc_html__('Enable this to make the needs field required.', 'bricks'),
      'label'       => esc_html__('Required', 'bricks'),
      'group'       => 'need',
      'tab'         => 'content',
      'type'        => 'checkbox',
    ];

    $this->controls['search_button_text'] = [
      'tab'         => 'content',
      'group'       => 'form',
      'label'       => esc_html__('Search Button Text', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Search', 'bricks'),
    ];
  }

  public function enqueue_scripts() {
    wp_enqueue_style(
      'growtherapy-search-filter-form',
      get_stylesheet_directory_uri() . '/css/search-filter-form.css',
      [],
      filemtime(get_stylesheet_directory() . '/css/search-filter-form.css')
    );

    wp_enqueue_script(
      'search-filter-form',
      get_stylesheet_directory_uri() . '/js/search-filter-form/index.js',
      [],
      filemtime(get_stylesheet_directory() . '/js/search-filter-form/index.js'),
      true
    );
    
    // Add module type for ES6 imports using filter
    add_filter('script_loader_tag', [$this, 'add_module_type'], 10, 3);
  }

  /**
   * Add module type attribute to script tag for ES6 imports
   */
  public function add_module_type($tag, $handle, $src) {
    if ($handle === 'search-filter-form') {
      return str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
  }

  // =============================================================================
  // RENDERING METHODS
  // =============================================================================

  public function render() {
    $settings = $this->settings;

    $dropdown_configs = [
      'type_of_care' => $this->build_dropdown_config('type-of-care', 'type_of_care', true),
      'location' => $this->build_dropdown_config('states', 'location', true),
      'insurance' => $this->build_dropdown_config('payors', 'insurance', true),
      'needs' => $this->build_dropdown_config('specialties', 'needs', false),
    ];

    $search_button_text = $settings['search_button_text'];

    echo $this->render_form_html($dropdown_configs, $search_button_text);
  }

  private function render_form_html($dropdown_configs, $search_button_text) {
    return <<<HTML
    <form 
      action="/find/therapists"
      aria-describedby="form-description"
      aria-labelledby="form-title"
      class="search-filter-form" 
      method="get" 
      {$this->render_attributes('_root')}
    >
      <div class="sr-only">
        <h2 id="form-title">Find a Therapist</h2>
        <p id="form-description">Use the filters below to find a therapist that matches your type of care, location, insurance, and needs</p>
      </div>
      
      {$this->render_dropdown($dropdown_configs['type_of_care'])}
      {$this->render_dropdown($dropdown_configs['location'])}
      {$this->render_dropdown($dropdown_configs['insurance'])}
      {$this->render_dropdown($dropdown_configs['needs'])}
      
      <button type="submit" class="search-filter-form__submit-button btn--s btn--primary">
        {$this->render_search_icon()}
        <span>{$search_button_text}</span>
      </button>
    </form>
    HTML;
  }

  private function render_dropdown($config) {
    $api_key = $config['api_key'];
    $dropdown_classes = $this->build_dropdown_classes($config);
    $label = esc_html($config['label']);

    $display_label = $label . ($config['required'] ? '*' : '');
    $modal_id = 'modal-' . $api_key;
    
    return <<<HTML
    <div
      class="search-filter-form-dropdown {$dropdown_classes}"
      data-search-filter-form-dropdown="{$api_key}"
    >
      {$this->render_modal_trigger($config, $modal_id, $display_label)}
      {$this->render_modal($config, $modal_id, $label, $api_key)}
    </div>
    HTML;
  }

  private function render_modal_trigger($config, $modal_id, $display_label) {
    $required_attr = $config['required'] ? 'true' : 'false';

    return <<<HTML
      <button
        type="button"
        class="search-filter-form-modal-trigger"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="{$modal_id}"
        data-search-filter-form-modal-trigger
        data-placeholder="{$display_label}"
        data-validation-message="{$config['validation_message']}"
        data-required="{$required_attr}"
      >
        <span
          class="search-filter-form-modal-trigger__label"
          data-search-filter-form-label
        >{$display_label}</span>
        <span
          aria-live="polite"
          class="search-filter-form__validation-message"
          role="alert"
          data-search-filter-form-validation-message
        ></span>
      </button>
    HTML;
  }

  private function render_modal($config, $modal_id, $label, $api_key) {
    $modal_heading = esc_html($config['modal_heading']);

    $optional_text = $config['required']
      ? ''
      : '<span class="search-filter-form-modal__optional-text">' . esc_html__('OPTIONAL', 'bricks') . '</span>';

    return <<<HTML
      <div
        class="search-filter-form-modal"
        id="{$modal_id}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{$modal_id}"
        aria-hidden="true"
        data-search-filter-form-dropdown-modal
      >
        <div class="search-filter-form-modal__header">
          <h3 class="search-filter-form-modal__heading">{$modal_heading}</h3>
          <button
            type="button"
            class="search-filter-form-modal__done-button"
            aria-label="Close {$label} options"
            data-search-filter-form-done-button
          >Done</button>
        </div>
        
        <div class="search-filter-form-modal__options">
          <div class="search-filter-form-modal__desktop-header">
            {$optional_text}
            <button
              type="button"
              class="search-filter-form-modal__done-button"
              aria-label="Close {$label} options"
              data-search-filter-form-done-button
            >Done</button>
          </div>

          <div class="search-filter-form-modal__search-input-wrapper">
            <label for="search-{$api_key}" class="sr-only">Search {$config['label']} options</label>
            {$this->render_search_icon()}
            <input
              autocomplete="off"
              aria-describedby="search-help-{$api_key}"
              class="search-filter-form-modal__search-input"
              data-search-filter-form-search-input
              id="search-{$api_key}"
              placeholder="Search"
              type="search"
            >
            <div id="search-help-{$api_key}" class="sr-only">Type to filter the available {$config['label']} options</div>
          </div>
          {$this->render_options_list($config, $api_key)}
        </div>
      </div>
    HTML;
  }

  private function render_option_input($checkbox_id, $checkbox_name, $value, $multi_select_class, $search_data, $config, $related_data_attributes) {
    $input_attributes = [
      'id="' . $checkbox_id . '"',
      'name="' . $checkbox_name . '"',
      'value="' . esc_attr($value) . '"',
      'class="search-filter-form-modal__input ' . $multi_select_class . '"',
      'data-search-data="' . esc_attr($search_data) . '"'
    ];
    
    if ($config['single_select']) {
      $input_attributes[] = 'data-search-filter-form-single-select';
    }
    
    if ($related_data_attributes) {
      $input_attributes[] = $related_data_attributes;
    }
    
    return '<input type="checkbox" ' . implode(' ', $input_attributes) . ' />';
  }

  private function render_deselect_button($config, $label) {
    if (!$config['single_select']) {
      return '';
    }
    
    return sprintf(
      '<button
        type="button"
        class="search-filter-form-modal__option-deselect"
        aria-label="Deselect %s"
        title="Deselect %s"
        data-search-filter-form-deselect-button
      >
        <svg
          width="12"
          height="11"
          viewBox="0 0 12 11"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          aria-hidden="true"
          focusable="false"
        >
          <path d="M10.6875 2.21875L7.40625 5.5L10.6875 8.8125C11.0938 9.1875 11.0938 9.84375 10.6875 10.2188C10.3125 10.625 9.65625 10.625 9.28125 10.2188L6 6.9375L2.6875 10.2188C2.3125 10.625 1.65625 10.625 1.28125 10.2188C0.875 9.84375 0.875 9.1875 1.28125 8.8125L4.5625 5.5L1.28125 2.21875C0.875 1.84375 0.875 1.1875 1.28125 0.8125C1.65625 0.40625 2.3125 0.40625 2.6875 0.8125L6 4.09375L9.28125 0.8125C9.65625 0.40625 10.3125 0.40625 10.6875 0.8125C11.0938 1.1875 11.0938 1.84375 10.6875 2.21875Z" fill="#353535"></path>
        </svg>
      </button>',
      esc_attr($label),
      esc_attr($label)
    );
  }

  private function render_search_icon(): string {
    return <<<SVG
            <svg
              class="search-filter-form-search-icon"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="-4 -3 32 32"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
            </svg>
SVG;
  }
  
  private function render_options_list($config, $api_key) {
    $api_data = $this->get_api_data($api_key);
    
    if (empty($api_data) || !is_array($api_data)) {
      return sprintf(
        '<fieldset class="search-filter-form-modal__fieldset">
          <legend class="sr-only">%s options</legend>
          <p class="search-filter-form-modal__no-options">No options available</p>
        </fieldset>',
        esc_html($api_key)
      );
    }
    
    $options_html = '';
    foreach ($api_data as $item) {
      $value = $item['value'] ?? $item['id'] ?? '';
      $label = $item['label'] ?? $item['name'] ?? '';
      
      if (!$value || !$label) {
        continue;
      }
      
      $checkbox_id = 'option-' . esc_attr($api_key) . '-' . preg_replace('/[^\da-z]/i', '', $value);
      $checkbox_name = $api_key . '-options';
      $multi_select_class = $config['single_select'] ? '' : 'search-filter-form-modal__input--multi-select';
      $search_data = $this->get_search_data($api_key, $item);
      
      $related_data_attributes = '';
      if ($api_key === 'states') {
        $related_insurance = $this->get_related_insurance_for_state($value);
        if (!empty($related_insurance)) {
          $related_data_attributes = ' data-related-insurance="' . esc_attr(json_encode($related_insurance)) . '"';
        }
      } elseif ($api_key === 'payors') {
        $related_states = $this->get_related_states_for_insurance($value);
        if (!empty($related_states)) {
          $related_data_attributes = ' data-related-states="' . esc_attr(json_encode($related_states)) . '"';
        }
      }
      
      $input_html = $this->render_option_input($checkbox_id, $checkbox_name, $value, $multi_select_class, $search_data, $config, $related_data_attributes);
      $deselect_button = $this->render_deselect_button($config, $label);
      
      $options_html .= sprintf(
        '<label data-search-filter-form-option class="search-filter-form-modal__option" for="%s">
          %s
          %s
          %s
        </label>',
        esc_attr($checkbox_id),
        $input_html,
        esc_html($label),
        $deselect_button
      );
    }
    
    return sprintf(
      '<fieldset class="search-filter-form-modal__fieldset">
        <legend class="sr-only">%s options</legend>
        %s
      </fieldset>',
      esc_html($api_key),
      $options_html
    );
  }

  // =============================================================================
  // UTILITY METHODS
  // =============================================================================

  private function build_dropdown_classes(array $config): string {
    return $this->build_classes([
      'search-filter-form-dropdown--required' => $config['required'],
      'search-filter-form-dropdown--single-select' => $config['single_select'],
      'search-filter-form-dropdown--multi-select' => !$config['single_select'],
    ]);
  }

  private function build_classes(array $class_conditions): string {
    return implode(' ', array_keys(array_filter($class_conditions)));
  }

  private function build_dropdown_config($api_key, $setting_prefix, $single_select) {
    $settings = $this->settings;
    
    // Ensure required settings exist with fallbacks
    $label = $settings["{$setting_prefix}_label"] ?? '';
    $modal_heading = $settings["{$setting_prefix}_modal_heading"] ?? '';
    $required = $settings["{$setting_prefix}_required"] ?? false;
    
    return [
      'api_key' => $api_key,
      'label' => $label,
      'modal_heading' => $modal_heading,
      'required' => $required,
      'single_select' => $single_select,
      'validation_message' => $this->build_validation_message($modal_heading),
    ];
  }

  private function build_validation_message($modal_heading) {
    return 'Please ' . strtolower($modal_heading);
  }

  // =============================================================================
  // DATA PROCESSING METHODS
  // =============================================================================

  private function get_api_data($api_key) {
    $api_map = [
      'states' => 'get_filter_api_states',
      'payors' => 'get_filter_api_payors',
      'specialties' => 'get_filter_api_specialties',
      'type-of-care' => 'get_filter_api_type_of_care'
    ];
    
    return isset($api_map[$api_key]) ? call_user_func($api_map[$api_key]) : [];
  }

  /**
   * Generate search data for filtering options
   * @param string $api_key - The API key (states, payors, specialties)
   * @param array $item - The item data
   * @return string - JSON encoded search data
   */
  private function get_search_data($api_key, $item) {
    if ($api_key === 'states') {
      $abbreviation = isset($item['abbreviation']) ? $item['abbreviation'] : '';
      $label = isset($item['label']) ? $item['label'] : (isset($item['name']) ? $item['name'] : '');
      
      return json_encode([
        'label' => $label,
        'abbreviation' => $abbreviation,
        'searchText' => strtolower($label . ' ' . $abbreviation)
      ]);
    } else {
      $label = isset($item['label']) ? $item['label'] : (isset($item['name']) ? $item['name'] : '');
      
      return json_encode([
        'label' => $label,
        'searchText' => strtolower($label)
      ]);
    }
  }

  private function get_related_insurance_for_state($state_value) {
    $payors_by_state = get_filter_api_payors_by_state();
    return isset($payors_by_state[$state_value]) ? $payors_by_state[$state_value] : [];
  }

  private function get_related_states_for_insurance($insurance_value) {
    $states_by_payor = get_filter_api_states_by_payor();
    return isset($states_by_payor[$insurance_value]) ? $states_by_payor[$insurance_value] : [];
  }

  // =============================================================================
  // BUILDER TEMPLATE
  // =============================================================================

  public static function render_builder() { ?>
    <script type="text/x-template" id="tmpl-bricks-element-search-filter-form">
      <div class="search-filter-form">
        <div class="search-filter-form-dropdown">
          <button class="search-filter-form-modal-trigger">
            <span class="search-filter-form-modal-trigger__label">{{ settings.type_of_care_label }}{{ settings.type_of_care_required ? '*' : '' }}</span>
          </button>
        </div>
        <div class="search-filter-form-dropdown">
          <button class="search-filter-form-modal-trigger">
            <span class="search-filter-form-modal-trigger__label">{{ settings.location_label }}{{ settings.location_required ? '*' : '' }}</span>
          </button>
        </div>
        <div class="search-filter-form-dropdown">
          <button class="search-filter-form-modal-trigger">
            <span class="search-filter-form-modal-trigger__label">{{ settings.insurance_label }}{{ settings.insurance_required ? '*' : '' }}</span>
          </button>
        </div>
        <div class="search-filter-form-dropdown">
          <button class="search-filter-form-modal-trigger">
            <span class="search-filter-form-modal-trigger__label">{{ settings.needs_label }}{{ settings.needs_required ? '*' : '' }}</span>
          </button>
        </div>

        <button class="search-filter-form__submit-button btn--s btn--primary">
          <span>{{ settings.search_button_text }}</span>
        </button>
      </div>
    </script>
  <?php }
}
