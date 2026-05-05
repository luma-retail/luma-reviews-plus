<?php
/**
 * Product review item partial.
 *
 * @var array $item
 * @var array $values
 */

?>
<article class="luma-reviews-plus-product-item">
    <?php if ( ! empty( $item['product_image'] ) ) : ?>
        <div class="luma-reviews-plus-product-item__image">
            <img src="<?php echo esc_url( $item['product_image'] ); ?>" alt="<?php echo esc_attr( $item['product_name'] ); ?>" />
        </div>
    <?php endif; ?>

    <div class="luma-reviews-plus-product-item__content">
        <h3><?php echo esc_html( $item['product_name'] ); ?></h3>

        <?php if ( ! empty( $item['variation_meta'] ) ) : ?>
            <div class="luma-reviews-plus-product-item__meta"><?php echo wp_kses_post( $item['variation_meta'] ); ?></div>
        <?php endif; ?>

        <label>
            <span><?php esc_html_e( 'Vurdering', 'luma-reviews-plus' ); ?></span>
            <select name="product_reviews[<?php echo esc_attr( $item['order_item_id'] ); ?>][rating]">
                <option value=""><?php esc_html_e( 'Velg', 'luma-reviews-plus' ); ?></option>
                <?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?>
                    <option value="<?php echo esc_attr( $rating ); ?>" <?php selected( (string) ( $values['rating'] ?? '' ), (string) $rating ); ?>><?php echo esc_html( $rating ); ?></option>
                <?php endfor; ?>
            </select>
        </label>

        <label>
            <span><?php esc_html_e( 'Kommentar', 'luma-reviews-plus' ); ?></span>
            <textarea name="product_reviews[<?php echo esc_attr( $item['order_item_id'] ); ?>][comment]" rows="4" <?php echo $this->settings->is_product_review_comment_required() ? 'required' : ''; ?>><?php echo esc_textarea( (string) ( $values['comment'] ?? '' ) ); ?></textarea>
        </label>
    </div>
</article>
