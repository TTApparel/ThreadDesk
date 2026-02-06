<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Render {
	public function render_section( $section ) {
		$section = sanitize_key( $section );

		$allowed = array( 'profile', 'designs', 'layouts', 'quotes', 'invoices' );
		if ( ! in_array( $section, $allowed, true ) ) {
			$section = 'profile';
		}

		$data = TTA_ThreadDesk::instance()->data->get_dashboard_data( $section );

		switch ( $section ) {
			case 'designs':
				$this->load_template( 'dashboard-designs.php', $data );
				break;
			case 'layouts':
				$this->load_template( 'dashboard-layouts.php', $data );
				break;
			case 'quotes':
				$this->load_template( 'dashboard-quotes.php', $data );
				break;
			case 'invoices':
				$this->load_template( 'dashboard-invoices.php', $data );
				break;
			case 'profile':
			default:
				$this->load_template( 'dashboard-profile.php', $data );
				break;
		}
	}

	private function load_template( $template, $data ) {
		$template_path = THREDDESK_PATH . 'templates/' . $template;
		if ( file_exists( $template_path ) ) {
			$context = $data;
			include $template_path;
		}
	}
}
