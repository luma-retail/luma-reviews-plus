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
     * Returns all default settings.
     *
     * @return array
     */
    public function get_defaults() {
        return array(
            'review_requests_enabled'             => 1,
            'review_email_delay_days'            => 5,
            'token_expiry_days'                  => 90,
            'review_page_slug'                   => 'vurdering',
            'review_page_heading'                => __( 'Del din vurdering', 'luma-reviews-plus' ),
            'review_page_intro'                  => __( 'Takk for kjopet. Vi vil gjerne hore hvordan du opplevde produktene og handelen.', 'luma-reviews-plus' ),
            'product_reviews_heading'            => __( 'Hvordan var produktene du kjopte?', 'luma-reviews-plus' ),
            'product_reviews_intro'              => __( 'Du kan vurdere ett eller flere produkter fra denne bestillingen.', 'luma-reviews-plus' ),
            'shop_review_heading'                => __( 'Hvordan var handleopplevelsen hos Fru Kvist?', 'luma-reviews-plus' ),
            'shop_review_intro'                  => __( 'Del gjerne hvordan du opplevde nettbutikken, levering og service.', 'luma-reviews-plus' ),
            'shop_review_tags'                   => array(
                __( 'Rask levering', 'luma-reviews-plus' ),
                __( 'Pent pakket', 'luma-reviews-plus' ),
                __( 'Riktig lagerstatus', 'luma-reviews-plus' ),
                __( 'God kundeservice', 'luma-reviews-plus' ),
                __( 'God hjelp/radgivning', 'luma-reviews-plus' ),
                __( 'Lett a handle', 'luma-reviews-plus' ),
                __( 'Annet', 'luma-reviews-plus' ),
            ),
            'product_review_comment_required'    => 0,
            'auto_approve_product_reviews'       => 0,
            'allow_shop_review_without_products' => 1,
            'allow_partial_product_reviews'      => 1,
            'public_display_name_mode'           => 'first_name_only',
            'submit_button_text'                 => __( 'Send vurderinger', 'luma-reviews-plus' ),
            'success_message'                    => __( 'Takk. Vurderingen din er registrert.', 'luma-reviews-plus' ),
            'expired_token_message'              => __( 'Denne vurderingslenken har utlopet.', 'luma-reviews-plus' ),
            'invalid_token_message'              => __( 'Denne vurderingslenken er ugyldig.', 'luma-reviews-plus' ),
            'already_reviewed_message'           => __( 'Alle produktene i denne bestillingen er allerede vurdert.', 'luma-reviews-plus' ),
            'public_consent_text'                => __( 'Fru Kvist kan vise kommentaren min pa nettsiden. Den vises kun med fornavn og eventuelt sted.', 'luma-reviews-plus' ),
        );
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
                'key'   => 'auto_approve_product_reviews',
                'label' => __( 'Auto-approve product reviews', 'luma-reviews-plus' ),
                'type'  => 'checkbox',
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
                'key'     => 'public_display_name_mode',
                'label'   => __( 'Public display name mode', 'luma-reviews-plus' ),
                'type'    => 'select',
                'options' => array(
                    'first_name_only' => __( 'First name only', 'luma-reviews-plus' ),
                    'full_name'       => __( 'Full name', 'luma-reviews-plus' ),
                ),
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
        $clean['review_page_slug']                   = Sanitizer::slug( $input['review_page_slug'] ?? $defaults['review_page_slug'] );
        $clean['review_page_heading']                = Sanitizer::text( $input['review_page_heading'] ?? $defaults['review_page_heading'] );
        $clean['review_page_intro']                  = Sanitizer::rich_text( $input['review_page_intro'] ?? $defaults['review_page_intro'] );
        $clean['product_reviews_heading']            = Sanitizer::text( $input['product_reviews_heading'] ?? $defaults['product_reviews_heading'] );
        $clean['product_reviews_intro']              = Sanitizer::rich_text( $input['product_reviews_intro'] ?? $defaults['product_reviews_intro'] );
        $clean['shop_review_heading']                = Sanitizer::text( $input['shop_review_heading'] ?? $defaults['shop_review_heading'] );
        $clean['shop_review_intro']                  = Sanitizer::rich_text( $input['shop_review_intro'] ?? $defaults['shop_review_intro'] );
        $clean['shop_review_tags']                   = $this->sanitize_tags( $input['shop_review_tags'] ?? $defaults['shop_review_tags'] );
        $clean['product_review_comment_required']    = Sanitizer::bool_to_int( $input['product_review_comment_required'] ?? 0 );
        $clean['auto_approve_product_reviews']       = Sanitizer::bool_to_int( $input['auto_approve_product_reviews'] ?? 0 );
        $clean['allow_shop_review_without_products'] = Sanitizer::bool_to_int( $input['allow_shop_review_without_products'] ?? 0 );
        $clean['allow_partial_product_reviews']      = Sanitizer::bool_to_int( $input['allow_partial_product_reviews'] ?? 0 );
        $clean['public_display_name_mode']           = in_array( $input['public_display_name_mode'] ?? '', array( 'first_name_only', 'full_name' ), true ) ? $input['public_display_name_mode'] : $defaults['public_display_name_mode'];
        $clean['submit_button_text']                 = Sanitizer::text( $input['submit_button_text'] ?? $defaults['submit_button_text'] );
        $clean['success_message']                    = Sanitizer::text( $input['success_message'] ?? $defaults['success_message'] );
        $clean['expired_token_message']              = Sanitizer::text( $input['expired_token_message'] ?? $defaults['expired_token_message'] );
        $clean['invalid_token_message']              = Sanitizer::text( $input['invalid_token_message'] ?? $defaults['invalid_token_message'] );
        $clean['already_reviewed_message']           = Sanitizer::text( $input['already_reviewed_message'] ?? $defaults['already_reviewed_message'] );
        $clean['public_consent_text']                = Sanitizer::text( $input['public_consent_text'] ?? $defaults['public_consent_text'] );

        return $clean;
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
     * Returns the review page slug.
     *
     * @return string
     */
    public function get_review_page_slug() {
        return (string) $this->get_setting( 'review_page_slug', 'vurdering' );
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
     * Returns the public display name mode.
     *
     * @return string
     */
    public function get_public_display_name_mode() {
        return (string) $this->get_setting( 'public_display_name_mode', 'first_name_only' );
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
        return (string) $this->get_setting( 'public_consent_text', '' );
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
