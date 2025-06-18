<?php

namespace Codemanas\InactiveLogout;

/**
 * Class with a few helpers
 *
 * @package inactive-logout
 */
class Helpers {

	public static $message;

	/**
	 * Convert seconds to minutes.
	 *
	 * @param  int  $value  Number of seconds.
	 *
	 * @return string
	 */
	public static function convertToMinutes( $value ) {
		$minutes = floor( $value / 60 );

		return $minutes . ' ' . esc_html__( 'Minute(s)', 'inactive-logout' );
	}

	/**
	 * Get all roles.
	 *
	 * @return array List of roles.
	 */
	public static function getAllRoles() {
		global $wp_roles;

		$result = array();
		foreach ( $wp_roles->roles as $role => $details ) {
			$result[ $role ] = $details['name'];
		}

		$result['no_role'] = 'No User Roles assigned users';

		return $result;
	}

	/**
	 * Check role is available in settings for multi-user.
	 *
	 * @param  null|string  $role  Name of role, default is null.
	 *
	 * @return bool Returns true if passed role is available, Otherwise false.
	 */
	public static function CheckRoleForMultiUser( $role = null ) {
		$selected = false;
		if ( ! empty( $role ) ) {
			$ina_multiuser_settings = self::get_option( '__ina_multiusers_settings' );
			if ( ! empty( $ina_multiuser_settings ) ) {
				foreach ( $ina_multiuser_settings as $ina_multiuser_setting ) {
					if ( in_array( $role, $ina_multiuser_setting, true ) ) {
						$selected = true;
					}
				}
			}
		}

		return $selected;
	}

	private static function get_bool_option( $key, $default = false ) {
		return (bool) Helpers::get_option( $key ) ?? $default;
	}

	private static function get_option_with_default( $key, $default ) {
		$value = Helpers::get_option( $key );

		return ! empty( $value ) ? $value : $default;
	}

	/**
     * Get whole settings or a desired setting
     *
	 * @param $type
	 *
	 * @return false|mixed|string|null
	 */
	public static function getSettings( $type = '' ) {
		$settings = get_option( '__ina_general_settings' );
		if ( ! empty( $type ) ) {
			return ! empty( $settings[ $type ] ) ? $settings[ $type ] : '';
		}

		return $settings;
	}

	/**
	 * Get inactive logout settings
	 *
	 * @return \stdClass
	 */
	public static function getInactiveSettingsData() {
		$settings                         = new \stdClass();
		$settings->logout_time            = self::get_option_with_default( '__ina_logout_time', 15 * 60 );
		$settings->prompt_countdown_timer = self::get_option_with_default( '__ina_countdown_timeout', 10 );
		$settings->disable_prompt_timer   = self::get_bool_option( '__ina_disable_countdown' );
		$settings->warn_only_enable       = self::get_bool_option( '__ina_warn_message_enabled' );
		$settings->popup_behaviour        = ( $settings->warn_only_enable && ! empty( Helpers::get_option( '__ina_popup_behaviour' ) ) ) ? Helpers::get_option( '__ina_popup_behaviour' ) : false;
		$settings->concurrent_enabled     = self::get_bool_option( '__ina_concurrent_login' );
		$settings->enabled_redirect       = self::get_bool_option( '__ina_enable_redirect' );
		$settings->redirect_page_link     = self::get_option_with_default( '__ina_redirect_page_link', false );
		$settings->automatic_redirect     = self::get_bool_option( '__ina_disable_automatic_redirect_on_logout' );
		$settings->debugger               = self::get_bool_option( '__ina_enable_debugger' );

		if ( ! empty( Helpers::get_option( '__ina_enable_timeout_multiusers' ) ) ) {
			global $current_user;
			$user_roles          = $current_user->roles ?? [];
			$multi_user_settings = Helpers::get_option( '__ina_multiusers_settings' ) ?? [];
			if ( ! empty( $multi_user_settings ) ) {
				$result = array_values( array_filter( $multi_user_settings, function ( $data ) use ( $user_roles ) {
					if ( empty( $user_roles ) ) {
						return isset( $data['role'] ) && $data['role'] === 'no_role';
					}

					return isset( $data['role'] ) && in_array( $data['role'], $user_roles, true );
				} ) );

				if ( ! empty( $result ) ) {
					$settings->advanced = $result[0];
				}
			}
		}

		return $settings;
	}

	/**
	 * Check if pro version is active
	 *
	 * @return bool
	 */
	public static function is_pro_version_active() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'inactive-logout-addon/inactive-logout-addon.php' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get Option based on multisite or only one site
	 *
	 * @param $key
	 *
	 * @return mixed|void
	 */
	public static function get_option( $key ) {
		if ( is_multisite() ) {
			$network_id = get_main_network_id();
			$override   = get_network_option( $network_id, '__ina_overrideby_multisite_setting' );
			if ( ! empty( $override ) ) {
				return get_network_option( $network_id, $key );
			}

			if ( is_main_site() || is_network_admin() ) {
				return get_site_option( $key );
			} else {
				return get_option( $key );
			}
		}

		return get_option( $key );
	}

	public static function isMultisite(): bool {
		return is_multisite() && get_current_blog_id() == get_main_network_id();
	}

	/**
	 * Update option
	 *
	 * @param $key
	 * @param $value
	 * @param  bool  $autoload
	 */
	public static function update_option( $key, $value, $autoload = true ) {
		if ( self::isMultisite() ) {
			update_site_option( $key, $value );
		} else {
			update_option( $key, $value, $autoload );
		}
	}

	/**
	 * Get Logout redirect page link.
	 *
	 * @param  bool  $settings
	 *
	 * @return false|mixed|string
	 */
	public static function getLogoutRedirectPage( $settings = false ) {
		if ( empty( $settings ) ) {
			$settings = Helpers::getInactiveSettingsData();
		}

		if ( ! empty( $settings->advanced ) && ! empty( $settings->advanced['redirect_page'] ) ) {
			$redirect_link = get_the_permalink( $settings->advanced['redirect_page'] );

			return ! empty( $redirect_link ) ? $redirect_link : $settings->advanced['redirect_page'];
		} elseif ( ! empty( $settings->enabled_redirect ) ) {
			if ( 'custom-page-redirect' == $settings->redirect_page_link ) {
				$ina_redirect_page_link = Helpers::get_option( '__ina_custom_redirect_text_field' );
				$redirect_link          = $ina_redirect_page_link;
			} else {
				$redirect_link = get_the_permalink( $settings->redirect_page_link );
			}
		}

		return ! empty( $redirect_link ) ? $redirect_link : false;
	}

	/**
	 * Get the admin notice message
	 *
	 * @return mixed|null
	 */
	public static function getMessage() {
		$session_message = Helpers::get_option( '__ina_saved_options' );
		if ( $session_message ) {
			Helpers::update_option( '__ina_saved_options', '', false );

			return $session_message;
		}

		return apply_filters( 'ina_admin_get_message', self::$message );
	}

	/**
	 * Set admin notice message
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function set_message( $message ) {
		self::$message = $message;
	}
}