<?php

namespace Luma\ReviewsPlus\Frontend;

use Luma\ReviewsPlus\Database\ShopReviewRepository;

/**
 * Renders public trust-summary content.
 *
 * Responsibilities:
 * - Register the shop-review summary shortcode.
 * - Format aggregate rating and quote data for frontend output.
 * - Expose public summary data to filters before rendering.
 */
class PublicTrustRenderer {

    /**
     * Shop review repository.
     *
     * @var ShopReviewRepository
     */
    protected $shop_review_repository;


    /**
     * Creates the renderer.
     *
     * @param ShopReviewRepository $shop_review_repository Repository.
     */
    public function __construct( ShopReviewRepository $shop_review_repository ) {
        $this->shop_review_repository = $shop_review_repository;
    }


    /**
     * Registers the shortcode.
     *
     * @return void
     */
    public function register() {
        \add_shortcode( 'luma_shop_reviews_summary', array( $this, 'render_shortcode' ) );
    }


    /**
     * Renders the summary shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_shortcode( $atts ) {
        $atts = \shortcode_atts(
            array(
                'show_rating'   => 'yes',
                'show_count'    => 'yes',
                'show_quotes'   => 'yes',
                'quote_count'   => 3,
                'minimum_rating' => 4,
            ),
            $atts,
            'luma_shop_reviews_summary'
        );

        $data = $this->shop_review_repository->get_summary_data( \absint( $atts['quote_count'] ), \absint( $atts['minimum_rating'] ) );
        $data = \apply_filters( 'luma_reviews_plus_public_summary_data', $data );

        if ( empty( $data['review_count'] ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="luma-shop-reviews-summary">
            <h2><?php echo esc_html( sprintf( __( 'Trygg netthandel hos %s', 'luma-reviews-plus' ), get_bloginfo( 'name' ) ) ); ?></h2>
            <?php if ( 'yes' === $atts['show_rating'] ) : ?>
                <p class="luma-shop-reviews-summary__rating">
                    <?php echo esc_html( sprintf( __( 'Kundene vurderer handleopplevelsen til %1$s / 5.', 'luma-reviews-plus' ), number_format_i18n( $data['average_rating'], 1 ) ) ); ?>
                </p>
            <?php endif; ?>
            <?php if ( 'yes' === $atts['show_count'] ) : ?>
                <p class="luma-shop-reviews-summary__count">
                    <?php echo esc_html( sprintf( __( 'Basert pa %d bekreftede kjopsopplevelser.', 'luma-reviews-plus' ), \absint( $data['review_count'] ) ) ); ?>
                </p>
            <?php endif; ?>
            <?php if ( 'yes' === $atts['show_quotes'] && ! empty( $data['quotes'] ) ) : ?>
                <div class="luma-shop-reviews-summary__quotes">
                    <?php foreach ( $data['quotes'] as $quote ) : ?>
                        <blockquote class="luma-shop-reviews-summary__quote">
                            <p><?php echo esc_html( $quote->comment ); ?></p>
                            <footer>
                                <?php echo esc_html( $quote->display_name ); ?>
                                <?php if ( ! empty( $quote->display_location ) ) : ?>
                                    <span><?php echo esc_html( $quote->display_location ); ?></span>
                                <?php endif; ?>
                            </footer>
                        </blockquote>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
