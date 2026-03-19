<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Guest_Token_Service {
	const COOKIE_NAME           = 'tta_threaddesk_guest';
	const TOKEN_BYTES           = 32;
	const TTL_SECONDS           = 604800; // 7 days.
	const INACTIVITY_SECONDS    = 172800; // 48 hours.
	const STATE_WRITE_INTERVAL_SECONDS = 900; // 15 minutes.
	const CLEANUP_EVENT_HOOK    = 'tta_threaddesk_cleanup_guest_posts';
	const TOKEN_TRANSIENT_PREFIX = 'tta_threaddesk_guest_token_';
	const OWNER_META_KEY        = 'tta_guest_token_hash';

	/**
	 * Ensure guest token lifecycle hooks are active.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp', array( $this, 'ensure_guest_token' ), 1 );
		add_action( 'wp_login', array( $this, 'rotate_token' ) );
		add_action( 'wp_logout', array( $this, 'rotate_token' ) );
		add_action( 'admin_post_tta_threaddesk_reset_guest_token', array( $this, 'handle_explicit_reset' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_reset_guest_token', array( $this, 'handle_explicit_reset' ) );
		add_action( self::CLEANUP_EVENT_HOOK, array( $this, 'cleanup_stale_guest_posts' ) );
	}

	/**
	 * Ensure a guest token exists and has not expired by absolute or inactivity windows.
	 *
	 * @return void
	 */
	public function ensure_guest_token() {
		if ( ! $this->should_manage_guest_cookie() || is_user_logged_in() || headers_sent() ) {
			return;
		}

		$token = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
		if ( '' === $token ) {
			$this->rotate_token();
			return;
		}

		$state = $this->get_token_state( $token );
		if ( empty( $state ) ) {
			$this->rotate_token();
			return;
		}

		$now         = time();
		$issued_at   = isset( $state['issued_at'] ) ? (int) $state['issued_at'] : 0;
		$last_seen   = isset( $state['last_seen'] ) ? (int) $state['last_seen'] : 0;
		$expires_at  = $issued_at + $this->get_ttl_seconds();
		$is_inactive = $last_seen > 0 && ( $now - $last_seen ) > $this->get_inactivity_seconds();

		if ( $expires_at <= $now || $is_inactive ) {
			$this->rotate_token();
			return;
		}

		if ( ! $this->should_update_activity_state( $state, $now ) ) {
			return;
		}

		$state['last_seen'] = $now;
		$this->set_token_state( $token, $state );
		$this->send_cookie( $token, $expires_at );
	}

	/**
	 * Rotate guest token and return the new value.
	 *
	 * @return string
	 */
	public function rotate_token() {
		$existing_token = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
		if ( '' !== $existing_token ) {
			delete_transient( $this->transient_key( $existing_token ) );
		}

		if ( headers_sent() ) {
			return '';
		}

		try {
			$token = bin2hex( random_bytes( self::TOKEN_BYTES ) );
		} catch ( Exception $exception ) {
			$token = wp_generate_password( self::TOKEN_BYTES * 2, false, false );
		}
		$now   = time();
		$state = array(
			'issued_at' => $now,
			'last_seen' => $now,
		);

		$this->set_token_state( $token, $state );
		$this->send_cookie( $token, $now + $this->get_ttl_seconds() );
		$_COOKIE[ self::COOKIE_NAME ] = $token;

		return $token;
	}

	/**
	 * Hash a token value for storage on guest-owned resources.
	 *
	 * @param string $token Raw token value.
	 *
	 * @return string
	 */
	public function hash_token( $token ) {
		return hash( 'sha256', (string) $token );
	}

	/**
	 * Remove stale guest-owned designs and layouts.
	 *
	 * @return void
	 */
	public function cleanup_stale_guest_posts() {
		$before      = gmdate( 'Y-m-d H:i:s', time() - $this->get_ttl_seconds() );
		$guest_posts = get_posts(
			array(
				'post_type'      => array( 'tta_layout', 'tta_design' ),
				'post_status'    => array( 'private', 'draft', 'publish', 'pending' ),
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'author'         => 0,
				'date_query'     => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => $before,
					),
				),
				'meta_query'     => array(
					array(
						'key'     => self::OWNER_META_KEY,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $guest_posts as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
	}

	/**
	 * Schedule cleanup if not already queued.
	 *
	 * @return void
	 */
	public function maybe_schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_EVENT_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_EVENT_HOOK );
		}
	}

	/**
	 * @return int
	 */
	public function get_ttl_seconds() {
		return (int) apply_filters( 'tta_threaddesk_guest_token_ttl_seconds', self::TTL_SECONDS );
	}

	/**
	 * @return int
	 */
	public function get_inactivity_seconds() {
		return (int) apply_filters( 'tta_threaddesk_guest_token_inactivity_seconds', self::INACTIVITY_SECONDS );
	}

	/**
	 * @return int
	 */
	public function get_state_write_interval_seconds() {
		return max( 0, (int) apply_filters( 'tta_threaddesk_guest_token_state_write_interval_seconds', self::STATE_WRITE_INTERVAL_SECONDS ) );
	}

	/**
	 * @return void
	 */
	public function handle_explicit_reset() {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'tta_threaddesk_reset_guest_token' ) ) {
			wp_die( esc_html__( 'Invalid token reset request.', 'threaddesk' ) );
		}

		$this->rotate_token();
		$redirect = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : home_url( '/' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @param string $token
	 *
	 * @return array<string,int>
	 */
	private function get_token_state( $token ) {
		$state = get_transient( $this->transient_key( $token ) );
		return is_array( $state ) ? $state : array();
	}

	/**
	 * @param string $token
	 * @param array  $state
	 *
	 * @return void
	 */
	private function set_token_state( $token, $state ) {
		set_transient( $this->transient_key( $token ), $state, $this->get_ttl_seconds() );
	}

	/**
	 * Decide whether request activity should be persisted.
	 *
	 * @param array<string,int> $state Existing token state.
	 * @param int               $now   Current unix timestamp.
	 *
	 * @return bool
	 */
	private function should_update_activity_state( $state, $now ) {
		$last_seen      = isset( $state['last_seen'] ) ? (int) $state['last_seen'] : 0;
		$write_interval = $this->get_state_write_interval_seconds();

		if ( $last_seen <= 0 || $write_interval <= 0 ) {
			return true;
		}

		if ( ( $now - $last_seen ) < $write_interval ) {
			return false;
		}

		if ( $this->is_high_traffic_page() ) {
			$time_since_seen = $now - $last_seen;
			$guard_interval  = max( $write_interval, $write_interval * 2 );

			if ( $time_since_seen < $guard_interval ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	private function transient_key( $token ) {
		return self::TOKEN_TRANSIENT_PREFIX . $this->hash_token( $token );
	}

	/**
	 * @return bool
	 */
	private function should_manage_guest_cookie() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	private function is_high_traffic_page() {
		if ( is_archive() || is_home() || is_front_page() ) {
			return true;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}

		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $token
	 * @param int    $expires
	 *
	 * @return void
	 */
	private function send_cookie( $token, $expires ) {
		$secure = is_ssl() || 'https' === wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );

		setcookie(
			self::COOKIE_NAME,
			$token,
			array(
				'expires'  => (int) $expires,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}
}
