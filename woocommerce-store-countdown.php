<?php
/**
 * Plugin Name: WooCommerce Store Countdown
 * Plugin URI: http://www.woothemes.com/products/woocommerce-store-countdown/
 * Description:
 * Version: 1.0.0
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Developer: Frankie Jarrett
 * Developer URI: http://frankiejarrett.com/
 * Depends: WooCommerce
 * Text Domain: woocommerce-store-countdown
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
 * Display a password strength meter when customers register during checkout.
 *
 * @version 1.0.0
 * @package WooCommerce
 * @author  Frankie Jarrett
 */
class WC_Store_Countdown {

	/**
	 * Hold class instance
	 *
	 * @access public
	 * @static
	 *
	 * @var WC_Store_Countdown
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

		define( 'WC_STORE_COUNTDOWN_URL', plugins_url( '/', __FILE__ ) );

		// Sanitize the countdown end time for programmatic use
		self::$countdown_end = str_replace( '@', '', (string) get_option( 'wc_store_countdown_end' ) );
		self::$countdown_end = date( 'Y-m-d H:i:s', strtotime( self::$countdown_end ) );

		// Use GMT when not using relative time
		if ( ! self::use_relative_time() ) {
			self::$countdown_end = get_gmt_from_date( self::$countdown_end );
		}

		// Automatically deactivate the countdown option if time has expired
		add_action( 'init', array( $this, 'maybe_deactivate_countdown' ) );

		// Add custom settings to General tab
		add_filter( 'woocommerce_get_settings_general', array( $this, 'settings' ) );

		// Enqueue timepicker addon
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Enqueue scripts and styles on the front-end
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Apply custom colors to store-wide notice
		add_action( 'wp_head', array( $this, 'wp_head' ) );

		// Add the countdown notice to the front-end
		add_action( 'wp_footer', array( $this, 'notice' ) );
	}

	/**
	 * Return an active instance of this class
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return WC_Store_Countdown
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
	 * Check if we should use relative time for the Store Countdown
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
		return ( 'yes' === (string) get_option( 'wc_store_countdown_relative_time' ) );
	}

	/**
	 * Check if the Store Countdown has expired
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return bool
	 */
	public static function has_expired() {
		if ( self::use_relative_time() ) {
			$expired = ( strtotime( self::$countdown_end ) < (int) date_i18n( 'U' ) );
		} else {
			$expired = ( strtotime( self::$countdown_end ) < time() );
		}

		return (bool) $expired;
	}

