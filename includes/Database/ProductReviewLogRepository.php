<?php

namespace Luma\ReviewsPlus\Database;

use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Tracks created product reviews per order item.
 *
 * Responsibilities:
 * - Prevent duplicate product reviews for the same order item.
 * - Return reviewed order-item IDs for review page filtering.
 * - Persist links between order items and created comment IDs.
 */
class ProductReviewLogRepository {

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
     * Creates the repository.
     *
     * @param \wpdb       $wpdb Database connection.
     * @param TableManager $table_manager Table manager.
     */
    public function __construct( \wpdb $wpdb, TableManager $table_manager ) {
        $this->wpdb          = $wpdb;
        $this->table_manager = $table_manager;
    }


    /**
     * Returns whether a review already exists for an order item.
     *
     * @param int $order_item_id Order item ID.
     * @return bool
     */
    public function has_review_for_order_item( $order_item_id ) {
        $table = $this->table_manager->get_product_reviews_table_name();
        $found = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$table} WHERE order_item_id = %d LIMIT 1", absint( $order_item_id ) ) );

        return ! empty( $found );
    }


    /**
     * Returns reviewed order item IDs for an order.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function get_reviewed_order_item_ids( $order_id ) {
        $table = $this->table_manager->get_product_reviews_table_name();

        return array_map( 'absint', $this->wpdb->get_col( $this->wpdb->prepare( "SELECT order_item_id FROM {$table} WHERE order_id = %d", absint( $order_id ) ) ) );
    }


    /**
     * Persists a created product review link.
     *
     * @param array $data Review log data.
     * @return bool
     */
    public function log_review( $data ) {
        $inserted = $this->wpdb->insert(
            $this->table_manager->get_product_reviews_table_name(),
            array(
                'order_id'          => absint( $data['order_id'] ),
                'order_item_id'     => absint( $data['order_item_id'] ),
                'product_id'        => absint( $data['product_id'] ),
                'variation_id'      => absint( $data['variation_id'] ),
                'review_comment_id' => absint( $data['review_comment_id'] ),
                'created_at'        => Helpers::current_time_mysql(),
            ),
            array( '%d', '%d', '%d', '%d', '%d', '%s' )
        );

        return false !== $inserted;
    }
}
