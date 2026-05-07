# DEVELOPER.md — Luma Reviews Plus

## Package identity

**Plugin name:** Luma Reviews Plus  
**Slug:** `luma-reviews-plus`  
**Text domain:** `luma-reviews-plus`  
**Namespace:** `Luma\ReviewsPlus`  
**Target platform:** WordPress + WooCommerce  
**Primary use case:** Order-specific product review collection and verified shop-experience reviews.

Luma Reviews Plus extends WooCommerce reviews without replacing WooCommerce’s native review model. Product reviews must continue to behave as normal WooCommerce/WP reviews, while the plugin adds a tokenized order-review flow and a separate shop-experience review layer.

---

## Core principle

Use WooCommerce/WP review rules wherever possible.

Product reviews must be stored as native WordPress comments with WooCommerce-compatible review metadata. The plugin should not create a parallel product review system.

Shop-experience reviews are separate from product reviews and should be stored in a dedicated custom table.

---

## Responsibilities

Luma Reviews Plus is responsible for:

- Generating secure tokenized review links for WooCommerce orders.
- Rendering an order-specific review page listing products from the order.
- Allowing customers to review one or more purchased products from a single page.
- Storing product reviews as native WooCommerce product reviews.
- Preventing duplicate product reviews for the same order item.
- Capturing an optional verified shop-experience rating and comment.
- Storing shop-experience reviews separately from product reviews.
- Providing admin moderation and overview for shop-experience reviews.
- Providing frontend display tools for aggregate shop-experience trust signals.

Luma Reviews Plus is not responsible for:

- Replacing WooCommerce’s product review system.
- Replacing WordPress comments.
- Publishing every shop-experience review automatically.
- Sending customer/order data to third-party review platforms.
- Managing Google, Trustpilot, or other external review integrations in V1.
- Implementing photo reviews, NPS, review replies, or advanced analytics in V1.

---

## V1 scope

### Included

- Tokenized review link per order.
- Order-specific public review page.
- Product review forms for products purchased in that order.
- Optional shop-experience review section.
- Optional consent checkbox for public use of shop-experience comment.
- Product reviews stored as native WooCommerce reviews.
- Shop-experience reviews stored in a custom table.
- Duplicate protection per order item.
- Basic admin overview for shop-experience reviews.
- Shortcode or block-render callback for public trust display.

### Excluded

- Trustpilot integration.
- Google Customer Reviews integration.
- Google Business Profile API integration.
- Photo/video reviews.
- Public review archive.
- Review replies.
- Review voting/helpfulness.
- NPS scoring.
- Automated sentiment analysis.
- Advanced analytics dashboard.
- Multi-language review translation.

---

## Recommended plugin structure

```text
luma-reviews-plus/
├── luma-reviews-plus.php
├── includes/
│   ├── Plugin.php
│   ├── Activation/
│   │   └── Activator.php
│   ├── Admin/
│   │   ├── ShopReviewsPage.php
│   │   └── SettingsPage.php
│   ├── Settings/
│   │   └── Settings.php
│   ├── Database/
│   │   ├── TableManager.php
│   │   ├── TokenRepository.php
│   │   ├── ProductReviewLogRepository.php
│   │   └── ShopReviewRepository.php
│   ├── Email/
│   │   ├── ReviewEmail.php
│   │   ├── ReviewEmailScheduler.php
│   │   └── ReviewLinkGenerator.php
│   ├── Frontend/
│   │   ├── ReviewPageController.php
│   │   ├── ReviewFormRenderer.php
│   │   └── PublicTrustRenderer.php
│   ├── Reviews/
│   │   ├── ProductReviewHandler.php
│   │   └── ShopReviewHandler.php
│   └── Utils/
│       ├── Helpers.php
│       └── Sanitizer.php
├── templates/
│   ├── review-page.php
│   ├── product-review-item.php
│   └── shop-experience-review.php
├── assets/
│   ├── css/
│   │   └── frontend.css
│   └── js/
│       └── frontend.js
├── languages/
└── readme.txt
```

---

## Bootstrap requirements

The main plugin file should:

- Define plugin constants.
- Register the activation hook.
- Load the Composer/autoloader or custom class loader if used.
- Initialize the plugin after WooCommerce is available.
- Fail gracefully if WooCommerce is inactive.

Recommended constants:

