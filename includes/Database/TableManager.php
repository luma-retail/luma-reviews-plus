<?php

namespace Luma\ReviewsPlus\Database;

/**
 * Manages plugin table names and schema creation.
 *
 * Responsibilities:
 * - Return stable custom table names.
 * - Create and upgrade plugin tables with dbDelta.
 * - Persist the current database schema version.
 */
class TableManager {

    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    protected $wpdb;


    /**
     * Creates the table manager.
     *
     * @param \wpdb $wpdb Database connection.
     */
    public function __construct( \wpdb $wpdb ) {
        $this->wpdb = $wpdb;
    }


    /**
     * Returns the tokens table name.
     *
     * @return string
     */
    public function get_tokens_table_name() {
        return $this->wpdb->prefix . 'luma_review_tokens';
    }


    /**
     * Returns the product review log table name.
     *
     * @return string
     */
    public function get_product_reviews_table_name() {
        return $this->wpdb->prefix . 'luma_order_product_reviews';
    }


    /**
     * Returns the shop reviews table name.
     *
     * @return string
     */
    public function get_shop_reviews_table_name() {
        return $this->wpdb->prefix . 'luma_shop_reviews';
    }


    /**
     * Creates or upgrades plugin tables.
     *
     * @return void
     */
    public function create_tables() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = array(
            "CREATE TABLE {$this->get_tokens_table_name()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                order_id bigint(20) unsigned NOT NULL,
                customer_id bigint(20) unsigned NULL,
                token_hash varchar(128) NOT NULL,
                status varchar(32) NOT NULL DEFAULT 'pending',
                expires_at datetime NULL,
                used_at datetime NULL,
                created_at datetime NOT NULL,
                last_sent_at datetime NULL,
                PRIMARY KEY  (id),
                KEY order_id (order_id),
                KEY customer_id (customer_id),
                UNIQUE KEY token_hash (token_hash)
            ) {$charset_collate};",
            "CREATE TABLE {$this->get_product_reviews_table_name()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                order_id bigint(20) unsigned NOT NULL,
                order_item_id bigint(20) unsigned NOT NULL,
                product_id bigint(20) unsigned NOT NULL,
                variation_id bigint(20) unsigned NULL,
                review_comment_id bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY order_item_id (order_item_id),
                KEY order_id (order_id),
                KEY product_id (product_id),
                KEY review_comment_id (review_comment_id)
            ) {$charset_collate};",
            "CREATE TABLE {$this->get_shop_reviews_table_name()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                order_id bigint(20) unsigned NOT NULL,
                customer_id bigint(20) unsigned NULL,
                rating tinyint(1) unsigned NOT NULL,
                comment text NULL,
                tags_json longtext NULL,
                public_consent tinyint(1) unsigned NOT NULL DEFAULT 0,
                display_name varchar(100) NULL,
                display_location varchar(100) NULL,
                approved_for_public_display tinyint(1) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY order_id (order_id),
                KEY customer_id (customer_id),
                KEY rating (rating),
                KEY approved_for_public_display (approved_for_public_display)
            ) {$charset_collate};",
        );

        foreach ( $sql as $statement ) {
            dbDelta( $statement );
        }

        update_option( 'luma_reviews_plus_db_version', LUMA_REVIEWS_PLUS_VERSION );
    }
}
