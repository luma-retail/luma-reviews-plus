<?php
/**
 * Plugin Name: Luma Reviews Plus
 * Description: WooCommerce review requests with native product reviews and verified shop-experience reviews.
 * Version: 0.4.6
 *  * Plugin URI: https://github.com/luma-retail/luma-reviews-plus
 * Author: Terje Johansen
 * Requires Plugins: woocommerce
 * Text Domain: luma-reviews-plus
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LUMA_REVIEWS_PLUS_VERSION', '0.4.6' );
define( 'LUMA_REVIEWS_PLUS_FILE', __FILE__ );
define( 'LUMA_REVIEWS_PLUS_PATH', \plugin_dir_path( __FILE__ ) );
define( 'LUMA_REVIEWS_PLUS_URL', \plugin_dir_url( __FILE__ ) );
define( 'LUMA_REVIEWS_PLUS_BASENAME', \plugin_basename( __FILE__ ) );

if ( ! function_exists( 'luma_reviews_plus_get_order_review_flag_data' ) ) {
    /**
     * Returns review-flag data for the given order.
     *
     * @param \WC_Order|int $order WooCommerce order object or ID.
     * @return array
     */
    function luma_reviews_plus_get_order_review_flag_data( $order ) {
        $resolved_order = $order instanceof \WC_Order ? $order : ( function_exists( 'wc_get_order' ) ? wc_get_order( $order ) : false );
        $data           = array(
            'has_flag'            => false,
            'message'             => '',
            'current_order_id'    => $resolved_order instanceof \WC_Order ? $resolved_order->get_id() : 0,
            'previous_order_id'   => 0,
            'previous_order_number' => '',
            'has_product_reviews' => false,
            'has_shop_review'     => false,
            'review_types'        => array(),
            'matched_customer_by' => '',
        );

        return apply_filters( 'luma_reviews_plus_order_review_flag_data', $data, $resolved_order );
    }
}

if ( ! function_exists( 'luma_reviews_plus_get_order_review_flag_message' ) ) {
    /**
     * Returns the order review-flag message for the given order.
     *
     * @param \WC_Order|int $order WooCommerce order object or ID.
     * @return string
     */
    function luma_reviews_plus_get_order_review_flag_message( $order ) {
        $data = luma_reviews_plus_get_order_review_flag_data( $order );

        return ! empty( $data['has_flag'] ) ? (string) $data['message'] : '';
    }
}

spl_autoload_register(
    static function( $class ) {
        $prefix = 'Luma\\ReviewsPlus\\';

        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative_class = substr( $class, strlen( $prefix ) );
        $file           = LUMA_REVIEWS_PLUS_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
);

\register_activation_hook( __FILE__, array( '\\Luma\\ReviewsPlus\\Activation\\Activator', 'activate' ) );

\add_action(
    'plugins_loaded',
    static function() {
        $plugin = new \Luma\ReviewsPlus\Plugin();
        $plugin->init();
    },
    20
);