```php
LUMA_REVIEWS_PLUS_VERSION
LUMA_REVIEWS_PLUS_FILE
LUMA_REVIEWS_PLUS_PATH
LUMA_REVIEWS_PLUS_URL
LUMA_REVIEWS_PLUS_BASENAME
```

Recommended initialization hook:

```php
add_action( 'plugins_loaded', [ $plugin, 'init' ] );
```

Do not load translated strings too early. Any code that calls translation functions should run after WordPress has loaded plugin text domains.

---

## Coding standards

Use:

- OOP.
- Namespaces.
- Small classes with explicit responsibilities.
- Named methods instead of anonymous callbacks where practical.
- WordPress escaping, sanitization, nonce, and capability checks.
- WooCommerce APIs where available.
- Docblocks for all classes and public methods.

Class docblocks must include a `Responsibilities:` section.

Example:

```php
namespace Luma\ReviewsPlus\Reviews;

/**
 * Handles creation of WooCommerce product reviews from verified order review submissions.
 *
 * Responsibilities:
 * - Validate product review submissions from tokenized order review pages.
 * - Create native WordPress comment records for WooCommerce product reviews.
 * - Store WooCommerce-compatible rating metadata.
 * - Prevent duplicate reviews for the same order item.
 */
class ProductReviewHandler {
}
```

Use two blank lines between methods.

Avoid unnecessary inline comments. Prefer clear method names and small methods.

---

## Settings architecture

The plugin must have a dedicated settings class.

Recommended class:

```php
namespace Luma\ReviewsPlus\Settings;

/**
 * Provides typed access to Luma Reviews Plus settings.
 *
 * Responsibilities:
 * - Register plugin settings with WooCommerce/WordPress.
 * - Provide defaults for all configurable behavior.
 * - Return sanitized, typed setting values to other classes.
 * - Centralize option keys so business logic does not read raw options directly.
 */
class Settings {
}
```

Business logic must not call `get_option()` directly except inside the settings class or a narrow repository/helper owned by it. Other classes should depend on `Settings` methods such as:

```php
is_review_requests_enabled(): bool
get_review_email_delay_days(): int
get_token_expiry_days(): int
get_review_page_slug(): string
get_review_page_heading(): string
get_review_page_intro(): string
get_product_reviews_heading(): string
get_shop_review_heading(): string
get_shop_review_intro(): string
is_product_review_comment_required(): bool
should_auto_approve_product_reviews(): bool
get_public_display_name_mode(): string
```

To support site-specific addons, the settings layer may also expose one narrow generic accessor for extension-owned keys, for example:

```php
get_setting( string $key, $default = null )
```

Core plugin code should still prefer typed getters for core settings.

### Recommended option key

Use one grouped option:

```text
luma_reviews_plus_settings
```

Storing settings as one option keeps the plugin easier to export, inspect, and migrate.

The settings registration should be extensible so a site-specific addon can register extra fields into the same grouped option without patching core.

Recommended filter:

```php
apply_filters( 'luma_reviews_plus_settings_fields', $fields )
```

This is the intended path for later extensions such as delivery-aware review timing settings.

### Core settings

V1 settings should include:

```text
Enable review requests
Days after completed order before review email is sent
Token expiry in days
Review page slug
Review page heading
Review page introduction text
Product reviews section heading
Product reviews section description
Shop-experience section heading
Shop-experience section description
Shop-experience tags
Require product review comment
Auto-approve product reviews
Allow shop-experience review without product review
Allow partial product review submissions
Public display name mode
```

Recommended defaults:

```text
Enable review requests: yes
Email delay: 5 days after completed order
Token expiry: 90 days
Review page slug: vurdering
Require product review comment: no
Auto-approve product reviews: no
Allow shop-experience review without product review: yes
Allow partial product review submissions: yes
Public display name mode: first_name_only
```

### Landing-page text settings

The review landing page copy must be editable without code changes.

Minimum editable strings:

```text
Page heading
Page intro
Product reviews heading
Product reviews intro
Shop-experience heading
Shop-experience intro
Submit button text
Success message
Expired token message
Invalid token message
All items already reviewed message
Public consent text
```

Use `wp_kses_post()` for rich text settings that render as body copy. Use `sanitize_text_field()` for headings, button labels, and short messages.

### Settings page location

Recommended location:

```text
WooCommerce → Settings → Products → Reviews Plus
```

