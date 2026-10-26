<?php
/**
 * Core plugin logic.
 *
 * @package Elementor_Mobile_Spacing_Fixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EMSF_Plugin {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'front_css' ), 99 );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'            => 1,
			'max_section_pad'    => 40,
			'max_widget_margin'  => 24,
			'collapse_empty_gap' => 1,
			'breakpoint'         => 767,
		);
	}

	/**
	 * Merged settings.
	 *
	 * @return array
	 */
	public static function settings() {
		return wp_parse_args( (array) get_option( 'emsf_settings', array() ), self::defaults() );
	}

	/**
	 * Settings page.
	 */
	public static function menu() {
		add_options_page(
			__( 'Elementor Mobile Spacing', 'upscale-mobile-spacing-fix-for-elementor' ),
			__( 'Elementor Mobile Spacing', 'upscale-mobile-spacing-fix-for-elementor' ),
			'manage_options',
			'emsf-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Register option.
	 */
	public static function register_settings() {
		register_setting(
			'emsf_settings_group',
			'emsf_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$out['enabled']            = empty( $input['enabled'] ) ? 0 : 1;
		$out['collapse_empty_gap'] = empty( $input['collapse_empty_gap'] ) ? 0 : 1;
		$out['max_section_pad']    = min( 200, max( 0, absint( $input['max_section_pad'] ?? 40 ) ) );
		$out['max_widget_margin']  = min( 200, max( 0, absint( $input['max_widget_margin'] ?? 24 ) ) );
		$out['breakpoint']         = min( 1024, max( 320, absint( $input['breakpoint'] ?? 767 ) ) );

		return $out;
	}

	/**
	 * Admin UI.
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Upscale Mobile Spacing Fix for Elementor', 'upscale-mobile-spacing-fix-for-elementor' ); ?></h1>
			<p><?php esc_html_e( 'Caps oversized padding/margins on small screens so Elementor pages stop looking broken on phones.', 'upscale-mobile-spacing-fix-for-elementor' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'emsf_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable fixes', 'upscale-mobile-spacing-fix-for-elementor' ); ?></th>
						<td><label><input type="checkbox" name="emsf_settings[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?> /> <?php esc_html_e( 'On', 'upscale-mobile-spacing-fix-for-elementor' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="emsf_bp"><?php esc_html_e( 'Mobile breakpoint (px)', 'upscale-mobile-spacing-fix-for-elementor' ); ?></label></th>
						<td><input name="emsf_settings[breakpoint]" id="emsf_bp" type="number" min="320" max="1024" value="<?php echo esc_attr( (string) $s['breakpoint'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="emsf_pad"><?php esc_html_e( 'Max section padding (px)', 'upscale-mobile-spacing-fix-for-elementor' ); ?></label></th>
						<td><input name="emsf_settings[max_section_pad]" id="emsf_pad" type="number" min="0" max="200" value="<?php echo esc_attr( (string) $s['max_section_pad'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="emsf_mar"><?php esc_html_e( 'Max widget margin (px)', 'upscale-mobile-spacing-fix-for-elementor' ); ?></label></th>
						<td><input name="emsf_settings[max_widget_margin]" id="emsf_mar" type="number" min="0" max="200" value="<?php echo esc_attr( (string) $s['max_widget_margin'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Collapse empty spacer gaps', 'upscale-mobile-spacing-fix-for-elementor' ); ?></th>
						<td><label><input type="checkbox" name="emsf_settings[collapse_empty_gap]" value="1" <?php checked( $s['collapse_empty_gap'], 1 ); ?> /> <?php esc_html_e( 'Reduce tall Elementor spacers on mobile', 'upscale-mobile-spacing-fix-for-elementor' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Front-end CSS.
	 */
	public static function front_css() {
		$s = self::settings();
		if ( empty( $s['enabled'] ) ) {
			return;
		}

		$bp  = (int) $s['breakpoint'];
		$pad = (int) $s['max_section_pad'];
		$mar = (int) $s['max_widget_margin'];

		$css  = "@media (max-width: {$bp}px){";
		$css .= ".elementor-section,.e-con.e-parent{padding-top:{$pad}px!important;padding-bottom:{$pad}px!important;}";
		$css .= ".elementor-widget:not(.elementor-widget-spacer){margin-top:0!important;margin-bottom:{$mar}px!important;}";
		$css .= ".elementor-column>.elementor-widget-wrap,.elementor-column>.elementor-element-populated{padding:8px!important;}";
		if ( ! empty( $s['collapse_empty_gap'] ) ) {
			$css .= ".elementor-widget-spacer{margin:0!important;}";
			$css .= ".elementor-widget-spacer .elementor-spacer-inner{height:16px!important;max-height:16px!important;}";
		}
		$css .= '}';

		wp_register_style( 'emsf-front', false, array(), EMSF_VERSION );
		wp_enqueue_style( 'emsf-front' );
		wp_add_inline_style( 'emsf-front', $css );
	}
}
