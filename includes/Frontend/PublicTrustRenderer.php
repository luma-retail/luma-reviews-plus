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
     * Frontend script handle.
     *
     * @var string
     */
    const SCRIPT_HANDLE = 'luma-reviews-plus-frontend';

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
        \add_action( 'wp_ajax_luma_reviews_plus_load_shop_quotes', array( $this, 'handle_load_more_ajax' ) );
        \add_action( 'wp_ajax_nopriv_luma_reviews_plus_load_shop_quotes', array( $this, 'handle_load_more_ajax' ) );
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
                'featured_only' => 'no',
                'show_more'     => 'yes',
                'load_more_count' => 0,
                'style'         => 'inherit',
            ),
            $atts,
            self::SHORTCODE
        );

        $resolved_style = $this->resolve_style_mode( $atts['style'] );

        if ( 'none' !== $resolved_style ) {
            $this->enqueue_style( $resolved_style );
        }

        $quote_count     = max( 1, \absint( $atts['quote_count'] ) );
        $minimum_rating  = max( 1, min( 5, \absint( $atts['minimum_rating'] ) ) );
        $featured_only   = $this->is_yes_value( $atts['featured_only'] );
        $show_quotes     = $this->is_yes_value( $atts['show_quotes'] );
        $show_more       = $this->is_yes_value( $atts['show_more'] );
        $load_more_count = max( 1, \absint( $atts['load_more_count'] ) );

        if ( 0 === \absint( $atts['load_more_count'] ) ) {
            $load_more_count = $quote_count;
        }

        $data = $this->shop_review_repository->get_summary_data( $quote_count, $minimum_rating, $featured_only );
        $data = \apply_filters( 'luma_reviews_plus_public_summary_data', $data );

        $loaded_quotes = is_array( $data['quotes'] ?? null ) ? count( $data['quotes'] ) : 0;
        $total_quotes  = $show_quotes ? $this->shop_review_repository->count_public_quotes( $minimum_rating, $featured_only ) : 0;
        $has_more      = $show_quotes && $show_more && $loaded_quotes < $total_quotes;

        if ( empty( $data['review_count'] ) ) {
            return '';
        }

        if ( $show_quotes && $has_more ) {
            $this->enqueue_script();
        }

        ob_start();
        ?>
        <div class="luma-shop-reviews-summary">
            <div class="luma-shop-reviews-summary__body">
                <?php if ( $this->is_yes_value( $atts['show_rating'] ) && $this->is_yes_value( $atts['show_count'] ) ) : ?>
                    <p class="luma-shop-reviews-summary__rating luma-shop-reviews-summary__summary-line">
                        <?php echo esc_html( sprintf( __( 'Our customers give us %1$s out of 5 stars based on %2$d customer reviews after purchase.', 'luma-reviews-plus' ), number_format_i18n( $data['average_rating'], 1 ), \absint( $data['review_count'] ) ) ); ?>
                    </p>
                <?php else : ?>
                    <?php if ( $this->is_yes_value( $atts['show_rating'] ) ) : ?>
                        <p class="luma-shop-reviews-summary__rating">
                            <?php echo esc_html( sprintf( __( 'Our customers give us %1$s out of 5 stars.', 'luma-reviews-plus' ), number_format_i18n( $data['average_rating'], 1 ) ) ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $this->is_yes_value( $atts['show_count'] ) ) : ?>
                        <p class="luma-shop-reviews-summary__count">
                            <?php echo esc_html( sprintf( __( 'Based on %d customer reviews after purchase.', 'luma-reviews-plus' ), \absint( $data['review_count'] ) ) ); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ( $show_quotes && ! empty( $data['quotes'] ) ) : ?>
                    <div class="luma-shop-reviews-summary__quotes" data-luma-shop-quotes data-offset="<?php echo esc_attr( $loaded_quotes ); ?>" data-total="<?php echo esc_attr( $total_quotes ); ?>" data-minimum-rating="<?php echo esc_attr( $minimum_rating ); ?>" data-featured-only="<?php echo esc_attr( $featured_only ? '1' : '0' ); ?>" data-load-count="<?php echo esc_attr( $load_more_count ); ?>">
                        <?php echo $this->render_quotes_html( $data['quotes'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <?php if ( $has_more ) : ?>
                        <p class="luma-shop-reviews-summary__actions">
                            <button type="button" class="button luma-shop-reviews-summary__load-more" data-luma-shop-quotes-load-more data-nonce="<?php echo esc_attr( wp_create_nonce( 'luma_reviews_plus_load_shop_quotes' ) ); ?>">
                                <?php esc_html_e( 'Show more reviews', 'luma-reviews-plus' ); ?>
                            </button>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }


    /**
     * Loads additional quote cards by AJAX.
     *
     * @return void
     */
    public function handle_load_more_ajax() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'luma_reviews_plus_load_shop_quotes' ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'luma-reviews-plus' ) ), 400 );
        }

        $offset         = isset( $_POST['offset'] ) ? max( 0, absint( wp_unslash( $_POST['offset'] ) ) ) : 0;
        $limit          = isset( $_POST['limit'] ) ? max( 1, absint( wp_unslash( $_POST['limit'] ) ) ) : 3;
        $minimum_rating = isset( $_POST['minimum_rating'] ) ? max( 1, min( 5, absint( wp_unslash( $_POST['minimum_rating'] ) ) ) ) : 4;
        $featured_only  = isset( $_POST['featured_only'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['featured_only'] ) );
        $total_quotes   = $this->shop_review_repository->count_public_quotes( $minimum_rating, $featured_only );

        if ( $offset >= $total_quotes ) {
            wp_send_json_success(
                array(
                    'html'       => '',
                    'next_offset' => $offset,
                    'has_more'   => false,
                )
            );
        }

        $quotes = $this->shop_review_repository->get_public_quotes(
            array(
                'offset'         => $offset,
                'limit'          => $limit,
                'minimum_rating' => $minimum_rating,
                'featured_only'  => $featured_only,
            )
        );

        $loaded_now  = is_array( $quotes ) ? count( $quotes ) : 0;
        $next_offset = $offset + $loaded_now;

        wp_send_json_success(
            array(
                'html'        => $this->render_quotes_html( $quotes ),
                'next_offset' => $next_offset,
                'has_more'    => $next_offset < $total_quotes,
            )
        );
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
     * Returns rendered quote cards markup.
     *
     * @param array $quotes Quote rows.
     * @return string
     */
    protected function render_quotes_html( array $quotes ) {
        ob_start();

        foreach ( $quotes as $quote ) {
            ?>
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
            <?php
        }

        return (string) ob_get_clean();
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
     * Returns whether an attribute value means yes.
     *
     * @param mixed $value Attribute value.
     * @return bool
     */
    protected function is_yes_value( $value ) {
        return 'yes' === strtolower( sanitize_key( (string) $value ) );
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


    /**
     * Enqueues the frontend script.
     *
     * @return void
     */
    protected function enqueue_script() {
        if ( ! \wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
            return;
        }

        \wp_enqueue_script( self::SCRIPT_HANDLE );
    }
}
