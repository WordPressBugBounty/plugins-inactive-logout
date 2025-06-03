<?php

namespace Codemanas\InactiveLogout;

/**
 * Core Functions for Concurrent Logins
 *
 * Derived from Prevent Concurrent Logins by Frankie Jarrett
 *
 * @since  1.1.0
 */
class ConcurrentLogin {

	/**
	 * Inactive_Concurrent_Login_Functions constructor.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'concurrent_logins' ) );
//		$this->concurrent_logins();
	}

	/**
	 * Detect if the current user has multiple sessions
	 *
	 * @return bool
	 * @since  1.1.0
	 */
	public function user_has_multiple_sessions() {
		return ( is_user_logged_in() && count( wp_get_all_sessions() ) > 1 );
	}

	/**
	 * Get the user's current session array
	 *
	 * @return array
	 */
	public function get_current_session() {
		$sessions = \WP_Session_Tokens::get_instance( get_current_user_id() );

		return $sessions->get( wp_get_session_token() );
	}

	/**
	 * Only allow one session per user
	 *
	 * If the current user's session has been taken over by a newer
	 * session then we will destroy their session automattically and
	 * they will have to login again to continue.
	 *
	 * @action init
	 */
	public function concurrent_logins() {
		if ( ! $this->user_has_multiple_sessions() ) {
			return;
		}

		$user_id = get_current_user_id();

		/**
		 * Filter to allow certain users to have concurrent sessions when necessary
		 *
		 * @param  bool  $prevent
		 * @param  int  $user_id  ID of the current user
		 *
		 * @return bool
		 */
		if ( false === (bool) apply_filters( 'ina_allow_multiple_sessions', true, $user_id ) ) {
			return;
		}

		/**
		 * Filter to limit number of concurrent users
		 */
		if ( false === (bool) apply_filters( 'ina_limit_logins_by_count', $user_id ) ) {
			return;
		}

		// Finding maximum value of all sessions available.
		$sessions = wp_get_all_sessions();
		$newest   = max( wp_list_pluck( $sessions, 'login' ) );
		$session  = $this->get_current_session();
		if ( $session['login'] === $newest ) {
			wp_destroy_other_sessions();

			do_action( 'ina_otherSessionsLoggedOut', $user_id );
		} else {
			wp_destroy_current_session();

			do_action( 'ina_currentSessionsLoggedOut', $user_id );
		}
	}

	/**
	 * Instance property
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * Create only one instance so that it may not Repeat
	 *
	 * @since 1.0.0
	 */
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
