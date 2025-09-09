<?php
/**
 * Template for the leaving page.
 *
 * @package Exit_Links_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$encoded_url  = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_STRING );
$encoded_url  = $encoded_url ? sanitize_text_field( wp_unslash( $encoded_url ) ) : '';
$external_url = urldecode( $encoded_url );

if ( empty( $external_url ) || ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
	wp_die( esc_html__( 'Invalid URL provided.', 'exit-links-manager' ) );
}

$parsed_url      = wp_parse_url( $external_url );
$external_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : $external_url;

get_header();
?>

<div class="go-page-container">
	<div class="warning-icon">⚠️</div>
	<h1><?php esc_html_e( 'You are leaving our website', 'exit-links-manager' ); ?></h1>
	
	<p><?php esc_html_e( 'You are about to visit:', 'exit-links-manager' ); ?></p>
	<p class="domain"><?php echo esc_html( $external_domain ); ?></p>
	
	<div class="warning-message">
		<p><?php esc_html_e( 'This is an external website. We are not responsible for the content, privacy policies, or practices of external sites.', 'exit-links-manager' ); ?></p>
	</div>

	<div>
		<a href="<?php echo esc_url( $external_url ); ?>" class="continue-button" id="continue-btn">
			<?php esc_html_e( 'Continue to External Site', 'exit-links-manager' ); ?>
		</a>
		<a onclick="window.close()" class="cancel-button">
			<?php esc_html_e( 'Go Back', 'exit-links-manager' ); ?>
		</a>
	</div>

	<div class="site-info">
		<p><?php bloginfo( 'name' ); ?> - <?php esc_html_e( 'External Link Redirect', 'exit-links-manager' ); ?></p>
	</div>
</div>

<?php
get_footer();
