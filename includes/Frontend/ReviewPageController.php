<?php

namespace Luma\ReviewsPlus\Frontend;

use Luma\ReviewsPlus\Database\ProductReviewLogRepository;
use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Database\TokenRepository;
use Luma\ReviewsPlus\Reviews\ProductReviewHandler;
use Luma\ReviewsPlus\Reviews\ShopReviewHandler;
use Luma\ReviewsPlus\Settings\Settings;

/**
 * Controls the tokenized public review page.
 *
 * Responsibilities:
 * - Register rewrite rules and detect review-page requests.
 * - Validate tokenized requests and load safe order context.
 * - Handle review form submissions.
 * - Render the frontend review experience.
 */
class ReviewPageController {

    /** @var Settings */
    protected $settings;

    /** @var TokenRepository */
    protected $token_repository;

    /** @var ProductReviewLogRepository */
    protected $product_review_log_repository;

    /** @var ShopReviewRepository */
    protected $shop_review_repository;

    /** @var ProductReviewHandler */
    protected $product_review_handler;

    /** @var ShopReviewHandler */
    protected $shop_review_handler;

    /** @var ReviewFormRenderer */
    protected $form_renderer;


    /**
     * Creates the controller.
     *
     * @param Settings                   $settings Settings service.
     * @param TokenRepository            $token_repository Token repository.
     * @param ProductReviewLogRepository $product_review_log_repository Review log repository.
     * @param ShopReviewRepository       $shop_review_repository Shop review repository.
     * @param ProductReviewHandler       $product_review_handler Product review handler.
     * @param ShopReviewHandler          $shop_review_handler Shop review handler.
     * @param ReviewFormRenderer         $form_renderer View renderer.
     */
    public function __construct( Settings $settings, TokenRepository $token_repository, ProductReviewLogRepository $product_review_log_repository, ShopReviewRepository $shop_review_repository, ProductReviewHandler $product_review_handler, ShopReviewHandler $shop_review_handler, ReviewFormRenderer $form_renderer ) {
        $this->settings                      = $settings;
        $this->token_repository              = $token_repository;
        $this->product_review_log_repository = $product_review_log_repository;
        $this->shop_review_repository        = $shop_review_repository;
        $this->product_review_handler        = $product_review_handler;
        $this->shop_review_handler           = $shop_review_handler;
        $this->form_renderer                 = $form_renderer;
    }


    /**
     * Registers controller hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'init', array( $this, 'register_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'maybe_render_review_page' ) );
    }


    /**
     * Registers rewrite rules.
     *
     * @return void
     */
    public function register_rewrite_rules() {
        add_rewrite_tag( '%luma_reviews_plus_review_page%', '1' );
        add_rewrite_rule( '^' . $this->settings->get_review_page_slug() . '/?$', 'index.php?luma_reviews_plus_review_page=1', 'top' );
    }


    /**
     * Adds custom query vars.
     *
     * @param array $vars Existing query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'luma_reviews_plus_review_page';

        return $vars;
    }


    /**
     * Renders the virtual review page when requested.
     *
     * @return void
     */
    public function maybe_render_review_page() {
        if ( '1' !== (string) get_query_var( 'luma_reviews_plus_review_page' ) ) {
            return;
        }

        wp_enqueue_style( 'luma-reviews-plus-frontend' );
        wp_enqueue_script( 'luma-reviews-plus-frontend' );

        $context = $this->build_context();

        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            $context = $this->handle_submission( $context );
        }

