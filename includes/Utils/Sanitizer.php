<?php

namespace Luma\ReviewsPlus\Utils;

/**
 * Provides shared sanitization helpers.
 *
 * Responsibilities:
 * - Normalize scalar values from request arrays.
 * - Sanitize reusable settings and form inputs.
 * - Keep sanitization rules consistent across the plugin.
 */
class Sanitizer {

    /**
     * Sanitizes a boolean-like value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public static function bool_to_int( $value ) {
        return empty( $value ) ? 0 : 1;
    }


    /**
     * Sanitizes an integer value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public static function absint( $value ) {
        return absint( $value );
    }


    /**
     * Sanitizes plain text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function text( $value ) {
        return sanitize_text_field( (string) $value );
    }


    /**
     * Sanitizes textarea text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function textarea( $value ) {
        return sanitize_textarea_field( (string) $value );
    }


    /**
     * Sanitizes rich text body copy.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function rich_text( $value ) {
        return wp_kses_post( (string) $value );
    }


    /**
     * Sanitizes a slug value.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function slug( $value ) {
        return sanitize_title( (string) $value );
    }


    /**
     * Sanitizes a list of strings.
     *
     * @param array $values Raw values.
     * @return array
     */
    public static function string_array( $values ) {
        $values = is_array( $values ) ? $values : array();

        return array_values( array_filter( array_map( 'sanitize_text_field', $values ) ) );
    }
}
