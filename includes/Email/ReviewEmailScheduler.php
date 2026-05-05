<?php

namespace Luma\ReviewsPlus\Email;

use Luma\ReviewsPlus\Database\TokenRepository;
use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Schedules and sends review request emails.
 *
 * Responsibilities:
 * - Schedule review requests when orders reach eligible statuses.
 * - Expose a stable Action Scheduler contract for addons.
 * - Send review emails from scheduled or manual triggers.
 * - Unschedule jobs when orders become ineligible.
 */
class ReviewEmailScheduler {

    /**
     * Action hook name.
     *
     * @var string
     */
    const ACTION_HOOK = 'luma_reviews_plus_send_review_request_email';


    /**
     * Action Scheduler group.
     *
     * @var string
     */
    const ACTION_GROUP = 'luma_reviews_plus';


    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Token repository.
     *
     * @var TokenRepository
     */
    protected $token_repository;


    /**
     * Review link generator.
     *
     * @var ReviewLinkGenerator
     */
    protected $link_generator;


    /**
     * Review email.
     *
     * @var ReviewEmail
     */
    protected $review_email;


    /**
     * Creates the scheduler.
     *
     * @param Settings            $settings Settings service.
     * @param TokenRepository     $token_repository Token repository.
     * @param ReviewLinkGenerator $link_generator Link generator.
     * @param ReviewEmail         $review_email Email instance.
     */
    public function __construct( Settings $settings, TokenRepository $token_repository, ReviewLinkGenerator $link_generator, ReviewEmail $review_email ) {
        $this->settings         = $settings;
        $this->token_repository = $token_repository;
        $this->link_generator   = $link_generator;
        $this->review_email     = $review_email;
    }


    /**
     * Registers scheduler hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
        add_action( self::ACTION_HOOK, array( $this, 'send_scheduled_review_request' ) );
        add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ) );
        add_action( 'woocommerce_order_action_luma_reviews_plus_send_review_request', array( $this, 'handle_manual_order_action' ) );
    }


    /**
     * Handles order status changes.
     *
     * @param int       $order_id Order ID.
     * @param string    $old_status Old status.
     * @param string    $new_status New status.
     * @param \WC_Order $order Order object.
     * @return void
     */
    public function handle_order_status_change( $order_id, $old_status, $new_status, $order ) {
        if ( ! $order instanceof \WC_Order ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! $order ) {
            return;
        }

        $old_eligible = in_array( $old_status, $this->get_eligible_statuses(), true );
        $new_eligible = in_array( $new_status, $this->get_eligible_statuses(), true );

        if ( ! $old_eligible && $new_eligible ) {
            $this->maybe_schedule_default_request( $order );
        }

        if ( $old_eligible && ! $new_eligible ) {
            $this->unschedule_review_request( $order_id, 'status_changed' );
        }
    }


    /**
     * Adds a manual WooCommerce order action.
     *
     * @param array $actions Existing actions.
     * @return array
     */
    public function add_order_action( $actions ) {
        $actions['luma_reviews_plus_send_review_request'] = __( 'Send review request', 'luma-reviews-plus' );

        return $actions;
    }


    /**
     * Handles manual review request sends.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return void
     */
    public function handle_manual_order_action( $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $this->send_review_request_for_order( $order, true );
        $order->add_order_note( __( 'Luma Reviews Plus review request sent manually.', 'luma-reviews-plus' ) );
    }


    /**
     * Schedules the default review request for an eligible order.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return void
     */
    protected function maybe_schedule_default_request( \WC_Order $order ) {
        if ( ! $this->settings->is_review_requests_enabled() ) {
            return;
        }

        if ( ! apply_filters( 'luma_reviews_plus_should_schedule_review_request', true, $order ) ) {
            return;
        }

        $timestamp = time() + ( $this->settings->get_review_email_delay_days() * DAY_IN_SECONDS );
        $timestamp = (int) apply_filters( 'luma_reviews_plus_review_request_schedule_timestamp', $timestamp, $order, 'default' );

        $this->schedule_review_request( $order->get_id(), $timestamp, 'default' );
    }


