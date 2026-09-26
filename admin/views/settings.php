<?php
/**
 * Settings view.
 *
 * @package LCS
 *
 * @var \LCS\Settings                          $settings
 * @var array<string,array<string,string>>     $stores
 * @var array<string,mixed>                    $state
 * @var array<string,array<string,string>>     $choices
 */

use LCS\Admin\Admin;

defined( 'ABSPATH' ) || exit;

$lcs_conn         = is_array( $state['connection'] ) ? $state['connection'] : array();
$lcs_store_id     = $settings->store_id();
$lcs_home         = trailingslashit( home_url() );
$lcs_select_field = static function ( string $field, array $options, string $current ): void {
	printf( '<select name="lcs[%1$s]" id="lcs-%1$s">', esc_attr( $field ) );
	foreach ( $options as $value => $label ) {
		printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
	}
	echo '</select>';
};
?>
<div class="wrap lcs-wrap">
	<h1><?php esc_html_e( 'Settings', 'lemon-catalog-sync' ); ?></h1>
	<?php Admin::notices(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
		<input type="hidden" name="action" value="lcs_save_settings" />
		<?php wp_nonce_field( 'lcs_save_settings' ); ?>

		<h2 class="title"><?php esc_html_e( 'Connection', 'lemon-catalog-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="lcs-api_key"><?php esc_html_e( 'API Key', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<?php if ( $settings->has_constant_key() ) : ?>
						<p><span class="lcs-badge lcs-badge--ok"><?php esc_html_e( 'Defined in wp-config.php', 'lemon-catalog-sync' ); ?></span> <code><?php echo esc_html( $settings->api_key_hint() ); ?></code></p>
						<p class="description"><?php esc_html_e( 'LCS_LEMON_API_KEY is set, so the key stored in the database (if any) is ignored.', 'lemon-catalog-sync' ); ?></p>
					<?php else : ?>
						<input type="password" class="regular-text" name="lcs[api_key]" id="lcs-api_key" value="" autocomplete="new-password" spellcheck="false"
							placeholder="<?php echo esc_attr( $settings->has_api_key() ? $settings->api_key_hint() : __( 'Paste your Lemon Squeezy API key', 'lemon-catalog-sync' ) ); ?>" />
						<?php if ( $settings->has_api_key() ) : ?>
							<p class="description"><?php esc_html_e( 'A key is saved. Leave empty to keep it; paste a new key to replace it.', 'lemon-catalog-sync' ); ?></p>
							<label><input type="checkbox" name="lcs[remove_api_key]" value="1" /> <?php esc_html_e( 'Remove saved API key', 'lemon-catalog-sync' ); ?></label>
						<?php elseif ( $settings->stored_key_unreadable() ) : ?>
							<p class="description lcs-text-warn"><?php esc_html_e( 'A saved key can no longer be decrypted (the site security salts changed). Please paste the key again.', 'lemon-catalog-sync' ); ?></p>
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'Recommended: define the key in wp-config.php instead:', 'lemon-catalog-sync' ); ?>
							<code>define( 'LCS_LEMON_API_KEY', '…' );</code>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Connection', 'lemon-catalog-sync' ); ?></th>
				<td>
					<?php if ( ! empty( $lcs_conn ) ) : ?>
						<span class="lcs-badge <?php echo ! empty( $lcs_conn['ok'] ) ? 'lcs-badge--ok' : 'lcs-badge--missing'; ?>"><?php echo ! empty( $lcs_conn['ok'] ) ? esc_html__( 'OK', 'lemon-catalog-sync' ) : esc_html__( 'Error', 'lemon-catalog-sync' ); ?></span>
						<?php echo esc_html( (string) $lcs_conn['message'] ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Not tested yet.', 'lemon-catalog-sync' ); ?>
					<?php endif; ?>
					<p><button type="submit" form="lcs-test-connection" class="button" <?php disabled( ! $settings->has_api_key() ); ?>><?php esc_html_e( 'Test Connection', 'lemon-catalog-sync' ); ?></button></p>
					<p class="description"><?php esc_html_e( 'Also refreshes the list of stores.', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lcs-store_id"><?php esc_html_e( 'Store', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<?php if ( $stores ) : ?>
						<select name="lcs[store_id]" id="lcs-store_id">
							<option value=""><?php esc_html_e( '— Select a store —', 'lemon-catalog-sync' ); ?></option>
							<?php foreach ( $stores as $lcs_store ) : ?>
								<option value="<?php echo esc_attr( $lcs_store['id'] ); ?>" <?php selected( $lcs_store_id, $lcs_store['id'] ); ?>>
									<?php echo esc_html( sprintf( '%s (#%s, %s)', $lcs_store['name'], $lcs_store['id'], $lcs_store['currency'] ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Products are only requested for this store (filter[store_id]).', 'lemon-catalog-sync' ); ?></p>
					<?php else : ?>
						<?php if ( '' !== $lcs_store_id ) : ?>
							<input type="hidden" name="lcs[store_id]" value="<?php echo esc_attr( $lcs_store_id ); ?>" />
							<p><?php echo esc_html( trim( $settings->get( 'store_name' ) . ' (#' . $lcs_store_id . ')' ) ); ?></p>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Save a valid API key or click "Test Connection" to load your stores.', 'lemon-catalog-sync' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Sync', 'lemon-catalog-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="lcs-sync_frequency"><?php esc_html_e( 'Automatic Sync Frequency', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<?php $lcs_select_field( 'sync_frequency', $choices['sync_frequency'], (string) $settings->get( 'sync_frequency' ) ); ?>
					<p class="description"><?php esc_html_e( 'Runs through WP-Cron. On low-traffic sites consider a real server cron that calls wp-cron.php.', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lcs-image_mode"><?php esc_html_e( 'Image Sync Mode', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<?php $lcs_select_field( 'image_mode', $choices['image_mode'], (string) $settings->get( 'image_mode' ) ); ?>
					<p class="description"><?php esc_html_e( 'Media Library mode imports each Lemon image once (no duplicates) and sets it as the featured image, unless you have chosen a featured image yourself.', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Frontend', 'lemon-catalog-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="lcs-checkout_mode"><?php esc_html_e( 'Checkout Mode', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<?php $lcs_select_field( 'checkout_mode', $choices['checkout_mode'], (string) $settings->get( 'checkout_mode' ) ); ?>
					<p class="description"><?php esc_html_e( 'Overlay loads the official Lemon.js from the Lemon Squeezy CDN, only on pages that show a buy button.', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lcs-display_mode"><?php esc_html_e( 'Frontend Display Mode', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<?php $lcs_select_field( 'display_mode', $choices['display_mode'], (string) $settings->get( 'display_mode' ) ); ?>
					<p class="description"><?php esc_html_e( 'Elementor Theme Builder (recommended): you design one global Single template. Automatic Product Block: the plugin prints image, price, description, variants and buy button above the product content (for sites without Elementor Pro).', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lcs-product_base"><?php esc_html_e( 'Product URL Base', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<code><?php echo esc_html( $lcs_home ); ?></code><input type="text" class="regular-text lcs-slug-input" name="lcs[product_base]" id="lcs-product_base" value="<?php echo esc_attr( (string) $settings->get( 'product_base' ) ); ?>" /><code>/lightroom-preset-pack/</code>
					<p class="description"><?php esc_html_e( 'Examples: urun, products, dijital-urun, magaza. Rewrite rules are refreshed once after a change.', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lcs-category_base"><?php esc_html_e( 'Product Category URL Base', 'lemon-catalog-sync' ); ?></label></th>
				<td>
					<code><?php echo esc_html( $lcs_home ); ?></code><input type="text" class="regular-text lcs-slug-input" name="lcs[category_base]" id="lcs-category_base" value="<?php echo esc_attr( (string) $settings->get( 'category_base' ) ); ?>" /><code>/preset/</code>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Uninstall', 'lemon-catalog-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Plugin data', 'lemon-catalog-sync' ); ?></th>
				<td>
					<label><input type="checkbox" name="lcs[delete_data_on_uninstall]" value="1" <?php checked( (bool) $settings->get( 'delete_data_on_uninstall' ) ); ?> /> <?php esc_html_e( 'Delete plugin settings on uninstall', 'lemon-catalog-sync' ); ?></label>
					<p class="description"><?php esc_html_e( 'Only settings, the stored API key and sync status are removed. Products, categories, Elementor content, SEO data and media are always preserved.', 'lemon-catalog-sync' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'lemon-catalog-sync' ) ); ?>
	</form>

	<form id="lcs-test-connection" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-lcs-busy="test">
		<input type="hidden" name="action" value="lcs_test_connection" />
		<?php wp_nonce_field( 'lcs_test_connection' ); ?>
	</form>
</div>
