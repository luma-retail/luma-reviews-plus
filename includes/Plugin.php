<?php

namespace Luma\ReviewsPlus;

use Luma\ReviewsPlus\Admin\ShopReviewsPage;
use Luma\ReviewsPlus\Admin\SettingsPage;
use Luma\ReviewsPlus\Database\ProductReviewLogRepository;
use Luma\ReviewsPlus\Database\ShopReviewRepository;
use Luma\ReviewsPlus\Database\TableManager;
use Luma\ReviewsPlus\Database\TokenRepository;
use Luma\ReviewsPlus\Email\ReviewEmail;
use Luma\ReviewsPlus\Email\ReviewEmailScheduler;
use Luma\ReviewsPlus\Email\ReviewLinkGenerator;
use Luma\ReviewsPlus\Frontend\PublicTrustRenderer;
use Luma\ReviewsPlus\Frontend\ReviewFormRenderer;
use Luma\ReviewsPlus\Frontend\ReviewPageController;
use Luma\ReviewsPlus\Reviews\ProductReviewHandler;
use Luma\ReviewsPlus\Reviews\ShopReviewHandler;
use Luma\ReviewsPlus\Settings\Settings;
use Luma\ReviewsPlus\Utils\Helpers;

/**
 * Boots the plugin and wires together the runtime services.
 *
 * Responsibilities:
 * - Load translations and runtime services.
 * - Fail gracefully when WooCommerce is unavailable.
 * - Register admin, frontend, and email components.
 * - Run conservative runtime upgrade checks.
 */
class Plugin {

    /**
     * Initializes the plugin services.
     *
     * @return void
     */
    public function init() {
        \add_action( 'init', array( $this, 'load_textdomain' ) );

        if ( ! class_exists( 'WooCommerce' ) ) {
            \add_action( 'admin_notices', array( $this, 'render_woocommerce_notice' ) );
            return;
        }

        $this->maybe_upgrade_database();
        $this->register_services();
    }


    /**
     * Loads plugin translations.
     *
     * @return void
     */
    public function load_textdomain() {
        \load_plugin_textdomain( 'luma-reviews-plus', false, dirname( LUMA_REVIEWS_PLUS_BASENAME ) . '/languages' );
    }


    /**
     * Displays a WooCommerce dependency notice.
     *
     * @return void
     */
    public function render_woocommerce_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo \esc_html__( 'Luma Reviews Plus requires WooCommerce to be active.', 'luma-reviews-plus' );
        echo '</p></div>';
    }


    /**
     * Runs runtime upgrade logic for database tables.
     *
     * @return void
     */
    protected function maybe_upgrade_database() {
        if ( version_compare( (string) \get_option( 'luma_reviews_plus_db_version', '0.0.0' ), LUMA_REVIEWS_PLUS_VERSION, '>=' ) ) {
            return;
        }

        $table_manager = new TableManager( $GLOBALS['wpdb'] );
        $table_manager->create_tables();
    }


    /**
     * Creates and registers the runtime services.
     *
     * @return void
     */
    protected function register_services() {
        $table_manager                = new TableManager( $GLOBALS['wpdb'] );
        $settings                     = new Settings();
        $token_repository             = new TokenRepository( $GLOBALS['wpdb'], $table_manager, $settings );
        $product_review_log_repository = new ProductReviewLogRepository( $GLOBALS['wpdb'], $table_manager );
        $shop_review_repository       = new ShopReviewRepository( $GLOBALS['wpdb'], $table_manager );

        $settings_page = new SettingsPage( $settings );
        $settings_page->register();

        if ( is_admin() ) {
            $shop_reviews_page = new ShopReviewsPage( $shop_review_repository, $settings );
            $shop_reviews_page->register();
        }

        $link_generator = new ReviewLinkGenerator( $settings );
        $scheduler      = new ReviewEmailScheduler( $settings, $token_repository, $link_generator );
        $scheduler->register();

        \add_filter( 'woocommerce_email_classes', array( $this, 'register_review_email' ) );
        \add_filter( 'woocommerce_locate_template', array( $this, 'locate_email_template' ), 10, 3 );

        $product_review_handler = new ProductReviewHandler( $settings, $product_review_log_repository );
        $shop_review_handler    = new ShopReviewHandler( $settings, $shop_review_repository );
        $form_renderer          = new ReviewFormRenderer( $settings );
        $controller             = new ReviewPageController( $settings, $token_repository, $product_review_log_repository, $shop_review_repository, $product_review_handler, $shop_review_handler, $form_renderer );
        $controller->register();

        $public_trust_renderer = new PublicTrustRenderer( $shop_review_repository );
        $public_trust_renderer->register();

        \add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

        Helpers::log( 'Luma Reviews Plus initialized.' );
    }


    /**
     * Registers the WooCommerce email class.
     *
     * @param array $email_classes Existing WooCommerce email classes.
     * @return array
     */
    public function register_review_email( $email_classes ) {
        $settings       = new Settings();
        $link_generator = new ReviewLinkGenerator( $settings );

        $email_classes['luma_reviews_plus_review_request'] = new ReviewEmail( $settings, $link_generator );

        return $email_classes;
    }


    /**
     * Locates plugin email templates.
     *
     * @param string $template Existing template path.
     * @param string $template_name Requested template name.
     * @param string $template_path Template path.
     * @return string
     */
    public function locate_email_template( $template, $template_name, $template_path ) {
        if ( 0 !== strpos( $template_name, 'emails/' ) ) {
            return $template;
        }

        $plugin_template = \trailingslashit( LUMA_REVIEWS_PLUS_PATH . 'templates' ) . $template_name;

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
    }


    /**
     * Registers frontend assets.
     *
     * @return void
     */
    public function register_assets() {
        \wp_register_style( 'luma-reviews-plus-frontend', LUMA_REVIEWS_PLUS_URL . 'assets/css/frontend.css', array(), LUMA_REVIEWS_PLUS_VERSION );
        \wp_register_script( 'luma-reviews-plus-frontend', LUMA_REVIEWS_PLUS_URL . 'assets/js/frontend.js', array(), LUMA_REVIEWS_PLUS_VERSION, true );
        \wp_localize_script(
            'luma-reviews-plus-frontend',
            'lumaReviewsPlusI18n',
            array(
                'shopRatingRequired'      => __( 'Choose a rating for the shopping experience, or skip the store review.', 'luma-reviews-plus' ),
                'productRatingRequired'   => __( 'Choose a rating for this product, or skip it.', 'luma-reviews-plus' ),
                'productCommentRequired'  => __( 'Write a short comment for this product.', 'luma-reviews-plus' ),
                'shopDisplayNameRequired' => __( 'Enter your display name for the store review.', 'luma-reviews-plus' ),
                'selectAtLeastOne'        => __( 'Choose at least one product or the store review before submitting.', 'luma-reviews-plus' ),
                'selectProductForStore'   => __( 'Choose at least one product if you also want to submit the store review.', 'luma-reviews-plus' ),
                'reviewErrorsSummary'     => __( 'Please review the highlighted fields below before submitting.', 'luma-reviews-plus' ),
            )
        );
    }
}
