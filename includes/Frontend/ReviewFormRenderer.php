<?php

namespace Luma\ReviewsPlus\Frontend;

use Luma\ReviewsPlus\Settings\Settings;

/**
 * Renders the public review page.
 *
 * Responsibilities:
 * - Render the main review page template.
 * - Render partial templates for product and shop review sections.
 * - Keep view logic out of the controller.
 */
class ReviewFormRenderer {

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Creates the renderer.
     *
     * @param Settings $settings Settings service.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }


    /**
     * Renders the review page.
     *
     * @param array $context View context.
     * @return string
     */
    public function render_page( array $context ) {
        ob_start();
        include LUMA_REVIEWS_PLUS_PATH . 'templates/review-page.php';

        return (string) ob_get_clean();
    }


    /**
     * Renders a product review item partial.
     *
     * @param array $item Item data.
     * @param array $values Posted or default field values.
     * @return void
     */
    public function render_product_review_item( array $item, array $values ) {
        include LUMA_REVIEWS_PLUS_PATH . 'templates/product-review-item.php';
    }


    /**
     * Renders the shop review partial.
     *
     * @param array $context View context.
     * @return void
     */
    public function render_shop_review_section( array $context ) {
        include LUMA_REVIEWS_PLUS_PATH . 'templates/shop-experience-review.php';
    }


    /**
     * Replaces supported placeholders in public-facing page copy.
     *
     * @param string $text Source text.
     * @param array  $context View context.
     * @return string
     */
    public function replace_page_placeholders( $text, array $context ) {
        $order = $context['order'] ?? null;

        $replacements = array(
            '{first_name}' => $order instanceof \WC_Order ? $order->get_billing_first_name() : '',
        );

        return strtr( (string) $text, $replacements );
    }
}
