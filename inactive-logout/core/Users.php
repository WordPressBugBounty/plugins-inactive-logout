<?php

namespace Codemanas\InactiveLogout;

class Users {

	/**
	 * Logout the actual session from here
	 *
	 * @since 3.0.0
	 * @author Deepen
	 */
	public function logoutSession() {
		check_ajax_referer( '_inaajax', 'security' );
		if ( is_user_logged_in() ) {
			$this->setToastMessage();
			wp_logout();
		}

		wp_send_json( array(
			'isLoggedIn' => is_user_logged_in()
		) );

		wp_die();
	}

	private function setToastMessage() {
		if ( ! empty( Helpers::getSettings( 'show_toast_notification' ) ) ) {
			setcookie( 'ina_redirection_logged_out', true, time() + 120, "/" );
		}
	}

	private static ?Users $_instance = null;

	public static function getInstance(): ?Users {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}