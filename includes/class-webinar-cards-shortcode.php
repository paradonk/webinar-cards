<?php
/**
 * Shortcode rendering.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the [webinar_cards] shortcode.
 */
class Webinar_Cards_Shortcode {

	/** @var bool Tracks whether the gate modal footer hook has been registered. */
	private static $gate_modal_registered = false;

	/**
	 * Hook setup — called on the init action.
	 *
	 * @return void
	 */
	public static function init() {
		wp_register_style(
			'webinar-cards',
			WEBINAR_CARDS_URL . 'assets/css/webinar-cards.css',
			array(),
			WEBINAR_CARDS_VERSION
		);
		wp_register_script(
			'webinar-cards',
			WEBINAR_CARDS_URL . 'assets/js/webinar-cards.js',
			array(),
			WEBINAR_CARDS_VERSION,
			true
		);

		// Pass gate state to JS — runs fresh every request, never cached.
		wp_localize_script(
			'webinar-cards',
			'wbcData',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wbc_gate_nonce' ),
				'gateEnabled' => Webinar_Cards_Access::is_gate_enabled(),
				'hasAccess'   => Webinar_Cards_Access::visitor_has_access(),
				'loginUrl'    => wp_login_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ),
				'registerUrl' => add_query_arg( 'redirect_to', esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ), wp_registration_url() ),
			)
		);

		add_shortcode( 'webinar_cards', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Render the [webinar_cards] shortcode.
	 *
	 * Attributes:
	 *   columns        – desktop columns (default 3)
	 *   tablet_columns – tablet columns  (default 2)
	 *   mobile_columns – mobile columns  (default 1)
	 *   posts_per_page – max cards shown (default 12)
	 *   category       – comma-separated wbc_category slugs, or '' for all
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'        => '3',
				'tablet_columns' => '2',
				'mobile_columns' => '1',
				'posts_per_page' => '12',
				'category'       => '',
			),
			$atts,
			'webinar_cards'
		);

		// Register the gate modal once per request via wp_footer (not cached).
		if ( Webinar_Cards_Access::is_gate_enabled() && ! self::$gate_modal_registered ) {
			self::$gate_modal_registered = true;
			add_action( 'wp_footer', array( __CLASS__, 'render_gate_modal' ) );
		}

		// ── Transient cache ───────────────────────────────────────────
		// Cache key includes a version number bumped on any webinar save/delete,
		// so visitors never see stale output after content changes.
		$cache_version = (int) get_option( 'wbc_cache_version', 0 );
		$cache_key     = 'wbc_grid_' . $cache_version . '_' . md5( (string) serialize( $atts ) );
		$cached        = get_transient( $cache_key );

		if ( false !== $cached ) {
			wp_enqueue_style( 'webinar-cards' );
			wp_enqueue_script( 'webinar-cards' );
			return (string) $cached;
		}

		$columns        = max( 1, min( 6, absint( $atts['columns'] ) ) );
		$tablet_columns = max( 1, min( 4, absint( $atts['tablet_columns'] ) ) );
		$mobile_columns = max( 1, min( 2, absint( $atts['mobile_columns'] ) ) );
		$posts_per_page = max( 1, absint( $atts['posts_per_page'] ) );
		$category       = sanitize_text_field( (string) $atts['category'] );

		// Named meta-query clauses let us include webinars whether or not they
		// have a date set. Posts with a date sort first (DESC); posts without
		// a date have NULL for meta_value which MySQL places last in DESC order.
		$query_args = array(
			'post_type'      => 'webinar',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation'      => 'OR',
				'date_clause'   => array(
					'key'     => '_wbc_date',
					'compare' => 'EXISTS',
					'type'    => 'DATE',
				),
				'nodate_clause' => array(
					'key'     => '_wbc_date',
					'compare' => 'NOT EXISTS',
				),
			),
			'orderby'        => array( 'date_clause' => 'DESC', 'date' => 'DESC' ),
		);

		if ( ! empty( $category ) ) {
			$slugs = array_filter( array_map( 'trim', explode( ',', $category ) ) );
			if ( ! empty( $slugs ) ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'wbc_category',
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				);
			}
		}

		$ids = ( new WP_Query( $query_args ) )->posts;

		// Prime all meta in ONE query — avoids N+1 per card.
		if ( ! empty( $ids ) ) {
			update_meta_cache( 'post', $ids );
		}

		wp_enqueue_style( 'webinar-cards' );
		wp_enqueue_script( 'webinar-cards' );

		$style = sprintf(
			'--wbc-columns:%1$d;--wbc-tablet-columns:%2$d;--wbc-mobile-columns:%3$d;',
			$columns,
			$tablet_columns,
			$mobile_columns
		);

		ob_start();
		?>
		<div class="wbc-webinar-grid" style="<?php echo esc_attr( $style ); ?>">
			<?php if ( $ids ) : ?>
				<?php foreach ( $ids as $post_id ) : ?>
					<?php self::render_card( $post_id ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="wbc-no-webinars"><?php esc_html_e( 'No webinars found.', 'webinar-cards' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		$output = (string) ob_get_clean();

		// Store in transient for 1 hour.
		set_transient( $cache_key, $output, HOUR_IN_SECONDS );

		return $output;
	}

	/**
	 * Bump the cache version to invalidate all cached shortcode output.
	 *
	 * Called on every webinar save so visitors never see stale cards.
	 *
	 * @return void
	 */
	public static function bust_cache() {
		update_option( 'wbc_cache_version', time() );
	}

	/**
	 * Bust the cache only when the trashed/deleted post is a webinar.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function maybe_bust_cache( $post_id ) {
		if ( 'webinar' === get_post_type( $post_id ) ) {
			self::bust_cache();
		}
	}

	/**
	 * Output the email-gate modal once in wp_footer.
	 *
	 * The modal is always present in the DOM; JS shows it when needed.
	 * It is NOT part of the cached shortcode output.
	 *
	 * @return void
	 */
	public static function render_gate_modal() {
		?>
		<div id="wbc-gate-modal" class="wbc-gate-modal" hidden aria-hidden="true">
			<div class="wbc-gate-backdrop"></div>
			<div class="wbc-gate-box" role="dialog" aria-modal="true" aria-labelledby="wbc-gate-title">

				<button type="button" class="wbc-gate-close" aria-label="<?php esc_attr_e( 'Close', 'webinar-cards' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
						<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
					</svg>
				</button>

				<div class="wbc-gate-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
						<rect x="3" y="11" width="18" height="11" rx="2"/>
						<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
					</svg>
				</div>

				<h2 id="wbc-gate-title" class="wbc-gate-title">
					<?php esc_html_e( 'Watch this Webinar', 'webinar-cards' ); ?>
				</h2>

				<?php /* ── Step 1: email entry ── */ ?>
				<div id="wbc-gate-email-step">
					<p class="wbc-gate-subtitle">
						<?php esc_html_e( 'Enter your email address to access this content.', 'webinar-cards' ); ?>
					</p>

					<form id="wbc-gate-form" class="wbc-gate-form" novalidate>
						<input
							type="email"
							id="wbc-gate-email"
							name="email"
							class="wbc-gate-email-input"
							placeholder="<?php esc_attr_e( 'your@email.com', 'webinar-cards' ); ?>"
							autocomplete="email"
							required
						>
						<button type="submit" id="wbc-gate-submit" class="wbc-gate-submit">
							<?php esc_html_e( 'Continue', 'webinar-cards' ); ?>
						</button>
					</form>

					<div id="wbc-gate-message" class="wbc-gate-message" hidden></div>

					<div class="wbc-gate-footer">
						<a href="#" id="wbc-gate-login"><?php esc_html_e( 'Sign in with your account', 'webinar-cards' ); ?></a>
						<span aria-hidden="true">&middot;</span>
						<a href="#" id="wbc-gate-register"><?php esc_html_e( 'Create an account', 'webinar-cards' ); ?></a>
					</div>
				</div>

				<?php /* ── Step 2: code verification ── */ ?>
				<div id="wbc-gate-verify-step" hidden>
					<p class="wbc-gate-subtitle" id="wbc-gate-verify-subtitle"></p>

					<form id="wbc-gate-verify-form" class="wbc-gate-form" novalidate>
						<input
							type="text"
							id="wbc-gate-code"
							name="code"
							class="wbc-gate-code-input"
							placeholder="000000"
							maxlength="6"
							autocomplete="one-time-code"
							inputmode="numeric"
						>
						<button type="submit" id="wbc-gate-verify-submit" class="wbc-gate-submit">
							<?php esc_html_e( 'Verify', 'webinar-cards' ); ?>
						</button>
					</form>

					<div id="wbc-gate-verify-message" class="wbc-gate-message" hidden></div>

					<div class="wbc-gate-footer">
						<a href="#" id="wbc-gate-back"><?php esc_html_e( '← Back', 'webinar-cards' ); ?></a>
						<span aria-hidden="true">&middot;</span>
						<a href="#" id="wbc-gate-resend"><?php esc_html_e( 'Resend code', 'webinar-cards' ); ?></a>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render a single webinar card.
	 *
	 * All get_post_meta() calls are served from the cache primed above —
	 * zero additional DB queries per card.
	 *
	 * @param int $post_id Webinar post ID.
	 * @return void
	 */
	private static function render_card( $post_id ) {
		$video_url   = (string) get_post_meta( $post_id, '_wbc_video_url', true );
		$speaker     = (string) get_post_meta( $post_id, '_wbc_speaker',   true );
		$date        = (string) get_post_meta( $post_id, '_wbc_date',      true );
		$title       = get_the_title( $post_id );
		$description = get_the_excerpt( $post_id );
		$date_fmt    = Webinar_Cards_Helpers::format_date( $date );

		$platform      = Webinar_Cards_Helpers::detect_platform( $video_url );
		$video_id      = $platform['id'];
		$platform_name = $platform['platform'];

		if ( 'youtube' === $platform_name ) {
			$thumb_url = Webinar_Cards_Helpers::youtube_thumbnail_url( $video_id );
		} elseif ( 'vimeo' === $platform_name ) {
			$thumb_url = Webinar_Cards_Helpers::vimeo_thumbnail_url( $video_id );
		} else {
			$thumb_url = '';
		}
		?>
		<article class="wbc-webinar-card">

			<?php if ( $video_id ) : ?>
				<div class="wbc-webinar-card__video" data-video-id="<?php echo esc_attr( $video_id ); ?>" data-platform="<?php echo esc_attr( $platform_name ); ?>">
					<img
						class="wbc-webinar-card__thumbnail"
						src="<?php echo esc_url( $thumb_url ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						loading="lazy"
					>
					<button class="wbc-play-btn" aria-label="<?php esc_attr_e( 'Play webinar', 'webinar-cards' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 68 48" aria-hidden="true" focusable="false">
							<path class="wbc-play-btn__bg" d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C0 13.05 0 24 0 24s0 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C68 34.95 68 24 68 24s0-10.95-1.48-16.26z"/>
							<path class="wbc-play-btn__arrow" d="M45 24 27 14v20"/>
						</svg>
					</button>
				</div>
			<?php elseif ( has_post_thumbnail( $post_id ) ) : ?>
				<div class="wbc-webinar-card__image-wrap">
					<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'wbc-webinar-card__thumbnail', 'loading' => 'lazy' ) ); ?>
				</div>
			<?php endif; ?>

			<div class="wbc-webinar-card__content">
				<?php if ( $date_fmt || $speaker ) : ?>
					<div class="wbc-webinar-card__meta-row">
						<?php if ( $date_fmt ) : ?>
							<span class="wbc-webinar-card__date"><?php echo esc_html( $date_fmt ); ?></span>
						<?php endif; ?>
						<?php if ( $speaker ) : ?>
							<span class="wbc-webinar-card__speaker-badge"><?php echo esc_html( $speaker ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<h3 class="wbc-webinar-card__title"><?php echo esc_html( $title ); ?></h3>

				<?php if ( $description ) : ?>
					<p class="wbc-webinar-card__desc"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</div>

		</article>
		<?php
	}
}
