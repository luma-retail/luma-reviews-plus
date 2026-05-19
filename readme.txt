=== Luma Reviews Plus ===
Contributors: terjejohansen
Tags: woocommerce, reviews, email, trust
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Luma Reviews Plus helps WooCommerce stores collect better product and shop reviews with tokenized order-specific review links.

== Description ==

Luma Reviews Plus helps WooCommerce stores ask for better reviews in a way that feels more relevant to the customer. Instead of sending a generic follow-up, it sends the customer to a review page tied to the exact order they placed, so they can review the products they actually bought and optionally share how the shopping experience felt overall.

The goal is simple: make it easier to collect useful, trustworthy feedback without replacing WooCommerce's normal product review system. Customers get a cleaner review flow, while the store gets product reviews, separate shop-experience feedback, and a better foundation for trust-building on the storefront.

This plugin is a good fit if you want more control than a basic follow-up email, but do not want to push your reviews into a third-party platform. It keeps product reviews native to WooCommerce, stores shop-experience reviews separately, and supports site-specific extensions for stores with custom delivery or fulfillment logic.

Key features:

* Sends WooCommerce review request emails with a secure review link.
* Lets customers review one or more purchased products from a single order-specific page.
* Saves product reviews as native WooCommerce reviews.
* Stores separate shop-experience reviews for trust summaries and moderation.
* Prevents duplicate product reviews for the same order item.
* Supports verified, tokenized review links without requiring customer login.
* Supports store-specific timing logic through extension hooks.
* Can optionally show a WooCommerce admin order-page flag when the customer reviewed the previous order.
* Includes a frontend shortcode for published shop-review summaries with conditional CSS loading.

== Show shop reviews on the frontend ==

Use the shortcode below on any page, post, or shortcode-enabled block:

[luma_shop_reviews_summary]

Useful examples:

* `[luma_shop_reviews_summary]`
* `[luma_shop_reviews_summary style="none"]`
* `[luma_shop_reviews_summary style="minimal" quote_count="6"]`
* `[luma_shop_reviews_summary show_quotes="no"]`

Available shortcode attributes:

* `style` accepts `inherit`, `none`, or `minimal`
* `show_rating` accepts `yes` or `no`
* `show_count` accepts `yes` or `no`
* `show_quotes` accepts `yes` or `no`
* `quote_count` controls how many quotes are shown
* `minimum_rating` controls the minimum rating required for quote display

Styling behavior:

* `inherit` uses the plugin setting under WooCommerce > Settings > Products > Reviews Plus.
* `none` loads no plugin CSS, so the theme can style everything.
* `minimal` loads a lightweight summary stylesheet only when the shortcode is present.

The summary stylesheet is only loaded on requests where the shortcode is rendered.

Why use this plugin:

* It encourages reviews by reminding the customer by email and by streamlining the review process.
* It keeps product reviews fully native to WooCommerce and WordPress.
* It lets you collect separate shop-experience reviews without mixing them into product reviews.
* It gives you a path for custom timing logic, such as sending review requests after delivery instead of after order completion.

Luma Reviews Plus does not replace WooCommerce reviews, and in v1 it does not send review data to Trustpilot, Google, or other third-party platforms.

== Installation ==

1. Upload the plugin to the plugins directory.
2. Activate the plugin through the WordPress admin.
3. Make sure WooCommerce is active.
4. Configure settings under WooCommerce > Settings > Products > Reviews Plus.
5. Configure the review request email under WooCommerce > Settings > Emails.

== Frequently Asked Questions ==

= How do I show published shop reviews on the frontend? =

Use the `[luma_shop_reviews_summary]` shortcode on a page, post, or shortcode-enabled block area. You can also control the output with attributes such as `style`, `show_quotes`, `quote_count`, and `minimum_rating`.

= Does this replace normal WooCommerce product reviews? =

No. Product reviews are still stored as native WordPress comments with WooCommerce-compatible review metadata.

= Can customers review more than one product from the same order? =

Yes. The review page is tied to one order and can show multiple purchased products.

= Does the plugin also collect a shop review? =

Yes. Customers can optionally leave a separate shop-experience review in addition to product reviews.

= Can I adapt review timing for delivery-based flows? =

Yes. The plugin is designed with hooks so a site-specific addon can adjust timing, for example by scheduling review requests after package delivery.

= Can staff see whether the customer reviewed the previous order? =

Yes. You can optionally enable an admin order-page flag under WooCommerce > Settings > Products > Reviews Plus. The plugin also exposes helper functions and filter hooks so other admin-side plugins can reuse the same message.

Example:

`$message = function_exists( 'luma_reviews_plus_get_order_review_flag_message' ) ? luma_reviews_plus_get_order_review_flag_message( $order ) : '';`

`if ( '' !== $message ) { echo '<div class="notice inline notice-info"><p>' . esc_html( $message ) . '</p></div>'; }`

== Changelog ==

= 0.3.4 =

* Added an optional WooCommerce admin order-page review flag for customers who reviewed the previous order.
* Added a public helper/filter contract so companion plugins and other admin tools can reuse the review flag message.

= 0.3.3 =

* Added an optional WooCommerce admin order-page review flag for customers who reviewed the previous order.
* Added a public helper/filter contract so companion plugins and other admin tools can reuse the review flag message.
* Added extension hooks so site-specific addons can persist custom settings in the shared settings group.
* Added support for Fru Kvist delivery-based review scheduling through the companion addon contract.

= 0.3.2 =

* Relaxed shop-review display-name matching so customers with multiple first names only need one given name.
* Ignored one-letter and punctuated one-letter first-name fragments when validating shop-review display names.

= 0.2.0 =

* Added public shop-review summary styling modes: `none` and `minimal`.
* Added conditional loading for the public summary stylesheet.
* Refined the frontend summary markup for easier theme styling.
* Added simple star rendering, warmer summary copy, and human-readable relative dates for quotes.
* Documented frontend shortcode usage and parameters.

= 0.1.0 =

* First implementation pass of Luma Reviews Plus.
* Tokenized review-request flow for WooCommerce orders.
* Native WooCommerce product review creation.
* Separate shop-experience reviews and admin moderation.
* Review email and scheduler foundation with extension hooks.
