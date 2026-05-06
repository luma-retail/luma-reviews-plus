<?php
/**
 * Public review page template.
 *
 * @var array $context
 */
?>
<div class="woocommerce luma-reviews-plus-content">
    <h1><?php echo esc_html( $this->settings->get_review_page_heading() ); ?></h1>
    <div class="luma-reviews-plus-intro"><?php echo wp_kses_post( wpautop( $this->replace_page_placeholders( $this->settings->get_review_page_intro(), $context ) ) ); ?></div>

    <?php foreach ( $context['messages'] as $message ) : ?>
        <div class="luma-reviews-plus-message"><?php echo esc_html( $message ); ?></div>
    <?php endforeach; ?>

    <?php if ( 'ready' === $context['state'] || ( 'already_reviewed' === $context['state'] && ( ! empty( $context['review_items'] ) || ! $context['shop_review'] ) ) ) : ?>
        <form method="post" class="luma-reviews-plus-form" novalidate data-comment-required="<?php echo $this->settings->is_product_review_comment_required() ? '1' : '0'; ?>" data-allow-shop-only="<?php echo $this->settings->allow_shop_review_without_products() ? '1' : '0'; ?>">
            <?php wp_nonce_field( 'luma_reviews_plus_submit_review', 'luma_reviews_plus_nonce' ); ?>
            <input type="hidden" name="token" value="<?php echo esc_attr( $context['token_raw'] ); ?>" />

            <div class="luma-reviews-plus-form-summary" hidden></div>

            <?php if ( ! empty( $context['review_items'] ) ) : ?>
                <section class="luma-reviews-plus-section">
                    <h2><?php echo esc_html( $this->settings->get_product_reviews_heading() ); ?></h2>
                    <p class="luma-reviews-plus-helper"><?php esc_html_e( 'You do not need to review everything. Only review what you want to give feedback on.', 'luma-reviews-plus' ); ?></p>

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
