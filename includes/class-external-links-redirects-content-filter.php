<?php
/**
 * Content filter functionality for External Links Redirects plugin.
 *
 * @package External_Links_Redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content filter class for External Links Redirects.
 */
class External_Links_Redirects_Content_Filter {

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
		add_action( 'template_redirect', array( $this, 'handle_direct_leaving_page_access' ) );
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
			$encoded_url = isset( $_GET['url'] ) ? sanitize_text_field( wp_unslash( $_GET['url'] ) ) : '';

			if ( empty( $encoded_url ) ) {
				wp_die( esc_html__( 'No URL provided.', 'external-links-redirects' ) );
			}

			// Validate the URL.
			$external_url = urldecode( $encoded_url );
			if ( ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
				wp_die( esc_html__( 'Invalid URL provided.', 'external-links-redirects' ) );
			}

			// Load the page template.
			$this->load_page_template();
			exit;
		}
	}

	/**
	 * Handle direct access to the leaving page.
	 */
	public function handle_direct_leaving_page_access() {
		// Check if we're on the leaving page directly.
		if ( is_page( 'leaving' ) ) {
			// Check if we have a URL parameter.
			$encoded_url = isset( $_GET['url'] ) ? sanitize_text_field( wp_unslash( $_GET['url'] ) ) : '';

			if ( empty( $encoded_url ) ) {
				// Redirect to home page if no URL parameter.
				wp_redirect( home_url() );
				exit;
			}

			// Validate the URL.
			$external_url = urldecode( $encoded_url );
			if ( ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
				wp_die( esc_html__( 'Invalid URL provided.', 'external-links-redirects' ) );
			}

			// The page template will handle the display.
		}
	}

	/**
	 * Load the page template for the leaving page.
	 */
	private function load_page_template() {
		// Set up global query for the leaving page.
		global $wp_query, $post;

		// Get the leaving page.
		$leaving_page = get_page_by_path( 'leaving' );

		if ( ! $leaving_page ) {
			wp_die( esc_html__( 'Leaving page not found.', 'external-links-redirects' ) );
		}

		// Set up the post data.
		$post = $leaving_page;
		setup_postdata( $post );

		// Set up the query.
		$wp_query->is_page              = true;
		$wp_query->is_singular          = true;
		$wp_query->is_home              = false;
		$wp_query->is_archive           = false;
		$wp_query->is_category          = false;
		$wp_query->is_tag               = false;
		$wp_query->is_tax               = false;
		$wp_query->is_author            = false;
		$wp_query->is_date              = false;
		$wp_query->is_year              = false;
		$wp_query->is_month             = false;
		$wp_query->is_day               = false;
		$wp_query->is_time              = false;
		$wp_query->is_search            = false;
		$wp_query->is_feed              = false;
		$wp_query->is_comment_feed      = false;
		$wp_query->is_trackback         = false;
		$wp_query->is_404               = false;
		$wp_query->is_paged             = false;
		$wp_query->is_admin             = false;
		$wp_query->is_attachment        = false;
		$wp_query->is_single            = false;
		$wp_query->is_preview           = false;
		$wp_query->is_robots            = false;
		$wp_query->is_posts_page        = false;
		$wp_query->is_post_type_archive = false;

		// Load the template.
		$template_path = EXTERNAL_LINKS_REDIRECTS_PLUGIN_DIR . 'templates/page-leaving.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback to the original template method.
			$this->display_leaving_page();
		}
	}

	/**
	 * Display the leaving page (fallback method).
	 */
	private function display_leaving_page() {
		$encoded_url  = isset( $_GET['url'] ) ? sanitize_text_field( wp_unslash( $_GET['url'] ) ) : '';
		$external_url = urldecode( $encoded_url );

		if ( empty( $external_url ) || ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
			wp_die( esc_html__( 'Invalid URL provided.', 'external-links-redirects' ) );
		}

		$parsed_url      = wp_parse_url( $external_url );
		$external_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : $external_url;

		$options        = get_option( 'external_links_redirects_options', array() );
		$redirect_delay = isset( $options['redirect_delay'] ) ? absint( $options['redirect_delay'] ) : 0;

		$this->load_leaving_template( $external_url, $external_domain, $redirect_delay );
	}

	/**
	 * Load the leaving page template.
	 *
	 * @param string $external_url The external URL.
	 * @param string $external_domain The external domain.
	 * @param int    $redirect_delay The redirect delay in seconds.
	 */
	private function load_leaving_template( $external_url, $external_domain, $redirect_delay ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Leaving Site', 'external-links-redirects' ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					line-height: 1.6;
					color: #333;
					background-color: #f8f9fa;
					margin: 0;
					padding: 20px;
				}
				.container {
					max-width: 600px;
					margin: 50px auto;
					background: white;
					padding: 40px;
					border-radius: 8px;
					box-shadow: 0 2px 10px rgba(0,0,0,0.1);
					text-align: center;
				}
				.warning-icon {
					font-size: 48px;
					color: #ff6b35;
					margin-bottom: 20px;
				}
				h1 {
					color: #2c3e50;
					margin-bottom: 20px;
					font-size: 28px;
				}
				.domain {
					font-weight: bold;
					color: #3498db;
					word-break: break-all;
				}
				.warning-message {
					background-color: #fff3cd;
					border: 1px solid #ffeaa7;
					border-radius: 4px;
					padding: 20px;
					margin: 20px 0;
					color: #856404;
				}
				.continue-button {
					display: inline-block;
					background-color: #007cba;
					color: white;
					padding: 12px 30px;
					text-decoration: none;
					border-radius: 4px;
					font-weight: bold;
					margin: 20px 10px;
					transition: background-color 0.3s;
				}
				.continue-button:hover {
					background-color: #005a87;
					color: white;
				}
				.cancel-button {
					display: inline-block;
					background-color: #6c757d;
					color: white;
					padding: 12px 30px;
					text-decoration: none;
					border-radius: 4px;
					font-weight: bold;
					margin: 20px 10px;
					transition: background-color 0.3s;
				}
				.cancel-button:hover {
					background-color: #545b62;
					color: white;
				}
				.countdown {
					font-size: 18px;
					color: #007cba;
					margin: 20px 0;
					font-weight: bold;
				}
				.site-info {
					margin-top: 30px;
					padding-top: 20px;
					border-top: 1px solid #eee;
					color: #666;
					font-size: 14px;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="warning-icon">⚠️</div>
				<h1><?php esc_html_e( 'You are leaving our website', 'external-links-redirects' ); ?></h1>
				
				<p><?php esc_html_e( 'You are about to visit:', 'external-links-redirects' ); ?></p>
				<p class="domain"><?php echo esc_html( $external_domain ); ?></p>
				
				<div class="warning-message">
					<p><?php esc_html_e( 'This is an external website. We are not responsible for the content, privacy policies, or practices of external sites.', 'external-links-redirects' ); ?></p>
				</div>

				<?php if ( $redirect_delay > 0 ) : ?>
					<div class="countdown" id="countdown">
						<?php
						printf(
							/* translators: %d: number of seconds */
							esc_html__( 'Redirecting automatically in %d seconds...', 'external-links-redirects' ),
							$redirect_delay
						);
						?>
					</div>
				<?php endif; ?>

				<div>
					<a href="<?php echo esc_url( $external_url ); ?>" class="continue-button" id="continue-btn">
						<?php esc_html_e( 'Continue to External Site', 'external-links-redirects' ); ?>
					</a>
					<a href="javascript:history.back()" class="cancel-button">
						<?php esc_html_e( 'Go Back', 'external-links-redirects' ); ?>
					</a>
				</div>

				<div class="site-info">
					<p><?php bloginfo( 'name' ); ?> - <?php esc_html_e( 'External Link Redirect', 'external-links-redirects' ); ?></p>
				</div>
			</div>

			<?php if ( $redirect_delay > 0 ) : ?>
				<script>
				(function() {
					var countdown = <?php echo esc_js( $redirect_delay ); ?>;
					var countdownElement = document.getElementById('countdown');
					var continueBtn = document.getElementById('continue-btn');
					
					function updateCountdown() {
						if (countdown > 0) {
							countdownElement.textContent = '<?php esc_js_e( 'Redirecting automatically in', 'external-links-redirects' ); ?> ' + countdown + ' <?php esc_js_e( 'seconds...', 'external-links-redirects' ); ?>';
							countdown--;
							setTimeout(updateCountdown, 1000);
						} else {
							window.location.href = continueBtn.href;
						}
					}
					
					updateCountdown();
				})();
				</script>
			<?php endif; ?>

			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}
}
