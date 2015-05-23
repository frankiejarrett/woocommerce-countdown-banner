<?php
/**
 * Plugin Name: WooCommerce Countdown Banner
 * Plugin URI: http://www.woothemes.com/products/woocommerce-countdown-banner/
 * Description: Display an animated store-wide countdown banner for special promotions or launches.
 * Version: 1.0.0
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Developer: Frankie Jarrett
 * Developer URI: http://frankiejarrett.com/
 * Depends: WooCommerce
 * Text Domain: woocommerce-countdown-banner
 * Domain Path: /languages
 *
 * Copyright: © 2009-2015 WooThemes.
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 *
 * Display an animated store-wide countdown banner for special promotions or launches.
 *
 * @version 1.0.0
 * @package WooCommerce
 * @author  Frankie Jarrett
 */
class WC_Countdown_Banner {

	/**
	 * Hold class instance
	 *
	 * @access public
	 * @static
	 *
	 * @var WC_Countdown_Banner
	 */
	public static $instance;

	/**
	 * Hold countdown end datetime
	 *
	 * @access public
	 * @static
	 *
	 * @var string
	 */
	public static $countdown_end;

	/**
	 * Plugin version number
	 *
	 * @const string
	 */
	const VERSION = '1.0.0';

	/**
	 * Class constructor
	 *
	 * @access private
	 */
	private function __construct() {
		if ( ! $this->woocommerce_exists() ) {
			return;
		}

		define( 'WC_COUNTDOWN_BANNER_URL', plugins_url( '/', __FILE__ ) );

		// Sanitize the countdown end time for programmatic use
		self::$countdown_end = str_replace( '@', '', (string) get_option( 'wc_countdown_banner_end' ) );

		// Use use local time if using relative time, otherwise use GMT
		if ( ! empty( self::$countdown_end ) ) {
			self::$countdown_end = self::use_relative_time() ? date( 'Y-m-d H:i:s', strtotime( self::$countdown_end ) ) : get_gmt_from_date( self::$countdown_end );
		}

		// Automatically deactivate the countdown option if time has expired
		add_action( 'init', array( $this, 'maybe_deactivate_countdown' ) );

		// Add custom settings for plugin options to General tab
		add_filter( 'woocommerce_general_settings', array( $this, 'settings' ) );

		// Enqueue timepicker addon
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Enqueue scripts and styles on the front-end
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Apply custom colors to store-wide banner
		add_action( 'wp_head', array( $this, 'wp_head' ) );

		// Add the countdown banner to the front-end
		add_action( 'wp_footer', array( $this, 'banner' ) );
	}

	/**
	 * Return an active instance of this class
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return WC_Countdown_Banner
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns true if WooCommerce exists
	 *
	 * Looks at the active list of plugins on the site to
	 * determine if WooCommerce is installed and activated.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function woocommerce_exists() {
		return in_array( 'woocommerce/woocommerce.php', (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/**
	 * Check if we should use relative time for the Countdown Banner
	 *
	 * If relative time is enabled, we will use the same countdown end time
	 * for all customer timezones.
	 *
	 * E.g. End the countdown at 3:00pm in New York and also at 3:00pm in Paris.
	 *
	 * Otherwise we will convert the time to GMT so that the countdown will end
	 * at the same "moment in time" no matter the customer timezone.
	 *
	 * E.g. End the countdown at 3:00pm in New York and at 9:00pm in Paris.
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return bool
	 */
	public static function use_relative_time() {
		return ( 'yes' === (string) get_option( 'wc_countdown_banner_relative_time' ) );
	}

	/**
	 * Check if the Countdown Banner has expired
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return bool
	 */
	public static function has_expired() {
		// Should not be considered expired when empty
		if ( empty( self::$countdown_end ) ) {
			return false;
		}

		if ( self::use_relative_time() ) {
			$expired = ( strtotime( self::$countdown_end ) < (int) date_i18n( 'U' ) );
		} else {
			$expired = ( strtotime( self::$countdown_end ) < time() );
		}

		return (bool) $expired;
	}

