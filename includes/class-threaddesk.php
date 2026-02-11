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
	private $auth_notice = '';
	private $auth_errors = array();
	private $auth_active_panel = '';
	private $auth_login_success = false;

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
		add_action( 'admin_post_tta_threaddesk_update_address', array( $this, 'handle_update_address' ) );
		add_action( 'admin_post_tta_threaddesk_save_design', array( $this, 'handle_save_design' ) );
		add_action( 'user_register', array( $this, 'handle_user_register' ) );
		add_action( 'init', array( $this, 'handle_auth_login' ) );
		add_action( 'init', array( $this, 'handle_auth_register' ) );
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

			<h2><?php echo esc_html__( 'Shortcodes', 'threaddesk' ); ?></h2>
			<p><?php echo esc_html__( 'Place these shortcodes on the appropriate pages to surface ThreadDesk features for your customers.', 'threaddesk' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Shortcode', 'threaddesk' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Placement', 'threaddesk' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>[threaddesk]</code></td>
						<td><?php echo esc_html__( 'Use on the main ThreadDesk dashboard page within the WooCommerce My Account area.', 'threaddesk' ); ?></td>
					</tr>
					<tr>
						<td><code>[threaddesk_auth]</code></td>
						<td><?php echo esc_html__( 'Use in your header or account menu to display the login/register modal and account links.', 'threaddesk' ); ?></td>
					</tr>
				</tbody>
			</table>

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


	public function handle_save_design() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_save_design' );

		if ( empty( $_FILES['threaddesk_design_file']['name'] ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Please choose a design file before saving.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) . 'designs/' );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upload = wp_handle_upload( $_FILES['threaddesk_design_file'], array( 'test_form' => false ) );
		if ( isset( $upload['error'] ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Design upload failed. Please try again.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) . 'designs/' );
			exit;
		}

		$file_name = sanitize_file_name( wp_basename( $upload['file'] ) );
		$title     = sanitize_text_field( preg_replace( '/\.[^.]+$/', '', $file_name ) );
		if ( '' === $title ) {
			$title = __( 'Design', 'threaddesk' );
		}

		$design_id = wp_insert_post(
			array(
				'post_type'   => 'tta_design',
				'post_status' => 'private',
				'post_title'  => $title,
				'post_author' => get_current_user_id(),
			)
		);

		if ( ! $design_id || is_wp_error( $design_id ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Unable to save design right now.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) . 'designs/' );
			exit;
		}

		$palette_raw = isset( $_POST['threaddesk_design_palette'] ) ? wp_unslash( $_POST['threaddesk_design_palette'] ) : '[]';
		$palette     = json_decode( $palette_raw, true );
		$palette     = is_array( $palette ) ? $palette : array();
		$palette     = array_values(
			array_filter(
				array_map(
					function ( $color ) {
						$color = strtoupper( sanitize_text_field( (string) $color ) );
						return preg_match( '/^#[0-9A-F]{6}$/', $color ) ? $color : '';
					},
					$palette
				)
			)
		);

		$settings_raw = isset( $_POST['threaddesk_design_analysis_settings'] ) ? wp_unslash( $_POST['threaddesk_design_analysis_settings'] ) : '{}';
		$settings     = json_decode( $settings_raw, true );
		$settings     = is_array( $settings ) ? $settings : array();
		$settings_clean = array(
			'minimumPercent'    => isset( $settings['minimumPercent'] ) ? (float) $settings['minimumPercent'] : 0.5,
			'mergeThreshold'    => isset( $settings['mergeThreshold'] ) ? (int) $settings['mergeThreshold'] : 22,
			'maximumColorCount' => isset( $settings['maximumColorCount'] ) ? (int) $settings['maximumColorCount'] : 4,
		);

		$color_count = isset( $_POST['threaddesk_design_color_count'] ) ? absint( $_POST['threaddesk_design_color_count'] ) : count( $palette );

		update_post_meta( $design_id, 'design_preview_url', esc_url_raw( $upload['url'] ) );
		update_post_meta( $design_id, 'design_file_name', $file_name );
		update_post_meta( $design_id, 'design_palette', wp_json_encode( $palette ) );
		update_post_meta( $design_id, 'design_color_count', $color_count );
		update_post_meta( $design_id, 'design_analysis_settings', wp_json_encode( $settings_clean ) );
		update_post_meta( $design_id, 'created_at', current_time( 'mysql' ) );

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Design saved successfully.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) . 'designs/' );
		exit;
	}

	public function handle_update_address() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_update_address' );

		$type = isset( $_POST['address_type'] ) ? sanitize_key( wp_unslash( $_POST['address_type'] ) ) : 'billing';
		if ( ! in_array( $type, array( 'billing', 'shipping', 'account' ), true ) ) {
			wp_die( esc_html__( 'Invalid address type.', 'threaddesk' ) );
		}

		$fields_by_type = array(
			'billing'  => array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ),
			'shipping' => array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ),
			'account'  => array(
				'user' => array( 'email' ),
			),
		);

		if ( 'account' === $type ) {
			if ( isset( $_POST['account_email'] ) ) {
				$email = sanitize_email( wp_unslash( $_POST['account_email'] ) );
				if ( is_email( $email ) ) {
					wp_update_user(
						array(
							'ID'         => get_current_user_id(),
							'user_email' => $email,
						)
					);
				} elseif ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Please enter a valid email address.', 'threaddesk' ), 'error' );
				}
			}
		} else {
			foreach ( $fields_by_type[ $type ] as $field ) {
				$key = "{$type}_{$field}";
				if ( isset( $_POST[ $key ] ) ) {
					$value  = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					update_user_meta( get_current_user_id(), $key, $value );
				}
			}
		}

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = home_url();
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	public function render_shortcode() {
		if ( ! is_user_logged_in() ) {
			return esc_html__( 'Please log in to view ThreadDesk.', 'threaddesk' );
		}

		wp_enqueue_script( 'threaddesk', THREDDESK_URL . 'assets/js/threaddesk.js', array( 'jquery' ), THREDDESK_VERSION, true );

		ob_start();

		$section = get_query_var( 'td_section', 'profile' );
		$this->render->render_section( $section );

		return ob_get_clean();
	}

	public function render_auth_shortcode() {
		wp_enqueue_style( 'threaddesk', THREDDESK_URL . 'assets/css/threaddesk.css', array(), THREDDESK_VERSION );

		if ( is_user_logged_in() && empty( $this->auth_notice ) && empty( $this->auth_errors ) ) {
			$current_user = wp_get_current_user();
			$full_name    = trim( $current_user->first_name . ' ' . $current_user->last_name );
			$display_name = $full_name ? $full_name : $current_user->display_name;
			$company_name = $current_user->user_login;
			$avatar       = '';
			$avatar_id    = (int) get_user_meta( $current_user->ID, 'tta_threaddesk_avatar_id', true );
			if ( $avatar_id ) {
				$avatar_url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' );
				if ( $avatar_url ) {
					$avatar = sprintf(
						'<img src="%s" alt="%s" class="threaddesk-auth__avatar" />',
						esc_url( $avatar_url ),
						esc_attr( $display_name )
					);
				}
			}
			if ( '' === $avatar ) {
				$avatar = get_avatar( $current_user->ID, 40, '', $display_name, array( 'class' => 'threaddesk-auth__avatar' ) );
			}
			$logout_url   = wp_logout_url( home_url() );
			$base_url     = function_exists( 'wc_get_account_endpoint_url' )
				? wc_get_account_endpoint_url( 'thread-desk' )
				: home_url();
			$sections     = array(
				'profile'  => __( 'Profile', 'threaddesk' ),
				'designs'  => __( 'Designs', 'threaddesk' ),
				'layouts'  => __( 'Placements', 'threaddesk' ),
				'quotes'   => __( 'Quotes', 'threaddesk' ),
				'invoices' => __( 'Invoices', 'threaddesk' ),
			);

			ob_start();
			?>
			<div class="threaddesk-auth" role="navigation" aria-label="<?php echo esc_attr__( 'Account links', 'threaddesk' ); ?>">
				<button type="button" class="threaddesk-auth__trigger" aria-label="<?php echo esc_attr__( 'ThreadDesk menu', 'threaddesk' ); ?>">
					<svg class="threaddesk-auth__icon" aria-hidden="true" viewBox="0 0 15 15" focusable="false">
						<path d="M7.5 0C3.4 0 0 3.4 0 7.5S3.4 15 7.5 15 15 11.6 15 7.5 11.6 0 7.5 0zm0 2.1c1.4 0 2.5 1.1 2.5 2.4S8.9 7 7.5 7 5 5.9 5 4.5s1.1-2.4 2.5-2.4zm0 11.4c-2.1 0-3.9-1-5-2.6C3.4 9.6 6 9 7.5 9s4.1.6 5 1.9c-1.1 1.6-2.9 2.6-5 2.6z"></path>
					</svg>
				</button>
				<div class="threaddesk-auth__menu" aria-hidden="true">
					<div class="threaddesk-auth__user">
						<?php echo $avatar; ?>
						<div class="threaddesk-auth__user-text">
							<span class="threaddesk-auth__user-name"><?php echo esc_html( $display_name ); ?></span>
							<span class="threaddesk-auth__user-company"><?php echo esc_html( $company_name ); ?></span>
						</div>
					</div>
					<div class="threaddesk-auth__divider" role="presentation"></div>
					<div class="threaddesk-auth__links">
						<?php foreach ( $sections as $section_key => $label ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'td_section', $section_key, $base_url ) ); ?>">
								<?php echo esc_html( $label ); ?>
							</a>
						<?php endforeach; ?>
					</div>
					<div class="threaddesk-auth__divider" role="presentation"></div>
					<a class="threaddesk-auth__logout" href="<?php echo esc_url( $logout_url ); ?>">
						<?php echo esc_html__( 'Log Out', 'threaddesk' ); ?>
					</a>
				</div>
			</div>
			<?php

			return ob_get_clean();
		}

			wp_enqueue_script( 'threaddesk', THREDDESK_URL . 'assets/js/threaddesk.js', array( 'jquery' ), THREDDESK_VERSION, true );

		$login_url = wp_login_url();
		$lost_url  = wp_lostpassword_url();

		ob_start();
		?>
		<div class="threaddesk-auth" role="navigation" aria-label="<?php echo esc_attr__( 'Account links', 'threaddesk' ); ?>">
			<button type="button" class="threaddesk-auth__trigger" aria-label="<?php echo esc_attr__( 'Log in or register', 'threaddesk' ); ?>">
				<svg class="threaddesk-auth__icon" aria-hidden="true" viewBox="0 0 15 15" focusable="false">
					<path d="M7.5 0C3.4 0 0 3.4 0 7.5S3.4 15 7.5 15 15 11.6 15 7.5 11.6 0 7.5 0zm0 2.1c1.4 0 2.5 1.1 2.5 2.4S8.9 7 7.5 7 5 5.9 5 4.5s1.1-2.4 2.5-2.4zm0 11.4c-2.1 0-3.9-1-5-2.6C3.4 9.6 6 9 7.5 9s4.1.6 5 1.9c-1.1 1.6-2.9 2.6-5 2.6z"></path>
				</svg>
				</button>
				<div class="threaddesk-auth__menu" aria-hidden="true">
					<button type="button" class="threaddesk-auth__menu-button" data-threaddesk-auth="login">
						<?php echo esc_html__( 'Sign in', 'threaddesk' ); ?>
					</button>
					<button type="button" class="threaddesk-auth__menu-button" data-threaddesk-auth="register">
						<?php echo esc_html__( 'Register', 'threaddesk' ); ?>
					</button>
					<button type="button" class="threaddesk-auth__menu-button" data-threaddesk-auth="forgot">
						<?php echo esc_html__( 'Forgot password', 'threaddesk' ); ?>
					</button>
				</div>
			</div>
			<div class="threaddesk-auth-modal" aria-hidden="true" data-threaddesk-auth-default="<?php echo esc_attr( $this->auth_active_panel ); ?>">
				<div class="threaddesk-auth-modal__overlay" data-threaddesk-auth-close></div>
				<div class="threaddesk-auth-modal__panel" role="dialog" aria-label="<?php echo esc_attr__( 'Account modal', 'threaddesk' ); ?>" aria-modal="true">
					<div class="threaddesk-auth-modal__actions">
						<button type="button" class="threaddesk-auth-modal__close" data-threaddesk-auth-close aria-label="<?php echo esc_attr__( 'Close account modal', 'threaddesk' ); ?>">
							<svg class="threaddesk-auth-modal__close-icon" width="12" height="12" viewBox="0 0 15 15" aria-hidden="true" focusable="false">
								<path d="M1 15a1 1 0 01-.71-.29 1 1 0 010-1.41l5.8-5.8-5.8-5.8A1 1 0 011.7.29l5.8 5.8 5.8-5.8a1 1 0 011.41 1.41l-5.8 5.8 5.8 5.8a1 1 0 01-1.41 1.41l-5.8-5.8-5.8 5.8A1 1 0 011 15z"></path>
							</svg>
						</button>
					</div>
					<div class="threaddesk-auth-modal__content">
						<div class="threaddesk-auth-modal__tabs" role="tablist">
							<button type="button" class="threaddesk-auth-modal__tab is-active" role="tab" aria-selected="true" data-threaddesk-auth-tab="login">
								<?php echo esc_html__( 'Login', 'threaddesk' ); ?>
							</button>
							<button type="button" class="threaddesk-auth-modal__tab" role="tab" aria-selected="false" data-threaddesk-auth-tab="register">
								<?php echo esc_html__( 'Sign Up', 'threaddesk' ); ?>
							</button>
						</div>
							<div class="threaddesk-auth-modal__forms">
								<div class="threaddesk-auth-modal__form is-active" data-threaddesk-auth-panel="login">
								<form class="threaddesk-auth-modal__form-inner" action="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>" method="post">
									<input type="hidden" name="threaddesk_login" value="1" />
									<?php wp_nonce_field( 'threaddesk_login', 'threaddesk_login_nonce' ); ?>
									<?php if ( ( $this->auth_notice || ! empty( $this->auth_errors ) ) && 'login' === $this->auth_active_panel ) : ?>
										<div class="threaddesk-auth-modal__notice" role="status">
											<?php if ( $this->auth_notice ) : ?>
												<p><?php echo wp_kses_post( $this->auth_notice ); ?></p>
											<?php endif; ?>
											<?php if ( ! empty( $this->auth_errors ) ) : ?>
												<ul>
													<?php foreach ( $this->auth_errors as $error ) : ?>
														<li><?php echo wp_kses_post( $error ); ?></li>
													<?php endforeach; ?>
												</ul>
											<?php endif; ?>
										</div>
									<?php endif; ?>
									<p>
										<label for="threaddesk_user_login"><?php echo esc_html__( 'Username or Email Address', 'threaddesk' ); ?></label>
										<input type="text" name="log" id="threaddesk_user_login" autocomplete="username" autocapitalize="off" />
									</p>
									<p>
										<label for="threaddesk_user_pass"><?php echo esc_html__( 'Password', 'threaddesk' ); ?></label>
										<input type="password" name="pwd" id="threaddesk_user_pass" autocomplete="current-password" />
									</p>
									<p class="threaddesk-auth-modal__form-row">
										<label class="threaddesk-auth-modal__checkbox">
											<input type="checkbox" name="rememberme" value="forever" />
											<?php echo esc_html__( 'Remember Me', 'threaddesk' ); ?>
										</label>
										<button type="button" class="threaddesk-auth-modal__link" data-threaddesk-auth="forgot">
											<?php echo esc_html__( 'Forgot Password?', 'threaddesk' ); ?>
										</button>
									</p>
									<p class="threaddesk-auth-modal__submit">
										<button type="submit" class="threaddesk-auth-modal__button">
											<?php echo esc_html__( 'Log In', 'threaddesk' ); ?>
										</button>
									</p>
								</form>
								</div>
									<div class="threaddesk-auth-modal__form" data-threaddesk-auth-panel="register">
										<form class="threaddesk-auth-modal__form-inner" action="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>" method="post">
											<input type="hidden" name="threaddesk_register" value="1" />
											<?php wp_nonce_field( 'threaddesk_register', 'threaddesk_register_nonce' ); ?>
											<?php if ( $this->auth_notice || ! empty( $this->auth_errors ) ) : ?>
												<div class="threaddesk-auth-modal__notice" role="status">
													<?php if ( $this->auth_notice ) : ?>
														<p><?php echo wp_kses_post( $this->auth_notice ); ?></p>
													<?php endif; ?>
													<?php if ( ! empty( $this->auth_errors ) ) : ?>
														<ul>
															<?php foreach ( $this->auth_errors as $error ) : ?>
																<li><?php echo wp_kses_post( $error ); ?></li>
															<?php endforeach; ?>
														</ul>
													<?php endif; ?>
												</div>
											<?php endif; ?>
											<div class="threaddesk-auth-modal__form-row">
											<p>
												<label for="threaddesk_register_first_name"><?php echo esc_html__( 'First Name', 'threaddesk' ); ?></label>
												<input type="text" name="first_name" id="threaddesk_register_first_name" autocomplete="given-name" />
											</p>
											<p>
												<label for="threaddesk_register_last_name"><?php echo esc_html__( 'Last Name', 'threaddesk' ); ?></label>
												<input type="text" name="last_name" id="threaddesk_register_last_name" autocomplete="family-name" />
											</p>
										</div>
										<p>
											<label for="threaddesk_register_company"><?php echo esc_html__( 'Company Name', 'threaddesk' ); ?></label>
											<input type="text" name="user_login" id="threaddesk_register_company" autocomplete="organization" />
										</p>
											<p>
												<label for="threaddesk_register_website"><?php echo esc_html__( 'Website (optional)', 'threaddesk' ); ?></label>
												<input type="text" name="user_url" id="threaddesk_register_website" autocomplete="url" />
											</p>
										<p>
											<label for="threaddesk_register_email"><?php echo esc_html__( 'Email', 'threaddesk' ); ?></label>
											<input type="email" name="user_email" id="threaddesk_register_email" autocomplete="email" />
										</p>
										<p>
											<label for="threaddesk_register_pass"><?php echo esc_html__( 'Password', 'threaddesk' ); ?></label>
											<input type="password" name="user_pass" id="threaddesk_register_pass" autocomplete="new-password" />
										</p>
										<p class="threaddesk-auth-modal__privacy">
											<?php
											printf(
												wp_kses(
													__( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our <a href="%s">privacy policy.</a>', 'threaddesk' ),
													array(
														'a' => array(
															'href' => array(),
														),
													)
												),
												esc_url( get_privacy_policy_url() )
											);
											?>
										</p>
										<p class="threaddesk-auth-modal__submit">
											<button type="submit" class="threaddesk-auth-modal__button">
												<?php echo esc_html__( 'Register', 'threaddesk' ); ?>
											</button>
										</p>
								</form>
							</div>
							<div class="threaddesk-auth-modal__form" data-threaddesk-auth-panel="forgot">
								<form class="threaddesk-auth-modal__form-inner" action="<?php echo esc_url( $lost_url ); ?>" method="post">
									<p>
										<label for="threaddesk_forgot_login"><?php echo esc_html__( 'Username or Email Address', 'threaddesk' ); ?></label>
										<input type="text" name="user_login" id="threaddesk_forgot_login" autocomplete="username" autocapitalize="off" />
									</p>
									<p class="threaddesk-auth-modal__submit">
										<button type="submit" class="threaddesk-auth-modal__button">
											<?php echo esc_html__( 'Get New Password', 'threaddesk' ); ?>
										</button>
									</p>
									<button type="button" class="threaddesk-auth-modal__link" data-threaddesk-auth="login">
										<?php echo esc_html__( '← Back to login', 'threaddesk' ); ?>
									</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php

		return ob_get_clean();
	}

	public function handle_user_register( $user_id ) {
		if ( empty( $_POST['threaddesk_register'] ) ) {
			return;
		}

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$company    = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
		$email      = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

		if ( $first_name || $last_name ) {
			wp_update_user(
				array(
					'ID'         => $user_id,
					'first_name' => $first_name,
					'last_name'  => $last_name,
				)
			);
		}

		$profile_defaults = array(
			'billing_first_name'  => $first_name,
			'billing_last_name'   => $last_name,
			'billing_company'     => $company,
			'billing_email'       => $email,
			'shipping_first_name' => $first_name,
			'shipping_last_name'  => $last_name,
			'shipping_company'    => $company,
			'shipping_email'      => $email,
		);

		foreach ( $profile_defaults as $meta_key => $meta_value ) {
			if ( '' !== $meta_value ) {
				update_user_meta( $user_id, $meta_key, $meta_value );
			}
		}

		if ( get_role( 'customer' ) ) {
			$user = new WP_User( $user_id );
			$user->set_role( 'customer' );
		}
	}

	public function handle_auth_register() {
		if ( empty( $_POST['threaddesk_register'] ) ) {
			return;
		}

		$this->auth_active_panel = 'register';

		if ( ! isset( $_POST['threaddesk_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['threaddesk_register_nonce'] ) ), 'threaddesk_register' ) ) {
			$this->auth_errors[] = __( 'Registration failed security validation. Please try again.', 'threaddesk' );
			return;
		}

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$company    = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
		$email      = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$password   = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : '';
		$website    = isset( $_POST['user_url'] ) ? sanitize_text_field( wp_unslash( $_POST['user_url'] ) ) : '';

		if ( '' === $first_name ) {
			$this->auth_errors[] = __( 'Missing First Name.', 'threaddesk' );
		}

		if ( '' === $last_name ) {
			$this->auth_errors[] = __( 'Missing Last Name.', 'threaddesk' );
		}

		if ( '' === $company ) {
			$this->auth_errors[] = __( 'Missing Company Name.', 'threaddesk' );
		}

		if ( '' === $email ) {
			$this->auth_errors[] = __( 'Missing Email.', 'threaddesk' );
		} elseif ( ! is_email( $email ) ) {
			$this->auth_errors[] = __( 'Please provide a valid email address.', 'threaddesk' );
		}

		if ( '' === $password ) {
			$this->auth_errors[] = __( 'Missing Password.', 'threaddesk' );
		}

		if ( ! empty( $this->auth_errors ) ) {
			return;
		}

		$user_id = wp_create_user( $company, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			$this->auth_errors[] = $user_id->get_error_message();
			return;
		}

		if ( $website ) {
			$normalized = esc_url_raw( $website, array( 'http', 'https' ) );
			if ( ! $normalized && ! empty( $website ) ) {
				$normalized = esc_url_raw( 'https://' . ltrim( $website, '/' ), array( 'http', 'https' ) );
			}
			if ( $normalized ) {
				wp_update_user(
					array(
						'ID'       => $user_id,
						'user_url' => $normalized,
					)
				);
			}
		}

		if ( $first_name || $last_name ) {
			wp_update_user(
				array(
					'ID'         => $user_id,
					'first_name' => $first_name,
					'last_name'  => $last_name,
				)
			);
		}

		if ( get_role( 'customer' ) ) {
			$user = new WP_User( $user_id );
			$user->set_role( 'customer' );
		}

		$this->auth_notice = __( 'Registration successful. Please check your email for confirmation.', 'threaddesk' );
	}

	public function handle_auth_login() {
		if ( empty( $_POST['threaddesk_login'] ) ) {
			return;
		}

		$this->auth_active_panel = 'login';

		if ( ! isset( $_POST['threaddesk_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['threaddesk_login_nonce'] ) ), 'threaddesk_login' ) ) {
			$this->auth_errors[] = __( 'Login failed security validation. Please try again.', 'threaddesk' );
			return;
		}

		$username = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '';
		$password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
		$remember = ! empty( $_POST['rememberme'] );

		if ( '' === $username ) {
			$this->auth_errors[] = __( 'Missing Username or Email.', 'threaddesk' );
		}

		if ( '' === $password ) {
			$this->auth_errors[] = __( 'Missing Password.', 'threaddesk' );
		}

		if ( ! empty( $this->auth_errors ) ) {
			return;
		}

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			$this->auth_errors[] = $user->get_error_message();
			return;
		}

		$this->auth_notice = __( 'Login successful. Welcome back!', 'threaddesk' );
		$this->auth_login_success = true;
	}
}
