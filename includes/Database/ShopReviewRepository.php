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

        // Enforce publish constraints at repository level.
        if ( ! $this->is_public_approval_allowed_from_data( $payload ) ) {
            $payload['approved_for_public_display'] = 0;
        }

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
        * @return bool
     */
    public function set_public_approval( $review_id, $approved ) {
        $review_id = absint( $review_id );
        $approved  = empty( $approved ) ? 0 : 1;

        if ( 1 === $approved ) {
            $review = $this->get_by_id( $review_id );

            if ( ! $review || ! $this->is_public_approval_allowed_for_review( $review ) ) {
                return false;
            }
        }

        $result = $this->wpdb->update(
            $this->table_manager->get_shop_reviews_table_name(),
            array( 'approved_for_public_display' => $approved ),
            array( 'id' => $review_id ),
            array( '%d' ),
            array( '%d' )
        );

        return false !== $result;
    }


    /**
     * Updates featured state.
     *
     * @param int $review_id Review ID.
     * @param int $is_featured Featured state.
     * @return void
     */
    public function set_featured( $review_id, $is_featured ) {
        $this->wpdb->update(
            $this->table_manager->get_shop_reviews_table_name(),
            array( 'is_featured' => empty( $is_featured ) ? 0 : 1 ),
            array( 'id' => absint( $review_id ) ),
            array( '%d' ),
            array( '%d' )
        );
    }


    /**
     * Updates editable admin fields for a review.
     *
     * @param int    $review_id Review ID.
     * @param string $display_name Display name.
     * @param string $comment Comment text.
     * @return bool
     */
    public function update_review_content( $review_id, $display_name, $comment ) {
        $review_id = absint( $review_id );
        $review    = $this->get_by_id( $review_id );

        if ( ! $review ) {
            return false;
        }

        $payload = array(
            'display_name' => (string) $display_name,
            'comment'      => (string) $comment,
            'updated_at'   => Helpers::current_time_mysql(),
        );
        $format  = array( '%s', '%s', '%s' );

        if ( ! $this->is_public_approval_allowed_from_data( array( 'public_consent' => ! empty( $review->public_consent ), 'comment' => $payload['comment'] ) ) ) {
            $payload['approved_for_public_display'] = 0;
            $format[]                               = '%d';
        }

        $result = $this->wpdb->update(
            $this->table_manager->get_shop_reviews_table_name(),
            $payload,
            array( 'id' => $review_id ),
            $format,
            array( '%d' )
        );

        return false !== $result;
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
    public function get_summary_data( $quote_count = 3, $minimum_rating = 4, $featured_only = false ) {
        $table   = $this->table_manager->get_shop_reviews_table_name();
        $summary = $this->wpdb->get_row( "SELECT AVG(rating) AS average_rating, COUNT(*) AS review_count FROM {$table}" );
        $quotes  = $this->get_public_quotes(
            array(
                'limit'          => absint( $quote_count ),
                'offset'         => 0,
                'minimum_rating' => absint( $minimum_rating ),
                'featured_only'  => ! empty( $featured_only ),
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
     * Returns paginated public quotes.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_public_quotes( array $args = array() ) {
        $defaults = array(
            'limit'          => 3,
            'offset'         => 0,
            'minimum_rating' => 4,
            'featured_only'  => false,
        );
        $args     = wp_parse_args( $args, $defaults );

        $limit          = max( 1, absint( $args['limit'] ) );
        $offset         = max( 0, absint( $args['offset'] ) );
        $minimum_rating = max( 1, min( 5, absint( $args['minimum_rating'] ) ) );
        $featured_only  = ! empty( $args['featured_only'] );
        $table          = $this->table_manager->get_shop_reviews_table_name();
        $where_sql      = $this->build_public_quotes_where_sql( $featured_only );

        $quotes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $minimum_rating,
                $limit,
                $offset
            )
        );

        foreach ( $quotes as $quote ) {
            $quote->tags = $this->decode_tags( $quote->tags_json );
        }

        return $quotes;
    }


    /**
     * Counts public quotes.
     *
     * @param int  $minimum_rating Minimum quote rating.
     * @param bool $featured_only Whether only featured quotes should be counted.
     * @return int
     */
    public function count_public_quotes( $minimum_rating = 4, $featured_only = false ) {
        $table          = $this->table_manager->get_shop_reviews_table_name();
        $minimum_rating = max( 1, min( 5, absint( $minimum_rating ) ) );
        $where_sql      = $this->build_public_quotes_where_sql( ! empty( $featured_only ) );

        return absint(
            $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} {$where_sql}",
                    $minimum_rating
                )
            )
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


    /**
     * Returns a WHERE clause for public quotes.
     *
     * @param bool $featured_only Whether only featured quotes should be included.
     * @return string
     */
    protected function build_public_quotes_where_sql( $featured_only ) {
        $where_sql = "WHERE approved_for_public_display = 1 AND public_consent = 1 AND comment <> '' AND rating >= %d";

        if ( $featured_only ) {
            $where_sql .= ' AND is_featured = 1';
        }

        return $where_sql;
    }


    /**
     * Returns whether a review row can be approved for public display.
     *
     * @param object $review Review row.
     * @return bool
     */
    protected function is_public_approval_allowed_for_review( $review ) {
        $public_consent = ! empty( $review->public_consent );
        $comment        = trim( (string) ( $review->comment ?? '' ) );

        return $public_consent && '' !== $comment;
    }


    /**
     * Returns whether payload data can be approved for public display.
     *
     * @param array $payload Normalized review payload.
     * @return bool
     */
    protected function is_public_approval_allowed_from_data( array $payload ) {
        $public_consent = ! empty( $payload['public_consent'] );
        $comment        = trim( (string) ( $payload['comment'] ?? '' ) );

        return $public_consent && '' !== $comment;
    }
}
