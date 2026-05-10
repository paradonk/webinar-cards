<?php
/**
 * Shared helper utilities.
 *
 * @package WebinarCards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless utility methods used by other classes.
 */
class Webinar_Cards_Helpers {

	/**
	 * Detect the video platform and extract the video ID from a URL.
	 *
	 * @param string $url Raw video URL.
	 * @return array{ platform: string, id: string }
	 *   platform — 'youtube', 'vimeo', or '' if not recognised.
	 *   id       — platform video ID, or '' on failure.
	 */
	public static function detect_platform( $url ) {
		$youtube_id = self::extract_youtube_id( $url );
		if ( $youtube_id ) {
			return array( 'platform' => 'youtube', 'id' => $youtube_id );
		}

		$vimeo_id = self::extract_vimeo_id( $url );
		if ( $vimeo_id ) {
			return array( 'platform' => 'vimeo', 'id' => $vimeo_id );
		}

		return array( 'platform' => '', 'id' => '' );
	}

	/**
	 * Extract an 11-character YouTube video ID from a URL.
	 *
	 * Handles:
	 *   https://www.youtube.com/watch?v=ID
	 *   https://youtu.be/ID
	 *   https://www.youtube.com/embed/ID
	 *   https://www.youtube.com/shorts/ID
	 *
	 * @param string $url Raw YouTube URL.
	 * @return string Video ID, or '' if not recognisable.
	 */
	public static function extract_youtube_id( $url ) {
		$url = trim( (string) $url );
		if ( empty( $url ) ) {
			return '';
		}

		if ( preg_match( '#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}

		if ( preg_match( '#youtube\.com/(?:watch\?v=|embed/|shorts/)([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}

		return '';
	}

	/**
	 * Extract a numeric Vimeo video ID from a URL.
	 *
	 * Handles:
	 *   https://vimeo.com/VIDEO_ID
	 *   https://vimeo.com/channels/CHANNEL/VIDEO_ID
	 *   https://vimeo.com/groups/GROUP/videos/VIDEO_ID
	 *   https://player.vimeo.com/video/VIDEO_ID
	 *
	 * @param string $url Raw Vimeo URL.
	 * @return string Numeric video ID, or '' if not recognisable.
	 */
	public static function extract_vimeo_id( $url ) {
		$url = trim( (string) $url );
		if ( empty( $url ) ) {
			return '';
		}

		if ( preg_match( '#vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)#', $url, $m ) ) {
			return $m[1];
		}

		return '';
	}

	/**
	 * Return a YouTube thumbnail URL for the given video ID.
	 *
	 * Uses mqdefault (320×180, true 16:9) — guaranteed to exist for every
	 * YouTube video, no black bars, one request per card.
	 *
	 * @param string $video_id YouTube video ID.
	 * @return string
	 */
	public static function youtube_thumbnail_url( $video_id ) {
		return 'https://img.youtube.com/vi/' . rawurlencode( $video_id ) . '/mqdefault.jpg';
	}

	/**
	 * Fetch a Vimeo thumbnail URL via the oEmbed API.
	 *
	 * Result is cached in a transient for 30 days so subsequent renders
	 * never make an HTTP request. On API failure the empty string is cached
	 * for 1 hour so a transient outage retries quickly.
	 *
	 * @param string $video_id Numeric Vimeo video ID.
	 * @return string Thumbnail URL, or '' on failure.
	 */
	public static function vimeo_thumbnail_url( $video_id ) {
		$transient_key = 'wbc_vimeo_thumb_' . $video_id;
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$response = wp_remote_get(
			'https://vimeo.com/api/oembed.json?url=' . rawurlencode( 'https://vimeo.com/' . $video_id ),
			array( 'timeout' => 5 )
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $transient_key, '', HOUR_IN_SECONDS );
			return '';
		}

		$data  = json_decode( wp_remote_retrieve_body( $response ) );
		$thumb = isset( $data->thumbnail_url ) ? (string) $data->thumbnail_url : '';

		set_transient( $transient_key, $thumb, $thumb ? 30 * DAY_IN_SECONDS : HOUR_IN_SECONDS );

		return $thumb;
	}

	/**
	 * Backward-compatible alias for extract_youtube_id().
	 *
	 * @param string $url Raw YouTube URL.
	 * @return string
	 */
	public static function extract_video_id( $url ) {
		return self::extract_youtube_id( $url );
	}

	/**
	 * Backward-compatible alias for youtube_thumbnail_url().
	 *
	 * @param string $video_id YouTube video ID.
	 * @return string
	 */
	public static function thumbnail_url( $video_id ) {
		return self::youtube_thumbnail_url( $video_id );
	}

	/**
	 * Sanitize and validate a Y-m-d date string.
	 *
	 * @param string $date Raw date input.
	 * @return string Sanitized Y-m-d date, or '' on failure.
	 */
	public static function sanitize_date( $date ) {
		$date = trim( (string) $date );
		if ( empty( $date ) ) {
			return '';
		}

		$dt     = \DateTime::createFromFormat( 'Y-m-d', $date );
		$errors = \DateTime::getLastErrors();

		if ( false === $dt || ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) {
			return '';
		}

		return $dt->format( 'Y-m-d' );
	}

	/**
	 * Format a Y-m-d date for localised display (e.g. "5 January 2025").
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return string Human-readable date, or '' if empty/invalid.
	 */
	public static function format_date( $date ) {
		if ( empty( $date ) ) {
			return '';
		}

		$dt = date_create( $date );
		return $dt ? wp_date( 'j F Y', $dt->getTimestamp() ) : '';
	}
}
