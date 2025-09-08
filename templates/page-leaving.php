<?php
/**
 * Template for the leaving page.
 *
 * @package External_Links_Redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$encoded_url  = isset( $_GET['url'] ) ? sanitize_text_field( wp_unslash( $_GET['url'] ) ) : '';
$external_url = urldecode( $encoded_url );

if ( empty( $external_url ) || ! filter_var( $external_url, FILTER_VALIDATE_URL ) ) {
	wp_die( esc_html__( 'Invalid URL provided.', 'external-links-redirects' ) );
}

$parsed_url      = wp_parse_url( $external_url );
$external_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : $external_url;

$options        = get_option( 'external_links_redirects_options', array() );
$redirect_delay = isset( $options['redirect_delay'] ) ? absint( $options['redirect_delay'] ) : 0;

get_header();
?>

<style>
.leaving-page-container {
	max-width: 600px;
	margin: 50px auto;
	padding: 40px;
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	text-align: center;
}
.leaving-page-container .warning-icon {
	font-size: 48px;
	color: #ff6b35;
	margin-bottom: 20px;
}
.leaving-page-container h1 {
	color: #2c3e50;
	margin-bottom: 20px;
	font-size: 28px;
}
.leaving-page-container .domain {
	font-weight: bold;
	color: #3498db;
	word-break: break-all;
}
.leaving-page-container .warning-message {
	background-color: #fff3cd;
	border: 1px solid #ffeaa7;
	border-radius: 4px;
	padding: 20px;
	margin: 20px 0;
	color: #856404;
}
.leaving-page-container .continue-button {
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
.leaving-page-container .continue-button:hover {
	background-color: #005a87;
	color: white;
}
.leaving-page-container .cancel-button {
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
.leaving-page-container .cancel-button:hover {
	background-color: #545b62;
	color: white;
}
.leaving-page-container .countdown {
	font-size: 18px;
	color: #007cba;
	margin: 20px 0;
	font-weight: bold;
}
.leaving-page-container .site-info {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #eee;
	color: #666;
	font-size: 14px;
}
</style>

<div class="leaving-page-container">
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

<?php
get_footer();
