=== Visual Website Optimizer ===
Contributors: vwointegrations
Plugin Name: VWO
Plugin URI: https://vwo.com/
Tags: vwo, a/b testing, wordpress optimization, woocommerce tracking, split testing
Requires at least: 2.7
Tested up to: 6.7.2
Stable tag: 4.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

VWO is the all-in-one platform that helps you conduct visitor research, build an optimization roadmap, and run continuous experimentation. 

== Description ==

This plugin simplifies the process of adding the VWO SmartCode to your WordPress site and enables automatic tracking of WooCommerce events like product views, add-to-cart actions, and completed purchases. It also offers optional server-side tracking to bypass client-side blockers and improve event reliability.

=== Key Features ===
* Easy VWO SmartCode integration
* Automatic WooCommerce event tracking
* Event-level data passed to VWO for advanced reporting
* Support for asynchronous and synchronous SmartCode types
* Optional server-side tracking for more accurate analytics
* Works seamlessly with WooCommerce

== Installation ==

1. Log in to your WordPress dashboard.
2. Navigate to Plugins > Add New.
3. Click "Upload Plugin" and select the plugin ZIP file.
4. Click "Install Now", then "Activate".
5. Go to **Settings > VWO** to configure your plugin.

== Frequently Asked Questions ==

= What is my VWO Account ID? =
You can find your VWO Account ID in your VWO dashboard under Account Settings.

= Does this plugin support server-side tracking? =
Yes, server-side tracking is available and can be enabled in the WooCommerce tab of the plugin settings.

= What WooCommerce events are tracked automatically? =
- Product Viewed
- Add to Cart
- Remove from Cart
- Purchase (Order Completed)

== Frequently Asked Questions ==
= I can't see any code added to my header or footer when I view my page source =
Your theme needs to have the header and footer actions in place before the `</head>` and before the `</body>`

= If I use this plugin, do I need to enter any other code on my website? =
No, this plugin is sufficient by itself

== Screenshots ==
1. General Settings
2. Advanced Settings
3. WooCommerce Settings

== Configuration and Setup ==

=== Configuring General Settings ===
1. Go to **Settings > VWO**.
2. Enter your VWO Account ID.
3. Choose your preferred code type:
   - **Asynchronous (Recommended)**: Loads faster, doesn't block elements.
   - **Synchronous**: Not recommended for performance reasons.
4. Click **Save Changes**.

=== Configuring WooCommerce Settings ===
1. Open the **WooCommerce** tab in the VWO settings.
2. Enable WooCommerce Event Tracking.
3. Enable the specific events you want to track:
   - Product Viewed
   - Add to Cart
   - Product Removed From Cart
   - Purchase Order
4. Click **Save Changes**.

=== Configuring Server-Side Tracking (Optional) ===
1. Enable **Server-Side Tracking** from the WooCommerce tab.
2. Click **Save Changes**.

=== Verifying Event Tracking in VWO ===
1. Log in to your VWO account.
2. Go to **Data360 > Events**.
3. Perform sample actions on your WooCommerce store.
4. Check the events list in Data360.
5. Register any events listed under “Unregistered Events”.

== WooCommerce Events Imported Into VWO ==

=== Product Viewed ===
- **Event API Name**: `woocommerce.productViewed`
- Payload:
  - price, currency, productId, productTitle, productUrl, productCategory, productSku, quantity, variantId

=== Add To Cart ===
- **Event API Name**: `woocommerce.addToCart`
- Payload:
  - price, currency, productId, productTitle, productUrl, productCategory, productSku, quantity, variantId

=== Remove from Cart ===
- **Event API Name**: `woocommerce.removeFromCart`
- Payload:
  - price, currency, productId, productTitle, productUrl, productCategory, productSku, quantity, variantId

=== Purchase ===
- **Event API Name**: `woocommerce.purchase`
- Payload:
  - orderId, productId, productSku, productPrice, productQuantity, discount, shippingPrice, totalTax, totalPrice, currencyCode

== Upgrade Notice ==

= 4.8 =
* New settings UI, WooCommerce support added, and multiple bug fixes. Upgrade recommended for improved tracking and compatibility.

== Changelog ==

= 4.8 =
* New settings UI, WooCommerce support added, and multiple bug fixes. Upgrade recommended for improved tracking and compatibility.

= 4.7 =
* Minor bug fix

= 4.6 =
* Revamped plugin settings UI for improved usability.
* Added support for WooCommerce event tracking (Product Viewed, Add to Cart, Purchase, etc.).
* Optional server-side tracking for WooCommerce events added.
* Multiple bug fixes and performance improvements.

= 4.5 =
* Tested with latest version
* VWO SmartCode 2.1 updated

= 4.4 =
* Tested with latest version
* Minor bug fix

= 4.3 =
* Minor bug fix

= 4.2 =
* Tested with latest version
* VWO SmartCode 2.1 updated

= 4.1 =
* Tested with latest version
* PHPCS Errors resolved
* WP Rocket support added

= 4.0 =
* Tested with latest version
* VWO SmartCode updated

= 3.9 =
* Tested with latest version
* Code improvement and add Rocket loader handling

= 3.8 =
* Tested with latest version
* Fix WP Rocket Issue

= 3.7 =
* Tested with latest version
* Fix Divi Frontend Editor Issue

= 3.6 =
* Tested with latest version

= 3.5 =
* Tested with latest version
* Rename label "Handle Rocket Loader Issue" to "Skip Deferred Execution"
* Set field default value of "Skip Deferred Execution" to "yes"

= 3.4 =
* Tested with latest version
* Code improvement and add Rocket loader handling

= 3.3 =
* Tested with latest version
* Add new options in settings

= 3.2 =
* Tested with latest version

= 3.1 =
* Add Setting link in plugin listing page

= 3.0 =
* Update Logo and links

= 2.9 =
* Tested with latest version

= 2.8 =
* Tested with latest version

= 2.7 =
* Update Plugin Name, Author and Description

= 2.6 =
* Remove Conflict Errors

= 2.5 =
* Update tested upto version

= 2.4 =
* Update links

= 2.3 =
* Minor bug fix

= 2.2 =
* Bug fix to have default tolerance values when plugin is updated

= 2.1 =
* Better documentation

= 2.0 =
* Option to choose between asynchronous or synchronous code
* Updated code snippet
* Faster website loading

= 1.3 =
* code snippet updated

= 1.0.1 =
* use Website instead of Web in name of functions and readme (branding)

= 1.0 =
* First Version
