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

		// Enqueue scripts and styles on the front-end
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add countdown to store-wide notice
		add_filter( 'woocommerce_demo_store', array( $this, 'countdown_notice' ) );
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
	 *
	 *
	 * More detailed info
	 *
	 * @action wp_enqueue_scripts
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'underscore' );
		wp_enqueue_script( 'jquery-final-countdown', WC_STORE_COUNTDOWN_URL . 'ui/jquery.countdown.min.js', array( 'jquery' ), '2.0.4' );
		wp_enqueue_script( 'wc-store-countdown', WC_STORE_COUNTDOWN_URL . 'ui/wc-store-countdown.js', array( 'jquery', 'underscore', 'jquery-final-countdown' ), self::VERSION );
		wp_enqueue_style( 'wc-store-countdown', WC_STORE_COUNTDOWN_URL . 'ui/wc-store-countdown.css', array(), self::VERSION );
	}

	/**
	 *
	 *
	 * More detailed info
	 *
	 * @action woocommerce_demo_store
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function countdown_notice( $notice ) {
		$countdown = sprintf(
			'<p class="wc-store-countdown-notice">%s<span id="wc-store-countdown"></span></p>',
			esc_html( '50% OFF Black Friday Sale Ends In' )
		);

		$template = '<script type="text/template" id="wc-store-countdown-template">
			<div class="time <%= label %>">
				<span class="count curr top"><%= curr %></span>
				<span class="count next top"><%= next %></span>
				<span class="count next bottom"><%= next %></span>
				<span class="count curr bottom"><%= curr %></span>
				<span class="label"><%= label.length < 6 ? label : label.substr(0, 3)  %></span>
			</div>
			</script>';

		return $countdown . $template . $notice;
	}

}

/**
 * Instantiate the plugin instance
 *
 * @global WC_Store_Countdown
 */
$GLOBALS['wc_store_countdown'] = WC_Store_Countdown::get_instance();
