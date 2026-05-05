<?php

namespace Luma\ReviewsPlus\Admin;

use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Settings\Settings;

/**
 * Provides the admin overview for shop reviews.
 *
 * Responsibilities:
 * - Register the WooCommerce admin page for shop reviews.
 * - Render the moderation table for verified shop reviews.
 * - Handle approval and deletion actions with capability and nonce checks.
 */
class ShopReviewsPage {

    /**
     * Shop review repository.
     *
     * @var ShopReviewRepository
     */
    protected $shop_review_repository;


    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Creates the admin page handler.
     *
     * @param ShopReviewRepository $shop_review_repository Repository.
     * @param Settings             $settings Settings service.
     */
    public function __construct( ShopReviewRepository $shop_review_repository, Settings $settings ) {
        $this->shop_review_repository = $shop_review_repository;
        $this->settings               = $settings;
    }


    /**
     * Registers admin hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
    }


    /**
     * Adds the submenu page.
     *
     * @return void
     */
    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Shop Reviews', 'luma-reviews-plus' ),
            __( 'Shop Reviews', 'luma-reviews-plus' ),
            'manage_woocommerce',
            'luma-reviews-plus-shop-reviews',
            array( $this, 'render_page' )
        );
    }


    /**
     * Handles moderation actions.
     *
     * @return void
     */
    public function handle_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        if ( 'luma-reviews-plus-shop-reviews' !== $page ) {
            return;
        }

        $action    = isset( $_GET['luma_reviews_plus_action'] ) ? sanitize_key( wp_unslash( $_GET['luma_reviews_plus_action'] ) ) : '';
        $review_id = isset( $_GET['review_id'] ) ? absint( wp_unslash( $_GET['review_id'] ) ) : 0;

        if ( ! $action || ! $review_id ) {
            return;
        }

        check_admin_referer( 'luma_reviews_plus_shop_review_action_' . $review_id );

        if ( 'approve' === $action ) {
            $this->shop_review_repository->set_public_approval( $review_id, 1 );
        } elseif ( 'unapprove' === $action ) {
            $this->shop_review_repository->set_public_approval( $review_id, 0 );
        } elseif ( 'delete' === $action ) {
            $this->shop_review_repository->delete_review( $review_id );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews' ) );
        exit;
    }


    /**
     * Renders the shop reviews table.
     *
     * @return void
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'luma-reviews-plus' ) );
        }

        $reviews = $this->shop_review_repository->list_reviews();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Shop Reviews', 'luma-reviews-plus' ) . '</h1>';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Date', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Order', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Customer', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Rating', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Tags', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Comment', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Public consent', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Public approved', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'luma-reviews-plus' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $reviews ) ) {
            echo '<tr><td colspan="9">' . esc_html__( 'No shop reviews found yet.', 'luma-reviews-plus' ) . '</td></tr>';
        }

        foreach ( $reviews as $review ) {
            $approve_action = empty( $review->approved_for_public_display ) ? 'approve' : 'unapprove';
            $approve_label  = empty( $review->approved_for_public_display ) ? __( 'Approve for public display', 'luma-reviews-plus' ) : __( 'Remove public approval', 'luma-reviews-plus' );
            $approve_url    = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=' . $approve_action . '&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $delete_url     = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=delete&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $order_url      = admin_url( 'post.php?post=' . absint( $review->order_id ) . '&action=edit' );

            echo '<tr>';
            echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $review->created_at ) ) . '</td>';
            echo '<td><a href="' . esc_url( $order_url ) . '">#' . esc_html( $review->order_id ) . '</a></td>';
            echo '<td>' . esc_html( $review->display_name ) . '</td>';
            echo '<td>' . esc_html( $review->rating ) . '/5</td>';
            echo '<td>' . esc_html( implode( ', ', (array) $review->tags ) ) . '</td>';
            echo '<td>' . esc_html( $review->comment ) . '</td>';
            echo '<td>' . ( ! empty( $review->public_consent ) ? esc_html__( 'Yes', 'luma-reviews-plus' ) : esc_html__( 'No', 'luma-reviews-plus' ) ) . '</td>';
            echo '<td>' . ( ! empty( $review->approved_for_public_display ) ? esc_html__( 'Yes', 'luma-reviews-plus' ) : esc_html__( 'No', 'luma-reviews-plus' ) ) . '</td>';
            echo '<td>';
            echo '<a class="button button-secondary" href="' . esc_url( $approve_url ) . '">' . esc_html( $approve_label ) . '</a> ';
            echo '<a class="button button-link-delete" href="' . esc_url( $delete_url ) . '">' . esc_html__( 'Delete review', 'luma-reviews-plus' ) . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}
