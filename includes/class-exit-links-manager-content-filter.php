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
		add_filter( 'the_content', array( $this, 'filter_external_links' ), 20 );
		add_filter( 'the_excerpt', array( $this, 'filter_external_links' ), 20 );
		add_filter( 'widget_text', array( $this, 'filter_external_links' ), 20 );
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_leaving_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
	}

	/**
	 * Enqueue frontend styles.
	 */
	public function enqueue_frontend_styles() {
		wp_enqueue_style(
			'exit-links-manager-styles',
			EXIT_LINKS_MANAGER_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			EXIT_LINKS_MANAGER_VERSION
		);
	}

	/**
	 * Filter content to wrap external links with redirect wrapper.
	 *
	 * @param string $content The content to filter.
	 * @return string Filtered content.
	 */
	public function filter_external_links( $content ) {
		if ( is_admin() || empty( $content ) ) {
			return $content;
		}

		$current_domain = $this->get_current_domain();

		// Pattern to match external links.
		$pattern = '/<a\s+([^>]*?)href=["\']([^"\']*?)["\']([^>]*?)>(.*?)<\/a>/i';

		$content = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $current_domain ) {
				$full_match  = $matches[0];
				$before_href = $matches[1];
				$url         = $matches[2];
				$after_href  = $matches[3];
				$link_text   = $matches[4];

				if ( ! $this->is_external_url( $url, $current_domain ) ) {
					return $full_match;
				}

				if ( false !== strpos( $url, '/leaving?url=' ) ) {
					return $full_match;
				}

				// Skip if it's a mailto, tel, or other non-http link.
				if ( ! $this->is_http_url( $url ) ) {
					return $full_match;
				}

				$encoded_url = urlencode( $url );

				$redirect_url = home_url( '/leaving?url=' . $encoded_url );

				$new_link = '<a ' . $before_href . 'href="' . esc_url( $redirect_url ) . '"' . $after_href . '>' . $link_text . '</a>';

				return $new_link;
			},
			$content
		);

		return $content;
	}

	/**
	 * Check if a URL is external.
	 *
	 * @param string $url The URL to check.
	 * @param string $current_domain The current domain.
	 * @return bool True if external, false otherwise.
	 */
	private function is_external_url( $url, $current_domain ) {
		if ( empty( $url ) || '/' === $url[0] || '#' === $url[0] ) {
			return false;
		}

		// Skip protocol-relative URLs that are same domain.
		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'http:' . $url;
		}

		$parsed_url = wp_parse_url( $url );
		if ( ! $parsed_url || ! isset( $parsed_url['host'] ) ) {
			return false;
		}

		return $parsed_url['host'] !== $current_domain;
	}

	/**
	 * Check if a URL is an HTTP/HTTPS URL.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if HTTP/HTTPS, false otherwise.
	 */
	private function is_http_url( $url ) {
		$parsed_url = wp_parse_url( $url );
		return isset( $parsed_url['scheme'] ) && in_array( $parsed_url['scheme'], array( 'http', 'https' ), true );
	}

	/**
	 * Get current domain.
	 *
	 * @return string Current domain.
	 */
	private function get_current_domain() {
		$home_url    = home_url();
		$parsed_home = wp_parse_url( $home_url );
		return isset( $parsed_home['host'] ) ? $parsed_home['host'] : '';
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
			$encoded_url = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_STRING );
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

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
