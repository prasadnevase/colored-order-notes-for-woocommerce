<?php
/**
 * WooCommerce Colored Order Notes
 *
 * @package     WooCommerceColoredOrderNotes
 * @author      Prasad Nevase
 * @copyright   2016 Your Name or Company Name
 * @license     GPLv3
 *
 * Plugin Name:     WooCommerce Colored Order Notes
 * Plugin URI:      https://wordpress.org/plugins/woocommerce-colored-order-notes/
 * Description:     Assign custom colors to WooCommerce order notes from backend.
 * Version:         1.0.3
 * Author:          Prasad Nevase
 * Author URI:      https://about.me/prasad.nevase
 * Text Domain:     colored-order-notes-for-woocommerce
 * License:         GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Settings_Order_Note_Colors' ) ) {

	/**
	 * Class WC_Settings_Order_Note_Colors
	 */
	class WC_Settings_Order_Note_Colors {

		/**
		 *  Class Constructor
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'init' ) );

		}

		/**
		 * Bootstraps the class and hooks required actions & filters.
		 */
		public static function init() {

			if ( version_compare( WC()->version, '2.5.0', '<' ) ) {

				return add_action( 'admin_notices', __CLASS__ . '::wc_onc_admin_notices' );

			}

			add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::wc_onc_add_settings_tab', 50 );
			add_filter( 'woocommerce_order_note_class', __CLASS__ . '::wc_onc_process_note_classes', 10, 2 );
			add_action( 'woocommerce_settings_tabs_order_note_color', __CLASS__ . '::wc_onc_settings_tab' );
			add_action( 'woocommerce_update_options_order_note_color', __CLASS__ . '::wc_onc_update_settings' );
			add_action( 'admin_head', __CLASS__ . '::wc_onc_css' );

		}


		/**
		 * Shows admin notice if WooCommerce version is below 2.5
		 */
		public static function wc_onc_admin_notices() {
			echo '<div class="error"><p>' . esc_html__( '<strong>WooCommerce Order Note Colors</strong> plugin requires WooCommerce version 2.5.0 or higher. Please take necessary backup, update WooCommerce then deactivate & activate this plugin.', 'colored-order-notes-for-woocommerce' ) . '</p></div>';
		}

		/**
		 * Add a new settings tab to the WooCommerce settings tabs array.
		 *
		 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
		 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
		 */
		public static function wc_onc_add_settings_tab( $settings_tabs ) {
			$settings_tabs['order_note_color'] = esc_html__( 'Order Note Colors', 'colored-order-notes-for-woocommerce' );
			return $settings_tabs;
		}


		/**
		 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
		 *
		 * @uses woocommerce_admin_fields()
		 * @uses self::get_settings()
		 */
		public static function wc_onc_settings_tab() {
			woocommerce_admin_fields( self::wc_onc_get_settings() );
		}

		/**
		 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
		 *
		 * @uses woocommerce_update_options()
		 * @uses self::get_settings()
		 */
		public static function wc_onc_update_settings() {
			woocommerce_update_options( self::wc_onc_get_settings() );
		}

		/**
		 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
		 *
		 * @return array Array of settings for @see woocommerce_admin_fields() function.
		 */
		public static function wc_onc_get_settings() {

			$wc_onc_settings       = array();
			$wc_onc_order_statuses = wc_get_order_statuses();

			$wc_onc_settings[] = array(
				'name' => esc_html__( 'Order Note Colors', 'colored-order-notes-for-woocommerce' ),
				'type' => 'title',
				'desc' => esc_html__( 'Here you can specify the bacground color for order note based on order status.', 'colored-order-notes-for-woocommerce' ),
				'id'   => 'wc_settings_order_note_colors',
			);

			/* This loop will provide color setting option for all default + custom order status */
			foreach ( $wc_onc_order_statuses as $wc_onc_order_status ) {

				$wc_onc_order_status_id = strtolower( 'onc_' . str_replace( ' ', '_', $wc_onc_order_status ) );

				$wc_onc_settings[] = array(
					'name' => $wc_onc_order_status,
					'type' => 'color',
					'id'   => $wc_onc_order_status_id,
				);

			}

			$wc_onc_settings[] = array(
				'type' => 'sectionend',
				'id'   => 'wc_settings_order_note_colors_end',
			);

			return apply_filters( 'wc_settings_tab_order_note_color_settings', $wc_onc_settings );
		}

		/**
		 * Append css class to $note_classes array based on the order status.
		 *
		 * @param Array  $note_classes Array of note css classes.
		 * @param Object $note Object which holds all parameter for a note.
		 * @return Array $note_classes Array of note css classes.
		 */
		public static function wc_onc_process_note_classes( $note_classes, $note ) {

			if ( ! empty( $note->content ) ) { // For WC >= 3.2.0 version.

				$per_note_status = explode( 'to ', $note->content );

			} elseif ( ! empty( $note->comment_content ) ) { // For WC <= 2.7.0 version.

				$per_note_status = explode( 'to ', $note->comment_content );

			} else {
				return $note_classes;
			}

			$onc_css_classes = self::wc_onc_get_settings();

			foreach ( $onc_css_classes as $onc_css_class ) {

				if ( 'color' === $onc_css_class['type'] && strtolower( rtrim( $per_note_status[ count( $per_note_status ) - 1 ], '.' ) ) === strtolower( $onc_css_class['name'] ) ) {

					$note_classes[] = $onc_css_class['id'];
				}
			}

			return $note_classes;
		}

		/**
		 * Generate the css for each order status and place it in admin head
		 */
		public static function wc_onc_css() {

			global $current_screen;

			$onc_css = '';

			$onc_note_colors = self::wc_onc_get_settings();

			/* Check if the current page is for Order CPT (Either order listing or edit page) */

			if ( 'shop_order' === $current_screen->post_type ) {

				foreach ( $onc_note_colors as $onc_note_color ) {

					if ( 'color' === $onc_note_color['type'] ) {

						$note_color = get_option( $onc_note_color['id'] );

						if ( ! empty( $note_color ) ) {

							$text_color = hexdec( ltrim( $note_color, '#' ) ) > 0xffffff / 2 ? 'black' : 'white';

							$onc_css .= '.note.' . $onc_note_color['id'] . ' .note_content { background: ' . $note_color . ' !important; color: ' . $text_color . '; }';

							$onc_css .= ' .note.' . $onc_note_color['id'] . ' .note_content:after { border-color: ' . $note_color . ' transparent !important; }';
						}
					}
				}

				/* Finaly print the css */
				echo '<style>' . wp_kses_post( $onc_css ) . '</style>';

			}
		}
	}
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

	global $wc_onc;

	$wc_onc = new WC_Settings_Order_Note_Colors();

}