This is the primary settings location for review behavior, landing-page text, token behavior, and public display behavior.

Email content should not primarily live here if the plugin implements a WooCommerce email class. It should live under WooCommerce email settings.

---

## WooCommerce email integration

The review request email should be implemented as a WooCommerce email class where practical.

Recommended class:

```php
namespace Luma\ReviewsPlus\Email;

use WC_Email;

/**
 * Sends tokenized order-review request emails.
 *
 * Responsibilities:
 * - Register a WooCommerce-compatible email type for review requests.
 * - Provide editable subject, heading, and body through WooCommerce email settings.
 * - Generate and inject the order-specific review link.
 * - Respect WooCommerce email enable/disable and template behavior.
 */
class ReviewEmail extends WC_Email {
}
```

Register the email through:

```php
add_filter( 'woocommerce_email_classes', [ $this, 'register_review_email' ] );
```

The WooCommerce email settings should control:

```text
Enable/disable this email
Subject
Email heading
Body text
Email type/html template where applicable
```

The plugin settings should control *when* the email is sent and how long the token remains valid. The WooCommerce email settings should control the email content.

### Email placeholders

Supported placeholders should include:

```text
{first_name}
{order_number}
{review_link}
{review_button}
{site_title}
```

`{review_button}` should render a WooCommerce-style button in HTML emails and a plain URL in plain-text emails.

### Email templates

Recommended template files:

```text
templates/emails/review-request.php
templates/emails/plain/review-request.php
```

Allow theme overrides using WooCommerce's normal template override pattern if feasible:

```text
yourtheme/woocommerce/emails/review-request.php
```

Do not bypass WooCommerce email settings for subject, heading, or body unless there is a deliberate compatibility reason.

---

## Security requirements

### Token security

Review links must use long random tokens.

Do not store raw tokens in the database. Store only a hash.

Recommended approach:

- Generate token with `wp_generate_password( 48, false, false )` or `random_bytes()`.
- Store `hash_hmac( 'sha256', $token, wp_salt( 'auth' ) )`.
- Send raw token only in the email URL.
- Compare hashes using `hash_equals()`.

Tokenized links should be treated as limited-purpose access credentials.

### Public review page must not expose sensitive order data

The tokenized page may show:

- Product name.
- Product image.
- Variation attributes relevant to the purchased item.
- Purchase date if useful.

The tokenized page must not show:

- Billing address.
- Shipping address.
- Phone number.
- Full email address.
- Payment method details.
- Order total unless explicitly needed.
- Private order notes.

### Nonces

All review submission forms must include a nonce.

The nonce does not replace token validation. Both must pass.

### Capabilities

Admin pages and actions must be protected with capability checks.

Recommended capability:

```php
manage_woocommerce
```

### Sanitization and escaping

Input must be sanitized before storage.

Output must be escaped at render time.

Use:

- `sanitize_text_field()` for short text.
- `sanitize_textarea_field()` for review comments unless richer formatting is deliberately supported.
- `absint()` for IDs and ratings.
- `wp_unslash()` before sanitizing superglobal values.
- `esc_html()`, `esc_attr()`, `esc_url()`, and `wp_kses_post()` depending on context.

---

## Database tables

Use custom tables for token management, product-review duplicate tracking, and shop-experience reviews.

Use the site prefix via `$wpdb->prefix` unless multisite/network-wide behavior is explicitly implemented later.

### `{$wpdb->prefix}luma_review_tokens`

Purpose: stores review tokens connected to WooCommerce orders.

Recommended fields:

```sql
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
order_id bigint(20) unsigned NOT NULL,
customer_id bigint(20) unsigned NULL,
token_hash varchar(128) NOT NULL,
status varchar(32) NOT NULL DEFAULT 'pending',
expires_at datetime NULL,
used_at datetime NULL,
created_at datetime NOT NULL,
last_sent_at datetime NULL,
PRIMARY KEY  (id),
KEY order_id (order_id),
KEY customer_id (customer_id),
UNIQUE KEY token_hash (token_hash)
```

Valid statuses:

```text
pending
partially_reviewed
completed
expired
disabled
```

A token should not necessarily be marked `completed` after the first submission. Customers may review only part of the order first.

### `{$wpdb->prefix}luma_order_product_reviews`

Purpose: prevents duplicate product reviews for the same order item.

Recommended fields:

