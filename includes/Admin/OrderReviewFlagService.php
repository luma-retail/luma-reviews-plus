<?php

namespace Luma\ReviewsPlus\Admin;

use Luma\ReviewsPlus\Database\ProductReviewLogRepository;
use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Settings\Settings;

/**
 * Provides and optionally renders admin review flags for WooCommerce orders.
 *
 * Responsibilities:
 * - Resolve whether the customer reviewed the previous order.
 * - Expose normalized order-flag data through a public filter contract.
 * - Render the optional WooCommerce admin order-page notice.
 */
class OrderReviewFlagService {

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
     * Shop review repository.
     *
     * @var ShopReviewRepository
     */
    protected $shop_review_repository;


    /**
     * Creates the service.
     *
     * @param Settings                   $settings Settings service.
     * @param ProductReviewLogRepository $product_review_log_repository Product review repository.
     * @param ShopReviewRepository       $shop_review_repository Shop review repository.
     */
    public function __construct( Settings $settings, ProductReviewLogRepository $product_review_log_repository, ShopReviewRepository $shop_review_repository ) {
        $this->settings                      = $settings;
        $this->product_review_log_repository = $product_review_log_repository;
        $this->shop_review_repository        = $shop_review_repository;
    }


    /**
     * Registers service hooks.
     *
     * @return void
     */
    public function register() {
        add_filter( 'luma_reviews_plus_order_review_flag_data', array( $this, 'populate_order_review_flag_data' ), 10, 2 );

        if ( $this->settings->should_show_admin_order_review_flag() ) {
            add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'render_admin_order_flag' ) );
        }
    }


    /**
     * Populates review-flag data for the given order.
     *
     * @param array          $data Existing review-flag data.
     * @param \WC_Order|bool $order Order object when available.
     * @return array
     */
    public function populate_order_review_flag_data( array $data, $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return $data;
        }

        $previous_order_match = $this->find_previous_order_match( $order );
        $previous_order       = $previous_order_match['order'];

        if ( ! $previous_order instanceof \WC_Order ) {
            return $data;
        }

        $has_product_reviews = $this->product_review_log_repository->has_reviews_for_order( $previous_order->get_id() );
        $has_shop_review     = $this->shop_review_repository->has_review_for_order( $previous_order->get_id() );

        if ( ! $has_product_reviews && ! $has_shop_review ) {
            return $data;
        }

        $review_types = array();

        if ( $has_product_reviews ) {
            $review_types[] = 'product';
        }

        if ( $has_shop_review ) {
            $review_types[] = 'shop';
        }

        $data = array_merge(
            $data,
            array(
                'has_flag'            => true,
                'current_order_id'    => $order->get_id(),
                'previous_order_id'   => $previous_order->get_id(),
                'previous_order_number' => (string) $previous_order->get_order_number(),
                'has_product_reviews' => $has_product_reviews,
                'has_shop_review'     => $has_shop_review,
                'review_types'        => $review_types,
                'matched_customer_by' => $previous_order_match['matched_customer_by'],
            )
        );

        $data['message'] = apply_filters( 'luma_reviews_plus_order_review_flag_message', $this->get_flag_message( $data ), $data, $order, $previous_order );

        return $data;
    }


    /**
     * Renders the optional flag on WooCommerce admin order pages.
     *
     * @param \WC_Order|int $order WooCommerce order object or ID.
     * @return void
     */
    public function render_admin_order_flag( $order ) {
        $order = $order instanceof \WC_Order ? $order : wc_get_order( $order );

        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $data = luma_reviews_plus_get_order_review_flag_data( $order );

        if ( empty( $data['has_flag'] ) || '' === (string) $data['message'] ) {
            return;
        }

        echo '<p class="form-field form-field-wide luma-reviews-plus-order-review-flag">';
        echo '<span class="luma-reviews-plus-order-review-flag__icon" aria-hidden="true">&#9733;</span> ';
        echo esc_html( $data['message'] );
        echo '</p>';
    }


    /**
     * Returns the default flag message.
     *
     * @param array $data Review-flag data.
     * @return string
     */
    protected function get_flag_message( array $data ) {
        if ( $data['has_product_reviews'] && $data['has_shop_review'] ) {
            return sprintf( __( 'Customer reviewed the previous order #%s with both product and shop feedback.', 'luma-reviews-plus' ), $data['previous_order_number'] );
        }

        if ( $data['has_product_reviews'] ) {
            return sprintf( __( 'Customer reviewed the previous order #%s with product feedback.', 'luma-reviews-plus' ), $data['previous_order_number'] );
        }

        return sprintf( __( 'Customer reviewed the previous order #%s with shop feedback.', 'luma-reviews-plus' ), $data['previous_order_number'] );
    }


    /**
     * Finds the previous order for the same customer identity.
     *
     * @param \WC_Order $order Current order.
     * @return array
     */
    protected function find_previous_order_match( \WC_Order $order ) {
        $matches          = array();
        $current_order_id = $order->get_id();
        $current_ts       = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0;
        $customer_id      = absint( $order->get_customer_id() );
        $billing_email    = sanitize_email( (string) $order->get_billing_email() );

        if ( $customer_id > 0 ) {
            foreach ( $this->get_orders_by_identity( array( 'customer_id' => $customer_id ) ) as $candidate ) {
                $matches[ $candidate->get_id() ] = array(
                    'order'               => $candidate,
                    'matched_customer_by' => 'customer_id',
                );
            }
        }

        if ( '' !== $billing_email ) {
            foreach ( $this->get_orders_by_identity( array( 'billing_email' => $billing_email ) ) as $candidate ) {
                if ( isset( $matches[ $candidate->get_id() ] ) ) {
                    continue;
                }

                $matches[ $candidate->get_id() ] = array(
                    'order'               => $candidate,
                    'matched_customer_by' => 'billing_email',
                );
            }
        }

        uasort(
            $matches,
            static function( $left, $right ) {
                $left_order  = $left['order'];
                $right_order = $right['order'];
                $left_ts     = $left_order->get_date_created() ? $left_order->get_date_created()->getTimestamp() : 0;
                $right_ts    = $right_order->get_date_created() ? $right_order->get_date_created()->getTimestamp() : 0;

                if ( $left_ts === $right_ts ) {
                    return $right_order->get_id() <=> $left_order->get_id();
                }

                return $right_ts <=> $left_ts;
            }
        );

        foreach ( $matches as $match ) {
            $candidate = $match['order'];

            if ( $candidate->get_id() === $current_order_id ) {
                continue;
            }

            $candidate_ts = $candidate->get_date_created() ? $candidate->get_date_created()->getTimestamp() : 0;

            if ( $current_ts > 0 && $candidate_ts > $current_ts ) {
                continue;
            }

            if ( $current_ts > 0 && $candidate_ts === $current_ts && $candidate->get_id() > $current_order_id ) {
                continue;
            }

            return $match;
        }

        return array(
            'order'               => null,
            'matched_customer_by' => '',
        );
    }


    /**
     * Returns recent orders for one customer identity.
     *
     * @param array $identity Query arguments for wc_get_orders().
     * @return \WC_Order[]
     */
    protected function get_orders_by_identity( array $identity ) {
        $query_args = array_merge(
            array(
                'limit'   => 10,
                'orderby' => 'date',
                'order'   => 'DESC',
                'return'  => 'objects',
                'type'    => 'shop_order',
                'status'  => array_keys( wc_get_order_statuses() ),
            ),
            $identity
        );

        $orders = wc_get_orders( $query_args );

        return array_values( array_filter( $orders, static function( $order ) {
            return $order instanceof \WC_Order;
        } ) );
    }
}