<?php
/**
 * Sync view.
 *
 * @package LCS
 *
 * @var \LCS\Settings           $settings
 * @var array<string,mixed>     $state
 * @var array<string,mixed>|null $lock
 * @var int                     $wait
 */

use LCS\Admin\Admin;

defined( 'ABSPATH' ) || exit;

$lcs_result = is_array( $state['last_result'] ) ? $state['last_result'] : array();
$lcs_conn   = is_array( $state['connection'] ) ? $state['connection'] : array();

if ( ! $settings->has_api_key() ) {
	$lcs_conn_label = __( 'No API key configured', 'lemon-catalog-sync' );
} elseif ( empty( $lcs_conn ) ) {
	$lcs_conn_label = __( 'API key configured (not tested yet)', 'lemon-catalog-sync' );
} else {
	$lcs_conn_label = (string) $lcs_conn['message'];
}

$lcs_env = match ( (string) $state['environment'] ) {
	'test'  => __( 'Test mode', 'lemon-catalog-sync' ),
	'live'  => __( 'Live', 'lemon-catalog-sync' ),
	default => __( 'Unknown (run a sync)', 'lemon-catalog-sync' ),
};

$lcs_rows = array(
	__( 'Connection Status', 'lemon-catalog-sync' )  => $lcs_conn_label,
	__( 'Selected Store', 'lemon-catalog-sync' )     => '' !== $settings->store_id() ? trim( $settings->get( 'store_name' ) . ' (#' . $settings->store_id() . ')' ) : __( 'None', 'lemon-catalog-sync' ),
	__( 'Environment', 'lemon-catalog-sync' )        => $lcs_env,
	__( 'Last Sync Attempt', 'lemon-catalog-sync' )  => Admin::time( (int) $state['last_attempt'] ) . ( $state['last_attempt_trigger'] ? ' — ' . $state['last_attempt_trigger'] : '' ),
	__( 'Last Successful Sync', 'lemon-catalog-sync' ) => Admin::time( (int) $state['last_success'] ),
	__( 'Total Lemon Products', 'lemon-catalog-sync' ) => (string) (int) ( $lcs_result['total'] ?? 0 ),
	__( 'Created', 'lemon-catalog-sync' )            => (string) (int) ( $lcs_result['created'] ?? 0 ),
	__( 'Updated', 'lemon-catalog-sync' )            => (string) (int) ( $lcs_result['updated'] ?? 0 ),
	__( 'Drafted', 'lemon-catalog-sync' )            => (string) (int) ( $lcs_result['drafted'] ?? 0 ),
	__( 'Unchanged', 'lemon-catalog-sync' )          => (string) (int) ( $lcs_result['unchanged'] ?? 0 ),
	__( 'Failed', 'lemon-catalog-sync' )             => (string) (int) ( $lcs_result['failed'] ?? 0 ),
	__( 'Missing from Lemon', 'lemon-catalog-sync' ) => (string) (int) ( $lcs_result['missing'] ?? 0 ),
	__( 'Missing Detection', 'lemon-catalog-sync' )  => (string) ( $lcs_result['missing_detection'] ?? '—' ),
	__( 'Current Lock Status', 'lemon-catalog-sync' ) => $lock
		/* translators: 1: owner (manual/cron), 2: human time. */
		? sprintf( __( 'Locked by %1$s sync, started %2$s ago (expires automatically)', 'lemon-catalog-sync' ), $lock['owner'], human_time_diff( $lock['time'] ) )
		: __( 'Unlocked', 'lemon-catalog-sync' ),
	__( 'Last Error', 'lemon-catalog-sync' )         => '' !== (string) $state['last_error'] ? $state['last_error'] . ' (' . Admin::time( (int) $state['last_error_time'] ) . ')' : __( 'None', 'lemon-catalog-sync' ),
);

$lcs_can_sync = $settings->has_api_key() && '' !== $settings->store_id() && ! $lock;
?>
<div class="wrap lcs-wrap">
	<h1><?php esc_html_e( 'Sync', 'lemon-catalog-sync' ); ?></h1>
	<?php Admin::notices(); ?>

	<div class="lcs-panel">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-lcs-busy="sync">
			<input type="hidden" name="action" value="lcs_sync_now" />
			<?php wp_nonce_field( 'lcs_sync_now' ); ?>
			<button type="submit" class="button button-primary button-hero" <?php disabled( ! $lcs_can_sync ); ?>><?php esc_html_e( 'SYNC NOW', 'lemon-catalog-sync' ); ?></button>
		</form>
		<?php if ( $wait > 0 ) : ?>
			<p class="description">
				<?php
				/* translators: %d: seconds. */
				echo esc_html( sprintf( __( 'Lemon Squeezy asked us to slow down. Automatic sync resumes in about %d seconds.', 'lemon-catalog-sync' ), $wait ) );
				?>
			</p>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Sync only writes Lemon-controlled data (title, status and _lcs_* fields). URL slugs, content, Elementor designs, categories and SEO data are never modified. If any API request fails, no product is drafted or deleted.', 'lemon-catalog-sync' ); ?></p>
	</div>

	<table class="widefat striped lcs-status-table">
		<tbody>
		<?php foreach ( $lcs_rows as $lcs_label => $lcs_value ) : ?>
			<tr><th scope="row"><?php echo esc_html( $lcs_label ); ?></th><td><?php echo esc_html( (string) $lcs_value ); ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $lcs_result['errors'] ) ) : ?>
		<h2><?php esc_html_e( 'Product errors in the last run', 'lemon-catalog-sync' ); ?></h2>
		<ul class="lcs-errors">
			<?php foreach ( (array) $lcs_result['errors'] as $lcs_error ) : ?>
				<li><?php echo esc_html( (string) $lcs_error ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( ! empty( $state['history'] ) ) : ?>
		<h2><?php esc_html_e( 'Recent runs', 'lemon-catalog-sync' ); ?></h2>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Time', 'lemon-catalog-sync' ); ?></th>
				<th><?php esc_html_e( 'Trigger', 'lemon-catalog-sync' ); ?></th>
				<th><?php esc_html_e( 'Result', 'lemon-catalog-sync' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( (array) $state['history'] as $lcs_entry ) : ?>
				<tr>
					<td><?php echo esc_html( Admin::time( (int) ( $lcs_entry['time'] ?? 0 ) ) ); ?></td>
					<td><?php echo esc_html( (string) ( $lcs_entry['trigger'] ?? '' ) ); ?></td>
					<td>
						<span class="lcs-badge <?php echo ! empty( $lcs_entry['ok'] ) ? 'lcs-badge--ok' : 'lcs-badge--missing'; ?>"><?php echo ! empty( $lcs_entry['ok'] ) ? esc_html__( 'OK', 'lemon-catalog-sync' ) : esc_html__( 'Failed', 'lemon-catalog-sync' ); ?></span>
						<?php echo esc_html( (string) ( $lcs_entry['message'] ?? '' ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
