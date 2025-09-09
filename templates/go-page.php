<?php
/**
 * Template for the leaving page.
 *
 * @package External_Links_Redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$encoded_url  = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_STRING );
$encoded_url  = $encoded_url ? sanitize_text_field( wp_unslash( $encoded_url ) ) : '';
$external_url = urldecode( $encoded_url );

if ( empty( $external_url ) || ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
	wp_die( esc_html__( 'Invalid URL provided.', 'external-links-redirects' ) );
}

$parsed_url      = wp_parse_url( $external_url );
$external_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : $external_url;

get_header();
?>

<div class="go-page-container">
	<div class="warning-icon">⚠️</div>
	<h1><?php esc_html_e( 'You are leaving our website', 'external-links-redirects' ); ?></h1>
	
	<p><?php esc_html_e( 'You are about to visit:', 'external-links-redirects' ); ?></p>
	<p class="domain"><?php echo esc_html( $external_domain ); ?></p>
	
	<div class="warning-message">
		<p><?php esc_html_e( 'This is an external website. We are not responsible for the content, privacy policies, or practices of external sites.', 'external-links-redirects' ); ?></p>
	</div>

	<div>
		<a href="<?php echo esc_url( $external_url ); ?>" class="continue-button" id="continue-btn">
			<?php esc_html_e( 'Continue to External Site', 'external-links-redirects' ); ?>
		</a>
		<a onclick="window.close()" class="cancel-button">
			<?php esc_html_e( 'Go Back', 'external-links-redirects' ); ?>
		</a>
	</div>

	<div class="site-info">
		<p><?php bloginfo( 'name' ); ?> - <?php esc_html_e( 'External Link Redirect', 'external-links-redirects' ); ?></p>
	</div>
</div>

<?php
get_footer();