```sql
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
order_id bigint(20) unsigned NOT NULL,
order_item_id bigint(20) unsigned NOT NULL,
product_id bigint(20) unsigned NOT NULL,
variation_id bigint(20) unsigned NULL,
review_comment_id bigint(20) unsigned NOT NULL,
created_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY order_item_id (order_item_id),
KEY order_id (order_id),
KEY product_id (product_id),
KEY review_comment_id (review_comment_id)
```

Use `order_item_id` as the primary duplicate-protection key.

### `{$wpdb->prefix}luma_shop_reviews`

Purpose: stores verified shop-experience reviews.

Recommended fields:

```sql
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
order_id bigint(20) unsigned NOT NULL,
customer_id bigint(20) unsigned NULL,
rating tinyint(1) unsigned NOT NULL,
comment text NULL,
tags_json longtext NULL,
public_consent tinyint(1) unsigned NOT NULL DEFAULT 0,
display_name varchar(100) NULL,
display_location varchar(100) NULL,
approved_for_public_display tinyint(1) unsigned NOT NULL DEFAULT 0,
created_at datetime NOT NULL,
updated_at datetime NULL,
PRIMARY KEY  (id),
UNIQUE KEY order_id (order_id),
KEY customer_id (customer_id),
KEY rating (rating),
KEY approved_for_public_display (approved_for_public_display)
```

Only one shop-experience review should be stored per order in V1.

---

## Activation and upgrades

Use `dbDelta()` for table creation and schema upgrades.

Store the installed schema/plugin version in an option:

```php
luma_reviews_plus_db_version
```

Activation must:

- Create required tables.
- Register rewrite rules if the review page uses a custom endpoint.
- Flush rewrite rules only when necessary.

Runtime upgrade checks should be conservative and not run expensive database work on every request.

---

## Review link flow

### Email link

The review email should include a single review link:

```text
https://example.com/review-order/?token=<raw-token>
```

The path may be configurable.

### Page load

On page load:

1. Read token from query variable.
2. Sanitize token.
3. Hash token.
4. Look up token hash.
5. Validate status and expiry.
6. Load WooCommerce order.
7. Confirm order status is eligible.
8. Render only reviewable order items.

### Eligible order statuses

Default eligible statuses:

```text
completed
```

Allow this to be filtered:

```php
apply_filters( 'luma_reviews_plus_eligible_order_statuses', [ 'completed' ] );
```

For stores using custom shipping/order flows, `processing` may be useful, but V1 should default to `completed`.

---

## Product review behavior

Product reviews must be stored as native WordPress comments compatible with WooCommerce.

Use:

```php
wp_insert_comment()
update_comment_meta( $comment_id, 'rating', $rating )
update_comment_meta( $comment_id, 'verified', 1 )
```

Also store plugin-specific metadata:

```php
update_comment_meta( $comment_id, '_luma_reviews_plus_order_id', $order_id )
update_comment_meta( $comment_id, '_luma_reviews_plus_order_item_id', $order_item_id )
update_comment_meta( $comment_id, '_luma_reviews_plus_token_id', $token_id )
```

Respect WooCommerce/WordPress review approval settings.

Do not force approval unless this is an explicit plugin setting.

### Review fields

V1 product review fields:

- Rating: required if a product review is submitted.
- Comment: optional or required based on setting.
- Reviewer name: derived from order/customer by default.
- Reviewer email: derived from order/customer by default.

A product review should only be created for an order item if a rating exists.

### Variations

If the purchased item is a variation:

- Store the review on the parent product ID unless WooCommerce behavior or store policy explicitly says otherwise.
- Store variation ID as comment meta and duplicate-tracking data.
- Display variation attributes on the review page to help the customer identify what they bought.

### Bundles and composite products

V1 should avoid over-complex bundle behavior.

Recommended default:

- Show top-level purchasable order items.
- Hide child/bundled line items when they are clearly children of a container product.
- Add filters so store-specific bundle logic can be customized.

Recommended filter:

```php
apply_filters( 'luma_reviews_plus_reviewable_order_items', $items, $order )
```

---

## Shop-experience review behavior

The shop-experience section is optional.

A shop-experience review should be saved only if a rating is submitted.

V1 fields:

- Rating, 1–5 stars.
- Optional comment.
- Optional tags.
- Optional public consent.
- Optional display name.
- Display location derived from the order billing city.

