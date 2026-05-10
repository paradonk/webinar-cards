<?php
/**
 * Admin meta box for webinar-specific fields.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the Webinar Details meta box.
 */
class Webinar_Cards_Meta {

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'wbc_webinar_details',
			__( 'Webinar Details', 'webinar-cards' ),
			array( __CLASS__, 'render_meta_box' ),
			'webinar',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box HTML.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'wbc_save_meta', 'wbc_meta_nonce' );

		$video_url = (string) get_post_meta( $post->ID, '_wbc_video_url', true );
		$speaker   = (string) get_post_meta( $post->ID, '_wbc_speaker',   true );
		$date      = (string) get_post_meta( $post->ID, '_wbc_date',      true );

		$platform = Webinar_Cards_Helpers::detect_platform( $video_url );
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="wbc_video_url"><?php esc_html_e( 'Video URL', 'webinar-cards' ); ?></label>
				</th>
				<td>
					<input
						type="url"
						id="wbc_video_url"
						name="wbc_video_url"
						value="<?php echo esc_attr( $video_url ); ?>"
						class="regular-text"
						placeholder="https://www.youtube.com/watch?v=…  or  https://vimeo.com/…"
					/>
					<p class="description">
						<?php esc_html_e( 'YouTube: watch, youtu.be, embed, or Shorts URL.', 'webinar-cards' ); ?>
						&nbsp;|&nbsp;
						<?php esc_html_e( 'Vimeo: vimeo.com/VIDEO_ID or channel/group URLs.', 'webinar-cards' ); ?>
						<?php if ( ! empty( $platform['platform'] ) ) : ?>
							&nbsp;&#10003;&nbsp;<strong><?php echo esc_html( ucfirst( $platform['platform'] ) ); ?></strong>
						<?php endif; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wbc_speaker"><?php esc_html_e( 'Speaker', 'webinar-cards' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="wbc_speaker"
						name="wbc_speaker"
						value="<?php echo esc_attr( $speaker ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wbc_date"><?php esc_html_e( 'Date', 'webinar-cards' ); ?></label>
				</th>
				<td>
					<input
						type="date"
						id="wbc_date"
						name="wbc_date"
						value="<?php echo esc_attr( $date ); ?>"
					/>
				</td>
			</tr>
		</table>
		<p class="description" style="margin-top:12px;">
			<?php esc_html_e( 'Short description: use the Excerpt field above (visible after enabling it via Screen Options).', 'webinar-cards' ); ?>
		</p>
		<?php
	}

	/**
	 * Save meta box values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object (unused but required by hook signature).
	 * @return void
	 */
	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['wbc_meta_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wbc_meta_nonce'] ) ), 'wbc_save_meta' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$video_url = isset( $_POST['wbc_video_url'] )
			? esc_url_raw( wp_unslash( $_POST['wbc_video_url'] ) )
			: '';

		$speaker = isset( $_POST['wbc_speaker'] )
			? sanitize_text_field( wp_unslash( $_POST['wbc_speaker'] ) )
			: '';

		$date = isset( $_POST['wbc_date'] )
			? Webinar_Cards_Helpers::sanitize_date( wp_unslash( $_POST['wbc_date'] ) )
			: '';

		update_post_meta( $post_id, '_wbc_video_url', $video_url );
		update_post_meta( $post_id, '_wbc_speaker',   $speaker );
		update_post_meta( $post_id, '_wbc_date',      $date );
	}
}
