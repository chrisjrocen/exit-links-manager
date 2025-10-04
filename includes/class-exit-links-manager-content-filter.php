<?php
/**
 * Content filter functionality for Exit Links Manager plugin.
 *
 * @package Exit_Links_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content filter class for Exit Links Manager.
 */
class Exit_Links_Manager_Content_Filter {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_leaving_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 1 );
	}

	/**
	 * Enqueue frontend styles and scripts.
	 */
	public function enqueue_frontend_styles() {
		wp_enqueue_style(
			'exit-links-manager-styles',
			EXIT_LINKS_MANAGER_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			EXIT_LINKS_MANAGER_VERSION
		);

		wp_enqueue_script(
			'exit-links-manager-script',
			EXIT_LINKS_MANAGER_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			EXIT_LINKS_MANAGER_VERSION,
			true
		);

		// Localize script with site URL.
		wp_localize_script(
			'exit-links-manager-script',
			'exitLinksManager',
			array(
				'siteUrl'    => home_url(),
				'leavingUrl' => home_url( '/leaving' ),
			)
		);
	}


	/**
	 * Maybe flush rewrite rules if needed.
	 */
	public function maybe_flush_rewrite_rules() {
		if ( get_transient( 'exit_links_manager_flush_rewrite_rules' ) ) {
			flush_rewrite_rules( true );
			delete_transient( 'exit_links_manager_flush_rewrite_rules' );
		}
	}

	/**
	 * Add rewrite rule for leaving page.
	 */
	public function add_rewrite_rule() {
		add_rewrite_rule( '^leaving/?$', 'index.php?leaving_page=1', 'top' );
	}

	/**
	 * Add query vars for leaving page.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'leaving_page';
		return $vars;
	}

	/**
	 * Handle the leaving page template.
	 */
	public function handle_leaving_page() {
		if ( get_query_var( 'leaving_page' ) ) {
			// Check if we have a URL parameter.
			$encoded_url = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_URL );
			$encoded_url = $encoded_url ? sanitize_text_field( wp_unslash( $encoded_url ) ) : '';

			if ( empty( $encoded_url ) ) {
				wp_die( esc_html__( 'No URL provided.', 'exit-links-manager' ) );
			}

			$external_url = urldecode( $encoded_url );
			if ( ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
				wp_die( esc_html__( 'Invalid URL provided.', 'exit-links-manager' ) );
			}

			$this->load_page_template();
			exit;
		}
	}

	/**
	 * Load the page template for the leaving page.
	 */
	private function load_page_template() {
		global $post;

		$leaving_page = get_page_by_path( 'leaving' );

		if ( ! $leaving_page ) {
			wp_die( esc_html__( 'Leaving page not found.', 'exit-links-manager' ) );
		}

		$post = $leaving_page;
		setup_postdata( $post );

		$template_path = EXIT_LINKS_MANAGER_PLUGIN_DIR . 'templates/go-page.php';

		if ( file_exists( $template_path ) && is_readable( $template_path ) ) {
			include $template_path;
		} else {
			wp_die( esc_html__( 'Template file not found or not readable.', 'exit-links-manager' ) );
		}
	}
}
