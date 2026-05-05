<?php

namespace Luma\ReviewsPlus\Email;

use Luma\ReviewsPlus\Settings\Settings;

/**
 * Builds review-page URLs.
 *
 * Responsibilities:
 * - Create tokenized public review-page URLs.
 * - Respect the configured review page slug.
 * - Expose a filter for site-specific URL customization.
 */
class ReviewLinkGenerator {

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Creates the URL generator.
     *
     * @param Settings $settings Settings service.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }


    /**
     * Builds a tokenized review-page URL.
     *
     * @param string         $raw_token Raw token.
     * @param \WC_Order|null $order Order context.
     * @return string
     */
    public function get_review_page_url( $raw_token, $order = null ) {
        $base_url = home_url( '/' . trim( $this->settings->get_review_page_slug(), '/' ) . '/' );
        $url      = add_query_arg( 'token', rawurlencode( (string) $raw_token ), $base_url );

        return (string) apply_filters( 'luma_reviews_plus_review_page_url', $url, $raw_token, $order );
    }
}
