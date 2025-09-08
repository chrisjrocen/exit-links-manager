<?php
/**
 * Admin functionality for External Links Redirects plugin.
 *
 * @package External_Links_Redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class for External Links Redirects.
 */
class External_Links_Redirects_Admin {

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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'External Links Redirects', 'external-links-redirects' ),
			__( 'External Links Redirects', 'external-links-redirects' ),
			'manage_options',
			'external-links-redirects',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'external_links_redirects_options',
			'external_links_redirects_options',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'external_links_redirects_general',
			__( 'General Settings', 'external-links-redirects' ),
			array( $this, 'general_section_callback' ),
			'external-links-redirects'
		);

		add_settings_field(
			'enable_redirects',
			__( 'Enable Redirects', 'external-links-redirects' ),
			array( $this, 'enable_redirects_callback' ),
			'external-links-redirects',
			'external_links_redirects_general'
		);

		add_settings_field(
			'redirect_delay',
			__( 'Redirect Delay (seconds)', 'external-links-redirects' ),
			array( $this, 'redirect_delay_callback' ),
			'external-links-redirects',
			'external_links_redirects_general'
		);
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input Input options.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		if ( isset( $input['enable_redirects'] ) ) {
			$sanitized['enable_redirects'] = (bool) $input['enable_redirects'];
		}

		if ( isset( $input['redirect_delay'] ) ) {
			$sanitized['redirect_delay'] = absint( $input['redirect_delay'] );
		}

		return $sanitized;
	}

	/**
	 * General section callback.
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure the general settings for external link redirects.', 'external-links-redirects' ) . '</p>';
	}

	/**
	 * Enable redirects callback.
	 */
	public function enable_redirects_callback() {
		$options = get_option( 'external_links_redirects_options', array() );
		$value   = isset( $options['enable_redirects'] ) ? $options['enable_redirects'] : true;

		echo '<input type="checkbox" id="enable_redirects" name="external_links_redirects_options[enable_redirects]" value="1" ' . checked( 1, $value, false ) . ' />';
		echo '<label for="enable_redirects">' . esc_html__( 'Enable external link redirects', 'external-links-redirects' ) . '</label>';
	}

	/**
	 * Redirect delay callback.
	 */
	public function redirect_delay_callback() {
		$options = get_option( 'external_links_redirects_options', array() );
		$value   = isset( $options['redirect_delay'] ) ? $options['redirect_delay'] : 0;

		echo '<input type="number" id="redirect_delay" name="external_links_redirects_options[redirect_delay]" value="' . esc_attr( $value ) . '" min="0" max="10" />';
		echo '<p class="description">' . esc_html__( 'Delay in seconds before redirecting to external links (0-10 seconds).', 'external-links-redirects' ) . '</p>';
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_external-links-redirects' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'external-links-redirects-admin',
			EXTERNAL_LINKS_REDIRECTS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			EXTERNAL_LINKS_REDIRECTS_VERSION
		);
	}

	/**
	 * Admin page callback.
	 */
	public function admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'external_links_redirects_options' );
				do_settings_sections( 'external-links-redirects' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
