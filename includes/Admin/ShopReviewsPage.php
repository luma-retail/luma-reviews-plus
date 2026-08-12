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

        $action    = '';
        $review_id = 0;

        if ( isset( $_POST['luma_reviews_plus_action'] ) ) {
            $action    = sanitize_key( wp_unslash( $_POST['luma_reviews_plus_action'] ) );
            $review_id = isset( $_POST['review_id'] ) ? absint( wp_unslash( $_POST['review_id'] ) ) : 0;
        } elseif ( isset( $_GET['luma_reviews_plus_action'] ) ) {
            $action    = sanitize_key( wp_unslash( $_GET['luma_reviews_plus_action'] ) );
            $review_id = isset( $_GET['review_id'] ) ? absint( wp_unslash( $_GET['review_id'] ) ) : 0;
        }

        if ( ! $action || ! $review_id ) {
            return;
        }

        check_admin_referer( 'luma_reviews_plus_shop_review_action_' . $review_id );

        if ( 'update' === $action ) {
            if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
                return;
            }

            $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
            $comment      = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

            $this->shop_review_repository->update_review_content( $review_id, $display_name, $comment );
        } elseif ( 'approve' === $action ) {
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
        echo '<th>' . esc_html__( 'Rating', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Customer', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Comment', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Public consent', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Public approved', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Order', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Tags', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Featured', 'luma-reviews-plus' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'luma-reviews-plus' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $reviews ) ) {
            echo '<tr><td colspan="10">' . esc_html__( 'No shop reviews found yet.', 'luma-reviews-plus' ) . '</td></tr>';
        }

        foreach ( $reviews as $review ) {
            $can_be_published = $this->can_review_be_published( $review );
            $approve_action   = empty( $review->approved_for_public_display ) ? 'approve' : 'unapprove';
            $approve_label    = empty( $review->approved_for_public_display ) ? __( 'Publish', 'luma-reviews-plus' ) : __( 'Unpublish', 'luma-reviews-plus' );
            $approve_url      = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=' . $approve_action . '&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $feature_action = empty( $review->is_featured ) ? 'feature' : 'unfeature';
            $feature_label  = empty( $review->is_featured ) ? __( 'Mark as featured', 'luma-reviews-plus' ) : __( 'Remove featured mark', 'luma-reviews-plus' );
            $feature_nonce  = wp_create_nonce( 'luma_reviews_plus_toggle_featured_' . absint( $review->id ) );
            $feature_url    = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=' . $feature_action . '&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $delete_url     = wp_nonce_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews&luma_reviews_plus_action=delete&review_id=' . absint( $review->id ) ), 'luma_reviews_plus_shop_review_action_' . absint( $review->id ) );
            $order_url      = admin_url( 'post.php?post=' . absint( $review->order_id ) . '&action=edit' );
            $form_id        = 'luma-reviews-plus-shop-review-edit-' . absint( $review->id );
            $name_field_id  = 'luma-reviews-plus-shop-review-name-' . absint( $review->id );
            $comment_field_id = 'luma-reviews-plus-shop-review-comment-' . absint( $review->id );

            echo '<tr class="luma-reviews-plus-shop-reviews__row" data-edit-row>';
            echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $review->created_at ) ) . '</td>';
            echo '<td>' . esc_html( $review->rating ) . '/5</td>';
            echo '<td>';
            echo '<label class="screen-reader-text" for="' . esc_attr( $name_field_id ) . '">' . esc_html__( 'Customer display name', 'luma-reviews-plus' ) . '</label>';
            echo '<span class="luma-reviews-plus-shop-reviews__view-only">' . esc_html( (string) $review->display_name ) . '</span>';
            echo '<input id="' . esc_attr( $name_field_id ) . '" class="regular-text luma-reviews-plus-shop-reviews__display-name luma-reviews-plus-shop-reviews__edit-only" type="text" name="display_name" form="' . esc_attr( $form_id ) . '" value="' . esc_attr( (string) $review->display_name ) . '" />';
            echo '</td>';
            echo '<td class="luma-reviews-plus-shop-reviews__comment">';
            echo '<label class="screen-reader-text" for="' . esc_attr( $comment_field_id ) . '">' . esc_html__( 'Shop review comment', 'luma-reviews-plus' ) . '</label>';
            echo '<div class="luma-reviews-plus-shop-reviews__view-only luma-reviews-plus-shop-reviews__comment-text">' . esc_html( (string) $review->comment ) . '</div>';
            echo '<textarea id="' . esc_attr( $comment_field_id ) . '" class="luma-reviews-plus-shop-reviews__comment-input luma-reviews-plus-shop-reviews__edit-only" name="comment" form="' . esc_attr( $form_id ) . '" rows="4">' . esc_textarea( (string) $review->comment ) . '</textarea>';
            echo '</td>';
            echo '<td>' . ( ! empty( $review->public_consent ) ? esc_html__( 'Yes', 'luma-reviews-plus' ) : esc_html__( 'No', 'luma-reviews-plus' ) ) . '</td>';
            echo '<td>' . ( ! empty( $review->approved_for_public_display ) ? esc_html__( 'Yes', 'luma-reviews-plus' ) : esc_html__( 'No', 'luma-reviews-plus' ) ) . '</td>';
            echo '<td><a href="' . esc_url( $order_url ) . '">#' . esc_html( $review->order_id ) . '</a></td>';
            echo '<td class="luma-reviews-plus-shop-reviews__tags">' . esc_html( implode( ', ', (array) $review->tags ) ) . '</td>';
            echo '<td>';
            echo '<a class="luma-reviews-plus-feature-toggle" href="' . esc_url( $feature_url ) . '" data-review-id="' . esc_attr( absint( $review->id ) ) . '" data-featured="' . esc_attr( ! empty( $review->is_featured ) ? '1' : '0' ) . '" data-nonce="' . esc_attr( $feature_nonce ) . '" aria-label="' . esc_attr( $feature_label ) . '" title="' . esc_attr( $feature_label ) . '">';
            echo '<span class="dashicons ' . esc_attr( ! empty( $review->is_featured ) ? 'dashicons-star-filled' : 'dashicons-star-empty' ) . '" aria-hidden="true"></span>';
            echo '</a>';
            echo '</td>';
            echo '<td>';
            echo '<form method="post" id="' . esc_attr( $form_id ) . '" action="' . esc_url( admin_url( 'admin.php?page=luma-reviews-plus-shop-reviews' ) ) . '">';
            echo wp_nonce_field( 'luma_reviews_plus_shop_review_action_' . absint( $review->id ), '_wpnonce', true, false );
            echo '<input type="hidden" name="luma_reviews_plus_action" value="update" />';
            echo '<input type="hidden" name="review_id" value="' . esc_attr( absint( $review->id ) ) . '" />';
            echo '<button type="button" class="button button-secondary luma-reviews-plus-shop-reviews__edit-button" data-edit-toggle>' . esc_html__( 'Edit', 'luma-reviews-plus' ) . '</button> ';
            echo '<button type="submit" class="button button-primary luma-reviews-plus-shop-reviews__save-button luma-reviews-plus-shop-reviews__edit-only">' . esc_html__( 'Save', 'luma-reviews-plus' ) . '</button> ';
            echo '<button type="button" class="button button-secondary luma-reviews-plus-shop-reviews__cancel-edit luma-reviews-plus-shop-reviews__edit-only" data-edit-cancel>' . esc_html__( 'Cancel', 'luma-reviews-plus' ) . '</button> ';
            echo '</form>';
            if ( ! empty( $review->approved_for_public_display ) || $can_be_published ) {
                echo '<a class="button button-secondary luma-reviews-plus-shop-reviews__moderation-action" href="' . esc_url( $approve_url ) . '">' . esc_html( $approve_label ) . '</a> ';
            } else {
                echo '<span class="description luma-reviews-plus-shop-reviews__moderation-action">' . esc_html__( 'Needs consent and comment', 'luma-reviews-plus' ) . '</span> ';
            }
            echo '<a class="button button-link-delete luma-reviews-plus-shop-reviews__moderation-action" href="' . esc_url( $delete_url ) . '">' . esc_html__( 'Delete review', 'luma-reviews-plus' ) . '</a>';
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

            .luma-reviews-plus-shop-reviews__comment-text {
                white-space: pre-wrap;
            }

            .luma-reviews-plus-shop-reviews__edit-only {
                display: none;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__view-only {
                display: none;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__edit-only {
                display: inline-block;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__comment-input {
                display: block;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__edit-button {
                display: none;
            }

            .luma-reviews-plus-shop-reviews__row:not(.is-editing) .luma-reviews-plus-shop-reviews__save-button {
                display: none;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__save-button {
                display: inline-block;
            }

            .luma-reviews-plus-shop-reviews__row:not(.is-editing) .luma-reviews-plus-shop-reviews__cancel-edit {
                display: none;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__cancel-edit {
                display: inline-block;
            }

            .luma-reviews-plus-shop-reviews__row.is-editing .luma-reviews-plus-shop-reviews__moderation-action {
                display: none;
            }

            .luma-reviews-plus-shop-reviews__display-name {
                width: 100%;
                max-width: 220px;
            }

            .luma-reviews-plus-shop-reviews__comment-input {
                width: 100%;
                min-width: 260px;
                min-height: 84px;
            }

            .luma-reviews-plus-shop-reviews__save-button {
                margin-bottom: 8px;
            }

            .luma-reviews-plus-shop-reviews__cancel-edit {
                margin-bottom: 8px;
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
                var editRows = document.querySelectorAll('[data-edit-row]');

                if (!links.length) {
                    // Continue to support edit toggles even if featured links are absent.
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

                editRows.forEach(function (row) {
                    var editButton = row.querySelector('[data-edit-toggle]');
                    var cancelButton = row.querySelector('[data-edit-cancel]');
                    var form = row.querySelector('form');

                    if (form) {
                        form.dataset.originalDisplayName = (form.querySelector('input[name="display_name"]') || {}).value || '';
                        form.dataset.originalComment = (form.querySelector('textarea[name="comment"]') || {}).value || '';
                    }

                    if (editButton) {
                        editButton.addEventListener('click', function () {
                            row.classList.add('is-editing');
                        });
                    }

                    if (cancelButton) {
                        cancelButton.addEventListener('click', function () {
                            var nameInput = form ? form.querySelector('input[name="display_name"]') : null;
                            var commentInput = form ? form.querySelector('textarea[name="comment"]') : null;

                            if (nameInput && form && typeof form.dataset.originalDisplayName !== 'undefined') {
                                nameInput.value = form.dataset.originalDisplayName;
                            }

                            if (commentInput && form && typeof form.dataset.originalComment !== 'undefined') {
                                commentInput.value = form.dataset.originalComment;
                            }

                            row.classList.remove('is-editing');
                        });
                    }
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