	/**
	 * Check if the Store Countdown is active
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
			( 'yes' === get_option( 'wc_store_countdown_active' ) )
			&&
			! self::has_expired()
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
			update_option( 'wc_store_countdown_active', 'no' );
		}
	}

	/**
	 *
	 *
	 * More detailed info
	 *
	 * @filter woocommerce_get_settings_general
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
				'name' => __( 'Store Countdown Options', 'woocommerce-store-countdown' ),
				'id'   => 'wc_store_countdown',
				'type' => 'title',
				'desc' => __( 'The following options are used to configure a site-wide store countdown notice.', 'woocommerce-store-countdown' ),
			),
			array(
				'name' => __( 'Store Countdown Notice', 'woocommerce-store-countdown' ),
				'id'   => 'wc_store_countdown_active',
				'type' => 'checkbox',
				'desc' => __( 'Enable site-wide store countdown notice', 'woocommerce-store-countdown' ),
			),
			array(
				'name' => __( 'Display Text', 'woocommerce-store-countdown' ),
				'id'   => 'wc_store_countdown_text',
				'type' => 'text',
				'css'  => 'min-width:300px;',
			),
			array(
				'name' => __( 'Countdown End', 'woocommerce-store-countdown' ),
				'id'   => 'wc_store_countdown_end',
				'type' => 'text',
				'desc' => esc_html( $timezone ),
				'css'  => 'min-width:200px;',
			),
			array(
				'name'     => __( 'Relative Time', 'woocommerce-store-countdown' ),
				'id'       => 'wc_store_countdown_relative_time',
				'type'     => 'checkbox',
				'desc'     => __( 'Enabled', 'woocommerce-store-countdown' ),
				'desc_tip' => __( "If enabled, the same Countdown End time will be used for all customer timezones — E.g. End the countdown at 3:00pm in New York and also at 3:00pm in Paris.", 'woocommerce-store-countdown' ),
			),
			array(
				'name'    => __( 'Background Color', 'woocommerce-store-countdown' ),
				'id'      => 'wc_store_countdown_bg_color',
				'type'    => 'color',
				'default' => '#a46497',
				'css'     => 'max-width:80px;',
			),
			array(
				'name'    => __( 'Text Color', 'woocommerce-store-countdown' ),
				'id'      => 'wc_store_countdown_text_color',
				'type'    => 'color',
				'default' => '#ffffff',
				'css'     => 'max-width:80px;',
			),
			array(
				'id'   => 'wc_store_countdown',
				'type' => 'sectionend',
			),
		);

		/**
		 * Filter the WooCommerce Store Countdown custom settings
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		$countdown_settings = (array) apply_filters( 'woocommerce_store_countdown_settings', $countdown_settings );

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
		wp_enqueue_script( 'jquery-datetimepicker', WC_STORE_COUNTDOWN_URL . 'ui/js/jquery.datetimepicker.min.js', array( 'jquery' ), '2.4.3' );
		wp_enqueue_script( 'wc-store-countdown-admin', WC_STORE_COUNTDOWN_URL . 'ui/js/admin.min.js', array( 'jquery' ), self::VERSION );

		// Styles
		wp_enqueue_style( 'jquery-datetimepicker', WC_STORE_COUNTDOWN_URL . 'ui/css/jquery.datetimepicker.min.css', array(), '2.4.3' );
		wp_enqueue_style( 'jquery-datetimepicker-woocommerce', WC_STORE_COUNTDOWN_URL . 'ui/css/jquery.datetimepicker-woocommerce.css', array(), self::VERSION );

		$time_format = (string) get_option( 'time_format' );
		$date_format = (string) get_option( 'date_format' );
		$format      = sprintf( '%s @ %s', $date_format, $time_format );

		// Localized vars
		wp_localize_script(
			'wc-store-countdown-admin',
			'wc_store_countdown_admin',
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
		wp_enqueue_script( 'jquery-final-countdown', WC_STORE_COUNTDOWN_URL . 'ui/js/jquery.countdown.min.js', array( 'jquery' ), '2.0.4' );
		wp_enqueue_script( 'wc-store-countdown', WC_STORE_COUNTDOWN_URL . 'ui/js/wc-store-countdown.min.js', array( 'jquery', 'underscore', 'jquery-final-countdown' ), self::VERSION );

		// Styles
		wp_enqueue_style( 'wc-store-countdown', WC_STORE_COUNTDOWN_URL . 'ui/css/wc-store-countdown.min.css', array(), self::VERSION );

		$end = self::use_relative_time() ? self::$countdown_end : gmdate( 'c', strtotime( self::$countdown_end ) );

		// Localized vars
		wp_localize_script(
			'wc-store-countdown',
			'wc_store_countdown',
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

		$bg_color = (string) get_option( 'wc_store_countdown_bg_color' );
		$bg_color = ! empty( $bg_color ) ? $bg_color : '#a46497';
		$color    = (string) get_option( 'wc_store_countdown_text_color' );
		$color    = ! empty( $color ) ? $color : '#ffffff';
		?>
		<style type="text/css">body{margin-top:61px;}@media screen and (max-width:782px){body{margin-top:109px;}}.wc-store-countdown-notice{background:<?php echo esc_html( $bg_color ) ?>;color:<?php echo esc_html( $color ) ?>;}</style>
		<?php
	}

	/**
	 * Print the countdown notice on the front-end
	 *
	 * @action woocommerce_demo_store
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function notice( $notice ) {
		if ( ! self::is_active() ) {
			return;
		}

		$display_text = (string) get_option( 'wc_store_countdown_text' );
		?>
		<div class="wc-store-countdown-notice"><?php echo esc_html( $display_text ) ?><span id="wc-store-countdown"></span></div>
		<script type="text/template" id="wc-store-countdown-template">
		<div class="time <%= label %>">
			<span class="count curr top"><%= curr %></span>
			<span class="count next top"><%= next %></span>
			<span class="count next bottom"><%= next %></span>
			<span class="count curr bottom"><%= curr %></span>
			<span class="label"><%= label.length < 6 ? label : label.substr(0, 3)  %></span>
		</div>
		</script>
		<?php
	}

}

/**
 * Instantiate the plugin instance
 *
 * @global WC_Store_Countdown
 */
$GLOBALS['wc_store_countdown'] = WC_Store_Countdown::get_instance();
