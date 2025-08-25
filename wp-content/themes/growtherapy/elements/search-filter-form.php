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
      'default'     => esc_html__('Select your state', 'bricks'),
      'placeholder' => esc_html__('Enter label text', 'bricks'),
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
      'default'     => esc_html__('Select your carrier', 'bricks'),
      'placeholder' => esc_html__('Enter label text', 'bricks'),
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
      'default'     => esc_html__('Select your needs', 'bricks'),
      'placeholder' => esc_html__('Enter label text', 'bricks'),
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
      ['jquery'],
      '1.0.0',
      true
    );

    // Localize script with API data and settings
    wp_localize_script('search-filter-form', 'searchFilterFormData', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('search_filter_form_nonce'),
      'apiData' => [
        'states'     => get_filter_api_states(),
        'payors'    => get_filter_api_payors(),
        'specialties' => get_filter_api_specialties(),
      ],
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
    ];

    $insurance_config = [
      'label'         => $settings['insurance_label'] ?? esc_html__('Select your carrier', 'bricks'),
      'placeholder'   => $settings['insurance_placeholder'] ?? esc_html__('Insurance', 'bricks'),
      'required'      => $settings['insurance_required'] ?? true,
      'single_select' => true, // Insurance is always single-select
      'api_key'       => 'payors',
    ];

    $needs_config = [
      'label'         => $settings['needs_label'] ?? esc_html__('Select your needs', 'bricks'),
      'placeholder'   => $settings['needs_placeholder'] ?? esc_html__('Needs', 'bricks'),
      'required'      => $settings['needs_required'] ?? false,
      'single_select' => false, // Needs is always multi-select
      'api_key'       => 'specialties',
    ];

    $search_button_text = $settings['search_button_text'] ?? esc_html__('Search', 'bricks');
    $search_button_icon = $settings['search_button_icon'] ?? 'fas fa-search';

    echo <<<HTML
    <form class="search-filter-form" method="get" data-search-filter-form {$this->render_attributes('_root')}>
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
      >
        <span class="search-filter-form__dropdown-button-label">{$placeholder}{$required_indicator}</span>
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
          {$this->render_optional_text($config)}
          <h3 class="search-filter-form__dropdown-modal-heading">{$label}</h3>
          <button type="button" class="search-filter-form__dropdown-modal-done-button" aria-label="Close">Done</button>
        </div>
        
        <div class="search-filter-form__dropdown-modal-options">
          <div class="search-filter-form__dropdown-modal-search-input-wrapper">
            <label for="search-{$type}" class="sr-only">Search options</label>
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
              type="text"
              id="search-{$type}"
              class="search-filter-form__dropdown-modal-search-input"
              placeholder="Search"
              aria-describedby="search-help-{$type}"
            >
            <div id="search-help-{$type}" class="sr-only">Type to filter the available options</div>
          </div>
          {$this->render_options_list($config, $modal_title_id, $placeholder, $api_key)}
        </div>
      </div>
    </div>
    HTML;
  }

  private function render_optional_text($config) {
    if (!$config['required']) {
      return '<span class="search-filter-form__dropdown-modal__optional-text">' . esc_html__('OPTIONAL', 'bricks') . '</span>';
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
          $options_html .= '<li class="search-filter-form__dropdown-modal-option" data-value="' . esc_attr($value) . '">' . esc_html($label) . '</li>';
        }
      }
    }
    
    // If no data, show a placeholder
    if (empty($options_html)) {
      $options_html = '<li class="search-filter-form__dropdown-modal-option">No options available</li>';
    }
    
    if ($config['single_select']) {
      return <<<HTML
      <ul class="search-filter-form__dropdown-modal-options-list" role="listbox" aria-labelledby="{$modal_title_id}">
        {$options_html}
      </ul>
      HTML;
    } else {
      return <<<HTML
      <fieldset class="search-filter-form__dropdown-modal__options-fieldset">
        <legend class="sr-only">{$placeholder} options</legend>
        <ul class="search-filter-form__dropdown-modal-options-list" role="listbox" aria-labelledby="{$modal_title_id}">
          {$options_html}
        </ul>
      </fieldset>
      HTML;
    }
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
