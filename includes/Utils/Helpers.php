<?php

namespace Luma\ReviewsPlus\Utils;

/**
 * Provides small shared helper methods.
 *
 * Responsibilities:
 * - Centralize logging helpers.
 * - Provide token hashing and timestamp helpers.
 * - Normalize common WooCommerce customer display values.
 */
class Helpers {

    /**
     * Plugin log source.
     *
     * @var string
     */
    const LOG_SOURCE = 'luma-reviews-plus';


    /**
     * Logs a message through the WooCommerce logger when available.
     *
     * @param string $message Log message.
     * @param string $level Log level.
     * @return void
     */
    public static function log( $message, $level = 'debug' ) {
        if ( ! function_exists( 'wc_get_logger' ) ) {
            return;
        }

        $logger = wc_get_logger();
        $logger->log( $level, $message, array( 'source' => self::LOG_SOURCE ) );
    }


    /**
     * Returns the current WordPress local datetime.
     *
     * @return string
     */
    public static function current_time_mysql() {
        return current_time( 'mysql' );
    }


    /**
     * Returns a stable token hash.
     *
     * @param string $token Raw token.
     * @return string
     */
    public static function hash_token( $token ) {
        return hash_hmac( 'sha256', (string) $token, wp_salt( 'auth' ) );
    }


    /**
     * Returns the order customer's display name.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return string
     */
    public static function get_order_customer_name( \WC_Order $order ) {
        $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

        if ( '' !== $name ) {
            return $name;
        }

        return (string) $order->get_formatted_billing_full_name();
    }


    /**
     * Returns the order customer's first name or a safe fallback.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return string
     */
    public static function get_order_first_name( \WC_Order $order ) {
        $first_name = trim( (string) $order->get_billing_first_name() );

        if ( '' !== $first_name ) {
            return $first_name;
        }

        return __( 'Customer', 'luma-reviews-plus' );
    }


    /**
     * Returns the order customer email.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return string
     */
    public static function get_order_customer_email( \WC_Order $order ) {
        return (string) $order->get_billing_email();
    }


    /**
     * Returns a displayable billing location.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return string
     */
    public static function get_order_location( \WC_Order $order ) {
        return trim( (string) $order->get_billing_city() );
    }
}
