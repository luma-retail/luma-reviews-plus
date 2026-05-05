<?php
/**
 * Public review page template.
 *
 * @var array $context
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'luma-reviews-plus-page' ); ?>>
<?php wp_body_open(); ?>
<main class="luma-reviews-plus-shell">
    <div class="luma-reviews-plus-card">
        <header class="luma-reviews-plus-header">
            <h1><?php echo esc_html( $this->settings->get_review_page_heading() ); ?></h1>
            <div class="luma-reviews-plus-intro"><?php echo wp_kses_post( wpautop( $this->settings->get_review_page_intro() ) ); ?></div>
        </header>

        <?php foreach ( $context['messages'] as $message ) : ?>
            <div class="luma-reviews-plus-message"><?php echo esc_html( $message ); ?></div>
        <?php endforeach; ?>

        <?php if ( $context['order'] ) : ?>
            <div class="luma-reviews-plus-order-meta">
                <strong><?php esc_html_e( 'Ordre', 'luma-reviews-plus' ); ?>:</strong>
                <span>#<?php echo esc_html( $context['order']->get_order_number() ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( 'ready' === $context['state'] || ( 'already_reviewed' === $context['state'] && ( ! empty( $context['review_items'] ) || ! $context['shop_review'] ) ) ) : ?>
            <form method="post" class="luma-reviews-plus-form">
                <?php wp_nonce_field( 'luma_reviews_plus_submit_review', 'luma_reviews_plus_nonce' ); ?>
                <input type="hidden" name="token" value="<?php echo esc_attr( $context['token_raw'] ); ?>" />

                <?php if ( ! empty( $context['review_items'] ) ) : ?>
                    <section class="luma-reviews-plus-section">
                        <h2><?php echo esc_html( $this->settings->get_product_reviews_heading() ); ?></h2>
                        <div class="luma-reviews-plus-intro"><?php echo wp_kses_post( wpautop( $this->settings->get_product_reviews_intro() ) ); ?></div>

                        <?php foreach ( $context['review_items'] as $item ) : ?>
                            <?php $this->render_product_review_item( $item, $context['posted_product_reviews'][ $item['order_item_id'] ] ?? array() ); ?>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if ( ! $context['shop_review'] ) : ?>
                    <section class="luma-reviews-plus-section">
                        <?php $this->render_shop_review_section( $context ); ?>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $context['review_items'] ) || ! $context['shop_review'] ) : ?>
                    <div class="luma-reviews-plus-submit">
                        <button type="submit" class="button alt"><?php echo esc_html( $this->settings->get_submit_button_text() ); ?></button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php wp_footer(); ?>
</body>
</html>
