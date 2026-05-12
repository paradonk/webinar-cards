<?php
/**
 * Plugin Name: Webinar Cards
 * Plugin URI:  https://www.data-civil.com/webinar
 * Description: Responsive webinar card grid with inline YouTube embeds, managed via a custom post type and a shortcode.
 * Version:     1.1.2
 * Author:      K.Paradorn
 * Author URI:  https://www.data-civil.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: webinar-cards
 * Domain Path: /languages
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WEBINAR_CARDS_VERSION',    '1.1.2' );
define( 'WEBINAR_CARDS_FILE',       __FILE__ );
define( 'WEBINAR_CARDS_PATH',       plugin_dir_path( __FILE__ ) );
define( 'WEBINAR_CARDS_URL',        plugin_dir_url( __FILE__ ) );
define( 'WEBINAR_CARDS_UPDATE_URL', 'https://www.data-civil.com/updates/webinar-cards.json' );

require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards.php';
require_once WEBINAR_CARDS_PATH . 'includes/class-webinar-cards-updater.php';

/**
 * Returns the plugin singleton.
 *
 * @return Webinar_Cards
 */
function webinar_cards() {
	return Webinar_Cards::instance();
}

webinar_cards();

register_activation_hook( __FILE__, array( 'Webinar_Cards', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Webinar_Cards', 'deactivate' ) );
