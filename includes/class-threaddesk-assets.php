<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Assets {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		$endpoint = get_query_var( 'thread-desk', false );
		$section  = get_query_var( 'td_section', '' );

		if ( false === $endpoint && empty( $section ) ) {
			return;
		}

		wp_enqueue_style( 'threaddesk', THREDDESK_URL . 'assets/css/threaddesk.css', array(), THREDDESK_VERSION );
		wp_enqueue_script( 'threaddesk', THREDDESK_URL . 'assets/js/threaddesk.js', array( 'jquery' ), THREDDESK_VERSION, true );
	}
}
