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
    // Enqueue the utilities CSS
    wp_enqueue_style(
      'growtherapy-utilities',
      get_stylesheet_directory_uri() . '/css/utilities.css',
      [],
      '1.0.0'
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
    $required_indicator = $config['required'] ? '*' : '';
    $modal_id = 'search-filter-options-modal-' . esc_attr($type);
    $modal_title_id = 'modal-title-' . esc_attr($type);
    
    $output = '<div class="search-filter-dropdown-wrapper ' . esc_attr($required_class) . ' ' . esc_attr($single_select_class) . '">';
    
    // Trigger Button
    $output .= '<button type="button" class="search-filter-select-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="' . $modal_id . '">';
    $output .= '<span class="search-filter-select-trigger__label">' . esc_html($config['placeholder']) . $required_indicator . '</span>';
    $output .= '</button>';
    
    // Modal Dialog
    $output .= '<div class="search-filter-options-modal" id="' . $modal_id . '" role="dialog" aria-modal="true" aria-labelledby="' . $modal_title_id . '" aria-hidden="true">';
    
    // Modal Header
    $output .= '<div class="search-filter-options-modal__header">';
    
    if (!$config['required']) {
      $output .= '<span class="search-filter-options-modal__optional-text">' . esc_html__('OPTIONAL', 'bricks') . '</span>';
    }
    
    $output .= '<button type="button" class="search-filter-options-modal__done-button" aria-label="' . esc_attr__('Close', 'bricks') . '">Done</button>';
    $output .= '</div>';
    
    // Search Input
    $output .= '<div class="search-filter-options-modal__search-input-wrapper">';
    $output .= '<label for="search-' . esc_attr($type) . '" class="sr-only">' . esc_html__('Search options', 'bricks') . '</label>';
    $output .= '<input type="text" id="search-' . esc_attr($type) . '" class="search-filter-options-modal__search-input" placeholder="' . esc_attr__('Search', 'bricks') . '" aria-describedby="search-help-' . esc_attr($type) . '">';
    $output .= '<div id="search-help-' . esc_attr($type) . '" class="sr-only">' . esc_html__('Type to filter the available options', 'bricks') . '</div>';
    $output .= '</div>';
    
    // Options Container
    $output .= '<div class="search-filter-options-modal__options-container" data-api-key="' . esc_attr($config['api_key']) . '">';
    
    if ($config['single_select']) {
      // Clickable options for single select
      $output .= '<ul class="search-filter-options-modal__options-list" role="listbox" aria-labelledby="' . $modal_title_id . '">';
      $output .= '<li>Test</li>';
      $output .= '</ul>';
    } else {
      // Checkboxes for multi select
      $output .= '<fieldset class="search-filter-options-modal__checkbox-group">';
      $output .= '<legend class="sr-only">' . esc_html($config['placeholder']) . ' ' . esc_html__('options', 'bricks') . '</legend>';
      $output .= '<div class="search-filter-options-modal__checkbox-options">';
      $output .= '<li>Test</li>';
      $output .= '</div></fieldset>';
    }
    
    $output .= '</div>';
    
    // Modal Footer
    $output .= '<div class="modal-footer">';
    if (!$config['single_select']) {
      $output .= '<button type="button" class="done-button">' . esc_html__('Done', 'bricks') . '</button>';
    }
    $output .= '</div>';
    
    $output .= '</div>';
    
    // Modal Backdrop
    $output .= '<div class="modal-backdrop" aria-hidden="true"></div>';
    
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
