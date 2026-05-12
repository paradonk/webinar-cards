<?php
/**
 * Main plugin class — singleton bootstrap.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads all dependencies and wires WordPress hooks.
 */
class Webinar_Cards {

	/** @var Webinar_Cards|null */
	private static $instance = null;

	/**
	 * Return (or create) the singleton instance.
	 *
	 * @return Webinar_Cards
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Require all class files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-helpers.php';
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-cpt.php';
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-meta.php';
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-shortcode.php';
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-help.php';
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-access.php';
		require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-settings.php';
		// Updater is required in webinar-cards.php so it is always available.
	}

	/**
	 * Register the shared Marketing top-level menu if no other plugin has done so yet.
	 *
	 * @return void
	 */
	public function maybe_register_marketing_menu() {
		global $admin_page_hooks;
		if ( isset( $admin_page_hooks['marketing-hub'] ) ) {
			return;
		}
		add_menu_page(
			__( 'Marketing', 'webinar-cards' ),
			__( 'Marketing', 'webinar-cards' ),
			'edit_posts',
			'marketing-hub',
			'__return_null',
			'dashicons-megaphone',
			25
		);
	}

	/**
	 * Register the Webinars section separator under the Marketing menu.
	 *
	 * @return void
	 */
	public function register_marketing_sections() {
		add_submenu_page( 'marketing-hub', '', '— Webinars', 'edit_posts', 'mh-sep-webinars', '__return_null' );
	}

	/**
	 * Reorder all Marketing submenu items. Idempotent — safe to run from multiple plugins.
	 *
	 * @return void
	 */
	public function reorder_marketing_submenu() {
		global $submenu;
		if ( empty( $submenu['marketing-hub'] ) ) {
			return;
		}

		$bfe          = [];
		$sep_events   = [];
		$events       = [];
		$sep_webinars = [];
		$webinars     = [];

		foreach ( $submenu['marketing-hub'] as $item ) {
			$url = isset( $item[2] ) ? $item[2] : '';
			if ( $url === 'marketing-hub' ) {
				continue;
			} elseif ( $url === 'bfe-status' ) {
				$bfe[] = $item;
			} elseif ( $url === 'mh-sep-events' ) {
				$sep_events[] = $item;
			} elseif ( $url === 'mh-sep-webinars' ) {
				$sep_webinars[] = $item;
			} elseif (
				false !== strpos( $url, 'post_type=event' ) ||
				in_array( $url, array( 'deg-import-events', 'deg-shortcode-guide', 'deg-settings' ), true )
			) {
				$events[] = $item;
			} elseif (
				false !== strpos( $url, 'post_type=webinar' ) ||
				in_array( $url, array( 'wbc-shortcode-guide', 'wbc-access-settings' ), true )
			) {
				$webinars[] = $item;
			}
		}

		$new = [];
		foreach ( $bfe as $item ) { $new[] = $item; }
		if ( ! empty( $events ) && ! empty( $sep_events ) ) {
			$new[] = $sep_events[0];
			foreach ( $events as $item ) { $new[] = $item; }
		}
		if ( ! empty( $webinars ) && ! empty( $sep_webinars ) ) {
			$new[] = $sep_webinars[0];
			foreach ( $webinars as $item ) { $new[] = $item; }
		}

		$submenu['marketing-hub'] = array_values( $new );
	}

