<?php

namespace Luma\ReviewsPlus\Activation;

use Luma\ReviewsPlus\Database\TableManager;
use Luma\ReviewsPlus\Settings\Settings;

/**
 * Handles plugin activation work.
 *
 * Responsibilities:
 * - Create required database tables.
 * - Seed default plugin settings.
 * - Register rewrite rules before flushing them.
 * - Store the installed schema version.
 */
class Activator {

    /**
     * Runs activation tasks.
     *
     * @return void
     */
    public static function activate() {
        $table_manager = new TableManager( $GLOBALS['wpdb'] );
        $settings      = new Settings();

        if ( ! \get_option( Settings::OPTION_NAME, false ) ) {
            \add_option( Settings::OPTION_NAME, $settings->get_defaults() );
        }

        $table_manager->create_tables();

        \add_rewrite_tag( '%luma_reviews_plus_review_page%', '1' );
        \add_rewrite_rule( '^' . $settings->get_review_page_slug() . '/?$', 'index.php?luma_reviews_plus_review_page=1', 'top' );
        \flush_rewrite_rules();
    }
}
