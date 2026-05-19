<?php

namespace Luma\ReviewsPlus\Settings;

use Luma\ReviewsPlus\Utils\Sanitizer;

/**
 * Provides typed access to plugin settings.
 *
 * Responsibilities:
 * - Define default settings and settings field metadata.
 * - Sanitize grouped settings values.
 * - Provide typed getters used by runtime services.
 * - Expose one narrow generic accessor for extension-owned keys.
 */
class Settings {

    /**
     * Grouped option name.
     *
     * @var string
     */
    const OPTION_NAME = 'luma_reviews_plus_settings';


    /**
     * Review page option name.
     *
     * @var string
     */
    const REVIEW_PAGE_ID_OPTION = 'luma_reviews_plus_review_page_id';


    /**
     * Returns all default settings.
     *
     * @return array
     */
    public function get_defaults() {
        $defaults = array(
            'review_requests_enabled'             => 1,
            'review_email_delay_days'            => 5,
            'token_expiry_days'                  => 90,
            'public_summary_style'               => 'minimal',
            'review_page_slug'                   => 'review',
            'review_page_heading'                => __( 'Share your review', 'luma-reviews-plus' ),
            'review_page_intro'                  => __( 'Thank you for your purchase. We would love to hear how you experienced the products and the shopping experience.', 'luma-reviews-plus' ),
            'product_reviews_heading'            => __( 'How were the products you bought?', 'luma-reviews-plus' ),
            'product_reviews_intro'              => __( 'You can review one or more products from this order.', 'luma-reviews-plus' ),
            'shop_review_heading'                => __( 'How was your shopping experience with Fru Kvist?', 'luma-reviews-plus' ),
            'shop_review_intro'                  => __( 'Feel free to share how you experienced the store, delivery, and service.', 'luma-reviews-plus' ),
            'shop_review_notifications_enabled'  => 1,
            'shop_review_notification_email'     => \sanitize_email( (string) \get_option( 'admin_email', '' ) ),
            'show_admin_order_review_flag'       => 0,
            'shop_review_tags'                   => array(
                __( 'Fast delivery', 'luma-reviews-plus' ),
                __( 'Beautifully packaged', 'luma-reviews-plus' ),
                __( 'Accurate stock information', 'luma-reviews-plus' ),
                __( 'Helpful customer service', 'luma-reviews-plus' ),
                __( 'Good advice and guidance', 'luma-reviews-plus' ),
                __( 'Easy to shop', 'luma-reviews-plus' ),
                __( 'Other', 'luma-reviews-plus' ),
            ),
            'product_review_comment_required'    => 0,
            'auto_approve_product_reviews'       => 0,
            'allow_shop_review_without_products' => 1,
            'allow_partial_product_reviews'      => 1,
            'submit_button_text'                 => __( 'Submit reviews', 'luma-reviews-plus' ),
            'success_message'                    => __( 'Thank you. Your review has been submitted.', 'luma-reviews-plus' ),
            'expired_token_message'              => __( 'This review link has expired.', 'luma-reviews-plus' ),
            'invalid_token_message'              => __( 'This review link is invalid.', 'luma-reviews-plus' ),
            'already_reviewed_message'           => __( 'All products in this order have already been reviewed.', 'luma-reviews-plus' ),
            'public_consent_text'                => $this->get_default_public_consent_text(),
        );

        return apply_filters( 'luma_reviews_plus_settings_defaults', $defaults );
    }


    /**
     * Returns sanitized saved settings merged with defaults.
     *
     * @return array
     */
    public function get_all() {
        $saved = get_option( self::OPTION_NAME, array() );

        return wp_parse_args( is_array( $saved ) ? $saved : array(), $this->get_defaults() );
    }


    /**
     * Returns a single setting value.
     *
     * @param string $key Setting key.
     * @param mixed  $default Optional default.
     * @return mixed
     */
    public function get_setting( $key, $default = null ) {
        $all = $this->get_all();

        if ( array_key_exists( $key, $all ) ) {
            return $all[ $key ];
        }

        return $default;
    }


