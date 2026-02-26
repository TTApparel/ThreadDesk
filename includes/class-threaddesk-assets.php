<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Assets {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		if ( $this->is_shortcode_present() ) {
			$this->enqueue_core_assets();
			return;
		}

		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		$endpoint = get_query_var( 'thread-desk', false );
		$section  = get_query_var( 'td_section', '' );

		if ( false === $endpoint && empty( $section ) ) {
			return;
		}

		$this->enqueue_core_assets();
	}

	private function enqueue_core_assets() {
		wp_enqueue_style( 'threaddesk', THREDDESK_URL . 'assets/css/threaddesk.css', array(), THREDDESK_VERSION );
		wp_enqueue_script( 'threaddesk', THREDDESK_URL . 'assets/js/threaddesk.js', array( 'jquery' ), THREDDESK_VERSION, true );
	}

	private function is_shortcode_present() {
		if ( ! is_singular() ) {
			return false;
		}

		global $post;

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'threaddesk' ) || has_shortcode( $post->post_content, 'threaddesk_screenprint' ) || has_shortcode( $post->post_content, 'threaddesk_auth' );
	}
}
