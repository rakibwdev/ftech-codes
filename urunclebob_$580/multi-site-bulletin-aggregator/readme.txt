=== Multi Site Bulletin Aggregator ===
Contributors: rakib
Tags: wordpress, news, aggregator, api, rss, shortcode
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fetch and display latest bulletins and breaking news from multiple WordPress sites using REST API.

== Description ==

Multi Site Bulletin Aggregator allows you to fetch posts from multiple WordPress websites and display them in one place.

You can:
- Aggregate posts from multiple WP sites
- Highlight breaking news using tag slug
- Show latest bulletins in a clean card layout
- Use a simple shortcode anywhere

Perfect for:
- News portals
- Multi-site networks
- Affiliate content aggregation
- Bulletin dashboards

== Installation ==

1. Download the plugin ZIP file
2. Go to your WordPress Admin Dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Upload the ZIP file and click Install Now
5. Activate the plugin

OR

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through the Plugins menu in WordPress

== Usage ==

1. Go to Dashboard → Bulletins
2. Add your site URLs (one per line)

Example:
https://example.com
https://another-site.com

Important:
- Only add base site URLs
- DO NOT add `/wp-json/wp/v2/posts`
- The plugin automatically handles API endpoints

3. Enter Breaking News Tag (slug)

Example:
breaking-news

4. Save settings

5. Use shortcode anywhere:

[msb_breaking]
[msb_bulletins]

You can place this shortcode in:
- Pages
- Posts
- Elementor widgets
- Theme templates

== Features ==

- Multi-site post aggregation
- Automatic REST API handling
- Breaking news ticker
- Latest bulletins display
- Time ago format (e.g., 2H AGO)
- Clean and responsive UI

== Frequently Asked Questions ==

= Why are posts not showing? =
Make sure:
- The target site REST API is enabled
- URL is correct
- Site is publicly accessible

= Can I add non-WordPress sites? =
No. This plugin only supports WordPress REST API.

= How many sites can I add? =
Unlimited, but too many may slow down performance.

= Can I change design? =
Yes, you can override CSS from your theme.

== Screenshots ==

1. Admin settings page
2. Breaking news ticker
3. Bulletin cards layout

== Changelog ==

= 1.0.0 =
- Initial release
- Multi-site aggregation
- Breaking news support
- Shortcode output

== Upgrade Notice ==

= 1.0.0 =
Initial release