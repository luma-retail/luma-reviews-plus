<?php

namespace Luma\ReviewsPlus\Email;

use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Sends admin notifications for newly created shop reviews.
 *
 * Responsibilities:
 * - Listen for newly created shop-experience reviews.
 * - Build a concise admin notification email with review details.
 * - Respect the configured notification recipient in plugin settings.
 */
class ShopReviewNotification {

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
     * Creates the notification handler.
     *
     * @param Settings             $settings Settings service.
     * @param ShopReviewRepository $shop_review_repository Repository.
     */
    public function __construct( Settings $settings, ShopReviewRepository $shop_review_repository ) {
        $this->settings               = $settings;
        $this->shop_review_repository = $shop_review_repository;
    }


    /**
     * Registers notification hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'luma_reviews_plus_shop_review_created', array( $this, 'send_notification' ), 10, 3 );
    }


    /**
     * Sends the shop review notification email.
     *
     * @param int $review_id Shop review ID.
     * @param int $order_id Order ID.
     * @param int $token_id Token ID.
     * @return void
     */
    public function send_notification( $review_id, $order_id, $token_id ) {
        if ( ! $this->settings->are_shop_review_notifications_enabled() ) {
            return;
        }

        $recipient = $this->settings->get_shop_review_notification_email();

        if ( '' === $recipient ) {
            return;
        }

        $review = $this->shop_review_repository->get_by_id( $review_id );

        if ( ! $review ) {
            return;
        }

        $order = wc_get_order( $order_id );

        $subject = sprintf(
            __( 'New shop review received for order #%s', 'luma-reviews-plus' ),
            $order instanceof \WC_Order ? $order->get_order_number() : absint( $order_id )
        );

        $lines   = array(
            sprintf( __( 'A new shop-experience review was submitted on %s.', 'luma-reviews-plus' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
            '',
            sprintf( __( 'Order: #%s', 'luma-reviews-plus' ), $order instanceof \WC_Order ? $order->get_order_number() : absint( $order_id ) ),
            sprintf( __( 'Customer: %s', 'luma-reviews-plus' ), (string) $review->display_name ),
            sprintf( __( 'Location: %s', 'luma-reviews-plus' ), '' !== (string) $review->display_location ? (string) $review->display_location : __( 'Not provided', 'luma-reviews-plus' ) ),
            sprintf( __( 'Rating: %d/5', 'luma-reviews-plus' ), absint( $review->rating ) ),
            sprintf( __( 'Tags: %s', 'luma-reviews-plus' ), ! empty( $review->tags ) ? implode( ', ', (array) $review->tags ) : __( 'None', 'luma-reviews-plus' ) ),
            sprintf( __( 'Public consent: %s', 'luma-reviews-plus' ), ! empty( $review->public_consent ) ? __( 'Yes', 'luma-reviews-plus' ) : __( 'No', 'luma-reviews-plus' ) ),
            sprintf( __( 'Token ID: %d', 'luma-reviews-plus' ), absint( $token_id ) ),
            '',
            __( 'Comment:', 'luma-reviews-plus' ),
            '' !== (string) $review->comment ? (string) $review->comment : __( 'No comment provided.', 'luma-reviews-plus' ),
            '',
            __( 'Open shop reviews:', 'luma-reviews-plus' ),
            admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews' ),
        );

        if ( $order instanceof \WC_Order ) {
            $lines[] = '';
            $lines[] = __( 'Open order:', 'luma-reviews-plus' );
            $lines[] = admin_url( 'post.php?post=' . absint( $order->get_id() ) . '&action=edit' );
        }

        $sent = wp_mail( $recipient, $subject, implode( PHP_EOL, $lines ) );

        if ( ! $sent ) {
            Helpers::log( sprintf( 'Failed to send shop review notification for review %d to %s.', absint( $review_id ), $recipient ), 'warning' );
        }
    }
}