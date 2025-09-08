<?php
/**
 * Plugin Name: External Links Redirects
 * Plugin URI: https://wp-fundi.com/external-links-redirects
 * Description: A WordPress plugin to handle external link redirects with frontend JavaScript functionality.
 * Version: 1.0.0
 * Author: Chris Ocen
 * Author URI: https://ocenchris.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: external-links-redirects
 * Requires at least: 4.7
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package External_Links_Redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EXTERNAL_LINKS_REDIRECTS_VERSION', '1.0.0' );
define( 'EXTERNAL_LINKS_REDIRECTS_PLUGIN_FILE', __FILE__ );
define( 'EXTERNAL_LINKS_REDIRECTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EXTERNAL_LINKS_REDIRECTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EXTERNAL_LINKS_REDIRECTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class External_Links_Redirects {

	/**
	 * Plugin instance.
	 *
	 * @var External_Links_Redirects
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return External_Links_Redirects
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

		if ( is_admin() ) {
			$this->init_admin();
		}

		$this->init_content_filter();
	}

	/**
	 * Initialize admin functionality.
	 */
	private function init_admin() {
		require_once EXTERNAL_LINKS_REDIRECTS_PLUGIN_DIR . 'includes/class-external-links-redirects-admin.php';

		new External_Links_Redirects_Admin();
	}

	/**
	 * Initialize content filter functionality.
	 */
	private function init_content_filter() {
		require_once EXTERNAL_LINKS_REDIRECTS_PLUGIN_DIR . 'includes/class-external-links-redirects-content-filter.php';

		new External_Links_Redirects_Content_Filter();
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {

		add_option( 'external_links_redirects_options_enable_redirects', true );

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
		$page_content = '<!-- This page is automatically managed by External Links Redirects plugin -->';
		$page_data    = array(
			'post_title'   => __( 'Leaving Site', 'external-links-redirects' ),
			'post_content' => $page_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'leaving',
			'post_author'  => 1,
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			do_action( 'qm/debug', 'External Links Redirects: Failed to create leaving page - ' . $page_id->get_error_message() );
			return false;
		}

		update_option( 'external_links_redirects_options_leaving_page_id', $page_id );

		return $page_id;
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'external_links_redirects_cleanup' );

		flush_rewrite_rules();
	}
}

// Initialize the plugin.
External_Links_Redirects::get_instance();
