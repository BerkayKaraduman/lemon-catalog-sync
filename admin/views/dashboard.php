<?php
/**
 * Dashboard view.
 *
 * @package LCS
 *
 * @var \LCS\Settings       $settings
 * @var array<string,mixed> $state
 * @var object              $counts
 */

use LCS\Admin\Admin;
use LCS\Settings;

defined( 'ABSPATH' ) || exit;

$lcs_choices   = Settings::choices();
$lcs_connected = $settings->has_api_key() && ! empty( $state['connection']['ok'] );
$lcs_published = (int) ( $counts->publish ?? 0 );
$lcs_draft     = (int) ( $counts->draft ?? 0 );
$lcs_total     = $lcs_published + $lcs_draft + (int) ( $counts->private ?? 0 ) + (int) ( $counts->pending ?? 0 ) + (int) ( $counts->future ?? 0 );

$lcs_cards = array(
	array(
		__( 'Connection', 'lemon-catalog-sync' ),
		$lcs_connected ? __( 'Connected', 'lemon-catalog-sync' ) : ( $settings->has_api_key() ? __( 'Not verified', 'lemon-catalog-sync' ) : __( 'No API key', 'lemon-catalog-sync' ) ),
		$lcs_connected ? 'ok' : 'warn',
	),
	array( __( 'Products', 'lemon-catalog-sync' ), (string) $lcs_total, '' ),
	array( __( 'Published', 'lemon-catalog-sync' ), (string) $lcs_published, '' ),
	array( __( 'Draft', 'lemon-catalog-sync' ), (string) $lcs_draft, '' ),
	array( __( 'Last Sync', 'lemon-catalog-sync' ), $state['last_success'] ? human_time_diff( (int) $state['last_success'] ) . ' ' . __( 'ago', 'lemon-catalog-sync' ) : __( 'Never', 'lemon-catalog-sync' ), '' ),
	array( __( 'Store', 'lemon-catalog-sync' ), '' !== $settings->store_id() ? ( (string) $settings->get( 'store_name' ) ?: '#' . $settings->store_id() ) : __( 'Not selected', 'lemon-catalog-sync' ), '' !== $settings->store_id() ? '' : 'warn' ),
	array( __( 'Mode', 'lemon-catalog-sync' ), $lcs_choices['display_mode'][ (string) $settings->get( 'display_mode' ) ] ?? '', '' ),
);
?>
<div class="wrap lcs-wrap">
	<h1><?php esc_html_e( 'Lemon Catalog Sync', 'lemon-catalog-sync' ); ?></h1>
	<?php Admin::notices(); ?>

	<div class="lcs-cards">
		<?php foreach ( $lcs_cards as $lcs_card ) : ?>
			<div class="lcs-card <?php echo $lcs_card[2] ? 'lcs-card--' . esc_attr( $lcs_card[2] ) : ''; ?>">
				<span class="lcs-card__label"><?php echo esc_html( $lcs_card[0] ); ?></span>
				<span class="lcs-card__value"><?php echo esc_html( $lcs_card[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( ! empty( $state['last_error'] ) && (int) $state['last_error_time'] >= (int) $state['last_success'] ) : ?>
		<div class="notice notice-error inline"><p><strong><?php esc_html_e( 'Last sync failed:', 'lemon-catalog-sync' ); ?></strong> <?php echo esc_html( (string) $state['last_error'] ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Quick Actions', 'lemon-catalog-sync' ); ?></h2>
	<div class="lcs-actions">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lcs-inline-form" data-lcs-busy="sync">
			<input type="hidden" name="action" value="lcs_sync_now" />
			<?php wp_nonce_field( 'lcs_sync_now' ); ?>
			<button type="submit" class="button button-primary" <?php disabled( '' === $settings->store_id() || ! $settings->has_api_key() ); ?>><?php esc_html_e( 'Sync Now', 'lemon-catalog-sync' ); ?></button>
		</form>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=lcs-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'lemon-catalog-sync' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=lcs_product' ) ); ?>"><?php esc_html_e( 'View Products', 'lemon-catalog-sync' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=lcs-elementor' ) ); ?>"><?php esc_html_e( 'Elementor Setup', 'lemon-catalog-sync' ); ?></a>
	</div>

	<?php if ( ! $settings->has_api_key() || '' === $settings->store_id() ) : ?>
		<div class="lcs-panel">
			<h2><?php esc_html_e( 'Getting started', 'lemon-catalog-sync' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Create an API key in Lemon Squeezy → Settings → API.', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Paste it in Lemon Catalog Sync → Settings (or define LCS_LEMON_API_KEY in wp-config.php) and select your store.', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Run the first sync from the Sync page.', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Design the product template (see Elementor Setup).', 'lemon-catalog-sync' ); ?></li>
			</ol>
		</div>
	<?php endif; ?>
</div>
