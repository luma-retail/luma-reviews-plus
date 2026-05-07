<?php

namespace Luma\ReviewsPlus\Reviews;

use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Handles saving shop-experience reviews.
 *
 * Responsibilities:
 * - Validate and sanitize submitted shop review fields.
 * - Persist one verified shop review per order.
 * - Enforce allowed tag values and public-consent defaults.
 * - Return save results for the review page controller.
 */
class ShopReviewHandler {

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Shop review repository.
     *
     * @var ShopReviewRepository
     */
    protected $shop_review_repository;


    /**
     * Creates the handler.
     *
     * @param Settings             $settings Settings service.
     * @param ShopReviewRepository $shop_review_repository Repository.
     */
    public function __construct( Settings $settings, ShopReviewRepository $shop_review_repository ) {
        $this->settings               = $settings;
        $this->shop_review_repository = $shop_review_repository;
    }


    /**
     * Saves a submitted shop review when a rating is present.
     *
     * @param \WC_Order $order Order object.
     * @param object     $token Token row.
     * @param array      $submitted Shop review form data.
     * @return array
     */
    public function handle_submission( \WC_Order $order, $token, array $submitted ) {
        $rating = absint( $submitted['rating'] ?? 0 );

        if ( $rating < 1 || $rating > 5 ) {
            return array(
                'errors'    => array(),
                'saved'     => false,
                'review_id' => 0,
            );
        }

        $allowed_tags  = $this->settings->get_shop_review_tags();
        $selected_tags = array_values( array_intersect( $allowed_tags, array_map( 'sanitize_text_field', (array) ( $submitted['tags'] ?? array() ) ) ) );
        $display_name  = sanitize_text_field( (string) ( $submitted['display_name'] ?? '' ) );
        $location      = sanitize_text_field( (string) ( $submitted['display_location'] ?? '' ) );
        $errors        = array();

        if ( '' === $display_name ) {
            $display_name = Helpers::get_order_customer_name( $order );
        }

        $first_name = trim( (string) $order->get_billing_first_name() );

        if ( '' !== $first_name && false === stripos( $display_name, $first_name ) ) {
            $errors[] = __( 'Your display name must include your first name so we can match the review to the order.', 'luma-reviews-plus' );
        }

        if ( ! empty( $errors ) ) {
            return array(
                'errors'    => $errors,
                'saved'     => false,
                'review_id' => 0,
            );
        }

        if ( '' === $location ) {
            $location = Helpers::get_order_location( $order );
        }

        $existing_review = $this->shop_review_repository->get_by_order_id( $order->get_id() );

        $review_id = $this->shop_review_repository->save_review(
            array(
                'order_id'                    => $order->get_id(),
                'customer_id'                 => $order->get_customer_id(),
                'rating'                      => $rating,
                'comment'                     => sanitize_textarea_field( (string) ( $submitted['comment'] ?? '' ) ),
                'tags'                        => $selected_tags,
                'public_consent'              => ! empty( $submitted['public_consent'] ),
                'display_name'                => $display_name,
                'display_location'            => $location,
                'approved_for_public_display' => 0,
            )
        );

        if ( $review_id > 0 && ! $existing_review ) {
            do_action( 'luma_reviews_plus_shop_review_created', $review_id, $order->get_id(), $token->id );
        }

        return array(
            'errors'    => array(),
            'saved'     => $review_id > 0,
            'review_id' => $review_id,
        );
    }
}
