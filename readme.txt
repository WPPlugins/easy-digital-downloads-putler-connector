=== Easy Digital Downloads Putler Connector ===
Contributors: putler, storeapps
Tags: analysis, reporting, sales, edd, easy digital downloads, management, products, orders, history, customers, graphs, charts
Requires at least: 3.3
Tested up to: 4.7.5
Stable tag: 2.4
License: GPL 3.0


== Description ==

Track Easy Digital Downloads orders with [Putler](http://putler.com/) -  Insightful reporting that grows your business.

Easy Digital Downloads Putler Connector sends transactions to Putler using Putler's Inbound API. All past orders are sent when you first configure this plugin. Future orders will sync to Putler automatically. 

You need a Putler account (Free or Paid), and an Easy Digital Downloads based store to use this plugin.

= Installation =

1. Ensure you have latest version of [Easy Digital Downloads](http://wordpress.org/plugins/easy-digital-downloads/) plugin installed
2. Unzip and upload contents of the plugin to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Click on 'Easy Digital Downloads Putler Connector ' option within WordPress admin sidebar menu

= Configuration =

Go to Wordpress > Tools > Putler Connector

This is where you need to enter Putler API Token and Putler Email Address to sync your past Easy Digital Downloads transactions to Putler and start tracking Easy Digital Downloads transactions with Putler.

1. Enter your Putler Email Address.
2. Enter your Putler API Token which you will get once you add a new account "Putler Inbound API" in Putler
3. Click on "Save & Send Past Orders to Putler" to send all the Easy Digital Downloads past orders to Putler.

All past orders will be sent to Putler. New orders will be automatically synced.

= Where to find your Putler API Token =

1. Sign up at: [Putler](https://web.putler.com/)
2. Go to Settings > Integrations and select "Putler Inbound API" as the account type
3. Note down the API Key and copy the same API Key in Putler Connector Settings

== Frequently Asked Questions ==

= Can I use this during 14 days trial of Putler? =

Yes, you can use this connector during your trial period.

== Screenshots ==

1. Easy Digital Downloads Putler Connector Settings Page

2. Putler Dashboard

3. Adding a new account in Putler - Notice API token that needs to be copied to Putler Connector settings

== Changelog ==

= 2.4 (30.05.2017) =
* Fix: Order total going 0 in some of the transactions
* Fix: Minor Fixes and compatibility

= 2.3 (18.05.2017) =
* Update: Track additional Easy Digital Downloads Order meta data for Putler Web
* Fix: Order date going null in some of the transactions
* Fix: Minor Fixes and compatibility

= 2.2 =
* Changed order status from "Complete" to "Completed" and send it to Putler.

= 2.1 =
* Fix: Date & Timezone issue

= 2.0 =
* New: Support for multiple API Tokens
* Fix: Minor Fixes and compatibility

= 1.0 =
* Initial release 


== Upgrade Notice ==

= 2.3 = 
Fixes related to order total going 0 in some of the transactions along with some important updates and fixes, recommended upgrade.

= 2.3 = 
Updates related to sending additional Easy Digital Downloads Order data for Putler Web along with some important updates and fixes, recommended upgrade.

= 2.2 = 
Updates for Putler web access.

= 2.1 =
Fixes related to date & timezone issue, recommended upgrade.

= 2.0 =
Support for multiple API Tokens and Minor Fixes and compatibility, recommended upgrade.

= 1.0 =
Welcome!!
