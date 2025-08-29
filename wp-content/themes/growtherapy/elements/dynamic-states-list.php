<?php
if (!defined('ABSPATH')) exit;

// =============================================================================
// BRICKS FRAMEWORK INTEGRATION
// =============================================================================

class Element_Dynamic_States_List extends \Bricks\Element {
  public $category     = 'custom';
  public $css_selector = '.dynamic-states-list';
  public $icon         = 'fas fas-list-ol';
  public $name         = 'dynamic-states-list';

	public function get_label() {
    return esc_html__('Dynamic States List', 'bricks');
  }

  public function get_keywords() {
    return [
      'list',
      'insurance',
      'location',
    ];
  }

  // =============================================================================
  // RENDERING METHODS
  // =============================================================================

  public function render(): void {
    echo render_states_list_html();
  }

  // =============================================================================
  // BUILDER TEMPLATE
  // =============================================================================

  public static function render_builder(): void {
		echo
    '<script type="text/x-template" id="tmpl-bricks-element-dynamic-states-list">
      <div class="dynamic-states-list dynamic-states-list--builder">' .
      	render_states_list_html() .
      '</div>
    </script>';
  }
}

function render_states_list_html(): string {
	$link_classes = 'link-hover--accent text--s footer__link text--decoration-none link--neutral-semi-light';
	$text_classes = 'footer__text';
	$item_classes = 'footer__list-item';
	$markup = '';
	$states = get_filter_api_states();

	foreach ($states as $state) {
		if (empty($state['slug']) || empty($state['label'])) {
			continue;
		}

		$slug = '/therapists/' . htmlspecialchars($state['slug'], ENT_QUOTES, 'UTF-8');
		$label = htmlspecialchars($state['label'], ENT_QUOTES, 'UTF-8');

		$text = "<span class='$text_classes'>$label</span>";
		$link = "<a href='$slug' class='$link_classes'>$text</a>";
		$item = "<li class='$item_classes'>$link</li>";

		$markup .= $item;
	}

	return <<<HTML
    	$markup
    HTML;
}


