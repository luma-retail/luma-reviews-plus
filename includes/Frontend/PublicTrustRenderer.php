<?php

namespace Luma\ReviewsPlus\Frontend;

use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Settings\Settings;

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
     * Shortcode tag.
     *
     * @var string
     */
    const SHORTCODE = 'luma_shop_reviews_summary';

    /**
     * Summary style handle.
     *
     * @var string
     */
    const STYLE_HANDLE = 'luma-reviews-plus-public-summary-minimal';

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
     * Creates the renderer.
     *
     * @param ShopReviewRepository $shop_review_repository Repository.
     * @param Settings             $settings Settings service.
     */
    public function __construct( ShopReviewRepository $shop_review_repository, Settings $settings ) {
        $this->shop_review_repository = $shop_review_repository;
        $this->settings               = $settings;
    }


    /**
     * Registers the shortcode.
     *
     * @return void
     */
    public function register() {
        \add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
        \add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );
    }


    /**
     * Enqueues summary assets when the resolved shortcode mode requires them.
     *
     * @return void
     */
    public function maybe_enqueue_assets() {
        foreach ( $this->get_candidate_post_ids() as $post_id ) {
            if ( $this->post_requires_summary_style( $post_id ) ) {
                $this->enqueue_style( 'minimal' );
                return;
            }
        }
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
                'style'         => 'inherit',
            ),
            $atts,
            self::SHORTCODE
        );

        $resolved_style = $this->resolve_style_mode( $atts['style'] );

        if ( 'none' !== $resolved_style ) {
            $this->enqueue_style( $resolved_style );
        }

        $data = $this->shop_review_repository->get_summary_data( \absint( $atts['quote_count'] ), \absint( $atts['minimum_rating'] ) );
        $data = \apply_filters( 'luma_reviews_plus_public_summary_data', $data );

        if ( empty( $data['review_count'] ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="luma-shop-reviews-summary">
            <div class="luma-shop-reviews-summary__body">
                <?php if ( 'yes' === $atts['show_rating'] && 'yes' === $atts['show_count'] ) : ?>
                    <p class="luma-shop-reviews-summary__rating luma-shop-reviews-summary__summary-line">
                        <?php echo esc_html( sprintf( __( 'Our customers give us %1$s out of 5 stars based on %2$d customer reviews after purchase.', 'luma-reviews-plus' ), number_format_i18n( $data['average_rating'], 1 ), \absint( $data['review_count'] ) ) ); ?>
                    </p>
                <?php else : ?>
                    <?php if ( 'yes' === $atts['show_rating'] ) : ?>
                        <p class="luma-shop-reviews-summary__rating">
                            <?php echo esc_html( sprintf( __( 'Our customers give us %1$s out of 5 stars.', 'luma-reviews-plus' ), number_format_i18n( $data['average_rating'], 1 ) ) ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( 'yes' === $atts['show_count'] ) : ?>
                        <p class="luma-shop-reviews-summary__count">
                            <?php echo esc_html( sprintf( __( 'Based on %d customer reviews after purchase.', 'luma-reviews-plus' ), \absint( $data['review_count'] ) ) ); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ( 'yes' === $atts['show_quotes'] && ! empty( $data['quotes'] ) ) : ?>
                    <div class="luma-shop-reviews-summary__quotes">
                        <?php foreach ( $data['quotes'] as $quote ) : ?>
                            <div class="luma-reviews-plus-card">
                                <blockquote class="luma-shop-reviews-summary__quote">
                                    <?php echo $this->get_quote_rating_html( $quote->rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <p><?php echo esc_html( $quote->comment ); ?></p>
                                    <footer>
                                        <cite class="luma-shop-reviews-summary__cite"><?php echo esc_html( $quote->display_name ); ?><?php if ( ! empty( $quote->display_location ) ) : ?><span>, <?php echo esc_html( $quote->display_location ); ?></span><?php endif; ?></cite>
                                        <?php if ( ! empty( $quote->created_at ) ) : ?>
                                            <time class="luma-shop-reviews-summary__date" datetime="<?php echo esc_attr( gmdate( 'c', (int) mysql2date( 'U', $quote->created_at, false ) ) ); ?>"><?php echo esc_html( $this->get_human_quote_date( $quote->created_at ) ); ?></time>
                                        <?php endif; ?>
                                    </footer>
                                </blockquote>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }


    /**
     * Returns rating markup for a public quote.
     *
     * @param mixed $rating Quote rating.
     * @return string
     */
    protected function get_quote_rating_html( $rating ) {
        $rating = max( 0, min( 5, (int) round( (float) $rating ) ) );

        if ( $rating <= 0 ) {
            return '';
        }

        return sprintf(
            '<div class="luma-shop-reviews-summary__stars" role="img" aria-label="%1$s"><span aria-hidden="true">%2$s</span></div>',
            esc_attr( sprintf( __( 'Rated %s out of 5', 'luma-reviews-plus' ), number_format_i18n( $rating, 1 ) ) ),
            esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) )
        );
    }


    /**
     * Returns a human-readable relative date label for a quote.
     *
     * @param string $created_at MySQL datetime string.
     * @return string
     */
    protected function get_human_quote_date( $created_at ) {
        $timestamp = (int) \mysql2date( 'U', (string) $created_at, false );

        if ( $timestamp <= 0 ) {
            return '';
        }

        $now            = (int) \current_time( 'timestamp' );
        $timestamp      = min( $timestamp, $now );
        $today_start    = (int) \strtotime( 'today', $now );
        $review_start   = (int) \strtotime( 'today', $timestamp );
        $days_ago       = (int) floor( ( $today_start - $review_start ) / DAY_IN_SECONDS );

        if ( $days_ago <= 0 ) {
            return __( 'Today', 'luma-reviews-plus' );
        }

        if ( 1 === $days_ago ) {
            return __( 'Yesterday', 'luma-reviews-plus' );
        }

        if ( $days_ago < 7 ) {
            return sprintf(
                _n( '%s day ago', '%s days ago', $days_ago, 'luma-reviews-plus' ),
                number_format_i18n( $days_ago )
            );
        }

        $weeks_ago = (int) floor( $days_ago / 7 );

        if ( 1 === $weeks_ago ) {
            return __( 'Last week', 'luma-reviews-plus' );
        }

        if ( 2 === $weeks_ago ) {
            return __( 'Two weeks ago', 'luma-reviews-plus' );
        }

        return sprintf(
            _n( '%s week ago', '%s weeks ago', $weeks_ago, 'luma-reviews-plus' ),
            number_format_i18n( $weeks_ago )
        );
    }


    /**
     * Returns the candidate post IDs that may contain the shortcode.
     *
     * @return array
     */
    protected function get_candidate_post_ids() {
        if ( \is_admin() ) {
            return array();
        }

        if ( ! \is_singular() && ! \is_front_page() && ! \is_home() ) {
            return array();
        }

        $post_ids = array(
            \get_queried_object_id(),
        );

        if ( \is_front_page() ) {
            $post_ids[] = (int) \get_option( 'page_on_front', 0 );
        }

        if ( \is_home() ) {
            $post_ids[] = (int) \get_option( 'page_for_posts', 0 );
        }

        return array_values( array_filter( array_unique( array_map( 'absint', $post_ids ) ) ) );
    }


    /**
     * Returns whether a post contains a shortcode instance that needs CSS.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    protected function post_requires_summary_style( $post_id ) {
        $post = \get_post( \absint( $post_id ) );

        if ( ! $post instanceof \WP_Post || '' === (string) $post->post_content ) {
            return false;
        }

        if ( ! \has_shortcode( $post->post_content, self::SHORTCODE ) ) {
            return false;
        }

        return $this->content_requires_summary_style( $post->post_content );
    }


    /**
     * Returns whether shortcode markup in a content string resolves to CSS.
     *
     * @param string $content Post content.
     * @return bool
     */
    protected function content_requires_summary_style( $content ) {
        $pattern = \get_shortcode_regex( array( self::SHORTCODE ) );

        if ( ! \preg_match_all( '/' . $pattern . '/', (string) $content, $matches, PREG_SET_ORDER ) ) {
            return false;
        }

        foreach ( $matches as $shortcode_match ) {
            if ( self::SHORTCODE !== ( $shortcode_match[2] ?? '' ) ) {
                continue;
            }

            $atts = \shortcode_parse_atts( $shortcode_match[3] ?? '' );
            $style = $this->resolve_style_mode( is_array( $atts ) ? ( $atts['style'] ?? 'inherit' ) : 'inherit' );

            if ( 'none' !== $style ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Resolves the effective style mode.
     *
     * @param mixed $style Requested style mode.
     * @return string
     */
    protected function resolve_style_mode( $style ) {
        $style = \sanitize_key( (string) $style );

        if ( 'inherit' === $style || '' === $style ) {
            $style = $this->settings->get_public_summary_style();
        }

        return in_array( $style, array( 'none', 'minimal' ), true ) ? $style : $this->settings->get_public_summary_style();
    }


    /**
     * Enqueues the stylesheet for a resolved style mode.
     *
     * @param string $style Resolved style mode.
     * @return void
     */
    protected function enqueue_style( $style ) {
        if ( 'minimal' !== $style ) {
            return;
        }

        if ( ! \wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
            return;
        }

        \wp_enqueue_style( self::STYLE_HANDLE );
    }
}
