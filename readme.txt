=== Webinar Cards ===
Contributors: K.Paradorn
Tags: webinar, video, youtube, vimeo, shortcode
Requires at least: 6.0
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Responsive webinar card grid with inline YouTube and Vimeo embeds, managed via a custom post type and a [webinar_cards] shortcode.

== Description ==

Webinar Cards replaces hardcoded webinar listings with dynamic webinar posts managed from the WordPress admin. Each card displays a video thumbnail that plays inline on click — no page redirect. Supports both YouTube and Vimeo.

**Features**

* Custom post type — manage webinars from the WordPress admin
* Meta fields — YouTube/Vimeo URL, Speaker, Date
* Excerpt — short description shown on the card
* Webinar Categories taxonomy with slug-based shortcode filtering
* Responsive grid — desktop / tablet / mobile column control via shortcode
* YouTube thumbnails pulled automatically — no API key required
* Vimeo thumbnails fetched via oEmbed API, cached 30 days per video
* Inline playback — video plays inside the card on click (fullscreen available)
* 1-hour transient cache, auto-invalidated on content change
* Self-hosted auto-updater with optional SHA-256 checksum verification

**Email Gate (optional)**

Restrict video access to specific email domains or addresses via Webinars → Access Settings.

* Guests who click a video are prompted for their email address
* Allow by domain — any address at an allowed domain is granted access
* Allow by address — specific email addresses can be individually whitelisted
* Secure 24-hour httponly access-token cookie
* Separate JS-readable grant cookie for compatibility with NitroPack, LiteSpeed Cache, WP Rocket, and other page-cache plugins
* Logged-in WordPress users always bypass the gate automatically

== Installation ==

1. Upload the `webinar-cards` folder to `/wp-content/plugins/`.
2. Activate the plugin through **WordPress Admin → Plugins**.
3. Add webinars under the new **Webinars** menu (inside the Marketing menu).
4. Fill in the YouTube or Vimeo URL, Speaker, and Date in the **Webinar Details** meta box.
5. Add a short description using the **Excerpt** field (enable via Screen Options if hidden).
6. Place `[webinar_cards]` in any page, post, or Elementor shortcode widget.

== Frequently Asked Questions ==

= Why doesn't a webinar appear in the grid? =

Check that the webinar's status is **Published** (not Draft). Webinars are ordered by the Date field; webinars without a date are still shown but sorted to the bottom.

= The YouTube thumbnail has black bars — how do I fix it? =

The plugin uses `mqdefault.jpg` (always 16:9). If bars appear, check that the YouTube URL is correct and the video is publicly accessible.

= Can I show all webinars with no limit? =

Set `posts_per_page` to a large number such as `100`.

= Where do I find the category slug? =

Go to **Webinars → Categories**. The slug is shown in the Slug column.

= How do I filter by category? =

Use the `category` attribute with the category slug: `[webinar_cards category="marketing"]`. Separate multiple slugs with a comma: `[webinar_cards category="marketing,sales"]`.

== Shortcode ==

`[webinar_cards]`

**Attributes**

* `columns` — desktop columns, default `3` (1–6)
* `tablet_columns` — tablet columns, default `2` (1–4)
* `mobile_columns` — mobile columns, default `1` (1–2)
* `posts_per_page` — maximum cards to show, default `12`
* `category` — filter by Webinar Category slug(s), comma-separated; default shows all

**Examples**

`[webinar_cards]`
`[webinar_cards columns="4" tablet_columns="2" mobile_columns="1"]`
`[webinar_cards posts_per_page="6"]`
`[webinar_cards category="marketing"]`
`[webinar_cards category="marketing,sales" columns="2"]`

== Screenshots ==

1. Webinar card grid on the front end.

== Changelog ==

= 1.1.4 =
* Fix: NitroPack page cache is now reliably purged when a webinar is saved. NitroPack only registers its integration hooks on non-admin requests, so previous do_action() calls from admin saves were silent no-ops. The purge now also runs via a WP-Cron event that fires outside admin context, where NitroPack's hooks are active.

= 1.1.3 =
* Fix: NitroPack cache purge now triggers correctly on every webinar save. The has_action guard was causing the purge to be skipped on admin requests because NitroPack registers its integration hooks on front-end requests only.

= 1.1.2 =
* Fix: NitroPack page cache is now automatically purged when a webinar is saved or deleted, so new cards appear on the site immediately without a manual cache clear.

= 1.1.1 =
* Fix: Registration link now redirects the user back to the originating page after account creation.

= 1.1.0 =
* New: Email gate — restrict video access by allowed domain or specific email address.
* New: Access Settings admin page (Webinars → Access Settings) to manage gate rules.
* New: Secure 24-hour access token cookie; separate JS-readable grant cookie for compatibility with NitroPack, LiteSpeed Cache, WP Rocket, and other page-cache plugins.
* New: Gate modal with email input, inline error messages, and sign-in / register fallback links.
* Fix: Webinars without a Date field were excluded from the grid after cache expiry. Now all published webinars appear; undated ones sort to the bottom.

= 1.0.0 =
* Initial release: custom post type, Webinar Categories taxonomy, responsive grid shortcode.
* YouTube and Vimeo inline embed on card click (autoplay, fullscreen-capable).
* YouTube mqdefault thumbnail (16:9, no black bars); Vimeo thumbnail via oEmbed, cached 30 days.
* Shortcode output caching via transient (1 hour, auto-invalidated).
* N+1 query prevention via update_meta_cache().
* Self-hosted auto-updater with optional SHA-256 checksum verification.

== Upgrade Notice ==

= 1.1.4 =
Fixes NitroPack cache not clearing after adding or editing a webinar. Upgrade required if cards don't appear after saving.

= 1.1.3 =
Fixes automatic NitroPack cache purging after adding or editing a webinar. Upgrade recommended for all NitroPack users.