	/**
	 * Check if the Countdown Banner is active
	 *
	 * 1. Option must be check in the WooCommerce General settings
	 * 2. The countdown time must be in the future
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return bool
	 */
	public static function is_active() {
		if (
			( 'yes' === get_option( 'wc_countdown_banner_active' ) )
			&&
			! self::has_expired()
			&&
			( ! empty( self::$countdown_end ) || ! empty( (string) get_option( 'wc_countdown_banner_text' ) ) )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Turn the countdown option off after the countdown expires
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_deactivate_countdown() {
		if ( self::has_expired() ) {
			update_option( 'wc_countdown_banner_active', 'no' );
		}
	}

	/**
	 * Add custom settings for plugin options to General tab
	 *
	 * WooCommerce > Settings > General
	 *
	 * @filter woocommerce_settings_general
	 *
	 * @access public
	 * @since 1.0.0
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings( $settings ) {
		$timezone = str_replace( array( '_', '/' ), array( ' ', ' / ' ), get_option( 'timezone_string' ) );

		$countdown_settings = array(
			array(
				'name' => __( 'Countdown Banner Options', 'woocommerce-countdown-banner' ),
				'id'   => 'wc_countdown_banner',
				'type' => 'title',
				'desc' => __( 'The following options are used to configure a site-wide countdown banner.', 'woocommerce-countdown-banner' ),
			),
			array(
				'name' => __( 'Countdown Banner', 'woocommerce-countdown-banner' ),
				'id'   => 'wc_countdown_banner_active',
				'type' => 'checkbox',
				'desc' => __( 'Enable site-wide countdown banner', 'woocommerce-countdown-banner' ),
			),
			array(
				'name'     => __( 'Display Text', 'woocommerce-countdown-banner' ),
				'id'       => 'wc_countdown_banner_text',
				'type'     => 'text',
				'desc'     => __( 'This is the message displayed inside the banner. Please enter text only, HTML code is not allowed.', 'woocommerce-countdown-banner' ),
				'desc_tip' => true,
				'css'      => 'min-width:300px;',
			),
			array(
				'name' => __( 'Countdown End', 'woocommerce-countdown-banner' ),
				'id'   => 'wc_countdown_banner_end',
				'type' => 'text',
				'desc' => esc_html( $timezone ),
				'css'  => 'min-width:200px;',
			),
			array(
				'name'     => __( 'Relative Time', 'woocommerce-countdown-banner' ),
				'id'       => 'wc_countdown_banner_relative_time',
				'type'     => 'checkbox',
				'desc'     => __( 'Enabled', 'woocommerce-countdown-banner' ),
				'desc_tip' => __( "If enabled, the same Countdown End time will be used for all customer timezones — E.g. End the countdown at 3:00pm in New York and also at 3:00pm in Paris.", 'woocommerce-countdown-banner' ),
			),
			array(
				'name'     => __( 'Background Color', 'woocommerce-countdown-banner' ),
				'id'       => 'wc_countdown_banner_bg_color',
				'type'     => 'color',
				'default'  => '#a46497',
				'desc'     => __( 'This sets the background color of the banner. Please enter a hexadecimal color code.', 'woocommerce-countdown-banner' ),
				'desc_tip' => true,
				'css'      => 'max-width:80px;',
			),
			array(
				'name'     => __( 'Text Color', 'woocommerce-countdown-banner' ),
				'id'       => 'wc_countdown_banner_text_color',
				'type'     => 'color',
				'default'  => '#ffffff',
				'desc'     => __( 'This sets the text color used inside the banner. Please enter a hexadecimal color code.', 'woocommerce-countdown-banner' ),
				'desc_tip' => true,
				'css'      => 'max-width:80px;',
			),
			array(
				'id'   => 'wc_countdown_banner',
				'type' => 'sectionend',
			),
		);

		/**
		 * Filter the WooCommerce Countdown Banner custom settings
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		$countdown_settings = (array) apply_filters( 'woocommerce_countdown_banner_settings', $countdown_settings );

		return array_merge( $settings, $countdown_settings );
	}

	/**
	 * Enqueue scripts and styles in the admin
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		// Scripts
		wp_enqueue_script( 'jquery-datetimepicker', WC_COUNTDOWN_BANNER_URL . 'ui/js/jquery.datetimepicker.min.js', array( 'jquery' ), '2.4.3' );
		wp_enqueue_script( 'wc-countdown-banner-admin', WC_COUNTDOWN_BANNER_URL . 'ui/js/admin.min.js', array( 'jquery' ), self::VERSION );

		// Styles
		wp_enqueue_style( 'jquery-datetimepicker', WC_COUNTDOWN_BANNER_URL . 'ui/css/jquery.datetimepicker.min.css', array(), '2.4.3' );
		wp_enqueue_style( 'jquery-datetimepicker-woocommerce', WC_COUNTDOWN_BANNER_URL . 'ui/css/jquery.datetimepicker-woocommerce.css', array( 'jquery-datetimepicker' ), self::VERSION );

		$time_format = (string) get_option( 'time_format' );
		$date_format = (string) get_option( 'date_format' );
		$format      = sprintf( '%s @ %s', $date_format, $time_format );

		// Localized vars
		wp_localize_script(
			'wc-countdown-banner-admin',
			'wc_countdown_banner_admin',
			array(
				'date_format' => esc_js( $date_format ),
				'time_format' => esc_js( $time_format ),
				'format'      => esc_js( $format ),
			)
		);
	}

	/**
	 * Enqueue scripts and styles on the front-end
	 *
	 * @action wp_enqueue_scripts
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! self::is_active() ) {
			return;
		}

		// Scripts
		wp_enqueue_script( 'underscore' );
		wp_enqueue_script( 'jquery-final-countdown', WC_COUNTDOWN_BANNER_URL . 'ui/js/jquery.countdown.min.js', array( 'jquery' ), '2.0.4' );
		wp_enqueue_script( 'wc-countdown-banner', WC_COUNTDOWN_BANNER_URL . 'ui/js/wc-countdown-banner.js', array( 'jquery', 'underscore', 'jquery-final-countdown' ), self::VERSION );

		// Styles
		wp_enqueue_style( 'wc-countdown-banner', WC_COUNTDOWN_BANNER_URL . 'ui/css/wc-countdown-banner.css', array(), self::VERSION );

		$end = self::use_relative_time() ? self::$countdown_end : gmdate( 'c', strtotime( self::$countdown_end ) );

		// Localized vars
		wp_localize_script(
			'wc-countdown-banner',
			'wc_countdown_banner',
			array(
				'end' => esc_js( $end ),
			)
		);
	}

	/**
	 * Print CSS styles on the front-end
	 *
	 * The reason for doing it this way is to be able to use the PHP
	 * variables to change the color of the banner. Using JS causes
	 * a short delay on page load, so printing this CSS in the page
	 * head is the fastest and most efficient way to set colors.
	 *
	 * @action wp_head
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wp_head() {
		if ( ! self::is_active() ) {
			return;
		}

		$bg_color = (string) get_option( 'wc_countdown_banner_bg_color' );
		$bg_color = ! empty( $bg_color ) ? $bg_color : '#a46497';
		$color    = (string) get_option( 'wc_countdown_banner_text_color' );
		$color    = ! empty( $color ) ? $color : '#ffffff';
		?>
		<style type="text/css">body{margin-top:61px;}.wc-countdown-banner{background:<?php echo esc_html( $bg_color ) ?>;color:<?php echo esc_html( $color ) ?>;}</style>
		<?php
		/**
		 * Fires after the Countdown Banner CSS rendered in page head
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_after_countdown_banner_css' );
	}

	/**
	 * Print the countdown banner on the front-end
	 *
	 * @action wp_footer
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function banner() {
		if ( ! self::is_active() ) {
			return;
		}

		$display_text = (string) get_option( 'wc_countdown_banner_text' );

		/**
		 * Fires before the Countdown Banner HTML markup
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_before_countdown_banner' );
		?>
		<div class="wc-countdown-banner"><span class="wccb-display-text"><?php echo esc_html( $display_text ) ?></span><?php if ( ! empty( self::$countdown_end ) ) : ?><span id="wccb-countdown-container"></span><?php endif; ?><div class="wccb-clearfix"></div></div>
		<?php if ( ! empty( self::$countdown_end ) ) : ?>
			<script type="text/template" id="wccb-template">
			<div class="wccb-time <%= label %>">
				<span class="wccb-count wccb-curr wccb-top"><%= curr %></span>
				<span class="wccb-count wccb-next wccb-top"><%= next %></span>
				<span class="wccb-count wccb-next wccb-bottom"><%= next %></span>
				<span class="wccb-count wccb-curr wccb-bottom"><%= curr %></span>
				<span class="wccb-label"><%= label.length < 6 ? label : label.substr( 0, 3 )  %></span>
			</div>
			</script>
		<?php endif; ?>
		<?php
		/**
		 * Fires after the Countdown Banner HTML markup
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_after_countdown_banner' );
	}

}

/**
 * Instantiate the plugin instance
 *
 * @global WC_Countdown_Banner
 */
$GLOBALS['wc_countdown_banner'] = WC_Countdown_Banner::get_instance();
