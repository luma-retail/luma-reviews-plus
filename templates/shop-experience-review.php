<?php
/**
 * Shop experience review partial.
 *
 * @var array $context
 */

$shop_values = $context['posted_shop_review'];
$shop_state  = in_array( $shop_values['section_state'] ?? '', array( 'reviewing', 'skipped', 'untouched' ), true ) ? $shop_values['section_state'] : ( ! empty( $shop_values['rating'] ) || '' !== trim( (string) ( $shop_values['comment'] ?? '' ) ) || ! empty( $shop_values['tags'] ) ? 'reviewing' : 'untouched' );
?>
<h2><?php echo esc_html( $this->settings->get_shop_review_heading() ); ?></h2>
<div class="luma-reviews-plus-intro"><?php echo wp_kses_post( wpautop( $this->settings->get_shop_review_intro() ) ); ?></div>

<div class="luma-reviews-plus-shop-review-fields luma-review-section" data-review-section="shop" data-section-state="<?php echo esc_attr( $shop_state ); ?>">
    <div class="luma-reviews-plus-section-actions" role="group" aria-label="<?php esc_attr_e( 'Store review', 'luma-reviews-plus' ); ?>">
        <span class="luma-reviews-plus-badge"><?php esc_html_e( 'Optional', 'luma-reviews-plus' ); ?></span>
        <button type="button" class="button button-secondary luma-reviews-plus-toggle" data-section-action="review"><?php esc_html_e( 'Review the shopping experience', 'luma-reviews-plus' ); ?></button>
        <button type="button" class="button button-secondary luma-reviews-plus-toggle" data-section-action="skip"><?php esc_html_e( 'Skip store review', 'luma-reviews-plus' ); ?></button>
    </div>
    <p class="luma-reviews-plus-section-summary" data-summary-untouched><?php esc_html_e( 'The store review is optional.', 'luma-reviews-plus' ); ?></p>
    <p class="luma-reviews-plus-section-summary" data-summary-skipped hidden><?php esc_html_e( 'The shopping experience will not be reviewed.', 'luma-reviews-plus' ); ?></p>

    <div class="luma-reviews-plus-section-fields">
    <input type="hidden" name="shop_review[section_state]" value="<?php echo esc_attr( $shop_state ); ?>" data-section-state-input />
    <?php $shop_rating_value = (string) ( $shop_values['rating'] ?? '' ); ?>
    <div class="comment-form-rating luma-reviews-plus-rating-field">
        <label for="luma-shop-rating"><?php esc_html_e( 'Store rating', 'luma-reviews-plus' ); ?></label>
        <p class="stars<?php echo '' !== $shop_rating_value ? ' selected' : ''; ?>">
            <span role="group" aria-label="<?php esc_attr_e( 'Store rating', 'luma-reviews-plus' ); ?>">
                <?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?>
                    <a
                        role="radio"
                        href="#"
                        class="star-<?php echo esc_attr( $rating ); ?><?php echo $shop_rating_value === (string) $rating ? ' active' : ''; ?>"
                        aria-checked="<?php echo $shop_rating_value === (string) $rating ? 'true' : 'false'; ?>"
                        tabindex="<?php echo $shop_rating_value === (string) $rating || ( '' === $shop_rating_value && 1 === $rating ) ? '0' : '-1'; ?>"
                        data-value="<?php echo esc_attr( $rating ); ?>"
                    ><?php echo esc_html( sprintf( __( '%d out of 5 stars', 'luma-reviews-plus' ), $rating ) ); ?></a>
                <?php endfor; ?>
            </span>
        </p>
        <select name="shop_review[rating]" id="luma-shop-rating" class="luma-reviews-plus-rating-select" aria-hidden="true" style="display: none;">
            <option value=""><?php esc_html_e( 'Rate...', 'luma-reviews-plus' ); ?></option>
            <option value="5" <?php selected( $shop_rating_value, '5' ); ?>><?php esc_html_e( 'Perfect', 'luma-reviews-plus' ); ?></option>
            <option value="4" <?php selected( $shop_rating_value, '4' ); ?>><?php esc_html_e( 'Good', 'luma-reviews-plus' ); ?></option>
            <option value="3" <?php selected( $shop_rating_value, '3' ); ?>><?php esc_html_e( 'Average', 'luma-reviews-plus' ); ?></option>
            <option value="2" <?php selected( $shop_rating_value, '2' ); ?>><?php esc_html_e( 'Not that bad', 'luma-reviews-plus' ); ?></option>
            <option value="1" <?php selected( $shop_rating_value, '1' ); ?>><?php esc_html_e( 'Very poor', 'luma-reviews-plus' ); ?></option>
        </select>
        <p class="luma-reviews-plus-field-error" hidden></p>
    </div>

    <p class="form-row form-row-wide">
        <label for="luma-shop-comment"><?php esc_html_e( 'Comment', 'luma-reviews-plus' ); ?></label>
        <textarea id="luma-shop-comment" name="shop_review[comment]" rows="5"><?php echo esc_textarea( (string) ( $shop_values['comment'] ?? '' ) ); ?></textarea>
        <p class="luma-reviews-plus-field-error" hidden></p>
    </p>

    <fieldset>
        <legend><?php esc_html_e( 'What worked well?', 'luma-reviews-plus' ); ?></legend>
        <div class="luma-reviews-plus-tags">
            <?php foreach ( $this->settings->get_shop_review_tags() as $tag ) : ?>
                <label>
                    <input type="checkbox" name="shop_review[tags][]" value="<?php echo esc_attr( $tag ); ?>" <?php checked( in_array( $tag, (array) ( $shop_values['tags'] ?? array() ), true ) ); ?> />
                    <span><?php echo esc_html( $tag ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <p class="form-row form-row-wide">
        <label for="luma-shop-display-name"><?php esc_html_e( 'Display name', 'luma-reviews-plus' ); ?></label>
        <input id="luma-shop-display-name" type="text" name="shop_review[display_name]" value="<?php echo esc_attr( (string) ( $shop_values['display_name'] ?? '' ) ); ?>" />
        <small><?php esc_html_e( 'You may remove your last name or add something like your Instagram name, but your first name must remain.', 'luma-reviews-plus' ); ?></small>
        <p class="luma-reviews-plus-field-error" hidden></p>
    </p>

    <label class="luma-reviews-plus-checkbox">
        <input type="checkbox" name="shop_review[public_consent]" value="1" <?php checked( ! empty( $shop_values['public_consent'] ) ); ?> />
        <span><?php echo esc_html( $this->settings->get_public_consent_text() ); ?></span>
    </label>
    </div>
</div>
