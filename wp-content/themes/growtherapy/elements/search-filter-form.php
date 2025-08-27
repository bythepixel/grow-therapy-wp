<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Element_Search_Filter_Form extends \Bricks\Element {
  public $category     = 'custom';
  public $name         = 'search-filter-form';
  public $icon         = 'fas fa-search';
  public $css_selector = '.search-filter-form-wrapper';
  public $scripts      = ['searchFilterForm'];

  public function get_label() {
    return esc_html__('Search Filter Form', 'bricks');
  }

  public function get_keywords() {
    return ['search', 'filter', 'form', 'dropdown', 'location', 'insurance', 'needs'];
  }

  public function set_control_groups() {
    $this->control_groups['dropdowns'] = [
      'title' => esc_html__('Dropdowns', 'bricks'),
      'tab'   => 'content',
    ];

    $this->control_groups['form'] = [
      'title' => esc_html__('Form Settings', 'bricks'),
      'tab'   => 'content',
    ];
  }

  public function set_controls() {
    // First Dropdown (Location)
    $this->controls['location_label'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Location Label', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Please select a state', 'bricks'),
      'placeholder' => esc_html__('Enter label and validation message', 'bricks'),
      'description' => esc_html__('This text serves as both the dropdown label and validation message when required', 'bricks'),
    ];

    $this->controls['location_placeholder'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Location Placeholder', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Location', 'bricks'),
      'placeholder' => esc_html__('Enter placeholder text', 'bricks'),
    ];

    $this->controls['location_required'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Location Required', 'bricks'),
      'type'        => 'checkbox',
      'default'     => true,
    ];

    // Second Dropdown (Insurance)
    $this->controls['insurance_label'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Insurance Label', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Please select an insurance', 'bricks'),
      'placeholder' => esc_html__('Enter label and validation message', 'bricks'),
      'description' => esc_html__('This text serves as both the dropdown label and validation message when required', 'bricks'),
    ];

    $this->controls['insurance_placeholder'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Insurance Placeholder', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Insurance', 'bricks'),
      'placeholder' => esc_html__('Enter placeholder text', 'bricks'),
    ];

    $this->controls['insurance_required'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Insurance Required', 'bricks'),
      'type'        => 'checkbox',
      'default'     => true,
    ];

    // Third Dropdown (Needs)
    $this->controls['needs_label'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Needs Label', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Please select at least one need', 'bricks'),
      'placeholder' => esc_html__('Enter label and validation message', 'bricks'),
      'description' => esc_html__('This text serves as both the dropdown label and validation message when required', 'bricks'),
    ];

    $this->controls['needs_placeholder'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Needs Placeholder', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Needs', 'bricks'),
      'placeholder' => esc_html__('Enter placeholder text', 'bricks'),
    ];

    $this->controls['needs_required'] = [
      'tab'         => 'content',
      'group'       => 'dropdowns',
      'label'       => esc_html__('Needs Required', 'bricks'),
      'type'        => 'checkbox',
      'default'     => false,
    ];

    // Form Settings
    $this->controls['search_button_text'] = [
      'tab'         => 'content',
      'group'       => 'form',
      'label'       => esc_html__('Search Button Text', 'bricks'),
      'type'        => 'text',
      'default'     => esc_html__('Search', 'bricks'),
    ];

    $this->controls['search_button_icon'] = [
      'tab'         => 'content',
      'group'       => 'form',
      'label'       => esc_html__('Search Button Icon', 'bricks'),
      'type'        => 'text',
      'default'     => 'fas fa-search',
      'placeholder' => esc_html__('fas fa-search', 'bricks'),
    ];
  }

  public function enqueue_scripts() {
    wp_enqueue_style(
      'growtherapy-search-filter-form',
      get_stylesheet_directory_uri() . '/css/search-filter-form.css',
      [],
      filemtime(get_stylesheet_directory() . '/css/search-filter-form.css')
    );

    // Enqueue the element-specific script
    wp_enqueue_script(
      'search-filter-form',
      get_stylesheet_directory_uri() . '/js/search-filter-form.js',
      [],
      filemtime(get_stylesheet_directory() . '/js/search-filter-form.js'),
      true
    );

    // Localize script with API data and settings
    wp_localize_script('search-filter-form', 'searchFilterFormData', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('search_filter_form_nonce'),
      'payorsByState' => get_filter_api_payors_by_state(),
      'statesByPayor' => get_filter_api_states_by_payor(),
    ]);
  }

  public function render() {
    $settings = $this->settings;

    // Get dropdown configurations
    $location_config = [
      'label'         => $settings['location_label'] ?? esc_html__('Select your state', 'bricks'),
      'placeholder'   => $settings['location_placeholder'] ?? esc_html__('Location', 'bricks'),
      'required'      => $settings['location_required'] ?? true,
      'single_select' => true, // Location is always single-select
      'api_key'       => 'states',
      'validation_message' => $settings['location_label'] ?? esc_html__('Please select a state', 'bricks'),
    ];

    $insurance_config = [
      'label'         => $settings['insurance_label'] ?? esc_html__('Select your carrier', 'bricks'),
      'placeholder'   => $settings['insurance_placeholder'] ?? esc_html__('Insurance', 'bricks'),
      'required'      => $settings['insurance_required'] ?? true,
      'single_select' => true, // Insurance is always single-select
      'api_key'       => 'payors',
      'validation_message' => $settings['insurance_label'] ?? esc_html__('Please select an insurance', 'bricks'),
    ];

    $needs_config = [
      'label'         => $settings['needs_label'] ?? esc_html__('Select your needs', 'bricks'),
      'placeholder'   => $settings['needs_placeholder'] ?? esc_html__('Needs', 'bricks'),
      'required'      => $settings['needs_required'] ?? false,
      'single_select' => false, // Needs is always multi-select
      'api_key'       => 'specialties',
      'validation_message' => $settings['needs_label'] ?? esc_html__('Please select at least one need', 'bricks'),
    ];

    $search_button_text = $settings['search_button_text'] ?? esc_html__('Search', 'bricks');
    $search_button_icon = $settings['search_button_icon'] ?? 'fas fa-search';

    echo <<<HTML
    <form 
      class="search-filter-form" 
      method="get" 
      action="/find/therapists"
      data-search-filter-form 
      {$this->render_attributes('_root')}
      aria-labelledby="form-title"
      aria-describedby="form-description"
    >
      <div class="sr-only">
        <h2 id="form-title">Find a Therapist</h2>
        <p id="form-description">Use the filters below to find a therapist that matches your location, insurance, and needs</p>
      </div>
      
      {$this->render_dropdown('location', $location_config)}
      {$this->render_dropdown('insurance', $insurance_config)}
      {$this->render_dropdown('needs', $needs_config)}
      
      <button type="submit" class="search-filter-form__search-button btn--s btn--primary">
        <i class="{$search_button_icon}"></i>
        <span>{$search_button_text}</span>
      </button>
    </form>
    HTML;
  }

  private function render_dropdown($type, $config) {
    $required_class = $config['required'] ? 'search-filter-form__dropdown--required' : '';
    $single_select_class = $config['single_select'] ? 'search-filter-form__dropdown--single-select' : 'search-filter-form__dropdown--multi-select';
    $required_indicator = $config['required'] ? '*' : '';
    $modal_id = 'search-filter-form__dropdown-modal-' . esc_attr($type);
    $modal_title_id = 'modal-title-' . esc_attr($type);
    $label = esc_html($config['label']);
    $placeholder = esc_html($config['placeholder']);
    $api_key = esc_attr($config['api_key']);
    $validation_message = esc_attr($config['validation_message'] ?? '');
    $required_attr = $config['required'] ? 'true' : 'false';
    
    return <<<HTML
    <div
      class="search-filter-form__dropdown {$required_class} {$single_select_class}"
      data-search-filter-form-dropdown
    >
      <button
        type="button"
        class="search-filter-form__dropdown-button"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="{$modal_id}"
        data-search-filter-form-dropdown-button
        data-placeholder="{$placeholder}{$required_indicator}"
        data-validation-message="{$validation_message}"
        data-required="{$required_attr}"
      >
        <span class="search-filter-form__dropdown-button-label">{$placeholder}{$required_indicator}</span>
        <span class="search-filter-form__dropdown-button-error-msg" role="alert" aria-live="polite"></span>
      </button>
      
      <div
        class="search-filter-form__dropdown-modal"
        id="{$modal_id}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{$modal_title_id}"
        aria-hidden="true"
        data-search-filter-form-dropdown-modal
      >
        <div class="search-filter-form__dropdown-modal-header">
          <h3 class="search-filter-form__dropdown-modal-heading">{$label}</h3>
          <button type="button" class="search-filter-form__dropdown-modal-done-button" aria-label="Close {$label} options">Done</button>
        </div>
        
        <div class="search-filter-form__dropdown-modal-options">
          <div class="search-filter-form__dropdown-modal-desktop-header">
            {$this->render_optional_text($config)}
            <button type="button" class="search-filter-form__dropdown-modal-done-button" aria-label="Close {$label} options">Done</button>
          </div>

          <div class="search-filter-form__dropdown-modal-search-input-wrapper">
            <label for="search-{$type}" class="sr-only">Search {$config['label']} options</label>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="-4 -3 32 32"
              stroke-width="1"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
            </svg>
            <input
              type="search"
              id="search-{$type}"
              class="search-filter-form__dropdown-modal-search-input"
              placeholder="Search"
              aria-describedby="search-help-{$type}"
              autocomplete="off"
              data-search-filter-form-search-input
            >
            <div id="search-help-{$type}" class="sr-only">Type to filter the available {$config['label']} options</div>
          </div>
          {$this->render_options_list($config, $modal_title_id, $placeholder, $api_key)}
        </div>
      </div>
    </div>
    HTML;
  }

  private function render_optional_text($config) {
    if (!$config['required']) {
      return '<span class="search-filter-form__dropdown-modal-optional-text">' . esc_html__('OPTIONAL', 'bricks') . '</span>';
    }
    return '';
  }

  private function render_options_list($config, $modal_title_id, $placeholder, $api_key) {
    // Get the appropriate API data based on the key
    $api_data = [];
    switch ($api_key) {
      case 'states':
        $api_data = get_filter_api_states();
        break;
      case 'payors':
        $api_data = get_filter_api_payors();
        break;
      case 'specialties':
        $api_data = get_filter_api_specialties();
        break;
    }
    
    // Generate options HTML
    $options_html = '';
    if (!empty($api_data) && is_array($api_data)) {
      foreach ($api_data as $item) {
        $value = isset($item['value']) ? $item['value'] : (isset($item['id']) ? $item['id'] : '');
        $label = isset($item['label']) ? $item['label'] : (isset($item['name']) ? $item['name'] : '');
        
        if ($value && $label) {
          $checkbox_id = 'option-' . esc_attr($api_key) . '-' . preg_replace('/[^\da-z]/i', '', $value);          ;
          $checkbox_name = $api_key . '-options';
          $multi_select_class = $config['single_select'] ? '' : 'search-filter-form__dropdown-modal-input--multi-select';
          
          // Generate search data and related data attributes
          $search_data = $this->get_search_data($api_key, $item);
          $related_data_attributes = '';
          
          if ($api_key === 'states') {
            // Add related insurance data for states
            $related_insurance = $this->get_related_insurance_for_state($value);
            if (!empty($related_insurance)) {
              $related_data_attributes .= ' data-related-insurance="' . esc_attr(json_encode($related_insurance)) . '"';
            }
          } elseif ($api_key === 'payors') {
            // Add related states data for insurance
            $related_states = $this->get_related_states_for_insurance($value);
            if (!empty($related_states)) {
              $related_data_attributes .= ' data-related-states="' . esc_attr(json_encode($related_states)) . '"';
            }
          }
          
          $options_html .= '<label class="search-filter-form__dropdown-modal-option" for="' . $checkbox_id . '">
            <input type="checkbox" 
              id="' . $checkbox_id . '" 
              name="' . $checkbox_name . '" 
              value="' . esc_attr($value) . '" 
              class="search-filter-form__dropdown-modal-input ' . $multi_select_class . '"
              ' . ($config['single_select'] ? 'data-search-filter-form-single-select' : '') . '
              data-search-data="' . esc_attr($search_data) . '"
              ' . $related_data_attributes . '
            />
            ' . esc_html($label) . '
            ' . ($config['single_select'] ? '
              <button
                type="button"
                class="search-filter-form__dropdown-modal-option-deselect"
                aria-label="Deselect ' . esc_attr($label) . '"
                title="Deselect ' . esc_attr($label) . '"
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
              </button>' : '') . '
          </label>';
        }
      }
    }
    
    // If no data, TODO
    
    return <<<HTML
    <fieldset class="search-filter-form__dropdown-modal-fieldset">
      <legend class="sr-only">{$placeholder} options</legend>
      {$options_html}
    </fieldset>
    HTML;
  }

  /**
   * Generate search data for filtering options
   * @param string $api_key - The API key (states, payors, specialties)
   * @param array $item - The item data
   * @return string - JSON encoded search data
   */
  private function get_search_data($api_key, $item) {
    if ($api_key === 'states') {
      // For states, include both full name and abbreviation
      $abbreviation = isset($item['abbreviation']) ? $item['abbreviation'] : '';
      $label = isset($item['label']) ? $item['label'] : (isset($item['name']) ? $item['name'] : '');
      
      return json_encode([
        'label' => $label,
        'abbreviation' => $abbreviation,
        'searchText' => strtolower($label . ' ' . $abbreviation)
      ]);
    } else {
      // For other dropdowns, just include the label
      $label = isset($item['label']) ? $item['label'] : (isset($item['name']) ? $item['name'] : '');
      
      return json_encode([
        'label' => $label,
        'searchText' => strtolower($label)
      ]);
    }
  }

  /**
   * Get related insurance data for a specific state
   * @param string $state_value - The state value
   * @return array - Array of related insurance data
   */
  private function get_related_insurance_for_state($state_value) {
    $payors_by_state = get_filter_api_payors_by_state();
    return isset($payors_by_state[$state_value]) ? $payors_by_state[$state_value] : [];
  }

  /**
   * Get related states data for a specific insurance
   * @param string $insurance_value - The insurance value
   * @return array - Array of related states data
   */
  private function get_related_states_for_insurance($insurance_value) {
    $states_by_payor = get_filter_api_states_by_payor();
    return isset($states_by_payor[$insurance_value]) ? $states_by_payor[$insurance_value] : [];
  }

  public static function render_builder() { ?>
    <script type="text/x-template" id="tmpl-bricks-element-search-filter-form">
      <div class="search-filter-form">
        <div class="search-filter-form__dropdown">
          <button class="search-filter-form__dropdown-button">
            <span class="search-filter-form__dropdown-button__label">{{ settings.location_placeholder || 'Location' }}{{ settings.location_required ? '*' : '' }}</span>
          </button>
        </div>
        <div class="search-filter-form__dropdown">
          <button class="search-filter-form__dropdown-button">
            <span class="search-filter-form__dropdown-button__label">{{ settings.insurance_placeholder || 'Insurance' }}{{ settings.insurance_required ? '*' : '' }}</span>
          </button>
        </div>
        <div class="search-filter-form__dropdown">
          <button class="search-filter-form__dropdown-button">
            <span class="search-filter-form__dropdown-button__label">{{ settings.needs_placeholder || 'Needs' }}{{ settings.needs_required ? '*' : '' }}</span>
          </button>
        </div>

        <button class="search-filter-form__search-button btn--s btn--primary">
          <i class="{{ settings.search_button_icon || 'fas fa-search' }}"></i>
          <span>{{ settings.search_button_text || 'Search' }}</span>
        </button>
      </div>
    </script>
  <?php }
}
