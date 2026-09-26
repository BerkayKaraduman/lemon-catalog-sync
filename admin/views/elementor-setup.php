<?php
/**
 * Elementor onboarding view.
 *
 * @package LCS
 *
 * @var bool   $elementor
 * @var bool   $elementor_ok
 * @var bool   $elementor_pro
 * @var bool   $editing
 * @var string $display_mode
 * @var string $theme_builder
 */

use LCS\Admin\Admin;
use LCS\Compat;

defined( 'ABSPATH' ) || exit;

$lcs_status = static function ( bool $ok, string $yes, string $no ): void {
	printf(
		'<span class="lcs-badge %1$s">%2$s</span>',
		$ok ? 'lcs-badge--ok' : 'lcs-badge--missing',
		esc_html( $ok ? $yes : $no )
	);
};
?>
<div class="wrap lcs-wrap">
	<h1><?php esc_html_e( 'Elementor Setup', 'lemon-catalog-sync' ); ?></h1>
	<?php Admin::notices(); ?>

	<table class="widefat striped lcs-status-table">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Elementor', 'lemon-catalog-sync' ); ?></th>
				<td>
					<?php if ( ! $elementor ) : ?>
						<?php $lcs_status( false, '', __( 'Elementor is not installed/active', 'lemon-catalog-sync' ) ); ?>
						<p class="description"><?php esc_html_e( 'The plugin works without Elementor: use the Automatic Product Block display mode or the shortcodes.', 'lemon-catalog-sync' ); ?></p>
					<?php elseif ( ! $elementor_ok ) : ?>
						<?php
						/* translators: %s: minimum Elementor version. */
						$lcs_status( false, '', sprintf( __( 'Elementor %s or newer is required for the LCS widgets', 'lemon-catalog-sync' ), Compat::MIN_ELEMENTOR_VERSION ) );
						?>
					<?php else : ?>
						<?php $lcs_status( true, __( 'Active', 'lemon-catalog-sync' ) . ' (' . ELEMENTOR_VERSION . ')', '' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $elementor ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'LCS Products Elementor Editing', 'lemon-catalog-sync' ); ?></th>
					<td><?php $lcs_status( $editing, __( 'Enabled', 'lemon-catalog-sync' ), __( 'Disabled', 'lemon-catalog-sync' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme Builder', 'lemon-catalog-sync' ); ?></th>
					<td>
						<?php $lcs_status( $elementor_pro, __( 'Available', 'lemon-catalog-sync' ), __( 'Requires Elementor Pro', 'lemon-catalog-sync' ) ); ?>
						<?php if ( ! $elementor_pro ) : ?>
							<p class="description"><?php esc_html_e( 'Without Elementor Pro, set Frontend Display Mode to "Automatic Product Block". You can still design each product with "Edit with Elementor" and use the LCS widgets on it.', 'lemon-catalog-sync' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Frontend Display Mode', 'lemon-catalog-sync' ); ?></th>
					<td>
						<?php echo esc_html( 'theme_builder' === $display_mode ? __( 'Elementor Theme Builder', 'lemon-catalog-sync' ) : __( 'Automatic Product Block', 'lemon-catalog-sync' ) ); ?>
						— <a href="<?php echo esc_url( admin_url( 'admin.php?page=lcs-settings' ) ); ?>"><?php esc_html_e( 'change', 'lemon-catalog-sync' ); ?></a>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $elementor ) : ?>
		<div class="lcs-panel">
			<h2><?php esc_html_e( 'Single product template (Theme Builder)', 'lemon-catalog-sync' ); ?></h2>
			<ol class="lcs-steps">
				<li>
					<?php esc_html_e( 'Elementor → Theme Builder', 'lemon-catalog-sync' ); ?>
					<?php if ( $elementor_pro ) : ?>
						(<a href="<?php echo esc_url( $theme_builder ); ?>"><?php esc_html_e( 'open', 'lemon-catalog-sync' ); ?></a>)
					<?php endif; ?>
				</li>
				<li><?php esc_html_e( 'Create a Single template', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Display Condition → Dijital Ürünler → All', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Add "LCS Product Image"', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Add "Post Title"', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Add "LCS Product Price"', 'lemon-catalog-sync' ); ?></li>
				<li><?php esc_html_e( 'Add "LCS Buy Button"', 'lemon-catalog-sync' ); ?></li>
				<li><strong><?php esc_html_e( 'Add the "Post Content" widget', 'lemon-catalog-sync' ); ?></strong></li>
			</ol>

			<div class="notice notice-info inline lcs-emphasis">
				<p>
					<strong><?php esc_html_e( 'The Post Content widget is essential.', 'lemon-catalog-sync' ); ?></strong>
					<?php esc_html_e( 'Whatever you build for a single product with "Dijital Ürünler → (product) → Edit with Elementor" — galleries, before/after sliders, videos, tutorials, FAQ — is displayed exactly where the Post Content widget sits in the global template. Without it, per-product content is not shown.', 'lemon-catalog-sync' ); ?>
				</p>
			</div>

			<p><?php esc_html_e( 'Optional widgets from the "Lemon Catalog" category: LCS Lemon Description, LCS Variant Selector, LCS Product Meta. Dynamic Tags ("Lemon Catalog" group) are available in Elementor Pro for any widget field.', 'lemon-catalog-sync' ); ?></p>

			<h2><?php esc_html_e( 'Archive template', 'lemon-catalog-sync' ); ?></h2>
			<p><?php esc_html_e( 'Theme Builder → Archive → Display Condition → Dijital Ürünler Archive (and Ürün Kategorileri). Without Elementor Pro use the shortcode [lcs_products columns="3" limit="12"].', 'lemon-catalog-sync' ); ?></p>

			<h2><?php esc_html_e( 'What sync never changes', 'lemon-catalog-sync' ); ?></h2>
			<p><?php esc_html_e( 'Elementor data (_elementor_*), post content, excerpt, URL slug, categories, featured image chosen by you, Yoast / Rank Math data. Only Lemon data (title, price, description, image URL, variants, buy URL, status) is refreshed.', 'lemon-catalog-sync' ); ?></p>
		</div>
	<?php endif; ?>
</div>
