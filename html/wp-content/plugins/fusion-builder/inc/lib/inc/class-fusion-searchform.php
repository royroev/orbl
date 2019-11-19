<?php
/**
 * Searchform
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion-Library
 * @since      2.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Get & set setting values.
 */
class Fusion_Searchform {

	/**
	 * Outputs search form.
	 *
	 * @param array $args Search form arguments.
	 * @return void
	 */
	public static function get_form( $args ) {
		if ( ! is_array( $args ) ) {
			// Set an empty array and allow default arguments to take over.
			$args = [];
		}

		// Defaults are to echo and to output no custom label on the form.
		$defaults = [
			'live_search' => '0',
			'design'      => 'classic',
		];

		$args = wp_parse_args( $args, $defaults );

		$class = '';

		if ( $args['live_search'] ) {
			$class .= ' fusion-live-search';
		}

		if ( 'classic' === $args['design'] ) {
			$class .= ' fusion-search-form-classic';
		} elseif ( 'clean' === $args['design'] ) {
			$class .= ' fusion-search-form-clean';
		}

		$is_live_search = $args['live_search'];

		?>
		<form role="search" class="searchform fusion-search-form <?php echo esc_attr( $class ); ?>" method="get" action="<?php echo esc_url_raw( home_url( '/' ) ); ?>">
			<div class="fusion-search-form-content">
				<div class="fusion-search-field search-field">
					<label><span class="screen-reader-text"><?php esc_attr_e( 'Search for:', 'fusion-builder' ); ?></span>
						<?php if ( $is_live_search ) : ?>
							<input type="search" class="s fusion-live-search-input" name="s" id="fusion-live-search-input" autocomplete="off" placeholder="<?php esc_html_e( 'Search ...', 'fusion-builder' ); ?>" required aria-required="true" aria-label="<?php esc_html_e( 'Search ...', 'fusion-builder' ); ?>"/>
						<?php else : ?>
							<input type="search" value="" name="s" class="s" placeholder="<?php esc_html_e( 'Search ...', 'fusion-builder' ); ?>" required aria-required="true" aria-label="<?php esc_html_e( 'Search ...', 'fusion-builder' ); ?>"/>
						<?php endif; ?>
					</label>
				</div>
				<div class="fusion-search-button search-button">
					<input type="submit" class="fusion-search-submit searchsubmit" value="&#xf002;" />
					<?php if ( $is_live_search ) : ?>
					<div class="fusion-slider-loading"></div>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $is_live_search ) : ?>
				<div class="fusion-search-results-wrapper"><div class="fusion-search-results"></div></div>
			<?php endif; ?>
		</form>
		<?php
	}

}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */