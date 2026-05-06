<?php

namespace Luma\ReviewsPlus\Admin;

use Luma\ReviewsPlus\Activation\Activator;
use Luma\ReviewsPlus\Settings\Settings;

/**
 * Renders and saves the WooCommerce settings section.
 *
 * Responsibilities:
 * - Add the Reviews Plus section under WooCommerce product settings.
 * - Render grouped plugin settings fields.
 * - Sanitize and persist the grouped settings option.
 * - Keep the managed review page synced with the configured slug and title.
 */
class SettingsPage {

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;


    /**
     * Creates the settings page handler.
     *
     * @param Settings $settings Settings service.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }


    /**
     * Registers settings hooks.
     *
     * @return void
     */
    public function register() {
        add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
        add_filter( 'woocommerce_get_settings_products', array( $this, 'get_settings' ), 10, 2 );
        add_action( 'woocommerce_admin_field_luma_reviews_plus_settings_group', array( $this, 'render_fields' ) );
        add_action( 'woocommerce_settings_save_products', array( $this, 'save' ) );
    }


    /**
     * Adds the settings section.
     *
     * @param array $sections Existing sections.
     * @return array
     */
    public function add_section( $sections ) {
        $sections['luma_reviews_plus'] = __( 'Reviews Plus', 'luma-reviews-plus' );

        return $sections;
    }


    /**
     * Returns section settings definitions.
     *
     * @param array  $settings Existing settings.
     * @param string $current_section Current section key.
     * @return array
     */
    public function get_settings( $settings, $current_section ) {
        if ( 'luma_reviews_plus' !== $current_section ) {
            return $settings;
        }

        return array(
            array(
                'title' => __( 'Luma Reviews Plus', 'luma-reviews-plus' ),
                'type'  => 'title',
                'id'    => 'luma_reviews_plus_settings_title',
                'desc'  => __( 'Configure review request timing, review page text, and public testimonial behavior.', 'luma-reviews-plus' ),
            ),
            array(
                'type' => 'luma_reviews_plus_settings_group',
                'id'   => 'luma_reviews_plus_settings_group',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'luma_reviews_plus_settings_end',
            ),
        );
    }


    /**
     * Renders grouped settings fields.
     *
     * @return void
     */
    public function render_fields() {
        $values = $this->settings->get_all();

        echo '<tr valign="top">';
        echo '<th scope="row">' . esc_html__( 'Email content', 'luma-reviews-plus' ) . '</th>';
        echo '<td><p class="description">' . wp_kses_post( sprintf( __( 'Edit the review request email subject, heading, and body in <a href="%s">WooCommerce email settings</a>.', 'luma-reviews-plus' ), esc_url( $this->get_review_email_settings_url() ) ) ) . '</p></td>';
        echo '</tr>';

        foreach ( $this->settings->get_settings_fields() as $field ) {
            $value = $values[ $field['key'] ] ?? '';
            echo '<tr valign="top">';
            echo '<th scope="row"><label for="luma_reviews_plus_settings_' . esc_attr( $field['key'] ) . '">' . esc_html( $field['label'] ) . '</label></th>';
            echo '<td>';
            $this->render_field_input( $field, $value );
            echo '</td>';
            echo '</tr>';
        }
    }


    /**
     * Saves the grouped settings option.
     *
     * @return void
     */
    public function save() {
        $current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

        if ( 'luma_reviews_plus' !== $current_section || ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $posted   = isset( $_POST['luma_reviews_plus_settings'] ) ? wp_unslash( $_POST['luma_reviews_plus_settings'] ) : array();
        $previous = $this->settings->get_all();
        $clean    = $this->settings->sanitize_settings( $posted );

        update_option( Settings::OPTION_NAME, $clean );

        if ( ! $this->settings->get_review_page_id() || $previous['review_page_slug'] !== $clean['review_page_slug'] || $previous['review_page_heading'] !== $clean['review_page_heading'] ) {
            Activator::ensure_review_page( $this->settings );
        }
    }


    /**
     * Renders a single field input.
     *
     * @param array $field Field definition.
     * @param mixed $value Current value.
     * @return void
     */
    protected function render_field_input( $field, $value ) {
        $name = 'luma_reviews_plus_settings[' . $field['key'] . ']';
        $id   = 'luma_reviews_plus_settings_' . $field['key'];

        switch ( $field['type'] ) {
            case 'checkbox':
                echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $value ), true, false ) . ' /> ' . esc_html__( 'Enabled', 'luma-reviews-plus' ) . '</label>';
                break;

            case 'number':
                echo '<input type="number" class="small-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( $field['min'] ?? 0 ) . '" max="' . esc_attr( $field['max'] ?? 9999 ) . '" />';
                break;

            case 'textarea_array':
                echo '<textarea class="large-text" rows="8" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( implode( PHP_EOL, (array) $value ) ) . '</textarea>';
                echo '<p class="description">' . esc_html__( 'One tag per line.', 'luma-reviews-plus' ) . '</p>';
                break;

            case 'richtext':
                echo '<textarea class="large-text" rows="5" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
                break;

            case 'select':
                echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
                foreach ( (array) $field['options'] as $option_value => $option_label ) {
                    echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
                }
                echo '</select>';
                break;

            case 'text':
            default:
                echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" />';
                break;
        }

        if ( ! empty( $field['description'] ) ) {
            echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
        }
    }


    /**
     * Returns the admin URL for the review request email settings.
     *
     * @return string
     */
    protected function get_review_email_settings_url() {
        return admin_url( 'admin.php?page=wc-settings&tab=email&section=luma_reviews_plus_review_request' );
    }
}