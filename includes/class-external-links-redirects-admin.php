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
			'external_links_redirects_options_enable_redirects',
			'external_links_redirects_options_enable_redirects',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'external_links_redirects_general',
			__( 'General Settings', 'external-links-redirects' ),
			array( $this, 'general_section_callback' ),
			'external-links-redirects'
		);

		add_settings_field(
			'external_links_redirects_options_enable_redirects',
			__( 'Enable Redirects', 'external-links-redirects' ),
			array( $this, 'enable_redirects_callback' ),
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
		return ( isset( $input ) && $input ) ? 1 : 0;
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
		$options = get_option( 'external_links_redirects_options_enable_redirects' );
		$value   = isset( $options ) ? $options : true;

		echo '<input type="checkbox" id="enable_redirects" name="external_links_redirects_options_enable_redirects" value="1" ' . checked( 1, $value, false ) . ' />';
		echo '<label for="enable_redirects">' . esc_html__( 'Enable external link redirects', 'external-links-redirects' ) . '</label>';
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
				settings_fields( 'external_links_redirects_options_enable_redirects' );
				do_settings_sections( 'external-links-redirects' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