Suggested tags:

```text
Rask levering
Pent pakket
Riktig lagerstatus
God kundeservice
God hjelp/rådgivning
Lett å handle
Annet
```

Tags should be stored as JSON after validation against an allowed list.

### Public display

Public display requires:

- Customer consent.
- Admin approval.

Aggregate score may be calculated from all verified shop-experience reviews, not only publicly approved comments.

Public quote display should use only approved comments with consent.

---

## Frontend UX requirements

The review page should:

- Be simple and fast.
- Work well on mobile.
- Clearly separate product reviews from shop-experience review.
- Allow partial completion.
- Avoid requiring login.
- Avoid exposing private order/customer data.

Submission rules:

- Accept form if at least one valid review/rating is submitted.
- Save product reviews only for products with submitted rating.
- Save shop review only if shop rating is submitted.
- Do not require all products to be reviewed.
- Do not make shop-experience review required.

Recommended headings:

```text
Hvordan var produktene du kjøpte?
Hvordan var handleopplevelsen hos Fru Kvist?
```

---

## Email behavior

Luma Reviews Plus should provide its own WooCommerce email class for review requests, unless the store deliberately wires the scheduler into an existing email system.

V1 should support:

- Review email delay setting.
- Review link generation.
- Preventing repeated sends unless explicitly allowed.
- WooCommerce-native editable email subject, heading, and body.
- Manual resend from order admin if useful.

Recommended delay:

```text
5 days after order completion
```

If exact delivery date is unavailable, use configurable delay after order completion.

The default core behavior is delay-after-status. However, the architecture must allow a site-specific addon to override the timing source and use a later fulfillment event such as package delivery.

Default token expiry:

```text
90 days
```

### Scheduling behavior

When an order reaches an eligible status, schedule a single review-request job based on the configured delay.

Recommended mechanism:

```text
Action Scheduler
```

WooCommerce includes Action Scheduler, so this is preferable to raw WP-Cron events.

Suggested action:

```php
luma_reviews_plus_send_review_request_email
```

The scheduled action should:

1. Load the order.
2. Confirm the order is still eligible.
3. Confirm review requests are enabled.
4. Confirm no review request has already been sent for this order unless resend is explicit.
5. Create or reuse a valid token.
6. Trigger the WooCommerce review request email.
7. Store sent timestamp on token/order meta.

Recommended order meta:

```text
_luma_reviews_plus_review_request_sent_at
_luma_reviews_plus_review_request_token_id
```

Do not send review requests for refunded, cancelled, failed, or trashed orders.

### Scheduling extensibility

The scheduler contract must support two extension patterns:

1. Simple override before scheduling, where an addon can change or suppress the initial timestamp when the needed information is already known at order-status time.
2. Deferred scheduling after a later event, where an addon can schedule the review request when an external system reports the real trigger event, such as delivered package status from Bring.

Recommended core scheduler responsibilities:

- Own the Action Scheduler hook name and group.
- Provide a stable way to schedule, unschedule, and inspect the pending review-request job for an order.
- Fire an action after scheduling so addons can observe or replace the default timing.

Recommended scheduler methods:

```php
schedule_review_request( int $order_id, int $timestamp, string $reason = 'default' )
unschedule_review_request( int $order_id )
is_review_request_scheduled( int $order_id )
```

Recommended stable Action Scheduler contract:

```php
hook: luma_reviews_plus_send_review_request_email
args: [ 'order_id' => $order_id ]
group: luma_reviews_plus
```

Recommended pre-schedule filters:

```php
apply_filters( 'luma_reviews_plus_should_schedule_review_request', true, $order )
apply_filters( 'luma_reviews_plus_review_request_schedule_timestamp', $timestamp, $order, 'default' )
```

Recommended post-schedule action:

```php
do_action( 'luma_reviews_plus_review_request_scheduled', $order_id, $job_id, $timestamp, 'default' )
```

Recommended unschedule action:

```php
do_action( 'luma_reviews_plus_review_request_unscheduled', $order_id, $job_id, $reason )
```

For a Bring-style addon, the expected flow is:

1. Detect that the order should not use the default completed-order delay.
2. Return `false` from `luma_reviews_plus_should_schedule_review_request` or unschedule the default job after `luma_reviews_plus_review_request_scheduled`.
3. Wait for a delivery event from the external integration.
4. Schedule a new `luma_reviews_plus_send_review_request_email` action for `delivery_date + configured_delay` using the stable scheduler API.

