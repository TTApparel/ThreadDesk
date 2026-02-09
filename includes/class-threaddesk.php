<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTA_ThreadDesk {
	private static $instance = null;

	public $assets;
	public $endpoints;
	public $render;
	public $data;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->assets    = new TTA_ThreadDesk_Assets();
		$this->endpoints = new TTA_ThreadDesk_Endpoints();
		$this->render    = new TTA_ThreadDesk_Render();
		$this->data      = new TTA_ThreadDesk_Data();

		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_tta_threaddesk_generate_demo', array( $this, 'handle_generate_demo' ) );
		add_action( 'admin_post_tta_threaddesk_request_order', array( $this, 'handle_request_order' ) );
		add_action( 'admin_post_tta_threaddesk_reorder', array( $this, 'handle_reorder' ) );
		add_action( 'admin_post_tta_threaddesk_avatar_upload', array( $this, 'handle_avatar_upload' ) );
		add_shortcode( 'threaddesk', array( $this, 'render_shortcode' ) );
		add_shortcode( 'threaddesk_auth', array( $this, 'render_auth_shortcode' ) );
	}

	public static function activate() {
		$instance = self::instance();
		$instance->register_post_types();
		$instance->endpoints->register_endpoints();
		flush_rewrite_rules();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'threaddesk', false, basename( dirname( THREDDESK_PATH ) ) . '/languages' );
	}

	public function register_post_types() {
		$common_args = array(
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
		);

		register_post_type(
			'tta_quote',
			array_merge(
				$common_args,
				array(
					'labels' => array(
						'name'          => __( 'Quotes', 'threaddesk' ),
						'singular_name' => __( 'Quote', 'threaddesk' ),
					),
				)
			)
		);

		register_post_type(
			'tta_design',
			array_merge(
				$common_args,
				array(
					'labels' => array(
						'name'          => __( 'Designs', 'threaddesk' ),
						'singular_name' => __( 'Design', 'threaddesk' ),
					),
				)
			)
		);

		register_post_type(
			'tta_layout',
			array_merge(
				$common_args,
				array(
					'labels' => array(
						'name'          => __( 'Layouts', 'threaddesk' ),
						'singular_name' => __( 'Layout', 'threaddesk' ),
					),
				)
			)
		);
	}

	public function register_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'ThreadDesk', 'threaddesk' ),
			__( 'ThreadDesk', 'threaddesk' ),
			'manage_woocommerce',
			'tta-threaddesk',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'tta_threaddesk_settings', 'tta_threaddesk_cover_image_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'tta_threaddesk_settings', 'tta_threaddesk_default_company', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$cover_image = get_option( 'tta_threaddesk_cover_image_url', '' );
		$company     = get_option( 'tta_threaddesk_default_company', '' );
		$nonce       = wp_create_nonce( 'tta_threaddesk_generate_demo' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'ThreadDesk Settings', 'threaddesk' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'tta_threaddesk_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="tta_threaddesk_cover_image_url"><?php echo esc_html__( 'Cover Image URL', 'threaddesk' ); ?></label>
						</th>
						<td>
							<input type="url" class="regular-text" id="tta_threaddesk_cover_image_url" name="tta_threaddesk_cover_image_url" value="<?php echo esc_url( $cover_image ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="tta_threaddesk_default_company"><?php echo esc_html__( 'Default Company Name', 'threaddesk' ); ?></label>
						</th>
						<td>
							<input type="text" class="regular-text" id="tta_threaddesk_default_company" name="tta_threaddesk_default_company" value="<?php echo esc_attr( $company ); ?>" />
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php echo esc_html__( 'Demo Data', 'threaddesk' ); ?></h2>
			<p><?php echo esc_html__( 'Generate demo quotes, designs, and layouts for the current admin user.', 'threaddesk' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tta_threaddesk_generate_demo" />
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
				<?php submit_button( __( 'Generate Demo Data', 'threaddesk' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_generate_demo() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_generate_demo' );

		$user_id = get_current_user_id();

		$demo_quotes = array(
			array(
				'post_title'  => __( 'Sample Quote #1001', 'threaddesk' ),
				'post_author' => $user_id,
			),
			array(
				'post_title'  => __( 'Sample Quote #1002', 'threaddesk' ),
				'post_author' => $user_id,
			),
		);

		foreach ( $demo_quotes as $quote ) {
			$quote_id = wp_insert_post(
				array(
					'post_type'   => 'tta_quote',
					'post_status' => 'private',
					'post_title'  => $quote['post_title'],
					'post_author' => $quote['post_author'],
				)
			);

			if ( $quote_id ) {
				update_post_meta( $quote_id, 'status', 'draft' );
				update_post_meta( $quote_id, 'total', '450.00' );
				update_post_meta( $quote_id, 'currency', 'USD' );
				update_post_meta( $quote_id, 'items_json', wp_json_encode( array( 'Sample Item' ) ) );
				update_post_meta( $quote_id, 'created_at', current_time( 'mysql' ) );
			}
		}

		$design_id = wp_insert_post(
			array(
				'post_type'   => 'tta_design',
				'post_status' => 'private',
				'post_title'  => __( 'Sample Design', 'threaddesk' ),
				'post_author' => $user_id,
			)
		);

		if ( $design_id ) {
			update_post_meta( $design_id, 'created_at', current_time( 'mysql' ) );
		}

		$layout_id = wp_insert_post(
			array(
				'post_type'   => 'tta_layout',
				'post_status' => 'private',
				'post_title'  => __( 'Sample Layout', 'threaddesk' ),
				'post_author' => $user_id,
			)
		);

		if ( $layout_id ) {
			update_post_meta( $layout_id, 'created_at', current_time( 'mysql' ) );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=tta-threaddesk' ) );
		exit;
	}

	public function handle_request_order() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_request_order' );

		$quote_id = isset( $_POST['quote_id'] ) ? absint( $_POST['quote_id'] ) : 0;

		$quote = get_post( $quote_id );
		if ( ! $quote || 'tta_quote' !== $quote->post_type || (int) $quote->post_author !== get_current_user_id() ) {
			wp_die( esc_html__( 'Invalid quote.', 'threaddesk' ) );
		}

		update_post_meta( $quote_id, 'status', 'pending' );
		update_post_meta( $quote_id, 'requested_at', current_time( 'mysql' ) );
		update_user_meta( get_current_user_id(), 'tta_threaddesk_last_request', current_time( 'mysql' ) );

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Order request sent successfully.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) . 'quotes/' );
		exit;
	}

	public function handle_reorder() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_reorder' );

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order || (int) $order->get_user_id() !== get_current_user_id() ) {
			wp_die( esc_html__( 'Invalid order.', 'threaddesk' ) );
		}

		$added = false;

		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( $product && $product->is_purchasable() ) {
					WC()->cart->add_to_cart( $product->get_id(), $item->get_quantity(), $item->get_variation_id(), $item->get_variation(), array() );
					$added = true;
				}
			}
		}

		if ( function_exists( 'wc_add_notice' ) ) {
			if ( $added ) {
				wc_add_notice( __( 'Items added to your cart.', 'threaddesk' ), 'success' );
			} else {
				wc_add_notice( __( 'Unable to reorder these items.', 'threaddesk' ), 'error' );
			}
		}

		wp_safe_redirect( $added ? wc_get_cart_url() : wc_get_account_endpoint_url( 'thread-desk' ) . 'invoices/' );
		exit;
	}

	public function handle_avatar_upload() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_avatar_upload' );

		if ( empty( $_FILES['threaddesk_avatar']['name'] ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload( $_FILES['threaddesk_avatar'], array( 'test_form' => false ) );

		if ( isset( $upload['error'] ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Avatar upload failed.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $upload['type'],
				'post_title'     => sanitize_file_name( wp_basename( $upload['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( $attachment_id ) {
			$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
			wp_update_attachment_metadata( $attachment_id, $metadata );
			update_user_meta( get_current_user_id(), 'tta_threaddesk_avatar_id', $attachment_id );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
		exit;
	}

	public function render_shortcode() {
		if ( ! is_user_logged_in() ) {
			return esc_html__( 'Please log in to view ThreadDesk.', 'threaddesk' );
		}

		ob_start();

		$section = get_query_var( 'td_section', 'profile' );
		$this->render->render_section( $section );

		return ob_get_clean();
	}

	public function render_auth_shortcode() {
		if ( is_user_logged_in() ) {
			return '';
		}

		wp_enqueue_style( 'threaddesk', THREDDESK_URL . 'assets/css/threaddesk.css', array(), THREDDESK_VERSION );

		$login_url    = wp_login_url();
		$register_url = wp_registration_url();
		$lost_url     = wp_lostpassword_url();

		ob_start();
		?>
		<div class="threaddesk-auth" role="navigation" aria-label="<?php echo esc_attr__( 'Account links', 'threaddesk' ); ?>">
			<button type="button" class="threaddesk-auth__trigger">
				<?php echo esc_html__( 'Log in/Register', 'threaddesk' ); ?>
			</button>
			<div class="threaddesk-auth__menu" aria-hidden="true">
				<a href="<?php echo esc_url( $login_url ); ?>">
					<?php echo esc_html__( 'Sign in', 'threaddesk' ); ?>
				</a>
				<a href="<?php echo esc_url( $register_url ); ?>">
					<?php echo esc_html__( 'Register', 'threaddesk' ); ?>
				</a>
				<a href="<?php echo esc_url( $lost_url ); ?>">
					<?php echo esc_html__( 'Forgot password', 'threaddesk' ); ?>
				</a>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
