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
    // Enqueue the element-specific script
    wp_enqueue_script(
      'search-filter-form',
      get_template_directory_uri() . '/js/search-filter-form.js',
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
      'placeholder'   => $settings['location_placeholder'] ?? esc_html__('Location', 'bricks'),
      'required'      => $settings['location_required'] ?? true,
      'single_select' => true, // Location is always single-select
      'api_key'       => 'states',
    ];

    $insurance_config = [
      'placeholder'   => $settings['insurance_placeholder'] ?? esc_html__('Insurance', 'bricks'),
      'required'      => $settings['insurance_required'] ?? true,
      'single_select' => true, // Insurance is always single-select
      'api_key'       => 'payors',
    ];

    $needs_config = [
      'placeholder'   => $settings['needs_placeholder'] ?? esc_html__('Needs', 'bricks'),
      'required'      => $settings['needs_required'] ?? false,
      'single_select' => false, // Needs is always multi-select
      'api_key'       => 'specialties',
    ];

    $search_button_text = $settings['search_button_text'] ?? esc_html__('Search', 'bricks');
    $search_button_icon = $settings['search_button_icon'] ?? 'fas fa-search';

    // Build the form HTML
    $output = "<div {$this->render_attributes('_root')}>";
    $output .= '<form class="search-filter-form" method="get">';
    
    // Location Dropdown
    $output .= $this->render_dropdown('location', $location_config);
    
    // Insurance Dropdown
    $output .= $this->render_dropdown('insurance', $insurance_config);
    
    // Needs Dropdown
    $output .= $this->render_dropdown('needs', $needs_config);
    
    // Search Button
    $output .= '<button type="submit" class="search-button">';
    $output .= '<i class="' . esc_attr($search_button_icon) . '"></i>';
    $output .= '<span>' . esc_html($search_button_text) . '</span>';
    $output .= '</button>';
    
    $output .= '</form>';
    $output .= '</div>';

    echo $output;
  }

  private function render_dropdown($type, $config) {
    $required_class = $config['required'] ? 'search-filter-dropdown-wrapper--required' : '';
    $single_select_class = $config['single_select'] ? 'search-filter-dropdown-wrapper--single-select' : 'search-filter-dropdown-wrapper--multi-select';
    $required_attr = $config['required'] ? 'required' : '';
    $required_indicator = $config['required'] ? ' *' : '';
    
    $output = '<div class="search-filter-dropdown-wrapper ' . esc_attr($required_class) . ' ' . esc_attr($single_select_class) . '">';
    
    // Dropdown Trigger
    $output .= '<button class="dropdown-trigger" data-dropdown="' . esc_attr($type) . '">';
    $output .= '<span class="dropdown-text">' . esc_html($config['placeholder']) . $required_indicator . '</span>';
    $output .= '<span class="dropdown-arrow"></span>';
    $output .= '</button>';
    
    // Hidden Input Fields
    if ($config['single_select']) {
      $output .= '<input type="hidden" name="' . esc_attr($type) . '" ' . $required_attr . '>';
    } else {
      $output .= '<input type="hidden" name="' . esc_attr($type) . '[]" ' . $required_attr . '>';
    }
    
    // Dropdown Modal (will be populated by JavaScript)
    $output .= '<div class="dropdown-modal" id="modal-' . esc_attr($type) . '">';
    $output .= '<div class="modal-header">';
    
    if (!$config['required']) {
      $output .= '<span class="optional-text">' . esc_html__('OPTIONAL', 'bricks') . '</span>';
    }
    
    if (!$config['single_select']) {
      $output .= '<button type="button" class="done-button">' . esc_html__('Done', 'bricks') . '</button>';
    }
    
    $output .= '</div>';
    $output .= '<div class="search-input-wrapper">';
    $output .= '<input type="text" class="search-input" placeholder="' . esc_attr__('Search', 'bricks') . '">';
    $output .= '</div>';
    $output .= '<div class="options-list" data-api-key="' . esc_attr($config['api_key']) . '"></div>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
  }

  public static function render_builder() { ?>
    <script type="text/x-template" id="tmpl-bricks-element-search-filter-form">
      <div class="search-filter-form-wrapper">
        <div class="builder-preview">
          <div class="dropdown-preview">
            <span class="preview-text">{{ settings.location_placeholder || 'Location' }}{{ settings.location_required ? ' *' : '' }}</span>
          </div>
          <div class="dropdown-preview">
            <span class="preview-text">{{ settings.insurance_placeholder || 'Insurance' }}{{ settings.insurance_required ? ' *' : '' }}</span>
          </div>
          <div class="dropdown-preview">
            <span class="preview-text">{{ settings.needs_placeholder || 'Needs' }}{{ settings.needs_required ? ' *' : '' }}</span>
          </div>
          <button class="search-button-preview">
            <i class="{{ settings.search_button_icon || 'fas fa-search' }}"></i>
            <span>{{ settings.search_button_text || 'Search' }}</span>
          </button>
        </div>
      </div>
    </script>
  <?php }
}
