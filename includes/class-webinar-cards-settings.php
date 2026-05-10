<?php
/**
 * Access Settings admin page.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Access Settings submenu and handles the save action.
 */
class Webinar_Cards_Settings {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu',                   array( __CLASS__, 'register_menu'  ) );
		add_action( 'admin_post_wbc_save_settings', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Add submenu page under the Webinars CPT menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'marketing-hub',
			__( 'Access Settings', 'webinar-cards' ),
			__( 'Access Settings', 'webinar-cards' ),
			'manage_options',
			'wbc-access-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		$gate    = (bool) get_option( 'wbc_gate_enabled', false );
		$domains = (string) get_option( 'wbc_allowed_domains', '' );
		$emails  = (string) get_option( 'wbc_allowed_emails', '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Webinar Cards — Access Settings', 'webinar-cards' ); ?></h1>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'webinar-cards' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wbc_save_settings', 'wbc_settings_nonce' ); ?>
				<input type="hidden" name="action" value="wbc_save_settings">

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row"><?php esc_html_e( 'Email Gate', 'webinar-cards' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wbc_gate_enabled" value="1" <?php checked( $gate ); ?>>
								<?php esc_html_e( 'Require email verification before watching webinars', 'webinar-cards' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, guests must enter an email that matches an allowed domain or address. Logged-in WordPress users always pass without seeing the gate.', 'webinar-cards' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="wbc_allowed_domains">
								<?php esc_html_e( 'Allowed Domains', 'webinar-cards' ); ?>
							</label>
						</th>
						<td>
							<textarea
								id="wbc_allowed_domains"
								name="wbc_allowed_domains"
								rows="6"
								class="large-text"
								placeholder="company.com&#10;university.edu"
							><?php echo esc_textarea( $domains ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'One domain per line (without @). Any email from a listed domain gets instant access.', 'webinar-cards' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="wbc_allowed_emails">
								<?php esc_html_e( 'Allowed Email Addresses', 'webinar-cards' ); ?>
							</label>
						</th>
						<td>
							<textarea
								id="wbc_allowed_emails"
								name="wbc_allowed_emails"
								rows="8"
								class="large-text"
								placeholder="john@example.com&#10;jane@example.com"
							><?php echo esc_textarea( $emails ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'One email address per line. These specific addresses always pass, regardless of their domain.', 'webinar-cards' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Save Settings', 'webinar-cards' ) ); ?>
			</form>

			<hr style="margin:2em 0;">

			<h2><?php esc_html_e( 'How the gate works', 'webinar-cards' ); ?></h2>
			<ol style="max-width:600px;line-height:1.8;">
				<li><?php esc_html_e( 'Logged-in WordPress users can always watch — no gate is shown.', 'webinar-cards' ); ?></li>
				<li><?php esc_html_e( 'When a guest clicks a video thumbnail, a modal asks for their email address.', 'webinar-cards' ); ?></li>
				<li><?php esc_html_e( 'If the email matches an allowed domain or specific address, the video plays immediately.', 'webinar-cards' ); ?></li>
				<li><?php esc_html_e( 'If the email is not on any list, the guest is offered a link to sign in or register.', 'webinar-cards' ); ?></li>
				<li><?php esc_html_e( 'Granted access is remembered for 24 hours via a browser cookie — the gate will not reappear.', 'webinar-cards' ); ?></li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Handle the save-settings form POST.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! isset( $_POST['wbc_settings_nonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['wbc_settings_nonce'] ) ),
				'wbc_save_settings'
			)
		) {
			wp_die( esc_html__( 'Security check failed.', 'webinar-cards' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'webinar-cards' ) );
		}

		update_option( 'wbc_gate_enabled',    ! empty( $_POST['wbc_gate_enabled'] ) ? 1 : 0 );
		update_option( 'wbc_allowed_domains', sanitize_textarea_field( wp_unslash( $_POST['wbc_allowed_domains'] ?? '' ) ) );
		update_option( 'wbc_allowed_emails',  sanitize_textarea_field( wp_unslash( $_POST['wbc_allowed_emails']  ?? '' ) ) );

		wp_safe_redirect( admin_url( 'admin.php?page=wbc-access-settings&updated=1' ) );
		exit;
	}
}
