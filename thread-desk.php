<?php
/**
 * Plugin Name: ThreadDesk
 * Description: Customer portal inside WooCommerce My Account for quotes, invoices, designs, and layouts.
 * Version: 1.0.1
 * Author: ThreadDesk
 * Text Domain: threaddesk
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'THREDDESK_VERSION' ) ) {
	define( 'THREDDESK_VERSION', '1.0.1' );
}

if ( ! defined( 'THREDDESK_PATH' ) ) {
	define( 'THREDDESK_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'THREDDESK_URL' ) ) {
	define( 'THREDDESK_URL', plugin_dir_url( __FILE__ ) );
}

require_once THREDDESK_PATH . 'includes/class-threaddesk.php';
require_once THREDDESK_PATH . 'includes/class-threaddesk-assets.php';
require_once THREDDESK_PATH . 'includes/class-threaddesk-endpoints.php';
require_once THREDDESK_PATH . 'includes/class-threaddesk-render.php';
require_once THREDDESK_PATH . 'includes/class-threaddesk-data.php';

register_activation_hook( __FILE__, array( 'TTA_ThreadDesk', 'activate' ) );

TTA_ThreadDesk::instance();
