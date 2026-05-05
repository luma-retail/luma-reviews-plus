=== Luma Reviews Plus ===
Contributors: terjejohansen
Tags: woocommerce, reviews, email, trust
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
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

= Does this replace normal WooCommerce product reviews? =

No. Product reviews are still stored as native WordPress comments with WooCommerce-compatible review metadata.

= Can customers review more than one product from the same order? =

Yes. The review page is tied to one order and can show multiple purchased products.

= Does the plugin also collect a shop review? =

Yes. Customers can optionally leave a separate shop-experience review in addition to product reviews.

= Can I adapt review timing for delivery-based flows? =

Yes. The plugin is designed with hooks so a site-specific addon can adjust timing, for example by scheduling review requests after package delivery.

== Changelog ==

= 0.1.0 =

* First implementation pass of Luma Reviews Plus.
* Tokenized review-request flow for WooCommerce orders.
* Native WooCommerce product review creation.
* Separate shop-experience reviews and admin moderation.
* Review email and scheduler foundation with extension hooks.
