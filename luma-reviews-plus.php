<?php
/**
 * Plugin Name: Luma Reviews Plus
 * Description: WooCommerce review requests with native product reviews and verified shop-experience reviews.
 * Version: 0.3.0
 *  * Plugin URI: https://github.com/luma-retail/luma-reviews-plus
 * Author: Terje Johansen
 * Requires Plugins: woocommerce
 * Text Domain: luma-reviews-plus
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LUMA_REVIEWS_PLUS_VERSION', '0.3.0' );
define( 'LUMA_REVIEWS_PLUS_FILE', __FILE__ );
define( 'LUMA_REVIEWS_PLUS_PATH', \plugin_dir_path( __FILE__ ) );
define( 'LUMA_REVIEWS_PLUS_URL', \plugin_dir_url( __FILE__ ) );
define( 'LUMA_REVIEWS_PLUS_BASENAME', \plugin_basename( __FILE__ ) );

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
