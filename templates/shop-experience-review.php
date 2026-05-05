<?php
/**
 * Shop experience review partial.
 *
 * @var array $context
 */

$shop_values = $context['posted_shop_review'];
?>
<h2><?php echo esc_html( $this->settings->get_shop_review_heading() ); ?></h2>
<div class="luma-reviews-plus-intro"><?php echo wp_kses_post( wpautop( $this->settings->get_shop_review_intro() ) ); ?></div>

<div class="luma-reviews-plus-shop-review-fields">
    <label>
        <span><?php esc_html_e( 'Butikkvurdering', 'luma-reviews-plus' ); ?></span>
        <select name="shop_review[rating]">
            <option value=""><?php esc_html_e( 'Velg', 'luma-reviews-plus' ); ?></option>
            <?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?>
                <option value="<?php echo esc_attr( $rating ); ?>" <?php selected( (string) ( $shop_values['rating'] ?? '' ), (string) $rating ); ?>><?php echo esc_html( $rating ); ?></option>
            <?php endfor; ?>
        </select>
    </label>

    <label>
        <span><?php esc_html_e( 'Kommentar', 'luma-reviews-plus' ); ?></span>
        <textarea name="shop_review[comment]" rows="5"><?php echo esc_textarea( (string) ( $shop_values['comment'] ?? '' ) ); ?></textarea>
    </label>

    <fieldset>
        <legend><?php esc_html_e( 'Hva var bra?', 'luma-reviews-plus' ); ?></legend>
        <div class="luma-reviews-plus-tags">
            <?php foreach ( $this->settings->get_shop_review_tags() as $tag ) : ?>
                <label>
                    <input type="checkbox" name="shop_review[tags][]" value="<?php echo esc_attr( $tag ); ?>" <?php checked( in_array( $tag, (array) ( $shop_values['tags'] ?? array() ), true ) ); ?> />
                    <span><?php echo esc_html( $tag ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <label>
        <span><?php esc_html_e( 'Visningsnavn', 'luma-reviews-plus' ); ?></span>
        <input type="text" name="shop_review[display_name]" value="<?php echo esc_attr( (string) ( $shop_values['display_name'] ?? '' ) ); ?>" />
    </label>

    <label>
        <span><?php esc_html_e( 'Sted', 'luma-reviews-plus' ); ?></span>
        <input type="text" name="shop_review[display_location]" value="<?php echo esc_attr( (string) ( $shop_values['display_location'] ?? '' ) ); ?>" />
    </label>

    <label class="luma-reviews-plus-checkbox">
        <input type="checkbox" name="shop_review[public_consent]" value="1" <?php checked( ! empty( $shop_values['public_consent'] ) ); ?> />
        <span><?php echo esc_html( $this->settings->get_public_consent_text() ); ?></span>
    </label>
</div>