This extension path should be treated as a supported public contract, not an internal implementation detail.

---

## Admin UI

### Settings page

Recommended location:

```text
WooCommerce → Settings → Products → Reviews Plus
```

The settings page should handle review behavior, landing-page text, token behavior, public display behavior, and shop-review tags.

Email subject, heading, and body should be configured through the WooCommerce email settings for the custom review-request email:

```text
WooCommerce → Settings → Emails → Review request
```

### Shop reviews page

Recommended location:

```text
WooCommerce → Reviews Plus → Shop Reviews
```

or as a submenu under WooCommerce:

```text
WooCommerce → Shop Reviews
```

Columns:

```text
Date
Order
Customer
Rating
Tags
Comment
Public consent
Public approved
Actions
```

Actions:

```text
Approve for public display
Remove public approval
View order
View token/review source
Delete review
```

Deletion should require nonce + capability check.

---

## Public display tools

V1 should include a shortcode or render callback for public trust sections.

Possible shortcode:

```text
[luma_shop_reviews_summary]
```

Attributes:

```text
show_rating="yes"
show_count="yes"
show_quotes="yes"
quote_count="3"
minimum_rating="4"
```

### Public summary styling contract

Public summary styling must use a mode-based contract rather than a simple boolean flag.

V1 setting:

```text
Key: public_summary_style
Location: WooCommerce → Settings → Products → Reviews Plus
Label: Public summary style
Type: select
Options: none, minimal
Default: minimal
```

V1 shortcode attribute:

```text
style="inherit"
style="none"
style="minimal"
```

Shortcode style resolution rules:

- Default shortcode value is `inherit`.
- If the shortcode provides `style`, that resolved mode wins.
- If the shortcode uses `inherit`, the plugin setting controls the mode.
- V1 must only ship the `none` and `minimal` style modes.
- The architecture must allow additional style modes later without changing option storage shape.

Runtime asset-loading rules:

- Public summary styles must live in a dedicated stylesheet separate from the review-page frontend stylesheet.
- No public summary stylesheet should be loaded on requests where the public summary shortcode is not used.
- If the resolved style mode is `none`, no public summary stylesheet should be enqueued.
- If the resolved style mode is `minimal`, only the dedicated minimal summary stylesheet should be enqueued.
- Shortcode presence should be detected early for normal page/post content so styles can load before output.
- The shortcode renderer may still enqueue the resolved stylesheet as a fallback for non-standard render paths.

V1 styling intent:

- `none` means no plugin CSS for the public summary.
- `minimal` means light structural styling only, with the active theme doing most of the visual work.
- V1 should avoid opinionated decorative styling for the public summary.

The renderer should support theme overrides later, but V1 may use a template file.

Recommended frontend copy pattern:

```text
Trygg netthandel hos Fru Kvist

Kundene våre vurderer netthandelen hos Fru Kvist til 4,9 / 5.
Basert på bekreftede kjøpsopplevelser.
```

Do not imply that shop-experience ratings are Google reviews, product reviews, or third-party reviews.

---

## Hooks and filters

Provide extension points early.

Recommended filters:

```php
luma_reviews_plus_settings_fields
luma_reviews_plus_eligible_order_statuses
luma_reviews_plus_reviewable_order_items
luma_reviews_plus_token_expiry_days
luma_reviews_plus_review_page_url
luma_reviews_plus_product_review_comment_required
luma_reviews_plus_shop_review_tags
luma_reviews_plus_public_summary_data
luma_reviews_plus_should_schedule_review_request
luma_reviews_plus_review_request_schedule_timestamp
```

Recommended actions:

```php
luma_reviews_plus_token_created
luma_reviews_plus_review_request_scheduled
luma_reviews_plus_review_request_unscheduled
luma_reviews_plus_review_email_sent
luma_reviews_plus_product_review_created
luma_reviews_plus_shop_review_created
luma_reviews_plus_token_completed
```

---

## Privacy and consent

The plugin must not send review data to external platforms in V1.

For public shop-experience testimonials:

- Ask for explicit consent.
- Store consent state.
- Allow admin approval before display.
- Display the chosen display name and, when available, the order billing city unless otherwise configured.

Default public display name should not expose full customer identity.

