<?php
/**
 * Product review item partial.
 *
 * @var array $item
 * @var array $values
 */

 $section_state = in_array( $values['section_state'] ?? '', array( 'reviewing', 'skipped', 'untouched' ), true ) ? $values['section_state'] : ( ! empty( $values['rating'] ) || '' !== trim( (string) ( $values['comment'] ?? '' ) ) ? 'reviewing' : 'untouched' );
?>
<div class="luma-reviews-plus-product-item luma-review-section" data-review-section="product" data-section-state="<?php echo esc_attr( $section_state ); ?>" data-product-name="<?php echo esc_attr( $item['product_name'] ); ?>">
    <?php if ( ! empty( $item['product_image'] ) ) : ?>
        <div class="luma-reviews-plus-product-item__image">
            <img src="<?php echo esc_url( $item['product_image'] ); ?>" alt="<?php echo esc_attr( $item['product_name'] ); ?>" />
        </div>
    <?php endif; ?>

    <div class="luma-reviews-plus-product-item__content">
        <h3><?php echo esc_html( $item['product_name'] ); ?></h3>
        <div class="luma-reviews-plus-section-actions" role="group" aria-label="<?php esc_attr_e( 'Product review', 'luma-reviews-plus' ); ?>">
            <span class="luma-reviews-plus-badge"><?php esc_html_e( 'Optional', 'luma-reviews-plus' ); ?></span>
            <button type="button" class="button button-secondary luma-reviews-plus-toggle" data-section-action="review"><?php esc_html_e( 'Review this product', 'luma-reviews-plus' ); ?></button>
            <button type="button" class="button button-secondary luma-reviews-plus-toggle" data-section-action="skip"><?php esc_html_e( 'Skip', 'luma-reviews-plus' ); ?></button>
        </div>
        <p class="luma-reviews-plus-section-summary" data-summary-untouched><?php esc_html_e( 'Would you like to review this product?', 'luma-reviews-plus' ); ?></p>
        <p class="luma-reviews-plus-section-summary" data-summary-skipped hidden><?php esc_html_e( 'This product will not be reviewed.', 'luma-reviews-plus' ); ?></p>

        <?php if ( ! empty( $item['variation_meta'] ) ) : ?>
            <div class="luma-reviews-plus-product-item__meta"><?php echo wp_kses_post( $item['variation_meta'] ); ?></div>
        <?php endif; ?>

        <input type="hidden" name="product_reviews[<?php echo esc_attr( $item['order_item_id'] ); ?>][section_state]" value="<?php echo esc_attr( $section_state ); ?>" data-section-state-input />

        <div class="luma-reviews-plus-section-fields">
        <?php $rating_value = (string) ( $values['rating'] ?? '' ); ?>
        <div class="comment-form-rating luma-reviews-plus-rating-field">
            <label for="luma-product-rating-<?php echo esc_attr( $item['order_item_id'] ); ?>"><?php esc_html_e( 'Rating', 'luma-reviews-plus' ); ?></label>
            <p class="stars<?php echo '' !== $rating_value ? ' selected' : ''; ?>">
                <span role="group" aria-label="<?php esc_attr_e( 'Rating', 'luma-reviews-plus' ); ?>">
                    <?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?>
                        <a
                            role="radio"
                            href="#"
                            class="star-<?php echo esc_attr( $rating ); ?><?php echo $rating_value === (string) $rating ? ' active' : ''; ?>"
                            aria-checked="<?php echo $rating_value === (string) $rating ? 'true' : 'false'; ?>"
                            tabindex="<?php echo $rating_value === (string) $rating || ( '' === $rating_value && 1 === $rating ) ? '0' : '-1'; ?>"
                            data-value="<?php echo esc_attr( $rating ); ?>"
                        ><?php echo esc_html( sprintf( __( '%d out of 5 stars', 'luma-reviews-plus' ), $rating ) ); ?></a>
                    <?php endfor; ?>
                </span>
            </p>
            <select name="product_reviews[<?php echo esc_attr( $item['order_item_id'] ); ?>][rating]" id="luma-product-rating-<?php echo esc_attr( $item['order_item_id'] ); ?>" class="luma-reviews-plus-rating-select" aria-hidden="true" style="display: none;">
                <option value=""><?php esc_html_e( 'Rate...', 'luma-reviews-plus' ); ?></option>
                <option value="5" <?php selected( $rating_value, '5' ); ?>><?php esc_html_e( 'Perfect', 'luma-reviews-plus' ); ?></option>
                <option value="4" <?php selected( $rating_value, '4' ); ?>><?php esc_html_e( 'Good', 'luma-reviews-plus' ); ?></option>
                <option value="3" <?php selected( $rating_value, '3' ); ?>><?php esc_html_e( 'Average', 'luma-reviews-plus' ); ?></option>
                <option value="2" <?php selected( $rating_value, '2' ); ?>><?php esc_html_e( 'Not that bad', 'luma-reviews-plus' ); ?></option>
                <option value="1" <?php selected( $rating_value, '1' ); ?>><?php esc_html_e( 'Very poor', 'luma-reviews-plus' ); ?></option>
            </select>
            <p class="luma-reviews-plus-field-error" hidden></p>
        </div>

        <p class="form-row form-row-wide">
            <label for="luma-product-comment-<?php echo esc_attr( $item['order_item_id'] ); ?>"><?php esc_html_e( 'Comment', 'luma-reviews-plus' ); ?></label>
            <?php if ( $this->settings->is_product_review_comment_required() ) : ?>
                <small><?php esc_html_e( 'Required if you review this product.', 'luma-reviews-plus' ); ?></small>
            <?php endif; ?>
            <textarea id="luma-product-comment-<?php echo esc_attr( $item['order_item_id'] ); ?>" name="product_reviews[<?php echo esc_attr( $item['order_item_id'] ); ?>][comment]" rows="4"><?php echo esc_textarea( (string) ( $values['comment'] ?? '' ) ); ?></textarea>
            <p class="luma-reviews-plus-field-error" hidden></p>
        </p>
        </div>
    </div>
</div>
