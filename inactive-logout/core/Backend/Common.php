<?php

namespace Codemanas\InactiveLogout\Backend;

use Codemanas\InactiveLogout\Helpers;
use Codemanas\InactiveLogout\Modal;

class Common {

	public function dismissNotices() {
		Helpers::update_option( 'ina_dismiss_like_notice', true );
	}

	private function getPostTypeQuery() {
		$q = filter_input( INPUT_GET, 'q' );

		$args = [
			's'           => $q,
			'post_type'   => apply_filters( 'ina_free_get_custom_post_types', array( 'post', 'page' ) ),
			'post_status' => 'publish',
		];

		// The Query
		return new \WP_Query( $args );
	}

	public function triggerDemoToast() {
		$message = Helpers::getSettings( 'after_redirection_toast_message' );
		$message = ! empty( $message ) ? $message : 'You have been automatically logged out due to inactivity';
		$modal   = Modal::instance();
		$content = $modal->getToastStyle();
		$content .= $modal->getToastHTML( $message );
		wp_send_json( $content );

		wp_die();
	}

	public function filterPostPagesUrl() {
		// The Query
		$posts_query = $this->getPostTypeQuery();

		$posts = [];
		if ( ! empty( $posts_query->have_posts() ) ) {
			foreach ( $posts_query->get_posts() as $post ) {
				$posts[] = [ 'text' => get_permalink( $post->ID ), 'id' => get_permalink( $post->ID ) ];
			}
		}
		wp_reset_postdata();
		wp_send_json( $posts );
		wp_die();
	}

	public function filterPostPagesId() {
		$posts_query = $this->getPostTypeQuery();

		$posts = [];
		if ( ! empty( $posts_query->have_posts() ) ) {
			foreach ( $posts_query->get_posts() as $post ) {
				$posts[] = [ 'text' => $post->post_title, 'id' => $post->ID ];
			}
		}
		wp_reset_postdata();
		wp_send_json( $posts );
		wp_die();
	}

	private static ?Common $_instance = null;

	public static function getInstance(): ?Common {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
