<?php

namespace Luma\ReviewsPlus\Database;

use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Stores verified shop-experience reviews.
 *
 * Responsibilities:
 * - Insert or update one shop review per order.
 * - Return admin lists and per-order shop reviews.
 * - Manage public approval and deletion.
 * - Provide summary and quote data for frontend trust widgets.
 */
class ShopReviewRepository {

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
     * Returns a shop review by order ID.
     *
     * @param int $order_id Order ID.
     * @return object|null
     */
    public function get_by_order_id( $order_id ) {
        $table = $this->table_manager->get_shop_reviews_table_name();
        $row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d LIMIT 1", absint( $order_id ) ) );

        if ( $row ) {
            $row->tags = $this->decode_tags( $row->tags_json );
        }

        return $row;
    }


    /**
     * Returns whether an order has a shop review.
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function has_review_for_order( $order_id ) {
        $table = $this->table_manager->get_shop_reviews_table_name();
        $found = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$table} WHERE order_id = %d LIMIT 1", absint( $order_id ) ) );

        return ! empty( $found );
    }


    /**
     * Returns a shop review by primary key.
     *
     * @param int $review_id Review ID.
     * @return object|null
     */
    public function get_by_id( $review_id ) {
        $table = $this->table_manager->get_shop_reviews_table_name();
        $row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $review_id ) ) );

        if ( $row ) {
            $row->tags = $this->decode_tags( $row->tags_json );
        }

        return $row;
    }


    /**
     * Inserts or updates a shop review.
     *
     * @param array $data Shop review data.
     * @return int
     */
    public function save_review( $data ) {
        $table    = $this->table_manager->get_shop_reviews_table_name();
        $existing = $this->get_by_order_id( $data['order_id'] );
        $payload  = array(
            'order_id'                    => absint( $data['order_id'] ),
            'customer_id'                 => absint( $data['customer_id'] ),
            'rating'                      => absint( $data['rating'] ),
            'comment'                     => (string) $data['comment'],
            'tags_json'                   => wp_json_encode( array_values( (array) $data['tags'] ) ),
            'public_consent'              => empty( $data['public_consent'] ) ? 0 : 1,
            'display_name'                => (string) $data['display_name'],
            'display_location'            => (string) $data['display_location'],
            'approved_for_public_display' => empty( $data['approved_for_public_display'] ) ? 0 : 1,
        );

        if ( $existing ) {
            $payload['updated_at'] = Helpers::current_time_mysql();
            $this->wpdb->update( $table, $payload, array( 'id' => absint( $existing->id ) ) );
            return (int) $existing->id;
        }

        $payload['created_at'] = Helpers::current_time_mysql();
        $this->wpdb->insert( $table, $payload );

        return (int) $this->wpdb->insert_id;
    }


    /**
     * Returns admin review rows.
     *
     * @return array
     */
    public function list_reviews() {
        $table = $this->table_manager->get_shop_reviews_table_name();
        $rows  = $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );

        foreach ( $rows as $row ) {
            $row->tags = $this->decode_tags( $row->tags_json );
        }

        return $rows;
    }


    /**
     * Updates public approval state.
     *
     * @param int $review_id Review ID.
     * @param int $approved Approval state.
     * @return void
     */
    public function set_public_approval( $review_id, $approved ) {
        $this->wpdb->update(
            $this->table_manager->get_shop_reviews_table_name(),
            array( 'approved_for_public_display' => empty( $approved ) ? 0 : 1 ),
            array( 'id' => absint( $review_id ) ),
            array( '%d' ),
            array( '%d' )
        );
    }


    /**
     * Deletes a review by ID.
     *
     * @param int $review_id Review ID.
     * @return void
     */
    public function delete_review( $review_id ) {
        $this->wpdb->delete( $this->table_manager->get_shop_reviews_table_name(), array( 'id' => absint( $review_id ) ), array( '%d' ) );
    }


    /**
     * Returns aggregated public summary data.
     *
     * @param int $quote_count Number of quotes.
     * @param int $minimum_rating Minimum quote rating.
     * @return array
     */
    public function get_summary_data( $quote_count = 3, $minimum_rating = 4 ) {
        $table   = $this->table_manager->get_shop_reviews_table_name();
        $summary = $this->wpdb->get_row( "SELECT AVG(rating) AS average_rating, COUNT(*) AS review_count FROM {$table}" );
        $quotes  = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE approved_for_public_display = 1 AND public_consent = 1 AND comment <> '' AND rating >= %d ORDER BY created_at DESC LIMIT %d",
                absint( $minimum_rating ),
                absint( $quote_count )
            )
        );

        foreach ( $quotes as $quote ) {
            $quote->tags = $this->decode_tags( $quote->tags_json );
        }

        return array(
            'average_rating' => $summary && null !== $summary->average_rating ? round( (float) $summary->average_rating, 1 ) : 0.0,
            'review_count'   => $summary ? absint( $summary->review_count ) : 0,
            'quotes'         => $quotes,
        );
    }


    /**
     * Decodes stored tags JSON.
     *
     * @param string|null $tags_json Stored JSON.
     * @return array
     */
    protected function decode_tags( $tags_json ) {
        $tags = json_decode( (string) $tags_json, true );

        return is_array( $tags ) ? array_values( array_filter( $tags ) ) : array();
    }
}
