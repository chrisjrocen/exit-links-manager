<?php
/**
 * Plugin Name: Exit Links Manager
 * Plugin URI: https://github.com/chrisjrocen/exit-links-manager
 * Description: Plugins to handle external link redirects. Warn users when they are leaving your site.
 * Version: 1.0.0
 * Author: Chris Ocen
 * Author URI: https://ocenchris.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: exit-links-manager
 * Requires at least: 4.7
 * Tested up to: 6.8.2
 * Requires PHP: 7.0
 * Tags: redirects, external links, warnings, link management
 *
 * @package Exit_Links_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EXIT_LINKS_MANAGER_VERSION', '1.0.0' );
define( 'EXIT_LINKS_MANAGER_PLUGIN_FILE', __FILE__ );
define( 'EXIT_LINKS_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EXIT_LINKS_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EXIT_LINKS_MANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class Exit_Links_Manager {

	/**
	 * Plugin instance.
	 *
	 * @var Exit_Links_Manager
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Exit_Links_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->init_content_filter();
	}

	/**
	 * Initialize content filter functionality.
	 */
	private function init_content_filter() {
		require_once EXIT_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-exit-links-manager-content-filter.php';

		new Exit_Links_Manager_Content_Filter();
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {

		$this->create_leaving_page();

		add_rewrite_rule( '^leaving/?$', 'index.php?leaving_page=1', 'top' );

		flush_rewrite_rules();
	}

	/**
	 * Create leaving page if it doesn't exist.
	 */
	private function create_leaving_page() {
		// Check if page with slug 'leaving' already exists.
		$existing_page = get_page_by_path( 'leaving' );

		if ( $existing_page ) {
			return $existing_page->ID;
		}

		// Create the leaving page.
		$page_content = '<!-- This page is automatically managed by Exit Links Manager plugin -->';
		$page_data    = array(
			'post_title'   => __( 'Leaving Site', 'exit-links-manager' ),
			'post_content' => $page_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'leaving',
			'post_author'  => 1,
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			do_action( 'qm/debug', 'Exit Links Manager: Failed to create leaving page - ' . $page_id->get_error_message() );
			return false;
		}

		return $page_id;
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'Exit_Links_Manager_cleanup' );

		flush_rewrite_rules();
	}
}

// Initialize the plugin.
Exit_Links_Manager::get_instance();
