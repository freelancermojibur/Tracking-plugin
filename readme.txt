=== Shilpi Order Stages ===
Contributors: mojibur
Tags: woocommerce, order status, shipment tracking, order tracking, customer emails
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 11.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Follow every order from supplier payment to the customer's door, with custom order stages, automatic customer emails and a tracking timeline.

== Description ==

Shilpi Order Stages is built for stores that sell made to order or imported products with a long journey: supplier payment, production, freight forwarder, sea freight, port, customs and final delivery.

The store owner only changes the stage. Everything else happens automatically: the customer gets a clear email with a progress bar, the stage date is recorded, and a history note is added to the order.

= Features =

* Nine order stages: Supplier unpaid, Paid in production, Factory ready, At forwarder warehouse, On ship, Arrived at port, Customs clearance, Out for delivery and Delivered (WooCommerce Completed).
* Stages 1 to 8 are counted as paid statuses in WooCommerce reports and Analytics.
* Coloured stage badges, a stage filter and "Change stage to X" bulk actions (with or without email) on the order list.
* Shipment & Supplier box on the order screen: supplier, invoice, total cost, freight forwarder, container / BL / vessel, tracking number and URL, ETA, internal notes and calculated profit.
* "Move to next stage" button and an "Email the customer" checkbox.
* Nine stage emails plus a "Delivery estimate updated" email, fully editable in WooCommerce > Settings > Emails, with HTML and plain text versions and an email client safe progress bar.
* No email on backward moves, no duplicate emails for the same stage.
* Optional replacement of the core Completed email with the Delivered stage email.
* Customer timeline on My Account > View order and on the order received page.
* Tracking page shortcode with nonce protection and rate limiting.
* Dashboard (WooCommerce > Order Stages) with active, to pay, in transit and overdue counts plus an ETA table.
* Daily overdue alert email.
* Rename stages (admin and customer names) and change badge colours in settings.
* Full HPOS (High-Performance Order Storage) support.
* Translation ready.

== Installation ==

1. Upload the `shilpi-order-stages` folder to `/wp-content/plugins/`, or upload the zip in Plugins > Add New > Upload Plugin.
2. Activate the plugin. WooCommerce must be active.
3. Go to WooCommerce > Settings > Order Stages to review the settings.
4. Go to WooCommerce > Settings > Emails to review the nine "Order stage" emails.
5. Create a page with the `[shilpi_track_order]` shortcode for guest order tracking.

= Install / update =

Always install from the release zip `shilpi-order-stages.zip` (built with `bash bin/build.sh`, output in `dist/`). Its root folder is `shilpi-order-stages/`.

Do not install the GitHub "Download ZIP" of a branch. It creates a folder named after the repository and branch, which breaks translations and creates a second copy of the plugin.

To update: go to Plugins > Add New > Upload Plugin, choose the new `shilpi-order-stages.zip` and confirm "Replace current with uploaded". Settings and order data are kept.

If an earlier copy is installed in a different folder (for example `Tracking-plugin-...`), deactivate and delete that copy first, then upload the zip. Deleting the plugin keeps order data; only plugin settings are removed, so note any custom settings before deleting.

== Frequently Asked Questions ==

= Which shortcode shows the tracking form? =

`[shilpi_track_order]`. The alias `[sk_track_order]` also works.

= Can I customise the email templates? =

Yes. Copy files from the plugin's `templates/` folder into your theme:

* `templates/emails/shilpi-os-stage.php` to `yourtheme/woocommerce/emails/shilpi-os-stage.php`
* `templates/emails/plain/shilpi-os-stage.php` to `yourtheme/woocommerce/emails/plain/shilpi-os-stage.php`
* `templates/emails/parts/shilpi-os-progress.php` and `shilpi-os-shipment.php` to `yourtheme/woocommerce/emails/parts/`
* `templates/myaccount/shilpi-os-timeline.php` and `shilpi-os-track-form.php` to `yourtheme/woocommerce/myaccount/`

= Which placeholders can I use in emails? =

`{customer_first_name}`, `{order_number}`, `{order_date}`, `{product_names}`, `{eta}`, `{tracking_number}`, `{tracking_url}`, `{shipping_address}` and `{site_title}`.

= When is the customer emailed? =

Only when an order moves forward to a stage that has not been emailed before, the email is enabled, and the "Email the customer" checkbox was ticked (or a bulk action without "(no email)" was used).

= Is order data deleted when I uninstall? =

No. Uninstalling removes only the plugin settings. Order stage dates, shipment details and notes stay with your orders.

= Can developers change the stages? =

Yes, with the `shilpi_os_stages` filter. Keys are order statuses without `wc-` and must be 17 characters or less. Other hooks: `shilpi_os_stage_changed`, `shilpi_os_send_stage_email`, `shilpi_os_find_order_by_number` and `shilpi_os_settings_fields`.

== Changelog ==

= 1.1.1 =
* Fixed: order notes written by Action Scheduler, cron, REST, WP-CLI or gateways now use admin stage names. Customer names are used only in My Account, the order received page, the tracking shortcode and customer emails.
* New: dashboard warning when an automatic stage move is more than 15 minutes late or has failed, with a link to Scheduled Actions.
* Improved: the core Processing email is never suppressed when Action Scheduler is unavailable.
* Improved: stages an order jumped over are shown as "skipped" (muted, no date) in emails, My Account and the tracking page.

= 1.1.0 =
* New: setting to replace the WooCommerce Processing order email with the stage 1 email (on by default), so new customers get one email instead of two.
* Improved: new orders are moved to the first stage in a background Action Scheduler task after payment completes, avoiding conflicts with payment gateways.
* New: dashboard card for orders still in Processing, with buttons to move them to the first stage with or without email.
* New: build script `bin/build.sh` that creates `dist/shilpi-order-stages.zip`.
* New: "Order stage" side box on the order screen with a mini stepper, the next stage button and the email checkbox. Shipment fields stay in the main box.
* New: the next stage button is ignored (with a notice) when the Status dropdown was also changed in the same save.
* New: order action "Resend current stage email to customer".
* New: "Delivery estimate updated" email, sent when the ETA changes and "Email the customer about the new ETA" is ticked.
* New: dashboard pipeline with a count per stage; "In transit / at port" links to the order list filtered to stages 5 to 7. The stage filter accepts a comma separated list.
* Improved: email progress bar is mobile friendly (dots, then "Step X of 9" and the next step).
* Improved: the customer timeline is hidden for old orders that never used stages.
* Improved: bulk actions are grouped as "Stage: X" and "Stage (no email): X"; the duplicate "Delivered" entry was removed.
* Fixed: plain text emails no longer show "&amp;" for "&".
* Tested with WooCommerce 11.2.

= 1.0.0 =
* First release.
