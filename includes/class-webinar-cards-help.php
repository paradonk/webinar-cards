<?php
/**
 * Shortcode help/reference admin page.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Shortcode Guide admin page.
 */
class Webinar_Cards_Help {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_head', array( __CLASS__, 'inline_styles' ) );
	}

	/**
	 * Register admin submenu under the Webinars CPT menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'marketing-hub',
			__( 'Shortcode Guide', 'webinar-cards' ),
			__( 'Shortcode Guide', 'webinar-cards' ),
			'edit_posts',
			'wbc-shortcode-guide',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Output scoped admin styles — only on this page.
	 *
	 * @return void
	 */
	public static function inline_styles() {
		$screen = get_current_screen();
		if ( ! $screen || 'marketing-hub_page_wbc-shortcode-guide' !== $screen->id ) {
			return;
		}
		?>
		<style>
			.wbc-guide-wrap { max-width: 860px; }
			.wbc-guide-wrap h2 { margin-top: 2em; }
			.wbc-guide-wrap h2:first-of-type { margin-top: 0.5em; }
			.wbc-shortcode-badge {
				display: inline-block;
				background: #1d2327;
				color: #f0f0f1;
				font-family: Consolas, Monaco, monospace;
				font-size: 15px;
				padding: 6px 14px;
				border-radius: 4px;
				letter-spacing: 0.02em;
				margin-bottom: 1.2em;
			}
			.wbc-attr-table { border-collapse: collapse; width: 100%; margin-bottom: 2em; }
			.wbc-attr-table th,
			.wbc-attr-table td { padding: 10px 14px; border: 1px solid #c3c4c7; vertical-align: top; }
			.wbc-attr-table thead th { background: #f6f7f7; font-weight: 600; white-space: nowrap; }
			.wbc-attr-table tbody tr:nth-child(even) { background: #f6f7f7; }
			.wbc-attr-table code { font-size: 12px; background: #eef0f1; padding: 2px 5px; border-radius: 3px; white-space: nowrap; }
			.wbc-attr-table .wbc-default { color: #2271b1; font-weight: 600; }
			.wbc-example-block {
				background: #1d2327;
				color: #f0f0f1;
				font-family: Consolas, Monaco, monospace;
				font-size: 13px;
				padding: 14px 18px;
				border-radius: 6px;
				margin: 0 0 1em;
				overflow-x: auto;
				line-height: 1.7;
			}
			.wbc-example-block span.attr { color: #79c0ff; }
			.wbc-example-block span.val  { color: #a5d6ff; }
			.wbc-example-label {
				font-size: 12px;
				font-weight: 600;
				color: #646970;
				text-transform: uppercase;
				letter-spacing: 0.06em;
				margin: 1.4em 0 0.3em;
			}
			.wbc-tip { background: #eef9fe; border-left: 4px solid #2271b1; padding: 10px 14px; margin: 1em 0; font-size: 13px; }
		</style>
		<?php
	}

	/**
	 * Render the guide page.
	 *
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap wbc-guide-wrap">
			<h1><?php esc_html_e( 'Webinar Cards — Shortcode Guide', 'webinar-cards' ); ?></h1>
			<p><?php esc_html_e( 'Place this shortcode in any page, post, or Elementor shortcode widget to display your webinar cards.', 'webinar-cards' ); ?></p>
			<div class="wbc-shortcode-badge">[webinar_cards]</div>

			<?php /* ── ATTRIBUTES TABLE ── */ ?>
			<h2><?php esc_html_e( 'All Attributes', 'webinar-cards' ); ?></h2>
			<table class="wbc-attr-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attribute', 'webinar-cards' ); ?></th>
						<th><?php esc_html_e( 'Accepted values', 'webinar-cards' ); ?></th>
						<th><?php esc_html_e( 'Default', 'webinar-cards' ); ?></th>
						<th><?php esc_html_e( 'Description', 'webinar-cards' ); ?></th>
					</tr>
				</thead>
				<tbody>

					<?php /* columns */ ?>
					<tr>
						<td><code>columns</code></td>
						<td>1 – 6</td>
						<td><span class="wbc-default"><code>3</code></span></td>
						<td><?php esc_html_e( 'Number of columns on desktop screens (> 1024 px).', 'webinar-cards' ); ?></td>
					</tr>

					<?php /* tablet_columns */ ?>
					<tr>
						<td><code>tablet_columns</code></td>
						<td>1 – 4</td>
						<td><span class="wbc-default"><code>2</code></span></td>
						<td><?php esc_html_e( 'Number of columns on tablet screens (≤ 1024 px).', 'webinar-cards' ); ?></td>
					</tr>

					<?php /* mobile_columns */ ?>
					<tr>
						<td><code>mobile_columns</code></td>
						<td>1 – 2</td>
						<td><span class="wbc-default"><code>1</code></span></td>
						<td><?php esc_html_e( 'Number of columns on mobile screens (≤ 767 px).', 'webinar-cards' ); ?></td>
					</tr>

					<?php /* posts_per_page */ ?>
					<tr>
						<td><code>posts_per_page</code></td>
						<td><?php esc_html_e( 'Any whole number ≥ 1', 'webinar-cards' ); ?></td>
						<td><span class="wbc-default"><code>12</code></span></td>
						<td><?php esc_html_e( 'Maximum number of webinar cards to display.', 'webinar-cards' ); ?></td>
					</tr>

					<?php /* category */ ?>
					<tr>
						<td><code>category</code></td>
						<td><?php esc_html_e( 'Category slug(s), comma-separated', 'webinar-cards' ); ?></td>
						<td><span class="wbc-default"><code><?php esc_html_e( '(all)', 'webinar-cards' ); ?></code></span></td>
						<td><?php esc_html_e( 'Filter webinars by one or more Webinar Category slugs. Separate multiple slugs with commas.', 'webinar-cards' ); ?></td>
					</tr>

				</tbody>
			</table>

			<?php /* ── CARD FIELDS ── */ ?>
			<h2><?php esc_html_e( 'Card Fields', 'webinar-cards' ); ?></h2>
			<table class="wbc-attr-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Field', 'webinar-cards' ); ?></th>
						<th><?php esc_html_e( 'Where to set it', 'webinar-cards' ); ?></th>
						<th><?php esc_html_e( 'Notes', 'webinar-cards' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Title', 'webinar-cards' ); ?></strong></td>
						<td><?php esc_html_e( 'Post title', 'webinar-cards' ); ?></td>
						<td><?php esc_html_e( 'Displayed as the card heading.', 'webinar-cards' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Short description', 'webinar-cards' ); ?></strong></td>
						<td><?php esc_html_e( 'Excerpt field', 'webinar-cards' ); ?></td>
						<td><?php esc_html_e( 'Enable the Excerpt field via Screen Options (top-right corner of the edit screen).', 'webinar-cards' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Video URL', 'webinar-cards' ); ?></strong></td>
						<td><?php esc_html_e( 'Webinar Details meta box', 'webinar-cards' ); ?></td>
						<td>
							<strong><?php esc_html_e( 'YouTube:', 'webinar-cards' ); ?></strong> <?php esc_html_e( 'watch, youtu.be, embed, or Shorts URL.', 'webinar-cards' ); ?><br>
							<strong><?php esc_html_e( 'Vimeo:', 'webinar-cards' ); ?></strong> <?php esc_html_e( 'vimeo.com/VIDEO_ID, channel, or group URL.', 'webinar-cards' ); ?><br>
							<?php esc_html_e( 'The thumbnail is pulled automatically; clicking the card plays the video inline.', 'webinar-cards' ); ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Speaker', 'webinar-cards' ); ?></strong></td>
						<td><?php esc_html_e( 'Webinar Details meta box', 'webinar-cards' ); ?></td>
						<td><?php esc_html_e( 'Shown as a green badge on the right side of the card.', 'webinar-cards' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Date', 'webinar-cards' ); ?></strong></td>
						<td><?php esc_html_e( 'Webinar Details meta box', 'webinar-cards' ); ?></td>
						<td><?php esc_html_e( 'Cards are ordered by this date (newest first). Webinars without a date will not appear.', 'webinar-cards' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Category', 'webinar-cards' ); ?></strong></td>
						<td><?php esc_html_e( 'Categories panel (right sidebar)', 'webinar-cards' ); ?></td>
						<td><?php esc_html_e( 'Used with the category shortcode attribute to filter output.', 'webinar-cards' ); ?></td>
					</tr>
				</tbody>
			</table>

			<?php /* ── EXAMPLES ── */ ?>
			<h2><?php esc_html_e( 'Examples', 'webinar-cards' ); ?></h2>

			<p class="wbc-example-label"><?php esc_html_e( 'Default — all webinars, 3 columns', 'webinar-cards' ); ?></p>
			<div class="wbc-example-block">[webinar_cards]</div>

			<p class="wbc-example-label"><?php esc_html_e( 'Show only 6 most recent webinars', 'webinar-cards' ); ?></p>
			<div class="wbc-example-block">[webinar_cards <span class="attr">posts_per_page</span>="<span class="val">6</span>"]</div>

			<p class="wbc-example-label"><?php esc_html_e( 'Custom grid layout', 'webinar-cards' ); ?></p>
			<div class="wbc-example-block">[webinar_cards <span class="attr">columns</span>="<span class="val">4</span>" <span class="attr">tablet_columns</span>="<span class="val">2</span>" <span class="attr">mobile_columns</span>="<span class="val">1</span>"]</div>

			<p class="wbc-example-label"><?php esc_html_e( 'Filter by a single category slug', 'webinar-cards' ); ?></p>
			<div class="wbc-example-block">[webinar_cards <span class="attr">category</span>="<span class="val">marketing</span>"]</div>

			<p class="wbc-example-label"><?php esc_html_e( 'Filter by multiple category slugs', 'webinar-cards' ); ?></p>
			<div class="wbc-example-block">[webinar_cards <span class="attr">category</span>="<span class="val">marketing,sales</span>"]</div>

			<p class="wbc-example-label"><?php esc_html_e( 'Full example — all options combined', 'webinar-cards' ); ?></p>
			<div class="wbc-example-block">[webinar_cards <span class="attr">columns</span>="<span class="val">3</span>" <span class="attr">tablet_columns</span>="<span class="val">2</span>" <span class="attr">mobile_columns</span>="<span class="val">1</span>" <span class="attr">posts_per_page</span>="<span class="val">12</span>" <span class="attr">category</span>="<span class="val">marketing</span>"]</div>

			<?php /* ── TIPS ── */ ?>
			<h2><?php esc_html_e( 'Tips', 'webinar-cards' ); ?></h2>
			<div class="wbc-tip">
				<?php esc_html_e( 'Cards are sorted by the Date field in the Webinar Details meta box — not by the WordPress publish date. Webinars without a date set will not appear in the grid.', 'webinar-cards' ); ?>
			</div>
			<div class="wbc-tip">
				<?php esc_html_e( 'Clicking anywhere on the video thumbnail plays the YouTube video inline inside the card. The fullscreen button on the player works normally.', 'webinar-cards' ); ?>
			</div>
			<div class="wbc-tip">
				<?php esc_html_e( 'To show the short description, open the post editor, click Screen Options (top-right), and enable the Excerpt checkbox.', 'webinar-cards' ); ?>
			</div>
			<div class="wbc-tip">
				<?php esc_html_e( 'You can place the shortcode inside an Elementor Shortcode widget, a Classic Editor page, or any WordPress block using the Shortcode block.', 'webinar-cards' ); ?>
			</div>

		</div>
		<?php
	}
}
