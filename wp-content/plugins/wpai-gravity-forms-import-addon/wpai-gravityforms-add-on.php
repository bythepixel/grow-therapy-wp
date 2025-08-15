<?php
/*
Plugin Name: WP All Import - Gravity Forms Add-On
Plugin URI: http://www.wpallimport.com/
Description: Import to Gravity Forms. Requires WP All Import & Gravity Forms.
Version: 1.0.2
Author: Soflyy
*/
/**
 * Plugin root dir with forward slashes as directory separator regardless of actuall DIRECTORY_SEPARATOR value
 * @var string
 */

define('PMGI_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
/**
 * Plugin root url for referencing static content
 * @var string
 */
define('PMGI_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));
/**
 * Plugin prefix for making names unique (be aware that this variable is used in conjuction with naming convention,
 * i.e. in order to change it one must not only modify this constant but also rename all constants, classes and functions which
 * names composed using this prefix)
 * @var string
 */
define('PMGI_PREFIX', 'pmgi_');

/**
 *  Current version.
 */
define('PMGI_VERSION', '1.0.2');

/**
 * Main plugin file, Introduces MVC pattern
 *
 * @singletone
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */

final class PMGI_Plugin {
	/**
	 * Singletone instance
	 * @var PMGI_Plugin
	 */
	protected static $instance;

	/**
	 * Plugin root dir
	 * @var string
	 */
	const ROOT_DIR = PMGI_ROOT_DIR;
	/**
	 * Plugin root URL
	 * @var string
	 */
	const ROOT_URL = PMGI_ROOT_URL;
	/**
	 * Prefix used for names of shortcodes, action handlers, filter functions etc.
	 * @var string
	 */
	const PREFIX = PMGI_PREFIX;
	/**
	 * Plugin file path
	 * @var string
	 */
	const FILE = __FILE__;

	/**
	 * Plugin text domain.
	 */
	const TEXT_DOMAIN = 'wp_all_import_gf_add_on';

	/**
	 * Return singletone instance
	 * @return PMGI_Plugin
	 */
	static public function getInstance() {
		if (self::$instance == NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return string
	 */
	static public function getEddName() {
		return 'Gravity Forms Import Add-On Pro';
	}

	/**
	 * Common logic for requestin plugin info fields
	 */
	public function __call($method, $args) {
		if (preg_match('%^get(.+)%i', $method, $mtch)) {
			$info = get_plugin_data(self::FILE);
			if (isset($info[$mtch[1]])) {
				return $info[$mtch[1]];
			}
		}
		throw new Exception("Requested method " . get_class($this) . "::$method doesn't exist.");
	}

	/**
	 * Class constructor containing dispatching logic.
	 *
	 * @param string $rootDir Plugin root dir
	 * @param string $pluginFilePath Plugin main file
	 */
	protected function __construct() {

		// register autoloading method
		spl_autoload_register(array($this, 'autoload'));

		// register helpers
		if (is_dir(self::ROOT_DIR . '/helpers')) foreach (PMGI_Helper::safe_glob(self::ROOT_DIR . '/helpers/*.php', PMGI_Helper::GLOB_RECURSE | PMGI_Helper::GLOB_PATH) as $filePath) {
			require_once $filePath;
		}

		if (is_dir(self::ROOT_DIR . '/libraries')) {
			foreach (PMGI_Helper::safe_glob(self::ROOT_DIR . '/libraries/*.php', PMGI_Helper::GLOB_RECURSE | PMGI_Helper::GLOB_PATH | PMGI_Helper::GLOB_NOSORT) as $filePath) {
				// Normalize the file path to start relative to the WordPress ABSPATH
				$relativePath = str_replace(ABSPATH, '', $filePath);

				// Check the relative path instead of the full path
				if (strpos($relativePath, 'view') === false && strpos($relativePath, 'template') === false) {
					require_once $filePath;
				}
			}
		}

		// register action handlers
		if (is_dir(self::ROOT_DIR . '/actions')) if (is_dir(self::ROOT_DIR . '/actions')) foreach (PMGI_Helper::safe_glob(self::ROOT_DIR . '/actions/*.php', PMGI_Helper::GLOB_RECURSE | PMGI_Helper::GLOB_PATH) as $filePath) {
			require_once $filePath;
			$function = $actionName = basename($filePath, '.php');
			if (preg_match('%^(.+?)[_-](\d+)$%', $actionName, $m)) {
				$actionName = $m[1];
				$priority = intval($m[2]);
			} else {
				$priority = 10;
			}
			add_action($actionName, self::PREFIX . str_replace('-', '_', $function), $priority, 99); // since we don't know at this point how many parameters each plugin expects, we make sure they will be provided with all of them (it's unlikely any developer will specify more than 99 parameters in a function)
		}

		// register filter handlers
		if (is_dir(self::ROOT_DIR . '/filters')) foreach (PMGI_Helper::safe_glob(self::ROOT_DIR . '/filters/*.php', PMGI_Helper::GLOB_RECURSE | PMGI_Helper::GLOB_PATH) as $filePath) {
			require_once $filePath;
			$function = $actionName = basename($filePath, '.php');
			if (preg_match('%^(.+?)[_-](\d+)$%', $actionName, $m)) {
				$actionName = $m[1];
				$priority = intval($m[2]);
			} else {
				$priority = 10;
			}

			add_filter($actionName, self::PREFIX . str_replace('-', '_', $function), $priority, 99); // since we don't know at this point how many parameters each plugin expects, we make sure they will be provided with all of them (it's unlikely any developer will specify more than 99 parameters in a function)
		}

		// register shortcodes handlers
		if (is_dir(self::ROOT_DIR . '/shortcodes')) foreach (PMGI_Helper::safe_glob(self::ROOT_DIR . '/shortcodes/*.php', PMGI_Helper::GLOB_RECURSE | PMGI_Helper::GLOB_PATH) as $filePath) {
			$tag = strtolower(str_replace('/', '_', preg_replace('%^' . preg_quote(self::ROOT_DIR . '/shortcodes/', '%') . '|\.php$%', '', $filePath)));
			add_shortcode($tag, [$this, 'shortcodeDispatcher']);
		}

		// register admin page pre-dispatcher
		add_action('admin_init', [$this, 'adminInit'], 1);
		add_action('init', [$this, 'init'], 10);
	}

	/**
	 *  Load plugin language text domain.
	 */
	public function init(){
		$this->load_plugin_textdomain();
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), PMGI_Plugin::TEXT_DOMAIN );
		load_plugin_textdomain( PMGI_Plugin::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
	}

	/**
	 * pre-dispatching logic for admin page controllers
	 */
	public function adminInit() {
		wp_enqueue_style('pmgi-admin-style', PMGI_ROOT_URL . '/static/css/admin.css');
		wp_enqueue_script('pmgi-admin-script', PMGI_ROOT_URL . '/static/js/admin.js', ['jquery']);
	}

	/**
	 * Dispatch shorttag: create corresponding controller instance and call its index method.
	 *
	 * @param array $args Shortcode tag attributes
	 * @param string $content Shortcode tag content
	 * @param string $tag Shortcode tag name which is being dispatched
	 * @return string
	 */
	public function shortcodeDispatcher($args, $content, $tag) {

		$controllerName = self::PREFIX . preg_replace_callback('%(^|_).%', [$this, "replace_callback"], $tag);// capitalize first letters of class name parts and add prefix
		$controller = new $controllerName();
		if ( ! $controller instanceof PMGI_Controller) {
			throw new Exception("Shortcode `$tag` matches to a wrong controller type.");
		}
		ob_start();
		$controller->index($args, $content);
		return ob_get_clean();
	}

	/**
	 * @var null
	 */
	protected $_admin_current_screen = NULL;

	/**
	 * @return null
	 */
	public function getAdminCurrentScreen() {
		return $this->_admin_current_screen;
	}

	/**
	 * @param $matches
	 * @return string
	 */
	public function replace_callback($matches) {
		return strtoupper($matches[0]);
	}

	/**
	 * Autoloader
	 * It's assumed class name consists of prefix folloed by its name which in turn corresponds to location of source file
	 * if `_` symbols replaced by directory path separator. File name consists of prefix folloed by last part in class name (i.e.
	 * symbols after last `_` in class name)
	 * When class has prefix it's source is looked in `models`, `controllers`, `shortcodes` folders, otherwise it looked in `core` or `library` folder
	 *
	 * @param string $className
	 * @return bool
	 */
	public function autoload($className) {
		$is_prefix = false;
		$filePath = str_replace('_', '/', preg_replace('%^' . preg_quote(self::PREFIX, '%') . '%', '', strtolower($className), 1, $is_prefix)) . '.php';
		if ( ! $is_prefix) { // also check file with original letter case
			$filePathAlt = $className . '.php';
		}
		foreach ($is_prefix ? ['models', 'controllers', 'shortcodes', 'classes'] : [] as $subdir) {
			$path = self::ROOT_DIR . '/' . $subdir . '/' . $filePath;
			if (is_file($path)) {
				require $path;
				return TRUE;
			}
			if ( ! $is_prefix) {
				$pathAlt = self::ROOT_DIR . '/' . $subdir . '/' . $filePathAlt;
				if (is_file($pathAlt)) {
					require $pathAlt;
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Method returns default import options, main utility of the method is to avoid warnings when new
	 * option is introduced but already registered imports don't have it
	 */
	public static function get_default_import_options() {
		return [
			'pmgi' => [
				'fields' => [],
				'notes' => [],
				'notes_repeater_mode' => 'csv',
				'notes_repeater_mode_separator' => '|',
				'notes_repeater_mode_foreach' => '',
				'is_multiple_field_value' => [],
				'multiple_value' => [],
				'search_in_files' => [],
				'date_created' => 'now',
				'date_updated' => 'now',
				'created_by' => '',
				'user_agent' => '',
				'ip' => '',
				'source_url' => '',
				'status' => 'active',
				'status_xpath' => '',
				'starred' => 'no',
				'starred_xpath' => '',
				'read' => 'yes',
				'read_xpath' => ''
			],
			'gravity_form_title' => '',
			'is_pmgi_update_date_created' => 1,
			'is_pmgi_update_date_updated' => 1,
			'is_pmgi_update_starred' => 1,
			'is_pmgi_update_read' => 1,
			'is_pmgi_update_ip' => 1,
			'is_pmgi_update_source_url' => 1,
			'is_pmgi_update_user_agent' => 1,
			'is_pmgi_update_created_by' => 1,
			'is_pmgi_update_status' => 1,
			'is_pmgi_update_entry_notes' => 1,
			'pmgi_update_entry_notes_logic' => 'full_update',
			'pmgi_is_update_entry_fields' => 1,
			'pmgi_update_entry_fields_logic' => 'full_update',
			'pmgi_is_update_entry_fields_list' => [],
		];
	}
}

PMGI_Plugin::getInstance();

// retrieve our api URL from the DB
$wpai_gf_addon_options = get_option('PMXI_Plugin_Options');

// Favor new API URL, but fallback to old if needed.
if( !empty($wpai_gf_addon_options['info_api_url_new'])){
	$api_url = $wpai_gf_addon_options['info_api_url_new'];
}elseif( !empty($wpai_gf_addon_options['info_api_url'])){
	$api_url = $wpai_gf_addon_options['info_api_url'];
}else{
	$api_url = null;
}

if (!empty($api_url)){
	// setup the updater
	$updater = new PMGI_Updater( $api_url, __FILE__, [
		'version' 	=> PMGI_VERSION,		// current version number
		'license' 	=> false, // license key (used get_option above to retrieve from DB)
		'item_name' => PMGI_Plugin::getEddName(), 	// name of this plugin
		'author' 	=> 'Soflyy'  // author of this plugin
	]);
}
