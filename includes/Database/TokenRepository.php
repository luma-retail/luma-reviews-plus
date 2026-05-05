<?php

namespace Luma\ReviewsPlus\Database;

use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Stores and validates review tokens.
 *
 * Responsibilities:
 * - Create fresh hashed review tokens for orders.
 * - Look up tokens by hashed raw token input.
 * - Update token lifecycle fields such as sent, used, and status.
 * - Disable stale tokens when newer tokens are created.
 */
class TokenRepository {

    /**
     * Database connection.
     *
     * @var \wpdb
     */
    protected $wpdb;


    /**
     * Table manager.
     *
     * @var TableManager
     */
    protected $table_manager;


    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Creates the token repository.
     *
     * @param \wpdb       $wpdb Database connection.
     * @param TableManager $table_manager Table manager.
     * @param Settings     $settings Settings service.
     */
    public function __construct( \wpdb $wpdb, TableManager $table_manager, Settings $settings ) {
        $this->wpdb          = $wpdb;
        $this->table_manager = $table_manager;
        $this->settings      = $settings;
    }


    /**
     * Creates a new token for an order.
     *
     * @param int $order_id WooCommerce order ID.
     * @param int $customer_id Customer ID.
     * @return array|null
     */
    public function create_token_for_order( $order_id, $customer_id = 0 ) {
        $this->disable_tokens_for_order( $order_id );

        $raw_token = wp_generate_password( 48, false, false );
        $hash      = Helpers::hash_token( $raw_token );
        $expires   = gmdate( 'Y-m-d H:i:s', time() + ( $this->settings->get_token_expiry_days() * DAY_IN_SECONDS ) );
        $table     = $this->table_manager->get_tokens_table_name();

        $inserted = $this->wpdb->insert(
            $table,
            array(
                'order_id'    => absint( $order_id ),
                'customer_id' => absint( $customer_id ),
                'token_hash'  => $hash,
                'status'      => 'pending',
                'expires_at'  => $expires,
                'created_at'  => Helpers::current_time_mysql(),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            Helpers::log( 'Failed to create review token for order ' . absint( $order_id ), 'error' );
            return null;
        }

        $token = array(
            'id'         => (int) $this->wpdb->insert_id,
            'order_id'   => (int) $order_id,
            'token_hash' => $hash,
            'raw_token'  => $raw_token,
            'expires_at' => $expires,
            'status'     => 'pending',
        );

        do_action( 'luma_reviews_plus_token_created', $token['id'], $order_id );

        return $token;
    }


    /**
     * Finds a token row by raw token input.
     *
     * @param string $raw_token Raw token.
     * @return object|null
     */
    public function find_by_raw_token( $raw_token ) {
        if ( '' === (string) $raw_token ) {
            return null;
        }

        $table = $this->table_manager->get_tokens_table_name();
        $hash  = Helpers::hash_token( $raw_token );
        $row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE token_hash = %s LIMIT 1", $hash ) );

        if ( ! $row ) {
            return null;
        }

        if ( ! empty( $row->expires_at ) && strtotime( (string) $row->expires_at ) < time() && in_array( $row->status, array( 'pending', 'partially_reviewed' ), true ) ) {
            $this->mark_status( $row->id, 'expired' );
            $row->status = 'expired';
        }

        return $row;
    }


    /**
     * Returns the latest token for an order.
     *
     * @param int $order_id Order ID.
     * @return object|null
     */
    public function get_latest_for_order( $order_id ) {
        $table = $this->table_manager->get_tokens_table_name();

        return $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT 1", absint( $order_id ) ) );
    }


    /**
     * Marks a token as sent.
     *
     * @param int $token_id Token ID.
     * @return void
     */
    public function mark_sent( $token_id ) {
        $this->wpdb->update(
            $this->table_manager->get_tokens_table_name(),
            array( 'last_sent_at' => Helpers::current_time_mysql() ),
            array( 'id' => absint( $token_id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }


    /**
     * Marks a token as used.
     *
     * @param int $token_id Token ID.
     * @return void
     */
    public function touch_used( $token_id ) {
        $this->wpdb->update(
            $this->table_manager->get_tokens_table_name(),
            array( 'used_at' => Helpers::current_time_mysql() ),
            array( 'id' => absint( $token_id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }


    /**
     * Updates token status.
     *
     * @param int    $token_id Token ID.
     * @param string $status New status.
     * @return void
     */
    public function mark_status( $token_id, $status ) {
        $this->wpdb->update(
            $this->table_manager->get_tokens_table_name(),
            array( 'status' => sanitize_key( $status ) ),
            array( 'id' => absint( $token_id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }


    /**
     * Disables open tokens for an order.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function disable_tokens_for_order( $order_id ) {
        $table = $this->table_manager->get_tokens_table_name();

        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$table} SET status = 'disabled' WHERE order_id = %d AND status IN ( 'pending', 'partially_reviewed' )",
                absint( $order_id )
            )
        );
    }
}
