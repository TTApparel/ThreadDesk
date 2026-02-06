<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Data {
	public function get_dashboard_data( $section ) {
		$user_id = get_current_user_id();
		$user    = get_user_by( 'id', $user_id );

		$stats = $this->get_order_stats( $user_id );

		$data = array(
			'user'              => $user,
			'section'           => $section,
			'cover_image'       => get_option( 'tta_threaddesk_cover_image_url', '' ),
			'company'           => get_option( 'tta_threaddesk_default_company', '' ),
			'order_stats'       => $stats,
			'design_count'      => $this->get_post_count( 'tta_design', $user_id ),
			'layout_count'      => $this->get_post_count( 'tta_layout', $user_id ),
			'quotes_count'      => $this->get_post_count( 'tta_quote', $user_id ),
			'recent_activity'   => $this->get_recent_activity( $user_id ),
			'quotes'            => $this->get_user_quotes( $user_id ),
			'designs'           => $this->get_user_designs( $user_id ),
			'layouts'           => $this->get_user_layouts( $user_id ),
			'orders'            => $this->get_user_orders( $user_id ),
			'account_links'     => $this->get_account_links(),
			'billing_address'   => $this->get_user_address( $user_id, 'billing' ),
			'shipping_address'  => $this->get_user_address( $user_id, 'shipping' ),
			'account_details'   => $this->get_account_details( $user ),
			'currency'          => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
			'outstanding_total' => $this->get_outstanding_total( $user_id ),
		);

		return $data;
	}

	public function get_order_stats( $user_id ) {
		$orders = $this->get_user_orders( $user_id );

		if ( empty( $orders ) ) {
			return array(
				'last_order'   => __( 'No orders yet', 'threaddesk' ),
				'avg_order'    => 0,
				'lifetime'     => 0,
				'order_count'  => 0,
				'last_order_date' => '',
			);
		}

		$total = 0;
		$last  = '';
		$last_date = '';

		foreach ( $orders as $order ) {
			$total += (float) $order->get_total();
			if ( empty( $last ) || $order->get_date_created()->getTimestamp() > $last ) {
				$last = $order->get_date_created()->getTimestamp();
				$last_date = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );
			}
		}

		$avg = $total / count( $orders );

		return array(
			'last_order'      => $last_date,
			'avg_order'       => $avg,
			'lifetime'        => $total,
			'order_count'     => count( $orders ),
			'last_order_date' => $last_date,
		);
	}

	public function get_post_count( $post_type, $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'private',
				'author'         => $user_id,
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);

		return (int) $query->found_posts;
	}

	public function get_user_quotes( $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'tta_quote',
				'post_status'    => 'private',
				'author'         => $user_id,
				'posts_per_page' => 10,
			)
		);

		return $query->posts;
	}

	public function get_user_designs( $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'tta_design',
				'post_status'    => 'private',
				'author'         => $user_id,
				'posts_per_page' => 10,
			)
		);

		return $query->posts;
	}

	public function get_user_layouts( $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'tta_layout',
				'post_status'    => 'private',
				'author'         => $user_id,
				'posts_per_page' => 10,
			)
		);

		return $query->posts;
	}

	public function get_user_orders( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		return wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 10,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
	}

	public function get_recent_activity( $user_id ) {
		$activity = array();
		$orders   = $this->get_user_orders( $user_id );
		$quotes   = $this->get_user_quotes( $user_id );

		foreach ( $orders as $order ) {
			$activity[] = array(
				'label' => sprintf( __( '#%1$s Order placed', 'threaddesk' ), $order->get_order_number() ),
				'date'  => $order->get_date_created()->date_i18n( get_option( 'date_format' ) ),
			);
			$activity[] = array(
				'label' => sprintf( __( '#%1$s Payment made', 'threaddesk' ), $order->get_order_number() ),
				'date'  => $order->get_date_paid() ? $order->get_date_paid()->date_i18n( get_option( 'date_format' ) ) : $order->get_date_created()->date_i18n( get_option( 'date_format' ) ),
			);
		}

		foreach ( $quotes as $quote ) {
			$activity[] = array(
				'label' => sprintf( __( 'Quote created: %s', 'threaddesk' ), $quote->post_title ),
				'date'  => get_the_date( get_option( 'date_format' ), $quote ),
			);
		}

		usort(
			$activity,
			function ( $a, $b ) {
				return strtotime( $b['date'] ) - strtotime( $a['date'] );
			}
		);

		return array_slice( $activity, 0, 6 );
	}

	public function get_account_links() {
		return array(
			'edit_billing'  => wc_get_account_endpoint_url( 'edit-address' ),
			'edit_shipping' => wc_get_account_endpoint_url( 'edit-address' ),
			'edit_account'  => wc_get_account_endpoint_url( 'edit-account' ),
		);
	}

	public function get_user_address( $user_id, $type ) {
		if ( ! function_exists( 'wc_get_account_formatted_address' ) ) {
			return '';
		}

		$address = wc_get_account_formatted_address( $type );
		return $address ? $address : __( 'Not provided yet.', 'threaddesk' );
	}

	public function get_account_details( $user ) {
		if ( ! $user ) {
			return array();
		}

		return array(
			'username' => $user->user_login,
			'email'    => $user->user_email,
		);
	}

	public function get_outstanding_total( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'pending', 'on-hold', 'failed' ),
				'limit'       => -1,
			)
		);

		$total = 0;
		foreach ( $orders as $order ) {
			$total += (float) $order->get_total();
		}

		return $total;
	}
}
