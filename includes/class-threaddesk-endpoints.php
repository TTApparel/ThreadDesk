<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Endpoints {
	public function __construct() {
		add_action( 'init', array( $this, 'register_endpoints' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_thread-desk_endpoint', array( $this, 'render_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'register_wc_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'redirect_account_to_threaddesk' ) );
	}

	public function register_endpoints() {
		add_rewrite_endpoint( 'thread-desk', EP_ROOT | EP_PAGES );

		add_rewrite_rule(
			'^my-account/thread-desk/(designs|layouts|quotes|invoices)/?$',
			'index.php?thread-desk=1&td_section=$matches[1]',
			'top'
		);
	}

	public function register_query_vars( $vars ) {
		$vars[] = 'td_section';
		return $vars;
	}

	public function register_wc_query_vars( $vars ) {
		$vars['thread-desk'] = 'thread-desk';
		return $vars;
	}

	public function add_menu_item( $items ) {
		return array(
			'thread-desk' => __( 'ThreadDesk', 'threaddesk' ),
		);
	}

	public function render_endpoint() {
		if ( ! is_user_logged_in() ) {
			echo esc_html__( 'Please log in to view this page.', 'threaddesk' );
			return;
		}

		$section = get_query_var( 'td_section', 'profile' );
		TTA_ThreadDesk::instance()->render->render_section( $section );
	}

	public function redirect_account_to_threaddesk() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		if ( is_wc_endpoint_url( 'thread-desk' ) ) {
			return;
		}

		if ( is_wc_endpoint_url() ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}
	}
}
