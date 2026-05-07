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

        if ( empty( $review->public_consent ) ) {
            return;
        }

        $order = \wc_get_order( $order_id );

        $subject = \sprintf(
            __( 'New shop review received for order #%s', 'luma-reviews-plus' ),
            $order instanceof \WC_Order ? $order->get_order_number() : \absint( $order_id )
        );

        $message = $this->get_message_html( $review, $order, $order_id );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        $sent = \wp_mail( $recipient, $subject, $message, $headers );

        if ( ! $sent ) {
            Helpers::log( \sprintf( 'Failed to send shop review notification for review %d to %s.', \absint( $review_id ), $recipient ), 'warning' );
        }
    }


    /**
     * Builds the HTML email body.
     *
    * @param object         $review Shop review row.
    * @param \WC_Order|bool $order Order object when available.
    * @param int            $order_id Order ID fallback.
     * @return string
     */
    protected function get_message_html( $review, $order, $order_id ) {
        $blog_name        = \wp_specialchars_decode( \get_bloginfo( 'name' ), ENT_QUOTES );
        $order_number     = $order instanceof \WC_Order ? $order->get_order_number() : \absint( $order_id );
        $customer_summary = $this->get_customer_summary( $review, $order );
        $comment          = \trim( (string) $review->comment );
        $tags             = ! empty( $review->tags ) ? \implode( ', ', (array) $review->tags ) : '';
        $shop_reviews_url = \admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews' );
        $comment_html     = '' !== $comment ? \nl2br( \esc_html( $comment ) ) : \esc_html__( 'No comment provided.', 'luma-reviews-plus' );

        $parts = array(
            '<div style="background:#f6f2eb;padding:24px 0;font-family:Arial,sans-serif;color:#1f1a17;">',
            '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e7ddd0;border-radius:16px;padding:32px 28px;">',
            '<p style="margin:0 0 24px;font-size:16px;line-height:1.5;">' . \esc_html( \sprintf( __( 'A new shop-experience review was submitted on %s.', 'luma-reviews-plus' ), $blog_name ) ) . '</p>',
            '<div style="margin:0 0 24px;padding:20px;border-radius:14px;background:#fcf7ef;border:1px solid #ead8b9;">',
            '<div style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#7a5b2b;">' . \esc_html__( 'Rating', 'luma-reviews-plus' ) . '</div>',
            '<div style="margin:0 0 8px;font-size:30px;line-height:1;">' . $this->get_rating_stars_html( \absint( $review->rating ) ) . '</div>',
            '<div style="font-size:24px;font-weight:700;line-height:1.2;">' . \esc_html( \sprintf( __( '%d/5', 'luma-reviews-plus' ), \absint( $review->rating ) ) ) . '</div>',
            '</div>',
            '<div style="margin:0 0 24px;">',
            '<div style="margin:0 0 10px;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#7a5b2b;">' . \esc_html__( 'Comment', 'luma-reviews-plus' ) . '</div>',
            '<div style="padding:18px 20px;background:#faf7f2;border-left:4px solid #b4863a;border-radius:0 12px 12px 0;font-size:18px;line-height:1.6;">' . $comment_html . '</div>',
            '</div>',
            '<p style="margin:0 0 10px;font-size:15px;line-height:1.6;"><strong>' . \esc_html__( 'Customer:', 'luma-reviews-plus' ) . '</strong> ' . \esc_html( $customer_summary ) . '</p>',
            '<p style="margin:0 0 10px;font-size:15px;line-height:1.6;"><strong>' . \esc_html__( 'Order:', 'luma-reviews-plus' ) . '</strong> #' . \esc_html( (string) $order_number ) . '</p>',
        );

        if ( '' !== $tags ) {
            $parts[] = '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;"><strong>' . \esc_html__( 'Tags:', 'luma-reviews-plus' ) . '</strong> ' . \esc_html( $tags ) . '</p>';
        }

        $parts[] = '<div style="margin-top:28px;">';
        $parts[] = '<a href="' . \esc_url( $shop_reviews_url ) . '" style="display:inline-block;padding:14px 22px;background:#1f1a17;color:#ffffff;text-decoration:none;border-radius:999px;font-weight:700;">' . \esc_html__( 'Open shop reviews for publishing', 'luma-reviews-plus' ) . '</a>';
        $parts[] = '<p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:#6b6259;">' . \esc_html( $shop_reviews_url ) . '</p>';
        $parts[] = '</div>';
        $parts[] = '</div>';
        $parts[] = '</div>';

        return \implode( '', $parts );
    }


    /**
     * Returns a one-line customer summary.
     *
     * @param object         $review Shop review row.
     * @param \WC_Order|bool $order Order object when available.
     * @return string
     */
    protected function get_customer_summary( $review, $order ) {
        $display_name = \trim( (string) $review->display_name );
        $full_name    = $order instanceof \WC_Order ? \trim( Helpers::get_order_customer_name( $order ) ) : '';
        $location     = \trim( (string) $review->display_location );

        if ( '' === $display_name ) {
            $display_name = $full_name;
        }

        if ( '' !== $full_name && '' !== $display_name && 0 !== \strcasecmp( $display_name, $full_name ) ) {
            $display_name = \sprintf( __( '%1$s (%2$s)', 'luma-reviews-plus' ), $display_name, $full_name );
        }

        if ( '' !== $location ) {
            return '' !== $display_name ? $display_name . ', ' . $location : $location;
        }

        return $display_name;
    }


    /**
     * Returns HTML for the rating stars.
     *
     * @param int $rating Review rating.
     * @return string
     */
    protected function get_rating_stars_html( $rating ) {
        $rating = max( 0, min( 5, \absint( $rating ) ) );
        $stars  = '';

        for ( $index = 1; $index <= 5; $index++ ) {
            $stars .= $index <= $rating
                ? '<span style="color:#d5a021;">&#9733;</span>'
                : '<span style="color:#d9d1c5;">&#9734;</span>';
        }

        return $stars;
    }
}