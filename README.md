# Luma Reviews Plus

Luma Reviews Plus helps WooCommerce stores ask for better reviews in a way that feels more relevant to the customer. Instead of sending a generic follow-up, it sends the customer to a review page tied to the exact order they placed, so they can review the products they actually bought and optionally share how the shopping experience felt overall.

The goal is simple: make it easier to collect useful, trustworthy feedback without replacing WooCommerce's normal product review system. Customers get a cleaner review flow, while the store gets product reviews, separate shop-experience feedback, and a better foundation for trust-building on the storefront.

This plugin is a good fit if you want more control than a basic follow-up email, but do not want to push your reviews into a third-party platform. It keeps product reviews native to WooCommerce, stores shop-experience reviews separately, and supports site-specific extensions for stores with custom delivery or fulfillment logic.

## Why Use This Plugin

- It encourages reviews by reminding the customer by email, and by streamlining the review process.
- It lets you collect separate shop-experience reviews without mixing them into product reviews.
- It keeps product reviews fully native to WooCommerce and WordPress.
- It supports verified, tokenized review links without requiring customer login.
- It gives you a path for store-specific timing logic, such as sending review requests after delivery instead of after order completion.

## What It Does

- Sends WooCommerce review request emails with a secure review link.
- Shows a public review page for one specific order.
- Lets customers review one or more purchased products from the same page.
- Saves product reviews as normal WooCommerce reviews.
- Prevents duplicate product reviews for the same order item.
- Saves a separate shop-experience review with rating, comment, tags, and consent.
- Includes an admin screen for moderating shop reviews.
- Includes an optional setting to auto-approve new shop-experience reviews for public display.
- Can optionally show a WooCommerce admin order-page flag when the customer reviewed the previous order.
- Includes a shortcode for showing aggregate shop-review trust content on the frontend.

## Show Reviews On The Frontend

The easiest way to show published shop reviews on the frontend is the built-in shortcode:

```text
[luma_shop_reviews_summary]
```

You can place it in any post, page, or block that accepts shortcodes.

Common shortcode examples:

```text
[luma_shop_reviews_summary]
[luma_shop_reviews_summary style="none"]
[luma_shop_reviews_summary style="minimal" quote_count="6"]
[luma_shop_reviews_summary show_quotes="no"]
[luma_shop_reviews_summary featured_only="yes" quote_count="5"]
[luma_shop_reviews_summary quote_count="24" load_more_count="24"]
```

Available shortcode attributes:

- `style`: `inherit`, `none`, or `minimal`
- `show_rating`: `yes` or `no`
- `show_count`: `yes` or `no`
- `show_quotes`: `yes` or `no`
- `quote_count`: how many published quotes to show
- `minimum_rating`: minimum rating required for quotes to appear
- `featured_only`: `yes` or `no` (show only featured shop reviews)
- `show_more`: `yes` or `no` (enable AJAX load-more button when more quotes exist)
- `load_more_count`: how many extra quotes to load each click (disabled unless set to a value greater than `0`)

How styling works:

- `inherit` uses the plugin setting under WooCommerce > Settings > Products > Reviews Plus.
- `none` loads no plugin CSS for the summary, so your theme handles all styling.
- `minimal` loads a lightweight summary stylesheet only on pages where the shortcode is used.

The public summary styles are loaded conditionally, so pages that do not render the shortcode do not load the summary stylesheet.

## What It Does Not Try To Do

- It does not replace WooCommerce reviews.
- It does not send review data to Trustpilot, Google, or other third parties in v1.
- It does not include photo reviews, replies, advanced analytics, or public review archives in v1.

## Product Roadmap

- Structured data on the frontend.
- Statistics.
- Customer image uploads.
- Consider slider or other way to show more quotes in the summary without taking too much space.

## Typical Use Case

1. A WooCommerce order reaches an eligible status.
2. The plugin schedules a review request email.
3. The customer receives a tokenized link to a dedicated review page.
4. The customer reviews one or more purchased products.
5. The customer can also leave a separate rating for the shopping experience.
6. Product reviews appear through WooCommerce as usual, while shop reviews are stored separately for trust summaries and admin moderation.

