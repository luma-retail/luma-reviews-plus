<?php

namespace Luma\ReviewsPlus\Reviews;

use Luma\ReviewsPlus\Database\ProductReviewLogRepository;
use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Handles creation of WooCommerce product reviews from tokenized submissions.
 *
 * Responsibilities:
 * - Validate product review rows submitted from the review page.
 * - Create native WordPress comments for WooCommerce product reviews.
 * - Store WooCommerce-compatible review metadata and duplicate logs.
 * - Return created review IDs and validation errors.
 */
class ProductReviewHandler {

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Product review log repository.
     *
     * @var ProductReviewLogRepository
     */
    protected $product_review_log_repository;


    /**
     * Creates the handler.
     *
     * @param Settings                   $settings Settings service.
     * @param ProductReviewLogRepository $product_review_log_repository Review log repository.
     */
    public function __construct( Settings $settings, ProductReviewLogRepository $product_review_log_repository ) {
        $this->settings                      = $settings;
        $this->product_review_log_repository = $product_review_log_repository;
    }


    /**
     * Creates product reviews from submitted values.
     *
     * @param \WC_Order $order Order object.
     * @param object     $token Token row.
     * @param array      $reviewable_items Reviewable order items.
     * @param array      $submitted_reviews Raw submitted review rows.
     * @return array
     */
    public function handle_submission( \WC_Order $order, $token, array $reviewable_items, array $submitted_reviews ) {
        $created = array();
        $errors  = array();

        foreach ( $reviewable_items as $order_item_id => $item_data ) {
            $submitted = isset( $submitted_reviews[ $order_item_id ] ) && is_array( $submitted_reviews[ $order_item_id ] ) ? $submitted_reviews[ $order_item_id ] : array();
            $rating    = absint( $submitted['rating'] ?? 0 );
            $comment   = sanitize_textarea_field( (string) ( $submitted['comment'] ?? '' ) );

            if ( $rating < 1 || $rating > 5 ) {
                continue;
            }

            if ( $this->settings->is_product_review_comment_required() && '' === $comment ) {
                $errors[] = sprintf( __( 'Please add a comment for %s.', 'luma-reviews-plus' ), $item_data['product_name'] );
                continue;
            }

            if ( $this->product_review_log_repository->has_review_for_order_item( $order_item_id ) ) {
                continue;
            }

            $comment_id = wp_insert_comment(
                array(
                    'comment_post_ID'      => absint( $item_data['product_id'] ),
                    'comment_author'       => Helpers::get_order_customer_name( $order ),
                    'comment_author_email' => Helpers::get_order_customer_email( $order ),
                    'comment_content'      => $comment,
                    'comment_type'         => 'review',
                    'comment_approved'     => $this->get_comment_approval_value(),
                    'user_id'              => absint( $order->get_customer_id() ),
                )
            );

            if ( ! $comment_id || is_wp_error( $comment_id ) ) {
                $errors[] = sprintf( __( 'Unable to save review for %s.', 'luma-reviews-plus' ), $item_data['product_name'] );
                continue;
            }

            update_comment_meta( $comment_id, 'rating', $rating );
            update_comment_meta( $comment_id, 'verified', 1 );
            update_comment_meta( $comment_id, '_luma_reviews_plus_order_id', $order->get_id() );
            update_comment_meta( $comment_id, '_luma_reviews_plus_order_item_id', $order_item_id );
            update_comment_meta( $comment_id, '_luma_reviews_plus_token_id', $token->id );

            $this->product_review_log_repository->log_review(
                array(
                    'order_id'          => $order->get_id(),
                    'order_item_id'     => $order_item_id,
                    'product_id'        => $item_data['product_id'],
                    'variation_id'      => $item_data['variation_id'],
                    'review_comment_id' => $comment_id,
                )
            );

            if ( function_exists( 'wc_delete_product_transients' ) ) {
                wc_delete_product_transients( $item_data['product_id'] );
            }

            clean_post_cache( $item_data['product_id'] );
            $created[] = (int) $comment_id;

            do_action( 'luma_reviews_plus_product_review_created', $comment_id, $order->get_id(), $order_item_id, $token->id );
        }

        return array(
            'created' => $created,
            'errors'  => $errors,
        );
    }


    /**
     * Returns the comment approval value.
     *
     * @return int
     */
    protected function get_comment_approval_value() {
        if ( $this->settings->should_auto_approve_product_reviews() ) {
            return 1;
        }

        return '1' === get_option( 'comment_moderation', '0' ) ? 0 : 1;
    }
}
