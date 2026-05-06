<?php
/**
 * Plain text review request template.
 *
 * @var string $email_heading
 * @var string $body_text
 * @var string $review_link
 * @var bool $append_review_link
 * @var string $review_link_fallback_text
 */

echo "=" . wp_strip_all_tags( $email_heading ) . "=\n\n";
echo wp_strip_all_tags( $body_text ) . "\n\n";

if ( ! empty( $append_review_link ) ) {
	echo wp_strip_all_tags( $review_link_fallback_text ) . "\n";
	echo wp_strip_all_tags( $review_link ) . "\n";
}
