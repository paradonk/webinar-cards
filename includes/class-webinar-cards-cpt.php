<?php
/**
 * Custom post type and taxonomy registration.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles webinar CPT, taxonomy, and REST meta field registration.
 */
class Webinar_Cards_CPT {

	/**
	 * Hook setup — called on the init action.
	 *
	 * @return void
	 */
	public static function init() {
		self::register_post_type();
		self::register_taxonomy();
		self::register_meta_fields();
	}

	/**
	 * Register the webinar post type.
	 *
	 * Also called directly from the activation hook so rewrite rules flush correctly.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Webinars', 'webinar-cards' ),
			'singular_name'      => __( 'Webinar', 'webinar-cards' ),
			'menu_name'          => __( 'Webinars', 'webinar-cards' ),
			'name_admin_bar'     => __( 'Webinar', 'webinar-cards' ),
			'add_new'            => __( 'Add New', 'webinar-cards' ),
			'add_new_item'       => __( 'Add New Webinar', 'webinar-cards' ),
			'new_item'           => __( 'New Webinar', 'webinar-cards' ),
			'edit_item'          => __( 'Edit Webinar', 'webinar-cards' ),
			'view_item'          => __( 'View Webinar', 'webinar-cards' ),
			'all_items'          => __( 'All Webinars', 'webinar-cards' ),
			'search_items'       => __( 'Search Webinars', 'webinar-cards' ),
			'not_found'          => __( 'No webinars found.', 'webinar-cards' ),
			'not_found_in_trash' => __( 'No webinars found in Trash.', 'webinar-cards' ),
		);

		register_post_type(
			'webinar',
			array(
				'labels'      => $labels,
				'public'      => false,   // no public archive page; output is via the block
				'show_ui'     => true,
				'show_in_menu' => 'marketing-hub',
				'menu_icon'   => 'dashicons-video-alt3',
				'supports'    => array( 'title', 'excerpt', 'thumbnail' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Register the Webinar Category taxonomy.
	 *
	 * @return void
	 */
	public static function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Webinar Categories', 'webinar-cards' ),
			'singular_name'     => __( 'Webinar Category',  'webinar-cards' ),
			'all_items'         => __( 'All Categories',    'webinar-cards' ),
			'edit_item'         => __( 'Edit Category',     'webinar-cards' ),
			'add_new_item'      => __( 'Add New Category',  'webinar-cards' ),
			'new_item_name'     => __( 'New Category Name', 'webinar-cards' ),
			'menu_name'         => __( 'Categories',        'webinar-cards' ),
		);

		register_taxonomy(
			'wbc_category',
			'webinar',
			array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Expose custom meta fields to the REST API.
	 *
	 * Required so Gutenberg's ServerSideRender can pass values to the render callback.
	 *
	 * @return void
	 */
	public static function register_meta_fields() {
		$fields = array(
			'_wbc_video_url' => 'Video URL (YouTube or Vimeo)',
			'_wbc_speaker'   => 'Speaker name',
			'_wbc_date'      => 'Webinar date (Y-m-d)',
		);

		foreach ( $fields as $key => $description ) {
			register_post_meta(
				'webinar',
				$key,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'description'   => $description,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
