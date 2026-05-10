<?php
/**
 * Email-gate access control.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles allowed-email/domain rules, token cookies, and AJAX validation.
 */
class Webinar_Cards_Access {

	const COOKIE_NAME  = 'wbc_access';
	const GRANT_COOKIE = 'wbc_granted'; // JS-readable flag (no sensitive data)
	const TOKEN_TTL    = DAY_IN_SECONDS; // 24 h
	const VERIFY_TTL   = 600;            // 10 minutes

	/**
	 * Register AJAX handlers.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_wbc_validate_email',        array( __CLASS__, 'ajax_validate_email' ) );
		add_action( 'wp_ajax_nopriv_wbc_validate_email', array( __CLASS__, 'ajax_validate_email' ) );
		add_action( 'wp_ajax_wbc_verify_code',           array( __CLASS__, 'ajax_verify_code' ) );
		add_action( 'wp_ajax_nopriv_wbc_verify_code',    array( __CLASS__, 'ajax_verify_code' ) );
	}

	/**
	 * True when the email gate is turned on in settings.
	 *
	 * @return bool
	 */
	public static function is_gate_enabled() {
		return (bool) get_option( 'wbc_gate_enabled', false );
	}

	/**
	 * True if the current request should skip the gate entirely.
	 *
	 * Logged-in users always pass. Guests pass when they hold a valid
	 * access-token cookie that resolves to an allowed email.
	 *
	 * @return bool
	 */
	public static function visitor_has_access() {
		if ( is_user_logged_in() ) {
			return true;
		}

		$token = isset( $_COOKIE[ self::COOKIE_NAME ] )
			? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) )
			: '';

		if ( ! $token ) {
			return false;
		}

		$email = get_transient( 'wbc_token_' . $token );
		return $email && self::email_is_allowed( (string) $email );
	}

	/**
	 * True if $email matches any allowed address or any allowed domain.
	 *
	 * @param string $email Email to test.
	 * @return bool
	 */
	public static function email_is_allowed( $email ) {
		$email = strtolower( trim( (string) $email ) );
		if ( ! is_email( $email ) ) {
			return false;
		}

		// Specific email addresses.
		$raw    = (string) get_option( 'wbc_allowed_emails', '' );
		$emails = array_filter( array_map( 'trim', explode( "\n", strtolower( $raw ) ) ) );
		if ( in_array( $email, $emails, true ) ) {
			return true;
		}

		// Domains — match the part after @.
		$domain  = substr( $email, strrpos( $email, '@' ) + 1 );
		$raw     = (string) get_option( 'wbc_allowed_domains', '' );
		$domains = array_filter( array_map( 'trim', explode( "\n", strtolower( $raw ) ) ) );
		if ( in_array( $domain, $domains, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * AJAX: validate a submitted email address.
	 *
	 * On success: creates a 24-hour transient token and sets the cookie.
	 * On failure: returns login / register URLs so JS can offer them.
	 *
	 * @return void
	 */
	public static function ajax_validate_email() {
		check_ajax_referer( 'wbc_gate_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_email',
					'message' => __( 'Please enter a valid email address.', 'webinar-cards' ),
				)
			);
		}

		if ( self::email_is_allowed( $email ) ) {
			if ( ! self::send_verification_code( $email ) ) {
				wp_send_json_error( array(
					'message' => __( 'Could not send verification email. Please try again.', 'webinar-cards' ),
				) );
			}
			wp_send_json_success( array( 'step' => 'verify' ) );
		}

		// Not on any list — return login / register URLs with redirect back here.
		$redirect = isset( $_SERVER['HTTP_REFERER'] )
			? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
			: home_url();

		wp_send_json_error(
			array(
				'code'         => 'not_allowed',
				'message'      => __( 'Your email is not on the access list. Please sign in or register to continue.', 'webinar-cards' ),
				'login_url'    => wp_login_url( $redirect ),
				'register_url' => wp_registration_url(),
			)
		);
	}

	/**
	 * AJAX: verify the one-time code submitted by the visitor.
	 *
	 * Consumes the code transient on success and sets the access cookies.
	 *
	 * @return void
	 */
	public static function ajax_verify_code() {
		check_ajax_referer( 'wbc_gate_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$code  = isset( $_POST['code']  ) ? preg_replace( '/\D/', '', wp_unslash( $_POST['code'] ) ) : '';

		if ( ! is_email( $email ) || ! $code ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'webinar-cards' ) ) );
		}

		$key    = 'wbc_verify_' . md5( strtolower( $email ) );
		$stored = get_transient( $key );

		if ( false === $stored || ! hash_equals( (string) $stored, (string) $code ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid or expired code. Please try again.', 'webinar-cards' ),
			) );
		}

		delete_transient( $key );
		self::grant_access( $email );

		wp_send_json_success( array( 'message' => __( 'Access granted.', 'webinar-cards' ) ) );
	}

	/**
	 * Generate a 6-digit code, store it in a transient, and email it to the visitor.
	 *
	 * @param string $email Verified, allowed email address.
	 * @return bool True if wp_mail() reported success.
	 */
	private static function send_verification_code( $email ) {
		$code = str_pad( (string) wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
		set_transient( 'wbc_verify_' . md5( strtolower( $email ) ), $code, self::VERIFY_TTL );

		$site_name = get_bloginfo( 'name' );
		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] Your webinar access code', 'webinar-cards' ), $site_name );
		$message = sprintf(
			/* translators: %s: 6-digit code */
			__( "Your one-time webinar access code is:\n\n    %s\n\nThis code expires in 10 minutes.\n\nIf you did not request this, you can ignore this email.", 'webinar-cards' ),
			$code
		);

		return wp_mail( $email, $subject, $message );
	}

	/**
	 * Issue a 24-hour access token and set the access cookies.
	 *
	 * @param string $email Verified email address.
	 * @return void
	 */
	private static function grant_access( $email ) {
		$token = wp_generate_password( 40, false );
		set_transient( 'wbc_token_' . $token, strtolower( $email ), self::TOKEN_TTL );

		$cookie_opts = array(
			'expires'  => time() + self::TOKEN_TTL,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'samesite' => 'Lax',
		);

		// Secure token — PHP verification only (httponly so JS cannot read it).
		setcookie( self::COOKIE_NAME, $token, array_merge( $cookie_opts, array( 'httponly' => true ) ) );

		// JS-readable grant flag — contains no sensitive data, just '1'.
		// Lets the client-side gate check work correctly even when the page HTML
		// is served from a cache (e.g. NitroPack, LiteSpeed, WP Rocket).
		setcookie( self::GRANT_COOKIE, '1', array_merge( $cookie_opts, array( 'httponly' => false ) ) );
	}
}
