=== Webinar Cards ===
Contributors: K.Paradorn
Tags: webinar, youtube, video, shortcode, grid
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A responsive webinar card grid with inline YouTube and Vimeo embeds, managed via a custom post type and a shortcode.

== Description ==

Webinar Cards replaces hardcoded HTML webinar listings with dynamic webinar posts managed from the WordPress admin area. Each card displays a video thumbnail that plays inline when clicked — no page redirect required. Both YouTube and Vimeo are supported.

Features:
- Custom post type for webinars
- Meta boxes for YouTube or Vimeo URL, Speaker, and Date
- Short description via the built-in Excerpt field
- Webinar Categories taxonomy with slug-based filtering
- Responsive CSS Grid layout (desktop / tablet / mobile columns)
- Shortcode support for Elementor shortcode widget and classic pages
- YouTube thumbnail pulled automatically (no API key required)
- Vimeo thumbnail fetched via oEmbed API and cached for 30 days
- Inline YouTube and Vimeo playback on click (fullscreen button available)
- Shortcode output caching (1-hour transient, auto-invalidated on content change)
- Self-hosted auto-updater — no wordpress.org required
- Email gate: restrict video access by allowed domain or specific email address
- Gate bypassed automatically for logged-in WordPress users
- 24-hour access token stored in a secure cookie; JS-readable grant flag for compatibility with page-cache plugins (NitroPack, LiteSpeed, WP Rocket, etc.)
- Access Settings admin page under Webinars menu

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Add webinars under the new `Webinars` menu.
4. Fill in the YouTube or Vimeo URL, Speaker, and Date in the Webinar Details meta box.
5. Add a short description using the Excerpt field (enable via Screen Options if hidden).
6. Place `[webinar_cards]` in a page, post, or Elementor shortcode widget.

== Shortcode ==

`[webinar_cards]`

Attributes:

| Attribute       | Default | Description                                      |
|-----------------|---------|--------------------------------------------------|
| columns         | 3       | Desktop columns (1–6)                            |
| tablet_columns  | 2       | Tablet columns (1–4)                             |
| mobile_columns  | 1       | Mobile columns (1–2)                             |
| posts_per_page  | 12      | Maximum number of cards to show                  |
| category        | (all)   | Filter by Webinar Category slug(s), comma-separated |

Examples:
- `[webinar_cards]`
- `[webinar_cards columns="4" tablet_columns="2" mobile_columns="1"]`
- `[webinar_cards posts_per_page="6"]`
- `[webinar_cards category="marketing"]`
- `[webinar_cards category="marketing,sales" columns="2"]`

== Frequently Asked Questions ==

= Why doesn't a webinar appear in the grid? =
Webinars are ordered by the Date field in the Webinar Details meta box. Webinars without a date set are still shown but sorted to the bottom of the grid.

= The YouTube thumbnail has black bars — how do I fix it? =
The plugin uses `mqdefault.jpg` thumbnails from YouTube, which are always 16:9 and have no black bars. If you see bars, check that the YouTube URL in the meta box is correct and the video is publicly accessible.

= Does the plugin support Vimeo? =
Yes. Paste any standard Vimeo URL (vimeo.com/VIDEO_ID, channel, group, or player URL) into the Video URL field. The thumbnail is fetched automatically via Vimeo's oEmbed API and cached for 30 days. The video plays inline on click, the same as YouTube.

= Can I show all webinars with no limit? =
There is no built-in unlimited option. Set `posts_per_page` to a large number such as `100` to effectively show all webinars.

= Where do I find the category slug? =
Go to Webinars → Categories in the WordPress admin. The slug is shown in the Slug column of the category list table.

= How does the email gate work? =
When the gate is enabled (Webinars → Access Settings), guests who click a video are asked to enter their email address. If the email matches an allowed domain (e.g. company.com) or a specific allowed address, the video plays immediately and access is remembered for 24 hours. If the email is not on any list, the visitor is offered a link to sign in or register. Logged-in WordPress users always bypass the gate.

= Does the email gate work with caching plugins? =
Yes. The gate uses a JS-readable browser cookie (wbc_granted) alongside a secure server-side token. Client-side access checks read the cookie directly, so the gate behaves correctly even when the page HTML is served from a cache such as NitroPack, LiteSpeed Cache, or WP Rocket.

== Changelog ==

= 1.1.0 =
* New: Email gate — restrict video access by allowed domain or specific email address.
* New: Access Settings admin page (Webinars → Access Settings) to manage gate rules.
* New: Secure 24-hour access token stored in an httponly cookie; separate JS-readable grant cookie for page-cache plugin compatibility.
* New: Gate modal with email input, inline error messages, and sign-in / register fallback links.
* Fix: Webinars without a Date field were excluded from the grid after cache expiry (meta_key inner-join bug). Now all published webinars appear; undated ones sort to the bottom.

= 1.0.0 =
* Initial release.
* Custom post type: webinar (title, excerpt, thumbnail, YouTube/Vimeo URL, speaker, date).
* Webinar Categories taxonomy with shortcode filtering.
* Responsive grid shortcode [webinar_cards] with desktop / tablet / mobile column control.
* YouTube and Vimeo support: inline embed on card click (autoplay, fullscreen-capable).
* YouTube thumbnail pulled automatically; uses mqdefault (16:9, no black bars, always available).
* Vimeo thumbnail fetched via oEmbed API and cached 30 days per video ID.
* Shortcode output caching via transient (1 hour, invalidated on save/trash/delete and category changes).
* N+1 query prevention via update_meta_cache().
* Shortcode Guide admin page under Webinars menu.
* Self-hosted auto-updater with optional SHA-256 checksum verification.
