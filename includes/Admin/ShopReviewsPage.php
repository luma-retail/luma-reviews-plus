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
        add_action( 'wp_ajax_luma_reviews_plus_toggle_featured_shop_review', array( $this, 'handle_toggle_featured_ajax' ) );
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
        } elseif ( 'feature' === $action ) {
            $this->shop_review_repository->set_featured( $review_id, 1 );
        } elseif ( 'unfeature' === $action ) {
            $this->shop_review_repository->set_featured( $review_id, 0 );
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
        echo '<th>' . esc_html__( 'Featured', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'luma-reviews-plus' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $reviews ) ) {
            echo '<tr><td colspan="10">' . esc_html__( 'No shop reviews found yet.', 'luma-reviews-plus' ) . '</td></tr>';
        }

        foreach ( $reviews as $review ) {
            $can_be_published = $this->can_review_be_published( $review );
            $approve_action   = empty( $review->approved_for_public_display ) ? 'approve' : 'unapprove';
            $approve_label    = empty( $review->approved_for_public_display ) ? __( 'Approve for public display', 'luma-reviews-plus' ) : __( 'Remove public approval', 'luma-reviews-plus' );
            $approve_url      = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=' . $approve_action . '&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $feature_action = empty( $review->is_featured ) ? 'feature' : 'unfeature';
            $feature_label  = empty( $review->is_featured ) ? __( 'Mark as featured', 'luma-reviews-plus' ) : __( 'Remove featured mark', 'luma-reviews-plus' );
            $feature_nonce  = wp_create_nonce( 'luma_reviews_plus_toggle_featured_' . absint( $review->id ) );
            $feature_url    = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=' . $feature_action . '&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $delete_url     = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=delete&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $order_url      = admin_url( 'post.php?post=' . absint( $review->order_id ) . '&action=edit' );

            echo '<tr>';
            echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $review->created_at ) ) . '</td>';
            echo '<td><a href="' . esc_url( $order_url ) . '">#' . esc_html( $review->order_id ) . '</a></td>';
            echo '<td>' . esc_html( $review->display_name ) . '</td>';
            echo '<td>' . esc_html( $review->rating ) . '/5</td>';
            echo '<td class="luma-reviews-plus-shop-reviews__tags">' . esc_html( implode( ', ', (array) $review->tags ) ) . '</td>';
            echo '<td class="luma-reviews-plus-shop-reviews__comment">' . esc_html( $review->comment ) . '</td>';
            echo '<td>' . ( ! empty( $review->public_consent ) ? esc_html__( 'Yes', 'luma-reviews-plus' ) : esc_html__( 'No', 'luma-reviews-plus' ) ) . '</td>';
            echo '<td>' . ( ! empty( $review->approved_for_public_display ) ? esc_html__( 'Yes', 'luma-reviews-plus' ) : esc_html__( 'No', 'luma-reviews-plus' ) ) . '</td>';
            echo '<td>';
            echo '<a class="luma-reviews-plus-feature-toggle" href="' . esc_url( $feature_url ) . '" data-review-id="' . esc_attr( absint( $review->id ) ) . '" data-featured="' . esc_attr( ! empty( $review->is_featured ) ? '1' : '0' ) . '" data-nonce="' . esc_attr( $feature_nonce ) . '" aria-label="' . esc_attr( $feature_label ) . '" title="' . esc_attr( $feature_label ) . '">';
            echo '<span class="dashicons ' . esc_attr( ! empty( $review->is_featured ) ? 'dashicons-star-filled' : 'dashicons-star-empty' ) . '" aria-hidden="true"></span>';
            echo '</a>';
            echo '</td>';
            echo '<td>';
            if ( ! empty( $review->approved_for_public_display ) || $can_be_published ) {
                echo '<a class="button button-secondary" href="' . esc_url( $approve_url ) . '">' . esc_html( $approve_label ) . '</a> ';
            } else {
                echo '<span class="description">' . esc_html__( 'Requires consent and comment to publish', 'luma-reviews-plus' ) . '</span> ';
            }
            echo '<a class="button button-link-delete" href="' . esc_url( $delete_url ) . '">' . esc_html__( 'Delete review', 'luma-reviews-plus' ) . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        $this->render_featured_toggle_script();
        echo '</div>';
    }


    /**
     * Handles AJAX feature toggles.
     *
     * @return void
     */
    public function handle_toggle_featured_ajax() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Not allowed.', 'luma-reviews-plus' ) ), 403 );
        }

        $review_id = isset( $_POST['review_id'] ) ? absint( wp_unslash( $_POST['review_id'] ) ) : 0;
        $nonce     = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( $review_id <= 0 || ! wp_verify_nonce( $nonce, 'luma_reviews_plus_toggle_featured_' . $review_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'luma-reviews-plus' ) ), 400 );
        }

        $review = $this->shop_review_repository->get_by_id( $review_id );

        if ( ! $review ) {
            wp_send_json_error( array( 'message' => __( 'Review not found.', 'luma-reviews-plus' ) ), 404 );
        }

        $is_featured = empty( $review->is_featured ) ? 1 : 0;
        $this->shop_review_repository->set_featured( $review_id, $is_featured );

        wp_send_json_success(
            array(
                'review_id'    => $review_id,
                'is_featured'  => $is_featured,
                'aria_label'   => $is_featured ? __( 'Remove featured mark', 'luma-reviews-plus' ) : __( 'Mark as featured', 'luma-reviews-plus' ),
            )
        );
    }


    /**
     * Outputs inline JS/CSS for featured toggles.
     *
     * @return void
     */
    protected function render_featured_toggle_script() {
        ?>
        <style>
            .luma-reviews-plus-shop-reviews__tags {
                color: #8a8f98;
                font-size: 12px;
                line-height: 1.4;
            }

            .luma-reviews-plus-shop-reviews__comment {
                color: #1d2327;
                font-size: 14px;
                font-weight: 500;
                line-height: 1.45;
            }

            .luma-reviews-plus-feature-toggle {
                color: #8a8f98;
                text-decoration: none;
            }

            .luma-reviews-plus-feature-toggle[data-featured="1"] {
                color: #f4b400;
            }

            .luma-reviews-plus-feature-toggle .dashicons {
                font-size: 20px;
                width: 20px;
                height: 20px;
                line-height: 20px;
            }

            .luma-reviews-plus-feature-toggle.is-busy {
                opacity: 0.55;
                pointer-events: none;
            }
        </style>
        <script>
            (function () {
                var links = document.querySelectorAll('.luma-reviews-plus-feature-toggle');

                if (!links.length) {
                    return;
                }

                links.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();

                        if (!window.ajaxurl || link.classList.contains('is-busy')) {
                            return;
                        }

                        link.classList.add('is-busy');

                        var formData = new window.FormData();
                        formData.append('action', 'luma_reviews_plus_toggle_featured_shop_review');
                        formData.append('review_id', link.getAttribute('data-review-id') || '');
                        formData.append('nonce', link.getAttribute('data-nonce') || '');

                        window.fetch(window.ajaxurl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: formData
                        }).then(function (response) {
                            return response.json();
                        }).then(function (payload) {
                            if (!payload || !payload.success || !payload.data) {
                                return;
                            }

                            var featured = Number(payload.data.is_featured) === 1;
                            var icon = link.querySelector('.dashicons');

                            link.setAttribute('data-featured', featured ? '1' : '0');
                            link.setAttribute('aria-label', payload.data.aria_label || '');
                            link.setAttribute('title', payload.data.aria_label || '');

                            if (icon) {
                                icon.classList.remove('dashicons-star-empty', 'dashicons-star-filled');
                                icon.classList.add(featured ? 'dashicons-star-filled' : 'dashicons-star-empty');
                            }
                        }).catch(function () {
                            // Keep fallback behavior silent in admin tables.
                        }).finally(function () {
                            link.classList.remove('is-busy');
                        });
                    });
                });
            })();
        </script>
        <?php
    }


    /**
     * Returns whether a review can be approved for public display.
     *
     * @param object $review Review row.
     * @return bool
     */
    protected function can_review_be_published( $review ) {
        if ( empty( $review->public_consent ) ) {
            return false;
        }

        return '' !== trim( (string) ( $review->comment ?? '' ) );
    }
}
