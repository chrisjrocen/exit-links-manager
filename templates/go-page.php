<?php

/**
 * Template for the leaving page.
 *
 * @package Exit_Links_Manager
 */

if (! defined('ABSPATH')) {
	exit;
}

$elm_encoded_url  = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
$elm_encoded_url  = $elm_encoded_url ? sanitize_text_field(wp_unslash($elm_encoded_url)) : '';
$elm_external_url = urldecode($elm_encoded_url);

if (empty($elm_external_url) || ! filter_var($elm_external_url, FILTER_VALIDATE_URL)) {
	wp_die(esc_html__('Invalid URL provided.', 'exit-links-manager'));
}

$elm_parsed_url      = wp_parse_url($elm_external_url);
$elm_external_domain = isset($elm_parsed_url['host']) ? $elm_parsed_url['host'] : $elm_external_url;

get_header();
?>

<div class="go-page-container">
    <div class="warning-icon">⚠️</div>
    <h1><?php esc_html_e('You are leaving our website', 'exit-links-manager'); ?></h1>

    <p><?php esc_html_e('You are about to visit:', 'exit-links-manager'); ?></p>
    <p class="domain"><?php echo esc_html($elm_external_domain); ?></p>

    <div class="warning-message">
        <p><?php esc_html_e('This is an external website. We are not responsible for the content, privacy policies, or practices of external sites.', 'exit-links-manager'); ?>
        </p>
    </div>

    <div>
        <a href="<?php echo esc_url($elm_external_url); ?>" class="continue-button" id="continue-btn"
            data-exit-links-processed="true">
            <?php esc_html_e('Continue to External Site', 'exit-links-manager'); ?>
        </a>
        <a onclick="window.close()" class="cancel-button">
            <?php esc_html_e('Go Back', 'exit-links-manager'); ?>
        </a>
    </div>

    <div class="site-info">
        <p><?php bloginfo('name'); ?> - <?php esc_html_e('External Link Redirect', 'exit-links-manager'); ?></p>
    </div>
</div>

<?php
get_footer();