    /**
     * Schedules a review request job.
     *
     * @param int    $order_id Order ID.
     * @param int    $timestamp Run timestamp.
     * @param string $reason Schedule reason.
     * @return int
     */
    public function schedule_review_request( $order_id, $timestamp, $reason = 'default' ) {
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            return 0;
        }

        $job_id = as_schedule_single_action(
            absint( $timestamp ),
            self::ACTION_HOOK,
            array( 'order_id' => absint( $order_id ) ),
            self::ACTION_GROUP
        );

        if ( $job_id ) {
            do_action( 'luma_reviews_plus_review_request_scheduled', absint( $order_id ), absint( $job_id ), absint( $timestamp ), $reason );
        }

        return (int) $job_id;
    }


    /**
     * Unschedules a pending review request job.
     *
     * @param int    $order_id Order ID.
     * @param string $reason Unschedule reason.
     * @return int
     */
    public function unschedule_review_request( $order_id, $reason = 'manual' ) {
        if ( ! function_exists( 'as_unschedule_action' ) ) {
            return 0;
        }

        $job_id = function_exists( 'as_next_scheduled_action' ) ? as_next_scheduled_action( self::ACTION_HOOK, array( 'order_id' => absint( $order_id ) ), self::ACTION_GROUP ) : 0;

        as_unschedule_action( self::ACTION_HOOK, array( 'order_id' => absint( $order_id ) ), self::ACTION_GROUP );
        do_action( 'luma_reviews_plus_review_request_unscheduled', absint( $order_id ), absint( $job_id ), $reason );

        return (int) $job_id;
    }


    /**
     * Returns whether a request is already scheduled.
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function is_review_request_scheduled( $order_id ) {
        return function_exists( 'as_next_scheduled_action' ) && (bool) as_next_scheduled_action( self::ACTION_HOOK, array( 'order_id' => absint( $order_id ) ), self::ACTION_GROUP );
    }


    /**
     * Sends a scheduled review request.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function send_scheduled_review_request( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof \WC_Order ) {
            Helpers::log( 'Unable to load order for scheduled review request: ' . absint( $order_id ), 'error' );
            return;
        }

        $this->send_review_request_for_order( $order, false );
    }


    /**
     * Sends a review request for an order.
     *
     * @param \WC_Order $order WooCommerce order.
     * @param bool       $manual Whether this is a manual send.
     * @return void
     */
    protected function send_review_request_for_order( \WC_Order $order, $manual ) {
        if ( ! $this->settings->is_review_requests_enabled() || ! $this->is_order_eligible( $order ) ) {
            return;
        }

        if ( ! $manual && $order->get_meta( '_luma_reviews_plus_review_request_sent_at', true ) ) {
            return;
        }

        $token = $this->token_repository->create_token_for_order( $order->get_id(), $order->get_customer_id() );

        if ( ! $token ) {
            return;
        }

        $review_link = $this->link_generator->get_review_page_url( $token['raw_token'], $order );

        $this->review_email->trigger( $order->get_id(), $review_link );
        $this->token_repository->mark_sent( $token['id'] );

        $order->update_meta_data( '_luma_reviews_plus_review_request_sent_at', current_time( 'mysql' ) );
        $order->update_meta_data( '_luma_reviews_plus_review_request_token_id', $token['id'] );
        $order->save();

        do_action( 'luma_reviews_plus_review_email_sent', $order->get_id(), $review_link );
    }


    /**
     * Returns eligible order statuses.
     *
     * @return array
     */
    protected function get_eligible_statuses() {
        return array_values( array_filter( (array) apply_filters( 'luma_reviews_plus_eligible_order_statuses', array( 'completed' ) ) ) );
    }


    /**
     * Returns whether an order is still eligible.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return bool
     */
    protected function is_order_eligible( \WC_Order $order ) {
        if ( in_array( $order->get_status(), array( 'cancelled', 'failed', 'refunded', 'trash' ), true ) ) {
            return false;
        }

        return in_array( $order->get_status(), $this->get_eligible_statuses(), true );
    }
}
