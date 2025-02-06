<?php

namespace Codemanas\InactiveLogout;

class Compatibility {

	public function __construct() {
		if ( is_admin() ) {
			$this->removeWishListMemberRestrictions();
		}
	}

	public function removeWishListMemberRestrictions() {
		$removeActions = apply_filters( 'ina_removeWishListMemberRestrictions', true );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wishlist-member/wpm.php' ) && $removeActions ) {
			global $WishListMemberInstance;
			remove_action( 'admin_enqueue_scripts', [ $WishListMemberInstance, 'remove_theme_and_plugins_scripts_and_styles' ], 999999999 );
			remove_action( 'admin_head', [ $WishListMemberInstance, 'remove_theme_and_plugins_scripts_and_styles' ], 999999999 );
			remove_action( 'admin_footer', [ $WishListMemberInstance, 'remove_theme_and_plugins_scripts_and_styles' ], 999999999 );

			add_action( 'admin_enqueue_scripts', [ $this, 'onlyEnqueueInactiveScripts' ], 999999999 );
			add_action( 'admin_head', [ $this, 'onlyEnqueueInactiveScripts' ], 999999999 );
			add_action( 'admin_footer', [ $this, 'onlyEnqueueInactiveScripts' ], 999999999 );
		}
	}


	public function onlyEnqueueInactiveScripts() {
		global $wp_styles, $wp_scripts;

		if ( wlm_get_data()['page'] != 'WishListMember' ) {
			return;
		}

		// Regex to match all themes and plugins except ours.
		$regex = '#/wp-content/(themes/|plugins/(?!' . preg_quote( basename( WLM_PLUGIN_DIR ) ) . ').+?/)#i';

		// Regex of style handles to remove.
		// Dg_admin_styles = Divi Carousel Plugin.
		// Widgetkit-admin = All-in-One Addons for Elementor - WidgetKitwidgetkit-for-elementor.
		$style_handles_regex = '#^(testify\-|optimizepress\-|dg_admin_styles|widgetkit\-admin|offloadingstyle|publitio)#';

		// Selectively remove styles.
		foreach ( $wp_styles->registered as $style ) {
			if ( preg_match( $regex, $style->src ) && preg_match( $style_handles_regex, $style->handle ) ) {
				wp_deregister_style( $style->handle );
			}
		}

		$allowed_scripts = [ 'inactive-logout', 'ina-logout-addon-bundler' ];
		foreach ( $wp_scripts->registered as $handle => $script ) {
			if ( isset( $script->src, $script->handle ) && ! in_array( $script->handle, $allowed_scripts, true ) && preg_match( $regex, $script->src ) ) {
				wp_deregister_script( $handle );
			}
		}
	}

	private static $_instance = null;

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}