        status_header( 200 );
        nocache_headers();
        $this->form_renderer->render_page( $context );
        exit;
    }


    /**
     * Builds the page context.
     *
     * @return array
     */
    protected function build_context() {
        $token_raw = sanitize_text_field( (string) ( $_REQUEST['token'] ?? '' ) );
        $context   = array(
            'state'                  => 'ready',
            'messages'               => array(),
            'token_raw'              => $token_raw,
            'token'                  => null,
            'order'                  => null,
            'review_items'           => array(),
            'shop_review'            => null,
            'posted_product_reviews' => array(),
            'posted_shop_review'     => array(),
        );

        if ( '' === $token_raw ) {
            $context['state']      = 'missing_token';
            $context['messages'][] = $this->settings->get_invalid_token_message();
            return $context;
        }

        $token = $this->token_repository->find_by_raw_token( $token_raw );

        if ( ! $token ) {
            $context['state']      = 'invalid_token';
            $context['messages'][] = $this->settings->get_invalid_token_message();
            return $context;
        }

        if ( in_array( $token->status, array( 'expired', 'disabled' ), true ) ) {
            $context['state']      = $token->status;
            $context['messages'][] = 'expired' === $token->status ? $this->settings->get_expired_token_message() : $this->settings->get_invalid_token_message();
            return $context;
        }

        $order = wc_get_order( $token->order_id );

        if ( ! $order instanceof \WC_Order ) {
            $context['state']      = 'order_not_found';
            $context['messages'][] = __( 'Ordren ble ikke funnet.', 'luma-reviews-plus' );
            return $context;
        }

        if ( ! $this->is_order_eligible( $order ) ) {
            $context['state']      = 'order_not_eligible';
            $context['messages'][] = __( 'Denne ordren er ikke lenger tilgjengelig for vurdering.', 'luma-reviews-plus' );
            return $context;
        }

        $shop_review  = $this->shop_review_repository->get_by_order_id( $order->get_id() );
        $review_items = $this->get_reviewable_items( $order );

        if ( empty( $review_items ) && $shop_review ) {
            $context['state']      = 'already_reviewed';
            $context['messages'][] = $this->settings->get_already_reviewed_message();
        }

        $context['token']        = $token;
        $context['order']        = $order;
        $context['shop_review']  = $shop_review;
        $context['review_items'] = $review_items;

        return $context;
    }


    /**
     * Handles form submissions.
     *
     * @param array $context Existing page context.
     * @return array
     */
    protected function handle_submission( array $context ) {
        $context['posted_product_reviews'] = isset( $_POST['product_reviews'] ) && is_array( $_POST['product_reviews'] ) ? wp_unslash( $_POST['product_reviews'] ) : array();
        $context['posted_shop_review']     = isset( $_POST['shop_review'] ) && is_array( $_POST['shop_review'] ) ? wp_unslash( $_POST['shop_review'] ) : array();

        if ( 'ready' !== $context['state'] || ! $context['token'] || ! $context['order'] ) {
            return $context;
        }

        if ( ! isset( $_POST['luma_reviews_plus_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['luma_reviews_plus_nonce'] ) ), 'luma_reviews_plus_submit_review' ) ) {
            $context['messages'][] = __( 'Sikkerhetskontrollen feilet. Last inn siden pa nytt og prov igjen.', 'luma-reviews-plus' );
            return $context;
        }

        if ( ! $this->settings->allow_partial_product_reviews() && ! $this->all_items_have_ratings( $context['review_items'], $context['posted_product_reviews'] ) ) {
            $context['messages'][] = __( 'Du ma vurdere alle produktene i bestillingen i denne innsendingen.', 'luma-reviews-plus' );
            return $context;
        }

        $product_result = $this->product_review_handler->handle_submission( $context['order'], $context['token'], $context['review_items'], $context['posted_product_reviews'] );

        foreach ( $product_result['errors'] as $error ) {
            $context['messages'][] = $error;
        }

        if ( ! $this->settings->allow_shop_review_without_products() && empty( $product_result['created'] ) && ! empty( $context['posted_shop_review']['rating'] ) ) {
            $context['messages'][] = __( 'Butikkvurdering krever minst en produktvurdering.', 'luma-reviews-plus' );
            return $context;
        }

        $shop_result = $this->shop_review_handler->handle_submission( $context['order'], $context['token'], $context['posted_shop_review'] );

        if ( empty( $product_result['created'] ) && empty( $shop_result['saved'] ) ) {
            $context['messages'][] = __( 'Send inn minst en gyldig vurdering for a fortsette.', 'luma-reviews-plus' );
            return $context;
        }

        $this->token_repository->touch_used( $context['token']->id );

        $context['shop_review']  = $this->shop_review_repository->get_by_order_id( $context['order']->get_id() );
        $context['review_items'] = $this->get_reviewable_items( $context['order'] );
        $context['messages'][]   = $this->settings->get_success_message();

        if ( empty( $context['review_items'] ) ) {
            $this->token_repository->mark_status( $context['token']->id, 'completed' );
            do_action( 'luma_reviews_plus_token_completed', $context['token']->id, $context['order']->get_id() );
        } else {
            $this->token_repository->mark_status( $context['token']->id, 'partially_reviewed' );
        }

        $context['posted_product_reviews'] = array();
        $context['posted_shop_review']     = array();

        if ( empty( $context['review_items'] ) && $context['shop_review'] ) {
            $context['state'] = 'already_reviewed';
        }

        return $context;
    }


    /**
     * Returns whether every reviewable item has a rating.
     *
     * @param array $review_items Reviewable items.
     * @param array $submitted_reviews Submitted reviews.
     * @return bool
     */
    protected function all_items_have_ratings( array $review_items, array $submitted_reviews ) {
        foreach ( array_keys( $review_items ) as $order_item_id ) {
            if ( empty( $submitted_reviews[ $order_item_id ]['rating'] ) ) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns eligible reviewable items for an order.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return array
     */
    protected function get_reviewable_items( \WC_Order $order ) {
        $reviewed_item_ids = $this->product_review_log_repository->get_reviewed_order_item_ids( $order->get_id() );
        $items             = array();

        foreach ( $order->get_items( 'line_item' ) as $order_item_id => $item ) {
            if ( in_array( (int) $order_item_id, $reviewed_item_ids, true ) || $this->is_child_item( $item ) ) {
                continue;
            }

            $product = $item->get_product();

            if ( ! $product || ! $product->is_purchasable() ) {
                continue;
            }

            $target_product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
            $image             = get_the_post_thumbnail_url( $target_product_id, 'woocommerce_thumbnail' );

            $items[ $order_item_id ] = array(
                'order_item_id'   => (int) $order_item_id,
                'item'            => $item,
                'product_id'      => (int) $target_product_id,
                'variation_id'    => (int) $product->get_id() !== (int) $target_product_id ? (int) $product->get_id() : 0,
                'product_name'    => $item->get_name(),
                'product_image'   => $image,
                'purchase_date'   => $order->get_date_completed() ? $order->get_date_completed()->date_i18n( get_option( 'date_format' ) ) : '',
                'variation_meta'  => wc_get_formatted_variation( $product, true, false, true ),
            );
        }

        return (array) apply_filters( 'luma_reviews_plus_reviewable_order_items', $items, $order );
    }


    /**
     * Returns whether an order item is a bundle or composite child.
     *
     * @param \WC_Order_Item_Product $item Order item.
     * @return bool
     */
    protected function is_child_item( \WC_Order_Item_Product $item ) {
        return (bool) ( $item->get_meta( '_bundled_by', true ) || $item->get_meta( '_composite_parent', true ) || $item->get_meta( '_parent_line_item_id', true ) );
    }


    /**
     * Returns whether an order is review-eligible.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return bool
     */
    protected function is_order_eligible( \WC_Order $order ) {
        return in_array( $order->get_status(), (array) apply_filters( 'luma_reviews_plus_eligible_order_statuses', array( 'completed' ) ), true );
    }
}