	/**
	 * Inject shared Marketing flyout CSS — once per page load across all plugins.
	 *
	 * @return void
	 */
	public function marketing_menu_styles() {
		if ( defined( 'MH_FLYOUT_STYLES_DONE' ) ) {
			return;
		}
		define( 'MH_FLYOUT_STYLES_DONE', true );
		?>
		<style>
		#toplevel_page_marketing-hub .mh-group-link {
			display: flex !important;
			align-items: center !important;
		}
		#toplevel_page_marketing-hub .mh-group-link::after {
			content: '\203a' !important;
			margin-left: auto !important;
			padding-left: 10px !important;
			font-size: 18px !important;
			line-height: 1 !important;
			opacity: 0.6 !important;
		}
		#toplevel_page_marketing-hub .mh-current > .mh-group-link {
			color: #fff !important;
		}
		.mh-flyout {
			display: none;
			position: fixed;
			background: #1d2327;
			min-width: 180px;
			padding: 6px 0 !important;
			margin: 0 !important;
			list-style: none !important;
			border-radius: 0 4px 4px 4px;
			box-shadow: 4px 4px 16px rgba(0, 0, 0, 0.55);
			z-index: 99999;
		}
		#toplevel_page_marketing-hub .mh-group:hover .mh-flyout {
			display: block;
		}
		.mh-flyout li { margin: 0 !important; padding: 0 !important; }
		.mh-flyout li a {
			display: block !important;
			padding: 6px 16px !important;
			color: #c3c4c7 !important;
			white-space: nowrap !important;
			font-size: 13px !important;
			line-height: 1.6 !important;
			text-decoration: none !important;
		}
		.mh-flyout li a:hover,
		.mh-flyout li.current a {
			background: #2c3338 !important;
			color: #fff !important;
		}
		</style>
		<?php
	}

	/**
	 * Inject shared Marketing flyout JS — once per page load across all plugins.
	 *
	 * @return void
	 */
	public function marketing_menu_scripts() {
		if ( defined( 'MH_FLYOUT_SCRIPTS_DONE' ) ) {
			return;
		}
		define( 'MH_FLYOUT_SCRIPTS_DONE', true );
		?>
		<script>
		(function () {
			document.addEventListener('DOMContentLoaded', function () {
				var menuItem = document.getElementById('toplevel_page_marketing-hub');
				if (!menuItem) return;
				var sub = menuItem.querySelector('ul.wp-submenu');
				if (!sub) return;

				var allItems = Array.from(sub.querySelectorAll(':scope > li'));
				var groups   = [];
				var current  = null;

				allItems.forEach(function (li) {
					var a    = li.querySelector('a');
					var href = a ? (a.getAttribute('href') || '') : '';
					if (href.indexOf('mh-sep-') > -1) {
						current = {
							label : a.textContent.trim().replace(/^[—\- ]+/, ''),
							items : [],
							sepLi : li,
						};
						groups.push(current);
					} else if (current) {
						current.items.push(li);
					}
				});

				if (!groups.length) return;

				groups.forEach(function (g) {
					g.sepLi.remove();
					g.items.forEach(function (li) { li.remove(); });
				});

				groups.forEach(function (g) {
					if (!g.items.length) return;

					var firstA    = g.items[0].querySelector('a');
					var firstHref = firstA ? firstA.getAttribute('href') : '#';

					var groupLi       = document.createElement('li');
					groupLi.className = 'mh-group';

					var groupA         = document.createElement('a');
					groupA.href        = firstHref;
					groupA.className   = 'mh-group-link';
					groupA.textContent = g.label;
					groupLi.appendChild(groupA);

					var flyout       = document.createElement('ul');
					flyout.className = 'mh-flyout';
					g.items.forEach(function (li) { flyout.appendChild(li); });
					groupLi.appendChild(flyout);

					if (flyout.querySelector('.current')) {
						groupLi.classList.add('mh-current');
					}

					groupLi.addEventListener('mouseenter', function () {
						var rect          = groupLi.getBoundingClientRect();
						flyout.style.top  = rect.top  + 'px';
						flyout.style.left = rect.right + 'px';
					});

					sub.appendChild(groupLi);
				});
			});
		}());
		</script>
		<?php
	}

	/**
	 * Register all action / filter hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_filter( 'register_url', function() { return 'https://www.dextragroup.com/registration/'; } );
		add_action( 'admin_menu', array( $this, 'maybe_register_marketing_menu' ), 1 );
		add_action( 'admin_menu', array( $this, 'register_marketing_sections' ), 2 );
		add_action( 'admin_menu', array( $this, 'reorder_marketing_submenu' ), 11 );
		add_action( 'admin_head',  array( $this, 'marketing_menu_styles' ) );
		add_action( 'admin_footer', array( $this, 'marketing_menu_scripts' ) );
		add_action( 'init', array( 'Webinar_Cards_CPT',       'init' ) );
		add_action( 'init', array( 'Webinar_Cards_Shortcode', 'init' ) );
		add_action( 'init', array( 'Webinar_Cards_Help',      'init' ) );
		add_action( 'init', array( 'Webinar_Cards_Access',    'init' ) );
		add_action( 'init', array( 'Webinar_Cards_Settings',  'init' ) );
		add_action( 'add_meta_boxes',    array( 'Webinar_Cards_Meta',      'add_meta_boxes'    ) );
		add_action( 'save_post_webinar', array( 'Webinar_Cards_Meta',      'save_meta'         ), 10, 2 );
		add_action( 'save_post_webinar', array( 'Webinar_Cards_Shortcode', 'bust_cache'        ) );
		add_action( 'trash_post',        array( 'Webinar_Cards_Shortcode', 'maybe_bust_cache'  ) );
		add_action( 'before_delete_post', array( 'Webinar_Cards_Shortcode', 'maybe_bust_cache' ) );

		// Also bust cache when webinar categories are changed.
		add_action( 'edited_wbc_category',  array( 'Webinar_Cards_Shortcode', 'bust_cache' ) );
		add_action( 'created_wbc_category', array( 'Webinar_Cards_Shortcode', 'bust_cache' ) );
		add_action( 'deleted_wbc_category', array( 'Webinar_Cards_Shortcode', 'bust_cache' ) );

		new Webinar_Cards_Updater( WEBINAR_CARDS_FILE, WEBINAR_CARDS_VERSION, WEBINAR_CARDS_UPDATE_URL );
	}

	/**
	 * Activation: register CPT and flush rewrite rules.
	 *
	 * @return void
	 */
	public static function activate() {
		Webinar_Cards_CPT::register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation: flush rewrite rules.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