    /**
     * Returns the field definitions for the admin settings UI.
     *
     * @return array
     */
    public function get_settings_fields() {
        $fields = array(
            array(
                'key'   => 'review_requests_enabled',
                'label' => __( 'Enable review requests', 'luma-reviews-plus' ),
                'type'  => 'checkbox',
            ),
            array(
                'key'   => 'review_email_delay_days',
                'label' => __( 'Days after completed order before review email is sent', 'luma-reviews-plus' ),
                'type'  => 'number',
                'min'   => 0,
                'max'   => 365,
            ),
            array(
                'key'   => 'token_expiry_days',
                'label' => __( 'Token expiry in days', 'luma-reviews-plus' ),
                'type'  => 'number',
                'min'   => 1,
                'max'   => 3650,
            ),
            array(
                'key'     => 'public_summary_style',
                'label'   => __( 'Public summary style', 'luma-reviews-plus' ),
                'type'    => 'select',
                'options' => $this->get_public_summary_style_options(),
            ),
            array(
                'key'   => 'review_page_slug',
                'label' => __( 'Review page slug', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'review_page_heading',
                'label' => __( 'Review page heading', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'review_page_intro',
                'label' => __( 'Review page introduction text', 'luma-reviews-plus' ),
                'type'  => 'richtext',
                'description' => __( 'Available placeholder: {first_name} (customer first name)', 'luma-reviews-plus' ),
            ),
            array(
                'key'   => 'product_reviews_heading',
                'label' => __( 'Product reviews section heading', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'product_reviews_intro',
                'label' => __( 'Product reviews section description', 'luma-reviews-plus' ),
                'type'  => 'richtext',
            ),
            array(
                'key'   => 'shop_review_heading',
                'label' => __( 'Shop-experience section heading', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'shop_review_intro',
                'label' => __( 'Shop-experience section description', 'luma-reviews-plus' ),
                'type'  => 'richtext',
            ),
            array(
                'key'         => 'shop_review_notifications_enabled',
                'label'       => __( 'Enable shop review notifications', 'luma-reviews-plus' ),
                'type'        => 'checkbox',
                'description' => __( 'Send an admin email when a new shop-experience review is submitted.', 'luma-reviews-plus' ),
            ),
            array(
                'key'         => 'shop_review_notification_email',
                'label'       => __( 'Shop review notification email', 'luma-reviews-plus' ),
                'type'        => 'email',
                'description' => __( 'New shop-experience reviews will be emailed to this address.', 'luma-reviews-plus' ),
            ),
            array(
                'key'         => 'show_admin_order_review_flag',
                'label'       => __( 'Show review flag on admin order pages', 'luma-reviews-plus' ),
                'type'        => 'checkbox',
                'description' => __( 'Show a WooCommerce admin order notice when the customer reviewed the previous order.', 'luma-reviews-plus' ),
            ),
            array(
                'key'   => 'shop_review_tags',
                'label' => __( 'Shop-experience tags', 'luma-reviews-plus' ),
                'type'  => 'textarea_array',
            ),
            array(
                'key'   => 'product_review_comment_required',
                'label' => __( 'Require product review comment', 'luma-reviews-plus' ),
                'type'  => 'checkbox',
            ),
            array(
                'key'         => 'auto_approve_product_reviews',
                'label'       => __( 'Auto-approve product reviews', 'luma-reviews-plus' ),
                'type'        => 'checkbox',
                'description' => __( 'Only applies to product reviews submitted through Reviews Plus from verified order review links. It does not change approval behavior for normal storefront product reviews.', 'luma-reviews-plus' ),
            ),
            array(
                'key'   => 'allow_shop_review_without_products',
                'label' => __( 'Allow shop-experience review without product review', 'luma-reviews-plus' ),
                'type'  => 'checkbox',
            ),
            array(
                'key'   => 'allow_partial_product_reviews',
                'label' => __( 'Allow partial product review submissions', 'luma-reviews-plus' ),
                'type'  => 'checkbox',
            ),
            array(
                'key'   => 'submit_button_text',
                'label' => __( 'Submit button text', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'success_message',
                'label' => __( 'Success message', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'expired_token_message',
                'label' => __( 'Expired token message', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'invalid_token_message',
                'label' => __( 'Invalid token message', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'already_reviewed_message',
                'label' => __( 'All items already reviewed message', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
            array(
                'key'   => 'public_consent_text',
                'label' => __( 'Public consent text', 'luma-reviews-plus' ),
                'type'  => 'text',
            ),
        );

        return apply_filters( 'luma_reviews_plus_settings_fields', $fields );
    }


    /**
     * Sanitizes grouped settings from the admin form.
     *
     * @param array $input Raw settings.
     * @return array
     */
    public function sanitize_settings( $input ) {
        $input    = is_array( $input ) ? $input : array();
        $defaults = $this->get_defaults();
        $clean    = $defaults;

        $clean['review_requests_enabled']             = Sanitizer::bool_to_int( $input['review_requests_enabled'] ?? 0 );
        $clean['review_email_delay_days']            = max( 0, Sanitizer::absint( $input['review_email_delay_days'] ?? $defaults['review_email_delay_days'] ) );
        $clean['token_expiry_days']                  = max( 1, Sanitizer::absint( $input['token_expiry_days'] ?? $defaults['token_expiry_days'] ) );
        $clean['public_summary_style']               = $this->sanitize_public_summary_style( $input['public_summary_style'] ?? $defaults['public_summary_style'] );
        $clean['review_page_slug']                   = Sanitizer::slug( $input['review_page_slug'] ?? $defaults['review_page_slug'] );
        $clean['review_page_heading']                = Sanitizer::text( $input['review_page_heading'] ?? $defaults['review_page_heading'] );
        $clean['review_page_intro']                  = Sanitizer::rich_text( $input['review_page_intro'] ?? $defaults['review_page_intro'] );
        $clean['product_reviews_heading']            = Sanitizer::text( $input['product_reviews_heading'] ?? $defaults['product_reviews_heading'] );
        $clean['product_reviews_intro']              = Sanitizer::rich_text( $input['product_reviews_intro'] ?? $defaults['product_reviews_intro'] );
        $clean['shop_review_heading']                = Sanitizer::text( $input['shop_review_heading'] ?? $defaults['shop_review_heading'] );
        $clean['shop_review_intro']                  = Sanitizer::rich_text( $input['shop_review_intro'] ?? $defaults['shop_review_intro'] );
        $clean['shop_review_notifications_enabled']  = Sanitizer::bool_to_int( $input['shop_review_notifications_enabled'] ?? 0 );
        $clean['shop_review_notification_email']     = $this->sanitize_notification_email( $input['shop_review_notification_email'] ?? $defaults['shop_review_notification_email'] );
        $clean['show_admin_order_review_flag']       = Sanitizer::bool_to_int( $input['show_admin_order_review_flag'] ?? 0 );
        $clean['shop_review_tags']                   = $this->sanitize_tags( $input['shop_review_tags'] ?? $defaults['shop_review_tags'] );
        $clean['product_review_comment_required']    = Sanitizer::bool_to_int( $input['product_review_comment_required'] ?? 0 );
        $clean['auto_approve_product_reviews']       = Sanitizer::bool_to_int( $input['auto_approve_product_reviews'] ?? 0 );
        $clean['allow_shop_review_without_products'] = Sanitizer::bool_to_int( $input['allow_shop_review_without_products'] ?? 0 );
        $clean['allow_partial_product_reviews']      = Sanitizer::bool_to_int( $input['allow_partial_product_reviews'] ?? 0 );
        $clean['submit_button_text']                 = Sanitizer::text( $input['submit_button_text'] ?? $defaults['submit_button_text'] );
        $clean['success_message']                    = Sanitizer::text( $input['success_message'] ?? $defaults['success_message'] );
        $clean['expired_token_message']              = Sanitizer::text( $input['expired_token_message'] ?? $defaults['expired_token_message'] );
        $clean['invalid_token_message']              = Sanitizer::text( $input['invalid_token_message'] ?? $defaults['invalid_token_message'] );
        $clean['already_reviewed_message']           = Sanitizer::text( $input['already_reviewed_message'] ?? $defaults['already_reviewed_message'] );
        $clean['public_consent_text']                = Sanitizer::text( $input['public_consent_text'] ?? $defaults['public_consent_text'] );

        return apply_filters( 'luma_reviews_plus_sanitized_settings', $clean, $input, $defaults );
    }


    /**
     * Returns whether review requests are enabled.
     *
     * @return bool
     */
    public function is_review_requests_enabled() {
        return (bool) $this->get_setting( 'review_requests_enabled', 1 );
    }


    /**
     * Returns the review email delay in days.
     *
     * @return int
     */
    public function get_review_email_delay_days() {
        return absint( $this->get_setting( 'review_email_delay_days', 5 ) );
    }


    /**
     * Returns the token expiry in days.
     *
     * @return int
     */
    public function get_token_expiry_days() {
        return (int) apply_filters( 'luma_reviews_plus_token_expiry_days', absint( $this->get_setting( 'token_expiry_days', 90 ) ) );
    }


    /**
     * Returns the default public summary style mode.
     *
     * @return string
     */
    public function get_public_summary_style() {
        return $this->sanitize_public_summary_style( $this->get_setting( 'public_summary_style', 'minimal' ) );
    }


    /**
     * Returns the review page slug.
     *
     * @return string
     */
    public function get_review_page_slug() {
        return (string) $this->get_setting( 'review_page_slug', 'review' );
    }


    /**
     * Returns the managed review page ID.
     *
     * @return int
     */
    public function get_review_page_id() {
        return absint( get_option( self::REVIEW_PAGE_ID_OPTION, 0 ) );
    }


    /**
     * Returns the review page heading.
     *
     * @return string
     */
    public function get_review_page_heading() {
        return (string) $this->get_setting( 'review_page_heading', '' );
    }


    /**
     * Returns the review page intro text.
     *
     * @return string
     */
    public function get_review_page_intro() {
        return (string) $this->get_setting( 'review_page_intro', '' );
    }


    /**
     * Returns the product review heading.
     *
     * @return string
     */
    public function get_product_reviews_heading() {
        return (string) $this->get_setting( 'product_reviews_heading', '' );
    }


    /**
     * Returns the follow-up heading shown after a partial submission.
     *
     * @return string
     */
    public function get_follow_up_product_reviews_heading() {
        return __( 'It is not too late to review these products as well.', 'luma-reviews-plus' );
    }


    /**
     * Returns the product review intro text.
     *
     * @return string
     */
    public function get_product_reviews_intro() {
        return (string) $this->get_setting( 'product_reviews_intro', '' );
    }


    /**
     * Returns the shop review heading.
     *
     * @return string
     */
    public function get_shop_review_heading() {
        return (string) $this->get_setting( 'shop_review_heading', '' );
    }


    /**
     * Returns the shop review intro text.
     *
     * @return string
     */
    public function get_shop_review_intro() {
        return (string) $this->get_setting( 'shop_review_intro', '' );
    }


    /**
     * Returns whether shop review admin notifications are enabled.
     *
     * @return bool
     */
    public function are_shop_review_notifications_enabled() {
        return (bool) $this->get_setting( 'shop_review_notifications_enabled', 1 );
    }


    /**
     * Returns the recipient for shop review notifications.
     *
     * @return string
     */
    public function get_shop_review_notification_email() {
        return $this->sanitize_notification_email( $this->get_setting( 'shop_review_notification_email', '' ) );
    }


    /**
     * Returns whether the admin order review flag should be shown.
     *
     * @return bool
     */
    public function should_show_admin_order_review_flag() {
        return (bool) $this->get_setting( 'show_admin_order_review_flag', 0 );
    }


    /**
     * Returns whether product comments are required.
     *
     * @return bool
     */
    public function is_product_review_comment_required() {
        return (bool) apply_filters( 'luma_reviews_plus_product_review_comment_required', (bool) $this->get_setting( 'product_review_comment_required', 0 ) );
    }


    /**
     * Returns whether product reviews should auto-approve.
     *
     * @return bool
     */
    public function should_auto_approve_product_reviews() {
        return (bool) $this->get_setting( 'auto_approve_product_reviews', 0 );
    }


    /**
     * Returns whether shop reviews can be submitted without product reviews.
     *
     * @return bool
     */
    public function allow_shop_review_without_products() {
        return (bool) $this->get_setting( 'allow_shop_review_without_products', 1 );
    }


    /**
     * Returns whether partial product review submissions are allowed.
     *
     * @return bool
     */
    public function allow_partial_product_reviews() {
        return (bool) $this->get_setting( 'allow_partial_product_reviews', 1 );
    }


    /**
     * Returns the available public summary style options.
     *
     * @return array
     */
    protected function get_public_summary_style_options() {
        return array(
            'none'    => __( 'None', 'luma-reviews-plus' ),
            'minimal' => __( 'Minimal', 'luma-reviews-plus' ),
        );
    }


    /**
     * Sanitizes the public summary style mode.
     *
     * @param mixed $style Raw style mode.
     * @return string
     */
    protected function sanitize_public_summary_style( $style ) {
        $style = Sanitizer::text( $style );

        if ( ! array_key_exists( $style, $this->get_public_summary_style_options() ) ) {
            return 'minimal';
        }

        return $style;
    }


    /**
     * Sanitizes the shop review notification email address.
     *
     * @param mixed $email Raw email address.
     * @return string
     */
    protected function sanitize_notification_email( $email ) {
        $email = Sanitizer::email( $email );

        if ( '' === $email || ! \is_email( $email ) ) {
            $email = Sanitizer::email( \get_option( 'admin_email', '' ) );
        }

        return \is_email( $email ) ? $email : '';
    }


    /**
     * Returns allowed shop review tags.
     *
     * @return array
     */
    public function get_shop_review_tags() {
        return apply_filters( 'luma_reviews_plus_shop_review_tags', (array) $this->get_setting( 'shop_review_tags', array() ) );
    }


    /**
     * Returns the submit button text.
     *
     * @return string
     */
    public function get_submit_button_text() {
        return (string) $this->get_setting( 'submit_button_text', '' );
    }


    /**
     * Returns the success message.
     *
     * @return string
     */
    public function get_success_message() {
        return (string) $this->get_setting( 'success_message', '' );
    }


    /**
     * Returns the expired token message.
     *
     * @return string
     */
    public function get_expired_token_message() {
        return (string) $this->get_setting( 'expired_token_message', '' );
    }


    /**
     * Returns the invalid token message.
     *
     * @return string
     */
    public function get_invalid_token_message() {
        return (string) $this->get_setting( 'invalid_token_message', '' );
    }


    /**
     * Returns the already-reviewed message.
     *
     * @return string
     */
    public function get_already_reviewed_message() {
        return (string) $this->get_setting( 'already_reviewed_message', '' );
    }


    /**
     * Returns the public consent text.
     *
     * @return string
     */
    public function get_public_consent_text() {
        return (string) $this->get_setting( 'public_consent_text', $this->get_default_public_consent_text() );
    }


    /**
     * Returns the default public consent text.
     *
     * @return string
     */
    protected function get_default_public_consent_text() {
        return __( 'Fru Kvist may show my comment on the website with the display name I provide here.', 'luma-reviews-plus' );
    }


    /**
     * Sanitizes tags from textarea or array input.
     *
     * @param mixed $tags Raw tags.
     * @return array
     */
    protected function sanitize_tags( $tags ) {
        if ( is_string( $tags ) ) {
            $tags = preg_split( '/\r\n|\r|\n/', $tags );
        }

        return Sanitizer::string_array( $tags );
    }
}