## Technical Overview

- Platform: WordPress + WooCommerce
- Plugin type: WooCommerce extension
- Namespace: `Luma\ReviewsPlus`
- Main plugin file: [luma-reviews-plus.php](luma-reviews-plus.php)
- WordPress readme: [readme.txt](readme.txt)

Core implementation areas:

- Bootstrap and wiring: [includes/Plugin.php](includes/Plugin.php)
- Settings: [includes/Settings/Settings.php](includes/Settings/Settings.php)
- Activation and tables: [includes/Activation/Activator.php](includes/Activation/Activator.php), [includes/Database/TableManager.php](includes/Database/TableManager.php)
- Email and scheduling: [includes/Email/ReviewEmail.php](includes/Email/ReviewEmail.php), [includes/Email/ReviewEmailScheduler.php](includes/Email/ReviewEmailScheduler.php)
- Frontend review flow: [includes/Frontend/ReviewPageController.php](includes/Frontend/ReviewPageController.php)
- Product and shop review handling: [includes/Reviews/ProductReviewHandler.php](includes/Reviews/ProductReviewHandler.php), [includes/Reviews/ShopReviewHandler.php](includes/Reviews/ShopReviewHandler.php)
- Admin moderation: [includes/Admin/ShopReviewsPage.php](includes/Admin/ShopReviewsPage.php)

## Data Model

The plugin uses a mixed storage model:

- Product reviews are stored as native WordPress comments with WooCommerce review metadata.
- Shop-experience reviews are stored in a dedicated custom table.
- Review tokens are stored in a dedicated custom table.
- Duplicate review protection per order item is stored in a dedicated custom table.

Review tokens are not stored in order meta. The plugin stores a hashed token in the `wp_luma_review_tokens` table and only keeps send-related references on the order, such as the sent timestamp and token row id.

This keeps WooCommerce compatibility for product reviews while still allowing a separate trust-focused review layer for the store experience.

## Testing Email Sends

- Set the review email delay to `0` if you want the request queued for immediate processing.
- Use the WooCommerce order action `Send review request` when you want to trigger a test email for a specific order right away.
- Email preview tools may not show a real review link, because the tokenized URL is generated during the actual send flow for a real order.

## Extensibility

The plugin is designed to support store-specific addons.

Notable extension points include:

- `luma_reviews_plus_get_order_review_flag_data( $order )`
- `luma_reviews_plus_get_order_review_flag_message( $order )`
- `luma_reviews_plus_order_review_flag_data`
- `luma_reviews_plus_order_review_flag_message`
- `luma_reviews_plus_settings_fields`
- `luma_reviews_plus_eligible_order_statuses`
- `luma_reviews_plus_reviewable_order_items`
- `luma_reviews_plus_should_schedule_review_request`
- `luma_reviews_plus_review_request_schedule_timestamp`
- `luma_reviews_plus_review_request_scheduled`
- `luma_reviews_plus_review_request_unscheduled`

This makes it possible to build addons that adjust review timing based on delivery events, custom logistics flows, or store-specific fulfillment rules.

It also makes it possible to build admin-side integrations, such as picklists or warehouse screens, that can show whether the customer reviewed the previous order.

Example:

```php
$message = function_exists( 'luma_reviews_plus_get_order_review_flag_message' )
	? luma_reviews_plus_get_order_review_flag_message( $order )
	: '';

if ( '' !== $message ) {
	echo '<div class="notice inline notice-info"><p>' . esc_html( $message ) . '</p></div>';
}
```

## Installation

1. Copy the plugin into your WordPress plugins directory.
2. Activate it in WordPress.
3. Make sure WooCommerce is active.
4. Configure plugin settings under WooCommerce > Settings > Products > Reviews Plus.
5. Configure the review request email under WooCommerce > Settings > Emails.

## Current Status

This repository contains the first implementation pass of the plugin structure and core flow described in the developer specification. It is intended for further runtime testing inside a real WordPress + WooCommerce environment.