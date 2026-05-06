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

        self::ensure_review_page( $settings );
    }


    /**
     * Creates or updates the managed review page.
     *
     * @param Settings $settings Settings service.
     * @return int
     */
    public static function ensure_review_page( Settings $settings ) {
        $page_id = $settings->get_review_page_id();
        $data    = self::get_review_page_post_data( $settings );

        if ( $page_id ) {
            $page = \get_post( $page_id );

            if ( $page instanceof \WP_Post && 'page' === $page->post_type ) {
                self::update_review_page( $page_id, $data );
                return $page_id;
            }
        }

        $existing_page = \get_page_by_path( $settings->get_review_page_slug(), OBJECT, 'page' );

        if ( $existing_page instanceof \WP_Post ) {
            self::update_review_page( $existing_page->ID, $data );
            return (int) $existing_page->ID;
        }

        $page_id = \wp_insert_post( $data, true, false );

        if ( \is_wp_error( $page_id ) || ! $page_id ) {
            return 0;
        }

        \update_option( Settings::REVIEW_PAGE_ID_OPTION, absint( $page_id ) );

        return absint( $page_id );
    }


    /**
     * Updates a managed review page and stores its ID.
     *
     * @param int   $page_id Page ID.
     * @param array $data Post data.
     * @return void
     */
    protected static function update_review_page( $page_id, array $data ) {
        $page = \get_post( $page_id );

        if ( ! $page instanceof \WP_Post ) {
            return;
        }

        if ( $page->post_title === $data['post_title'] && $page->post_name === $data['post_name'] && $page->post_content === $data['post_content'] && $page->comment_status === $data['comment_status'] ) {
            \update_option( Settings::REVIEW_PAGE_ID_OPTION, absint( $page_id ) );
            return;
        }

        \wp_update_post(
            array_merge(
                $data,
                array(
                    'ID' => absint( $page_id ),
                )
            ),
            false,
            false
        );

        \update_option( Settings::REVIEW_PAGE_ID_OPTION, absint( $page_id ) );
    }


    /**
     * Returns the managed review page post data.
     *
     * @param Settings $settings Settings service.
     * @return array
     */
    protected static function get_review_page_post_data( Settings $settings ) {
        return array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_title'     => $settings->get_review_page_heading(),
            'post_name'      => $settings->get_review_page_slug(),
            'post_content'   => '',
            'comment_status' => 'closed',
        );
    }
}
