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
		$items['thread-desk'] = __( 'ThreadDesk', 'threaddesk' );
		return $items;
	}

	public function render_endpoint() {
		if ( ! is_user_logged_in() ) {
			echo esc_html__( 'Please log in to view this page.', 'threaddesk' );
			return;
		}

		$section = get_query_var( 'td_section', 'profile' );
		TTA_ThreadDesk::instance()->render->render_section( $section );
	}
}
