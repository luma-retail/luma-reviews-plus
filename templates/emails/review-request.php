<?php
/**
 * Review request email template.
 *
 * @var string $email_heading
 * @var string $body_text
 * @var string $review_link
 * @var string $review_button
 * @var bool $append_review_button
 * @var bool $append_review_link
 * @var string $review_link_fallback_text
 * @var \WC_Email $email
 */

do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p><?php echo wp_kses_post( $body_text ); ?></p>
<?php if ( ! empty( $append_review_button ) ) : ?>
<p><?php echo wp_kses_post( $review_button ); ?></p>
<?php endif; ?>
<?php if ( ! empty( $append_review_link ) ) : ?>
<p><?php echo esc_html( $review_link_fallback_text ); ?></p>
<p><a href="<?php echo esc_url( $review_link ); ?>"><?php echo esc_html( $review_link ); ?></a></p>
<?php endif; ?>
<?php
do_action( 'woocommerce_email_footer', $email );