Recommended consent text:

```text
Fru Kvist kan vise kommentaren min på nettsiden. Den vises kun med fornavn og eventuelt sted.
```

---

## WooCommerce compatibility

The plugin must respect WooCommerce review behavior.

Product reviews should work with:

- Native WooCommerce review display.
- Native average rating calculation.
- Native review count.
- Native moderation settings.
- Existing product review schema where applicable.

After inserting product reviews, ensure WooCommerce product rating transients/cache are updated if needed.

Useful WooCommerce functions/actions may include:

```php
wc_get_order()
wc_get_product()
wc_review_ratings_enabled()
wc_update_product_lookup_tables()
```

Avoid manually recalculating WooCommerce internals unless necessary.

---

## Error states

Handle these states clearly on the frontend:

- Missing token.
- Invalid token.
- Expired token.
- Disabled token.
- Order not found.
- Order not eligible for review.
- No reviewable products.
- All products already reviewed.

Use customer-friendly messages. Do not expose internal IDs or technical details.

---

## Logging

Use WooCommerce logger if available.

Recommended helper:

```php
wc_get_logger()
```

Recommended log source:

```text
luma-reviews-plus
```

Log important failures:

- Token validation failure where useful.
- Review insertion failure.
- Email scheduling/sending failure.
- Database schema upgrade failure.

Do not log raw tokens.

---

## Testing checklist

### Token flow

- Token is generated for eligible order.
- Raw token is never stored.
- Invalid token fails.
- Expired token fails.
- Disabled token fails.
- Valid token loads order review page.
- Token page does not expose private order data.

### Product reviews

- Product review is created as native WooCommerce review.
- Rating is stored correctly.
- Review appears on product page according to moderation settings.
- Duplicate review for same order item is blocked.
- Partial product review submission works.
- Variation purchase displays correctly.
- Review author/name/email are derived safely from order data.

### Shop reviews

- Shop review saves separately from product reviews.
- Only one shop review is saved per order.
- Tags are validated against allowed tags.
- Consent is stored correctly.
- Public approval is required before quote display.
- Aggregate score includes verified shop reviews as intended.

### Admin

- Settings require correct capability.
- Admin actions require nonce.
- Settings are sanitized correctly before storage.
- Settings class returns typed values with defaults.
- Landing-page text settings render safely.
- WooCommerce review-request email settings are editable.
- Shop review list renders safely.
- Public approval can be toggled.
- Order links work.

### Frontend

- Review page works on mobile.
- Empty submission is rejected.
- At least one submitted rating is accepted.
- Already-reviewed products are not shown again.
- Customer can submit shop review without product reviews.
- Customer can submit product reviews without shop review.

---

## Future roadmap

Possible V2 features:

- Review reminder resend logic.
- Richer analytics.
- Review request segmentation.
- Review source attribution.
- Public shop-review archive.
- Review replies.
- Review import/export.
- Admin dashboard widgets.
- Photo reviews.
- Integration with Luma Campaign Studio.
- Integration with Luma Analytics.
- Optional Google Merchant Center product review feed support.

Do not design V1 in a way that blocks these, but do not build them prematurely.

---

## Suggested implementation order

1. Create plugin scaffold and bootstrap.
2. Add activation and database tables.
3. Implement the settings class with defaults and typed getters.
4. Add the WooCommerce settings section for Reviews Plus.
5. Implement token repository and token generation.
6. Add review page route/controller.
7. Render order-specific product review form using editable page text.
8. Implement product review handler using native WooCommerce reviews.
9. Implement duplicate tracking per order item.
10. Add shop-experience form and repository.
11. Add admin shop-review overview.
12. Add public trust summary shortcode.
13. Add WooCommerce review-request email class.
14. Add email link generation and Action Scheduler scheduling.
15. Harden security, escaping, and edge cases.
16. Test with real WooCommerce order scenarios.

---

## Non-negotiables

- Product reviews must remain native WooCommerce/WP reviews.
- Shop-experience reviews must be stored separately.
- Raw tokens must not be stored.
- Tokenized pages must not expose sensitive order data.
- Public shop testimonials require consent and admin approval.
- The plugin must not share data with Google, Trustpilot, or other third parties in V1.
- Review behavior settings must be centralized in the settings class.
- Review request email content should use WooCommerce email settings where feasible.
- Keep V1 small and operationally useful.
