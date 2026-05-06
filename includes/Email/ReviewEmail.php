<?php

namespace Luma\ReviewsPlus\Email;

use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Sends review request emails.
 *
 * Responsibilities:
 * - Register a WooCommerce customer email type for review requests.
 * - Provide editable subject, heading, and body settings.
 * - Inject order-specific review placeholders into email content.
 * - Render HTML and plain-text email templates.
 */
class ReviewEmail extends \WC_Email {

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings_service;


    /**
     * Link generator.
     *
     * @var ReviewLinkGenerator
     */
    protected $link_generator;


    /**
     * Review link for the current send.
     *
     * @var string
     */
    protected $review_link = '';


    /**
     * Creates the email instance.
     *
     * @param Settings            $settings Settings service.
     * @param ReviewLinkGenerator $link_generator Link generator.
     */
    public function __construct( Settings $settings, ReviewLinkGenerator $link_generator ) {
        $this->settings_service = $settings;
        $this->link_generator   = $link_generator;

        $this->id             = 'luma_reviews_plus_review_request';
        $this->title          = __( 'Review request', 'luma-reviews-plus' );
        $this->description    = __( 'Sent to customers after an eligible order to request product and shop reviews.', 'luma-reviews-plus' );
        $this->customer_email = true;
        $this->template_html  = 'emails/review-request.php';
        $this->template_plain = 'emails/plain/review-request.php';
        $this->template_base  = LUMA_REVIEWS_PLUS_PATH . 'templates/';
        $this->placeholders   = array(
            '{first_name}'   => '',
            '{order_number}' => '',
            '{review_link}'  => '',
            '{review_button}' => '',
            '{site_title}'   => $this->get_blogname(),
        );

        parent::__construct();

        $this->recipient = '';
        add_action( 'woocommerce_update_options_email_' . $this->id, array( $this, 'process_admin_options' ) );
    }


    /**
     * Initializes email settings fields.
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'luma-reviews-plus' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'luma-reviews-plus' ),
                'default' => 'yes',
            ),
            'subject' => array(
                'title'       => __( 'Subject', 'luma-reviews-plus' ),
                'type'        => 'text',
                'description' => __( 'Available placeholders: {first_name}, {order_number}, {review_link}, {site_title}.', 'luma-reviews-plus' ),
                'placeholder' => $this->get_default_subject(),
                'default'     => $this->get_default_subject(),
            ),
            'heading' => array(
                'title'       => __( 'Email heading', 'luma-reviews-plus' ),
                'type'        => 'text',
                'description' => __( 'Displayed in the email heading area.', 'luma-reviews-plus' ),
                'placeholder' => $this->get_default_heading(),
                'default'     => $this->get_default_heading(),
            ),
            'body_text' => array(
                'title'       => __( 'Body text', 'luma-reviews-plus' ),
                'type'        => 'textarea',
                'description' => __( 'Available placeholders: {first_name}, {order_number}, {review_link}, {review_button}, {site_title}.', 'luma-reviews-plus' ),
                'default'     => $this->get_default_body_text(),
            ),
            'email_type' => array(
                'title'       => __( 'Email type', 'luma-reviews-plus' ),
                'type'        => 'select',
                'description' => __( 'Choose the email format.', 'luma-reviews-plus' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
            ),
        );
    }


    /**
     * Triggers an email send.
     *
     * @param int    $order_id Order ID.
     * @param string $review_link Review link.
     * @return void
     */
    public function trigger( $order_id, $review_link = '' ) {
        if ( ! $order_id ) {
            return;
        }

        $this->object     = wc_get_order( $order_id );
        $this->review_link = (string) $review_link;

        if ( ! $this->object ) {
            return;
        }

        $this->recipient                  = Helpers::get_order_customer_email( $this->object );
        $this->placeholders['{first_name}']    = Helpers::get_order_first_name( $this->object );
        $this->placeholders['{order_number}']  = $this->object->get_order_number();
        $this->placeholders['{review_link}']   = $this->review_link;
        $this->placeholders['{review_button}'] = $this->get_review_button_markup();

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }


    /**
     * Returns the default subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Hvordan var bestilling #{order_number}?', 'luma-reviews-plus' );
    }


    /**
     * Returns the default heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'We would love to hear what you think', 'luma-reviews-plus' );
    }


    /**
     * Returns the default body copy.
     *
     * @return string
     */
    public function get_default_body_text() {
        return __( 'Hi {first_name},\n\nThank you for your purchase from {site_title}. Feel free to share your reviews here: {review_link}', 'luma-reviews-plus' );
    }


    /**
     * Returns the configured body text.
     *
     * @return string
     */
    public function get_body_text() {
        return $this->format_string( $this->get_option( 'body_text', $this->get_default_body_text() ) );
    }


    /**
     * Returns the configured body text before placeholder formatting.
     *
     * @return string
     */
    protected function get_body_text_template() {
        return (string) $this->get_option( 'body_text', $this->get_default_body_text() );
    }


    /**
     * Returns the HTML email content.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'          => $this->object,
                'email_heading'  => $this->get_heading(),
                'body_text'      => nl2br( wp_kses_post( $this->get_body_text() ) ),
                'review_link'    => $this->review_link,
                'review_button'  => $this->get_review_button_markup(),
                'append_review_button' => $this->should_append_review_button(),
                'append_review_link'   => $this->should_append_review_link(),
                'review_link_fallback_text' => $this->get_review_link_fallback_text(),
                'sent_to_admin'  => false,
                'plain_text'     => false,
                'email'          => $this,
            ),
            '',
            $this->template_base
        );
    }


    /**
     * Returns the plain-text email content.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'body_text'     => wp_strip_all_tags( $this->get_body_text() ),
                'review_link'   => $this->review_link,
                'append_review_link' => $this->should_append_review_link(),
                'review_link_fallback_text' => $this->get_review_link_fallback_text(),
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            ),
            '',
            $this->template_base
        );
    }


    /**
     * Returns whether the HTML email should append the review button.
     *
     * @return bool
     */
    protected function should_append_review_button() {
        return false === strpos( $this->get_body_text_template(), '{review_button}' );
    }


    /**
     * Returns whether the email should append the raw review link fallback.
     *
     * @return bool
     */
    protected function should_append_review_link() {
        return false === strpos( $this->get_body_text_template(), '{review_link}' );
    }


    /**
     * Returns the fallback text shown before the raw review link.
     *
     * @return string
     */
    protected function get_review_link_fallback_text() {
        return __( 'You can use this link if the button does not work:', 'luma-reviews-plus' );
    }


    /**
     * Returns the HTML review button markup.
     *
     * @return string
     */
    protected function get_review_button_markup() {
        if ( 'plain' === $this->get_email_type() ) {
            return $this->review_link;
        }

        return '<a href="' . esc_url( $this->review_link ) . '" style="display:inline-block;padding:12px 18px;background:#406144;color:#ffffff;text-decoration:none;border-radius:4px;">' . esc_html__( 'Leave your review', 'luma-reviews-plus' ) . '</a>';
    }
}
