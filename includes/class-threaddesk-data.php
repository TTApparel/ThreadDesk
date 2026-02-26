<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk_Data {
	public function get_dashboard_data( $section ) {
		$user_id = get_current_user_id();
		$user    = get_user_by( 'id', $user_id );

		$stats = $this->get_order_stats( $user_id );
		$activity_page = isset( $_GET['td_activity_page'] ) ? absint( wp_unslash( $_GET['td_activity_page'] ) ) : 1;
		$activity_pagination = $this->get_recent_activity_page( $user_id, 10, $activity_page );

		$data = array(
			'user'              => $user,
			'section'           => $section,
			'cover_image'       => get_option( 'tta_threaddesk_cover_image_url', '' ),
			'company'           => get_option( 'tta_threaddesk_default_company', '' ),
			'client_name'       => $this->get_customer_name( $user_id ),
			'avatar_url'        => $this->get_avatar_url( $user_id ),
			'order_stats'       => $stats,
			'design_count'      => $this->get_post_count( 'tta_design', $user_id ),
			'layout_count'      => $this->get_post_count( 'tta_layout', $user_id ),
			'quotes_count'      => $this->get_post_count( 'tta_quote', $user_id ),
			'recent_activity'   => isset( $activity_pagination['entries'] ) ? $activity_pagination['entries'] : array(),
			'recent_activity_pagination' => $activity_pagination,
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


	public static function append_user_activity( $user_id, $label, $context = '' ) {
		$user_id = absint( $user_id );
		$label   = sanitize_text_field( (string) $label );
		$context = sanitize_key( (string) $context );
		if ( $user_id <= 0 || '' === $label ) {
			return;
		}

		$events = get_user_meta( $user_id, 'tta_threaddesk_activity_log', true );
		$events = is_array( $events ) ? $events : array();
		$events[] = array(
			'label'     => $label,
			'context'   => $context,
			'timestamp' => current_time( 'timestamp' ),
			'date'      => current_time( get_option( 'date_format' ) ),
		);

		if ( count( $events ) > 250 ) {
			$events = array_slice( $events, -250 );
		}

		update_user_meta( $user_id, 'tta_threaddesk_activity_log', $events );
	}

	public function get_stored_user_activity( $user_id, $limit = 25 ) {
		$events = get_user_meta( $user_id, 'tta_threaddesk_activity_log', true );
		$events = is_array( $events ) ? $events : array();
		$normalized = array();

		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}
			$label = isset( $event['label'] ) ? sanitize_text_field( (string) $event['label'] ) : '';
			if ( '' === $label ) {
				continue;
			}
			$timestamp = isset( $event['timestamp'] ) ? (int) $event['timestamp'] : 0;
			if ( $timestamp <= 0 ) {
				$date_raw = isset( $event['date'] ) ? (string) $event['date'] : '';
				$timestamp = $date_raw ? strtotime( $date_raw ) : 0;
			}
			$date = $timestamp > 0 ? wp_date( get_option( 'date_format' ), $timestamp ) : current_time( get_option( 'date_format' ) );
			$normalized[] = array(
				'label'     => $label,
				'date'      => $date,
				'timestamp' => $timestamp,
				'context'   => isset( $event['context'] ) ? sanitize_key( (string) $event['context'] ) : '',
			);
		}

		usort(
			$normalized,
			function ( $a, $b ) {
				return (int) $b['timestamp'] - (int) $a['timestamp'];
			}
		);

		if ( $limit > 0 ) {
			$normalized = array_slice( $normalized, 0, absint( $limit ) );
		}

		return $normalized;
	}


	public function get_recent_activity( $user_id, $limit = 6 ) {
		$activity = $this->get_stored_user_activity( $user_id, 0 );
		$orders   = $this->get_user_orders( $user_id );
		$quotes   = $this->get_user_quotes( $user_id );

		foreach ( $orders as $order ) {
			$created_date = $order->get_date_created();
			$created_ts   = $created_date ? $created_date->getTimestamp() : current_time( 'timestamp' );
			$activity[] = array(
				'label'     => sprintf( __( '#%1$s Order placed', 'threaddesk' ), $order->get_order_number() ),
				'date'      => wp_date( get_option( 'date_format' ), $created_ts ),
				'timestamp' => $created_ts,
			);

			$paid_date = $order->get_date_paid();
			$paid_ts   = $paid_date ? $paid_date->getTimestamp() : $created_ts;
			$activity[] = array(
				'label'     => sprintf( __( '#%1$s Payment made', 'threaddesk' ), $order->get_order_number() ),
				'date'      => wp_date( get_option( 'date_format' ), $paid_ts ),
				'timestamp' => $paid_ts,
			);
		}

		foreach ( $quotes as $quote ) {
			$quote_ts = get_post_time( 'U', true, $quote );
			$activity[] = array(
				'label'     => sprintf( __( 'Quote created: %s', 'threaddesk' ), $quote->post_title ),
				'date'      => wp_date( get_option( 'date_format' ), $quote_ts ? $quote_ts : current_time( 'timestamp' ) ),
				'timestamp' => $quote_ts ? $quote_ts : current_time( 'timestamp' ),
			);
		}

		usort(
			$activity,
			function ( $a, $b ) {
				return (int) ( isset( $b['timestamp'] ) ? $b['timestamp'] : 0 ) - (int) ( isset( $a['timestamp'] ) ? $a['timestamp'] : 0 );
			}
		);

		if ( $limit > 0 ) {
			$activity = array_slice( $activity, 0, absint( $limit ) );
		}

		return $activity;
	}


	public function get_recent_activity_page( $user_id, $per_page = 10, $page = 1 ) {
		$per_page = max( 1, absint( $per_page ) );
		$page     = max( 1, absint( $page ) );
		$all      = $this->get_recent_activity( $user_id, 0 );
		$total    = count( $all );
		$pages    = max( 1, (int) ceil( $total / $per_page ) );
		$page     = min( $page, $pages );
		$offset   = ( $page - 1 ) * $per_page;

		return array(
			'entries'     => array_slice( $all, $offset, $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
			'total'       => $total,
			'total_pages' => $pages,
		);
	}

	public function get_account_links() {
		$account_base   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
		$billing_link   = function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-address', 'billing', $account_base ) : '';
		$shipping_link  = function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-address', 'shipping', $account_base ) : '';
		$edit_address   = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-address' ) : '';
		$edit_account   = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-account' ) : '';
		$fallback_link  = function_exists( 'admin_url' ) ? admin_url( 'profile.php' ) : '';

		return array(
			'edit_billing'  => $billing_link ? $billing_link : ( $edit_address ? $edit_address : $fallback_link ),
			'edit_shipping' => $shipping_link ? $shipping_link : ( $edit_address ? $edit_address : $fallback_link ),
			'edit_account'  => $edit_account ? $edit_account : $fallback_link,
		);
	}

	public function get_user_address( $user_id, $type ) {
		$fields = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'first_name', 'last_name', 'company', 'phone', 'email' );
		$address = array();

		foreach ( $fields as $field ) {
			$address[ $field ] = get_user_meta( $user_id, "{$type}_{$field}", true );
		}

		$address['formatted'] = '';

		if ( function_exists( 'wc_format_address' ) ) {
			$address['formatted'] = wc_format_address( $address );
		}

		return $address;
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

	private function get_customer_name( $user_id ) {
		if ( function_exists( 'wc_get_customer' ) ) {
			$customer = wc_get_customer( $user_id );
			if ( $customer ) {
				$name = trim( $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name() );
				if ( $name ) {
					return $name;
				}
				$name = trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() );
				if ( $name ) {
					return $name;
				}
			}
		}

		$user = get_user_by( 'id', $user_id );
		return $user ? $user->display_name : __( 'Client Name', 'threaddesk' );
	}

	private function get_avatar_url( $user_id ) {
		$attachment_id = (int) get_user_meta( $user_id, 'tta_threaddesk_avatar_id', true );
		if ( $attachment_id ) {
			$avatar_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
			if ( $avatar_url ) {
				return $avatar_url;
			}
		}

		if ( function_exists( 'get_avatar_url' ) ) {
			return get_avatar_url( $user_id, array( 'size' => 200 ) );
		}

		return '';
	}
}
