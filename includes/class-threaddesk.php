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
	public $guest_tokens;
	private $auth_notice = '';
	private $auth_errors = array();
	private $auth_active_panel = '';
	private $auth_login_success = false;
	private $auth_register_success = false;
	private $screenprint_payload_request_cache = array();
	private $screenprint_dataset_request_cache = array();
	private $screenprint_cache_group = 'tta_threaddesk_screenprint';
	private $screenprint_variation_page_limit = 150;
	private $screenprint_variation_initial_limit = 24;

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
		$this->data         = new TTA_ThreadDesk_Data();
		$this->guest_tokens = new TTA_ThreadDesk_Guest_Token_Service();
		$this->guest_tokens->register_hooks();
		$this->guest_tokens->maybe_schedule_cleanup();

		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 1 );
		add_action( 'add_meta_boxes', array( $this, 'register_admin_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'maybe_assign_internal_reference' ), 10, 3 );
		add_action( 'post_updated', array( $this, 'handle_entity_updated_activity' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'handle_entity_deleted_activity' ) );
		add_filter( 'manage_edit-tta_quote_columns', array( $this, 'filter_quote_admin_columns' ) );
		add_filter( 'manage_edit-tta_design_columns', array( $this, 'filter_design_admin_columns' ) );
		add_filter( 'manage_edit-tta_layout_columns', array( $this, 'filter_layout_admin_columns' ) );
		add_action( 'manage_tta_quote_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
		add_action( 'manage_tta_design_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
		add_action( 'manage_tta_layout_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'render_quote_quick_edit_status_inline_field' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'render_design_quick_edit_status_field' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'render_layout_quick_edit_status_field' ), 10, 2 );
		add_action( 'admin_footer-edit.php', array( $this, 'render_quote_quick_edit_status_inline_script' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'render_design_quick_edit_status_script' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'render_layout_quick_edit_status_script' ) );
		add_action( 'save_post_tta_quote', array( $this, 'handle_quote_status_save' ), 10, 2 );
		add_action( 'save_post_tta_design', array( $this, 'handle_design_status_save' ), 10, 2 );
		add_action( 'save_post_tta_layout', array( $this, 'handle_layout_status_save' ), 10, 2 );
		add_action( 'save_post_product', array( $this, 'handle_product_postbox_save' ), 10, 2 );
		add_action( 'save_post_tta_layout', array( $this, 'invalidate_screenprint_payload_cache' ) );
		add_action( 'save_post_tta_design', array( $this, 'invalidate_screenprint_payload_cache' ) );
		add_action( 'save_post_tta_quote', array( $this, 'invalidate_screenprint_payload_cache' ) );
		add_action( 'save_post_product', array( $this, 'invalidate_screenprint_payload_cache' ) );
		add_action( 'update_option_tta_threaddesk_layout_categories', array( $this, 'handle_layout_categories_option_updated' ), 10, 3 );
		add_action( 'update_option_tta_threaddesk_print_pricing', array( $this, 'handle_print_pricing_option_updated' ), 10, 3 );
		add_filter( 'manage_edit-tta_quote_sortable_columns', array( $this, 'filter_quote_sortable_columns' ) );
		add_filter( 'manage_edit-tta_design_sortable_columns', array( $this, 'filter_design_sortable_columns' ) );
		add_filter( 'manage_edit-tta_layout_sortable_columns', array( $this, 'filter_layout_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_admin_sorting_queries' ) );
		add_action( 'admin_post_tta_threaddesk_generate_demo', array( $this, 'handle_generate_demo' ) );
		add_action( 'admin_post_tta_threaddesk_request_order', array( $this, 'handle_request_order' ) );
		add_action( 'admin_post_tta_threaddesk_reorder', array( $this, 'handle_reorder' ) );
		add_action( 'admin_post_tta_threaddesk_avatar_upload', array( $this, 'handle_avatar_upload' ) );
		add_action( 'admin_post_tta_threaddesk_update_address', array( $this, 'handle_update_address' ) );
		add_action( 'admin_post_tta_threaddesk_save_design', array( $this, 'handle_save_design' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_save_design', array( $this, 'handle_save_design' ) );
		add_action( 'admin_post_tta_threaddesk_save_layout', array( $this, 'handle_save_layout' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_save_layout', array( $this, 'handle_save_layout' ) );
		add_action( 'admin_post_tta_threaddesk_rename_design', array( $this, 'handle_rename_design' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_rename_design', array( $this, 'handle_rename_design' ) );
		add_action( 'admin_post_tta_threaddesk_delete_design', array( $this, 'handle_delete_design' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_delete_design', array( $this, 'handle_delete_design' ) );
		add_action( 'admin_post_tta_threaddesk_rename_layout', array( $this, 'handle_rename_layout' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_rename_layout', array( $this, 'handle_rename_layout' ) );
		add_action( 'admin_post_tta_threaddesk_delete_layout', array( $this, 'handle_delete_layout' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_save_design', array( $this, 'handle_save_design' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_save_layout', array( $this, 'handle_save_layout' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_rename_design', array( $this, 'handle_rename_design' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_delete_design', array( $this, 'handle_delete_design' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_rename_layout', array( $this, 'handle_rename_layout' ) );
		add_action( 'admin_post_nopriv_tta_threaddesk_delete_layout', array( $this, 'handle_delete_layout' ) );
		add_action( 'admin_post_tta_threaddesk_admin_save_user', array( $this, 'handle_admin_save_user' ) );
		add_action( 'admin_post_tta_threaddesk_export_activity_csv', array( $this, 'handle_admin_export_activity_csv' ) );
		add_action( 'wp_ajax_tta_threaddesk_screenprint_add_to_quote', array( $this, 'handle_screenprint_add_to_quote' ) );
		add_action( 'wp_ajax_tta_threaddesk_screenprint_variations', array( $this, 'handle_screenprint_variations' ) );
		add_action( 'wp_ajax_nopriv_tta_threaddesk_screenprint_variations', array( $this, 'handle_screenprint_variations' ) );
		add_action( 'wp_ajax_tta_threaddesk_screenprint_bootstrap', array( $this, 'handle_screenprint_bootstrap' ) );
		add_action( 'wp_ajax_nopriv_tta_threaddesk_screenprint_bootstrap', array( $this, 'handle_screenprint_bootstrap' ) );
		add_action( 'user_register', array( $this, 'handle_user_register' ) );
		add_action( 'init', array( $this, 'handle_auth_login' ) );
		add_action( 'init', array( $this, 'handle_auth_register' ) );
		add_action( 'wp_login', array( $this, 'handle_wp_login_merge_guest_drafts' ), 10, 2 );
		add_shortcode( 'threaddesk', array( $this, 'render_shortcode' ) );
		add_shortcode( 'threaddesk_auth', array( $this, 'render_auth_shortcode' ) );
		add_shortcode( 'threaddesk_screenprint', array( $this, 'render_screenprint_shortcode' ) );

		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_screenprint_cart_selection' ), 10, 5 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'capture_screenprint_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'restore_screenprint_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'render_screenprint_cart_item_display_data' ), 10, 2 );
	}

	/**
	 * Prevent missing heartbeat dependency notices when wp-auth-check is enqueued.
	 *
	 * @param WP_Scripts $scripts Script registry instance.
	 *
	 * @return void
	 */
	public function ensure_heartbeat_dependency( $scripts ) {
		if ( ! $scripts instanceof WP_Scripts ) {
			return;
		}

		if ( isset( $scripts->registered['heartbeat'] ) ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$scripts->add( 'heartbeat', includes_url( 'js/heartbeat' . $suffix . '.js' ), array( 'jquery', 'wp-hooks' ), false, 1 );
	}

	public static function activate() {
		$instance = self::instance();
		$instance->register_post_types();
		$instance->endpoints->register_endpoints();
		$instance->guest_tokens->maybe_schedule_cleanup();
		flush_rewrite_rules();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'threaddesk', false, basename( dirname( THREDDESK_PATH ) ) . '/languages' );
	}

	public function register_post_types() {
		$common_args = array(
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'tta-threaddesk',
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
		add_menu_page(
			__( 'ThreadDesk', 'threaddesk' ),
			__( 'ThreadDesk', 'threaddesk' ),
			'manage_woocommerce',
			'tta-threaddesk',
			array( $this, 'render_admin_quotes_page' ),
			'dashicons-screenoptions',
			56
		);


		add_submenu_page(
			'tta-threaddesk',
			__( 'Users', 'threaddesk' ),
			__( 'Users', 'threaddesk' ),
			'manage_woocommerce',
			'tta-threaddesk-users',
			array( $this, 'render_admin_users_page' )
		);

		// Hidden page used when navigating from the Users table into a specific profile.
		add_submenu_page(
			'',
			__( 'User Detail', 'threaddesk' ),
			__( 'User Detail', 'threaddesk' ),
			'manage_woocommerce',
			'tta-threaddesk-user-detail',
			array( $this, 'render_admin_user_detail_page' )
		);

		add_submenu_page(
			'tta-threaddesk',
			__( 'Settings', 'threaddesk' ),
			__( 'Settings', 'threaddesk' ),
			'manage_woocommerce',
			'tta-threaddesk-settings',
			array( $this, 'render_settings_page' )
		);

		remove_submenu_page( 'tta-threaddesk', 'tta-threaddesk' );
		remove_submenu_page( 'woocommerce', 'tta-threaddesk' );
	}


	public function enqueue_admin_assets( $hook ) {
		$is_thread_desk_screen = 'toplevel_page_tta-threaddesk' === $hook || false !== strpos( (string) $hook, 'tta-threaddesk-settings' );
		$product_post_id       = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		$product_post_type     = '';
		if ( isset( $_GET['post_type'] ) ) {
			$product_post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		} elseif ( $product_post_id > 0 ) {
			$product_post_type = get_post_type( $product_post_id );
		}
		$is_product_editor     = in_array( (string) $hook, array( 'post.php', 'post-new.php' ), true ) && 'product' === $product_post_type;
		if ( ! $is_product_editor && ! $is_thread_desk_screen ) {
			return;
		}

		if ( wp_script_is( 'heartbeat', 'registered' ) ) {
			wp_enqueue_script( 'heartbeat' );
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
	}



	private function get_available_placement_slots() {
		return array(
			'left_chest'  => __( 'Left Chest', 'threaddesk' ),
			'right_chest' => __( 'Right Chest', 'threaddesk' ),
			'full_chest'  => __( 'Full Chest', 'threaddesk' ),
			'left_sleeve' => __( 'Left Sleeve', 'threaddesk' ),
			'right_sleeve'=> __( 'Right Sleeve', 'threaddesk' ),
			'back'        => __( 'Back', 'threaddesk' ),
		);
	}

	private function get_design_status_options() {
		return array(
			'pending'  => __( 'Pending', 'threaddesk' ),
			'approved' => __( 'Approved', 'threaddesk' ),
			'rejected' => __( 'Rejected', 'threaddesk' ),
		);
	}

	private function get_design_rejection_reason_options() {
		return array(
			'low_resolution' => __( 'The design file is of too low a resolution to proceed to printing.', 'threaddesk' ),
			'copyright_risk' => __( 'The design is copyrighted and is at risk of infringement.', 'threaddesk' ),
			'detail_concerns' => __( 'Gradients/Transparencies/Fine detail concerns', 'threaddesk' ),
			'other'          => __( 'Other (a representative will be in contact with you in the coming days)', 'threaddesk' ),
		);
	}

	private function sanitize_design_rejection_reason( $reason ) {
		$reason  = sanitize_key( (string) $reason );
		$options = $this->get_design_rejection_reason_options();
		return isset( $options[ $reason ] ) ? $reason : '';
	}

	private function sanitize_design_status( $status ) {
		$status  = sanitize_key( (string) $status );
		$options = $this->get_design_status_options();
		return isset( $options[ $status ] ) ? $status : 'pending';
	}

	private function get_design_status( $design_id ) {
		$stored = get_post_meta( (int) $design_id, 'design_status', true );
		return $this->sanitize_design_status( $stored );
	}

	private function get_layout_status_options() {
		return array(
			'pending'  => __( 'Pending', 'threaddesk' ),
			'approved' => __( 'Approved', 'threaddesk' ),
			'rejected' => __( 'Rejected', 'threaddesk' ),
		);
	}

	private function get_layout_rejection_reason_options() {
		return array(
			'placement_restrictions' => __( 'Placement restrictions (too close to seams, on zippers, pockets or off garment)', 'threaddesk' ),
			'overlapping_designs'    => __( 'Overlapping designs', 'threaddesk' ),
			'other'                  => __( 'Other', 'threaddesk' ),
		);
	}

	private function sanitize_layout_status( $status ) {
		$status  = sanitize_key( (string) $status );
		$options = $this->get_layout_status_options();
		return isset( $options[ $status ] ) ? $status : 'pending';
	}

	private function sanitize_layout_rejection_reason( $reason ) {
		$reason  = sanitize_key( (string) $reason );
		$options = $this->get_layout_rejection_reason_options();
		return isset( $options[ $reason ] ) ? $reason : '';
	}

	private function get_layout_status( $layout_id ) {
		$stored = get_post_meta( (int) $layout_id, 'layout_status', true );
		return $this->sanitize_layout_status( $stored );
	}

	private function get_quote_status_options() {
		return array(
			'pending'  => __( 'Pending', 'threaddesk' ),
			'approved' => __( 'Approved', 'threaddesk' ),
			'rejected' => __( 'Rejected', 'threaddesk' ),
		);
	}

	private function sanitize_quote_status( $status ) {
		$status = sanitize_key( (string) $status );
		if ( in_array( $status, array( 'draft', 'private', 'new' ), true ) ) {
			$status = 'pending';
		}
		$options = $this->get_quote_status_options();
		return isset( $options[ $status ] ) ? $status : 'pending';
	}

	private function get_quote_status( $quote_id ) {
		$stored = get_post_meta( (int) $quote_id, 'status', true );
		return $this->sanitize_quote_status( $stored );
	}

	private function render_media_picker_field( $name, $value ) {
		$value = $value ? esc_url( $value ) : '';
		?>
		<div class="threaddesk-media-picker-field">
			<input type="hidden" data-threaddesk-media-input name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<button type="button" class="button" data-threaddesk-media-select><?php echo esc_html__( 'Select image', 'threaddesk' ); ?></button>
			<button type="button" class="button-link" data-threaddesk-media-clear><?php echo esc_html__( 'Clear', 'threaddesk' ); ?></button>
			<div><img data-threaddesk-media-preview src="<?php echo esc_url( $value ); ?>" style="max-width:80px; max-height:80px; <?php echo $value ? '' : 'display:none;'; ?>" alt="" /></div>
		</div>
		<?php
	}

	private function render_settings_page_inline_script() {
		?>
		<script data-cfasync="false">
		jQuery(function ($) {
			const tbody = $('[data-threaddesk-placement-sortable]');
			const refreshOrders = function () {
				tbody.find('[data-threaddesk-placement-row]').each(function (index) {
					$(this).find('[data-threaddesk-placement-order]').val(index + 1);
				});
			};

			if (tbody.length && $.fn.sortable) {
				tbody.sortable({
					items: '> tr[data-threaddesk-placement-row]',
					handle: '.dashicons-move',
					axis: 'y',
					update: refreshOrders,
				});
				refreshOrders();
			}

			$(document).on('click', '[data-threaddesk-media-select]', function (event) {
				event.preventDefault();
				const wrapper = $(this).closest('.threaddesk-media-picker-field');
				const input = wrapper.find('[data-threaddesk-media-input]');
				const preview = wrapper.find('[data-threaddesk-media-preview]');
				const frame = wp.media({
					title: 'Select image',
					button: { text: 'Use image' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					const attachment = frame.state().get('selection').first().toJSON();
					const url = attachment.url || '';
					input.val(url);
					if (url) {
						preview.attr('src', url).show();
					}
				});

				frame.open();
			});

			$(document).on('click', '[data-threaddesk-media-clear]', function (event) {
				event.preventDefault();
				const wrapper = $(this).closest('.threaddesk-media-picker-field');
				wrapper.find('[data-threaddesk-media-input]').val('');
				wrapper.find('[data-threaddesk-media-preview]').attr('src', '').hide();
			});
		});
		</script>
		<?php
	}

	public function register_settings() {
		register_setting( 'tta_threaddesk_settings', 'tta_threaddesk_cover_image_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'tta_threaddesk_settings', 'tta_threaddesk_default_company', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'tta_threaddesk_settings', 'tta_threaddesk_layout_categories', array( 'sanitize_callback' => array( $this, 'sanitize_layout_categories' ) ) );
		register_setting( 'tta_threaddesk_settings', 'tta_threaddesk_print_pricing', array( 'sanitize_callback' => array( $this, 'sanitize_print_pricing_settings' ) ) );
	}

	private function get_default_print_pricing_settings() {
		return array(
			'setup_cost'       => 50,
			'color_setup_cost' => 30,
			'color_change_cost'=> 5,
			'repeat_reduction' => 15,
			'print_cost'       => 1.25,
			'color_cost'       => 0.10,
			'garment_cost'     => 50,
			'total_margins'    => 30,
		);
	}

	public function sanitize_print_pricing_settings( $value ) {
		$defaults = $this->get_default_print_pricing_settings();
		if ( ! is_array( $value ) ) {
			return $defaults;
		}

		$sanitized = $defaults;
		foreach ( $defaults as $key => $default_value ) {
			$raw_value = isset( $value[ $key ] ) ? $value[ $key ] : $default_value;
			$number = is_numeric( $raw_value ) ? (float) $raw_value : (float) $default_value;
			if ( $number < 0 ) {
				$number = 0;
			}
			if ( in_array( $key, array( 'garment_cost', 'total_margins' ), true ) && $number > 99.99 ) {
				$number = 99.99;
			}
			$sanitized[ $key ] = in_array( $key, array( 'setup_cost', 'color_setup_cost', 'color_change_cost', 'repeat_reduction', 'garment_cost', 'total_margins' ), true ) ? round( $number, 2 ) : round( $number, 4 );
		}

		return $sanitized;
	}

	public function sanitize_layout_categories( $value ) {
		$sanitized = array();
		if ( ! is_array( $value ) ) {
			return $sanitized;
		}

		$sortable = array();
		$placement_slots = $this->get_available_placement_slots();

		foreach ( $value as $term_id => $row ) {
			$term_id = absint( $term_id );
			if ( ! $term_id || empty( $row['enabled'] ) ) {
				continue;
			}

			$order = isset( $row['order'] ) ? absint( $row['order'] ) : 9999;
			$placements = array();
			if ( ! empty( $row['placements'] ) && is_array( $row['placements'] ) ) {
				foreach ( $row['placements'] as $placement_key => $enabled ) {
					$placement_key = sanitize_key( $placement_key );
					if ( isset( $placement_slots[ $placement_key ] ) && $enabled ) {
						$placements[] = $placement_key;
					}
				}
			}
			if ( empty( $placements ) ) {
				$placements = array_keys( $placement_slots );
			}

			$product_categories = array();
			if ( ! empty( $row['product_categories'] ) && is_array( $row['product_categories'] ) ) {
				foreach ( $row['product_categories'] as $product_category_id => $enabled ) {
					$product_category_id = absint( $product_category_id );
					if ( $product_category_id > 0 && $enabled ) {
						$product_categories[] = $product_category_id;
					}
				}
			}

			$product_categories = array_values( array_unique( $product_categories ) );

			$sortable[] = array(
				'term_id' => $term_id,
				'order'   => $order,
				'data'    => array(
					'enabled'     => 1,
					'order'       => $order,
					'front_image' => isset( $row['front_image'] ) ? esc_url_raw( $row['front_image'] ) : '',
					'back_image'  => isset( $row['back_image'] ) ? esc_url_raw( $row['back_image'] ) : '',
					'side_image'  => isset( $row['side_image'] ) ? esc_url_raw( $row['side_image'] ) : '',
					'side_label'  => isset( $row['side_label'] ) && 'right' === sanitize_key( $row['side_label'] ) ? 'right' : 'left',
					'placements'  => $placements,
					'product_categories' => $product_categories,
				),
			);
		}

		usort(
			$sortable,
			function ( $a, $b ) {
				if ( $a['order'] === $b['order'] ) {
					return $a['term_id'] - $b['term_id'];
				}

				return $a['order'] - $b['order'];
			}
		);

		$position = 1;
		foreach ( $sortable as $item ) {
			$item['data']['order'] = $position;
			$sanitized[ $item['term_id'] ] = $item['data'];
			$position++;
		}

		return $sanitized;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$cover_image        = get_option( 'tta_threaddesk_cover_image_url', '' );
		$company            = get_option( 'tta_threaddesk_default_company', '' );
		$layout_categories  = get_option( 'tta_threaddesk_layout_categories', array() );
		$print_pricing      = get_option( 'tta_threaddesk_print_pricing', array() );
		$print_pricing      = wp_parse_args( is_array( $print_pricing ) ? $print_pricing : array(), $this->get_default_print_pricing_settings() );
		$placement_terms    = taxonomy_exists( 'product_cat' ) ? get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		) : array();
		$placement_terms    = is_wp_error( $placement_terms ) ? array() : $placement_terms;
		$placement_order_map = array();
		if ( is_array( $layout_categories ) ) {
			foreach ( $layout_categories as $term_id => $row ) {
				$placement_order_map[ absint( $term_id ) ] = isset( $row['order'] ) ? absint( $row['order'] ) : 9999;
			}
		}
		if ( ! empty( $placement_terms ) ) {
			usort(
				$placement_terms,
				function ( $a, $b ) use ( $placement_order_map ) {
					$a_order = isset( $placement_order_map[ $a->term_id ] ) ? $placement_order_map[ $a->term_id ] : 9999;
					$b_order = isset( $placement_order_map[ $b->term_id ] ) ? $placement_order_map[ $b->term_id ] : 9999;
					if ( $a_order === $b_order ) {
						return strcasecmp( $a->name, $b->name );
					}
					return $a_order - $b_order;
				}
			);
		}
		$placement_slots     = $this->get_available_placement_slots();
		$nonce              = wp_create_nonce( 'tta_threaddesk_generate_demo' );
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
					<tr>
						<th scope="row"><?php echo esc_html__( 'Print Cost Variables', 'threaddesk' ); ?></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Used for estimated unit-cost values in the Screenprint quantities step.', 'threaddesk' ); ?></p>
							<div style="display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:10px;max-width:760px;">
								<label>
									<span><?php echo esc_html__( 'Setup Cost', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.01" name="tta_threaddesk_print_pricing[setup_cost]" value="<?php echo esc_attr( (string) $print_pricing['setup_cost'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Color Setup Cost', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.01" name="tta_threaddesk_print_pricing[color_setup_cost]" value="<?php echo esc_attr( (string) $print_pricing['color_setup_cost'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Color Change Cost', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.01" name="tta_threaddesk_print_pricing[color_change_cost]" value="<?php echo esc_attr( (string) $print_pricing['color_change_cost'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Repeat Reduction', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.01" name="tta_threaddesk_print_pricing[repeat_reduction]" value="<?php echo esc_attr( (string) $print_pricing['repeat_reduction'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Print Cost', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.0001" name="tta_threaddesk_print_pricing[print_cost]" value="<?php echo esc_attr( (string) $print_pricing['print_cost'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Color Cost', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.0001" name="tta_threaddesk_print_pricing[color_cost]" value="<?php echo esc_attr( (string) $print_pricing['color_cost'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Garment Cost (%)', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" step="0.01" name="tta_threaddesk_print_pricing[garment_cost]" value="<?php echo esc_attr( (string) $print_pricing['garment_cost'] ); ?>" />
								</label>
								<label>
									<span><?php echo esc_html__( 'Total Margins (%)', 'threaddesk' ); ?></span><br />
									<input type="number" min="0" max="99.99" step="0.01" name="tta_threaddesk_print_pricing[total_margins]" value="<?php echo esc_attr( (string) $print_pricing['total_margins'] ); ?>" />
								</label>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Placement Categories', 'threaddesk' ); ?></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Choose which WooCommerce product categories appear under Placements. Configure Front, Back, and Side (default Left) images for each enabled category.', 'threaddesk' ); ?></p>
							<?php if ( ! empty( $placement_terms ) ) : ?>
								<table class="widefat striped" style="margin-top:10px;">
										<thead>
											<tr>
												<th><?php echo esc_html__( 'Order', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Use', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Category', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Front Image', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Back Image', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Side Image', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Side', 'threaddesk' ); ?></th>
												<th><?php echo esc_html__( 'Placements', 'threaddesk' ); ?></th>
											</tr>
										</thead>
										<tbody data-threaddesk-placement-sortable>
											<?php foreach ( $placement_terms as $index => $term ) : ?>
												<?php $configured = isset( $layout_categories[ $term->term_id ] ) && is_array( $layout_categories[ $term->term_id ] ) ? $layout_categories[ $term->term_id ] : array(); ?>
												<?php $front_image = isset( $configured['front_image'] ) ? $configured['front_image'] : ''; ?>
												<?php $back_image = isset( $configured['back_image'] ) ? $configured['back_image'] : ''; ?>
												<?php $side_image = isset( $configured['side_image'] ) ? $configured['side_image'] : ''; ?>
												<?php $configured_placements = isset( $configured['placements'] ) && is_array( $configured['placements'] ) ? $configured['placements'] : array_keys( $placement_slots ); ?>
												<?php $row_order = isset( $configured['order'] ) ? absint( $configured['order'] ) : ( $index + 1 ); ?>
												<tr data-threaddesk-placement-row>
													<td><span class="dashicons dashicons-move" aria-hidden="true"></span><input type="hidden" data-threaddesk-placement-order name="tta_threaddesk_layout_categories[<?php echo esc_attr( $term->term_id ); ?>][order]" value="<?php echo esc_attr( $row_order ); ?>" /></td>
													<td><label><input type="checkbox" name="tta_threaddesk_layout_categories[<?php echo esc_attr( $term->term_id ); ?>][enabled]" value="1" <?php checked( ! empty( $configured['enabled'] ) ); ?> /> <?php echo esc_html__( 'Show', 'threaddesk' ); ?></label></td>
													<td><strong><?php echo esc_html( $term->name ); ?></strong><br /><code><?php echo esc_html( $term->slug ); ?></code></td>
													<td><?php $this->render_media_picker_field( "tta_threaddesk_layout_categories[{$term->term_id}][front_image]", $front_image ); ?></td>
													<td><?php $this->render_media_picker_field( "tta_threaddesk_layout_categories[{$term->term_id}][back_image]", $back_image ); ?></td>
													<td><?php $this->render_media_picker_field( "tta_threaddesk_layout_categories[{$term->term_id}][side_image]", $side_image ); ?></td>
													<td>
														<select name="tta_threaddesk_layout_categories[<?php echo esc_attr( $term->term_id ); ?>][side_label]">
															<option value="left" <?php selected( isset( $configured['side_label'] ) ? $configured['side_label'] : 'left', 'left' ); ?>><?php echo esc_html__( 'Left', 'threaddesk' ); ?></option>
															<option value="right" <?php selected( isset( $configured['side_label'] ) ? $configured['side_label'] : 'left', 'right' ); ?>><?php echo esc_html__( 'Right', 'threaddesk' ); ?></option>
														</select>
													</td>

													<td>
														<?php foreach ( $placement_slots as $placement_key => $placement_label ) : ?>
															<label style="display:block; margin:0 0 4px;">
																<input type="checkbox" name="tta_threaddesk_layout_categories[<?php echo esc_attr( $term->term_id ); ?>][placements][<?php echo esc_attr( $placement_key ); ?>]" value="1" <?php checked( in_array( $placement_key, $configured_placements, true ) ); ?> />
																<?php echo esc_html( $placement_label ); ?>
															</label>
														<?php endforeach; ?>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
</table>
								<p class="description"><?php echo esc_html__( 'Drag rows by the move icon to control display order in Placements.', 'threaddesk' ); ?></p>
								<?php
								$enabled_placement_terms = array();
								foreach ( $placement_terms as $placement_term ) {
									$placement_term_id = (int) $placement_term->term_id;
									if ( isset( $layout_categories[ $placement_term_id ]['enabled'] ) && ! empty( $layout_categories[ $placement_term_id ]['enabled'] ) ) {
										$enabled_placement_terms[] = $placement_term;
									}
								}
								?>
								<h3 style="margin-top:18px;"><?php echo esc_html__( 'Placement Availability by Product Category', 'threaddesk' ); ?></h3>
								<p class="description"><?php echo esc_html__( 'For each enabled placement category, choose which product categories can use it in Placements.', 'threaddesk' ); ?></p>
								<?php if ( ! empty( $enabled_placement_terms ) ) : ?>
									<div style="overflow:auto; margin-top:10px;">
										<table class="widefat striped">
											<thead>
												<tr>
													<th><?php echo esc_html__( 'Placement Category', 'threaddesk' ); ?></th>
													<?php foreach ( $placement_terms as $product_term ) : ?>
														<th><?php echo esc_html( $product_term->name ); ?></th>
													<?php endforeach; ?>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $enabled_placement_terms as $placement_term ) : ?>
													<?php
													$placement_term_id = (int) $placement_term->term_id;
													$configured_product_categories = isset( $layout_categories[ $placement_term_id ]['product_categories'] ) && is_array( $layout_categories[ $placement_term_id ]['product_categories'] ) ? array_map( 'absint', $layout_categories[ $placement_term_id ]['product_categories'] ) : array();
													?>
													<tr>
														<td><strong><?php echo esc_html( $placement_term->name ); ?></strong></td>
														<?php foreach ( $placement_terms as $product_term ) : ?>
															<td style="text-align:center;">
																<input type="checkbox" name="tta_threaddesk_layout_categories[<?php echo esc_attr( $placement_term_id ); ?>][product_categories][<?php echo esc_attr( $product_term->term_id ); ?>]" value="1" <?php checked( in_array( (int) $product_term->term_id, $configured_product_categories, true ) ); ?> />
															</td>
														<?php endforeach; ?>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php else : ?>
									<p><?php echo esc_html__( 'Enable at least one placement category above to map product category availability.', 'threaddesk' ); ?></p>
								<?php endif; ?>
							<?php else : ?>
								<p><?php echo esc_html__( 'No product categories found. Create WooCommerce product categories first.', 'threaddesk' ); ?></p>
							<?php endif; ?>
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
					<tr>
						<td><code>[threaddesk_screenprint]</code></td>
						<td><?php echo esc_html__( 'Use on single product pages to let logged-in users apply saved layouts as screenprint previews on the current product images.', 'threaddesk' ); ?></td>
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
			<?php $this->render_settings_page_inline_script(); ?>
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
			if ( ! $is_update ) {
			update_post_meta( $layout_id, 'created_at', current_time( 'mysql' ) );
		}
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
		$this->log_user_activity( get_current_user_id(), sprintf( __( 'Quote submitted for approval: %s', 'threaddesk' ), $quote->post_title ), 'quote' );
		update_post_meta( $quote_id, 'requested_at', current_time( 'mysql' ) );
		update_user_meta( get_current_user_id(), 'tta_threaddesk_last_request', current_time( 'mysql' ) );

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Order request sent successfully.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) . 'quotes/' );
		exit;
	}


	public function handle_screenprint_add_to_quote() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to add a quote.', 'threaddesk' ) ), 401 );
		}

		check_ajax_referer( 'tta_threaddesk_screenprint_quote', 'nonce' );

		$product_id      = isset( $_POST['productId'] ) ? absint( $_POST['productId'] ) : 0;
		$layout_id       = isset( $_POST['layoutId'] ) ? absint( $_POST['layoutId'] ) : 0;
		$layout_title    = isset( $_POST['layoutTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['layoutTitle'] ) ) : '';
		$selected_color  = isset( $_POST['selectedColor'] ) ? sanitize_text_field( wp_unslash( $_POST['selectedColor'] ) ) : '';
		$selected_color_key = isset( $_POST['selectedColorKey'] ) ? sanitize_key( wp_unslash( $_POST['selectedColorKey'] ) ) : '';
		$quote_title_input = isset( $_POST['quoteTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['quoteTitle'] ) ) : '';
		$existing_quote_id = isset( $_POST['existingQuoteId'] ) ? absint( $_POST['existingQuoteId'] ) : 0;
		$rows_raw        = isset( $_POST['rows'] ) ? wp_unslash( $_POST['rows'] ) : array();
		$prints_raw      = isset( $_POST['prints'] ) ? wp_unslash( $_POST['prints'] ) : array();
		$rows_json_raw   = isset( $_POST['rowsJson'] ) ? wp_unslash( $_POST['rowsJson'] ) : '';
		$prints_json_raw = isset( $_POST['printsJson'] ) ? wp_unslash( $_POST['printsJson'] ) : '';

		$rows = array();
		if ( is_string( $rows_json_raw ) && '' !== trim( $rows_json_raw ) ) {
			$rows_candidate = json_decode( (string) $rows_json_raw, true );
			if ( is_array( $rows_candidate ) ) {
				$rows = $rows_candidate;
			}
		}
		if ( empty( $rows ) && is_array( $rows_raw ) ) {
			$rows = $rows_raw;
		} elseif ( empty( $rows ) && is_string( $rows_raw ) && '' !== trim( $rows_raw ) ) {
			$rows_candidate = json_decode( (string) $rows_raw, true );
			if ( is_array( $rows_candidate ) ) {
				$rows = $rows_candidate;
			}
		}
		$quote_rows = array();
		$total = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$qty = isset( $row['qty'] ) ? absint( $row['qty'] ) : 0;
			if ( $qty <= 0 ) {
				continue;
			}
			$estimated_unit_cost = isset( $row['estimatedUnitCost'] ) ? (float) $row['estimatedUnitCost'] : 0;
			$line_total = $estimated_unit_cost * $qty;
			$total += $line_total;
			$placement_items = array();
			if ( isset( $row['placements'] ) && is_array( $row['placements'] ) ) {
				foreach ( $row['placements'] as $placement ) {
					if ( ! is_array( $placement ) ) {
						continue;
					}
					$placement_colors = array();
					$placement_selected_colors = array();
					if ( isset( $placement['selectedColors'] ) && is_array( $placement['selectedColors'] ) ) {
						$placement_selected_colors = $placement['selectedColors'];
					} elseif ( isset( $placement['selectedColors'] ) && is_string( $placement['selectedColors'] ) ) {
						$placement_selected_colors = array_map( 'trim', explode( ',', (string) $placement['selectedColors'] ) );
					}
					foreach ( $placement_selected_colors as $placement_color ) {
							$clean_color = sanitize_text_field( (string) $placement_color );
							if ( '' !== $clean_color ) {
								$placement_colors[] = $clean_color;
							}
					}
					$placement_items[] = array(
						'placementLabel' => isset( $placement['placementLabel'] ) ? sanitize_text_field( (string) $placement['placementLabel'] ) : '',
						'designName'     => isset( $placement['designName'] ) ? sanitize_text_field( (string) $placement['designName'] ) : '',
						'designId'       => isset( $placement['designId'] ) ? absint( $placement['designId'] ) : 0,
						'approxSize'     => isset( $placement['approxSize'] ) ? absint( $placement['approxSize'] ) : 0,
						'approxSizeLabel'=> isset( $placement['approxSizeLabel'] ) ? sanitize_text_field( (string) $placement['approxSizeLabel'] ) : '',
						'selectedColors' => $placement_colors,
					);
				}
			}

			$mockups = array();
			if ( isset( $row['mockups'] ) && is_array( $row['mockups'] ) ) {
				$mockup_views = array( 'front', 'left', 'right', 'side', 'back' );
				foreach ( $mockup_views as $view_key ) {
					$mockups[ $view_key ] = isset( $row['mockups'][ $view_key ] ) ? esc_url_raw( (string) $row['mockups'][ $view_key ] ) : '';
				}
				$mockups['sideLabel'] = isset( $row['mockups']['sideLabel'] ) && 'right' === sanitize_key( (string) $row['mockups']['sideLabel'] ) ? 'right' : 'left';
				$mockups['rightSource'] = isset( $row['mockups']['rightSource'] ) && 'left' === sanitize_key( (string) $row['mockups']['rightSource'] ) ? 'left' : 'right';
			}
			$placement_overlays = array();
			if ( isset( $row['placementOverlays'] ) && is_array( $row['placementOverlays'] ) ) {
				foreach ( $row['placementOverlays'] as $angle_key => $entries ) {
					if ( ! is_array( $entries ) ) {
						continue;
					}
					$clean_angle_key = sanitize_key( (string) $angle_key );
					if ( '' === $clean_angle_key ) {
						$clean_angle_key = 'front';
					}
					$placement_overlays[ $clean_angle_key ] = array();
					foreach ( $entries as $entry ) {
						if ( ! is_array( $entry ) ) {
							continue;
						}
						$url_raw = isset( $entry['url'] ) ? (string) $entry['url'] : '';
						$url = esc_url_raw( $url_raw );
						if ( '' === $url && preg_match( '#^data:image\/(png|jpe?g|webp);base64,#i', $url_raw ) ) {
							$url = $url_raw;
						}
						if ( '' === $url ) {
							continue;
						}
						$placement_overlays[ $clean_angle_key ][] = array(
							'placementKey'   => isset( $entry['placementKey'] ) ? sanitize_text_field( (string) $entry['placementKey'] ) : '',
							'placementLabel' => isset( $entry['placementLabel'] ) ? sanitize_text_field( (string) $entry['placementLabel'] ) : '',
							'designId'       => isset( $entry['designId'] ) ? absint( $entry['designId'] ) : 0,
							'designName'     => isset( $entry['designName'] ) ? sanitize_text_field( (string) $entry['designName'] ) : '',
							'angle'          => isset( $entry['angle'] ) ? sanitize_key( (string) $entry['angle'] ) : $clean_angle_key,
							'url'            => $url,
							'top'            => isset( $entry['top'] ) ? (float) $entry['top'] : 50.0,
							'left'           => isset( $entry['left'] ) ? (float) $entry['left'] : 50.0,
							'width'          => isset( $entry['width'] ) ? (float) $entry['width'] : 25.0,
						);
					}
					if ( empty( $placement_overlays[ $clean_angle_key ] ) ) {
						unset( $placement_overlays[ $clean_angle_key ] );
					}
				}
			}
			$quote_rows[] = array(
				'variationId'                => isset( $row['variationId'] ) ? absint( $row['variationId'] ) : 0,
				'productSku'                 => isset( $row['productSku'] ) ? sanitize_text_field( (string) $row['productSku'] ) : '',
				'productShortDescription'    => isset( $row['productShortDescription'] ) ? sanitize_text_field( (string) $row['productShortDescription'] ) : '',
				'qty'                        => $qty,
				'estimatedUnitCost'          => round( $estimated_unit_cost, 4 ),
				'estimatedVariationCostTotal'=> round( $line_total, 4 ),
				'placements'                 => $placement_items,
				'placementOverlays'          => $placement_overlays,
				'mockups'                    => $mockups,
			);
		}

		if ( empty( $quote_rows ) ) {
			wp_send_json_error( array( 'message' => __( 'Add at least one quantity before creating a quote.', 'threaddesk' ) ), 400 );
		}

		$prints = array();
		$prints_source = array();
		if ( is_string( $prints_json_raw ) && '' !== trim( $prints_json_raw ) ) {
			$prints_candidate = json_decode( (string) $prints_json_raw, true );
			if ( is_array( $prints_candidate ) ) {
				$prints_source = $prints_candidate;
			}
		}
		if ( empty( $prints_source ) && is_array( $prints_raw ) ) {
			$prints_source = $prints_raw;
		} elseif ( empty( $prints_source ) && is_string( $prints_raw ) && '' !== trim( $prints_raw ) ) {
			$prints_candidate = json_decode( (string) $prints_raw, true );
			if ( is_array( $prints_candidate ) ) {
				$prints_source = $prints_candidate;
			}
		}
		if ( is_array( $prints_source ) ) {
			foreach ( $prints_source as $print ) {
				if ( ! is_array( $print ) ) {
					continue;
				}
				$colors = array();
				$print_selected_colors = array();
				if ( isset( $print['selectedColors'] ) && is_array( $print['selectedColors'] ) ) {
					$print_selected_colors = $print['selectedColors'];
				} elseif ( isset( $print['selectedColors'] ) && is_string( $print['selectedColors'] ) ) {
					$print_selected_colors = array_map( 'trim', explode( ',', (string) $print['selectedColors'] ) );
				}
				foreach ( $print_selected_colors as $color ) {
						$clean_color = sanitize_text_field( (string) $color );
						if ( '' !== $clean_color ) {
							$colors[] = $clean_color;
						}
				}
				$prints[] = array(
					'printKey'       => isset( $print['printKey'] ) ? sanitize_text_field( (string) $print['printKey'] ) : '',
					'designId'        => isset( $print['designId'] ) ? absint( $print['designId'] ) : 0,
					'designName'      => isset( $print['designName'] ) ? sanitize_text_field( (string) $print['designName'] ) : '',
					'placementLabel'  => isset( $print['placementLabel'] ) ? sanitize_text_field( (string) $print['placementLabel'] ) : '',
					'approxSize'      => isset( $print['approxSize'] ) ? absint( $print['approxSize'] ) : 0,
					'approxSizeLabel' => isset( $print['approxSizeLabel'] ) ? sanitize_text_field( (string) $print['approxSizeLabel'] ) : '',
					'selectedColors'  => $colors,
				);
			}
		}

		$user_id = get_current_user_id();
		$product = $product_id > 0 && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		$product_name = $product && is_callable( array( $product, 'get_name' ) ) ? (string) $product->get_name() : __( 'Product', 'threaddesk' );
		$quote_title_default = sprintf( __( 'Screenprint Quote - %1$s - %2$s', 'threaddesk' ), $product_name, current_time( 'Y-m-d H:i' ) );
		$quote_title = '' !== trim( $quote_title_input ) ? trim( $quote_title_input ) : $quote_title_default;

		$quote_id = 0;
		$is_update = false;
		if ( $existing_quote_id > 0 ) {
			$existing_quote = get_post( $existing_quote_id );
			if ( $existing_quote && 'tta_quote' === $existing_quote->post_type && (int) $existing_quote->post_author === $user_id ) {
				$quote_id = (int) $existing_quote_id;
				$is_update = true;
			}
		}

		$existing_rows = array();
		$existing_prints = array();
		$existing_total = 0;
		if ( $is_update ) {
			$existing_rows_raw = get_post_meta( $quote_id, 'screenprint_quote_rows_json', true );
			if ( is_array( $existing_rows_raw ) ) {
				$existing_rows = $existing_rows_raw;
			} else {
				$existing_rows = json_decode( (string) $existing_rows_raw, true );
			}
			$existing_rows = is_array( $existing_rows ) ? $existing_rows : array();
			$existing_prints_raw = get_post_meta( $quote_id, 'screenprint_quote_prints_json', true );
			if ( is_array( $existing_prints_raw ) ) {
				$existing_prints = $existing_prints_raw;
			} else {
				$existing_prints = json_decode( (string) $existing_prints_raw, true );
			}
			$existing_prints = is_array( $existing_prints ) ? $existing_prints : array();
			$existing_total = (float) get_post_meta( $quote_id, 'total', true );
		} else {
			$quote_id = wp_insert_post(
				array(
					'post_type'   => 'tta_quote',
					'post_status' => 'private',
					'post_title'  => $quote_title,
					'post_author' => $user_id,
				),
				true
			);

			if ( is_wp_error( $quote_id ) || ! $quote_id ) {
				wp_send_json_error( array( 'message' => __( 'Unable to create quote right now.', 'threaddesk' ) ), 500 );
			}
		}

		if ( ! $is_update && '' !== $quote_title ) {
			wp_update_post( array( 'ID' => $quote_id, 'post_title' => $quote_title ) );
		}

		$normalize_print_signature = static function ( $print ) {
			if ( ! is_array( $print ) ) {
				return '';
			}
			$design_id = isset( $print['designId'] ) ? absint( $print['designId'] ) : 0;
			$design_name = isset( $print['designName'] ) ? sanitize_text_field( (string) $print['designName'] ) : '';
			$size_value = isset( $print['approxSize'] ) ? absint( $print['approxSize'] ) : 0;
			if ( $size_value <= 0 && isset( $print['approxSizeLabel'] ) ) {
				if ( preg_match( '/(\d+)/', (string) $print['approxSizeLabel'], $matches ) ) {
					$size_value = absint( $matches[1] );
				}
			}
			if ( $size_value <= 0 ) {
				$size_value = 100;
			}
			if ( $design_id > 0 ) {
				return 'id:' . (string) $design_id . '|size:' . (string) $size_value;
			}
			$name_key = sanitize_title( $design_name );
			if ( '' === $name_key ) {
				return '';
			}
			return 'name:' . $name_key . '|size:' . (string) $size_value;
		};

		$final_rows = array_merge( $existing_rows, $quote_rows );
		$final_prints = array();
		$seen_print_signatures = array();
		foreach ( array_merge( $existing_prints, $prints ) as $print_entry ) {
			if ( ! is_array( $print_entry ) ) {
				continue;
			}
			$signature = $normalize_print_signature( $print_entry );
			if ( '' !== $signature ) {
				if ( isset( $seen_print_signatures[ $signature ] ) ) {
					continue;
				}
				$seen_print_signatures[ $signature ] = true;
			}
			$final_prints[] = $print_entry;
		}
		$final_total = round( $existing_total + $total, 2 );

		update_post_meta( $quote_id, 'status', 'draft' );
		update_post_meta( $quote_id, 'currency', function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD' );
		update_post_meta( $quote_id, 'total', $final_total );
		update_post_meta( $quote_id, 'created_at', current_time( 'mysql' ) );
		update_post_meta( $quote_id, 'items_json', $final_rows );
		update_post_meta( $quote_id, 'screenprint_quote_rows_json', $final_rows );
		update_post_meta( $quote_id, 'screenprint_quote_prints_json', $final_prints );
		update_post_meta( $quote_id, 'screenprint_quote_context', array(
			'productId' => $product_id,
			'layoutId'  => $layout_id,
			'layoutTitle' => $layout_title,
			'selectedColor' => $selected_color,
			'selectedColorKey' => $selected_color_key,
		) );

		$this->log_user_activity( $user_id, sprintf( $is_update ? __( 'Screenprint quote updated: %s', 'threaddesk' ) : __( 'Screenprint quote created: %s', 'threaddesk' ), get_the_title( $quote_id ) ), 'quote' );

		wp_send_json_success( array(
			'quoteId'      => (int) $quote_id,
			'adminEditUrl' => admin_url( 'post.php?post=' . absint( $quote_id ) . '&action=edit' ),
			'isUpdate'     => $is_update ? 1 : 0,
			'message'      => $is_update ? __( 'Quote updated successfully.', 'threaddesk' ) : __( 'Quote added successfully.', 'threaddesk' ),
		) );
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

		$avatar_file  = isset( $_FILES['threaddesk_avatar'] ) && is_array( $_FILES['threaddesk_avatar'] ) ? $_FILES['threaddesk_avatar'] : null;
		$avatar_name  = isset( $avatar_file['name'] ) ? trim( (string) $avatar_file['name'] ) : '';
		$avatar_error = isset( $avatar_file['error'] ) ? (int) $avatar_file['error'] : UPLOAD_ERR_NO_FILE;
		$avatar_tmp   = isset( $avatar_file['tmp_name'] ) ? trim( (string) $avatar_file['tmp_name'] ) : '';
		if ( '' === $avatar_name || UPLOAD_ERR_OK !== $avatar_error || '' === $avatar_tmp || ! is_uploaded_file( $avatar_tmp ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Avatar upload failed.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload( $avatar_file, array( 'test_form' => false ) );

		if ( ! is_array( $upload ) || isset( $upload['error'] ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Avatar upload failed.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}

		$uploaded_file_path = isset( $upload['file'] ) ? trim( (string) $upload['file'] ) : '';
		if ( '' === $uploaded_file_path ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Avatar upload failed.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
			exit;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => isset( $upload['type'] ) ? (string) $upload['type'] : '',
				'post_title'     => sanitize_file_name( wp_basename( $uploaded_file_path ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$uploaded_file_path
		);

		if ( $attachment_id ) {
			$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded_file_path );
			wp_update_attachment_metadata( $attachment_id, $metadata );
			update_user_meta( get_current_user_id(), 'tta_threaddesk_avatar_id', $attachment_id );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'thread-desk' ) );
		exit;
	}


	private function get_designs_redirect_url() {
		return add_query_arg( 'td_section', 'designs', trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) ) );
	}

	private function get_layouts_redirect_url( $query_args = array() ) {
		$base = add_query_arg( 'td_section', 'layouts', trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) ) );
		return ! empty( $query_args ) && is_array( $query_args ) ? add_query_arg( $query_args, $base ) : $base;
	}

	private function get_request_owner_context() {
		if ( is_user_logged_in() ) {
			return array(
				'user_id'          => get_current_user_id(),
				'guest_token_hash' => '',
			);
		}

		$token = isset( $_COOKIE[ TTA_ThreadDesk_Guest_Token_Service::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ TTA_ThreadDesk_Guest_Token_Service::COOKIE_NAME ] ) ) : '';
		if ( '' === $token ) {
			$token = $this->guest_tokens->rotate_token();
		}

		return array(
			'user_id'          => 0,
			'guest_token_hash' => '' !== $token ? $this->guest_tokens->hash_token( $token ) : '',
		);
	}

	private function can_manage_owned_post( $post, $owner_context ) {
		if ( ! $post instanceof WP_Post || ! is_array( $owner_context ) ) {
			return false;
		}

		$user_id = isset( $owner_context['user_id'] ) ? (int) $owner_context['user_id'] : 0;
		if ( $user_id > 0 ) {
			return (int) $post->post_author === $user_id;
		}

		$expected_hash = isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '';
		$stored_hash   = (string) get_post_meta( (int) $post->ID, TTA_ThreadDesk_Guest_Token_Service::OWNER_META_KEY, true );

		return '' !== $expected_hash && hash_equals( $stored_hash, $expected_hash );
	}

	private function assign_post_owner_meta( $post_id, $owner_context ) {
		if ( ! is_array( $owner_context ) ) {
			return;
		}

		$user_id = isset( $owner_context['user_id'] ) ? (int) $owner_context['user_id'] : 0;
		if ( $user_id > 0 ) {
			delete_post_meta( $post_id, TTA_ThreadDesk_Guest_Token_Service::OWNER_META_KEY );
			return;
		}

		$guest_hash = isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '';
		if ( '' !== $guest_hash ) {
			update_post_meta( $post_id, TTA_ThreadDesk_Guest_Token_Service::OWNER_META_KEY, $guest_hash );
		}
	}


	private function get_user_design_storage( $user_id, $owner_context = array() ) {
		$uploads = wp_upload_dir();
		$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
		$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
		if ( '' === $basedir || '' === $baseurl ) {
			return null;
		}

		$folder = '';
		if ( $user_id > 0 ) {
			$user   = get_userdata( $user_id );
			$login  = $user ? (string) $user->user_login : 'user-' . (string) $user_id;
			$folder = sanitize_file_name( $login );
			if ( '' === $folder ) {
				$folder = 'user-' . (string) $user_id;
			}
		} else {
			$guest_hash = isset( $owner_context['guest_token_hash'] ) ? sanitize_key( (string) $owner_context['guest_token_hash'] ) : '';
			if ( '' === $guest_hash ) {
				$folder = 'guest';
			} else {
				$folder = 'guest-' . substr( $guest_hash, 0, 20 );
			}
		}

		$base_dir = trailingslashit( $basedir ) . 'ThreadDesk/Designs/' . $folder;
		$base_url = trailingslashit( $baseurl ) . 'ThreadDesk/Designs/' . rawurlencode( $folder );

		if ( ! wp_mkdir_p( $base_dir ) ) {
			return null;
		}

		return array(
			'dir' => $base_dir,
			'url' => $base_url,
		);
	}

	private function persist_design_svg_file( $design_id, $svg_markup, $base_name, $storage ) {
		if ( ! is_array( $storage ) || empty( $storage['dir'] ) || empty( $storage['url'] ) ) {
			return;
		}
		$svg_markup = is_string( $svg_markup ) ? trim( $svg_markup ) : '';
		if ( '' === $svg_markup || false === stripos( $svg_markup, '<svg' ) ) {
			return;
		}

		$base_name = sanitize_file_name( $base_name );
		if ( '' === $base_name ) {
			$base_name = 'design-' . (string) $design_id;
		}
		$svg_filename = $base_name . '.svg';
		$svg_path     = trailingslashit( $storage['dir'] ) . $svg_filename;
		$svg_url      = trailingslashit( $storage['url'] ) . rawurlencode( $svg_filename );

		$bytes = file_put_contents( $svg_path, $svg_markup );
		if ( false === $bytes ) {
			return;
		}

		update_post_meta( $design_id, 'design_svg_file_path', $svg_path );
		update_post_meta( $design_id, 'design_svg_file_url', esc_url_raw( $svg_url ) );
		update_post_meta( $design_id, 'design_svg_file_name', $svg_filename );
	}

	private function persist_design_mockup_png_file( $design_id, $png_data_url, $base_name, $storage ) {
		if ( ! is_array( $storage ) || empty( $storage['dir'] ) || empty( $storage['url'] ) ) {
			return;
		}
		$png_data_url = is_string( $png_data_url ) ? trim( $png_data_url ) : '';
		if ( '' === $png_data_url || 0 !== strpos( $png_data_url, 'data:image/png;base64,' ) ) {
			return;
		}
		$binary = base64_decode( substr( $png_data_url, 22 ), true );
		if ( false === $binary || '' === $binary ) {
			return;
		}
		$base_name = sanitize_file_name( $base_name );
		if ( '' === $base_name ) {
			$base_name = 'design-' . (string) $design_id;
		}
		$png_filename = $base_name . '-mockup.png';
		$png_path     = trailingslashit( $storage['dir'] ) . $png_filename;
		$png_url      = trailingslashit( $storage['url'] ) . rawurlencode( $png_filename );
		$bytes = file_put_contents( $png_path, $binary );
		if ( false === $bytes ) {
			return;
		}
		update_post_meta( $design_id, 'design_mockup_file_path', $png_path );
		update_post_meta( $design_id, 'design_mockup_file_url', esc_url_raw( $png_url ) );
		update_post_meta( $design_id, 'design_mockup_file_name', $png_filename );
	}

	private function rename_design_file( $current_path, $current_url, $target_base_name, $extension, $storage ) {
		$current_path = is_string( $current_path ) ? trim( $current_path ) : '';
		$current_url  = is_string( $current_url ) ? trim( $current_url ) : '';
		$extension    = sanitize_file_name( ltrim( (string) $extension, '.' ) );
		$target_base_name = sanitize_file_name( (string) $target_base_name );

		if ( '' === $current_path || '' === $extension || '' === $target_base_name || ! is_array( $storage ) || empty( $storage['dir'] ) || empty( $storage['url'] ) ) {
			return array(
				'path' => $current_path,
				'url'  => $current_url,
				'name' => $current_path ? sanitize_file_name( wp_basename( $current_path ) ) : '',
			);
		}

		if ( ! file_exists( $current_path ) || ! is_file( $current_path ) ) {
			return array(
				'path' => $current_path,
				'url'  => $current_url,
				'name' => $current_path ? sanitize_file_name( wp_basename( $current_path ) ) : '',
			);
		}

		$target_file_name = wp_unique_filename( $storage['dir'], $target_base_name . '.' . $extension );
		$target_path      = trailingslashit( $storage['dir'] ) . $target_file_name;
		if ( $target_path === $current_path ) {
			return array(
				'path' => $current_path,
				'url'  => trailingslashit( $storage['url'] ) . rawurlencode( $target_file_name ),
				'name' => $target_file_name,
			);
		}

		$renamed = @rename( $current_path, $target_path );
		if ( ! $renamed ) {
			$renamed = @copy( $current_path, $target_path );
			if ( $renamed ) {
				@unlink( $current_path );
			}
		}

		if ( ! $renamed ) {
			return array(
				'path' => $current_path,
				'url'  => $current_url,
				'name' => sanitize_file_name( wp_basename( $current_path ) ),
			);
		}

		return array(
			'path' => $target_path,
			'url'  => trailingslashit( $storage['url'] ) . rawurlencode( $target_file_name ),
			'name' => $target_file_name,
		);
	}

	private function maybe_delete_design_file( $path ) {
		$path = is_string( $path ) ? trim( $path ) : '';
		if ( '' === $path || ! file_exists( $path ) || ! is_file( $path ) ) {
			return;
		}
		@unlink( $path );
	}

	private function maybe_delete_design_files_for_post( $design_id ) {
		$paths = array(
			(string) get_post_meta( $design_id, 'design_original_file_path', true ),
			(string) get_post_meta( $design_id, 'design_svg_file_path', true ),
			(string) get_post_meta( $design_id, 'design_mockup_file_path', true ),
		);

		foreach ( $paths as $path ) {
			$this->maybe_delete_design_file( $path );
		}
	}


	public function handle_save_layout() {
		check_admin_referer( 'tta_threaddesk_save_layout' );

		$owner_context   = $this->get_request_owner_context();
		$current_user_id = (int) $owner_context['user_id'];
		$layout_id_input = isset( $_POST['threaddesk_layout_id'] ) ? absint( $_POST['threaddesk_layout_id'] ) : 0;
		$category_slug   = isset( $_POST['threaddesk_layout_category'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_layout_category'] ) ) : '';
		$category_id     = isset( $_POST['threaddesk_layout_category_id'] ) ? absint( $_POST['threaddesk_layout_category_id'] ) : 0;
		$payload_raw     = isset( $_POST['threaddesk_layout_payload'] ) ? wp_unslash( $_POST['threaddesk_layout_payload'] ) : '';
		$payload         = json_decode( (string) $payload_raw, true );
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		$return_context = isset( $_POST['threaddesk_layout_return_context'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_layout_return_context'] ) ) : '';
		$return_url     = isset( $_POST['threaddesk_layout_return_url'] ) ? esc_url_raw( wp_unslash( $_POST['threaddesk_layout_return_url'] ) ) : '';
		$redirect_url   = $this->get_layouts_redirect_url();
		if ( 'screenprint_chooser' === $return_context ) {
			$candidate_url = '' !== $return_url ? $return_url : (string) wp_get_referer();
			if ( '' !== $candidate_url ) {
				$redirect_url = add_query_arg( 'td_screenprint_return', '1', remove_query_arg( 'td_screenprint_return', $candidate_url ) );
			}
		}

		$placements_by_angle = isset( $payload['placementsByAngle'] ) && is_array( $payload['placementsByAngle'] ) ? $payload['placementsByAngle'] : array();
		$has_any_placement   = false;
		$related_design_ids  = array();
		foreach ( $placements_by_angle as $angle => $placements ) {
			if ( ! is_array( $placements ) ) {
				continue;
			}
			foreach ( $placements as $placement_key => $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$raw_url = isset( $entry['url'] ) ? (string) $entry['url'] : '';
				$url     = esc_url_raw( $raw_url );
				if ( '' === $url && preg_match( '#^data:image\/(png|jpe?g|webp);base64,#i', $raw_url ) ) {
					$url = $raw_url;
				}
				if ( '' !== $url ) {
					$has_any_placement = true;
				}
				$design_id = isset( $entry['designId'] ) ? absint( $entry['designId'] ) : 0;
				if ( $design_id > 0 ) {
					$related_design_ids[] = $design_id;
				}
				$placements_by_angle[ $angle ][ $placement_key ] = array(
					'url'            => $url,
					'baseUrl'        => isset( $entry['baseUrl'] ) ? esc_url_raw( (string) $entry['baseUrl'] ) : '',
					'designId'       => $design_id,
					'designName'     => isset( $entry['designName'] ) ? sanitize_text_field( (string) $entry['designName'] ) : '',
					'placementLabel' => isset( $entry['placementLabel'] ) ? sanitize_text_field( (string) $entry['placementLabel'] ) : '',
					'placementKey'   => sanitize_key( (string) $placement_key ),
					'angle'          => sanitize_key( (string) $angle ),
					'top'            => isset( $entry['top'] ) ? (float) $entry['top'] : 0,
					'left'           => isset( $entry['left'] ) ? (float) $entry['left'] : 0,
					'width'          => isset( $entry['width'] ) ? (float) $entry['width'] : 0,
					'baseWidth'      => isset( $entry['baseWidth'] ) ? (float) $entry['baseWidth'] : 0,
					'sliderValue'    => isset( $entry['sliderValue'] ) ? (float) $entry['sliderValue'] : 100,
					'designRatio'    => isset( $entry['designRatio'] ) ? (float) $entry['designRatio'] : 1,
					'paletteBase'    => isset( $entry['paletteBase'] ) && is_array( $entry['paletteBase'] ) ? array_map( 'sanitize_text_field', $entry['paletteBase'] ) : array(),
					'paletteCurrent' => isset( $entry['paletteCurrent'] ) && is_array( $entry['paletteCurrent'] ) ? array_map( 'sanitize_text_field', $entry['paletteCurrent'] ) : array(),
				);
			}
		}

		if ( ! $has_any_placement ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Please save at least one placement before saving the layout.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$category_label = $category_slug;
		if ( $category_id > 0 ) {
			$term = get_term( $category_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$category_label = $term->name;
			}
		}
		if ( '' === $category_label ) {
			$category_label = __( 'Layout', 'threaddesk' );
		}

		$default_layout_design_name = '';
		foreach ( $placements_by_angle as $angle_entries ) {
			if ( ! is_array( $angle_entries ) ) {
				continue;
			}
			foreach ( $angle_entries as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$entry_design_name = isset( $entry['designName'] ) ? trim( (string) $entry['designName'] ) : '';
				if ( '' === $entry_design_name ) {
					$entry_url = isset( $entry['url'] ) ? trim( (string) $entry['url'] ) : '';
					if ( '' !== $entry_url ) {
						$path = wp_parse_url( $entry_url, PHP_URL_PATH );
						if ( is_string( $path ) && '' !== $path ) {
							$entry_design_name = pathinfo( basename( $path ), PATHINFO_FILENAME );
						}
					}
				}
				$entry_design_name = sanitize_text_field( (string) $entry_design_name );
				if ( '' !== $entry_design_name ) {
					$default_layout_design_name = $entry_design_name;
					break 2;
				}
			}
		}

		$layout_title = sprintf( __( '%1$s Layout %2$s', 'threaddesk' ), $category_label, date_i18n( 'Y-m-d H:i' ) );
		if ( '' !== $default_layout_design_name ) {
			$layout_title = trim( $default_layout_design_name . ' ' . $category_label );
		}
		$layout_title = sanitize_text_field( $layout_title );
		if ( '' === $layout_title ) {
			$layout_title = __( 'Layout', 'threaddesk' );
		}

		$layout_id    = 0;
		$is_update    = false;

		if ( $layout_id_input > 0 ) {
			$existing_layout = get_post( $layout_id_input );
			if ( $existing_layout && 'tta_layout' === $existing_layout->post_type && $this->can_manage_owned_post( $existing_layout, $owner_context ) ) {
				$layout_id = (int) $existing_layout->ID;
				$is_update = true;
			}
		}

		if ( $layout_id <= 0 ) {
			$layout_insert_data = array(
				'post_type'   => 'tta_layout',
				'post_status' => ( 0 === (int) $owner_context['user_id'] ) ? 'publish' : 'private',
				'post_title'  => $layout_title,
				'post_author' => $current_user_id,
			);
			if ( 0 === (int) $owner_context['user_id'] ) {
				$layout_insert_data['post_author'] = 0;
			}
			$layout_id = wp_insert_post( $layout_insert_data );
		}

			if ( ! $layout_id || is_wp_error( $layout_id ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Unable to save layout right now.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$this->assign_post_owner_meta( $layout_id, $owner_context );

		$payload['placementsByAngle'] = $placements_by_angle;
		$payload['category']          = $category_slug;
		$payload['categoryId']        = $category_id;
		$payload['relatedDesignIds']  = array_values( array_unique( array_filter( array_map( 'absint', $related_design_ids ) ) ) );

		update_post_meta( $layout_id, 'layout_category', $category_slug );
		update_post_meta( $layout_id, 'layout_category_id', $category_id );
		update_post_meta( $layout_id, 'layout_payload', wp_json_encode( $payload ) );
		update_post_meta( $layout_id, 'layout_placements', wp_json_encode( $placements_by_angle ) );
		update_post_meta( $layout_id, 'layout_related_design_ids', wp_json_encode( $payload['relatedDesignIds'] ) );
		if ( ! $is_update ) {
			update_post_meta( $layout_id, 'created_at', current_time( 'mysql' ) );
		}
		if ( 0 === (int) $owner_context['user_id'] ) {
			update_post_meta( $layout_id, '_tta_guest_token', isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '' );
		} else {
			delete_post_meta( $layout_id, '_tta_guest_token' );
		}

		$this->log_user_activity( $current_user_id, $is_update ? sprintf( __( 'Layout updated: %s', 'threaddesk' ), get_the_title( $layout_id ) ) : sprintf( __( 'Layout created: %s', 'threaddesk' ), get_the_title( $layout_id ) ), 'layout' );
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $is_update ? __( 'Layout updated successfully.', 'threaddesk' ) : __( 'Layout saved successfully.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function handle_save_design() {
		check_admin_referer( 'tta_threaddesk_save_design' );

		$owner_context    = $this->get_request_owner_context();
		$current_user_id  = (int) $owner_context['user_id'];
		$existing_design_id = isset( $_POST['threaddesk_design_id'] ) ? absint( $_POST['threaddesk_design_id'] ) : 0;
		$design_id         = 0;
		$upload            = null;
		$file_name         = '';
		$title_input       = isset( $_POST['threaddesk_design_title'] ) ? sanitize_text_field( wp_unslash( $_POST['threaddesk_design_title'] ) ) : '';
		$storage           = $this->get_user_design_storage( $current_user_id, $owner_context );
		$return_context   = isset( $_POST['threaddesk_design_return_context'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_design_return_context'] ) ) : '';
		$return_category  = isset( $_POST['threaddesk_design_return_layout_category'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_design_return_layout_category'] ) ) : '';
		$return_placement = isset( $_POST['threaddesk_design_return_layout_placement'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_design_return_layout_placement'] ) ) : '';
		$return_url_raw   = isset( $_POST['threaddesk_design_return_url'] ) ? esc_url_raw( wp_unslash( $_POST['threaddesk_design_return_url'] ) ) : '';
		$return_url       = '';
		if ( '' !== $return_url_raw ) {
			$return_url = wp_validate_redirect( $return_url_raw, '' );
		}
		$redirect_url = $this->get_designs_redirect_url();
		if ( '' !== $return_url ) {
			$redirect_url = $return_url;
		} elseif ( 'layout_viewer' === $return_context ) {
			$query_args = array(
				'td_layout_return' => '1',
			);
			if ( '' !== $return_category ) {
				$query_args['td_layout_category'] = $return_category;
			}
			if ( '' !== $return_placement ) {
				$query_args['td_layout_placement'] = $return_placement;
			}
			$redirect_url = $this->get_layouts_redirect_url( $query_args );
		}

		if ( ! $storage ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Unable to access your design storage directory.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( $existing_design_id > 0 ) {
			$existing = get_post( $existing_design_id );
			if ( $existing && 'tta_design' === $existing->post_type && $this->can_manage_owned_post( $existing, $owner_context ) ) {
				$design_id = $existing_design_id;
				$file_name = (string) get_post_meta( $design_id, 'design_file_name', true );
			}
		}

		$old_original_path = $design_id ? (string) get_post_meta( $design_id, 'design_original_file_path', true ) : '';
		$old_svg_path      = $design_id ? (string) get_post_meta( $design_id, 'design_svg_file_path', true ) : '';
		$old_mockup_path   = $design_id ? (string) get_post_meta( $design_id, 'design_mockup_file_path', true ) : '';
		$old_design_title  = $design_id ? (string) get_the_title( $design_id ) : '';

		$design_file        = isset( $_FILES['threaddesk_design_file'] ) && is_array( $_FILES['threaddesk_design_file'] ) ? $_FILES['threaddesk_design_file'] : null;
		$design_upload_name = isset( $design_file['name'] ) ? trim( (string) $design_file['name'] ) : '';
		if ( '' !== $design_upload_name ) {
			$design_upload_error = isset( $design_file['error'] ) ? (int) $design_file['error'] : UPLOAD_ERR_NO_FILE;
			$design_upload_tmp   = isset( $design_file['tmp_name'] ) ? trim( (string) $design_file['tmp_name'] ) : '';
			if ( UPLOAD_ERR_OK !== $design_upload_error || '' === $design_upload_tmp || ! is_uploaded_file( $design_upload_tmp ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Design upload failed. Please try again.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			$upload = wp_handle_upload( $design_file, array( 'test_form' => false ) );
			if ( ! is_array( $upload ) || isset( $upload['error'] ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Design upload failed. Please try again.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}

			$uploaded_file_path = isset( $upload['file'] ) ? trim( (string) $upload['file'] ) : '';
			if ( '' === $uploaded_file_path ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Design upload failed. Please try again.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}

			$file_name = sanitize_file_name( wp_basename( $uploaded_file_path ) );
		}

		if ( 0 === $design_id ) {
			if ( '' === $file_name ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Please choose a design file before saving.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}

			$title = sanitize_text_field( preg_replace( '/\.[^.]+$/', '', $file_name ) );
			if ( '' === $title ) {
				$title = __( 'Design', 'threaddesk' );
			}

			$design_insert_data = array(
				'post_type'   => 'tta_design',
				'post_status' => 'private',
				'post_title'  => $title,
				'post_author' => $current_user_id,
			);
			if ( 0 === (int) $owner_context['user_id'] ) {
				$design_insert_data['post_author'] = 0;
			}
			$design_id = wp_insert_post( $design_insert_data );

			if ( ! $design_id || is_wp_error( $design_id ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Unable to save design right now.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}

			update_post_meta( $design_id, 'design_status', 'pending' );
		}

		$this->assign_post_owner_meta( $design_id, $owner_context );

		$palette_raw = isset( $_POST['threaddesk_design_palette'] ) ? wp_unslash( $_POST['threaddesk_design_palette'] ) : '[]';
		$palette     = json_decode( $palette_raw, true );
		$palette     = is_array( $palette ) ? $palette : array();
		$palette     = array_values(
			array_filter(
				array_map(
					function ( $color ) {
						$color = strtoupper( sanitize_text_field( (string) $color ) );
						if ( 'TRANSPARENT' === $color ) {
							return 'transparent';
						}
						return preg_match( '/^#[0-9A-F]{6}$/', $color ) ? $color : '';
					},
					$palette
				)
			)
		);

		$settings_raw = isset( $_POST['threaddesk_design_analysis_settings'] ) ? wp_unslash( $_POST['threaddesk_design_analysis_settings'] ) : '{}';
		$settings     = json_decode( $settings_raw, true );
		$settings     = is_array( $settings ) ? $settings : array();
		$maximum_color_count = isset( $settings['maximumColorCount'] ) ? (int) $settings['maximumColorCount'] : 4;
		$maximum_color_count = max( 1, min( 8, $maximum_color_count ) );
		$settings_clean = array(
			'minimumPercent'      => isset( $settings['minimumPercent'] ) ? (float) $settings['minimumPercent'] : 0.5,
			'mergeThreshold'      => isset( $settings['mergeThreshold'] ) ? (int) $settings['mergeThreshold'] : 22,
			'maximumColorCount'   => $maximum_color_count,
			'MS_scans'            => $maximum_color_count,
			'potraceTurdsize'     => isset( $settings['potraceTurdsize'] ) ? (int) $settings['potraceTurdsize'] : 2,
			'potraceAlphamax'     => isset( $settings['potraceAlphamax'] ) ? (float) $settings['potraceAlphamax'] : 1.0,
			'potraceOpticurve'    => isset( $settings['potraceOpticurve'] ) ? (bool) $settings['potraceOpticurve'] : true,
			'potraceOpttolerance' => isset( $settings['potraceOpttolerance'] ) ? (float) $settings['potraceOpttolerance'] : 0.2,
			'multiScanSmooth'     => ! isset( $settings['multiScanSmooth'] ) || (bool) $settings['multiScanSmooth'],
			'multiScanStack'      => ! isset( $settings['multiScanStack'] ) || (bool) $settings['multiScanStack'],
		);

		$color_count = isset( $_POST['threaddesk_design_color_count'] ) ? absint( $_POST['threaddesk_design_color_count'] ) : count( $palette );
		$svg_markup  = isset( $_POST['threaddesk_design_svg_markup'] ) ? wp_unslash( $_POST['threaddesk_design_svg_markup'] ) : '';
		$mockup_png_data = isset( $_POST['threaddesk_design_mockup_png_data'] ) ? wp_unslash( $_POST['threaddesk_design_mockup_png_data'] ) : '';
		if ( $upload && isset( $upload['file'] ) && '' !== trim( (string) $upload['file'] ) ) {
			$incoming_file_path = trim( (string) $upload['file'] );
			$incoming_name      = sanitize_file_name( wp_basename( $incoming_file_path ) );
			$incoming_extension = strtolower( pathinfo( $incoming_name, PATHINFO_EXTENSION ) );
			$target_seed_name   = $incoming_name;
			$target_name        = wp_unique_filename( $storage['dir'], $target_seed_name );
			$target_path        = trailingslashit( $storage['dir'] ) . $target_name;
			$target_url         = trailingslashit( $storage['url'] ) . rawurlencode( $target_name );
			$move_succeeded     = @rename( $incoming_file_path, $target_path );

			if ( ! $move_succeeded ) {
				$move_succeeded = @copy( $incoming_file_path, $target_path );
				if ( $move_succeeded ) {
					@unlink( $incoming_file_path );
				}
			}

			if ( ! $move_succeeded ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Unable to finalize design upload.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}

			$file_name = $target_name;
			update_post_meta( $design_id, 'design_preview_url', esc_url_raw( $target_url ) );
			update_post_meta( $design_id, 'design_original_file_path', $target_path );
			update_post_meta( $design_id, 'design_original_file_url', esc_url_raw( $target_url ) );

			$updated_title = '' !== $title_input ? $title_input : sanitize_text_field( preg_replace( '/\.[^.]+$/', '', $file_name ) );
			if ( '' !== $updated_title ) {
				wp_update_post( array( 'ID' => $design_id, 'post_title' => $updated_title ) );
			}

			if ( $old_original_path && $old_original_path !== $target_path ) {
				$this->maybe_delete_design_file( $old_original_path );
			}
		}

		if ( ! $upload && '' !== $title_input ) {
			wp_update_post( array( 'ID' => $design_id, 'post_title' => $title_input ) );
			$current_original_path = (string) get_post_meta( $design_id, 'design_original_file_path', true );
			$current_original_url  = (string) get_post_meta( $design_id, 'design_original_file_url', true );
			$current_svg_path      = (string) get_post_meta( $design_id, 'design_svg_file_path', true );
			$current_svg_url       = (string) get_post_meta( $design_id, 'design_svg_file_url', true );
			$original_extension    = strtolower( pathinfo( (string) $file_name, PATHINFO_EXTENSION ) );
			$rename_base           = sanitize_file_name( $title_input );

			if ( '' !== $rename_base && '' !== $original_extension ) {
				$renamed_original = $this->rename_design_file( $current_original_path, $current_original_url, $rename_base, $original_extension, $storage );
				if ( ! empty( $renamed_original['name'] ) ) {
					$file_name = (string) $renamed_original['name'];
					update_post_meta( $design_id, 'design_preview_url', esc_url_raw( (string) $renamed_original['url'] ) );
					update_post_meta( $design_id, 'design_original_file_path', (string) $renamed_original['path'] );
					update_post_meta( $design_id, 'design_original_file_url', esc_url_raw( (string) $renamed_original['url'] ) );
				}

				$renamed_svg = $this->rename_design_file( $current_svg_path, $current_svg_url, $rename_base, 'svg', $storage );
				if ( ! empty( $renamed_svg['name'] ) ) {
					update_post_meta( $design_id, 'design_svg_file_path', (string) $renamed_svg['path'] );
					update_post_meta( $design_id, 'design_svg_file_url', esc_url_raw( (string) $renamed_svg['url'] ) );
					update_post_meta( $design_id, 'design_svg_file_name', (string) $renamed_svg['name'] );
				}

				$renamed_mockup = $this->rename_design_file( $current_mockup_path, $current_mockup_url, $rename_base . '-mockup', 'png', $storage );
				if ( ! empty( $renamed_mockup['name'] ) ) {
					update_post_meta( $design_id, 'design_mockup_file_path', (string) $renamed_mockup['path'] );
					update_post_meta( $design_id, 'design_mockup_file_url', esc_url_raw( (string) $renamed_mockup['url'] ) );
					update_post_meta( $design_id, 'design_mockup_file_name', (string) $renamed_mockup['name'] );
				}
			}
		}

		if ( '' !== $file_name ) {
			update_post_meta( $design_id, 'design_file_name', $file_name );
			if ( '' === $title_input ) {
				$default_title = sanitize_text_field( preg_replace( '/\.[^.]+$/', '', (string) $file_name ) );
				if ( '' !== $default_title ) {
					wp_update_post(
						array(
							'ID'         => $design_id,
							'post_title' => $default_title,
						)
					);
				}
			}
		}

		$svg_base_name = sanitize_file_name( preg_replace( '/\.[^.]+$/', '', (string) $file_name ) );
		if ( '' === $svg_base_name ) {
			$svg_base_name = sanitize_file_name( (string) get_the_title( $design_id ) );
		}
		if ( '' === $svg_base_name ) {
			$svg_base_name = 'design-' . (string) $design_id;
		}
		$this->persist_design_svg_file( $design_id, $svg_markup, $svg_base_name, $storage );
		$this->persist_design_mockup_png_file( $design_id, $mockup_png_data, $svg_base_name, $storage );

		$current_svg_path = (string) get_post_meta( $design_id, 'design_svg_file_path', true );
		if ( $old_svg_path && $current_svg_path && $old_svg_path !== $current_svg_path ) {
			$this->maybe_delete_design_file( $old_svg_path );
		}
		$current_mockup_path = (string) get_post_meta( $design_id, 'design_mockup_file_path', true );
		if ( $old_mockup_path && $current_mockup_path && $old_mockup_path !== $current_mockup_path ) {
			$this->maybe_delete_design_file( $old_mockup_path );
		}

		update_post_meta( $design_id, 'design_palette', wp_json_encode( $palette ) );
		update_post_meta( $design_id, 'design_color_count', $color_count );
		update_post_meta( $design_id, 'design_analysis_settings', wp_json_encode( $settings_clean ) );
		update_post_meta( $design_id, 'created_at', current_time( 'mysql' ) );
		if ( 0 === (int) $owner_context['user_id'] ) {
			update_post_meta( $design_id, '_tta_guest_token', isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '' );
		} else {
			delete_post_meta( $design_id, '_tta_guest_token' );
		}

		$current_design_title = (string) get_the_title( $design_id );
		if ( $design_id > 0 && '' !== $current_design_title && $current_design_title !== $old_design_title ) {
			$this->sync_design_references_after_title_change( $design_id, $old_design_title, $current_design_title );
		}

		$this->log_user_activity( $current_user_id, $existing_design_id > 0 ? sprintf( __( 'Design updated: %s', 'threaddesk' ), get_the_title( $design_id ) ) : sprintf( __( 'Design uploaded: %s', 'threaddesk' ), get_the_title( $design_id ) ), 'design' );
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Design saved successfully.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function handle_rename_design() {
		check_admin_referer( 'tta_threaddesk_rename_design' );

		$owner_context = $this->get_request_owner_context();

		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;
		$title     = isset( $_POST['design_title'] ) ? sanitize_text_field( wp_unslash( $_POST['design_title'] ) ) : '';
		$title     = trim( (string) $title );
		$design    = get_post( $design_id );
		$old_title = $design instanceof WP_Post ? (string) $design->post_title : '';

		if ( ! $design || 'tta_design' !== $design->post_type || ! $this->can_manage_owned_post( $design, $owner_context ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Invalid design.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_designs_redirect_url() );
			exit;
		}

		if ( '' === $title ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Please enter a design title.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_designs_redirect_url() );
			exit;
		}

		$storage = $this->get_user_design_storage( (int) $design->post_author, $owner_context );
		if ( ! $storage ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Unable to access your design storage directory.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_designs_redirect_url() );
			exit;
		}

		wp_update_post( array( 'ID' => $design_id, 'post_title' => $title ) );
		if ( '' !== $old_title && $old_title !== $title ) {
			$this->sync_design_references_after_title_change( $design_id, $old_title, $title );
		}
		$this->log_user_activity( (int) $design->post_author, sprintf( __( 'Design renamed: %s', 'threaddesk' ), $title ), 'design' );
		$current_file_name     = (string) get_post_meta( $design_id, 'design_file_name', true );
		$current_original_path = (string) get_post_meta( $design_id, 'design_original_file_path', true );
		$current_original_url  = (string) get_post_meta( $design_id, 'design_original_file_url', true );
		$current_svg_path      = (string) get_post_meta( $design_id, 'design_svg_file_path', true );
		$current_svg_url       = (string) get_post_meta( $design_id, 'design_svg_file_url', true );
		$current_mockup_path   = (string) get_post_meta( $design_id, 'design_mockup_file_path', true );
		$current_mockup_url    = (string) get_post_meta( $design_id, 'design_mockup_file_url', true );
		$original_extension    = strtolower( pathinfo( $current_file_name, PATHINFO_EXTENSION ) );
		$rename_base           = sanitize_file_name( $title );

		if ( '' !== $rename_base && '' !== $original_extension ) {
			$renamed_original = $this->rename_design_file( $current_original_path, $current_original_url, $rename_base, $original_extension, $storage );
			if ( ! empty( $renamed_original['name'] ) ) {
				update_post_meta( $design_id, 'design_file_name', (string) $renamed_original['name'] );
				update_post_meta( $design_id, 'design_preview_url', esc_url_raw( (string) $renamed_original['url'] ) );
				update_post_meta( $design_id, 'design_original_file_path', (string) $renamed_original['path'] );
				update_post_meta( $design_id, 'design_original_file_url', esc_url_raw( (string) $renamed_original['url'] ) );
			}

			$renamed_svg = $this->rename_design_file( $current_svg_path, $current_svg_url, $rename_base, 'svg', $storage );
			if ( ! empty( $renamed_svg['name'] ) ) {
				update_post_meta( $design_id, 'design_svg_file_path', (string) $renamed_svg['path'] );
				update_post_meta( $design_id, 'design_svg_file_url', esc_url_raw( (string) $renamed_svg['url'] ) );
				update_post_meta( $design_id, 'design_svg_file_name', (string) $renamed_svg['name'] );
			}

			$renamed_mockup = $this->rename_design_file( $current_mockup_path, $current_mockup_url, $rename_base . '-mockup', 'png', $storage );
			if ( ! empty( $renamed_mockup['name'] ) ) {
				update_post_meta( $design_id, 'design_mockup_file_path', (string) $renamed_mockup['path'] );
				update_post_meta( $design_id, 'design_mockup_file_url', esc_url_raw( (string) $renamed_mockup['url'] ) );
				update_post_meta( $design_id, 'design_mockup_file_name', (string) $renamed_mockup['name'] );
			}
		}

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Design title updated.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( $this->get_designs_redirect_url() );
		exit;
	}

	private function sync_design_references_after_title_change( $design_id, $old_title, $new_title ) {
		$design_id = absint( $design_id );
		$new_title = sanitize_text_field( (string) $new_title );
		$old_title = sanitize_text_field( (string) $old_title );
		if ( $design_id <= 0 || '' === $new_title || $new_title === $old_title ) {
			return;
		}

		$design_urls = array(
			'mockup'  => esc_url_raw( (string) get_post_meta( $design_id, 'design_mockup_file_url', true ) ),
			'preview' => esc_url_raw( (string) get_post_meta( $design_id, 'design_preview_url', true ) ),
			'svg'     => esc_url_raw( (string) get_post_meta( $design_id, 'design_svg_file_url', true ) ),
		);

		$related_layouts  = $this->find_related_posts_by_id_in_meta( $design_id, 'tta_layout' );
		$related_quotes   = $this->find_related_posts_by_id_in_meta( $design_id, 'tta_quote' );
		$related_invoices = $this->find_related_posts_by_id_in_meta( $design_id, 'shop_order' );
		$related_posts    = array_merge( $related_layouts, $related_quotes, $related_invoices );
		$related_posts    = array_values( array_filter( $related_posts, function( $post ) { return $post instanceof WP_Post; } ) );

		$processed_post_ids = array();
		foreach ( $related_posts as $related_post ) {
			$post_id = (int) $related_post->ID;
			if ( $post_id <= 0 || in_array( $post_id, $processed_post_ids, true ) ) {
				continue;
			}
			$processed_post_ids[] = $post_id;
			$this->update_design_references_in_post_meta( $post_id, $design_id, $old_title, $new_title, $design_urls );
		}
	}

	private function update_design_references_in_post_meta( $post_id, $design_id, $old_title, $new_title, $design_urls ) {
		global $wpdb;
		$post_id = absint( $post_id );
		$design_id = absint( $design_id );
		if ( $post_id <= 0 || $design_id <= 0 ) {
			return;
		}
		$meta_rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id ) );
		if ( empty( $meta_rows ) ) {
			return;
		}
		foreach ( $meta_rows as $row ) {
			$meta_id = isset( $row->meta_id ) ? absint( $row->meta_id ) : 0;
			if ( $meta_id <= 0 ) {
				continue;
			}
			$raw_value = isset( $row->meta_value ) ? (string) $row->meta_value : '';
			$value = maybe_unserialize( $raw_value );
			$changed = $this->update_design_reference_in_value( $value, $design_id, $old_title, $new_title, $design_urls );
			if ( ! $changed ) {
				continue;
			}
			update_metadata_by_mid( 'post', $meta_id, $value );
		}
	}

	private function update_design_reference_in_value( &$value, $design_id, $old_title, $new_title, $design_urls ) {
		$changed = false;
		if ( is_array( $value ) ) {
			$entry_design_id = 0;
			if ( isset( $value['designId'] ) ) {
				$entry_design_id = absint( $value['designId'] );
			} elseif ( isset( $value['design_id'] ) ) {
				$entry_design_id = absint( $value['design_id'] );
			}
			if ( $entry_design_id === (int) $design_id ) {
				if ( isset( $value['designName'] ) && (string) $value['designName'] !== $new_title ) {
					$value['designName'] = $new_title;
					$changed = true;
				}
				if ( isset( $value['design_name'] ) && (string) $value['design_name'] !== $new_title ) {
					$value['design_name'] = $new_title;
					$changed = true;
				}
				$preferred_raster = '' !== (string) $design_urls['mockup'] ? (string) $design_urls['mockup'] : ( '' !== (string) $design_urls['preview'] ? (string) $design_urls['preview'] : (string) $design_urls['svg'] );
				$preferred_vector = '' !== (string) $design_urls['svg'] ? (string) $design_urls['svg'] : ( '' !== (string) $design_urls['preview'] ? (string) $design_urls['preview'] : (string) $design_urls['mockup'] );
				$url_key_map = array(
					'url' => $preferred_raster,
					'baseUrl' => $preferred_raster,
					'preview' => $preferred_raster,
					'previewUrl' => $preferred_raster,
					'mockupUrl' => '' !== (string) $design_urls['mockup'] ? (string) $design_urls['mockup'] : $preferred_raster,
					'designUrl' => $preferred_vector,
					'sourceUrl' => $preferred_vector,
					'svgUrl' => $preferred_vector,
				);
				foreach ( $url_key_map as $url_key => $target_url ) {
					if ( '' === $target_url || ! isset( $value[ $url_key ] ) ) {
						continue;
					}
					if ( (string) $value[ $url_key ] !== $target_url ) {
						$value[ $url_key ] = $target_url;
						$changed = true;
					}
				}
				if ( isset( $value['placementKey'] ) ) {
					$current_key = (string) $value['placementKey'];
					if ( '' !== $old_title && ( $current_key === $old_title || sanitize_key( $current_key ) === sanitize_key( $old_title ) ) ) {
						$value['placementKey'] = sanitize_key( $new_title );
						$changed = true;
					}
				}
			}
			foreach ( $value as &$item ) {
				if ( $this->update_design_reference_in_value( $item, $design_id, $old_title, $new_title, $design_urls ) ) {
					$changed = true;
				}
			}
			unset( $item );
			return $changed;
		}
		if ( is_object( $value ) ) {
			$array_value = (array) $value;
			if ( $this->update_design_reference_in_value( $array_value, $design_id, $old_title, $new_title, $design_urls ) ) {
				$value = (object) $array_value;
				return true;
			}
			return false;
		}
		if ( is_string( $value ) ) {
			$trimmed = trim( $value );
			if ( '' === $trimmed || ( '{' !== substr( $trimmed, 0, 1 ) && '[' !== substr( $trimmed, 0, 1 ) ) ) {
				return false;
			}
			$decoded = json_decode( $trimmed, true );
			if ( ! is_array( $decoded ) ) {
				return false;
			}
			if ( $this->update_design_reference_in_value( $decoded, $design_id, $old_title, $new_title, $design_urls ) ) {
				$value = wp_json_encode( $decoded );
				return true;
			}
			return false;
		}
		return false;
	}


	public function handle_delete_design() {
		check_admin_referer( 'tta_threaddesk_delete_design' );
		$owner_context = $this->get_request_owner_context();
		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;
		$design    = get_post( $design_id );
		if ( ! $design || 'tta_design' !== $design->post_type || ! $this->can_manage_owned_post( $design, $owner_context ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Invalid design.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_designs_redirect_url() );
			exit;
		}

		$this->maybe_delete_design_files_for_post( $design_id );
		$this->log_user_activity( (int) $design->post_author, sprintf( __( 'Design deleted: %s', 'threaddesk' ), $design->post_title ), 'design' );
		wp_delete_post( $design_id, true );
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Design deleted.', 'threaddesk' ), 'success' );
		}
		wp_safe_redirect( $this->get_designs_redirect_url() );
		exit;
	}

	public function handle_rename_layout() {
		check_admin_referer( 'tta_threaddesk_rename_layout' );

		$owner_context = $this->get_request_owner_context();

		$layout_id = isset( $_POST['layout_id'] ) ? absint( $_POST['layout_id'] ) : 0;
		$title     = isset( $_POST['layout_title'] ) ? sanitize_text_field( wp_unslash( $_POST['layout_title'] ) ) : '';
		$title     = trim( (string) $title );
		$layout    = get_post( $layout_id );

		if ( ! $layout || 'tta_layout' !== $layout->post_type || ! $this->can_manage_owned_post( $layout, $owner_context ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Invalid layout.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_layouts_redirect_url() );
			exit;
		}

		if ( '' === $title ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Please enter a placement layout name.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_layouts_redirect_url() );
			exit;
		}

		wp_update_post(
			array(
				'ID'         => $layout_id,
				'post_title' => $title,
			)
		);
		$this->log_user_activity( (int) $layout->post_author, sprintf( __( 'Layout renamed: %s', 'threaddesk' ), $title ), 'layout' );

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Placement layout name updated.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( $this->get_layouts_redirect_url() );
		exit;
	}

	public function handle_delete_layout() {
		check_admin_referer( 'tta_threaddesk_delete_layout' );

		$owner_context = $this->get_request_owner_context();

		$layout_id = isset( $_POST['layout_id'] ) ? absint( $_POST['layout_id'] ) : 0;
		$layout    = get_post( $layout_id );

		if ( ! $layout || 'tta_layout' !== $layout->post_type || ! $this->can_manage_owned_post( $layout, $owner_context ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Invalid layout.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_layouts_redirect_url() );
			exit;
		}

		$this->log_user_activity( (int) $layout->post_author, sprintf( __( 'Layout deleted: %s', 'threaddesk' ), $layout->post_title ), 'layout' );
		wp_delete_post( $layout_id, true );
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Placement layout deleted.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( $this->get_layouts_redirect_url() );
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
					$this->log_user_activity( get_current_user_id(), __( 'Account email updated.', 'threaddesk' ), 'account' );
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
			$this->log_user_activity( get_current_user_id(), sprintf( __( '%s information updated.', 'threaddesk' ), 'billing' === $type ? __( 'Billing', 'threaddesk' ) : __( 'Shipping', 'threaddesk' ) ), $type );
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

	public function handle_screenprint_variations() {
		check_ajax_referer( 'tta_threaddesk_screenprint_variations', 'nonce' );

		$product_id    = isset( $_POST['productId'] ) ? absint( $_POST['productId'] ) : 0;
		$offset        = isset( $_POST['offset'] ) ? max( 0, absint( $_POST['offset'] ) ) : 0;
		$requested_raw = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : $this->screenprint_variation_page_limit;
		$limit         = max( 1, min( $requested_raw, $this->screenprint_variation_page_limit ) );
		$in_stock_only = isset( $_POST['inStockOnly'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['inStockOnly'] ) );
		$fields        = isset( $_POST['fields'] ) ? sanitize_key( wp_unslash( $_POST['fields'] ) ) : 'full';
		$color_key     = isset( $_POST['colorKey'] ) ? sanitize_key( wp_unslash( $_POST['colorKey'] ) ) : '';
		$color_label   = isset( $_POST['colorLabel'] ) ? sanitize_text_field( wp_unslash( $_POST['colorLabel'] ) ) : '';

		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'threaddesk' ) ), 400 );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Unable to load product.', 'threaddesk' ) ), 404 );
		}

		$total = $this->count_screenprint_variations(
			$product,
			array(
				'in_stock_only' => $in_stock_only,
				'color_key'     => $color_key,
				'color_label'   => $color_label,
			)
		);
		$rows  = $this->build_screenprint_variation_payload(
			$product,
			array(
				'offset'        => $offset,
				'limit'         => $limit,
				'in_stock_only' => $in_stock_only,
				'fields'        => 'keys' === $fields ? 'keys' : 'full',
				'color_key'     => $color_key,
				'color_label'   => $color_label,
			)
		);

		wp_send_json_success(
			array(
				'variations' => $rows,
				'total'      => $total,
				'offset'     => $offset,
				'returned'   => count( $rows ),
				'hasMore'    => ( $offset + count( $rows ) ) < $total,
				'limit'      => $limit,
			)
		);
	}

	public function handle_screenprint_bootstrap() {
		check_ajax_referer( 'tta_threaddesk_screenprint_bootstrap', 'nonce' );

		$product_id = isset( $_POST['productId'] ) ? absint( $_POST['productId'] ) : 0;
		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'threaddesk' ) ), 400 );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Unable to load product.', 'threaddesk' ) ), 404 );
		}

		$is_authenticated = is_user_logged_in();
		$owner_context    = $this->get_request_owner_context();

		$requested_raw = isset( $_POST['datasets'] ) ? sanitize_text_field( wp_unslash( $_POST['datasets'] ) ) : '';
		$requested     = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) $requested_raw ) ) ) );
		if ( empty( $requested ) ) {
			$requested = array( 'layouts', 'designs', 'variations', 'quote_list' );
		}

		$allowed  = array( 'layouts', 'designs', 'variations', 'quote_list' );
		$datasets = array_values( array_intersect( $requested, $allowed ) );
		if ( empty( $datasets ) ) {
			wp_send_json_error( array( 'message' => __( 'No datasets requested.', 'threaddesk' ) ), 400 );
		}

		$response = array();
		foreach ( $datasets as $dataset ) {
			$response[ $dataset ] = $this->get_screenprint_cached_dataset_payload( $dataset, $product_id, $product, $is_authenticated, $owner_context );
		}

		wp_send_json_success(
			array(
				'datasets' => $response,
			)
		);
	}

	private function get_screenprint_cached_dataset_payload( $dataset, $product_id, $product, $is_authenticated, $owner_context ) {
		$user_id       = $is_authenticated ? get_current_user_id() : 0;
		$context_key   = $this->get_screenprint_payload_cache_key( $product_id, $user_id, $owner_context );
		$dataset_key   = 'dataset:' . sanitize_key( (string) $dataset );
		$cache_key     = 'tta_screenprint_dataset_' . md5( $context_key . '|' . $dataset_key );
		$request_cache = isset( $this->screenprint_dataset_request_cache[ $cache_key ] ) ? $this->screenprint_dataset_request_cache[ $cache_key ] : null;
		if ( is_array( $request_cache ) ) {
			return $request_cache;
		}

		$cached_payload = wp_cache_get( $cache_key, $this->screenprint_cache_group );
		if ( is_array( $cached_payload ) ) {
			$this->screenprint_dataset_request_cache[ $cache_key ] = $cached_payload;
			return $cached_payload;
		}

		$transient_payload = get_transient( $cache_key );
		if ( is_array( $transient_payload ) ) {
			wp_cache_set( $cache_key, $transient_payload, $this->screenprint_cache_group, 5 * MINUTE_IN_SECONDS );
			$this->screenprint_dataset_request_cache[ $cache_key ] = $transient_payload;
			return $transient_payload;
		}

		$payload = $this->build_screenprint_dataset_payload( $dataset, $product_id, $product, $is_authenticated, $owner_context );
		set_transient( $cache_key, $payload, 5 * MINUTE_IN_SECONDS );
		wp_cache_set( $cache_key, $payload, $this->screenprint_cache_group, 5 * MINUTE_IN_SECONDS );
		$this->screenprint_dataset_request_cache[ $cache_key ] = $payload;

		return $payload;
	}

	private function build_screenprint_dataset_payload( $dataset, $product_id, $product, $is_authenticated, $owner_context ) {
		$user_id = $is_authenticated ? get_current_user_id() : 0;

		if ( 'designs' === $dataset ) {
			return array(
				'items' => $this->build_screenprint_design_list_payload( $owner_context, $user_id ),
			);
		}

		if ( 'quote_list' === $dataset ) {
			return array(
				'items' => $this->build_screenprint_pending_quotes_payload( $is_authenticated, $user_id ),
			);
		}

		if ( 'variations' === $dataset ) {
			$initial_color_context = $this->get_screenprint_initial_color_context( $product_id, $product );
			$variation_limit       = absint( apply_filters( 'tta_threaddesk_screenprint_variation_initial_limit', $this->screenprint_variation_initial_limit, $product_id ) );
			$variation_limit = max( 1, min( $variation_limit, 500 ) );
			$rows            = $this->build_screenprint_variation_payload(
				$product,
				array(
					'offset'        => 0,
					'limit'         => $variation_limit,
					'in_stock_only' => true,
					'fields'        => 'keys',
					'color_key'     => $initial_color_context['key'],
					'color_label'   => $initial_color_context['label'],
				)
			);
			$total           = $this->count_screenprint_variations(
				$product,
				array(
					'in_stock_only' => true,
					'color_key'     => $initial_color_context['key'],
					'color_label'   => $initial_color_context['label'],
				)
			);

			return array(
				'items' => $rows,
				'meta'  => array(
					'total'         => $total,
					'offset'        => 0,
					'returned'      => count( $rows ),
					'has_more'      => count( $rows ) < $total,
					'limit'         => $variation_limit,
					'in_stock_only' => true,
					'fields'        => 'keys',
					'color_key'     => $initial_color_context['key'],
					'color_label'   => $initial_color_context['label'],
				),
			);
		}

		if ( 'layouts' === $dataset ) {
			$layout_category_settings = get_option( 'tta_threaddesk_layout_categories', array() );
			$product_context          = $this->build_screenprint_product_category_context( $product_id );
			$product_term_ids         = isset( $product_context['product_term_ids'] ) && is_array( $product_context['product_term_ids'] ) ? $product_context['product_term_ids'] : array();
			$product_term_slugs       = isset( $product_context['product_term_slugs'] ) && is_array( $product_context['product_term_slugs'] ) ? $product_context['product_term_slugs'] : array();

			return array(
				'items' => $this->build_screenprint_layout_list_payload( $owner_context, $user_id, $layout_category_settings, $product_term_ids, $product_term_slugs ),
			);
		}

		return array( 'items' => array() );
	}


	private function get_screenprint_shortcode_data_payload( $product_id, $product, $is_authenticated, $owner_context ) {
		$user_id       = $is_authenticated ? get_current_user_id() : 0;
		$cache_key     = $this->get_screenprint_payload_cache_key( $product_id, $user_id, $owner_context );
		$request_cache = isset( $this->screenprint_payload_request_cache[ $cache_key ] ) ? $this->screenprint_payload_request_cache[ $cache_key ] : null;
		if ( is_array( $request_cache ) ) {
			return $request_cache;
		}

		$cached_payload = wp_cache_get( $cache_key, $this->screenprint_cache_group );
		if ( is_array( $cached_payload ) ) {
			$this->screenprint_payload_request_cache[ $cache_key ] = $cached_payload;
			return $cached_payload;
		}

		$transient_payload = get_transient( $cache_key );
		if ( is_array( $transient_payload ) ) {
			wp_cache_set( $cache_key, $transient_payload, $this->screenprint_cache_group, 5 * MINUTE_IN_SECONDS );
			$this->screenprint_payload_request_cache[ $cache_key ] = $transient_payload;
			return $transient_payload;
		}

		$layout_category_settings = get_option( 'tta_threaddesk_layout_categories', array() );
		$product_context          = $this->build_screenprint_product_category_context( $product_id );
		$product_term_ids         = isset( $product_context['product_term_ids'] ) && is_array( $product_context['product_term_ids'] ) ? $product_context['product_term_ids'] : array();
		$product_term_slugs       = isset( $product_context['product_term_slugs'] ) && is_array( $product_context['product_term_slugs'] ) ? $product_context['product_term_slugs'] : array();
		$initial_color_context    = $this->get_screenprint_initial_color_context( $product_id, $product );
		$variation_limit          = absint( apply_filters( 'tta_threaddesk_screenprint_variation_initial_limit', $this->screenprint_variation_initial_limit, $product_id ) );
		$variation_limit          = max( 1, min( $variation_limit, 500 ) );
		$screenprint_variations   = $this->build_screenprint_variation_payload(
			$product,
			array(
				'offset'        => 0,
				'limit'         => $variation_limit,
				'in_stock_only' => true,
				'fields'        => 'keys',
				'color_key'     => $initial_color_context['key'],
				'color_label'   => $initial_color_context['label'],
			)
		);
		$screenprint_variation_total = $this->count_screenprint_variations(
			$product,
			array(
				'in_stock_only' => true,
				'color_key'     => $initial_color_context['key'],
				'color_label'   => $initial_color_context['label'],
			)
		);

		$payload = array(
			'layout_items'               => $this->build_screenprint_layout_list_payload( $owner_context, $user_id, $layout_category_settings, $product_term_ids, $product_term_slugs ),
			'saved_designs'              => $this->build_screenprint_design_list_payload( $owner_context, $user_id ),
			'screenprint_pending_quotes' => $this->build_screenprint_pending_quotes_payload( $is_authenticated, $user_id ),
			'screenprint_variations'     => $screenprint_variations,
			'screenprint_variations_meta' => array(
				'total'       => $screenprint_variation_total,
				'offset'      => 0,
				'returned'    => count( $screenprint_variations ),
				'has_more'    => count( $screenprint_variations ) < $screenprint_variation_total,
				'limit'       => $variation_limit,
				'in_stock_only' => true,
				'fields'      => 'keys',
			),
			'print_pricing_settings'     => wp_parse_args( (array) get_option( 'tta_threaddesk_print_pricing', array() ), $this->get_default_print_pricing_settings() ),
		);

		$payload = array_merge( $payload, $this->build_screenprint_color_payload( $product_id, $product ) );
		$payload = array_merge( $payload, $this->build_screenprint_placement_categories_payload( $layout_category_settings, $product_context ) );

		set_transient( $cache_key, $payload, 5 * MINUTE_IN_SECONDS );
		wp_cache_set( $cache_key, $payload, $this->screenprint_cache_group, 5 * MINUTE_IN_SECONDS );
		$this->screenprint_payload_request_cache[ $cache_key ] = $payload;

		return $payload;
	}

	private function build_screenprint_product_category_context( $product_id ) {
		$product_term_ids   = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		$product_term_slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
		$product_term_ids   = is_array( $product_term_ids ) ? array_map( 'absint', $product_term_ids ) : array();
		$product_term_slugs = is_array( $product_term_slugs ) ? array_map( 'sanitize_key', $product_term_slugs ) : array();

		$preferred_product_category_id   = ! empty( $product_term_ids ) ? (int) reset( $product_term_ids ) : 0;
		$preferred_product_category_slug = ! empty( $product_term_slugs ) ? (string) reset( $product_term_slugs ) : '';

		if ( taxonomy_exists( 'product_cat' ) && ! empty( $product_term_ids ) ) {
			$product_terms = wp_get_post_terms( $product_id, 'product_cat' );
			if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
				$deepest_term  = null;
				$deepest_depth = -1;
				foreach ( $product_terms as $product_term ) {
					if ( ! $product_term || is_wp_error( $product_term ) || ! isset( $product_term->term_id ) ) {
						continue;
					}
					$depth = count( get_ancestors( (int) $product_term->term_id, 'product_cat', 'taxonomy' ) );
					if ( $depth > $deepest_depth ) {
						$deepest_depth = $depth;
						$deepest_term  = $product_term;
					}
				}
				if ( $deepest_term && ! empty( $deepest_term->term_id ) ) {
					$preferred_product_category_id   = absint( $deepest_term->term_id );
					$preferred_product_category_slug = sanitize_key( (string) $deepest_term->slug );
				}
			}
		}

		return array(
			'product_term_ids'               => $product_term_ids,
			'product_term_slugs'             => $product_term_slugs,
			'preferred_product_category_id'   => $preferred_product_category_id,
			'preferred_product_category_slug' => $preferred_product_category_slug,
		);
	}

	private function build_screenprint_layout_list_payload( $owner_context, $user_id, $layout_category_settings, $product_term_ids, $product_term_slugs ) {
		$layout_query_args = array(
			'post_type'      => 'tta_layout',
			'posts_per_page' => 100,
			'post_status'    => array( 'private', 'publish' ),
		);
		if ( (int) $owner_context['user_id'] > 0 ) {
			$layout_query_args['author'] = $user_id;
		} else {
			$layout_query_args['author']     = 0;
			$layout_query_args['meta_key']   = '_tta_guest_token';
			$layout_query_args['meta_value'] = isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '';
		}
		$layout_posts = get_posts( $layout_query_args );
		$layout_status_labels = array(
			'pending'  => __( 'Pending', 'threaddesk' ),
			'approved' => __( 'Approved', 'threaddesk' ),
			'rejected' => __( 'Rejected', 'threaddesk' ),
		);
		$layout_items = array();
		foreach ( $layout_posts as $layout_post ) {
			$layout_category_id   = (int) get_post_meta( $layout_post->ID, 'layout_category_id', true );
			$layout_category_slug = sanitize_key( (string) get_post_meta( $layout_post->ID, 'layout_category', true ) );
			$matches_category     = false;
			if ( $layout_category_id > 0 ) {
				$settings                    = isset( $layout_category_settings[ $layout_category_id ] ) && is_array( $layout_category_settings[ $layout_category_id ] ) ? $layout_category_settings[ $layout_category_id ] : array();
				$has_product_category_mapping = isset( $settings['product_categories'] );
				$configured_product_categories = $has_product_category_mapping && is_array( $settings['product_categories'] ) ? array_map( 'absint', $settings['product_categories'] ) : array();
				if ( $has_product_category_mapping ) {
					$matches_category = ! empty( $configured_product_categories ) && (bool) array_intersect( $configured_product_categories, $product_term_ids );
				} elseif ( in_array( $layout_category_id, $product_term_ids, true ) ) {
					$matches_category = true;
				}
			}
			if ( ! $matches_category && '' !== $layout_category_slug && in_array( $layout_category_slug, $product_term_slugs, true ) ) {
				$matches_category = true;
			}
			if ( ! $matches_category ) {
				continue;
			}
			$payload_raw = get_post_meta( $layout_post->ID, 'layout_payload', true );
			$payload     = json_decode( (string) $payload_raw, true );
			if ( ! is_array( $payload ) ) {
				$payload = array();
			}
			$placements = isset( $payload['placementsByAngle'] ) && is_array( $payload['placementsByAngle'] ) ? $payload['placementsByAngle'] : array();
			if ( empty( $placements ) ) {
				$legacy_placements_raw = get_post_meta( $layout_post->ID, 'layout_placements', true );
				$legacy_placements     = json_decode( (string) $legacy_placements_raw, true );
				if ( is_array( $legacy_placements ) ) {
					$placements = $legacy_placements;
				}
			}
			if ( empty( $placements ) ) {
				continue;
			}
			if ( empty( $payload['placementsByAngle'] ) ) {
				$payload['placementsByAngle'] = $placements;
			}
			$layout_angles   = isset( $payload['angles'] ) && is_array( $payload['angles'] ) ? $payload['angles'] : array();
			$preview_angle   = '';
			$preview_entries = array();
			$print_count     = 0;
			foreach ( $placements as $angle_key => $angle_placements ) {
				if ( ! is_array( $angle_placements ) ) {
					continue;
				}
				foreach ( $angle_placements as $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$entry_url = isset( $entry['url'] ) ? (string) $entry['url'] : '';
					if ( '' === $entry_url ) {
						$entry_url = isset( $entry['sourceUrl'] ) ? (string) $entry['sourceUrl'] : '';
					}
					if ( '' === $entry_url ) {
						$entry_url = isset( $entry['designUrl'] ) ? (string) $entry['designUrl'] : '';
					}
					if ( '' === $entry_url ) {
						$entry_url = isset( $entry['previewUrl'] ) ? (string) $entry['previewUrl'] : '';
					}
					if ( '' === $entry_url ) {
						$entry_url = isset( $entry['preview'] ) ? (string) $entry['preview'] : '';
					}
					if ( '' === $entry_url ) {
						continue;
					}
					$print_count++;
					if ( '' === $preview_angle ) {
						$preview_angle = sanitize_key( (string) $angle_key );
					}
					if ( '' !== $preview_angle && sanitize_key( (string) $angle_key ) === $preview_angle ) {
						$preview_entries[] = $entry;
					}
				}
			}
			if ( empty( $preview_entries ) && '' !== $preview_angle && isset( $placements[ $preview_angle ] ) && is_array( $placements[ $preview_angle ] ) ) {
				$preview_entries = $placements[ $preview_angle ];
			}
			$preview_base_url = '';
			if ( '' !== $preview_angle && isset( $layout_angles[ $preview_angle ] ) ) {
				$preview_base_url = (string) $layout_angles[ $preview_angle ];
			}
			$layout_status = sanitize_key( (string) get_post_meta( $layout_post->ID, 'layout_status', true ) );
			if ( ! isset( $layout_status_labels[ $layout_status ] ) ) {
				$layout_status = 'pending';
			}
			if ( 'rejected' === $layout_status ) {
				continue;
			}
			$layout_items[] = array(
				'id'              => (int) $layout_post->ID,
				'title'           => (string) $layout_post->post_title,
				'category_id'     => $layout_category_id,
				'category_slug'   => $layout_category_slug,
				'placementsByAngle' => $placements,
				'previewBaseUrl'  => $preview_base_url,
				'previewEntries'  => $preview_entries,
				'printCount'      => $print_count,
				'statusKey'       => $layout_status,
				'statusLabel'     => $layout_status_labels[ $layout_status ],
			);
		}
		return $layout_items;
	}

	private function build_screenprint_design_list_payload( $owner_context, $user_id ) {
		$design_query_args = array(
			'post_type'      => 'tta_design',
			'posts_per_page' => 100,
			'post_status'    => array( 'private', 'publish' ),
		);
		if ( (int) $owner_context['user_id'] > 0 ) {
			$design_query_args['author'] = $user_id;
		} else {
			$design_query_args['author']     = 0;
			$design_query_args['meta_key']   = '_tta_guest_token';
			$design_query_args['meta_value'] = isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '';
		}
		$design_posts  = get_posts( $design_query_args );
		$saved_designs = array();
		foreach ( $design_posts as $design_post ) {
			$design_title = trim( (string) $design_post->post_title );
			if ( '' === $design_title ) {
				$design_title = __( 'Design', 'threaddesk' );
			}
			$saved_designs[] = array(
				'id'       => (int) $design_post->ID,
				'title'    => $design_title,
				'svg'      => esc_url_raw( (string) get_post_meta( $design_post->ID, 'design_svg_file_url', true ) ),
				'preview'  => esc_url_raw( (string) get_post_meta( $design_post->ID, 'design_preview_url', true ) ),
				'mockup'   => esc_url_raw( (string) get_post_meta( $design_post->ID, 'design_mockup_file_url', true ) ),
				'ratio'    => 0,
				'fileName' => (string) get_post_meta( $design_post->ID, 'design_file_name', true ),
				'palette'  => (string) get_post_meta( $design_post->ID, 'design_palette', true ) ?: '[]',
				'settings' => (string) get_post_meta( $design_post->ID, 'design_analysis_settings', true ) ?: '{}',
				'svgName'  => (string) get_post_meta( $design_post->ID, 'design_svg_file_name', true ),
			);
		}
		return $saved_designs;
	}

	private function build_screenprint_pending_quotes_payload( $is_authenticated, $user_id ) {
		$screenprint_pending_quotes = array();
		if ( ! $is_authenticated || $user_id <= 0 ) {
			return $screenprint_pending_quotes;
		}
		$pending_quote_posts = get_posts(
			array(
				'post_type'      => 'tta_quote',
				'post_status'    => array( 'private', 'publish', 'draft', 'pending' ),
				'posts_per_page' => 50,
				'author'         => $user_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		foreach ( $pending_quote_posts as $pending_quote_post ) {
			if ( ! $pending_quote_post instanceof WP_Post ) {
				continue;
			}
			if ( 'pending' !== $this->get_quote_status( $pending_quote_post->ID ) ) {
				continue;
			}
			$quote_design_names = $this->get_screenprint_quote_design_names( $pending_quote_post->ID );
			$screenprint_pending_quotes[] = array(
				'id'          => (int) $pending_quote_post->ID,
				'title'       => (string) $pending_quote_post->post_title,
				'designNames' => $quote_design_names,
				'designCount' => count( $quote_design_names ),
			);
		}
		return $screenprint_pending_quotes;
	}

	private function get_screenprint_quote_design_names( $quote_id ) {
		$raw_prints = get_post_meta( (int) $quote_id, 'screenprint_quote_prints_json', true );
		if ( is_array( $raw_prints ) ) {
			$prints = $raw_prints;
		} else {
			$prints = json_decode( (string) $raw_prints, true );
		}
		if ( ! is_array( $prints ) || empty( $prints ) ) {
			return array();
		}
		$design_names = array();
		foreach ( $prints as $print ) {
			if ( ! is_array( $print ) ) {
				continue;
			}
			$design_name = isset( $print['designName'] ) ? sanitize_text_field( (string) $print['designName'] ) : '';
			if ( '' === $design_name ) {
				continue;
			}
			$design_names[ strtolower( $design_name ) ] = $design_name;
		}

		return array_values( $design_names );
	}

	private function build_screenprint_variation_payload( $product, $args = array() ) {
		$screenprint_variations = array();
		if ( ! $product || ! is_callable( array( $product, 'is_type' ) ) || ! $product->is_type( 'variable' ) ) {
			return $screenprint_variations;
		}

		$args = wp_parse_args(
			(array) $args,
			array(
				'offset'        => 0,
				'limit'         => 0,
				'in_stock_only' => false,
				'fields'        => 'full',
				'color_key'     => '',
				'color_label'   => '',
			)
		);

		$debug_start = microtime( true );
		$available_variations = is_callable( array( $product, 'get_available_variations' ) ) ? $product->get_available_variations() : array();
		$term_label_maps      = $this->build_screenprint_attribute_term_label_maps( $available_variations );
		$offset               = max( 0, absint( $args['offset'] ) );
		$limit                = max( 0, absint( $args['limit'] ) );
		$end                  = $limit > 0 ? $offset + $limit : 0;
		$rendered             = 0;
		$processed            = 0;
		$garment_name         = is_callable( array( $product, 'get_name' ) ) ? (string) $product->get_name() : '';
		$fields_mode          = 'keys' === $args['fields'] ? 'keys' : 'full';
		$requested_color_key  = sanitize_key( (string) $args['color_key'] );
		$requested_color_name = sanitize_title( (string) $args['color_label'] );
		foreach ( $available_variations as $available_variation ) {
			$variation_id = isset( $available_variation['variation_id'] ) ? absint( $available_variation['variation_id'] ) : 0;
			if ( $variation_id <= 0 ) {
				continue;
			}
			$in_stock = ! empty( $available_variation['is_in_stock'] );
			if ( ! empty( $args['in_stock_only'] ) && ! $in_stock ) {
				continue;
			}
			if ( $processed < $offset ) {
				$processed++;
				continue;
			}
			if ( $end > 0 && $processed >= $end ) {
				break;
			}
			$processed++;

			$attributes  = isset( $available_variation['attributes'] ) && is_array( $available_variation['attributes'] ) ? $available_variation['attributes'] : array();
			$size_label  = '';
			$color_label = '';
			$color_key   = '';
			foreach ( $attributes as $attribute_key => $attribute_value ) {
				$key = sanitize_key( str_replace( 'attribute_', '', (string) $attribute_key ) );
				if ( '' === $key ) {
					continue;
				}
				$raw_value = sanitize_text_field( (string) $attribute_value );
				if ( '' === $raw_value ) {
					continue;
				}
				$resolved_label = $raw_value;
				$raw_value_slug = sanitize_title( $raw_value );
				if ( 0 === strpos( $key, 'pa_' ) && '' !== $raw_value_slug && isset( $term_label_maps[ $key ][ $raw_value_slug ] ) ) {
					$resolved_label = (string) $term_label_maps[ $key ][ $raw_value_slug ];
				}
				if ( '' === $size_label && false !== strpos( $key, 'size' ) ) {
					$size_label = $resolved_label;
				}
				if ( false !== strpos( $key, 'color' ) ) {
					if ( '' === $color_label ) {
						$color_label = $resolved_label;
					}
					if ( '' === $color_key ) {
						$color_key = sanitize_key( (string) $raw_value );
					}
				}
			}

			$row = array(
				'variationId' => $variation_id,
				'size'        => '' !== $size_label ? $size_label : __( 'N/A', 'threaddesk' ),
				'color'       => '' !== $color_label ? $color_label : __( 'N/A', 'threaddesk' ),
				'colorKey'    => '' !== $color_key ? $color_key : '',
			);
			if ( '' !== $requested_color_key || '' !== $requested_color_name ) {
				$row_color_key   = sanitize_key( (string) $row['colorKey'] );
				$row_color_label = sanitize_title( (string) $row['color'] );
				$matches_key     = '' !== $requested_color_key && '' !== $row_color_key && $requested_color_key === $row_color_key;
				$matches_label   = '' !== $requested_color_name && '' !== $row_color_label && $requested_color_name === $row_color_label;
				if ( ! $matches_key && ! $matches_label ) {
					continue;
				}
			}
			if ( 'full' === $fields_mode ) {
				$max_qty         = isset( $available_variation['max_qty'] ) && '' !== $available_variation['max_qty'] ? (int) $available_variation['max_qty'] : null;
				$inventory_label = $in_stock ? __( 'In stock', 'threaddesk' ) : __( 'Out of stock', 'threaddesk' );
				$row             = array_merge(
					$row,
					array(
						'productSku'  => isset( $available_variation['sku'] ) ? sanitize_text_field( (string) $available_variation['sku'] ) : '',
						'garmentName' => $garment_name,
						'inventory'   => null !== $max_qty ? $max_qty : $inventory_label,
						'price'       => isset( $available_variation['display_price'] ) ? round( (float) $available_variation['display_price'], 4 ) : 0,
						'inStock'     => (bool) $in_stock,
					)
				);
			}
			$screenprint_variations[] = $row;
			$rendered++;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$elapsed_ms = round( ( microtime( true ) - $debug_start ) * 1000, 2 );
			error_log( sprintf( '[ThreadDesk] screenprint variations assembled product=%d rows=%d mode=%s offset=%d limit=%d in_stock_only=%d elapsed_ms=%s', absint( $product->get_id() ), $rendered, $fields_mode, $offset, $limit, ! empty( $args['in_stock_only'] ) ? 1 : 0, $elapsed_ms ) );
		}

		return $screenprint_variations;
	}

	private function build_screenprint_attribute_term_label_maps( $available_variations ) {
		$taxonomy_slug_map = array();
		foreach ( $available_variations as $available_variation ) {
			$attributes = isset( $available_variation['attributes'] ) && is_array( $available_variation['attributes'] ) ? $available_variation['attributes'] : array();
			foreach ( $attributes as $attribute_key => $attribute_value ) {
				$taxonomy = sanitize_key( str_replace( 'attribute_', '', (string) $attribute_key ) );
				if ( '' === $taxonomy || 0 !== strpos( $taxonomy, 'pa_' ) ) {
					continue;
				}
				$slug = sanitize_title( (string) $attribute_value );
				if ( '' === $slug ) {
					continue;
				}
				if ( ! isset( $taxonomy_slug_map[ $taxonomy ] ) ) {
					$taxonomy_slug_map[ $taxonomy ] = array();
				}
				$taxonomy_slug_map[ $taxonomy ][ $slug ] = true;
			}
		}

		$term_label_maps = array();
		foreach ( $taxonomy_slug_map as $taxonomy => $slug_map ) {
			$slugs = array_keys( $slug_map );
			if ( empty( $slugs ) ) {
				continue;
			}
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'slug'       => $slugs,
				)
			);
			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				$term_slug = sanitize_title( (string) $term->slug );
				if ( '' === $term_slug ) {
					continue;
				}
				if ( ! isset( $term_label_maps[ $taxonomy ] ) ) {
					$term_label_maps[ $taxonomy ] = array();
				}
				$term_label_maps[ $taxonomy ][ $term_slug ] = (string) $term->name;
			}
		}

		return $term_label_maps;
	}

	private function count_screenprint_variations( $product, $args = array() ) {
		if ( ! $product || ! is_callable( array( $product, 'is_type' ) ) || ! $product->is_type( 'variable' ) ) {
			return 0;
		}
		$args                 = wp_parse_args(
			(array) $args,
			array(
				'in_stock_only' => false,
				'color_key'     => '',
				'color_label'   => '',
			)
		);
		$available_variations = is_callable( array( $product, 'get_available_variations' ) ) ? $product->get_available_variations() : array();
		$requested_color_key  = sanitize_key( (string) $args['color_key'] );
		$requested_color_name = sanitize_title( (string) $args['color_label'] );
		$total                = 0;
		foreach ( $available_variations as $available_variation ) {
			$variation_id = isset( $available_variation['variation_id'] ) ? absint( $available_variation['variation_id'] ) : 0;
			if ( $variation_id <= 0 ) {
				continue;
			}
			if ( ! empty( $args['in_stock_only'] ) && empty( $available_variation['is_in_stock'] ) ) {
				continue;
			}
			if ( '' !== $requested_color_key || '' !== $requested_color_name ) {
				$attributes       = isset( $available_variation['attributes'] ) && is_array( $available_variation['attributes'] ) ? $available_variation['attributes'] : array();
				$variation_color  = '';
				$variation_label  = '';
				foreach ( $attributes as $attribute_key => $attribute_value ) {
					$key = sanitize_key( str_replace( 'attribute_', '', (string) $attribute_key ) );
					if ( false === strpos( $key, 'color' ) ) {
						continue;
					}
					$raw_value = sanitize_text_field( (string) $attribute_value );
					if ( '' === $variation_color ) {
						$variation_color = sanitize_key( $raw_value );
					}
					if ( '' === $variation_label ) {
						$variation_label = sanitize_title( $raw_value );
					}
				}
				$matches_key   = '' !== $requested_color_key && '' !== $variation_color && $requested_color_key === $variation_color;
				$matches_label = '' !== $requested_color_name && '' !== $variation_label && $requested_color_name === $variation_label;
				if ( ! $matches_key && ! $matches_label ) {
					continue;
				}
			}
			$total++;
		}
		return $total;
	}

	private function get_screenprint_initial_color_context( $product_id, $product ) {
		$payload = $this->build_screenprint_color_payload( $product_id, $product );
		$key     = isset( $payload['initial_color_key'] ) ? sanitize_key( (string) $payload['initial_color_key'] ) : '';
		$label   = '';
		$choices = isset( $payload['screenprint_color_choices'] ) && is_array( $payload['screenprint_color_choices'] ) ? $payload['screenprint_color_choices'] : array();
		foreach ( $choices as $choice ) {
			if ( ! is_array( $choice ) ) {
				continue;
			}
			$choice_key = isset( $choice['key'] ) ? sanitize_key( (string) $choice['key'] ) : '';
			if ( '' === $choice_key || $choice_key !== $key ) {
				continue;
			}
			$label = isset( $choice['label'] ) ? sanitize_text_field( (string) $choice['label'] ) : '';
			break;
		}

		return array(
			'key'   => $key,
			'label' => $label,
		);
	}

	private function build_screenprint_color_payload( $product_id, $product ) {
		$default_product_images    = $this->get_screenprint_product_images( $product );
		$product_color_options     = $this->get_product_color_options( $product_id );
		$product_postbox_views     = $this->get_product_postbox_views( $product_id );
		$product_postbox_colors    = isset( $product_postbox_views['colors'] ) && is_array( $product_postbox_views['colors'] ) ? $product_postbox_views['colors'] : array();
		$screenprint_color_choices = array();
		$screenprint_images_by_color = array();
		if ( empty( $product_color_options ) ) {
			$product_color_options = array( 'default' => __( 'Default', 'threaddesk' ) );
		}
		foreach ( $product_color_options as $color_key => $color_label ) {
			$normalized_key = sanitize_key( (string) $color_key );
			if ( '' === $normalized_key ) {
				continue;
			}
			$config         = isset( $product_postbox_colors[ $normalized_key ] ) && is_array( $product_postbox_colors[ $normalized_key ] ) ? $product_postbox_colors[ $normalized_key ] : array();
			$side_label     = isset( $config['side_label'] ) && 'right' === sanitize_key( (string) $config['side_label'] ) ? 'right' : 'left';
			$resolved_side  = ! empty( $config['side_image'] ) ? (string) $config['side_image'] : ( ! empty( $config['side_fallback_url'] ) ? (string) $config['side_fallback_url'] : (string) $default_product_images['left'] );
			$images_for_color = array(
				'front'     => ! empty( $config['front_image'] ) ? (string) $config['front_image'] : ( ! empty( $config['front_fallback_url'] ) ? (string) $config['front_fallback_url'] : (string) $default_product_images['front'] ),
				'left'      => $resolved_side,
				'back'      => ! empty( $config['back_image'] ) ? (string) $config['back_image'] : ( ! empty( $config['back_fallback_url'] ) ? (string) $config['back_fallback_url'] : (string) $default_product_images['back'] ),
				'right'     => $resolved_side,
				'sideLabel' => $side_label,
			);
			$screenprint_images_by_color[ $normalized_key ] = $images_for_color;
			$screenprint_color_choices[] = array(
				'key'   => $normalized_key,
				'label' => sanitize_text_field( (string) $color_label ),
				'image' => (string) $images_for_color['front'],
			);
		}
		if ( empty( $screenprint_images_by_color ) ) {
			$screenprint_images_by_color['default'] = array(
				'front'     => (string) $default_product_images['front'],
				'left'      => (string) $default_product_images['left'],
				'back'      => (string) $default_product_images['back'],
				'right'     => (string) $default_product_images['right'],
				'sideLabel' => 'left',
			);
			$screenprint_color_choices[] = array(
				'key'   => 'default',
				'label' => __( 'Default', 'threaddesk' ),
				'image' => (string) $default_product_images['front'],
			);
		}
		return array(
			'screenprint_images_by_color' => $screenprint_images_by_color,
			'screenprint_color_choices'   => $screenprint_color_choices,
			'initial_color_key'           => isset( $screenprint_color_choices[0]['key'] ) ? (string) $screenprint_color_choices[0]['key'] : 'default',
		);
	}

	private function build_screenprint_placement_categories_payload( $layout_category_settings, $product_context ) {
		$product_term_ids   = isset( $product_context['product_term_ids'] ) ? (array) $product_context['product_term_ids'] : array();
		$product_term_slugs = isset( $product_context['product_term_slugs'] ) ? (array) $product_context['product_term_slugs'] : array();
		$default_category_slug = isset( $product_context['preferred_product_category_slug'] ) ? (string) $product_context['preferred_product_category_slug'] : '';
		$default_category_id   = isset( $product_context['preferred_product_category_id'] ) ? absint( $product_context['preferred_product_category_id'] ) : 0;
		$placement_slot_labels = $this->get_available_placement_slots();
		$placement_categories  = array();
		if ( taxonomy_exists( 'product_cat' ) && is_array( $layout_category_settings ) ) {
			uasort( $layout_category_settings, function ( $a, $b ) {
				return ( isset( $a['order'] ) ? absint( $a['order'] ) : 9999 ) - ( isset( $b['order'] ) ? absint( $b['order'] ) : 9999 );
			} );
			foreach ( $layout_category_settings as $term_id => $settings ) {
				$term_id = absint( $term_id );
				$settings = is_array( $settings ) ? $settings : array();
				if ( ! $term_id || empty( $settings['enabled'] ) ) {
					continue;
				}
				$term = get_term( $term_id, 'product_cat' );
				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}
				$has_product_category_mapping  = isset( $settings['product_categories'] );
				$configured_product_categories = $has_product_category_mapping && is_array( $settings['product_categories'] ) ? array_map( 'absint', $settings['product_categories'] ) : array();
				if ( $has_product_category_mapping ) {
					if ( empty( $configured_product_categories ) || empty( array_intersect( $configured_product_categories, $product_term_ids ) ) ) {
						continue;
					}
				} elseif ( ! in_array( $term_id, $product_term_ids, true ) && ! in_array( sanitize_key( $term->slug ), $product_term_slugs, true ) ) {
					continue;
				}
				$configured_placements = isset( $settings['placements'] ) && is_array( $settings['placements'] ) ? $settings['placements'] : array_keys( $placement_slot_labels );
				$placements = array();
				foreach ( $configured_placements as $placement_key ) {
					$placement_key = sanitize_key( $placement_key );
					if ( isset( $placement_slot_labels[ $placement_key ] ) ) {
						$placements[] = array( 'key' => $placement_key, 'label' => $placement_slot_labels[ $placement_key ] );
					}
				}
				$thumbnail_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );
				$term_thumb   = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';
				$placement_categories[] = array(
					'label'       => $term->name,
					'image_url'   => ! empty( $settings['front_image'] ) ? esc_url_raw( $settings['front_image'] ) : $term_thumb,
					'front_image' => ! empty( $settings['front_image'] ) ? esc_url_raw( $settings['front_image'] ) : '',
					'back_image'  => ! empty( $settings['back_image'] ) ? esc_url_raw( $settings['back_image'] ) : '',
					'side_image'  => ! empty( $settings['side_image'] ) ? esc_url_raw( $settings['side_image'] ) : '',
					'side_label'  => isset( $settings['side_label'] ) && 'right' === $settings['side_label'] ? 'right' : 'left',
					'placements'  => $placements,
					'term_id'     => (int) $term->term_id,
					'term_slug'   => $term->slug,
				);
			}
		}
		if ( ! empty( $placement_categories ) ) {
			$placement_category_map_by_id = array();
			$placement_category_map_by_slug = array();
			foreach ( $placement_categories as $placement_category ) {
				if ( ! empty( $placement_category['term_id'] ) ) {
					$placement_category_map_by_id[ absint( $placement_category['term_id'] ) ] = $placement_category;
				}
				if ( ! empty( $placement_category['term_slug'] ) ) {
					$placement_category_map_by_slug[ sanitize_key( (string) $placement_category['term_slug'] ) ] = $placement_category;
				}
			}
			$primary_category = null;
			foreach ( $product_term_ids as $product_term_id ) {
				if ( isset( $placement_category_map_by_id[ absint( $product_term_id ) ] ) ) {
					$primary_category = $placement_category_map_by_id[ absint( $product_term_id ) ];
					break;
				}
			}
			if ( ! is_array( $primary_category ) ) {
				foreach ( $product_term_slugs as $product_term_slug ) {
					$product_term_slug = sanitize_key( (string) $product_term_slug );
					if ( '' !== $product_term_slug && isset( $placement_category_map_by_slug[ $product_term_slug ] ) ) {
						$primary_category = $placement_category_map_by_slug[ $product_term_slug ];
						break;
					}
				}
			}
			if ( ! is_array( $primary_category ) && $default_category_id > 0 && isset( $placement_category_map_by_id[ $default_category_id ] ) ) {
				$primary_category = $placement_category_map_by_id[ $default_category_id ];
			}
			if ( ! is_array( $primary_category ) && '' !== $default_category_slug && isset( $placement_category_map_by_slug[ $default_category_slug ] ) ) {
				$primary_category = $placement_category_map_by_slug[ $default_category_slug ];
			}
			if ( ! is_array( $primary_category ) ) {
				$primary_category = reset( $placement_categories );
			}
			if ( is_array( $primary_category ) ) {
				$default_category_slug = ! empty( $primary_category['term_slug'] ) ? sanitize_key( (string) $primary_category['term_slug'] ) : $default_category_slug;
				$default_category_id   = ! empty( $primary_category['term_id'] ) ? absint( $primary_category['term_id'] ) : $default_category_id;
			}
		}
		return array(
			'placement_categories' => $placement_categories,
			'default_category_slug' => $default_category_slug,
			'default_category_id'   => $default_category_id,
		);
	}

	private function get_screenprint_payload_cache_key( $product_id, $user_id, $owner_context ) {
		$auth_fragment = $user_id > 0 ? 'u:' . absint( $user_id ) : 'guest';
		$guest_hash    = isset( $owner_context['guest_token_hash'] ) ? (string) $owner_context['guest_token_hash'] : '';
		$layout_version = (string) get_option( 'tta_threaddesk_layout_categories_version', '0' );
		$pricing_version = (string) get_option( 'tta_threaddesk_print_pricing_version', '0' );
		$cache_version   = (string) get_option( 'tta_threaddesk_screenprint_cache_version', '1' );
		$key_parts = array(
			'pid:' . absint( $product_id ),
			'auth:' . $auth_fragment,
			'guest:' . md5( $guest_hash ),
			'layoutv:' . $layout_version,
			'pricingv:' . $pricing_version,
			'cachev:' . $cache_version,
		);
		return 'tta_screenprint_payload_' . md5( implode( '|', $key_parts ) );
	}

	public function handle_layout_categories_option_updated( $old_value, $value, $option ) {
		update_option( 'tta_threaddesk_layout_categories_version', (string) time(), false );
		$this->invalidate_screenprint_payload_cache();
	}

	public function handle_print_pricing_option_updated( $old_value, $value, $option ) {
		update_option( 'tta_threaddesk_print_pricing_version', (string) time(), false );
		$this->invalidate_screenprint_payload_cache();
	}

	public function invalidate_screenprint_payload_cache( ...$args ) {
		$version = (int) get_option( 'tta_threaddesk_screenprint_cache_version', 1 );
		update_option( 'tta_threaddesk_screenprint_cache_version', $version + 1, false );
		$this->screenprint_payload_request_cache = array();
		$this->screenprint_dataset_request_cache = array();
	}



	public function render_screenprint_shortcode() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return '';
		}

		$is_authenticated = is_user_logged_in();
		$owner_context    = $this->get_request_owner_context();

		$product_id = get_the_ID();
		$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		if ( ! $product ) {
			return '';
		}

		$layout_category_settings  = get_option( 'tta_threaddesk_layout_categories', array() );
		$product_context           = $this->build_screenprint_product_category_context( $product_id );
		$color_payload             = $this->build_screenprint_color_payload( $product_id, $product );
		$placement_payload         = $this->build_screenprint_placement_categories_payload( $layout_category_settings, $product_context );
		$screenprint_images_by_color = isset( $color_payload['screenprint_images_by_color'] ) && is_array( $color_payload['screenprint_images_by_color'] ) ? $color_payload['screenprint_images_by_color'] : array();
		$screenprint_color_choices   = isset( $color_payload['screenprint_color_choices'] ) && is_array( $color_payload['screenprint_color_choices'] ) ? $color_payload['screenprint_color_choices'] : array();
		$initial_color_key           = isset( $color_payload['initial_color_key'] ) ? (string) $color_payload['initial_color_key'] : '';
		$default_category_slug       = isset( $placement_payload['default_category_slug'] ) ? (string) $placement_payload['default_category_slug'] : '';
		$default_category_id         = isset( $placement_payload['default_category_id'] ) ? absint( $placement_payload['default_category_id'] ) : 0;

		$instance_id                = 'threaddesk-screenprint-' . wp_rand( 1000, 99999 );
		$screenprint_open_chooser   = isset( $_GET['td_screenprint_return'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['td_screenprint_return'] ) );
		$screenprint_return_url     = remove_query_arg( 'td_screenprint_return', get_permalink( $product_id ) );

		wp_enqueue_style( 'threaddesk', THREDDESK_URL . 'assets/css/threaddesk.css', array(), THREDDESK_VERSION );
		wp_enqueue_script( 'threaddesk', THREDDESK_URL . 'assets/js/threaddesk.js', array( 'jquery' ), THREDDESK_VERSION, true );

		ob_start();
		?>
		<div class="threaddesk-screenprint" id="<?php echo esc_attr( $instance_id ); ?>" data-threaddesk-screenprint-product-id="<?php echo esc_attr( (string) $product_id ); ?>" data-threaddesk-screenprint-bootstrap-nonce="<?php echo esc_attr( wp_create_nonce( 'tta_threaddesk_screenprint_bootstrap' ) ); ?>" data-threaddesk-screenprint-authenticated="<?php echo $is_authenticated ? '1' : '0'; ?>">
			<div class="threaddesk-screenprint__color-picker" data-threaddesk-screenprint-color-picker style="display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;justify-content:center;">
				<?php foreach ( $screenprint_color_choices as $choice_index => $choice ) : ?>
					<button type="button" class="threaddesk-screenprint__open-color" data-threaddesk-screenprint-open-color="<?php echo esc_attr( $choice['key'] ); ?>" aria-label="<?php echo esc_attr( $choice['label'] ); ?>" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 0;width:70px;border:1px solid #dcdcde;background:#fff;border-radius:4px;cursor:pointer;position:relative;overflow:visible;<?php echo 0 === (int) $choice_index ? 'box-shadow:0 0 0 1px #2271b1;' : ''; ?>">
						<span class="threaddesk-screenprint__color-tag" aria-hidden="true"><?php echo esc_html( $choice['label'] ); ?></span>
						<?php if ( ! empty( $choice['image'] ) ) : ?>
							<img src="<?php echo esc_url( $choice['image'] ); ?>" alt="" aria-hidden="true" style="width:56px;height:56px;object-fit:cover;border-radius:3px;" />
						<?php else : ?>
							<span style="display:inline-flex;width:56px;height:56px;align-items:center;justify-content:center;background:#f0f0f1;border-radius:3px;color:#666;" aria-hidden="true">—</span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="threaddesk-screenprint__show-all-wrap hide-colors" hidden>
				<button type="button" class="threaddesk-screenprint__show-all" data-threaddesk-screenprint-show-all-colors><?php echo esc_html__( 'View all colors', 'threaddesk' ); ?></button>
			</div>
			<div class="threaddesk-layout-modal<?php echo $screenprint_open_chooser ? " is-active" : ""; ?>" aria-hidden="<?php echo $screenprint_open_chooser ? "false" : "true"; ?>" data-threaddesk-screenprint-modal="true">
				<div class="threaddesk-auth-modal__overlay" data-threaddesk-screenprint-close></div>
				<div class="threaddesk-auth-modal__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Screenprint layout chooser', 'threaddesk' ); ?>">
					<div class="threaddesk-auth-modal__actions"><button type="button" class="threaddesk-auth-modal__close" data-threaddesk-screenprint-close>&times;</button></div>
					<div class="threaddesk-layout-modal__content" data-threaddesk-screenprint-step="quotes" aria-hidden="true" hidden>
						<h3><?php echo esc_html__( 'Choose an existing pending quote', 'threaddesk' ); ?></h3>
						<div class="threaddesk-layout-modal__options" data-threaddesk-screenprint-quote-options></div>
						<p class="threaddesk-layout-modal__empty" data-threaddesk-screenprint-quote-empty hidden><?php echo esc_html__( 'No pending quotes found. You can create a new quote.', 'threaddesk' ); ?></p>
					</div>
					<div class="threaddesk-layout-modal__content is-active" data-threaddesk-screenprint-step="chooser" aria-hidden="false">
						<h3><?php echo esc_html__( 'Choose from your saved layouts', 'threaddesk' ); ?></h3>
						<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-screenprint-back-to-quotes hidden><?php echo esc_html__( '← Back to quotes', 'threaddesk' ); ?></button>
						<div class="threaddesk-layout-modal__options" data-threaddesk-screenprint-options></div>
							<p class="threaddesk-layout-modal__empty" data-threaddesk-screenprint-empty><?php echo esc_html__( 'Loading layouts…', 'threaddesk' ); ?></p>
					</div>
					<div class="threaddesk-layout-modal__content threaddesk-layout-viewer" data-threaddesk-screenprint-step="viewer" aria-hidden="true" hidden>
						<div class="threaddesk-layout-viewer__left-column">
							<div class="threaddesk-layout-viewer__stage threaddesk-screenprint__stage" data-threaddesk-screenprint-stage>
								<img src="" alt="" class="threaddesk-layout-viewer__main-image" data-threaddesk-screenprint-main />
								<div class="threaddesk-screenprint__overlay" data-threaddesk-screenprint-overlay></div>
							</div>
							<div class="threaddesk-layout-viewer__angles">
								<button type="button" class="threaddesk-layout-viewer__angle is-active" data-threaddesk-screenprint-angle="front">
									<div class="threaddesk-layout-viewer__angle-image-wrap">
										<img src="" alt="" data-threaddesk-screenprint-angle-image="front" />
									</div>
									<span><?php echo esc_html__( 'FRONT', 'threaddesk' ); ?></span>
								</button>
								<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-screenprint-angle="left">
									<div class="threaddesk-layout-viewer__angle-image-wrap">
										<img src="" alt="" data-threaddesk-screenprint-angle-image="left" />
									</div>
									<span><?php echo esc_html__( 'LEFT', 'threaddesk' ); ?></span>
								</button>
								<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-screenprint-angle="back">
									<div class="threaddesk-layout-viewer__angle-image-wrap">
										<img src="" alt="" data-threaddesk-screenprint-angle-image="back" />
									</div>
									<span><?php echo esc_html__( 'BACK', 'threaddesk' ); ?></span>
								</button>
								<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-screenprint-angle="right">
									<div class="threaddesk-layout-viewer__angle-image-wrap">
										<img src="" alt="" data-threaddesk-screenprint-angle-image="right" />
									</div>
									<span><?php echo esc_html__( 'RIGHT', 'threaddesk' ); ?></span>
								</button>
							</div>
						</div>
						<div class="threaddesk-screenprint__right-column">
							<div class="threaddesk-layout-viewer__design-panel">
								<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-screenprint-back><?php echo esc_html__( 'Back to Saved Layouts', 'threaddesk' ); ?></button>
								<p data-threaddesk-screenprint-selected><?php echo esc_html__( 'No layout selected yet.', 'threaddesk' ); ?></p>
								<p class="threaddesk-screenprint__selected-color" data-threaddesk-screenprint-selected-color><?php echo esc_html__( 'Color: --', 'threaddesk' ); ?></p>
								<div class="threaddesk-screenprint__selected-designs">
									<div class="threaddesk-layout-viewer__design-list threaddesk-screenprint__selected-design-list" data-threaddesk-screenprint-selected-design-list></div>
									<p class="threaddesk-layout-viewer__placement-empty" data-threaddesk-screenprint-selected-design-empty><?php echo esc_html__( 'No designs on this view yet.', 'threaddesk' ); ?></p>
								</div>
							</div>
							<button type="button" class="threaddesk-screenprint__quantities-button" data-threaddesk-screenprint-open-quantities><?php echo esc_html__( 'ADD QUANTITIES', 'threaddesk' ); ?></button>
						</div>
					</div>
					<div class="threaddesk-layout-modal__content threaddesk-screenprint__quantities-step" data-threaddesk-screenprint-step="quantities" aria-hidden="true" hidden>
						<h4><?php echo esc_html__( 'Add quantities', 'threaddesk' ); ?></h4>
						<div class="threaddesk-screenprint__quote-designs" data-threaddesk-screenprint-quote-designs></div>
						<div class="threaddesk-screenprint__quantities-list" data-threaddesk-screenprint-quantities-list></div>
						<p class="threaddesk-layout-viewer__placement-empty" data-threaddesk-screenprint-quantities-empty hidden><?php echo esc_html__( 'No size/color variations are available for this product.', 'threaddesk' ); ?></p>
						<button type="button" class="threaddesk-screenprint__quantities-button" data-threaddesk-screenprint-add-to-quote><?php echo esc_html__( 'ADD TO QUOTE', 'threaddesk' ); ?></button>
						<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-screenprint-back-to-viewer><?php echo esc_html__( '← Back to applied layout', 'threaddesk' ); ?></button>
					</div>
				</div>
			</div>
			<button type="button" data-threaddesk-layout-open data-threaddesk-screenprint-layout-open data-threaddesk-layout-category-open="<?php echo esc_attr( $default_category_slug ); ?>" data-threaddesk-layout-category-id-open="<?php echo esc_attr( (string) $default_category_id ); ?>" hidden></button>
			<div class="threaddesk-layout-modal" aria-hidden="true" data-threaddesk-layout-builder data-threaddesk-layout-designs="<?php echo esc_attr( wp_json_encode( $saved_designs ) ); ?>">
				<div class="threaddesk-auth-modal__overlay" data-threaddesk-layout-close></div>
				<div class="threaddesk-auth-modal__panel" role="dialog" aria-label="<?php echo esc_attr__( 'Choose a placement category', 'threaddesk' ); ?>" aria-modal="true">
					<div class="threaddesk-auth-modal__actions">
						<button type="button" class="threaddesk-auth-modal__close" data-threaddesk-layout-close aria-label="<?php echo esc_attr__( 'Close placement modal', 'threaddesk' ); ?>">&times;</button>
					</div>
					<div class="threaddesk-auth-modal__content">
						<div class="threaddesk-layout-modal__content is-active" data-threaddesk-layout-step="chooser" aria-hidden="false">
							<h3><?php echo esc_html__( 'Create a placement layout', 'threaddesk' ); ?></h3>
							<p><?php echo esc_html__( 'Choose a product category to start your layout.', 'threaddesk' ); ?></p>
							<div class="threaddesk-layout-modal__grid">
								<?php if ( ! empty( $placement_categories ) ) : ?>
									<?php foreach ( $placement_categories as $placement_category ) : ?>
										<button type="button" class="threaddesk-layout-modal__option"
											data-threaddesk-layout-category="<?php echo esc_attr( $placement_category['term_slug'] ); ?>"
											data-threaddesk-layout-category-id="<?php echo esc_attr( $placement_category['term_id'] ); ?>"
											data-threaddesk-layout-front-image="<?php echo esc_url( $placement_category['front_image'] ); ?>"
											data-threaddesk-layout-back-image="<?php echo esc_url( $placement_category['back_image'] ); ?>"
											data-threaddesk-layout-side-image="<?php echo esc_url( $placement_category['side_image'] ); ?>"
											data-threaddesk-layout-side-label="<?php echo esc_attr( $placement_category['side_label'] ); ?>"
											data-threaddesk-layout-placements="<?php echo esc_attr( wp_json_encode( $placement_category['placements'] ) ); ?>">
											<span class="threaddesk-layout-modal__image-wrap">
												<?php if ( ! empty( $placement_category['image_url'] ) ) : ?>
													<img src="<?php echo esc_url( $placement_category['image_url'] ); ?>" alt="<?php echo esc_attr( $placement_category['label'] ); ?>" class="threaddesk-layout-modal__image" />
												<?php else : ?>
													<span class="threaddesk-layout-modal__image-fallback"><?php echo esc_html__( 'No image', 'threaddesk' ); ?></span>
												<?php endif; ?>
											</span>
											<span class="threaddesk-layout-modal__label"><?php echo esc_html( $placement_category['label'] ); ?></span>
										</button>
									<?php endforeach; ?>
								<?php else : ?>
									<p class="threaddesk-layout-modal__empty"><?php echo esc_html__( 'No placement categories configured yet. Ask your administrator to configure categories in WooCommerce → ThreadDesk.', 'threaddesk' ); ?></p>
								<?php endif; ?>
							</div>
						</div>
						<div class="threaddesk-layout-modal__content threaddesk-layout-viewer" data-threaddesk-layout-step="viewer" hidden aria-hidden="true">
							<div class="threaddesk-layout-viewer__left-column">
								<div class="threaddesk-layout-viewer__stage">
									<img src="" alt="" class="threaddesk-layout-viewer__main-image" data-threaddesk-layout-main-image />
									<img src="" alt="" class="threaddesk-layout-viewer__design-overlay" data-threaddesk-layout-design-overlay hidden />
								</div>
								<div class="threaddesk-layout-viewer__angles">
									<button type="button" class="threaddesk-layout-viewer__angle is-active" data-threaddesk-layout-angle="front"><div class="threaddesk-layout-viewer__angle-image-wrap"><img src="" alt="" data-threaddesk-layout-angle-image="front" /></div><span><?php echo esc_html__( 'FRONT', 'threaddesk' ); ?></span></button>
									<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-layout-angle="left"><div class="threaddesk-layout-viewer__angle-image-wrap"><img src="" alt="" data-threaddesk-layout-angle-image="left" /></div><span><?php echo esc_html__( 'LEFT', 'threaddesk' ); ?></span></button>
									<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-layout-angle="back"><div class="threaddesk-layout-viewer__angle-image-wrap"><img src="" alt="" data-threaddesk-layout-angle-image="back" /></div><span><?php echo esc_html__( 'BACK', 'threaddesk' ); ?></span></button>
									<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-layout-angle="right"><div class="threaddesk-layout-viewer__angle-image-wrap"><img src="" alt="" data-threaddesk-layout-angle-image="right" /></div><span><?php echo esc_html__( 'RIGHT', 'threaddesk' ); ?></span></button>
								</div>
							</div>
							<div class="threaddesk-layout-viewer__design-panel">
								<div class="threaddesk-layout-viewer__panel-step" data-threaddesk-layout-panel-step="placements">
									<h4><?php echo esc_html__( 'Choose Placement', 'threaddesk' ); ?></h4>
									<div class="threaddesk-layout-viewer__placement-list" data-threaddesk-layout-placement-list></div>
									<p class="threaddesk-layout-viewer__placement-empty" data-threaddesk-layout-placement-empty><?php echo esc_html__( 'No placements available for this category.', 'threaddesk' ); ?></p>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="threaddesk-layout-viewer__save-layout-form" data-threaddesk-layout-save-layout-form hidden>
										<input type="hidden" name="action" value="tta_threaddesk_save_layout" />
										<?php wp_nonce_field( 'tta_threaddesk_save_layout' ); ?>
										<input type="hidden" name="threaddesk_layout_category" value="" data-threaddesk-layout-save-category />
										<input type="hidden" name="threaddesk_layout_category_id" value="0" data-threaddesk-layout-save-category-id />
										<input type="hidden" name="threaddesk_layout_id" value="0" data-threaddesk-layout-save-id />
										<input type="hidden" name="threaddesk_layout_payload" value="" data-threaddesk-layout-save-payload />
										<input type="hidden" name="threaddesk_layout_return_context" value="screenprint_chooser" />
										<input type="hidden" name="threaddesk_layout_return_url" value="<?php echo esc_url( $screenprint_return_url ); ?>" />
										<button type="submit" class="threaddesk-layout-viewer__save-layout-button" data-threaddesk-layout-save-layout><?php echo esc_html__( 'Save Layout', 'threaddesk' ); ?></button>
									</form>
								</div>
								<div class="threaddesk-layout-viewer__panel-step" data-threaddesk-layout-panel-step="designs" hidden>
									<h4 data-threaddesk-layout-design-heading><?php echo esc_html__( 'Choose Design', 'threaddesk' ); ?></h4>
									<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-layout-back-to-placements><?php echo esc_html__( '← Back to placements', 'threaddesk' ); ?></button>
									<div class="threaddesk-layout-viewer__design-list" data-threaddesk-layout-design-list></div>
									<p class="threaddesk-layout-viewer__placement-empty" data-threaddesk-layout-design-empty><?php echo esc_html__( 'No saved designs yet. Add designs from the Designs panel first.', 'threaddesk' ); ?></p>
									<button type="button" class="threaddesk-layout-viewer__add-design-button" data-threaddesk-design-open><?php echo esc_html__( 'Add New Design', 'threaddesk' ); ?></button>
								</div>
								<div class="threaddesk-layout-viewer__panel-step" data-threaddesk-layout-panel-step="adjust" hidden>
									<h4 data-threaddesk-layout-adjust-heading><?php echo esc_html__( 'Adjust Placement', 'threaddesk' ); ?></h4>
									<p class="threaddesk-layout-viewer__selection-name" data-threaddesk-layout-selected-design><?php echo esc_html__( 'No design selected', 'threaddesk' ); ?></p>
									<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-layout-back-to-designs><?php echo esc_html__( '← Change design', 'threaddesk' ); ?></button>
									<div class="threaddesk-layout-viewer__adjust-palette-label" data-threaddesk-layout-adjust-palette-label hidden><?php echo esc_html__( 'Colors', 'threaddesk' ); ?></div>
									<div class="threaddesk-layout-viewer__adjust-palette" data-threaddesk-layout-adjust-palette hidden></div>
									<div class="threaddesk-layout-viewer__adjust-palette-options-label" data-threaddesk-layout-adjust-palette-options-label hidden><?php echo esc_html__( 'Choose a color', 'threaddesk' ); ?></div>
									<div class="threaddesk-layout-viewer__adjust-palette-options" data-threaddesk-layout-adjust-palette-options hidden></div>
									<label class="threaddesk-layout-viewer__size-label" for="threaddesk-layout-size-slider-screenprint"><?php echo esc_html__( 'Size', 'threaddesk' ); ?></label>
									<input id="threaddesk-layout-size-slider-screenprint" type="range" min="60" max="140" value="100" class="threaddesk-layout-viewer__size-slider" data-threaddesk-layout-size-slider />
									<p class="threaddesk-layout-viewer__size-reading" data-threaddesk-layout-size-reading><?php echo esc_html__( 'Approx. size: --', 'threaddesk' ); ?></p>
									<div class="threaddesk-layout-viewer__adjust-actions">
										<button type="button" class="threaddesk-layout-viewer__remove-button" data-threaddesk-layout-remove-placement hidden><?php echo esc_html__( 'Remove Design', 'threaddesk' ); ?></button>
										<button type="button" class="threaddesk-layout-viewer__save-button" data-threaddesk-layout-save-placement><?php echo esc_html__( 'Save Placement', 'threaddesk' ); ?></button>
										<button type="button" class="threaddesk-layout-viewer__save-layout-button" data-threaddesk-layout-save-layout-shortcut><?php echo esc_html__( 'Save Layout', 'threaddesk' ); ?></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			</div>
			<div class="threaddesk-design-modal" aria-hidden="true">
				<div class="threaddesk-auth-modal__overlay" data-threaddesk-design-close></div>
				<div class="threaddesk-auth-modal__panel" role="dialog" aria-label="<?php echo esc_attr__( 'Choose design', 'threaddesk' ); ?>" aria-modal="true">
					<div class="threaddesk-auth-modal__actions">
						<button type="button" class="threaddesk-auth-modal__close" data-threaddesk-design-close aria-label="<?php echo esc_attr__( 'Close design modal', 'threaddesk' ); ?>">
							<svg class="threaddesk-auth-modal__close-icon" width="12" height="12" viewBox="0 0 15 15" aria-hidden="true" focusable="false">
								<path d="M1 15a1 1 0 01-.71-.29 1 1 0 010-1.41l5.8-5.8-5.8-5.8A1 1 0 011.7.29l5.8 5.8 5.8-5.8a1 1 0 011.41 1.41l-5.8 5.8 5.8 5.8a1 1 0 01-1.41 1.41l-5.8-5.8-5.8 5.8A1 1 0 011 15z"></path>
							</svg>
						</button>
					</div>
					<div class="threaddesk-auth-modal__content threaddesk-designer">
						<form class="threaddesk-auth-modal__form-inner" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<input type="hidden" name="action" value="tta_threaddesk_save_design" />
							<?php wp_nonce_field( 'tta_threaddesk_save_design' ); ?>
							<input type="hidden" name="threaddesk_design_id" value="0" data-threaddesk-design-id-field />
							<input type="hidden" name="threaddesk_design_return_context" value="" data-threaddesk-design-return-context />
							<input type="hidden" name="threaddesk_design_return_layout_category" value="" data-threaddesk-design-return-layout-category />
							<input type="hidden" name="threaddesk_design_return_layout_placement" value="" data-threaddesk-design-return-layout-placement />
							<input type="hidden" name="threaddesk_design_return_url" value="<?php echo esc_url( $screenprint_return_url ); ?>" data-threaddesk-design-return-base-url="<?php echo esc_url( $screenprint_return_url ); ?>" />
							<label class="threaddesk-designer__title-field" for="threaddesk_design_title_screenprint"><?php echo esc_html__( 'Title', 'threaddesk' ); ?></label>
							<input type="text" id="threaddesk_design_title_screenprint" name="threaddesk_design_title" data-threaddesk-design-title-input maxlength="120" value="" />
							<div class="threaddesk-designer__design-image" data-threaddesk-design-preview>
								<img class="threaddesk-designer__design-image-upload" data-threaddesk-design-upload-preview alt="<?php echo esc_attr__( 'Uploaded design preview', 'threaddesk' ); ?>" />
								<canvas class="threaddesk-designer__design-canvas" data-threaddesk-design-canvas aria-hidden="true"></canvas>
								<div class="threaddesk-designer__design-image-overlay" aria-hidden="true"></div>
								<svg viewBox="0 0 320 210" role="img" aria-label="<?php echo esc_attr__( 'Design preview', 'threaddesk' ); ?>">
									<rect x="0" y="0" width="320" height="210" rx="14" fill="#f4f4f4"></rect>
									<path d="M58 168L99 56h35l41 112h-27l-8-24H93l-8 24H58z" fill="#111111" data-threaddesk-preview-layer="0"></path>
									<path d="M110 124h28l-14-42-14 42z" fill="#ffffff" data-threaddesk-preview-layer="1"></path>
									<circle cx="217" cy="98" r="44" fill="#1f1f1f" data-threaddesk-preview-layer="2"></circle>
									<rect x="187" y="142" width="60" height="18" rx="9" fill="#3a3a3a" data-threaddesk-preview-layer="3"></rect>
								</svg>
							</div>
							<input type="file" id="threaddesk_design_file_screenprint" accept=".png,.jpg,.jpeg,image/png,image/jpeg" name="threaddesk_design_file" data-threaddesk-design-file hidden />
							<small class="threaddesk-designer__file-name" data-threaddesk-design-file-name><?php echo esc_html__( 'No file selected', 'threaddesk' ); ?></small>
							<input type="hidden" name="threaddesk_design_palette" data-threaddesk-design-palette value="[]" />
							<input type="hidden" name="threaddesk_design_color_count" data-threaddesk-design-color-count value="0" />
							<input type="hidden" name="threaddesk_design_analysis_settings" data-threaddesk-design-settings value="{}" />
							<input type="hidden" name="threaddesk_design_svg_markup" data-threaddesk-design-svg-markup value="" />
							<input type="hidden" name="threaddesk_design_mockup_png_data" data-threaddesk-design-mockup-png value="" />
							<div class="threaddesk-designer__controls">
								<div class="threaddesk-designer__control-head">
									<label for="threaddesk_design_max_colors_screenprint"><?php echo esc_html__( 'Maximum color count', 'threaddesk' ); ?></label>
									<div class="threaddesk-designer__color-counter">
										<input type="range" id="threaddesk_design_max_colors_screenprint" min="1" max="8" value="8" data-threaddesk-max-colors />
										<strong data-threaddesk-color-count>8</strong>
									</div>
								</div>
								<p class="threaddesk-designer__status" data-threaddesk-design-status aria-live="polite"></p>
								<div class="threaddesk-designer__swatches" data-threaddesk-color-swatches>
									<label>
										<span><?php echo esc_html__( 'Color 1', 'threaddesk' ); ?></span>
										<input type="color" value="#000000" />
									</label>
								</div>
							</div>
							<p class="threaddesk-auth-modal__submit">
								<button type="submit" class="threaddesk-auth-modal__button"><?php echo esc_html__( 'Save Design', 'threaddesk' ); ?></button>
							</p>
						</form>
					</div>
				</div>
			</div>
			<script data-cfasync="false">
		(function(){
			const root=document.getElementById(<?php echo wp_json_encode( $instance_id ); ?>); if(!root){return;}
			window.root=root;
			window.threaddeskScreenprintRoot=root;
			let layouts=[];
			let imageMap=<?php echo wp_json_encode( $screenprint_images_by_color ); ?>||{};
			const initialColorKey=<?php echo wp_json_encode( $initial_color_key ); ?>;
			let variationRows=[];
			let variationTotal=0;
			let variationReturned=0;
			let variationHasMore=false;
			let variationRowsMode='keys';
			const variationPageLimit=<?php echo (int) $this->screenprint_variation_page_limit; ?>;
			let variationLoading=false;
			let pricingSettings=<?php echo wp_json_encode( wp_parse_args( (array) get_option( 'tta_threaddesk_print_pricing', array() ), $this->get_default_print_pricing_settings() ) ); ?>||{};
			let pendingQuotes=[];
			let savedDesigns=[];
			let bootstrapLoaded=false;
			const i18nNoPreview=<?php echo wp_json_encode( __( 'No placement preview', 'threaddesk' ) ); ?>;
			const i18nPrintCountLabel=<?php echo wp_json_encode( __( 'Print count', 'threaddesk' ) ); ?>;
			const i18nSelectedPrefix=<?php echo wp_json_encode( __( 'LAYOUT', 'threaddesk' ) ); ?>;
			const i18nSelectedColorPrefix=<?php echo wp_json_encode( __( 'Color', 'threaddesk' ) ); ?>;
			const i18nDesignFallback=<?php echo wp_json_encode( __( 'Design', 'threaddesk' ) ); ?>;
			const i18nAdjust=<?php echo wp_json_encode( __( 'ADJUST', 'threaddesk' ) ); ?>;
			const i18nApproxSizePrefix=<?php echo wp_json_encode( __( 'Approx. size', 'threaddesk' ) ); ?>;
			const i18nCreateLayout=<?php echo wp_json_encode( __( 'CREATE A LAYOUT', 'threaddesk' ) ); ?>;
			const i18nCreateNewQuote=<?php echo wp_json_encode( __( 'CREATE NEW QUOTE', 'threaddesk' ) ); ?>;
			const i18nCreateNewQuoteHint=<?php echo wp_json_encode( __( 'Start a brand new quote for this product.', 'threaddesk' ) ); ?>;
			const i18nNoDesignsInQuote=<?php echo wp_json_encode( __( 'No designs in this quote yet.', 'threaddesk' ) ); ?>;
			const i18nDesignCountLabel=<?php echo wp_json_encode( __( 'Design count', 'threaddesk' ) ); ?>;
			const i18nPendingQuotePrefix=<?php echo wp_json_encode( __( 'PENDING QUOTE', 'threaddesk' ) ); ?>;
			const i18nCreateLayoutHint=<?php echo wp_json_encode( __( 'Need a new layout? Start in the placements builder.', 'threaddesk' ) ); ?>;
			const i18nGuestEmpty=<?php echo wp_json_encode( __( 'No saved layouts in this browser yet.', 'threaddesk' ) ); ?>;
			const i18nUserEmpty=<?php echo wp_json_encode( __( 'No saved layouts match this product categories yet.', 'threaddesk' ) ); ?>;
			const i18nInventoryLabel=<?php echo wp_json_encode( __( 'Inventory', 'threaddesk' ) ); ?>;
			const i18nQuantityLabel=<?php echo wp_json_encode( __( 'Quantity', 'threaddesk' ) ); ?>;
			const i18nEstimatedUnitCostLabel=<?php echo wp_json_encode( __( 'Est. Cost/Unit', 'threaddesk' ) ); ?>;
			const i18nQuoteDesignsTitle=<?php echo wp_json_encode( __( 'Designs in this quote', 'threaddesk' ) ); ?>;
			const i18nQuoteGarmentsTitle=<?php echo wp_json_encode( __( 'Garment in this quote', 'threaddesk' ) ); ?>;
			const i18nEstimatedColorCountLabel=<?php echo wp_json_encode( __( 'Estimated color count', 'threaddesk' ) ); ?>;
			const i18nAddToQuoteSuccess=<?php echo wp_json_encode( __( 'Quote added successfully.', 'threaddesk' ) ); ?>;
			const i18nAddToQuoteError=<?php echo wp_json_encode( __( 'Unable to add quote right now.', 'threaddesk' ) ); ?>;
			const i18nAddToQuoteRequiresQty=<?php echo wp_json_encode( __( 'Please add at least one quantity before creating a quote.', 'threaddesk' ) ); ?>;
			const i18nQuoteTitlePrompt=<?php echo wp_json_encode( __( 'Name your Quote', 'threaddesk' ) ); ?>;
			const i18nQuoteTitleRequired=<?php echo wp_json_encode( __( 'A quote title is required.', 'threaddesk' ) ); ?>;
			const i18nQuoteSavedContinue=<?php echo wp_json_encode( __( 'Quote saved. Continue adding articles to this quote?', 'threaddesk' ) ); ?>;
			const i18nKeepShopping=<?php echo wp_json_encode( __( 'ADD MORE TO QUOTE', 'threaddesk' ) ); ?>;
			const i18nContinueHere=<?php echo wp_json_encode( __( 'SUBMIT QUOTE', 'threaddesk' ) ); ?>;
			const screenprintQuoteAjaxUrl=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			const screenprintQuoteNonce=<?php echo wp_json_encode( wp_create_nonce( 'tta_threaddesk_screenprint_quote' ) ); ?>;
			const screenprintVariationNonce=<?php echo wp_json_encode( wp_create_nonce( 'tta_threaddesk_screenprint_variations' ) ); ?>;
			const screenprintBootstrapNonce=String(root.getAttribute('data-threaddesk-screenprint-bootstrap-nonce')||'').trim();
			const screenprintProductId=Number(root.getAttribute('data-threaddesk-screenprint-product-id')||0);
			const screenprintProductsPageUrl=<?php echo wp_json_encode( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>;
			const createLayoutCategory=<?php echo wp_json_encode( $default_category_slug ); ?>;
			const createLayoutCategoryId=<?php echo (int) $default_category_id; ?>;
			const shouldOpenChooser=<?php echo $screenprint_open_chooser ? 'true' : 'false'; ?>;
			const isAuthenticated=String(root.getAttribute('data-threaddesk-screenprint-authenticated')||'0').trim()==='1';
			const modal=root.querySelector('[data-threaddesk-screenprint-modal]');
			if(!modal){console.error('[ThreadDesk] screenprint modal missing');return;}
			const colorPicker=root.querySelector('[data-threaddesk-screenprint-color-picker]');
			const showAllWrap=root.querySelector('.hide-colors');
			const showAllBtn=root.querySelector('[data-threaddesk-screenprint-show-all-colors]');
			const options=root.querySelector('[data-threaddesk-screenprint-options]');
			const quoteOptions=root.querySelector('[data-threaddesk-screenprint-quote-options]');
			const quoteEmptyState=root.querySelector('[data-threaddesk-screenprint-quote-empty]');
			const emptyState=root.querySelector('[data-threaddesk-screenprint-empty]');
			const quotesStep=root.querySelector('[data-threaddesk-screenprint-step="quotes"]');
			const chooserStep=root.querySelector('[data-threaddesk-screenprint-step="chooser"]');
			const viewerStep=root.querySelector('[data-threaddesk-screenprint-step="viewer"]');
			const quantitiesStep=root.querySelector('[data-threaddesk-screenprint-step="quantities"]');
			const backToQuotesButton=root.querySelector('[data-threaddesk-screenprint-back-to-quotes]');
			const selectedLabel=root.querySelector('[data-threaddesk-screenprint-selected]');
			const selectedColorLabels=root.querySelectorAll('[data-threaddesk-screenprint-selected-color]');
			const selectedColorLabel=selectedColorLabels.length?selectedColorLabels[0]:null;
			if(selectedColorLabels.length>1){for(let i=1;i<selectedColorLabels.length;i++){selectedColorLabels[i].remove();}}
			const selectedDesignList=root.querySelector('[data-threaddesk-screenprint-selected-design-list]');
			const openQuantitiesButton=root.querySelector('[data-threaddesk-screenprint-open-quantities]');
			const addToQuoteButton=root.querySelector('[data-threaddesk-screenprint-add-to-quote]');
			const quantitiesList=root.querySelector('[data-threaddesk-screenprint-quantities-list]');
			const quoteDesigns=root.querySelector('[data-threaddesk-screenprint-quote-designs]');
			const quantitiesEmpty=root.querySelector('[data-threaddesk-screenprint-quantities-empty]');
			const selectedDesignEmpty=root.querySelector('[data-threaddesk-screenprint-selected-design-empty]');
			const main=root.querySelector('[data-threaddesk-screenprint-main]');
			const overlayWrap=root.querySelector('[data-threaddesk-screenprint-overlay]');
			const stage=root.querySelector('[data-threaddesk-screenprint-stage]');
			const angleThumbs=root.querySelectorAll('[data-threaddesk-screenprint-angle-image]');
				if(!colorPicker||!options||!chooserStep||!viewerStep||!quantitiesStep){return;}
			let selected=null; let angle='front'; let selectedColor=initialColorKey; let stageRatioLocked=false; let colorsExpanded=false; let activePlacementKey=''; let activePaletteEditor=null; let dragState=null; let selectedExistingQuoteId=0;
			const screenprintPaletteOptionSet=['transparent','#FFFFFF','#000000','#FEDB00','#FED141','#FFB81C','#FF6A39','#E38331','#BE531C','#C8102E','#D22730','#BE3A34','#A6192E','#A50034','#FF85BD','#BA9CC5','#512D6D','#833177','#351F65','#10069F','#131F29','#28334A','#002D72','#004C97','#0076A8','#8BBEE8','#0092CB','#00AFD7','#007C80','#007A53','#00AD50','#249E6B','#00664F','#304F42','#4E3629','#7B4D35','#D3BC8D','#D5CB9F','#B1B3B3','#A7A8AA','#F2E9DB'];
			if(!selectedColor||!imageMap[selectedColor]){const keys=Object.keys(imageMap||{}); selectedColor=keys.length?keys[0]:'';}
			let images=(imageMap&&imageMap[selectedColor])?imageMap[selectedColor]:{};
			const cartForm=(root.closest('form.cart'))||(root.closest('.product')?root.closest('.product').querySelector('form.cart'):null)||document.querySelector('form.cart');
			const cartLayoutIdField=root.querySelector('[data-threaddesk-cart-layout-id]');
			const cartLayoutColorField=root.querySelector('[data-threaddesk-cart-layout-color]');
			const cartLayoutDesignIdsField=root.querySelector('[data-threaddesk-cart-layout-design-ids]');
			const cartLayoutSnapshotField=root.querySelector('[data-threaddesk-cart-layout-snapshot]');
			const ensureCartFieldsInForm=()=>{
				if(!cartForm){return;}
				[cartLayoutIdField,cartLayoutColorField,cartLayoutDesignIdsField,cartLayoutSnapshotField].forEach((field)=>{
					if(!field||field.form===cartForm){return;}
					cartForm.appendChild(field);
				});
			};
			const syncCartSelection=()=>{
				ensureCartFieldsInForm();
				if(!cartLayoutIdField||!cartLayoutColorField||!cartLayoutDesignIdsField||!cartLayoutSnapshotField){return;}
				if(!selected||!selected.id){
					cartLayoutIdField.value='0';
					cartLayoutColorField.value='';
					cartLayoutDesignIdsField.value='';
					cartLayoutSnapshotField.value='';
					return;
				}
				const seenDesignIds=[];
				const summaryPlacements=[];
				const byAngle=(selected.placementsByAngle&&typeof selected.placementsByAngle==='object')?selected.placementsByAngle:{};
				Object.keys(byAngle).forEach((angleKey)=>{
					const raw=byAngle[angleKey];
					const entries=normalizePlacementEntries(raw,angleKey);
					entries.forEach((entry)=>{
						if(!entry||typeof entry!=='object'){return;}
						const designId=Number(entry.designId||0);
						if(designId>0&&!seenDesignIds.includes(designId)){seenDesignIds.push(designId);}
						summaryPlacements.push({
							placementKey:String(entry.placementKey||'').trim(),
							placementLabel:String(entry.placementLabel||'').trim(),
							designId:designId,
							designName:String(entry.designName||'').trim()
						});
					});
				});
				const snapshot={
					layoutId:Number(selected.id||0),
					layoutTitle:String(selected.title||'').trim(),
					layoutStatus:String(selected.statusLabel||'').trim(),
					printCount:Number(selected.printCount||0),
					selectedColor:String(selectedColor||'').trim(),
					designIds:seenDesignIds,
					placements:summaryPlacements
				};
				cartLayoutIdField.value=String(snapshot.layoutId||0);
				cartLayoutColorField.value=snapshot.selectedColor;
				cartLayoutDesignIdsField.value=seenDesignIds.join(',');
				cartLayoutSnapshotField.value=JSON.stringify(snapshot);
			};
			const bootstrapCacheNamespace='tta_threaddesk_screenprint_bootstrap_v1';
			const bootstrapContextKey='pid:'+String(screenprintProductId||0)+'|auth:'+(isAuthenticated?'1':'0');
			window.ttaThreadDeskScreenprintBootstrapCache=window.ttaThreadDeskScreenprintBootstrapCache||{};
			const bootstrapMemoryCache=window.ttaThreadDeskScreenprintBootstrapCache;
			const mergeBootstrapDatasets=(datasets)=>{
				if(!datasets||typeof datasets!=='object'){return;}
				if(datasets.layouts&&Array.isArray(datasets.layouts.items)){layouts=datasets.layouts.items;}
				if(datasets.designs&&Array.isArray(datasets.designs.items)){savedDesigns=datasets.designs.items;}
				if(datasets.quote_list&&Array.isArray(datasets.quote_list.items)){pendingQuotes=datasets.quote_list.items;}
				if(datasets.variations){
					variationRows=Array.isArray(datasets.variations.items)?datasets.variations.items:[];
					variationRowsMode='keys';
					const variationMeta=datasets.variations.meta&&typeof datasets.variations.meta==='object'?datasets.variations.meta:{};
					const total=Number(variationMeta.total||variationRows.length||0);
					variationTotal=Number.isFinite(total)&&total>=0?total:variationRows.length;
					const returned=Number(variationMeta.returned||variationRows.length||0);
					variationReturned=Number.isFinite(returned)&&returned>=0?returned:variationRows.length;
					variationHasMore=!!variationMeta.has_more;
				}
			};
			const getSessionBootstrapCache=()=>{
				if(!window.sessionStorage){return null;}
				try{
					const raw=window.sessionStorage.getItem(bootstrapCacheNamespace);
					return raw?JSON.parse(raw):null;
				}catch(e){return null;}
			};
			const setSessionBootstrapCache=(cache)=>{
				if(!window.sessionStorage){return;}
				try{window.sessionStorage.setItem(bootstrapCacheNamespace,JSON.stringify(cache||{}));}
				catch(e){}
			};
			const primeBootstrapFromClientCache=()=>{
				const mem=bootstrapMemoryCache[bootstrapContextKey];
				if(mem&&typeof mem==='object'){
					mergeBootstrapDatasets(mem);
					return true;
				}
				const sessionCache=getSessionBootstrapCache();
				if(sessionCache&&sessionCache[bootstrapContextKey]&&typeof sessionCache[bootstrapContextKey]==='object'){
					bootstrapMemoryCache[bootstrapContextKey]=sessionCache[bootstrapContextKey];
					mergeBootstrapDatasets(sessionCache[bootstrapContextKey]);
					return true;
				}
				return false;
			};
			const persistBootstrapClientCache=(datasets)=>{
				const next=datasets&&typeof datasets==='object'?datasets:{};
				bootstrapMemoryCache[bootstrapContextKey]=next;
				const sessionCache=getSessionBootstrapCache()||{};
				sessionCache[bootstrapContextKey]=next;
				setSessionBootstrapCache(sessionCache);
			};
			const fetchBootstrapDatasets=async()=>{
				const payload=new URLSearchParams();
				payload.set('action','tta_threaddesk_screenprint_bootstrap');
				payload.set('nonce',screenprintBootstrapNonce||'');
				payload.set('productId',String(screenprintProductId||0));
				payload.set('datasets','layouts,designs,variations,quote_list');
				const response=await fetch(screenprintQuoteAjaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString()});
				const data=await response.json();
				if(!data||!data.success){throw new Error((data&&data.data&&data.data.message)?String(data.data.message):'Unable to load screenprint data');}
				return data&&data.data&&data.data.datasets&&typeof data.data.datasets==='object'?data.data.datasets:{};
			};
			const loadBootstrapDatasets=async()=>{
				const hadCache=primeBootstrapFromClientCache();
				if(hadCache){
					renderQuoteOptions();
					renderLayoutOptions();
				}
				try{
					const datasets=await fetchBootstrapDatasets();
					mergeBootstrapDatasets(datasets);
					persistBootstrapClientCache(datasets);
					bootstrapLoaded=true;
					renderQuoteOptions();
					renderLayoutOptions();
				}catch(error){
					console.error('[ThreadDesk screenprint bootstrap]',error);
				}
			};
			const getStepFocusable=(container)=>{
				if(!container){return null;}
				return container.querySelector('button:not([disabled]),[href],input:not([type="hidden"]):not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])');
			};
			const setStep=(step,shouldMoveFocus=true)=>{
				const showQuotes=step==='quotes';
				const showChooser=step==='chooser';
				const showViewer=step==='viewer';
				const showQuantities=step==='quantities';
				const activeElement=document.activeElement;
				if(activeElement&&activeElement!==document.body){
					const activeInQuotes=!!(quotesStep&&quotesStep.contains(activeElement));
					const activeInChooser=!!(chooserStep&&chooserStep.contains(activeElement));
					const activeInViewer=!!(viewerStep&&viewerStep.contains(activeElement));
					const activeInQuantities=!!(quantitiesStep&&quantitiesStep.contains(activeElement));
					if((activeInQuotes&&!showQuotes)||(activeInChooser&&!showChooser)||(activeInViewer&&!showViewer)||(activeInQuantities&&!showQuantities)){
						if(typeof activeElement.blur==='function'){activeElement.blur();}
					}
				}
				if(quotesStep){quotesStep.hidden=!showQuotes;quotesStep.classList.toggle('is-active',showQuotes);quotesStep.setAttribute('aria-hidden',showQuotes?'false':'true');}
				if(chooserStep){chooserStep.hidden=!showChooser;chooserStep.classList.toggle('is-active',showChooser);chooserStep.setAttribute('aria-hidden',showChooser?'false':'true');}
				if(viewerStep){viewerStep.hidden=!showViewer;viewerStep.classList.toggle('is-active',showViewer);viewerStep.setAttribute('aria-hidden',showViewer?'false':'true');}
				if(quantitiesStep){quantitiesStep.hidden=!showQuantities;quantitiesStep.classList.toggle('is-active',showQuantities);quantitiesStep.setAttribute('aria-hidden',showQuantities?'false':'true');}
				if(backToQuotesButton){backToQuotesButton.hidden=!(showChooser&&Array.isArray(pendingQuotes)&&pendingQuotes.length>0);}
				const shownStep=showQuotes?quotesStep:(showChooser?chooserStep:(showViewer?viewerStep:quantitiesStep));
				if(shouldMoveFocus&&shownStep){
					const nextFocus=getStepFocusable(shownStep);
					if(nextFocus&&typeof nextFocus.focus==='function'){window.requestAnimationFrame(()=>{nextFocus.focus();});}
				}
			};
			const openScreenprintChooserModal=()=>{
				if(!modal){return;}
				modal.classList.add('is-active');
				modal.setAttribute('aria-hidden','false');
				document.body.classList.add('threaddesk-modal-open');
				const shouldPromptQuoteSelection=isAuthenticated&&Array.isArray(pendingQuotes)&&pendingQuotes.length>0;
				setStep(shouldPromptQuoteSelection?'quotes':'chooser');
			};
			const closeScreenprintModal=()=>{
				if(!modal){return;}
				const activeElement=document.activeElement;
				if(activeElement&&modal.contains(activeElement)&&typeof activeElement.blur==='function'){activeElement.blur();}
				modal.classList.remove('is-active');
				modal.setAttribute('aria-hidden','true');
				document.body.classList.remove('threaddesk-modal-open');
				const shouldPromptQuoteSelection=isAuthenticated&&Array.isArray(pendingQuotes)&&pendingQuotes.length>0;
				setStep(shouldPromptQuoteSelection?'quotes':'chooser',false);
			};
			const syncScreenprintPanelHeight=()=>{
				if(!viewerStep||viewerStep.hidden||!stage){return;}
				const stageHeight=Math.round(stage.getBoundingClientRect().height||0);
				if(stageHeight>0){viewerStep.style.setProperty('--threaddesk-screenprint-stage-rendered-height',stageHeight+'px');}
			};
			const normalizeColorValue=(value)=>String(value||'').trim().toLowerCase().replace(/\s+/g,'-');
			const getApproxSizeLabel=(placementKey,sliderValue,designRatio)=>{
				const key=String(placementKey||'').trim().toLowerCase();
				const slider=Number(sliderValue);
				const ratioRaw=Number(designRatio);
				const ratio=(Number.isFinite(ratioRaw)&&ratioRaw>0)?ratioRaw:1;
				const sliderMin=60;
				const sliderMax=140;
				const ranges={
					full_chest:{min:4.5,max:12.5},
					back:{min:4.5,max:12.5},
					left_chest:{approx:4.0},
					right_chest:{approx:4.0},
					left_sleeve:{approx:4.0},
					right_sleeve:{approx:4.0}
				};
				const range=Object.prototype.hasOwnProperty.call(ranges,key)?ranges[key]:{approx:4.0};
				let maxDimension=4.0;
				if(Object.prototype.hasOwnProperty.call(range,'min')&&Object.prototype.hasOwnProperty.call(range,'max')){
					const clamped=Math.max(sliderMin,Math.min(sliderMax,Number.isFinite(slider)?slider:100));
					const normalized=(clamped-sliderMin)/(sliderMax-sliderMin);
					maxDimension=Number(range.min)+((Number(range.max)-Number(range.min))*normalized);
				}else{
					maxDimension=Number(range.approx||4.0)*((Number.isFinite(slider)?slider:100)/100);
				}
				let width=maxDimension;
				let height=maxDimension;
				if(ratio>1){height=maxDimension/ratio;}
				else if(ratio>0&&ratio<1){width=maxDimension*ratio;}
				return i18nApproxSizePrefix+': '+String(width.toFixed(1))+'" W × '+String(height.toFixed(1))+'" H';
			};
			const defaultPricing={setup_cost:50,color_setup_cost:30,color_change_cost:5,repeat_reduction:15,print_cost:1.25,color_cost:0.10,garment_cost:50,total_margins:30};
			const getPricingNumber=(key)=>{
				const fallback=Object.prototype.hasOwnProperty.call(defaultPricing,key)?defaultPricing[key]:0;
				const raw=Object.prototype.hasOwnProperty.call(pricingSettings,key)?pricingSettings[key]:fallback;
				const value=Number(raw);
				return Number.isFinite(value)&&value>=0?value:fallback;
			};
			const getSelectedDesignColorCount=()=>{
				if(!selected||!selected.placementsByAngle||typeof selected.placementsByAngle!=='object'){return 1;}
				let maxColorCount=0;
				Object.keys(selected.placementsByAngle).forEach((angleKey)=>{
					const entries=normalizePlacementEntries(selected.placementsByAngle[angleKey],angleKey);
					entries.forEach((entry)=>{
						const paletteCurrent=Array.isArray(entry&&entry.paletteCurrent)?entry.paletteCurrent:[];
						const paletteOriginal=Array.isArray(entry&&entry.paletteOriginal)?entry.paletteOriginal:[];
						const palette=(paletteCurrent.length?paletteCurrent:paletteOriginal).filter((color)=>String(color||'').trim()!==''&&String(color||'').trim().toLowerCase()!=='transparent');
						if(palette.length>maxColorCount){maxColorCount=palette.length;}
					});
				});
				return maxColorCount>0?maxColorCount:1;
			};
			const formatCurrency=(value)=>'$'+String(Number(value).toFixed(2));
			const getSelectedPrintCount=()=>{
				if(!selected||!selected.placementsByAngle||typeof selected.placementsByAngle!=='object'){return 1;}
				let printCount=0;
				Object.keys(selected.placementsByAngle).forEach((angleKey)=>{
					const entries=normalizePlacementEntries(selected.placementsByAngle[angleKey],angleKey);
					entries.forEach((entry)=>{
						if(entry&&entry.url){printCount++;}
					});
				});
				return printCount>0?printCount:1;
			};
			const getTotalRequestedQuantity=()=>{
				if(!quantitiesList){return 0;}
				return Array.from(quantitiesList.querySelectorAll('.threaddesk-screenprint__quantity-input')).reduce((sum,input)=>{
					const value=Number(input&&input.value);
					if(!Number.isFinite(value)||value<=0){return sum;}
					return sum+value;
				},0);
			};
			const calculateEstimatedUnitCost=(totalQuantity,designSummaries,variationPrice)=>{
				const qty=Number(totalQuantity);
				if(!Number.isFinite(qty)||qty<=0){return null;}
				const setup=getPricingNumber('setup_cost');
				const colorSetup=getPricingNumber('color_setup_cost');
				const printCost=getPricingNumber('print_cost');
				const colorCost=getPricingNumber('color_cost');
				const garmentCostPct=getPricingNumber('garment_cost');
				const totalMarginsPct=getPricingNumber('total_margins');
				const garmentBase=Math.max(0,Number(variationPrice)||0);
				const garmentValue=garmentBase*(garmentCostPct/100);
				const summaries=Array.isArray(designSummaries)?designSummaries:[];
				const totalUnitPrintCost=summaries.reduce((sum,summary)=>{
					const count=Math.max(1,Number(summary&&summary.estimatedColorCount)||1);
					const setupUnitCost=((setup+(colorSetup*count)+(Math.max(0,Number(summary&&summary.additionalSetupCost)||0)))/qty);
					const placementPrintCost=Math.max(1,Number(summary&&summary.totalPrintCostCount)||1)*printCost;
					return sum+setupUnitCost+(colorCost*count)+placementPrintCost;
				},0);
				const fallbackCount=Math.max(1,summaries.length||0);
				const fallbackPrintCost=fallbackCount*printCost;
				const baseUnitCost=garmentValue+(totalUnitPrintCost>0?totalUnitPrintCost:((((setup+(colorSetup*1))/qty)+(colorCost*1)+fallbackPrintCost)));
				const marginDivisor=1-(totalMarginsPct/100);
				if(!Number.isFinite(marginDivisor)||marginDivisor<=0){return baseUnitCost;}
				return baseUnitCost/marginDivisor;
			};
			const getSelectedDesignSummaries=()=>{
				if(!selected||!selected.placementsByAngle||typeof selected.placementsByAngle!=='object'){return [];}
				const colorChangeCost=getPricingNumber('color_change_cost');
				const summaryMap={};
				Object.keys(selected.placementsByAngle).forEach((angleKey)=>{
					const entries=normalizePlacementEntries(selected.placementsByAngle[angleKey],angleKey);
					entries.forEach((entry,index)=>{
						if(!entry||typeof entry!=='object'){return;}
						const designId=Number(entry.designId||0);
						const designLabel=String((entry.designName)||(entry.placementLabel)||i18nDesignFallback).trim()||i18nDesignFallback;
						const designSource=String((entry.baseUrl)||(entry.__paletteSource)||(entry.url)||'').trim();
						const designIdentity=(designId>0?('id:'+String(designId)):('src:'+designSource+'|label:'+designLabel+'|idx:'+String(index)));
						const approxSize=Math.round(Number(entry.sliderValue)||100);
						const summaryKey=designIdentity+'|size:'+String(approxSize);
						const paletteCurrent=Array.isArray(entry.paletteCurrent)?entry.paletteCurrent:[];
						const paletteOriginal=Array.isArray(entry.paletteOriginal)?entry.paletteOriginal:[];
						const palette=(paletteCurrent.length?paletteCurrent:paletteOriginal).filter((color)=>String(color||'').trim()!==''&&String(color||'').trim().toLowerCase()!=='transparent');
						const normalizedPalette=palette.map((color)=>String(color||'').trim().toLowerCase()).filter((color)=>color!=='').sort();
						const estimatedColorCount=palette.length>0?palette.length:1;
						if(!summaryMap[summaryKey]){
							summaryMap[summaryKey]={
								designLabel:designLabel,
								estimatedColorCount:estimatedColorCount,
								additionalSetupCost:0,
								printCostCount:1,
								paletteSignature:normalizedPalette.join('|')
							};
							return;
						}
						const summary=summaryMap[summaryKey];
						summary.estimatedColorCount=Math.max(Number(summary.estimatedColorCount)||1,estimatedColorCount);
						const existingPalette=String(summary.paletteSignature||'');
						const currentPalette=normalizedPalette.join('|');
						if(existingPalette!==currentPalette){
							summary.additionalSetupCost+=colorChangeCost;
						}
					});
				});
				return Object.values(summaryMap).map((summary)=>({
					designLabel:summary.designLabel,
					estimatedColorCount:summary.estimatedColorCount,
					additionalSetupCost:summary.additionalSetupCost,
					totalPrintCostCount:summary.printCostCount
				}));
			};
			const renderQuoteDesignSummary=(rows)=>{
				if(!quoteDesigns){return;}
				quoteDesigns.innerHTML='';
				const designSummaries=getSelectedDesignSummaries();
				if(!designSummaries.length){quoteDesigns.hidden=true;return;}
				const garments=[];
				(rows||[]).forEach((row)=>{
					const garmentName=String((row&&row.garmentName)||'').trim();
					const garmentColor=String((row&&row.color)||'').trim();
					const label=(garmentName||'')+(garmentColor?' - '+garmentColor:'');
					if(label&&!garments.includes(label)){garments.push(label);}
				});
				const title=document.createElement('p');
				title.className='threaddesk-screenprint__quote-designs-title';
				title.textContent=i18nQuoteDesignsTitle||'Designs in this quote';
				const garmentTitle=document.createElement('p');
				garmentTitle.className='threaddesk-screenprint__quote-designs-title';
				garmentTitle.textContent=i18nQuoteGarmentsTitle||'Garment in this quote';
				const garmentText=document.createElement('p');
				garmentText.className='threaddesk-screenprint__quote-designs-garments';
				garmentText.textContent=garments.length?garments.join(', '):'--';
				const list=document.createElement('ul');
				list.className='threaddesk-screenprint__quote-designs-list';
				designSummaries.forEach((summary)=>{
					const item=document.createElement('li');
					item.className='threaddesk-screenprint__quote-designs-item';
					item.textContent=String(summary.designLabel||i18nDesignFallback)+' • '+(i18nEstimatedColorCountLabel||'Estimated color count')+': '+String(summary.estimatedColorCount||1);
					list.appendChild(item);
				});
				quoteDesigns.appendChild(garmentTitle);
				quoteDesigns.appendChild(garmentText);
				quoteDesigns.appendChild(title);
				quoteDesigns.appendChild(list);
				quoteDesigns.hidden=false;
			};
			const variationPreloadByColor={};
			const variationRowsByColorKey={};
			const getVariationColorCacheKey=(colorKey,colorLabel='')=>{
				const normalizedKey=normalizeColorValue(colorKey);
				if(normalizedKey){return normalizedKey;}
				return normalizeColorValue(colorLabel||'default')||'default';
			};
			const getVariationRowsForColor=()=>{
				return (Array.isArray(variationRows)?variationRows:[]).filter((row)=>{
					const rowColorKey=normalizeColorValue(row&&row.colorKey);
					const selectedColorKey=normalizeColorValue(selectedColor);
					if(rowColorKey&&selectedColorKey){return rowColorKey===selectedColorKey;}
					return normalizeColorValue(row&&row.color)===normalizeColorValue(getSelectedColorLabel());
				});
			};
			const loadMoreVariations=async()=>{
				if(variationLoading||!variationHasMore){return;}
				variationLoading=true;
				try{
					const payload=new URLSearchParams();
					payload.set('action','tta_threaddesk_screenprint_variations');
					payload.set('nonce',screenprintVariationNonce||'');
					payload.set('productId',String(screenprintProductId||0));
					payload.set('offset',String(Array.isArray(variationRows)?variationRows.length:0));
					payload.set('limit',String(variationPageLimit));
					payload.set('inStockOnly','1');
					payload.set('fields',variationRowsMode==='full'?'full':'keys');
					payload.set('colorKey',String(selectedColor||''));
					payload.set('colorLabel',String(getSelectedColorLabel()||''));
					const response=await fetch(screenprintQuoteAjaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString()});
					const data=await response.json();
					if(!data||!data.success){throw new Error((data&&data.data&&data.data.message)?String(data.data.message):'Unable to load more variants');}
					const rows=(data&&data.data&&Array.isArray(data.data.variations))?data.data.variations:[];
					if(rows.length){variationRows=variationRows.concat(rows);}
					variationReturned=Number((data&&data.data&&data.data.returned)||0);
					const receivedTotal=Number((data&&data.data&&data.data.total)||variationTotal);
					variationTotal=Number.isFinite(receivedTotal)&&receivedTotal>=0?receivedTotal:variationTotal;
					variationHasMore=!!(data&&data.data&&data.data.hasMore);
				}catch(error){
					console.error('[ThreadDesk screenprint variations load]',error);
				}finally{
					variationLoading=false;
				}
			};
			const loadVariationsForSelectedColor=async(fields='full')=>{
				variationLoading=true;
				try{
					const payload=new URLSearchParams();
					payload.set('action','tta_threaddesk_screenprint_variations');
					payload.set('nonce',screenprintVariationNonce||'');
					payload.set('productId',String(screenprintProductId||0));
					payload.set('offset','0');
					payload.set('limit',String(variationPageLimit));
					payload.set('inStockOnly','1');
					payload.set('fields',fields==='keys'?'keys':'full');
					payload.set('colorKey',String(selectedColor||''));
					payload.set('colorLabel',String(getSelectedColorLabel()||''));
					const response=await fetch(screenprintQuoteAjaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString()});
					const data=await response.json();
					if(!data||!data.success){throw new Error((data&&data.data&&data.data.message)?String(data.data.message):'Unable to load variants');}
					variationRows=(data&&data.data&&Array.isArray(data.data.variations))?data.data.variations:[];
					variationRowsMode=fields==='keys'?'keys':'full';
					variationReturned=Number((data&&data.data&&data.data.returned)||variationRows.length||0);
					const receivedTotal=Number((data&&data.data&&data.data.total)||variationRows.length||0);
					variationTotal=Number.isFinite(receivedTotal)&&receivedTotal>=0?receivedTotal:variationRows.length;
					variationHasMore=!!(data&&data.data&&data.data.hasMore);
				}finally{
					variationLoading=false;
				}
			};
			const loadVariationsForColor=async(colorKey,colorLabel)=>{
				const requestedColorKey=String(colorKey||'').trim();
				const requestedColorLabel=String(colorLabel||'').trim();
				const cacheKey=getVariationColorCacheKey(requestedColorKey,requestedColorLabel);
				const cached=variationRowsByColorKey[cacheKey];
				if(cached&&Array.isArray(cached.rows)&&cached.rows.length){
					variationRows=cached.rows.slice();
					variationRowsMode='full';
					variationReturned=Number(cached.returned||variationRows.length||0);
					variationTotal=Number(cached.total||variationRows.length||0);
					variationHasMore=false;
					return variationRows;
				}
				variationLoading=true;
				try{
					const nextRows=[];
					let offset=0;
					let hasMore=true;
					let total=0;
					while(hasMore){
						const payload=new URLSearchParams();
						payload.set('action','tta_threaddesk_screenprint_variations');
						payload.set('nonce',screenprintVariationNonce||'');
						payload.set('productId',String(screenprintProductId||0));
						payload.set('colorKey',requestedColorKey);
						payload.set('colorLabel',requestedColorLabel);
						payload.set('inStockOnly','1');
						payload.set('offset',String(offset));
						payload.set('limit',String(variationPageLimit));
						payload.set('fields','full');
						const response=await fetch(screenprintQuoteAjaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString()});
						const data=await response.json();
						if(!data||!data.success){throw new Error((data&&data.data&&data.data.message)?String(data.data.message):'Unable to load variants');}
						const rows=(data&&data.data&&Array.isArray(data.data.variations))?data.data.variations:[];
						if(rows.length){nextRows.push(...rows);}
						total=Number((data&&data.data&&data.data.total)||nextRows.length||0);
						hasMore=!!(data&&data.data&&data.data.hasMore);
						offset=nextRows.length;
						if(!rows.length){hasMore=false;}
					}
					variationRows=nextRows;
					variationRowsMode='full';
					variationReturned=nextRows.length;
					variationTotal=Number.isFinite(total)&&total>=0?total:nextRows.length;
					variationHasMore=false;
					variationRowsByColorKey[cacheKey]={rows:nextRows.slice(),returned:variationReturned,total:variationTotal,hasMore:false};
					return variationRows;
				}finally{
					variationLoading=false;
				}
			};
			const ensureVariationsReadyForSelectedColor=async()=>{
				const colorLabel=String(getSelectedColorLabel()||'').trim();
				const colorKey=getVariationColorCacheKey(selectedColor,colorLabel);
				if(variationPreloadByColor[colorKey]){await variationPreloadByColor[colorKey];return;}
				variationPreloadByColor[colorKey]=(async()=>{
					await loadVariationsForColor(selectedColor,colorLabel);
				})();
				try{
					await variationPreloadByColor[colorKey];
				}finally{
					delete variationPreloadByColor[colorKey];
				}
			};
			const renderVariationQuantities=()=>{
				if(!quantitiesList){return;}
				quantitiesList.innerHTML='';
				const designSummaries=getSelectedDesignSummaries();
				const rows=getVariationRowsForColor();
				if(quantitiesEmpty){quantitiesEmpty.hidden=rows.length>0;}
				renderQuoteDesignSummary(rows);
				const estimateRows=[];
				const refreshAllEstimates=()=>{
					const totalQuantity=getTotalRequestedQuantity();
					estimateRows.forEach((entry)=>{
						const unitCost=calculateEstimatedUnitCost(totalQuantity,designSummaries,entry&&entry.row&&entry.row.price);
						entry.estimate.textContent=(i18nEstimatedUnitCostLabel||'Est. Cost/Unit')+': '+(null===unitCost?'--':formatCurrency(unitCost));
					});
				};
				rows.forEach((row)=>{
					const item=document.createElement('div');
					item.className='threaddesk-screenprint__quantity-item';
					const details=document.createElement('div');
					details.className='threaddesk-screenprint__quantity-details';
					details.textContent=String((row&&row.size)||'N/A');
					const stock=document.createElement('div');
					stock.className='threaddesk-screenprint__quantity-stock';
					const inventoryValue=(row&&row.inventory)!==undefined?(row&&row.inventory):'--';
					stock.textContent=i18nInventoryLabel+': '+String(inventoryValue);
					const inputWrap=document.createElement('label');
					inputWrap.className='threaddesk-screenprint__quantity-input-wrap';
					inputWrap.textContent=i18nQuantityLabel;
					const input=document.createElement('input');
					input.type='number';
					input.min='0';
					input.step='1';
					input.value='0';
					input.className='threaddesk-screenprint__quantity-input';
					const stockLimit=Number(inventoryValue);
					if(Number.isFinite(stockLimit)&&stockLimit>=0){input.max=String(Math.floor(stockLimit));}
					const variationId=String((row&&row.variationId)||0);
					input.setAttribute('data-threaddesk-screenprint-variation-id',variationId);
					input.name='threaddesk_variation_quantity_'+variationId;
					input.id='threaddesk-screenprint-quantity-'+variationId;
					inputWrap.setAttribute('for',input.id);
					const estimate=document.createElement('div');
					estimate.className='threaddesk-screenprint__quantity-estimate';
					estimateRows.push({row,estimate});
					input.addEventListener('input',()=>{
						const limit=Number(input.max);
						const value=Number(input.value);
						if(Number.isFinite(limit)&&Number.isFinite(value)&&value>limit){input.value=String(limit);}
						refreshAllEstimates();
					});
					inputWrap.appendChild(input);
					item.appendChild(details);
					item.appendChild(stock);
					item.appendChild(inputWrap);
					item.appendChild(estimate);
					quantitiesList.appendChild(item);
				});
				refreshAllEstimates();
			};

			const getApproxSizeLabelForEntry=(entry)=>{
				if(!entry||typeof entry!=='object'){return '--';}
				const placementKey=String(entry.placementKey||'').trim().toLowerCase();
				const sliderRaw=Number(entry.sliderValue||100);
				const sliderValue=Number.isFinite(sliderRaw)?sliderRaw:100;
				const ratioRaw=Number(entry.designRatio||1);
				const ratio=Number.isFinite(ratioRaw)&&ratioRaw>0?ratioRaw:1;
				const sliderMin=60;
				const sliderMax=140;
				const rangeMap={
					full_chest:{min:4.5,max:12.5},
					back:{min:4.5,max:12.5},
					left_chest:{approx:4.0},
					right_chest:{approx:4.0},
					left_sleeve:{approx:4.0},
					right_sleeve:{approx:4.0}
				};
				const range=rangeMap[placementKey]||{approx:4.0};
				let maxDimension=4.0;
				if(Number.isFinite(range.min)&&Number.isFinite(range.max)){
					const clamped=Math.max(sliderMin,Math.min(sliderMax,sliderValue));
					const normalized=(clamped-sliderMin)/(sliderMax-sliderMin);
					maxDimension=Number(range.min)+((Number(range.max)-Number(range.min))*normalized);
				}else{
					maxDimension=Number(range.approx||4.0)*(sliderValue/100);
				}
				let width=maxDimension;
				let height=maxDimension;
				if(ratio>1){height=maxDimension/ratio;}
				else if(ratio>0&&ratio<1){width=maxDimension*ratio;}
				return width.toFixed(1)+'" W × '+height.toFixed(1)+'" H';
			};
			const getSelectedPlacementEntries=()=>{
				if(!selected||!selected.placementsByAngle||typeof selected.placementsByAngle!=='object'){return [];}
				const entries=[];
				Object.keys(selected.placementsByAngle).forEach((angleKey)=>{
					const normalizedEntries=normalizePlacementEntries(selected.placementsByAngle[angleKey],angleKey);
					normalizedEntries.forEach((entry)=>{
						if(!entry||typeof entry!=='object'){return;}
						const paletteCurrent=Array.isArray(entry.paletteCurrent)?entry.paletteCurrent:[];
						const paletteOriginal=Array.isArray(entry.paletteOriginal)?entry.paletteOriginal:[];
						const selectedColors=(paletteCurrent.length?paletteCurrent:paletteOriginal)
							.map((value)=>String(value||'').trim())
							.filter((value)=>value!==''&&value.toLowerCase()!=='transparent');
						entries.push({
							printKey:String(entry.placementKey||'').trim()+'|'+String(entry.designId||0)+'|'+String(Math.round(Number(entry.sliderValue)||100))+'|'+selectedColors.join('|'),
							designId:Number(entry.designId||0),
							designName:String(entry.designName||entry.placementLabel||i18nDesignFallback).trim()||i18nDesignFallback,
							placementLabel:String(entry.placementLabel||angleKey||'Placement').trim()||'Placement',
							approxSize:Math.round(Number(entry.sliderValue)||100),
							approxSizeLabel:getApproxSizeLabelForEntry(entry),
							selectedColors:selectedColors
						});
					});
				});
				return entries;
			};
			const getPlacementOverlaysForRequest=()=>{
				if(!selected||!selected.placementsByAngle||typeof selected.placementsByAngle!=='object'){return {};}
				const overlays={};
				Object.keys(selected.placementsByAngle).forEach((angleKey)=>{
					const entries=normalizePlacementEntries(selected.placementsByAngle[angleKey],angleKey);
					const prepared=[];
					entries.forEach((entry)=>{
						if(!entry||typeof entry!=='object'){return;}
						const src=getEntrySource(entry);
						if(!src){return;}
						prepared.push({
							placementKey:String(entry.placementKey||'').trim(),
							placementLabel:String(entry.placementLabel||angleKey||'Placement').trim()||'Placement',
							designId:Number(entry.designId||0),
							designName:String(entry.designName||entry.placementLabel||i18nDesignFallback).trim()||i18nDesignFallback,
							angle:String(angleKey||'').trim()||'front',
							url:src,
							top:Number(entry.top||50),
							left:Number(entry.left||50),
							width:Number(entry.width||25)
						});
					});
					if(prepared.length){overlays[String(angleKey||'front').trim()||'front']=prepared;}
				});
				return overlays;
			};
			const getQuoteRowsForRequest=()=>{
				const rows=[];
				if(!quantitiesList){return rows;}
				const placementEntries=getSelectedPlacementEntries();
				const placementOverlays=getPlacementOverlaysForRequest();
				const groupedPlacementEntries={};
				placementEntries.forEach((entry)=>{if(!groupedPlacementEntries[entry.printKey]){groupedPlacementEntries[entry.printKey]=entry;}});
				const inputEls=Array.from(quantitiesList.querySelectorAll('input[data-threaddesk-screenprint-variation-id]'));
				inputEls.forEach((input)=>{
					const qty=Math.max(0,Math.floor(Number(input.value||0)));
					if(!qty){return;}
					const variationId=String(input.getAttribute('data-threaddesk-screenprint-variation-id')||'0');
					const row=(Array.isArray(variationRows)?variationRows:[]).find((item)=>String((item&&item.variationId)||0)===variationId)||{};
					const size=String((row&&row.size)||'N/A').trim()||'N/A';
					const color=String((row&&row.color)||getSelectedColorLabel()||'').trim();
					const garmentName=String((row&&row.garmentName)||'').trim();
					const shortDescription=[garmentName,color,size].filter((part)=>String(part||'').trim()!=='').join(' - ');
					const unitCost=calculateEstimatedUnitCost(getTotalRequestedQuantity(),getSelectedDesignSummaries(),row&&row.price);
					const leftView=String((images&&images.left)||'').trim();
					const rightView=String((images&&images.right)||'').trim();
					const sideView=String((images&&images.side)||leftView||rightView||'').trim();
					const sideLabel=String((images&&images.sideLabel)||'left').toLowerCase()==='right'?'right':'left';
					rows.push({
						variationId:Number(variationId||0),
						productSku:String((row&&row.productSku)||'').trim(),
						productShortDescription:shortDescription,
						qty:qty,
						estimatedUnitCost:null===unitCost?0:Number(unitCost),
						placements:Object.values(groupedPlacementEntries).map((entry)=>({placementLabel:entry.placementLabel,designName:entry.designName,designId:entry.designId,approxSize:Number(entry.approxSize||100),approxSizeLabel:entry.approxSizeLabel||String(entry.approxSize||100)+'%',selectedColors:Array.isArray(entry.selectedColors)?entry.selectedColors:[]})),
						placementOverlays:placementOverlays,
						mockups:{front:String((images&&images.front)||'').trim(),left:leftView||rightView,right:rightView||leftView,rightSource:rightView?'right':'left',side:sideView,sideLabel:sideLabel,back:String((images&&images.back)||'').trim()}
					});
				});
				return rows;
			};

			const openAuthLoginPanel=()=>{
				const loginTrigger=document.querySelector('[data-threaddesk-auth="login"]');
				if(loginTrigger&&typeof loginTrigger.click==='function'){loginTrigger.click();return;}
				const authModal=document.querySelector('.threaddesk-auth-modal');
				if(!authModal){return;}
				authModal.classList.add('is-active');
				authModal.setAttribute('aria-hidden','false');
				document.body.classList.add('threaddesk-modal-open');
				authModal.querySelectorAll('[data-threaddesk-auth-panel]').forEach((panel)=>{panel.classList.remove('is-active');panel.setAttribute('aria-hidden','true');});
				authModal.querySelectorAll('[data-threaddesk-auth-tab]').forEach((tab)=>{tab.classList.remove('is-active');tab.setAttribute('aria-selected','false');});
				const loginPanel=authModal.querySelector('[data-threaddesk-auth-panel="login"]');
				if(loginPanel){loginPanel.classList.add('is-active');loginPanel.setAttribute('aria-hidden','false');}
				const loginTab=authModal.querySelector('[data-threaddesk-auth-tab="login"]');
				if(loginTab){loginTab.classList.add('is-active');loginTab.setAttribute('aria-selected','true');}
			};
			const openQuoteFlowPopup=()=>{
				const overlay=document.createElement('div');
				overlay.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:99999;padding:20px;';
				const panel=document.createElement('div');
				panel.style.cssText='background:#fff;max-width:520px;width:100%;border-radius:8px;padding:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);';
				const heading=document.createElement('h4');
				heading.style.cssText='margin:0 0 8px 0;font-size:18px;line-height:1.3;';
				const text=document.createElement('p');
				text.style.cssText='margin:0 0 14px 0;';
				const input=document.createElement('input');
				input.type='text';
				input.style.cssText='width:100%;padding:10px 12px;border:1px solid #c3c4c7;border-radius:4px;margin:0 0 14px 0;';
				const actions=document.createElement('div');
				actions.style.cssText='display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;';
				const clearActions=()=>{actions.innerHTML='';};
				const close=()=>{overlay.remove();};
				const showNameStep=(onConfirm)=>{
					heading.textContent=i18nQuoteTitlePrompt||'Name your Quote';
					text.textContent='';
					input.value='';
					input.hidden=false;
					clearActions();
					const cancelBtn=document.createElement('button');
					cancelBtn.type='button';
					cancelBtn.className='threaddesk-layout-viewer__back-button';
					cancelBtn.textContent='Cancel';
					const saveBtn=document.createElement('button');
					saveBtn.type='button';
					saveBtn.className='threaddesk-screenprint__quantities-button';
					saveBtn.textContent='Save Quote';
					cancelBtn.addEventListener('click',()=>{close();onConfirm(null);});
					saveBtn.addEventListener('click',()=>{
						const cleaned=String(input.value||'').trim();
						if(!cleaned){window.alert(i18nQuoteTitleRequired||'A quote title is required.');input.focus();return;}
						onConfirm(cleaned);
					});
					actions.appendChild(cancelBtn);
					actions.appendChild(saveBtn);
					window.setTimeout(()=>{input.focus();},10);
				};
				const showSuccessStep=(message)=>{
					heading.textContent=i18nAddToQuoteSuccess||'Quote added successfully!';
					text.textContent=String(message||i18nQuoteSavedContinue||'Quote saved. Continue adding articles to this quote?');
					input.hidden=true;
					clearActions();
					const submitQuoteBtn=document.createElement('button');
					submitQuoteBtn.type='button';
					submitQuoteBtn.className='threaddesk-layout-viewer__back-button';
					submitQuoteBtn.textContent=i18nContinueHere||'SUBMIT QUOTE';
					const addMoreBtn=document.createElement('button');
					addMoreBtn.type='button';
					addMoreBtn.className='threaddesk-screenprint__quantities-button';
					addMoreBtn.textContent=i18nKeepShopping||'ADD MORE TO QUOTE';
					submitQuoteBtn.addEventListener('click',()=>{if(window.localStorage){window.localStorage.removeItem('tta_threaddesk_continue_quote');window.localStorage.removeItem('tta_threaddesk_active_quote_id');}close();});
					addMoreBtn.addEventListener('click',()=>{if(window.localStorage){window.localStorage.setItem('tta_threaddesk_continue_quote','1');}if(screenprintProductsPageUrl){window.location.href=String(screenprintProductsPageUrl);return;}close();});
					actions.appendChild(submitQuoteBtn);
					actions.appendChild(addMoreBtn);
				};
				panel.appendChild(heading);
				panel.appendChild(text);
				panel.appendChild(input);
				panel.appendChild(actions);
				overlay.appendChild(panel);
				document.body.appendChild(overlay);
				return {showNameStep,showSuccessStep,close};
			};
			const submitAddToQuote=async()=>{
				if(!isAuthenticated){openAuthLoginPanel();return;}
				const rows=getQuoteRowsForRequest();
				if(!rows.length){window.alert(i18nAddToQuoteRequiresQty||'Please add at least one quantity before creating a quote.');return;}
				const prints=getSelectedPlacementEntries();
				const payload=new URLSearchParams();
				payload.set('action','tta_threaddesk_screenprint_add_to_quote');
				payload.set('nonce',screenprintQuoteNonce||'');
				payload.set('productId',String(screenprintProductId||0));
				payload.set('layoutId',String((selected&&selected.id)||0));
				payload.set('layoutTitle',String((selected&&selected.title)||''));
				payload.set('selectedColor',String(getSelectedColorLabel()||''));
				payload.set('selectedColorKey',String(selectedColor||''));
				payload.set('rowsJson',JSON.stringify(rows));
				payload.set('printsJson',JSON.stringify(prints));
				const usingSelectedPendingQuote=Number(selectedExistingQuoteId||0)>0;
				let popup=null;
				if(usingSelectedPendingQuote){
					payload.set('existingQuoteId',String(selectedExistingQuoteId));
				}else{
					popup=openQuoteFlowPopup();
					const quoteTitle=await new Promise((resolve)=>{popup.showNameStep(resolve);});
					if(null===quoteTitle){return;}
					payload.set('quoteTitle',quoteTitle);
				}
				const shouldContinueExisting=window.localStorage&&String(window.localStorage.getItem('tta_threaddesk_continue_quote')||'').trim()==='1';
				const activeQuoteId=window.localStorage?String(window.localStorage.getItem('tta_threaddesk_active_quote_id')||'').trim():'';
				if(!usingSelectedPendingQuote&&shouldContinueExisting&&activeQuoteId){payload.set('existingQuoteId',activeQuoteId);}
				if(addToQuoteButton){addToQuoteButton.disabled=true;}
				try{
					const response=await fetch(screenprintQuoteAjaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString()});
					const data=await response.json();
					if(!response.ok||!data||!data.success){throw new Error((data&&data.data&&data.data.message)?data.data.message:(i18nAddToQuoteError||'Unable to add quote right now.'));}
					if(window.localStorage&&data&&data.data&&data.data.quoteId){window.localStorage.setItem('tta_threaddesk_active_quote_id',String(data.data.quoteId));if(!shouldContinueExisting){window.localStorage.removeItem('tta_threaddesk_continue_quote');}}
					if(!popup){popup=openQuoteFlowPopup();}
					popup.showSuccessStep((data&&data.data&&data.data.message)||i18nQuoteSavedContinue||'Quote saved. Continue adding articles to this quote?');
				}catch(error){
					if(popup){popup.close();}
					window.alert((error&&error.message)?error.message:(i18nAddToQuoteError||'Unable to add quote right now.'));
				}finally{
					if(addToQuoteButton){addToQuoteButton.disabled=false;}
				}
			};
			const getSideLabel=()=>String((images&&images.sideLabel)||'left').toLowerCase()==='right'?'right':'left';
			const getAngleTransform=(targetAngle)=>{
				if(targetAngle!=='left'&&targetAngle!=='right'){return 'none';}
				const sideLabel=getSideLabel();
				if(targetAngle==='left'){return sideLabel==='right'?'scaleX(-1)':'none';}
				return sideLabel==='right'?'none':'scaleX(-1)';
			};
			const getAngleImage=(targetAngle)=>{
				const key=String(targetAngle||'front');
				if(key==='right'){return String((images&&images.right)||images.left||images.front||'').trim();}
				if(key==='left'){return String((images&&images.left)||images.right||images.front||'').trim();}
				return String((images&&images[key])||images.front||'').trim();
			};
			const syncAngleThumbs=()=>{angleThumbs.forEach((img)=>{const key=img.getAttribute('data-threaddesk-screenprint-angle-image')||'front';img.src=getAngleImage(key);img.style.transform=getAngleTransform(key);});};
			const getSelectedColorLabel=()=>{
				if(!colorPicker){return '';}
				const colorButtons=Array.from(colorPicker.querySelectorAll('[data-threaddesk-screenprint-open-color]'));
				for(let i=0;i<colorButtons.length;i++){
					const button=colorButtons[i];
					if(String(button.getAttribute('data-threaddesk-screenprint-open-color')||'').trim()!==selectedColor){continue;}
					return String(button.getAttribute('aria-label')||'').trim();
				}
				return '';
			};
			const createStrongPrefixLabel=(prefix,value)=>{
				const fragment=document.createDocumentFragment();
				const strong=document.createElement('strong');
				strong.textContent=String(prefix||'').trim()+':';
				fragment.appendChild(strong);
				fragment.appendChild(document.createTextNode(' '+String(value||'').trim()));
				return fragment;
			};
			const renderSelectedColorLabel=()=>{
				if(!selectedColorLabel){return;}
				const label=getSelectedColorLabel();
				selectedColorLabel.replaceChildren(createStrongPrefixLabel(i18nSelectedColorPrefix||'Color',label||'--'));
			};
			const renderSelectedLayoutLabel=(title)=>{
				if(!selectedLabel){return;}
				selectedLabel.replaceChildren(createStrongPrefixLabel(i18nSelectedPrefix||'LAYOUT',title||'--'));
			};
			const getEntrySource=(entry)=>String((entry&&entry.__recoloredSource) || (entry&&entry.url) || (entry&&entry.sourceUrl)|| (entry&&entry.designUrl) || (entry&&entry.previewUrl) || (entry&&entry.preview) || (entry&&entry.imageUrl) || (entry&&entry.mockupUrl) || (entry&&entry.svgUrl) || '').trim();
			const normalizeHexColor=(value)=>{
				const raw=String(value||'').trim();
				if(raw.toLowerCase()==='transparent'){return 'transparent';}
				if(!raw){return '';}
				const hex=raw.charAt(0)==='#'?raw:('#'+raw);
				return /^#[0-9a-fA-F]{6}$/.test(hex)?hex.toUpperCase():'';
			};
			const escapeRegex=(value)=>String(value||'').replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
			const decodeSvgDataUrl=(source)=>{
				const raw=String(source||'').trim();
				const marker='data:image/svg+xml,';
				const markerBase64='data:image/svg+xml;base64,';
				if(raw.indexOf(markerBase64)===0){
					try{return atob(raw.substring(markerBase64.length));}catch(e){return ''; }
				}
				if(raw.indexOf(marker)===0){
					try{return decodeURIComponent(raw.substring(marker.length));}catch(e){return raw.substring(marker.length);}
				}
				if(raw.indexOf('<svg')!==-1){return raw;}
				return '';
			};
			const encodeSvgDataUrl=(svgMarkup)=>'data:image/svg+xml,'+encodeURIComponent(String(svgMarkup||''));
			const parsePaletteColor=(value)=>{
				const token=String(value||'').trim();
				if(!token){return null;}
				if(token.toLowerCase()==='transparent'){return {token:'transparent',rgb:[255,255,255],alpha:0};}
				const hex=normalizeHexColor(token);
				if(!hex){return null;}
				return {token:hex,rgb:[parseInt(hex.substring(1,3),16),parseInt(hex.substring(3,5),16),parseInt(hex.substring(5,7),16)],alpha:255};
			};
			const recolorRasterEntry=(entry,originalSource,paletteBase,paletteCurrent)=>{
				const sourceParsed=paletteBase.map(parsePaletteColor).filter(Boolean);
				const targetParsed=paletteCurrent.map(parsePaletteColor).filter(Boolean);
				if(!sourceParsed.length||sourceParsed.length!==targetParsed.length){return;}
				let changed=false;
				for(let i=0;i<sourceParsed.length;i++){if(sourceParsed[i].token!==targetParsed[i].token){changed=true;break;}}
				if(!changed){entry.__recoloredSource=originalSource;entry.url=originalSource;return;}
				const cacheKey=originalSource+'|'+sourceParsed.map((item)=>item.token).join(',')+'|'+targetParsed.map((item)=>item.token).join(',');
				if(!entry.__recolorCache||typeof entry.__recolorCache!=='object'){entry.__recolorCache={};}
				if(entry.__recolorCache[cacheKey]){entry.__recoloredSource=entry.__recolorCache[cacheKey];entry.url=entry.__recoloredSource;return;}
				const img=new Image();
				img.crossOrigin='anonymous';
				img.addEventListener('load',()=>{
					try{
						const canvas=document.createElement('canvas');
						canvas.width=img.naturalWidth||img.width;
						canvas.height=img.naturalHeight||img.height;
						const ctx=canvas.getContext('2d',{willReadFrequently:true});
						if(!ctx){return;}
						ctx.drawImage(img,0,0);
						const imageData=ctx.getImageData(0,0,canvas.width,canvas.height);
						const pixels=imageData.data;
						for(let i=0;i<pixels.length;i+=4){
							if(pixels[i+3]===0){continue;}
							let bestIndex=0;
							let bestScore=Number.POSITIVE_INFINITY;
							for(let c=0;c<sourceParsed.length;c++){
								const dr=pixels[i]-sourceParsed[c].rgb[0];
								const dg=pixels[i+1]-sourceParsed[c].rgb[1];
								const db=pixels[i+2]-sourceParsed[c].rgb[2];
								const score=(dr*dr)+(dg*dg)+(db*db);
								if(score<bestScore){bestScore=score;bestIndex=c;}
							}
							if(targetParsed[bestIndex].alpha===0){pixels[i]=255;pixels[i+1]=255;pixels[i+2]=255;pixels[i+3]=0;}
							else{pixels[i]=targetParsed[bestIndex].rgb[0];pixels[i+1]=targetParsed[bestIndex].rgb[1];pixels[i+2]=targetParsed[bestIndex].rgb[2];pixels[i+3]=Math.max(pixels[i+3],targetParsed[bestIndex].alpha);}
						}
						ctx.putImageData(imageData,0,0);
						const recolored=canvas.toDataURL('image/png');
						entry.__recolorCache[cacheKey]=recolored;
						entry.__recoloredSource=recolored;
						entry.url=recolored;
						render();
					}catch(e){}
				});
				img.addEventListener('error',()=>{});
				img.src=originalSource;
			};
			const applyEntryPalette=(entry)=>{
				if(!entry||typeof entry!=='object'){return;}
				const paletteBase=Array.isArray(entry.paletteBase)?entry.paletteBase:[];
				const paletteCurrent=Array.isArray(entry.paletteCurrent)?entry.paletteCurrent:[];
				if(!paletteBase.length||!paletteCurrent.length){return;}
				const originalSource=String(entry.__paletteSource||entry.baseUrl||entry.url||entry.sourceUrl||entry.designUrl||entry.previewUrl||entry.preview||'').trim();
				if(!originalSource){return;}
				entry.__paletteSource=originalSource;
				const svgMarkup=decodeSvgDataUrl(originalSource);
				if(svgMarkup){
					let nextMarkup=svgMarkup;
					for(let i=0;i<Math.min(paletteBase.length,paletteCurrent.length);i++){
						const from=normalizeHexColor(paletteBase[i]);
						const to=normalizeHexColor(paletteCurrent[i]);
						if(!from||!to||from===to){continue;}
						nextMarkup=nextMarkup.replace(new RegExp(escapeRegex(from),'gi'),to);
					}
					entry.__recoloredSource=encodeSvgDataUrl(nextMarkup);
					entry.url=entry.__recoloredSource;
					return;
				}
				recolorRasterEntry(entry,originalSource,paletteBase,paletteCurrent);
			};
			const setActivePlacement=(placementKey)=>{
				activePlacementKey=String(placementKey||'').trim();
				if(!selectedDesignList){
					if(activePaletteEditor&&activePaletteEditor.placementKey!==activePlacementKey){activePaletteEditor=null;}
					return;
				}
				const items=Array.from(selectedDesignList.querySelectorAll('.threaddesk-screenprint__selected-design-item'));
				const hasMatch=!!activePlacementKey&&items.some((item)=>String(item.getAttribute('data-threaddesk-screenprint-placement-key')||'').trim()===activePlacementKey);
				if(!hasMatch){activePlacementKey='';}
				if(activePaletteEditor&&activePaletteEditor.placementKey!==activePlacementKey){activePaletteEditor=null;}
				selectedDesignList.classList.toggle('has-active-placement',!!activePlacementKey);
				items.forEach((item)=>{
					const key=String(item.getAttribute('data-threaddesk-screenprint-placement-key')||'').trim();
					const isActive=!!activePlacementKey&&key===activePlacementKey;
					item.classList.toggle('is-active',isActive);
					const button=item.querySelector('.threaddesk-screenprint__selected-design-option');
					if(button){button.classList.toggle('is-active',isActive);}
				});
			};
			const getPlacementEntry=(map,targetAngle,placementKey)=>{
				const key=String(placementKey||'').trim();
				if(!key){return null;}
				const candidates=[targetAngle];
				if(targetAngle==='left'){candidates.push('side');}
				if(targetAngle==='side'){candidates.push('left');}
				for(let i=0;i<candidates.length;i++){
					const candidate=candidates[i];
					const raw=map&&Object.prototype.hasOwnProperty.call(map,candidate)?map[candidate]:null;
					const entries=normalizePlacementEntries(raw,candidate);
					for(let j=0;j<entries.length;j++){
						if(String(entries[j]&&entries[j].placementKey||'').trim()===key){return entries[j];}
					}
				}
				return null;
			};
			const renderSelectedDesignPreview=(map)=>{
				if(!selectedDesignList||!selectedDesignEmpty){return;}
				selectedDesignList.innerHTML='';
				const grouped={};
				const order=[];
				const byAngle=(map&&typeof map==='object')?map:{};
				Object.keys(byAngle).forEach((angleKey)=>{
					const raw=byAngle[angleKey];
					const items=normalizePlacementEntries(raw,angleKey);
					items.forEach((entry)=>{
						if(!entry||typeof entry!=='object'){return;}
						const key=String(entry.placementKey||'').trim();
						if(!key){return;}
						if(!grouped[key]){entry.__angleKey=angleKey;grouped[key]=entry;order.push(key);}
					});
				});
				let count=0;
				order.forEach((placementKey)=>{
					const entry=grouped[placementKey];
					const src=getEntrySource(entry);
					if(!src){return;}
					const title=String(entry.designName||entry.placementLabel||i18nDesignFallback).trim()||i18nDesignFallback;
					const placementLabel=String(entry.placementLabel||entry.placementKey||'').trim()||'Placement';
					const itemWrap=document.createElement('div');
					itemWrap.className='threaddesk-screenprint__selected-design-item';
					itemWrap.setAttribute('data-threaddesk-screenprint-placement-key',String(entry.placementKey||'').trim());
					const item=document.createElement('button');
					item.type='button';
					item.className='threaddesk-layout-viewer__design-option threaddesk-screenprint__selected-design-option';
					item.setAttribute('data-threaddesk-tooltip',i18nAdjust);
					item.setAttribute('aria-label',i18nAdjust+' '+title);
					const img=document.createElement('img');
					img.className='threaddesk-layout-viewer__design-option-image';
					img.src=src;
					img.alt='';
					img.setAttribute('aria-hidden','true');
					const name=document.createElement('span');
					name.className='threaddesk-layout-viewer__design-option-title';
					name.textContent=title;
					const placement=document.createElement('span');
					placement.className='threaddesk-layout-viewer__placement-option threaddesk-screenprint__active-placement-option';
					placement.setAttribute('aria-hidden','true');
					placement.textContent=placementLabel;
					const adjustPalette=document.createElement('div');
					adjustPalette.className='threaddesk-layout-viewer__adjust-palette threaddesk-screenprint__active-adjust-palette';
					const paletteCurrent=Array.isArray(entry.paletteCurrent)?entry.paletteCurrent:[];
					const paletteBase=Array.isArray(entry.paletteBase)?entry.paletteBase:[];
					const placementKeyValue=String(entry.placementKey||'').trim();
					const adjustPaletteOptions=document.createElement('div');
					adjustPaletteOptions.className='threaddesk-layout-viewer__adjust-palette-options threaddesk-screenprint__active-adjust-palette-options';
					paletteCurrent.forEach((rawColor,colorIndex)=>{
						const color=String(rawColor||'').trim();
						if(!color){return;}
						const dot=document.createElement('button');
						dot.type='button';
						dot.className='threaddesk-layout-viewer__palette-dot';
						dot.setAttribute('aria-label','Adjust color '+String(colorIndex+1));
						if(color==='transparent'){dot.classList.add('is-transparent');}
						else{dot.style.setProperty('--threaddesk-layout-palette-color',color);}
						dot.addEventListener('click',(event)=>{
							event.preventDefault();
							event.stopPropagation();
							setActivePlacement(placementKeyValue);
							if(activePaletteEditor&&activePaletteEditor.placementKey===placementKeyValue&&Number(activePaletteEditor.colorIndex)===colorIndex){
								activePaletteEditor=null;
							}else{
								activePaletteEditor={placementKey:placementKeyValue,colorIndex:colorIndex};
							}
							render();
						});
						adjustPalette.appendChild(dot);
					});
					const activeEditor=activePaletteEditor&&activePaletteEditor.placementKey===placementKeyValue?Number(activePaletteEditor.colorIndex):-1;
					if(activeEditor>=0&&paletteCurrent[activeEditor]!==undefined){
						const optionPool=[];
						screenprintPaletteOptionSet.forEach((raw)=>{const value=String(raw||'').trim();if(value&&!optionPool.includes(value)){optionPool.push(value);}});
						paletteBase.forEach((raw)=>{const value=String(raw||'').trim();if(value&&!optionPool.includes(value)){optionPool.push(value);}});
						paletteCurrent.forEach((raw)=>{const value=String(raw||'').trim();if(value&&!optionPool.includes(value)){optionPool.push(value);}});
						if(!optionPool.includes('transparent')){optionPool.push('transparent');}
						optionPool.forEach((choice)=>{
							const choiceBtn=document.createElement('button');
							choiceBtn.type='button';
							choiceBtn.className='threaddesk-layout-viewer__adjust-palette-choice';
							if(choice==='transparent'){choiceBtn.classList.add('is-transparent');}
							else{choiceBtn.style.setProperty('--threaddesk-layout-palette-choice-color',choice);}
							if(String(paletteCurrent[activeEditor]||'').trim()===choice){choiceBtn.classList.add('is-active');}
							choiceBtn.setAttribute('aria-label','Set color to '+choice);
							choiceBtn.addEventListener('click',(event)=>{
								event.preventDefault();
								event.stopPropagation();
								if(!Array.isArray(entry.paletteCurrent)){entry.paletteCurrent=[];}
								entry.paletteCurrent[activeEditor]=choice;
								applyEntryPalette(entry);
								activePaletteEditor=null;
								render();
							});
							adjustPaletteOptions.appendChild(choiceBtn);
						});
						if(adjustPaletteOptions.childElementCount){
							adjustPaletteOptions.classList.add('is-open');
							adjustPaletteOptions.removeAttribute('hidden');
							adjustPaletteOptions.setAttribute('aria-hidden','false');
						}
					}
					const sizeReading=document.createElement('p');
					sizeReading.className='threaddesk-layout-viewer__size-reading threaddesk-screenprint__active-size-reading';
					sizeReading.setAttribute('aria-hidden','true');
					const sliderValue=Number(entry.sliderValue||100);
					sizeReading.textContent=i18nApproxSizePrefix+': '+getApproxSizeLabelForEntry(entry);
					const saveChangesBtn=document.createElement('button');
					saveChangesBtn.type='button';
					saveChangesBtn.className='threaddesk-screenprint__active-save-changes';
					saveChangesBtn.textContent='SAVE CHANGES';
					saveChangesBtn.setAttribute('aria-label','Save changes');
					saveChangesBtn.addEventListener('click',(event)=>{
						event.preventDefault();
						event.stopPropagation();
						setActivePlacement('');
						render();
					});
					item.appendChild(img);
					item.appendChild(name);
					itemWrap.appendChild(item);
					itemWrap.appendChild(placement);
					itemWrap.appendChild(adjustPalette);
					itemWrap.appendChild(adjustPaletteOptions);
					itemWrap.appendChild(sizeReading);
					itemWrap.appendChild(saveChangesBtn);
					item.addEventListener('click',()=>{
						if(!selected){return;}
						const currentPlacementKey=String(entry.placementKey||'').trim();
						if(!currentPlacementKey){return;}
						const placementAngle=String(entry.__angleKey||angle||'front').toLowerCase();
						if(placementAngle==='front'||placementAngle==='back'||placementAngle==='left'||placementAngle==='right'||placementAngle==='side'){angle=placementAngle;}
						setActivePlacement(currentPlacementKey);
						render();
					});
					selectedDesignList.appendChild(itemWrap);
					count++;
				});
				setActivePlacement(activePlacementKey);
				selectedDesignEmpty.style.display=count?'none':'block';
			};
			const setupCollapsedColors=()=>{
				if(!colorPicker){return;}
				const colorButtons=Array.from(colorPicker.querySelectorAll('[data-threaddesk-screenprint-open-color]'));
				if(!colorButtons.length){if(showAllWrap){showAllWrap.classList.add('hide-colors--hidden');showAllWrap.hidden=true;}return;}
				if(colorsExpanded){
					if(showAllWrap){showAllWrap.classList.add('hide-colors--hidden');showAllWrap.hidden=true;showAllWrap.style.display='none';showAllWrap.style.pointerEvents='none';showAllWrap.setAttribute('aria-hidden','true');if(showAllBtn){showAllBtn.disabled=true;}}
					return;
				}
				colorButtons.forEach((button)=>{
					button.classList.remove('threaddesk-screenprint__open-color--collapsed','threaddesk-screenprint__open-color--revealed');
				});
				if(showAllWrap){showAllWrap.classList.remove('is-hiding','hide-colors--hidden');showAllWrap.hidden=true;showAllWrap.style.display='';showAllWrap.style.pointerEvents='';showAllWrap.setAttribute('aria-hidden','false');if(showAllBtn){showAllBtn.disabled=false;}}
				let secondRowTop=null;
				let thirdRowTop=null;
				for(let i=0;i<colorButtons.length;i++){
					const top=colorButtons[i].offsetTop;
					if(secondRowTop===null&&top!==colorButtons[0].offsetTop){secondRowTop=top;continue;}
					if(secondRowTop!==null&&top!==secondRowTop){thirdRowTop=top;break;}
				}
				if(thirdRowTop===null){return;}
				colorButtons.forEach((button)=>{
					if(button.offsetTop>=thirdRowTop){button.classList.add('threaddesk-screenprint__open-color--collapsed');}
				});
				if(showAllWrap){showAllWrap.classList.remove('hide-colors--hidden');showAllWrap.style.display='';showAllWrap.style.pointerEvents='';showAllWrap.hidden=false;showAllWrap.setAttribute('aria-hidden','false');if(showAllBtn){showAllBtn.disabled=false;}}
			};
			const expandColors=()=>{
				if(!colorPicker){return;}
				colorsExpanded=true;
				colorPicker.querySelectorAll('.threaddesk-screenprint__open-color--collapsed').forEach((button)=>{
					button.classList.remove('threaddesk-screenprint__open-color--collapsed');
					button.classList.add('threaddesk-screenprint__open-color--revealed');
					window.setTimeout(()=>button.classList.remove('threaddesk-screenprint__open-color--revealed'),350);
				});
				if(showAllWrap){
					showAllWrap.classList.add('is-hiding');
					showAllWrap.setAttribute('aria-hidden','true');
					showAllWrap.style.pointerEvents='none';
					if(showAllBtn){showAllBtn.disabled=true;}
					window.setTimeout(()=>{showAllWrap.hidden=true;showAllWrap.style.display='none';showAllWrap.classList.remove('is-hiding');showAllWrap.classList.add('hide-colors--hidden');},300);
				}
			};
			let screenprintInitialized=false;
			let screenprintEventsBound=false;
			let screenprintBootstrapLoading=false;
			const initScreenprint=()=>{
				if(screenprintInitialized){return;}
				screenprintInitialized=true;
				if(!screenprintEventsBound){
					screenprintEventsBound=true;
					window.addEventListener('resize',()=>{if(showAllWrap&&!showAllWrap.hidden&&!colorsExpanded){setupCollapsedColors();}});
					window.addEventListener('resize',syncScreenprintPanelHeight);
					if(showAllBtn){
						showAllBtn.addEventListener('click',(event)=>{
							event.preventDefault();
							initScreenprint();
							expandColors();
						});
					}
					root.querySelectorAll('[data-threaddesk-screenprint-close]').forEach((el)=>{
						el.addEventListener('click',(event)=>{
							if(event){
								event.preventDefault();
								event.stopPropagation();
							}
							closeScreenprintModal();
						});
					});
					root.querySelectorAll('[data-threaddesk-screenprint-back]').forEach((el)=>{
						el.addEventListener('click',()=>{setStep('chooser');});
					});
					if(backToQuotesButton){
						backToQuotesButton.addEventListener('click',()=>{setStep('quotes');});
					}
					if(openQuantitiesButton){
						openQuantitiesButton.addEventListener('click',async(event)=>{
							event.preventDefault();
							openQuantitiesButton.disabled=true;
							try{
								await loadVariationsForColor(selectedColor,getSelectedColorLabel());
								renderVariationQuantities();
								setStep('quantities');
							}finally{
								openQuantitiesButton.disabled=false;
							}
						});
					}
					if(addToQuoteButton){
						addToQuoteButton.addEventListener('click',(event)=>{
							event.preventDefault();
							submitAddToQuote();
						});
					}
					root.addEventListener('click',async(event)=>{
						const trigger=event.target&&event.target.closest?event.target.closest('[data-threaddesk-screenprint-open-quantities]'):null;
						if(!trigger){return;}
						event.preventDefault();
						trigger.disabled=true;
						try{
							await loadVariationsForColor(selectedColor,getSelectedColorLabel());
							renderVariationQuantities();
							setStep('quantities');
						}finally{
							trigger.disabled=false;
						}
					});
					root.querySelectorAll('[data-threaddesk-screenprint-back-to-viewer]').forEach((el)=>{
						el.addEventListener('click',()=>{setStep('viewer');});
					});
					root.querySelectorAll('[data-threaddesk-screenprint-angle]').forEach((btn)=>btn.addEventListener('click',()=>{
						angle=btn.getAttribute('data-threaddesk-screenprint-angle')||'front';
						root.querySelectorAll('[data-threaddesk-screenprint-angle]').forEach((item)=>item.classList.remove('is-active'));
						btn.classList.add('is-active');
						render();
					}));
					document.addEventListener('threaddesk:auth-success',()=>{
						if(!screenprintInitialized){return;}
						if(screenprintBootstrapLoading){return;}
						screenprintBootstrapLoading=true;
						loadBootstrapDatasets().finally(()=>{screenprintBootstrapLoading=false;});
					});
					console.debug('[ThreadDesk] screenprint listeners bound');
				}
				renderQuoteOptions();
				renderLayoutOptions();
				window.requestAnimationFrame(setupCollapsedColors);
				if(!screenprintBootstrapLoading){
					screenprintBootstrapLoading=true;
					loadBootstrapDatasets().finally(()=>{screenprintBootstrapLoading=false;});
				}
			};
			syncAngleThumbs();
			renderSelectedColorLabel();
			if(shouldOpenChooser&&modal){
				window.setTimeout(()=>{initScreenprint();openScreenprintChooserModal();},1000);
			}
			const onScreenprintColorClick=(btn)=>{
				initScreenprint();
				selectedColor=String(btn.getAttribute('data-threaddesk-screenprint-open-color')||'').trim();
				images=(imageMap&&imageMap[selectedColor])?imageMap[selectedColor]:{};
				syncAngleThumbs();
				renderSelectedColorLabel();
				root.querySelectorAll('[data-threaddesk-screenprint-open-color]').forEach((item)=>{item.style.boxShadow='none';});
				btn.style.boxShadow='0 0 0 1px #2271b1';
				openScreenprintChooserModal();
				syncCartSelection();
				loadVariationsForColor(selectedColor,getSelectedColorLabel()).catch((error)=>{console.error('[ThreadDesk screenprint color load]',error);});
			};
			root.querySelectorAll('[data-threaddesk-screenprint-open-color]').forEach((btn)=>{
				btn.addEventListener('click',()=>{onScreenprintColorClick(btn);});
			});
			const lockStageRatio=(src)=>{
				if(stageRatioLocked||!stage||!src){return;}
				const probe=new Image();
				probe.addEventListener('load',()=>{
					if(stageRatioLocked||!probe.naturalWidth||!probe.naturalHeight){return;}
					stage.style.aspectRatio=probe.naturalWidth+' / '+probe.naturalHeight;
					stageRatioLocked=true;
				});
				probe.src=src;
			};
			function normalizePlacementEntries(raw, angleKey){
				const angle=String(angleKey||'').trim()||'angle';
				const withFallbackKey=(entry,fallbackKey)=>{
					if(!entry||typeof entry!=='object'){return null;}
					const existingKey=String(entry.placementKey||'').trim();
					if(existingKey){return entry;}
					const labelKey=String(entry.placementLabel||entry.designName||'').trim();
					entry.placementKey=labelKey||fallbackKey;
					return entry;
				};
				if(Array.isArray(raw)){
					return raw.map((entry,index)=>{
						if(entry&&typeof entry==='object'){
							return withFallbackKey(entry,'placement-'+angle+'-'+String(index+1));
						}
						const src=String(entry||'').trim();
						if(!src){return null;}
						return {placementKey:'placement-'+angle+'-'+String(index+1),url:src,placementLabel:'Placement'};
					}).filter(Boolean);
				}
				if(raw&&typeof raw==='object'){
					return Object.entries(raw).map(([entryKey,entry],index)=>{
						const key=String(entryKey||'').trim()||('placement-'+angle+'-'+String(index+1));
						if(entry&&typeof entry==='object'){
							return withFallbackKey(entry,key);
						}
						const src=String(entry||'').trim();
						if(!src){return null;}
						return {placementKey:key,url:src,placementLabel:'Placement'};
					}).filter(Boolean);
				}
				return [];
			};
			const getAngleEntries=(map,targetAngle)=>{
				const candidates=[targetAngle];
				if(targetAngle==='left'){candidates.push('side');}
				if(targetAngle==='side'){candidates.push('left');}
				for(let i=0;i<candidates.length;i++){
					const key=candidates[i];
					const raw=map&&Object.prototype.hasOwnProperty.call(map,key)?map[key]:null;
					const entries=normalizePlacementEntries(raw,key);
					if(entries.length){return entries;}
				}
				return [];
			};
			const renderAngleOverlays=(map)=>{
				root.querySelectorAll('.threaddesk-layout-viewer__angle-overlay').forEach((el)=>el.remove());
				root.querySelectorAll('[data-threaddesk-screenprint-angle]').forEach((btn)=>{
					const targetAngle=btn.getAttribute('data-threaddesk-screenprint-angle')||'front';
					const imageWrap=btn.querySelector('.threaddesk-layout-viewer__angle-image-wrap');
					if(!imageWrap){return;}
					const entries=getAngleEntries(map,targetAngle);
					entries.forEach((entry)=>{
						const src=getEntrySource(entry);
						if(!src){return;}
						const img=document.createElement('img');
						img.className='threaddesk-layout-viewer__angle-overlay';
						img.src=src; img.alt=''; img.setAttribute('aria-hidden','true');
						img.style.top=(Number(entry.top||0)).toFixed(2)+'%';
						img.style.left=(Number(entry.left||0)).toFixed(2)+'%';
						img.style.width=(Number(entry.width||0)).toFixed(2)+'%';
						imageWrap.appendChild(img);
					});
				});
			};
			const render=()=>{
				if(!selected){return;}
				syncScreenprintPanelHeight();
				const map=selected.placementsByAngle||{};
				const entries=getAngleEntries(map,angle);
				main.src=getAngleImage(angle);
				main.style.transform=getAngleTransform(angle);
				lockStageRatio(main.src);
				main.style.display=main.src?'block':'none';
				overlayWrap.innerHTML='';
				entries.forEach((entry)=>{
					const src=getEntrySource(entry);
					if(!src){return;}
					const placementKey=String(entry.placementKey||'').trim();
					const img=document.createElement('img');
					img.className='threaddesk-screenprint__overlay-design';
					img.setAttribute('data-threaddesk-screenprint-placement-key',placementKey);
					img.src=src; img.alt=''; img.setAttribute('aria-hidden','true');
					img.style.position='absolute';
					img.style.top=(Number(entry.top||0)).toFixed(2)+'%';
					img.style.left=(Number(entry.left||0)).toFixed(2)+'%';
					img.style.width=(Number(entry.width||0)).toFixed(2)+'%';
					img.style.transform='translate(-50%, -50%)';
					img.classList.toggle('is-active',!!activePlacementKey&&placementKey===activePlacementKey);
					img.addEventListener('mousedown',(event)=>{
						event.preventDefault();
						setActivePlacement(placementKey);
						dragState={placementKey:placementKey};
					});
					img.addEventListener('touchstart',(event)=>{
						const touch=event.touches&&event.touches[0];
						if(!touch){return;}
						event.preventDefault();
						setActivePlacement(placementKey);
						dragState={placementKey:placementKey};
					});
					img.addEventListener('click',()=>setActivePlacement(placementKey));
					overlayWrap.appendChild(img);
				});
				renderAngleOverlays(map);
				renderSelectedDesignPreview(map);
			};
			const updateDragPosition=(clientX,clientY)=>{
				if(!selected||!dragState||!stage){return;}
				const map=selected.placementsByAngle||{};
				const entry=getPlacementEntry(map,angle,dragState.placementKey);
				if(!entry){return;}
				const rect=stage.getBoundingClientRect();
				if(!rect.width||!rect.height){return;}
				const x=Math.min(Math.max(clientX-rect.left,0),rect.width);
				const y=Math.min(Math.max(clientY-rect.top,0),rect.height);
				entry.left=(x/rect.width)*100;
				entry.top=(y/rect.height)*100;
				render();
			};
			document.addEventListener('mousemove',(event)=>{if(dragState){updateDragPosition(event.clientX,event.clientY);}});
			document.addEventListener('touchmove',(event)=>{if(!dragState){return;} const touch=event.touches&&event.touches[0]; if(!touch){return;} event.preventDefault(); updateDragPosition(touch.clientX,touch.clientY);},{passive:false});
			document.addEventListener('mouseup',()=>{dragState=null;});
			document.addEventListener('touchend',()=>{dragState=null;});
			document.addEventListener('touchcancel',()=>{dragState=null;});
				const renderQuoteOptions=()=>{
					if(!quoteOptions){return;}
					quoteOptions.innerHTML='';
					const createBtn=document.createElement('button');
					createBtn.type='button';
					createBtn.className='threaddesk-screenprint-option threaddesk-screenprint-option--create threaddesk__card threaddesk__card--design';
					const createPreview=document.createElement('div');
					createPreview.className='threaddesk__card-design-preview';
					const createPlaceholder=document.createElement('span');
					createPlaceholder.className='threaddesk-layout-modal__image-fallback';
					createPlaceholder.textContent='+';
					createPreview.appendChild(createPlaceholder);
					const createTitle=document.createElement('h5');
					createTitle.className='threaddesk-screenprint-option__title';
					createTitle.textContent=i18nCreateNewQuote||'CREATE NEW QUOTE';
					const createHint=document.createElement('p');
					createHint.className='threaddesk__card-design-color-count';
					createHint.textContent=i18nCreateNewQuoteHint||'Start a brand new quote for this product.';
					createBtn.appendChild(createPreview);
					createBtn.appendChild(createTitle);
					createBtn.appendChild(createHint);
					createBtn.addEventListener('click',()=>{
						selectedExistingQuoteId=0;
						setStep('chooser');
					});
					quoteOptions.appendChild(createBtn);
					const quotes=Array.isArray(pendingQuotes)?pendingQuotes:[];
					if(quoteEmptyState){quoteEmptyState.hidden=quotes.length>0;}
					quotes.forEach((quote)=>{
						const quoteId=Number(quote&&quote.id||0);
						if(!quoteId){return;}
						const designNames=Array.isArray(quote&&quote.designNames)?quote.designNames.filter((name)=>String(name||'').trim()!==''):[];
						const designCount=Number(quote&&quote.designCount||designNames.length||0);
						const btn=document.createElement('button');
						btn.type='button';
						btn.className='threaddesk-screenprint-option threaddesk__card threaddesk__card--design';
						const preview=document.createElement('div');
						preview.className='threaddesk__card-design-preview';
						const designList=document.createElement('ul');
						designList.className='threaddesk-screenprint-option__design-list';
						if(designNames.length){
							designNames.forEach((designName)=>{
								const listItem=document.createElement('li');
								listItem.textContent=String(designName).trim();
								designList.appendChild(listItem);
							});
						}else{
							const listItem=document.createElement('li');
							listItem.textContent=i18nNoDesignsInQuote||'No designs in this quote yet.';
							designList.appendChild(listItem);
						}
						preview.appendChild(designList);
						const title=document.createElement('h5');
						title.className='threaddesk-screenprint-option__title';
						title.textContent=String((quote&&quote.title)||'').trim()||((i18nPendingQuotePrefix||'PENDING QUOTE')+' #'+String(quoteId));
						const meta=document.createElement('p');
						meta.className='threaddesk__card-design-color-count';
						meta.textContent=(i18nDesignCountLabel||'Design count')+': '+String(designCount);
						btn.appendChild(preview);
						btn.appendChild(title);
						btn.appendChild(meta);
						btn.addEventListener('click',()=>{
							selectedExistingQuoteId=quoteId;
							setStep('chooser');
						});
						quoteOptions.appendChild(btn);
					});
				};
				const renderLayoutOptions=()=>{
					options.innerHTML='';
					{
					const createBtn=document.createElement('button');
				createBtn.type='button';
				createBtn.className='threaddesk-screenprint-option threaddesk-screenprint-option--create threaddesk__card threaddesk__card--design';
				const createPreview=document.createElement('div');
				createPreview.className='threaddesk__card-design-preview';
				const createPlaceholder=document.createElement('span');
				createPlaceholder.className='threaddesk-layout-modal__image-fallback';
				createPlaceholder.textContent='+';
				createPreview.appendChild(createPlaceholder);
				const createTitle=document.createElement('h5');
				createTitle.className='threaddesk-screenprint-option__title';
				createTitle.textContent=i18nCreateLayout;
				const createMeta=document.createElement('p');
				createMeta.className='threaddesk__card-design-color-count';
				createMeta.textContent=i18nCreateLayoutHint;
				createBtn.appendChild(createPreview);
				createBtn.appendChild(createTitle);
				createBtn.appendChild(createMeta);
					createBtn.addEventListener('click',()=>{
						if(typeof createBtn.blur==='function'){createBtn.blur();}
						if(modal){modal.classList.remove('is-active');modal.setAttribute('aria-hidden','true');}
						const localScope=root.closest('.product')||document;
						const layoutOpen=
							root.querySelector('[data-threaddesk-screenprint-layout-open]')||
							localScope.querySelector('[data-threaddesk-screenprint-layout-open]')||
							root.querySelector('[data-threaddesk-layout-open]')||
							localScope.querySelector('[data-threaddesk-layout-open]')||
							document.querySelector('[data-threaddesk-layout-open]');
						if(!layoutOpen){return;}
						if(createLayoutCategory){layoutOpen.setAttribute('data-threaddesk-layout-category-open', createLayoutCategory);}
						if(createLayoutCategoryId>0){layoutOpen.setAttribute('data-threaddesk-layout-category-id-open', String(createLayoutCategoryId));}
						document.body.classList.remove('threaddesk-modal-open');
						window.setTimeout(()=>{layoutOpen.click();},0);
					});
					options.appendChild(createBtn);
					}
					(layouts||[]).forEach((layout)=>{
				const btn=document.createElement('button');
				btn.type='button';
				btn.className='threaddesk-screenprint-option threaddesk__card threaddesk__card--design';
				const title=(layout.title||'').trim()||('Layout #'+layout.id);
				const entries=Array.isArray(layout.previewEntries)?layout.previewEntries:[];
				const preview=document.createElement('div');
				preview.className='threaddesk__card-design-preview';
				const baseSrc=String(layout.previewBaseUrl||'').trim();
				if(baseSrc){
					const base=document.createElement('img');
					base.className='threaddesk__card-layout-preview-base';
					base.src=baseSrc;
					base.alt='';
					base.setAttribute('aria-hidden','true');
					preview.appendChild(base);
				}
				entries.forEach((entry)=>{
					const overlaySrc=String(entry.url||entry.sourceUrl||entry.designUrl||entry.previewUrl||entry.preview||'').trim();
					if(!overlaySrc){return;}
					const overlay=document.createElement('img');
					overlay.className='threaddesk__card-layout-preview-overlay';
					overlay.src=overlaySrc;
					overlay.alt='';
					overlay.style.top=(Number(entry.top||50)).toFixed(2)+'%';
					overlay.style.left=(Number(entry.left||50)).toFixed(2)+'%';
					overlay.style.width=(Number(entry.width||25)).toFixed(2)+'%';
					preview.appendChild(overlay);
				});
				if(!preview.childElementCount){
					const fallback=document.createElement('span');
					fallback.className='threaddesk-layout-modal__image-fallback';
					fallback.textContent=i18nNoPreview;
					preview.appendChild(fallback);
				}
				const titleWrap=document.createElement('h5');
				titleWrap.className='threaddesk-screenprint-option__title';
				titleWrap.textContent=title;
				const meta=document.createElement('p');
				meta.className='threaddesk__card-design-color-count';
				const count=document.createElement('span');
				count.textContent=i18nPrintCountLabel+': '+String(Number(layout.printCount||0));
					const status=document.createElement('span');
					status.className='threaddesk__card-design-status threaddesk__card-design-status--'+String(layout.statusKey||'pending');
					status.textContent=String(layout.statusLabel||'Pending').toUpperCase();
					meta.appendChild(count);
					meta.appendChild(status);
				btn.appendChild(preview);
				btn.appendChild(titleWrap);
				btn.appendChild(meta);
				btn.addEventListener('click',()=>{selected=layout; renderSelectedLayoutLabel(title); setStep('viewer'); render(); window.requestAnimationFrame(syncScreenprintPanelHeight);});
					options.appendChild(btn);
					});
					if(emptyState){
						emptyState.textContent=bootstrapLoaded?(isAuthenticated?i18nUserEmpty:i18nGuestEmpty):'Loading layouts…';
						emptyState.hidden=Array.isArray(layouts)&&layouts.length>0;
					}
				};
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	private function get_screenprint_product_images( $product ) {
		$product_id = $product && is_callable( array( $product, 'get_id' ) ) ? (int) $product->get_id() : 0;
		$front_id   = $product_id ? (int) get_post_thumbnail_id( $product_id ) : 0;
		$front_url  = $front_id ? wp_get_attachment_image_url( $front_id, 'large' ) : '';
		$gallery_ids = $product && is_callable( array( $product, 'get_gallery_image_ids' ) ) ? (array) $product->get_gallery_image_ids() : array();
		$side_url   = ! empty( $gallery_ids[0] ) ? wp_get_attachment_image_url( (int) $gallery_ids[0], 'large' ) : '';
		$back_url   = ! empty( $gallery_ids[1] ) ? wp_get_attachment_image_url( (int) $gallery_ids[1], 'large' ) : '';
		$right_url  = ! empty( $gallery_ids[2] ) ? wp_get_attachment_image_url( (int) $gallery_ids[2], 'large' ) : '';
		if ( '' === $front_url ) {
			$front_url = $side_url ? $side_url : $back_url;
		}
		if ( '' === $side_url ) {
			$side_url = $front_url;
		}
		if ( '' === $back_url ) {
			$back_url = $side_url ? $side_url : $front_url;
		}
		if ( '' === $right_url ) {
			$right_url = $side_url ? $side_url : $front_url;
		}

		return array(
			'front' => (string) $front_url,
			'left'  => (string) $side_url,
			'back'  => (string) $back_url,
			'right' => (string) $right_url,
		);
	}


	private function get_current_actor_guest_token() {
		if ( is_user_logged_in() ) {
			return '';
		}

		$token = isset( $_COOKIE['tta_threaddesk_guest_token'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tta_threaddesk_guest_token'] ) ) : '';
		if ( '' === $token && isset( $_REQUEST['tta_threaddesk_guest_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_REQUEST['tta_threaddesk_guest_token'] ) );
		}

		return preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $token );
	}

	private function actor_owns_threaddesk_post( $post, $expected_post_type ) {
		if ( ! $post instanceof WP_Post || $expected_post_type !== $post->post_type ) {
			return false;
		}

		if ( is_user_logged_in() ) {
			return (int) $post->post_author === get_current_user_id();
		}

		$guest_token = $this->get_current_actor_guest_token();
		if ( '' === $guest_token ) {
			return false;
		}

		$owner_token = sanitize_text_field( (string) get_post_meta( $post->ID, 'threaddesk_guest_token', true ) );
		if ( '' === $owner_token ) {
			return false;
		}

		return hash_equals( $owner_token, $guest_token );
	}

	private function build_screenprint_layout_cart_snapshot( $layout_post, $selected_color = '' ) {
		$layout_id = $layout_post instanceof WP_Post ? (int) $layout_post->ID : 0;
		if ( $layout_id <= 0 ) {
			return array();
		}

		$payload_raw = (string) get_post_meta( $layout_id, 'layout_payload', true );
		$payload = json_decode( $payload_raw, true );
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		$placements_by_angle = isset( $payload['placementsByAngle'] ) && is_array( $payload['placementsByAngle'] ) ? $payload['placementsByAngle'] : array();
		$design_ids = array();
		$placement_summary = array();
		foreach ( $placements_by_angle as $angle_entries ) {
			if ( ! is_array( $angle_entries ) ) {
				continue;
			}
			foreach ( $angle_entries as $placement_key => $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$design_id = isset( $entry['designId'] ) ? absint( $entry['designId'] ) : 0;
				if ( $design_id > 0 ) {
					$design_ids[] = $design_id;
				}
				$placement_summary[] = array(
					'placement_key'   => sanitize_key( (string) $placement_key ),
					'placement_label' => isset( $entry['placementLabel'] ) ? sanitize_text_field( (string) $entry['placementLabel'] ) : '',
					'design_id'       => $design_id,
					'design_name'     => isset( $entry['designName'] ) ? sanitize_text_field( (string) $entry['designName'] ) : '',
				);
			}
		}

		$design_ids = array_values( array_unique( array_filter( array_map( 'absint', $design_ids ) ) ) );

		return array(
			'layout_id'          => $layout_id,
			'layout_title'       => sanitize_text_field( (string) get_the_title( $layout_id ) ),
			'layout_status'      => $this->get_layout_status( $layout_id ),
			'selected_color'     => sanitize_key( (string) $selected_color ),
			'design_ids'         => $design_ids,
			'placement_summary'  => $placement_summary,
			'placement_count'    => count( $placement_summary ),
		);
	}

	public function validate_screenprint_cart_selection( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
		$layout_id = isset( $_POST['threaddesk_layout_id'] ) ? absint( $_POST['threaddesk_layout_id'] ) : 0;
		if ( $layout_id <= 0 ) {
			return $passed;
		}

		$layout_post = get_post( $layout_id );
		if ( ! $this->actor_owns_threaddesk_post( $layout_post, 'tta_layout' ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'The selected layout is invalid or no longer belongs to your account.', 'threaddesk' ), 'error' );
			}
			return false;
		}

		$snapshot = $this->build_screenprint_layout_cart_snapshot( $layout_post, isset( $_POST['threaddesk_layout_color'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_layout_color'] ) ) : '' );
		$layout_design_ids = isset( $snapshot['design_ids'] ) && is_array( $snapshot['design_ids'] ) ? $snapshot['design_ids'] : array();
		$posted_design_ids = array();
		if ( isset( $_POST['threaddesk_layout_design_ids'] ) ) {
			$posted_raw = sanitize_text_field( wp_unslash( $_POST['threaddesk_layout_design_ids'] ) );
			$posted_design_ids = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $posted_raw ) ) ) ) );
		}

		if ( ! empty( $posted_design_ids ) ) {
			$invalid_posted_ids = array_diff( $posted_design_ids, $layout_design_ids );
			if ( ! empty( $invalid_posted_ids ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'One or more selected designs are invalid for the chosen layout.', 'threaddesk' ), 'error' );
				}
				return false;
			}
		}

		foreach ( $layout_design_ids as $design_id ) {
			$design_post = get_post( $design_id );
			if ( ! $this->actor_owns_threaddesk_post( $design_post, 'tta_design' ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'One or more designs in the selected layout are not available for your account.', 'threaddesk' ), 'error' );
				}
				return false;
			}
		}

		return $passed;
	}

	public function capture_screenprint_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$layout_id = isset( $_POST['threaddesk_layout_id'] ) ? absint( $_POST['threaddesk_layout_id'] ) : 0;
		if ( $layout_id <= 0 ) {
			return $cart_item_data;
		}

		$layout_post = get_post( $layout_id );
		if ( ! $this->actor_owns_threaddesk_post( $layout_post, 'tta_layout' ) ) {
			return $cart_item_data;
		}

		$selected_color = isset( $_POST['threaddesk_layout_color'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_layout_color'] ) ) : '';
		$snapshot = $this->build_screenprint_layout_cart_snapshot( $layout_post, $selected_color );
		if ( empty( $snapshot ) ) {
			return $cart_item_data;
		}

		$cart_item_data['threaddesk_screenprint'] = $snapshot;
		$cart_item_data['threaddesk_screenprint_signature'] = md5( wp_json_encode( $snapshot ) . '|' . microtime( true ) );

		return $cart_item_data;
	}

	public function restore_screenprint_cart_item_data( $cart_item, $session_values, $cart_item_key ) {
		if ( isset( $session_values['threaddesk_screenprint'] ) && is_array( $session_values['threaddesk_screenprint'] ) ) {
			$cart_item['threaddesk_screenprint'] = $session_values['threaddesk_screenprint'];
		}
		if ( isset( $session_values['threaddesk_screenprint_signature'] ) ) {
			$cart_item['threaddesk_screenprint_signature'] = sanitize_text_field( (string) $session_values['threaddesk_screenprint_signature'] );
		}

		return $cart_item;
	}

	public function render_screenprint_cart_item_display_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['threaddesk_screenprint'] ) || ! is_array( $cart_item['threaddesk_screenprint'] ) ) {
			return $item_data;
		}

		$snapshot = $cart_item['threaddesk_screenprint'];
		$layout_title = isset( $snapshot['layout_title'] ) ? sanitize_text_field( (string) $snapshot['layout_title'] ) : '';
		$selected_color = isset( $snapshot['selected_color'] ) ? sanitize_text_field( (string) $snapshot['selected_color'] ) : '';
		$placement_summary = isset( $snapshot['placement_summary'] ) && is_array( $snapshot['placement_summary'] ) ? $snapshot['placement_summary'] : array();
		$design_summary = array();
		foreach ( $placement_summary as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$placement_label = isset( $entry['placement_label'] ) ? sanitize_text_field( (string) $entry['placement_label'] ) : '';
			$design_name = isset( $entry['design_name'] ) ? sanitize_text_field( (string) $entry['design_name'] ) : '';
			if ( '' === $placement_label && '' === $design_name ) {
				continue;
			}
			$design_summary[] = trim( $placement_label . ( '' !== $design_name ? ': ' . $design_name : '' ) );
		}

		if ( '' !== $layout_title ) {
			$item_data[] = array(
				'key'   => __( 'ThreadDesk layout', 'threaddesk' ),
				'value' => $layout_title,
			);
		}
		if ( '' !== $selected_color ) {
			$item_data[] = array(
				'key'   => __( 'ThreadDesk color', 'threaddesk' ),
				'value' => $selected_color,
			);
		}
		if ( ! empty( $design_summary ) ) {
			$item_data[] = array(
				'key'   => __( 'ThreadDesk placements', 'threaddesk' ),
				'value' => implode( ', ', $design_summary ),
			);
		}

		return $item_data;
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
			<div class="threaddesk-auth-modal" aria-hidden="true" data-threaddesk-auth-default="<?php echo esc_attr( $this->auth_active_panel ); ?>" data-threaddesk-auth-refresh-layouts="<?php echo ( $this->auth_login_success || $this->auth_register_success ) ? '1' : '0'; ?>">
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

		$this->merge_guest_drafts_to_user( $user_id );

		$this->auth_notice = __( 'Registration successful. Please check your email for confirmation.', 'threaddesk' );
	}


	public function render_admin_quotes_page() {
		wp_safe_redirect( admin_url( 'edit.php?post_type=tta_quote' ) );
		exit;
	}

	public function render_admin_users_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );

		echo '<div class="wrap"><h1>' . esc_html__( 'ThreadDesk Users', 'threaddesk' ) . '</h1>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'User/Company', 'threaddesk' ) . '</th><th>' . esc_html__( 'Email', 'threaddesk' ) . '</th><th>' . esc_html__( 'Designs', 'threaddesk' ) . '</th><th>' . esc_html__( 'Layouts', 'threaddesk' ) . '</th><th>' . esc_html__( 'Quotes', 'threaddesk' ) . '</th></tr></thead><tbody>';
		foreach ( $users as $user ) {
			$designs = count_user_posts( $user->ID, 'tta_design' );
			$layouts = count_user_posts( $user->ID, 'tta_layout' );
			$quotes  = count_user_posts( $user->ID, 'tta_quote' );
			$company = $this->get_user_company_label( $user->ID );
			$link = add_query_arg( array( 'page' => 'tta-threaddesk-user-detail', 'user_id' => $user->ID ), admin_url( 'admin.php' ) );
			$user_label = '<strong>' . esc_html( $user->display_name ) . '</strong>';
			if ( $company && $company !== $user->display_name ) {
				$user_label .= '<br /><small>' . esc_html( $company ) . '</small>';
			}
			echo '<tr><td><a href="' . esc_url( $link ) . '">' . $user_label . '</a></td><td>' . esc_html( $user->user_email ) . '</td><td>' . esc_html( (string) $designs ) . '</td><td>' . esc_html( (string) $layouts ) . '</td><td>' . esc_html( (string) $quotes ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public function render_admin_user_detail_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$user = $user_id ? get_userdata( $user_id ) : false;
		$active_section = isset( $_GET['td_user_section'] ) ? sanitize_key( wp_unslash( $_GET['td_user_section'] ) ) : '';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'ThreadDesk User Profile', 'threaddesk' ) . '</h1>';

		if ( isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User details updated.', 'threaddesk' ) . '</p></div>';
		}

		if ( ! $user ) {
			echo '<p>' . esc_html__( 'Select a valid ThreadDesk user from the Users list.', 'threaddesk' ) . '</p>';
			echo '</div>';
			return;
		}

		$avatar_id = (int) get_user_meta( $user_id, 'tta_threaddesk_avatar_id', true );
		$avatar_url = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
		if ( ! $avatar_url && function_exists( 'get_avatar_url' ) ) {
			$avatar_url = get_avatar_url( $user_id, array( 'size' => 120 ) );
		}

		$billing = $this->data->get_user_address( $user_id, 'billing' );
		$shipping = $this->data->get_user_address( $user_id, 'shipping' );
		$stats = $this->data->get_order_stats( $user_id );
		$company = $this->get_user_company_label( $user_id );
		$outstanding = $this->data->get_outstanding_total( $user_id );

		$design_count = (int) count_user_posts( $user_id, 'tta_design' );
		$layout_count = (int) count_user_posts( $user_id, 'tta_layout' );
		$quote_count = (int) count_user_posts( $user_id, 'tta_quote' );
		$order_count = isset( $stats['order_count'] ) ? (int) $stats['order_count'] : 0;

		$base_detail_url = add_query_arg(
			array(
				'page'    => 'tta-threaddesk-user-detail',
				'user_id' => $user_id,
			),
			admin_url( 'admin.php' )
		);

		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=tta-threaddesk-users' ) ) . '">&larr; ' . esc_html__( 'Back to Users', 'threaddesk' ) . '</a></p>';
		echo '<h2>' . esc_html( $user->display_name ) . '</h2>';
		echo '<p><strong>' . esc_html__( 'Company/Username', 'threaddesk' ) . ':</strong> ' . esc_html( $company ? $company : $user->user_login ) . '</p>';

		echo '<table class="widefat striped" style="max-width:980px;margin-bottom:16px;"><thead><tr><th>' . esc_html__( 'Activity', 'threaddesk' ) . '</th><th>' . esc_html__( 'Account Totals', 'threaddesk' ) . '</th></tr></thead><tbody>';
		echo '<tr><td><a href="' . esc_url( add_query_arg( 'td_user_section', 'designs', $base_detail_url ) ) . '">' . esc_html__( 'Designs', 'threaddesk' ) . '</a>: ' . esc_html( (string) $design_count ) . '</td><td>' . esc_html__( 'Outstanding Total', 'threaddesk' ) . ': ' . esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $outstanding ) ) : number_format_i18n( (float) $outstanding, 2 ) ) . '</td></tr>';
		echo '<tr><td><a href="' . esc_url( add_query_arg( 'td_user_section', 'layouts', $base_detail_url ) ) . '">' . esc_html__( 'Layouts', 'threaddesk' ) . '</a>: ' . esc_html( (string) $layout_count ) . '</td><td>' . esc_html__( 'Last Order', 'threaddesk' ) . ': ' . esc_html( isset( $stats['last_order'] ) ? (string) $stats['last_order'] : __( 'No orders yet', 'threaddesk' ) ) . '</td></tr>';
		echo '<tr><td><a href="' . esc_url( add_query_arg( 'td_user_section', 'quotes', $base_detail_url ) ) . '">' . esc_html__( 'Quotes', 'threaddesk' ) . '</a>: ' . esc_html( (string) $quote_count ) . '</td><td>' . esc_html__( 'Average Order', 'threaddesk' ) . ': ' . esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( isset( $stats['avg_order'] ) ? (float) $stats['avg_order'] : 0 ) ) : number_format_i18n( isset( $stats['avg_order'] ) ? (float) $stats['avg_order'] : 0, 2 ) ) . '</td></tr>';
		echo '<tr><td><a href="' . esc_url( add_query_arg( 'td_user_section', 'orders', $base_detail_url ) ) . '">' . esc_html__( 'Orders', 'threaddesk' ) . '</a>: ' . esc_html( (string) $order_count ) . '</td><td>' . esc_html__( 'Lifetime Value', 'threaddesk' ) . ': ' . esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( isset( $stats['lifetime'] ) ? (float) $stats['lifetime'] : 0 ) ) : number_format_i18n( isset( $stats['lifetime'] ) ? (float) $stats['lifetime'] : 0, 2 ) ) . '</td></tr>';
		echo '</tbody></table>';

		$activity_page = isset( $_GET['td_activity_page'] ) ? absint( wp_unslash( $_GET['td_activity_page'] ) ) : 1;
		$activity_data = $this->data->get_recent_activity_page( $user_id, 10, $activity_page );
		$recent_events = isset( $activity_data['entries'] ) ? $activity_data['entries'] : array();
		echo '<h2>' . esc_html__( 'Recent Activity', 'threaddesk' ) . '</h2>';
		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'tta_threaddesk_export_activity_csv',
					'user_id' => $user_id,
				),
				admin_url( 'admin-post.php' )
			),
			'tta_threaddesk_export_activity_csv_' . $user_id
		);
		echo '<p><a class="button" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export Activity CSV', 'threaddesk' ) . '</a></p>';
		if ( empty( $recent_events ) ) {
			echo '<p>' . esc_html__( 'No recent activity logged.', 'threaddesk' ) . '</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:980px;margin-bottom:16px;"><thead><tr><th>' . esc_html__( 'Date', 'threaddesk' ) . '</th><th>' . esc_html__( 'Event', 'threaddesk' ) . '</th></tr></thead><tbody>';
			foreach ( $recent_events as $event ) {
				$event_date = isset( $event['date'] ) ? (string) $event['date'] : '';
				$event_label = isset( $event['label'] ) ? (string) $event['label'] : '';
				echo '<tr><td>' . esc_html( $event_date ) . '</td><td>' . esc_html( $event_label ) . '</td></tr>';
			}
			echo '</tbody></table>';
			$total_pages = isset( $activity_data['total_pages'] ) ? (int) $activity_data['total_pages'] : 1;
			$current_page = isset( $activity_data['page'] ) ? (int) $activity_data['page'] : 1;
			if ( $total_pages > 1 ) {
				echo '<p>';
				if ( $current_page > 1 ) {
					$prev_url = add_query_arg( 'td_activity_page', $current_page - 1, $base_detail_url );
					echo '<a class="button" href="' . esc_url( $prev_url ) . '">' . esc_html__( 'Previous', 'threaddesk' ) . '</a> ';
				}
				echo '<span>' . esc_html( sprintf( __( 'Page %1$d of %2$d', 'threaddesk' ), $current_page, $total_pages ) ) . '</span>';
				if ( $current_page < $total_pages ) {
					$next_url = add_query_arg( 'td_activity_page', $current_page + 1, $base_detail_url );
					echo ' <a class="button" href="' . esc_url( $next_url ) . '">' . esc_html__( 'Next', 'threaddesk' ) . '</a>';
				}
				echo '</p>';
			}
		}

		if ( in_array( $active_section, array( 'designs', 'layouts', 'quotes', 'orders' ), true ) ) {
			$this->render_admin_user_related_items( $user_id, $active_section, $base_detail_url );
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:980px;">';
		echo '<input type="hidden" name="action" value="tta_threaddesk_admin_save_user" />';
		echo '<input type="hidden" name="user_id" value="' . esc_attr( (string) $user_id ) . '" />';
		wp_nonce_field( 'tta_threaddesk_admin_save_user', 'tta_threaddesk_admin_save_user_nonce' );

		echo '<h2>' . esc_html__( 'Profile', 'threaddesk' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th><label for="tta_td_first_name">' . esc_html__( 'First Name', 'threaddesk' ) . '</label></th><td><input name="first_name" id="tta_td_first_name" type="text" class="regular-text" value="' . esc_attr( (string) $user->first_name ) . '" /></td></tr>';
		echo '<tr><th><label for="tta_td_last_name">' . esc_html__( 'Last Name', 'threaddesk' ) . '</label></th><td><input name="last_name" id="tta_td_last_name" type="text" class="regular-text" value="' . esc_attr( (string) $user->last_name ) . '" /></td></tr>';
		echo '<tr><th><label for="tta_td_user_email">' . esc_html__( 'Email', 'threaddesk' ) . '</label></th><td><input name="user_email" id="tta_td_user_email" type="email" class="regular-text" value="' . esc_attr( (string) $user->user_email ) . '" /></td></tr>';
		echo '<tr><th><label for="tta_td_user_login">' . esc_html__( 'Username', 'threaddesk' ) . '</label></th><td><input id="tta_td_user_login" type="text" class="regular-text" value="' . esc_attr( (string) $user->user_login ) . '" readonly /></td></tr>';
		echo '<tr><th><label for="tta_td_company">' . esc_html__( 'Company', 'threaddesk' ) . '</label></th><td><input name="company" id="tta_td_company" type="text" class="regular-text" value="' . esc_attr( $company ) . '" /></td></tr>';
		echo '<tr><th><label for="tta_td_avatar_select">' . esc_html__( 'Profile Photo', 'threaddesk' ) . '</label></th><td>';
		echo '<input name="avatar_id" id="tta_td_avatar_id" type="hidden" value="' . esc_attr( (string) $avatar_id ) . '" />';
		echo '<button type="button" class="button" id="tta_td_avatar_select">' . esc_html__( 'Select / Change Photo', 'threaddesk' ) . '</button> ';
		echo '<button type="button" class="button-link" id="tta_td_avatar_remove">' . esc_html__( 'Remove', 'threaddesk' ) . '</button>';
		echo '<div><img id="tta_td_avatar_preview" src="' . esc_url( $avatar_url ) . '" alt="" style="margin-top:8px;max-width:80px;height:auto;border-radius:8px;border:1px solid #ddd;' . ( $avatar_url ? '' : 'display:none;' ) . '" /></div>';
		echo '</td></tr>';
		echo '</tbody></table>';

		echo '<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">';
		echo '<div style="flex:1 1 420px;min-width:320px;">';
		$this->render_admin_user_address_fields( 'billing', __( 'Billing Details', 'threaddesk' ), $billing );
		echo '</div>';
		echo '<div style="flex:1 1 420px;min-width:320px;">';
		$this->render_admin_user_address_fields( 'shipping', __( 'Shipping Details', 'threaddesk' ), $shipping );
		echo '</div>';
		echo '</div>';

		echo '<h2>' . esc_html__( 'Account Details', 'threaddesk' ) . '</h2>';
		echo '<p>' . esc_html__( 'Username is read-only in this screen. You can update name, email, company, avatar, and billing/shipping fields.', 'threaddesk' ) . '</p>';

		submit_button( __( 'Save User Profile', 'threaddesk' ) );
		echo '</form>';

		echo '<script>jQuery(function($){var frame=null;$("#tta_td_avatar_select").on("click",function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:"' . esc_js( __( 'Select profile photo', 'threaddesk' ) ) . '",button:{text:"' . esc_js( __( 'Use photo', 'threaddesk' ) ) . '"},multiple:false,library:{type:"image"}});frame.on("select",function(){var a=frame.state().get("selection").first().toJSON();var u=a.url||"";var i=parseInt(a.id||0,10);$("#tta_td_avatar_id").val(i>0?i:"");if(u){$("#tta_td_avatar_preview").attr("src",u).show();}});frame.open();});$("#tta_td_avatar_remove").on("click",function(e){e.preventDefault();$("#tta_td_avatar_id").val("0");$("#tta_td_avatar_preview").attr("src","").hide();});});</script>';

		echo '</div>';
	}

	private function render_admin_user_related_items( $user_id, $section, $base_detail_url ) {
		$section_labels = array(
			'designs' => __( 'Designs', 'threaddesk' ),
			'layouts' => __( 'Layouts', 'threaddesk' ),
			'quotes'  => __( 'Quotes', 'threaddesk' ),
			'orders'  => __( 'Orders', 'threaddesk' ),
		);

		if ( ! isset( $section_labels[ $section ] ) ) {
			return;
		}

		echo '<h2>' . sprintf( esc_html__( '%s for this user', 'threaddesk' ), esc_html( $section_labels[ $section ] ) ) . '</h2>';

		if ( 'orders' === $section ) {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				echo '<p>' . esc_html__( 'WooCommerce orders are unavailable.', 'threaddesk' ) . '</p>';
				return;
			}
			$orders = wc_get_orders( array( 'customer_id' => $user_id, 'limit' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );
			if ( empty( $orders ) ) {
				echo '<p>' . esc_html__( 'No orders found.', 'threaddesk' ) . '</p>';
				return;
			}
			echo '<ul>';
			foreach ( $orders as $order ) {
				$link = admin_url( 'post.php?post=' . absint( $order->get_id() ) . '&action=edit' );
				echo '<li><a href="' . esc_url( $link ) . '">#' . esc_html( $order->get_order_number() ) . '</a> — ' . esc_html( $order->get_status() ) . '</li>';
			}
			echo '</ul>';
			return;
		}

		$post_type_map = array(
			'designs' => 'tta_design',
			'layouts' => 'tta_layout',
			'quotes'  => 'tta_quote',
		);
		$post_type = $post_type_map[ $section ];

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'author'         => $user_id,
				'posts_per_page' => 50,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			)
		);

		if ( in_array( $section, array( 'designs', 'layouts' ), true ) ) {
			$status_key = 'designs' === $section ? 'design_status' : 'layout_status';
			$approved = 0;
			$pending_or_rejected = 0;
			foreach ( $posts as $item ) {
				$status = sanitize_key( (string) get_post_meta( $item->ID, $status_key, true ) );
				if ( 'approved' === $status ) {
					$approved++;
				} else {
					$pending_or_rejected++;
				}
			}
			echo '<p><strong>' . esc_html__( 'Approved', 'threaddesk' ) . ':</strong> ' . esc_html( (string) $approved ) . ' &nbsp; <strong>' . esc_html__( 'Pending/Rejected', 'threaddesk' ) . ':</strong> ' . esc_html( (string) $pending_or_rejected ) . '</p>';
		}

		if ( empty( $posts ) ) {
			echo '<p>' . esc_html__( 'None found.', 'threaddesk' ) . '</p>';
			return;
		}

		echo '<ul>';
		foreach ( $posts as $item ) {
			$title = '' !== trim( (string) $item->post_title ) ? $item->post_title : '#' . $item->ID;
			echo '<li><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">' . esc_html( $title ) . '</a></li>';
		}
		echo '</ul>';
		echo '<p><a href="' . esc_url( remove_query_arg( 'td_user_section', $base_detail_url ) ) . '">' . esc_html__( 'Hide list', 'threaddesk' ) . '</a></p>';
	}

	private function render_admin_user_address_fields( $type, $heading, $address ) {
		$address = is_array( $address ) ? $address : array();
		$fields = array(
			'address_1' => __( 'Address Line 1', 'threaddesk' ),
			'address_2' => __( 'Address Line 2', 'threaddesk' ),
			'city'      => __( 'City', 'threaddesk' ),
			'state'     => __( 'State/Province', 'threaddesk' ),
			'postcode'  => __( 'Postcode', 'threaddesk' ),
			'country'   => __( 'Country', 'threaddesk' ),
			'phone'     => __( 'Phone', 'threaddesk' ),
			'email'     => __( 'Email', 'threaddesk' ),
		);

		echo '<h2>' . esc_html( $heading ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $fields as $field_key => $field_label ) {
			$value = isset( $address[ $field_key ] ) ? (string) $address[ $field_key ] : '';
			$name = $type . '_' . $field_key;
			echo '<tr><th><label for="tta_td_' . esc_attr( $name ) . '">' . esc_html( $field_label ) . '</label></th><td><input name="' . esc_attr( $name ) . '" id="tta_td_' . esc_attr( $name ) . '" type="text" class="regular-text" value="' . esc_attr( $value ) . '" /></td></tr>';
		}
		echo '</tbody></table>';
	}


	private function log_user_activity( $user_id, $message, $context = '' ) {
		if ( ! class_exists( 'TTA_ThreadDesk_Data' ) ) {
			return;
		}
		TTA_ThreadDesk_Data::append_user_activity( $user_id, $message, $context );
	}

	private function get_guest_token_cookie_name() {
		return 'tta_threaddesk_guest_token';
	}

	private function get_current_guest_token() {
		$cookie_name = $this->get_guest_token_cookie_name();
		$token       = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

		if ( '' === $token && isset( $_REQUEST['tta_guest_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_REQUEST['tta_guest_token'] ) );
		}

		return preg_replace( '/[^A-Za-z0-9_\-]/', '', $token );
	}

	private function clear_guest_token_cookie() {
		$cookie_name = $this->get_guest_token_cookie_name();
		setcookie( $cookie_name, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		if ( COOKIEPATH && COOKIEPATH !== SITECOOKIEPATH ) {
			setcookie( $cookie_name, '', time() - HOUR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
		unset( $_COOKIE[ $cookie_name ] );
	}

	private function resolve_guest_import_title_collision( $post_type, $user_id, $post_id, $title ) {
		$title = sanitize_text_field( (string) $title );
		if ( '' === $title ) {
			$title = 'tta_design' === $post_type ? __( 'Design', 'threaddesk' ) : __( 'Layout', 'threaddesk' );
		}

		$collision = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'private', 'publish', 'draft', 'pending' ),
				'author'         => $user_id,
				'title'          => $title,
				'exclude'        => array( $post_id ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $collision ) ) {
			return $title;
		}

		$renamed = sprintf( __( '%1$s (Imported #%2$d)', 'threaddesk' ), $title, (int) $post_id );
		return sanitize_text_field( $renamed );
	}

	private function merge_guest_drafts_to_user( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$guest_token = $this->get_current_guest_token();
		if ( '' === $guest_token ) {
			return 0;
		}

		$meta_keys = array( 'tta_guest_token', 'threaddesk_guest_token', 'guest_token' );
		$post_ids  = array();

		foreach ( array( 'tta_layout', 'tta_design' ) as $post_type ) {
			foreach ( $meta_keys as $meta_key ) {
				$ids = get_posts(
					array(
						'post_type'      => $post_type,
						'post_status'    => array( 'private', 'publish', 'draft', 'pending' ),
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'author'         => 0,
						'meta_query'     => array(
							array(
								'key'   => $meta_key,
								'value' => $guest_token,
							),
						),
					)
				);
				if ( ! empty( $ids ) ) {
					$post_ids = array_merge( $post_ids, $ids );
				}
			}
		}

		$post_ids       = array_values( array_unique( array_map( 'absint', $post_ids ) ) );
		$imported_count = 0;

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'tta_layout', 'tta_design' ), true ) ) {
				continue;
			}

			if ( (int) $post->post_author > 0 && (int) $post->post_author !== $user_id ) {
				continue;
			}

			$new_title = $this->resolve_guest_import_title_collision( $post->post_type, $user_id, $post->ID, $post->post_title );
			$updated   = wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_author' => $user_id,
					'post_title'  => $new_title,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				continue;
			}

			foreach ( $meta_keys as $meta_key ) {
				delete_post_meta( $post->ID, $meta_key );
			}

			$entity_label = 'tta_design' === $post->post_type ? __( 'design draft', 'threaddesk' ) : __( 'layout draft', 'threaddesk' );
			$this->log_user_activity( $user_id, sprintf( __( 'Imported guest %1$s: %2$s', 'threaddesk' ), $entity_label, get_the_title( $post->ID ) ), 'account' );
			$imported_count++;
		}

		if ( $imported_count > 0 ) {
			$this->clear_guest_token_cookie();
		}

		return $imported_count;
	}

	public function handle_wp_login_merge_guest_drafts( $user_login, $user ) {
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$this->merge_guest_drafts_to_user( (int) $user->ID );
	}

	private function get_user_company_label( $user_id ) {
		$company = (string) get_user_meta( $user_id, 'billing_company', true );
		if ( '' === $company ) {
			$company = (string) get_user_meta( $user_id, 'shipping_company', true );
		}
		if ( '' === $company ) {
			$user = get_userdata( $user_id );
			$company = $user ? (string) $user->user_login : '';
		}

		return $company;
	}

	public function handle_admin_save_user() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		$nonce = isset( $_POST['tta_threaddesk_admin_save_user_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_admin_save_user_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tta_threaddesk_admin_save_user' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'threaddesk' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$user = $user_id ? get_userdata( $user_id ) : false;
		if ( ! $user ) {
			wp_die( esc_html__( 'User not found.', 'threaddesk' ) );
		}

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$company    = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
		$avatar_id  = isset( $_POST['avatar_id'] ) ? absint( $_POST['avatar_id'] ) : 0;

		$update_user_args = array(
			'ID'         => $user_id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'display_name' => trim( $first_name . ' ' . $last_name ) ? trim( $first_name . ' ' . $last_name ) : $user->display_name,
		);
		if ( '' !== $user_email ) {
			$update_user_args['user_email'] = $user_email;
		}
		wp_update_user( $update_user_args );

		if ( $avatar_id ) {
			update_user_meta( $user_id, 'tta_threaddesk_avatar_id', $avatar_id );
		} else {
			delete_user_meta( $user_id, 'tta_threaddesk_avatar_id' );
		}

		update_user_meta( $user_id, 'billing_company', $company );
		update_user_meta( $user_id, 'shipping_company', $company );

		$address_fields = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone', 'email' );
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			foreach ( $address_fields as $field ) {
				$key = $type . '_' . $field;
				$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
				if ( 'email' === $field ) {
					$value = sanitize_email( $value );
				}
				update_user_meta( $user_id, $key, $value );
			}
		}

		$this->log_user_activity( $user_id, __( 'Account profile updated by administrator.', 'threaddesk' ), 'account' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'tta-threaddesk-user-detail',
					'user_id' => $user_id,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}


	public function handle_admin_export_activity_csv() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;
		if ( $user_id <= 0 ) {
			wp_die( esc_html__( 'User not found.', 'threaddesk' ) );
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tta_threaddesk_export_activity_csv_' . $user_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'threaddesk' ) );
		}

		$events = $this->data->get_recent_activity( $user_id, 0 );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="threaddesk-activity-user-' . $user_id . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			exit;
		}

		fputcsv( $output, array( 'Date', 'Event', 'Context' ) );
		foreach ( $events as $event ) {
			$date    = isset( $event['date'] ) ? (string) $event['date'] : '';
			$label   = isset( $event['label'] ) ? (string) $event['label'] : '';
			$context = isset( $event['context'] ) ? (string) $event['context'] : '';
			fputcsv( $output, array( $date, $label, $context ) );
		}

		fclose( $output );
		exit;
	}

	public function register_admin_meta_boxes() {
		add_meta_box( 'threaddesk_design_detail', __( 'ThreadDesk Design Details', 'threaddesk' ), array( $this, 'render_design_admin_meta_box' ), 'tta_design', 'normal', 'high' );
		add_meta_box( 'threaddesk_design_usage', __( 'Used Layouts / Quotes / Invoices', 'threaddesk' ), array( $this, 'render_design_usage_admin_meta_box' ), 'tta_design', 'side', 'default' );
		add_meta_box( 'threaddesk_design_status', __( 'Design Status', 'threaddesk' ), array( $this, 'render_design_status_admin_meta_box' ), 'tta_design', 'side', 'high' );
		add_meta_box( 'threaddesk_layout_detail', __( 'ThreadDesk Layout Details', 'threaddesk' ), array( $this, 'render_layout_admin_meta_box' ), 'tta_layout', 'normal', 'high' );
		add_meta_box( 'threaddesk_layout_designs', __( 'Designs', 'threaddesk' ), array( $this, 'render_layout_designs_admin_meta_box' ), 'tta_layout', 'side', 'default' );
		add_meta_box( 'threaddesk_layout_status', __( 'Layout Status', 'threaddesk' ), array( $this, 'render_layout_status_admin_meta_box' ), 'tta_layout', 'side', 'high' );
		add_meta_box( 'threaddesk_quote_status', __( 'Quote Status', 'threaddesk' ), array( $this, 'render_quote_status_admin_meta_box' ), 'tta_quote', 'side', 'high' );
		add_meta_box( 'threaddesk_quote_lines', __( 'Quote Line Items', 'threaddesk' ), array( $this, 'render_quote_line_items_admin_meta_box' ), 'tta_quote', 'normal', 'high' );
		add_meta_box( 'threaddesk_quote_prints', __( 'Prints in Quote', 'threaddesk' ), array( $this, 'render_quote_prints_admin_meta_box' ), 'tta_quote', 'side', 'default' );
		add_meta_box( 'threaddesk_product_postbox', __( 'ThreadDesk Product Postbox', 'threaddesk' ), array( $this, 'render_product_postbox_meta_box' ), 'product', 'normal', 'default' );
	}

	private function get_product_color_options( $product_id ) {
		$options = array();
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		if ( ! $product || ! is_callable( array( $product, 'get_attributes' ) ) ) {
			return $options;
		}

		$attributes = (array) $product->get_attributes();
		$color_attribute = null;
		foreach ( $attributes as $attribute ) {
			if ( ! $attribute || ! is_object( $attribute ) || ! method_exists( $attribute, 'get_name' ) ) {
				continue;
			}
			$attribute_name = sanitize_key( (string) $attribute->get_name() );
			if ( in_array( $attribute_name, array( 'pa_color', 'color' ), true ) ) {
				$color_attribute = $attribute;
				break;
			}
		}

		if ( ! $color_attribute ) {
			return $options;
		}

		if ( method_exists( $color_attribute, 'is_taxonomy' ) && $color_attribute->is_taxonomy() ) {
			$taxonomy = (string) $color_attribute->get_name();
			$terms = wc_get_product_terms( $product_id, $taxonomy, array( 'fields' => 'all' ) );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( ! $term instanceof WP_Term ) {
						continue;
					}
					$key = sanitize_key( (string) $term->slug );
					$label = sanitize_text_field( (string) $term->name );
					if ( '' === $key || '' === $label ) {
						continue;
					}
					$options[ $key ] = $label;
				}
			}
		} elseif ( method_exists( $color_attribute, 'get_options' ) ) {
			foreach ( (array) $color_attribute->get_options() as $raw_label ) {
				$label = sanitize_text_field( (string) $raw_label );
				$key   = sanitize_title( $label );
				if ( '' === $key || '' === $label ) {
					continue;
				}
				$options[ $key ] = $label;
			}
		}

		return $options;
	}

	public function get_product_postbox_views( $product_id ) {
		$stored = get_post_meta( (int) $product_id, 'tta_threaddesk_product_postbox', true );
		$stored = is_array( $stored ) ? $stored : array();
		$colors = isset( $stored['colors'] ) && is_array( $stored['colors'] ) ? $stored['colors'] : array();
		$normalized = array();
		foreach ( $colors as $color_key => $row ) {
			$key = sanitize_key( (string) $color_key );
			if ( '' === $key || ! is_array( $row ) ) {
				continue;
			}
			$normalized[ $key ] = array(
				'front_image' => isset( $row['front_image'] ) ? esc_url_raw( $row['front_image'] ) : '',
				'front_fallback_url' => isset( $row['front_fallback_url'] ) ? esc_url_raw( $row['front_fallback_url'] ) : '',
				'back_image'  => isset( $row['back_image'] ) ? esc_url_raw( $row['back_image'] ) : '',
				'back_fallback_url' => isset( $row['back_fallback_url'] ) ? esc_url_raw( $row['back_fallback_url'] ) : '',
				'side_image'  => isset( $row['side_image'] ) ? esc_url_raw( $row['side_image'] ) : '',
				'side_fallback_url' => isset( $row['side_fallback_url'] ) ? esc_url_raw( $row['side_fallback_url'] ) : '',
				'side_label'  => ( isset( $row['side_label'] ) && 'right' === sanitize_key( (string) $row['side_label'] ) ) ? 'right' : 'left',
			);
		}

		return array( 'colors' => $normalized );
	}

	public function render_product_postbox_meta_box( $post ) {
		$product_id = $post instanceof WP_Post ? (int) $post->ID : 0;
		if ( $product_id <= 0 ) {
			return;
		}

		$colors = $this->get_product_color_options( $product_id );
		$views  = $this->get_product_postbox_views( $product_id );
		$saved_colors = isset( $views['colors'] ) && is_array( $views['colors'] ) ? $views['colors'] : array();
		wp_nonce_field( 'tta_threaddesk_product_postbox_save', 'tta_threaddesk_product_postbox_nonce' );
		?>
		<p><?php echo esc_html__( 'Configure per-color FRONT, BACK, and SIDE mockup images for this product. SIDE is treated as Left by default.', 'threaddesk' ); ?></p>
		<?php if ( empty( $colors ) ) : ?>
			<p><em><?php echo esc_html__( 'No Color attribute values were found on this product. Add a Color attribute (global pa_color or custom Color) to enable tabs.', 'threaddesk' ); ?></em></p>
			<?php return; ?>
		<?php endif; ?>
		<div class="threaddesk-product-postbox" data-threaddesk-product-postbox>
			<div class="threaddesk-auth-modal__tabs" role="tablist" style="margin-bottom:16px;">
				<?php $is_first = true; ?>
				<?php foreach ( $colors as $color_key => $color_label ) : ?>
					<button type="button" class="threaddesk-auth-modal__tab<?php echo $is_first ? ' is-active' : ''; ?>" data-threaddesk-product-color-tab="<?php echo esc_attr( $color_key ); ?>" role="tab" aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"><?php echo esc_html( $color_label ); ?></button>
					<?php $is_first = false; ?>
				<?php endforeach; ?>
			</div>

			<?php $is_first_panel = true; ?>
			<?php foreach ( $colors as $color_key => $color_label ) : ?>
				<?php $row = isset( $saved_colors[ $color_key ] ) && is_array( $saved_colors[ $color_key ] ) ? $saved_colors[ $color_key ] : array(); ?>
				<div class="threaddesk-product-postbox__panel" data-threaddesk-product-color-panel="<?php echo esc_attr( $color_key ); ?>" <?php echo $is_first_panel ? '' : 'hidden'; ?> style="padding:12px;border:1px solid #dcdcde;background:#fff;">
					<p><strong><?php echo esc_html( sprintf( __( '%s views', 'threaddesk' ), $color_label ) ); ?></strong></p>
					<div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
						<div style="flex:1 1 250px;min-width:220px;">
							<p><strong><?php echo esc_html__( 'Front image', 'threaddesk' ); ?></strong></p>
							<?php $this->render_media_picker_field( "tta_threaddesk_product_postbox[colors][{$color_key}][front_image]", isset( $row['front_image'] ) ? $row['front_image'] : '' ); ?>
							<p>
								<label for="tta_threaddesk_product_postbox_front_fallback_<?php echo esc_attr( $color_key ); ?>"><strong><?php echo esc_html__( 'Fallback link', 'threaddesk' ); ?></strong></label><br />
								<input id="tta_threaddesk_product_postbox_front_fallback_<?php echo esc_attr( $color_key ); ?>" type="url" class="regular-text" name="tta_threaddesk_product_postbox[colors][<?php echo esc_attr( $color_key ); ?>][front_fallback_url]" value="<?php echo esc_url( isset( $row['front_fallback_url'] ) ? $row['front_fallback_url'] : '' ); ?>" placeholder="https://" />
							</p>
						</div>
						<div style="flex:1 1 250px;min-width:220px;">
							<p><strong><?php echo esc_html__( 'Back image', 'threaddesk' ); ?></strong></p>
							<?php $this->render_media_picker_field( "tta_threaddesk_product_postbox[colors][{$color_key}][back_image]", isset( $row['back_image'] ) ? $row['back_image'] : '' ); ?>
							<p>
								<label for="tta_threaddesk_product_postbox_back_fallback_<?php echo esc_attr( $color_key ); ?>"><strong><?php echo esc_html__( 'Fallback link', 'threaddesk' ); ?></strong></label><br />
								<input id="tta_threaddesk_product_postbox_back_fallback_<?php echo esc_attr( $color_key ); ?>" type="url" class="regular-text" name="tta_threaddesk_product_postbox[colors][<?php echo esc_attr( $color_key ); ?>][back_fallback_url]" value="<?php echo esc_url( isset( $row['back_fallback_url'] ) ? $row['back_fallback_url'] : '' ); ?>" placeholder="https://" />
							</p>
						</div>
						<div style="flex:1 1 250px;min-width:220px;">
							<p><strong><?php echo esc_html__( 'Side image', 'threaddesk' ); ?></strong></p>
							<?php $this->render_media_picker_field( "tta_threaddesk_product_postbox[colors][{$color_key}][side_image]", isset( $row['side_image'] ) ? $row['side_image'] : '' ); ?>
							<p>
								<label for="tta_threaddesk_product_postbox_side_fallback_<?php echo esc_attr( $color_key ); ?>"><strong><?php echo esc_html__( 'Fallback link', 'threaddesk' ); ?></strong></label><br />
								<input id="tta_threaddesk_product_postbox_side_fallback_<?php echo esc_attr( $color_key ); ?>" type="url" class="regular-text" name="tta_threaddesk_product_postbox[colors][<?php echo esc_attr( $color_key ); ?>][side_fallback_url]" value="<?php echo esc_url( isset( $row['side_fallback_url'] ) ? $row['side_fallback_url'] : '' ); ?>" placeholder="https://" />
							</p>
							<p>
								<label for="tta_threaddesk_product_postbox_side_label_<?php echo esc_attr( $color_key ); ?>"><strong><?php echo esc_html__( 'Side view represents', 'threaddesk' ); ?></strong></label><br />
								<select id="tta_threaddesk_product_postbox_side_label_<?php echo esc_attr( $color_key ); ?>" name="tta_threaddesk_product_postbox[colors][<?php echo esc_attr( $color_key ); ?>][side_label]">
									<option value="left" <?php selected( isset( $row['side_label'] ) ? $row['side_label'] : 'left', 'left' ); ?>><?php echo esc_html__( 'Left', 'threaddesk' ); ?></option>
									<option value="right" <?php selected( isset( $row['side_label'] ) ? $row['side_label'] : 'left', 'right' ); ?>><?php echo esc_html__( 'Right', 'threaddesk' ); ?></option>
								</select>
							</p>
						</div>
					</div>
				</div>
				<?php $is_first_panel = false; ?>
			<?php endforeach; ?>
		</div>
		<script>
		jQuery(function ($) {
			const wrapper = $('[data-threaddesk-product-postbox]');
			if (!wrapper.length) { return; }

			const switchColorPanel = function (key) {
				wrapper.find('[data-threaddesk-product-color-tab]').removeClass('is-active').attr('aria-selected', 'false');
				wrapper.find('[data-threaddesk-product-color-tab="' + key + '"]').addClass('is-active').attr('aria-selected', 'true');
				wrapper.find('[data-threaddesk-product-color-panel]').attr('hidden', 'hidden');
				wrapper.find('[data-threaddesk-product-color-panel="' + key + '"]').removeAttr('hidden');
			};

			wrapper.on('click', '[data-threaddesk-product-color-tab]', function () {
				switchColorPanel(String($(this).attr('data-threaddesk-product-color-tab') || ''));
			});

			wrapper.on('click', '[data-threaddesk-media-select]', function (event) {
				event.preventDefault();
				const fieldWrap = $(this).closest('.threaddesk-media-picker-field');
				const input = fieldWrap.find('[data-threaddesk-media-input]');
				const preview = fieldWrap.find('[data-threaddesk-media-preview]');
				const frame = wp.media({ title: 'Select image', button: { text: 'Use image' }, multiple: false, library: { type: 'image' } });
				frame.on('select', function () {
					const attachment = frame.state().get('selection').first().toJSON();
					const url = attachment.url || '';
					input.val(url);
					if (url) {
						preview.attr('src', url).show();
					}
				});
				frame.open();
			});

			wrapper.on('click', '[data-threaddesk-media-clear]', function (event) {
				event.preventDefault();
				const fieldWrap = $(this).closest('.threaddesk-media-picker-field');
				fieldWrap.find('[data-threaddesk-media-input]').val('');
				fieldWrap.find('[data-threaddesk-media-preview]').attr('src', '').hide();
			});
		});
		</script>
		<?php
	}

	public function handle_product_postbox_save( $post_id, $post ) {
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['tta_threaddesk_product_postbox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_product_postbox_nonce'] ) ), 'tta_threaddesk_product_postbox_save' ) ) {
			return;
		}

		$raw = isset( $_POST['tta_threaddesk_product_postbox'] ) ? wp_unslash( $_POST['tta_threaddesk_product_postbox'] ) : array();
		$raw_colors = is_array( $raw ) && isset( $raw['colors'] ) && is_array( $raw['colors'] ) ? $raw['colors'] : array();
		$valid_colors = $this->get_product_color_options( $post_id );
		$sanitized_colors = array();
		foreach ( $valid_colors as $color_key => $color_label ) {
			$row = isset( $raw_colors[ $color_key ] ) && is_array( $raw_colors[ $color_key ] ) ? $raw_colors[ $color_key ] : array();
			$sanitized_colors[ $color_key ] = array(
				'front_image' => isset( $row['front_image'] ) ? esc_url_raw( $row['front_image'] ) : '',
				'front_fallback_url' => isset( $row['front_fallback_url'] ) ? esc_url_raw( $row['front_fallback_url'] ) : '',
				'back_image'  => isset( $row['back_image'] ) ? esc_url_raw( $row['back_image'] ) : '',
				'back_fallback_url' => isset( $row['back_fallback_url'] ) ? esc_url_raw( $row['back_fallback_url'] ) : '',
				'side_image'  => isset( $row['side_image'] ) ? esc_url_raw( $row['side_image'] ) : '',
				'side_fallback_url' => isset( $row['side_fallback_url'] ) ? esc_url_raw( $row['side_fallback_url'] ) : '',
				'side_label'  => ( isset( $row['side_label'] ) && 'right' === sanitize_key( (string) $row['side_label'] ) ) ? 'right' : 'left',
			);
		}

		if ( empty( $sanitized_colors ) ) {
			delete_post_meta( $post_id, 'tta_threaddesk_product_postbox' );
			return;
		}

		update_post_meta(
			$post_id,
			'tta_threaddesk_product_postbox',
			array(
				'colors' => $sanitized_colors,
			)
		);
	}

	public function render_layout_status_admin_meta_box( $post ) {
		$status = $this->get_layout_status( $post->ID );
		$options = $this->get_layout_status_options();
		$rejection_reason = $this->sanitize_layout_rejection_reason( get_post_meta( $post->ID, 'layout_rejection_reason', true ) );
		$rejection_reason_options = $this->get_layout_rejection_reason_options();
		wp_nonce_field( 'tta_threaddesk_layout_status_meta_box', 'tta_threaddesk_layout_status_meta_nonce' );
		echo '<label for="threaddesk_layout_status_field" class="screen-reader-text">' . esc_html__( 'Layout status', 'threaddesk' ) . '</label>';
		echo '<select id="threaddesk_layout_status_field" name="threaddesk_layout_status" style="width:100%;">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p id="threaddesk_layout_rejection_reason_wrap" style="margin-top:10px;' . ( 'rejected' === $status ? '' : 'display:none;' ) . '">';
		echo '<label for="threaddesk_layout_rejection_reason_field"><strong>' . esc_html__( 'Rejection reason', 'threaddesk' ) . '</strong></label><br />';
		echo '<select id="threaddesk_layout_rejection_reason_field" name="threaddesk_layout_rejection_reason" style="width:100%;">';
		echo '<option value="">' . esc_html__( 'Select a reason', 'threaddesk' ) . '</option>';
		foreach ( $rejection_reason_options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $rejection_reason, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</p>';
		?>
		<script>
		jQuery(function ($) {
			const $status = $('#threaddesk_layout_status_field');
			const $reasonWrap = $('#threaddesk_layout_rejection_reason_wrap');
			const sync = function () {
				$reasonWrap.toggle(String($status.val() || '') === 'rejected');
			};
			$status.on('change', sync);
			sync();
		});
		</script>
		<?php
	}

	public function render_quote_status_admin_meta_box( $post ) {
		$status = $this->get_quote_status( $post->ID );
		$options = $this->get_quote_status_options();
		wp_nonce_field( 'tta_threaddesk_quote_status_meta_box', 'tta_threaddesk_quote_status_meta_nonce' );
		echo '<label for="threaddesk_quote_status_field" class="screen-reader-text">' . esc_html__( 'Quote status', 'threaddesk' ) . '</label>';
		echo '<select id="threaddesk_quote_status_field" name="threaddesk_quote_status" style="width:100%;">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public function render_design_status_admin_meta_box( $post ) {
		$status = $this->get_design_status( $post->ID );
		$options = $this->get_design_status_options();
		$rejection_reason = $this->sanitize_design_rejection_reason( get_post_meta( $post->ID, 'design_rejection_reason', true ) );
		$rejection_reason_options = $this->get_design_rejection_reason_options();
		wp_nonce_field( 'tta_threaddesk_design_status_meta_box', 'tta_threaddesk_design_status_meta_nonce' );
		echo '<label for="threaddesk_design_status_field" class="screen-reader-text">' . esc_html__( 'Design status', 'threaddesk' ) . '</label>';
		echo '<select id="threaddesk_design_status_field" name="threaddesk_design_status" style="width:100%;">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p id="threaddesk_design_rejection_reason_wrap" style="margin-top:10px;' . ( 'rejected' === $status ? '' : 'display:none;' ) . '">';
		echo '<label for="threaddesk_design_rejection_reason_field"><strong>' . esc_html__( 'Rejection reason', 'threaddesk' ) . '</strong></label><br />';
		echo '<select id="threaddesk_design_rejection_reason_field" name="threaddesk_design_rejection_reason" style="width:100%;">';
		echo '<option value="">' . esc_html__( 'Select a reason', 'threaddesk' ) . '</option>';
		foreach ( $rejection_reason_options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $rejection_reason, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</p>';
		?>
		<script>
		jQuery(function ($) {
			const $status = $('#threaddesk_design_status_field');
			const $reasonWrap = $('#threaddesk_design_rejection_reason_wrap');
			const sync = function () {
				$reasonWrap.toggle(String($status.val() || '') === 'rejected');
			};
			$status.on('change', sync);
			sync();
		});
		</script>
		<?php
	}


	public function render_quote_line_items_admin_meta_box( $post ) {
		if ( ! $post instanceof WP_Post ) {
			echo '<p>' . esc_html__( 'No quote data available.', 'threaddesk' ) . '</p>';
			return;
		}

		$rows_raw = get_post_meta( $post->ID, 'screenprint_quote_rows_json', true );
		if ( ! is_array( $rows_raw ) && '' === trim( (string) $rows_raw ) ) {
			$rows_raw = get_post_meta( $post->ID, 'items_json', true );
		}
		if ( is_array( $rows_raw ) ) {
			$rows = $rows_raw;
		} else {
			$rows = json_decode( (string) $rows_raw, true );
		}
		if ( is_array( $rows ) && isset( $rows['rows'] ) && is_array( $rows['rows'] ) ) {
			$rows = $rows['rows'];
		}
		if ( is_array( $rows ) && isset( $rows['items'] ) && is_array( $rows['items'] ) ) {
			$rows = $rows['items'];
		}
		if ( is_array( $rows ) && ! empty( $rows ) && array_keys( $rows ) !== range( 0, count( $rows ) - 1 ) ) {
			$rows = array_values( array_filter( $rows, 'is_array' ) );
		}
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No line items recorded for this quote.', 'threaddesk' ) . '</p>';
			return;
		}

		echo '<div class="threaddesk-admin-table-wrap" style="overflow-x:auto;">';
		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Product SKU', 'threaddesk' ) . '</th>';
		echo '<th>' . esc_html__( 'Product Short Description', 'threaddesk' ) . '</th>';
		echo '<th>' . esc_html__( 'Qty', 'threaddesk' ) . '</th>';
		echo '<th>' . esc_html__( 'Estimated Cost', 'threaddesk' ) . '</th>';
		echo '<th>' . esc_html__( 'Placements & Prints', 'threaddesk' ) . '</th>';
		echo '<th>' . esc_html__( 'Mockups', 'threaddesk' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$sku  = isset( $row['productSku'] ) ? (string) $row['productSku'] : '';
			$desc = isset( $row['productShortDescription'] ) ? (string) $row['productShortDescription'] : '';
			$qty  = isset( $row['qty'] ) ? absint( $row['qty'] ) : 0;
			$estimated = isset( $row['estimatedVariationCostTotal'] ) ? (float) $row['estimatedVariationCostTotal'] : 0;
			$placements = isset( $row['placements'] ) && is_array( $row['placements'] ) ? $row['placements'] : array();
			$placement_text = array();
			foreach ( $placements as $placement ) {
				if ( ! is_array( $placement ) ) {
					continue;
				}
				$placement_label = isset( $placement['placementLabel'] ) ? sanitize_text_field( (string) $placement['placementLabel'] ) : '';
				$design_name = isset( $placement['designName'] ) ? sanitize_text_field( (string) $placement['designName'] ) : '';
				if ( '' === $placement_label && '' === $design_name ) {
					continue;
				}
				$size_label = isset( $placement['approxSizeLabel'] ) ? sanitize_text_field( (string) $placement['approxSizeLabel'] ) : '';
				$placement_text[] = trim( $placement_label . ( '' !== $design_name ? ': ' . $design_name : '' ) . ( '' !== $size_label ? ' (' . $size_label . ')' : '' ) );
			}
			echo '<tr>';
			echo '<td>' . esc_html( '' !== $sku ? $sku : '—' ) . '</td>';
			echo '<td>' . esc_html( '' !== $desc ? $desc : '—' ) . '</td>';
			echo '<td>' . esc_html( (string) $qty ) . '</td>';
			echo '<td>' . esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $estimated ) ) : number_format_i18n( $estimated, 2 ) ) . '</td>';
			echo '<td>' . esc_html( ! empty( $placement_text ) ? implode( ', ', $placement_text ) : '—' ) . '</td>';
			$mockups = isset( $row['mockups'] ) && is_array( $row['mockups'] ) ? $row['mockups'] : array();
			$side_label = isset( $mockups['sideLabel'] ) && 'right' === sanitize_key( (string) $mockups['sideLabel'] ) ? 'right' : 'left';
			$left_mockup = isset( $mockups['left'] ) ? trim( (string) $mockups['left'] ) : '';
			$right_mockup = isset( $mockups['right'] ) ? trim( (string) $mockups['right'] ) : '';
			$side_mockup = isset( $mockups['side'] ) ? trim( (string) $mockups['side'] ) : '';
			$has_right_mockup = '' !== $right_mockup;
			$has_side_mockup = '' !== $side_mockup;
			$right_reuses_left_view = ! $has_right_mockup && $has_side_mockup;
			if ( $has_right_mockup && (
				( '' !== $left_mockup && $left_mockup === $right_mockup ) ||
				( '' !== $side_mockup && $side_mockup === $right_mockup )
			) ) {
				$right_reuses_left_view = true;
			}
			$right_source = isset( $mockups['rightSource'] ) ? sanitize_key( (string) $mockups['rightSource'] ) : '';
			if ( ! in_array( $right_source, array( 'left', 'right' ), true ) ) {
				$right_source = $right_reuses_left_view ? 'left' : 'right';
			}
			if ( 'left' === $side_label && $right_reuses_left_view ) {
				$right_source = 'left';
			}
			$mockup_payload = array(
				'front' => isset( $mockups['front'] ) ? esc_url_raw( (string) $mockups['front'] ) : '',
				'left'  => isset( $mockups['left'] ) ? esc_url_raw( (string) $mockups['left'] ) : '',
				'back'  => isset( $mockups['back'] ) ? esc_url_raw( (string) $mockups['back'] ) : '',
				'right' => isset( $mockups['right'] ) ? esc_url_raw( (string) $mockups['right'] ) : ( isset( $mockups['side'] ) ? esc_url_raw( (string) $mockups['side'] ) : '' ),
				'sideLabel' => $side_label,
				'rightMirror' => ( 'left' === $right_source ) ? 1 : 0,
			);
			$overlay_payload = array(
				'front' => array(),
				'left'  => array(),
				'back'  => array(),
				'right' => array(),
			);

			$overlay_groups = array();
			if ( isset( $row['placementsByAngle'] ) && is_array( $row['placementsByAngle'] ) ) {
				$overlay_groups = $row['placementsByAngle'];
			} elseif ( isset( $row['placementOverlays'] ) && is_array( $row['placementOverlays'] ) ) {
				$overlay_groups = $row['placementOverlays'];
			}

			$normalized_angle_map = array(
				'front' => 'front',
				'back'  => 'back',
				'left'  => 'left',
				'right' => 'right',
				'side'  => 'right',
			);

			foreach ( $overlay_groups as $angle_key => $entries ) {
				if ( ! is_array( $entries ) ) {
					continue;
				}
				$angle = strtolower( (string) $angle_key );
				$target_view = isset( $normalized_angle_map[ $angle ] ) ? $normalized_angle_map[ $angle ] : '';
				if ( '' === $target_view ) {
					continue;
				}
				foreach ( $entries as $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$url_raw = isset( $entry['url'] ) ? (string) $entry['url'] : '';
					$url = esc_url_raw( $url_raw );
					if ( '' === $url && preg_match( '#^data:image\/(png|jpe?g|webp);base64,#i', $url_raw ) ) {
						$url = $url_raw;
					}
					if ( '' === $url ) {
						continue;
					}
					$overlay_payload[ $target_view ][] = array(
						'url'   => $url,
						'top'   => isset( $entry['top'] ) ? (float) $entry['top'] : 50.0,
						'left'  => isset( $entry['left'] ) ? (float) $entry['left'] : 50.0,
						'width' => isset( $entry['width'] ) ? (float) $entry['width'] : 25.0,
					);
				}
			}

			if ( isset( $row['placements'] ) && is_array( $row['placements'] ) ) {
				foreach ( $row['placements'] as $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$angle = strtolower( (string) ( $entry['angle'] ?? $entry['view'] ?? '' ) );
					$target_view = isset( $normalized_angle_map[ $angle ] ) ? $normalized_angle_map[ $angle ] : '';
					if ( '' === $target_view ) {
						continue;
					}
					$url_raw = isset( $entry['url'] ) ? (string) $entry['url'] : '';
					$url = esc_url_raw( $url_raw );
					if ( '' === $url && preg_match( '#^data:image\/(png|jpe?g|webp);base64,#i', $url_raw ) ) {
						$url = $url_raw;
					}
					if ( '' === $url ) {
						continue;
					}
					$overlay_payload[ $target_view ][] = array(
						'url'   => $url,
						'top'   => isset( $entry['top'] ) ? (float) $entry['top'] : 50.0,
						'left'  => isset( $entry['left'] ) ? (float) $entry['left'] : 50.0,
						'width' => isset( $entry['width'] ) ? (float) $entry['width'] : 25.0,
					);
				}
			}
			$has_mockup = ( '' !== $mockup_payload['front'] ) || ( '' !== $mockup_payload['left'] ) || ( '' !== $mockup_payload['right'] ) || ( '' !== $mockup_payload['back'] );
			echo '<td>';
			if ( $has_mockup ) {
				echo '<button type="button" class="button" data-threaddesk-quote-mockup="' . esc_attr( wp_json_encode( $mockup_payload ) ) . '" data-threaddesk-quote-mockup-overlays="' . esc_attr( wp_json_encode( $overlay_payload ) ) . '">' . esc_html__( 'SHOW', 'threaddesk' ) . '</button>';
			} else {
				echo esc_html__( '—', 'threaddesk' );
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';

		?>
		<script>
		(function(){
			if(window.__tdQuoteMockupBound){return;}
			window.__tdQuoteMockupBound=true;
			document.addEventListener('click',function(event){
				var trigger=event.target&&event.target.closest?event.target.closest('[data-threaddesk-quote-mockup]'):null;
				if(!trigger){return;}
				event.preventDefault();
				var raw=trigger.getAttribute('data-threaddesk-quote-mockup')||'{}';
				var payload={};
				try{payload=JSON.parse(raw);}catch(e){payload={};}
				var overlaysRaw=trigger.getAttribute('data-threaddesk-quote-mockup-overlays')||'{}';
				var overlaysPayload={};
				try{overlaysPayload=JSON.parse(overlaysRaw);}catch(e){overlaysPayload={};}
				var overlay=document.createElement('div');
				overlay.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;';
				var panel=document.createElement('div');
				panel.style.cssText='background:#fff;max-width:980px;width:100%;max-height:90vh;overflow:auto;border-radius:8px;padding:16px;';
				var title=document.createElement('h3');
				title.textContent='Mockups';
				title.style.margin='0 0 12px 0';
				var grid=document.createElement('div');
				grid.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;';
				['front','left','back','right'].forEach(function(view){
					var card=document.createElement('div');
					card.style.cssText='border:1px solid #dcdcde;border-radius:6px;padding:8px;background:#f9f9f9;';
					var label=document.createElement('p');
					label.style.cssText='margin:0 0 8px 0;font-weight:600;text-transform:capitalize;';
					label.textContent=view;
					card.appendChild(label);
					var src=String((payload&&payload[view])||'').trim();
					if(src){
						var frame=document.createElement('div');
						frame.style.cssText='position:relative;width:100%;aspect-ratio:1/1;border-radius:4px;background:#fff;overflow:hidden;';
						var img=document.createElement('img');
						img.src=src;
						img.alt=view+' mockup';
						img.style.cssText='position:absolute;inset:0;width:100%;height:100%;display:block;object-fit:contain;';
						if(view==='right'&&Number(payload&&payload.rightMirror)===1){img.style.transform='scaleX(-1)';}
						frame.appendChild(img);
						var viewOverlays=Array.isArray(overlaysPayload&&overlaysPayload[view])?overlaysPayload[view]:[];
						viewOverlays.forEach(function(placement){
							var placementUrl=String((placement&&placement.url)||'').trim();
							if(!placementUrl){return;}
							var placementImg=document.createElement('img');
							placementImg.src=placementUrl;
							placementImg.alt='';
							placementImg.setAttribute('aria-hidden','true');
							var topPct=Number(placement&&placement.top);
							var leftPct=Number(placement&&placement.left);
							var widthPct=Number(placement&&placement.width);
							if(!Number.isFinite(topPct)){topPct=50;}
							if(!Number.isFinite(leftPct)){leftPct=50;}
							if(!Number.isFinite(widthPct)){widthPct=25;}
							placementImg.style.cssText='position:absolute;top:'+topPct.toFixed(2)+'%;left:'+leftPct.toFixed(2)+'%;width:'+widthPct.toFixed(2)+'%;height:auto;transform:translate(-50%,-50%);object-fit:contain;pointer-events:none;';
							frame.appendChild(placementImg);
						});
						card.appendChild(frame);
					}else{
						var empty=document.createElement('p');
						empty.textContent='No image';
						empty.style.margin='0';
						card.appendChild(empty);
					}
					grid.appendChild(card);
				});
				var closeBtn=document.createElement('button');
				closeBtn.type='button';
				closeBtn.className='button button-primary';
				closeBtn.textContent='Close';
				closeBtn.style.marginTop='12px';
				closeBtn.addEventListener('click',function(){overlay.remove();});
				overlay.addEventListener('click',function(e){if(e.target===overlay){overlay.remove();}});
				panel.appendChild(title);
				panel.appendChild(grid);
				panel.appendChild(closeBtn);
				overlay.appendChild(panel);
				document.body.appendChild(overlay);
			});
		})();
		</script>
		<?php

	}

	public function render_quote_prints_admin_meta_box( $post ) {
		if ( ! $post instanceof WP_Post ) {
			echo '<p>' . esc_html__( 'No print details available.', 'threaddesk' ) . '</p>';
			return;
		}

		$extract_colors = static function ( $print ) {
			$color_keys = array( 'selectedColors', 'colors', 'paletteCurrent', 'paletteOriginal', 'palette', 'inkColors', 'selectedPantones' );
			foreach ( $color_keys as $color_key ) {
				if ( ! isset( $print[ $color_key ] ) ) {
					continue;
				}
				$raw_colors = $print[ $color_key ];
				if ( is_string( $raw_colors ) ) {
					$raw_colors = array_map( 'trim', explode( ',', $raw_colors ) );
				}
				if ( ! is_array( $raw_colors ) ) {
					continue;
				}
				$colors = array();
				foreach ( $raw_colors as $color ) {
					$clean_color = sanitize_text_field( (string) $color );
					if ( '' !== $clean_color ) {
						$colors[] = $clean_color;
					}
				}
				if ( ! empty( $colors ) ) {
					return array_values( array_unique( $colors ) );
				}
			}
			if ( isset( $print['placement'] ) && is_array( $print['placement'] ) ) {
				foreach ( $color_keys as $color_key ) {
					if ( ! isset( $print['placement'][ $color_key ] ) ) {
						continue;
					}
					$raw_colors = $print['placement'][ $color_key ];
					if ( is_string( $raw_colors ) ) {
						$raw_colors = array_map( 'trim', explode( ',', $raw_colors ) );
					}
					if ( ! is_array( $raw_colors ) ) {
						continue;
					}
					$colors = array();
					foreach ( $raw_colors as $color ) {
						$clean_color = sanitize_text_field( (string) $color );
						if ( '' !== $clean_color ) {
							$colors[] = $clean_color;
						}
					}
					if ( ! empty( $colors ) ) {
						return array_values( array_unique( $colors ) );
					}
				}
			}
			return array();
		};

		$extract_size_label = static function ( $print ) {
			$size_label_keys = array( 'approxSizeLabel', 'sizeLabel', 'sizeText' );
			foreach ( $size_label_keys as $size_label_key ) {
				if ( ! isset( $print[ $size_label_key ] ) ) {
					continue;
				}
				$candidate = sanitize_text_field( (string) $print[ $size_label_key ] );
				if ( '' !== $candidate ) {
					return $candidate;
				}
			}
			$size_keys = array( 'approxSize', 'size', 'sliderValue', 'sizePercent' );
			foreach ( $size_keys as $size_key ) {
				if ( ! isset( $print[ $size_key ] ) ) {
					continue;
				}
				$size_value = absint( $print[ $size_key ] );
				if ( $size_value > 0 ) {
					return sprintf( '%s%%', (string) $size_value );
				}
			}
			if ( isset( $print['placement'] ) && is_array( $print['placement'] ) ) {
				foreach ( $size_label_keys as $size_label_key ) {
					if ( ! isset( $print['placement'][ $size_label_key ] ) ) {
						continue;
					}
					$candidate = sanitize_text_field( (string) $print['placement'][ $size_label_key ] );
					if ( '' !== $candidate ) {
						return $candidate;
					}
				}
				foreach ( $size_keys as $size_key ) {
					if ( ! isset( $print['placement'][ $size_key ] ) ) {
						continue;
					}
					$size_value = absint( $print['placement'][ $size_key ] );
					if ( $size_value > 0 ) {
						return sprintf( '%s%%', (string) $size_value );
					}
				}
			}
			return '';
		};

		$prints_raw = get_post_meta( $post->ID, 'screenprint_quote_prints_json', true );
		if ( ! is_array( $prints_raw ) && '' === trim( (string) $prints_raw ) ) {
			$prints_raw = get_post_meta( $post->ID, 'prints_json', true );
		}
		if ( is_array( $prints_raw ) ) {
			$prints = $prints_raw;
		} else {
			$prints = json_decode( (string) $prints_raw, true );
		}
		if ( is_array( $prints ) && isset( $prints['prints'] ) && is_array( $prints['prints'] ) ) {
			$prints = $prints['prints'];
		}
		if ( ! is_array( $prints ) || empty( $prints ) ) {
			$rows_raw = get_post_meta( $post->ID, 'screenprint_quote_rows_json', true );
			if ( ! is_array( $rows_raw ) && '' === trim( (string) $rows_raw ) ) {
				$rows_raw = get_post_meta( $post->ID, 'items_json', true );
			}
			if ( is_array( $rows_raw ) ) {
				$rows = $rows_raw;
			} else {
				$rows = json_decode( (string) $rows_raw, true );
			}
			if ( is_array( $rows ) && isset( $rows['rows'] ) && is_array( $rows['rows'] ) ) {
				$rows = $rows['rows'];
			}
			if ( is_array( $rows ) && isset( $rows['items'] ) && is_array( $rows['items'] ) ) {
				$rows = $rows['items'];
			}
			if ( is_array( $rows ) && ! empty( $rows ) && array_keys( $rows ) !== range( 0, count( $rows ) - 1 ) ) {
				$rows = array_values( array_filter( $rows, 'is_array' ) );
			}
			$prints = array();
			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					if ( isset( $row['prints'] ) && is_array( $row['prints'] ) ) {
						foreach ( $row['prints'] as $print_row_entry ) {
							if ( is_array( $print_row_entry ) ) {
								$prints[] = $print_row_entry;
							}
						}
					}
					if ( ! isset( $row['placements'] ) || ! is_array( $row['placements'] ) ) {
						continue;
					}
					foreach ( $row['placements'] as $placement ) {
						if ( ! is_array( $placement ) ) {
							continue;
						}
						$prints[] = $placement;
					}
				}
			}
		}

		if ( ! is_array( $prints ) || empty( $prints ) ) {
			echo '<p>' . esc_html__( 'No prints recorded for this quote.', 'threaddesk' ) . '</p>';
			return;
		}

		echo '<ul style="margin:0;padding-left:18px;">';
		foreach ( $prints as $print ) {
			if ( ! is_array( $print ) ) {
				continue;
			}
			$design_name = isset( $print['designName'] ) ? sanitize_text_field( (string) $print['designName'] ) : __( 'Design', 'threaddesk' );
			$placement_label = isset( $print['placementLabel'] ) ? sanitize_text_field( (string) $print['placementLabel'] ) : '';
			$size_label = $extract_size_label( $print );
			$colors = $extract_colors( $print );
			$parts = array( $design_name );
			if ( '' !== $placement_label ) {
				$parts[] = sprintf( __( 'Placement: %s', 'threaddesk' ), $placement_label );
			}
			$parts[] = sprintf( __( 'Size: %s', 'threaddesk' ), '' !== $size_label ? $size_label : '—' );
			$parts[] = sprintf( __( 'Colors: %s', 'threaddesk' ), ! empty( $colors ) ? implode( ', ', $colors ) : '—' );
			echo '<li>' . esc_html( implode( ' • ', $parts ) ) . '</li>';
		}
		echo '</ul>';
	}

	public function render_design_admin_meta_box( $post ) {
		$original_url = (string) get_post_meta( $post->ID, 'design_original_file_url', true );
		$svg_url = (string) get_post_meta( $post->ID, 'design_svg_file_url', true );
		$preview_url = (string) get_post_meta( $post->ID, 'design_preview_url', true );
		$saved_mockup_url = (string) get_post_meta( $post->ID, 'design_mockup_file_url', true );
		$mockup_url = $saved_mockup_url ? $saved_mockup_url : ( $svg_url ? $svg_url : $preview_url );
		$palette_raw = (string) get_post_meta( $post->ID, 'design_palette', true );
		$palette = json_decode( $palette_raw, true );
		if ( ! is_array( $palette ) ) { $palette = array(); }
		$normalized_palette = array();
		foreach ( $palette as $raw_color ) {
			$color = strtoupper( trim( (string) $raw_color ) );
			if ( '' === $color || 'TRANSPARENT' === $color ) {
				continue;
			}
			$normalized_palette[] = $color;
		}
		$unique_palette = array_values( array_unique( $normalized_palette ) );
		$color_count = count( $unique_palette );
		$uploaded_at = (string) get_post_meta( $post->ID, 'created_at', true );
		$owner = get_userdata( (int) $post->post_author );
		$dimensions = $this->get_visible_image_dimensions_from_url( $mockup_url );
		echo '<p><strong>' . esc_html__( 'Owner', 'threaddesk' ) . ':</strong> ' . esc_html( $owner ? $owner->display_name : __( 'Unknown', 'threaddesk' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Uploaded', 'threaddesk' ) . ':</strong> ' . esc_html( $uploaded_at ?: $post->post_date ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Color count', 'threaddesk' ) . ':</strong> ' . esc_html( (string) $color_count ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Palette (Pantone/Hex)', 'threaddesk' ) . ':</strong> ' . esc_html( implode( ', ', $unique_palette ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'PNG ratio (W:H)', 'threaddesk' ) . ':</strong> ' . esc_html( $dimensions ) . '</p>';
		echo '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">';
		if ( $original_url ) { echo '<div><p><strong>' . esc_html__( 'Original image', 'threaddesk' ) . ':</strong></p><img style="max-width:280px;height:auto;display:block;" src="' . esc_url( $original_url ) . '" alt="" /></div>'; }
		if ( $mockup_url ) { echo '<div><p><strong>' . esc_html__( 'Mockup PNG', 'threaddesk' ) . ':</strong></p><img style="max-width:280px;height:auto;display:block;" src="' . esc_url( $mockup_url ) . '" alt="" /></div>'; }
		echo '</div>';
		if ( $svg_url ) { echo '<p><strong>' . esc_html__( 'Created SVG', 'threaddesk' ) . ':</strong> <a href="' . esc_url( $svg_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open SVG', 'threaddesk' ) . '</a></p>'; }
		echo '<p><em>' . esc_html__( 'Mockup PNG uses the saved design mockup image generated at save/edit time to match the design-card preview, with SVG/preview fallback for older designs.', 'threaddesk' ) . '</em></p>';
	}

	public function render_design_usage_admin_meta_box( $post ) {
		$related_layouts  = $this->find_related_posts_by_id_in_meta( $post->ID, 'tta_layout' );
		$related_quotes   = $this->find_related_posts_by_id_in_meta( $post->ID, 'tta_quote' );
		$related_invoices = $this->find_related_posts_by_id_in_meta( $post->ID, 'shop_order' );

		echo $this->render_related_post_links_list( $related_layouts, __( 'Layouts', 'threaddesk' ) );
		echo $this->render_related_post_links_list( $related_quotes, __( 'Quotes', 'threaddesk' ) );
		echo $this->render_related_post_links_list( $related_invoices, __( 'Invoices', 'threaddesk' ) );
	}


	public function render_layout_admin_meta_box( $post ) {
		$owner = get_userdata( (int) $post->post_author );
		$category = (string) get_post_meta( $post->ID, 'layout_category', true );
		$created = (string) get_post_meta( $post->ID, 'created_at', true );
		$layout_payload_raw = (string) get_post_meta( $post->ID, 'layout_payload', true );
		$layout_payload = json_decode( $layout_payload_raw, true );
		if ( ! is_array( $layout_payload ) ) {
			$layout_payload = array();
		}

		$payload_angles = isset( $layout_payload['angles'] ) && is_array( $layout_payload['angles'] ) ? $layout_payload['angles'] : array();
		$payload_placements = isset( $layout_payload['placementsByAngle'] ) && is_array( $layout_payload['placementsByAngle'] ) ? $layout_payload['placementsByAngle'] : array();
		$preview_angles = array(
			'front' => isset( $payload_angles['front'] ) ? (string) $payload_angles['front'] : '',
			'left'  => isset( $payload_angles['left'] ) ? (string) $payload_angles['left'] : '',
			'back'  => isset( $payload_angles['back'] ) ? (string) $payload_angles['back'] : '',
			'right' => isset( $payload_angles['right'] ) ? (string) $payload_angles['right'] : '',
		);

		foreach ( $preview_angles as $angle_key => $raw_preview_url ) {
			$sanitized_preview_url = esc_url_raw( $raw_preview_url );
			if ( '' === $sanitized_preview_url && preg_match( '#^data:image\/(png|jpe?g|webp);base64,#i', $raw_preview_url ) ) {
				$sanitized_preview_url = $raw_preview_url;
			}
			$preview_angles[ $angle_key ] = $sanitized_preview_url;
		}

		$has_preview_angles = false;
		foreach ( $preview_angles as $preview_url ) {
			if ( '' !== $preview_url ) {
				$has_preview_angles = true;
				break;
			}
		}

		echo '<div style="width:100%;max-width:100%;box-sizing:border-box;overflow:hidden;">';
		echo '<p><strong>' . esc_html__( 'User', 'threaddesk' ) . ':</strong> ' . esc_html( $owner ? $owner->display_name : __( 'Unknown', 'threaddesk' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Category', 'threaddesk' ) . ':</strong> ' . esc_html( $category ?: __( 'Not set', 'threaddesk' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Created', 'threaddesk' ) . ':</strong> ' . esc_html( $created ?: $post->post_date ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Last edited', 'threaddesk' ) . ':</strong> ' . esc_html( $post->post_modified ) . '</p>';

		echo '<p><strong>' . esc_html__( 'Placement angles', 'threaddesk' ) . ':</strong></p>';
		if ( $has_preview_angles ) {
			echo '<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:start;width:100%;max-width:100%;">';
			$left_preview_url = isset( $preview_angles['left'] ) ? (string) $preview_angles['left'] : '';
			$right_preview_url = isset( $preview_angles['right'] ) ? (string) $preview_angles['right'] : '';
			foreach ( $preview_angles as $angle_key => $preview_url ) {
				$mirror_angle = ( 'right' === $angle_key && '' !== $left_preview_url && $left_preview_url === $right_preview_url );
				echo '<div style="min-width:0;">';
				echo '<p style="margin:0 0 6px;"><strong>' . esc_html( strtoupper( $angle_key ) ) . '</strong></p>';
				echo '<div style="position:relative;width:100%;aspect-ratio:1/1;background:#f6f6f6;border:1px solid #ddd;border-radius:4px;overflow:hidden;">';
				echo '<div style="position:absolute;inset:0;' . ( $mirror_angle ? 'transform:scaleX(-1);transform-origin:center center;' : '' ) . '">';

				if ( '' !== $preview_url ) {
					$base_src = preg_match( '#^data:image/#i', $preview_url ) ? esc_attr( $preview_url ) : esc_url( $preview_url );
					echo '<img src="' . $base_src . '" alt="' . esc_attr( strtoupper( $angle_key ) . ' ' . __( 'view', 'threaddesk' ) ) . '" style="position:absolute;inset:0;display:block;width:100%;height:100%;object-fit:contain;" />';
				} else {
					echo '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#777;font-size:12px;">' . esc_html__( 'No image', 'threaddesk' ) . '</div>';
				}

				$angle_placements = isset( $payload_placements[ $angle_key ] ) && is_array( $payload_placements[ $angle_key ] ) ? $payload_placements[ $angle_key ] : array();
				foreach ( $angle_placements as $placement_entry ) {
					if ( ! is_array( $placement_entry ) ) {
						continue;
					}
					$overlay_url_raw = isset( $placement_entry['url'] ) ? (string) $placement_entry['url'] : '';
					$overlay_url = esc_url_raw( $overlay_url_raw );
					if ( '' === $overlay_url && preg_match( '#^data:image\/(png|jpe?g|webp);base64,#i', $overlay_url_raw ) ) {
						$overlay_url = $overlay_url_raw;
					}
					if ( '' === $overlay_url ) {
						continue;
					}
					$overlay_src = preg_match( '#^data:image/#i', $overlay_url ) ? esc_attr( $overlay_url ) : esc_url( $overlay_url );
					$overlay_top = isset( $placement_entry['top'] ) ? (float) $placement_entry['top'] : 50.0;
					$overlay_left = isset( $placement_entry['left'] ) ? (float) $placement_entry['left'] : 50.0;
					$overlay_width = isset( $placement_entry['width'] ) ? (float) $placement_entry['width'] : 25.0;
						echo '<img src="' . $overlay_src . '" alt="" aria-hidden="true" style="position:absolute;top:' . esc_attr( number_format( $overlay_top, 2, '.', '' ) ) . '%;left:' . esc_attr( number_format( $overlay_left, 2, '.', '' ) ) . '%;width:' . esc_attr( number_format( $overlay_width, 2, '.', '' ) ) . '%;height:auto;transform:translate(-50%,-50%);object-fit:contain;pointer-events:none;" />';
					}

					echo '</div>';
					echo '</div>';
					echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<p><em>' . esc_html__( 'No angle preview images available in this layout payload.', 'threaddesk' ) . '</em></p>';
		}

		$meta = get_post_meta( $post->ID );
		echo '<details style="margin-top:12px;max-width:100%;"><summary><strong>' . esc_html__( 'Design + placement/sizing data', 'threaddesk' ) . '</strong></summary><div style="max-width:100%;overflow:auto;"><ul style="margin-top:8px;">';
		foreach ( $meta as $key => $values ) {
			if ( false === strpos( $key, 'design' ) && false === strpos( $key, 'placement' ) && false === strpos( $key, 'layout' ) && false === strpos( $key, 'size' ) ) { continue; }
			$value = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : '';
			if ( is_array( $value ) || is_object( $value ) ) { $value = wp_json_encode( $value ); }
			echo '<li><code>' . esc_html( $key ) . '</code>: ' . esc_html( (string) $value ) . '</li>';
		}
		echo '</ul></div></details>';
		echo '</div>';
	}

	public function render_layout_designs_admin_meta_box( $post ) {
		$layout_payload_raw = (string) get_post_meta( $post->ID, 'layout_payload', true );
		$layout_payload = json_decode( $layout_payload_raw, true );
		if ( ! is_array( $layout_payload ) ) {
			echo '<p><em>' . esc_html__( 'No design usage data available.', 'threaddesk' ) . '</em></p>';
			return;
		}

		$placements_by_angle = isset( $layout_payload['placementsByAngle'] ) && is_array( $layout_payload['placementsByAngle'] ) ? $layout_payload['placementsByAngle'] : array();
		$rows = array();
		foreach ( $placements_by_angle as $angle_entries ) {
			if ( ! is_array( $angle_entries ) ) {
				continue;
			}
			foreach ( $angle_entries as $placement_key => $entry ) {
				if ( ! is_array( $entry ) || empty( $entry['url'] ) ) {
					continue;
				}
				$design_id = isset( $entry['designId'] ) ? absint( $entry['designId'] ) : 0;
				$design_name = isset( $entry['designName'] ) ? sanitize_text_field( (string) $entry['designName'] ) : '';
				if ( $design_id > 0 ) {
					$resolved_title = get_the_title( $design_id );
					if ( is_string( $resolved_title ) && '' !== trim( $resolved_title ) ) {
						$design_name = sanitize_text_field( $resolved_title );
					}
				}
				if ( '' === $design_name ) {
					$design_name = __( 'Design', 'threaddesk' );
				}
				$placement_label = isset( $entry['placementLabel'] ) ? sanitize_text_field( (string) $entry['placementLabel'] ) : ucwords( str_replace( '_', ' ', (string) $placement_key ) );
				$slider_value = isset( $entry['sliderValue'] ) ? (float) $entry['sliderValue'] : 100;
				$design_ratio = isset( $entry['designRatio'] ) ? (float) $entry['designRatio'] : 1;
				$size_label = $this->get_layout_entry_size_label( (string) $placement_key, $slider_value, $design_ratio );
				$colors = isset( $entry['paletteCurrent'] ) && is_array( $entry['paletteCurrent'] ) ? $entry['paletteCurrent'] : array();
				$colors = array_values( array_filter( array_map( 'sanitize_text_field', $colors ), function ( $color ) { return '' !== trim( (string) $color ); } ) );
				$rows[] = array(
					'design_id' => $design_id,
					'design_name' => $design_name,
					'placement' => $placement_label,
					'size' => $size_label,
					'colors' => $colors,
				);
			}
		}

		if ( empty( $rows ) ) {
			echo '<p><em>' . esc_html__( 'No designs are currently used in this layout.', 'threaddesk' ) . '</em></p>';
			return;
		}

		echo '<div style="display:flex;flex-direction:column;gap:10px;">';
		foreach ( $rows as $row ) {
			echo '<div style="border:1px solid #ddd;border-radius:4px;padding:8px;">';
			echo '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'Design', 'threaddesk' ) . ':</strong> ' . esc_html( $row['design_name'] ) . '</p>';
			if ( $row['design_id'] > 0 ) {
				$edit_link = get_edit_post_link( $row['design_id'] );
				if ( $edit_link ) {
					echo '<p style="margin:0 0 6px;"><a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'View design in backend', 'threaddesk' ) . '</a></p>';
				}
			}
			echo '<p style="margin:0 0 4px;"><strong>' . esc_html__( 'Placement', 'threaddesk' ) . ':</strong> ' . esc_html( $row['placement'] ) . '</p>';
			echo '<p style="margin:0 0 4px;"><strong>' . esc_html__( 'Approx. size', 'threaddesk' ) . ':</strong> ' . esc_html( $row['size'] ) . '</p>';
			echo '<p style="margin:0;"><strong>' . esc_html__( 'Chosen colors', 'threaddesk' ) . ':</strong> ' . esc_html( empty( $row['colors'] ) ? __( 'None', 'threaddesk' ) : implode( ', ', $row['colors'] ) ) . '</p>';
			echo '</div>';
		}
		echo '</div>';
	}

	private function get_layout_entry_size_label( $placement_key, $slider_value, $design_ratio ) {
		$placement_key = sanitize_key( (string) $placement_key );
		$slider_value = (float) $slider_value;
		$ratio = (float) $design_ratio;
		if ( $ratio <= 0 ) {
			$ratio = 1;
		}
		$slider_min = 60;
		$slider_max = 140;
		$range_map = array(
			'full_chest' => array( 'min' => 4.5, 'max' => 12.5 ),
			'back'       => array( 'min' => 4.5, 'max' => 12.5 ),
			'left_chest' => array( 'approx' => 4.0 ),
			'right_chest'=> array( 'approx' => 4.0 ),
			'left_sleeve'=> array( 'approx' => 4.0 ),
			'right_sleeve'=> array( 'approx' => 4.0 ),
		);
		$range = isset( $range_map[ $placement_key ] ) ? $range_map[ $placement_key ] : array( 'approx' => 4.0 );
		if ( isset( $range['min'], $range['max'] ) ) {
			$clamped = max( $slider_min, min( $slider_max, $slider_value ) );
			$normalized = ( $clamped - $slider_min ) / ( $slider_max - $slider_min );
			$max_dimension = (float) $range['min'] + ( ( (float) $range['max'] - (float) $range['min'] ) * $normalized );
		} else {
			$max_dimension = (float) $range['approx'] * ( $slider_value / 100 );
		}
		$width = $max_dimension;
		$height = $max_dimension;
		if ( $ratio > 1 ) {
			$height = $max_dimension / $ratio;
		} elseif ( $ratio > 0 && $ratio < 1 ) {
			$width = $max_dimension * $ratio;
		}
		return number_format_i18n( $width, 1 ) . '" W × ' . number_format_i18n( $height, 1 ) . '" H';
	}

	private function get_image_dimensions_from_url( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' === $url ) { return __( 'Unknown', 'threaddesk' ); }
		$uploads = wp_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
		$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
		$path = '';
		if ( $baseurl && 0 === strpos( $url, $baseurl ) ) {
			$path = $basedir . substr( $url, strlen( $baseurl ) );
		}
		if ( ! $path || ! file_exists( $path ) ) { return __( 'Unknown', 'threaddesk' ); }
		$size = @getimagesize( $path );
		if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) { return __( 'Unknown', 'threaddesk' ); }
		return (int) $size[0] . ':' . (int) $size[1];
	}

	private function get_visible_image_dimensions_from_url( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' === $url ) {
			return __( 'Unknown', 'threaddesk' );
		}

		$uploads = wp_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
		$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
		$path = '';
		if ( $baseurl && 0 === strpos( $url, $baseurl ) ) {
			$path = $basedir . substr( $url, strlen( $baseurl ) );
		}
		if ( ! $path || ! file_exists( $path ) ) {
			return __( 'Unknown', 'threaddesk' );
		}

		$size = @getimagesize( $path );
		if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) {
			return __( 'Unknown', 'threaddesk' );
		}

		$full_width = (int) $size[0];
		$full_height = (int) $size[1];
		$mime = isset( $size['mime'] ) ? (string) $size['mime'] : '';

		if ( 'image/png' !== $mime || ! function_exists( 'imagecreatefrompng' ) ) {
			return $full_width . ':' . $full_height;
		}

		$image = @imagecreatefrompng( $path );
		if ( false === $image ) {
			return $full_width . ':' . $full_height;
		}

		$min_x = $full_width;
		$min_y = $full_height;
		$max_x = -1;
		$max_y = -1;

		for ( $y = 0; $y < $full_height; $y++ ) {
			for ( $x = 0; $x < $full_width; $x++ ) {
				$rgba = imagecolorat( $image, $x, $y );
				$alpha = ( $rgba & 0x7F000000 ) >> 24;
				if ( $alpha < 127 ) {
					if ( $x < $min_x ) { $min_x = $x; }
					if ( $y < $min_y ) { $min_y = $y; }
					if ( $x > $max_x ) { $max_x = $x; }
					if ( $y > $max_y ) { $max_y = $y; }
				}
			}
		}

		imagedestroy( $image );

		if ( $max_x < $min_x || $max_y < $min_y ) {
			return __( 'Unknown', 'threaddesk' );
		}

		$visible_width = ( $max_x - $min_x ) + 1;
		$visible_height = ( $max_y - $min_y ) + 1;
		if ( $visible_width <= 0 || $visible_height <= 0 ) {
			return __( 'Unknown', 'threaddesk' );
		}

		return $visible_width . ':' . $visible_height;
	}

	private function find_related_posts_by_id_in_meta( $id, $post_type, $reverse = false ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) { return array(); }
		if ( $reverse && 'tta_design' === $post_type ) {
			// Find designs referenced by current layout meta values.
			$meta = get_post_meta( $id );
			$ids = array();
			foreach ( $meta as $vals ) {
				foreach ( (array) $vals as $v ) {
					preg_match_all( '/\b(\d{1,10})\b/', (string) $v, $m );
					if ( ! empty( $m[1] ) ) { $ids = array_merge( $ids, array_map( 'absint', $m[1] ) ); }
				}
			}
			$ids = array_values( array_unique( array_filter( $ids ) ) );
			if ( empty( $ids ) ) { return array(); }
			return get_posts( array( 'post_type' => 'tta_design', 'post__in' => $ids, 'numberposts' => 20 ) );
		}
		$sql = $wpdb->prepare(
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = %s AND p.post_status <> 'trash' AND pm.meta_value LIKE %s ORDER BY p.post_date DESC LIMIT 20",
			$post_type,
			'%' . $wpdb->esc_like( (string) $id ) . '%'
		);
		$rows = $wpdb->get_col( $sql );
		if ( empty( $rows ) ) { return array(); }
		$filtered = array();
		foreach ( $rows as $row_id ) {
			$meta = get_post_meta( (int) $row_id );
			$found = false;
			foreach ( $meta as $vals ) {
				foreach ( (array) $vals as $v ) {
					if ( preg_match( '/\b' . preg_quote( (string) $id, '/' ) . '\b/', (string) $v ) ) { $found = true; break 2; }
				}
			}
			if ( $found ) { $filtered[] = get_post( (int) $row_id ); }
		}
		return array_filter( $filtered );
	}

	private function render_related_post_links_list( $posts, $label ) {
		if ( empty( $posts ) ) {
			return '<p><em>' . esc_html( $label ) . ': ' . esc_html__( 'None', 'threaddesk' ) . '</em></p>';
		}
		$out = '<p><strong>' . esc_html( $label ) . ':</strong></p><ul>';
		foreach ( $posts as $item ) {
			if ( ! $item instanceof WP_Post ) { continue; }
			$out .= '<li><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">' . esc_html( $item->post_title ?: ('#' . $item->ID) ) . '</a></li>';
		}
		$out .= '</ul>';
		return $out;
	}


	private function get_internal_reference_counter_option_key( $post_type ) {
		return 'tta_threaddesk_ref_counter_' . sanitize_key( (string) $post_type );
	}

	private function assign_internal_reference( $post_id, $post_type ) {
		$post_id   = absint( $post_id );
		$post_type = sanitize_key( (string) $post_type );
		if ( ! $post_id || '' === $post_type ) {
			return '';
		}
		$existing = (string) get_post_meta( $post_id, 'tta_internal_ref', true );
		if ( '' !== $existing ) {
			return $existing;
		}
		$counter_key = $this->get_internal_reference_counter_option_key( $post_type );
		$next_ref    = absint( get_option( $counter_key, 0 ) ) + 1;
		update_option( $counter_key, $next_ref, false );
		update_post_meta( $post_id, 'tta_internal_ref', (string) $next_ref );
		return (string) $next_ref;
	}

	public function maybe_assign_internal_reference( $post_id, $post, $update ) {
		unset( $update );
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'tta_quote', 'tta_design', 'tta_layout' ), true ) ) {
			return;
		}
		$this->assign_internal_reference( $post_id, $post->post_type );
	}

	private function filter_entity_admin_columns( $columns ) {
		$columns = is_array( $columns ) ? $columns : array();
		$new_columns = array();
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
		}
		$new_columns['tta_internal_ref'] = __( 'Reference #', 'threaddesk' );
		$new_columns['title']            = __( 'Title', 'threaddesk' );
		$new_columns['tta_owner']        = __( 'User', 'threaddesk' );
		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, array( 'cb', 'title', 'author' ), true ) ) {
				continue;
			}
			$new_columns[ $key ] = $label;
		}
		if ( ! isset( $new_columns['date'] ) ) {
			$new_columns['date'] = __( 'Date', 'threaddesk' );
		}
		return $new_columns;
	}

	public function filter_quote_admin_columns( $columns ) {
		$columns = $this->filter_entity_admin_columns( $columns );
		$updated = array();
		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'tta_internal_ref' === $key ) {
				$updated['tta_quote_status'] = __( 'Status', 'threaddesk' );
			}
		}
		return $updated;
	}

	public function filter_design_admin_columns( $columns ) {
		$columns = $this->filter_entity_admin_columns( $columns );
		$updated = array();
		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'tta_internal_ref' === $key ) {
				$updated['tta_design_status'] = __( 'Status', 'threaddesk' );
			}
		}
		return $updated;
	}

	public function filter_layout_admin_columns( $columns ) {
		$columns = $this->filter_entity_admin_columns( $columns );
		$updated = array();
		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'tta_internal_ref' === $key ) {
				$updated['tta_layout_status'] = __( 'Status', 'threaddesk' );
			}
		}
		return $updated;
	}

	public function render_custom_admin_columns( $column, $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, array( 'tta_quote', 'tta_design', 'tta_layout' ), true ) ) {
			return;
		}
		if ( 'tta_internal_ref' === $column ) {
			echo esc_html( $this->assign_internal_reference( $post_id, $post->post_type ) );
			return;
		}
		if ( 'tta_owner' === $column ) {
			$owner = get_userdata( (int) $post->post_author );
			if ( ! $owner ) {
				echo esc_html__( 'Unknown', 'threaddesk' );
				return;
			}
			$url = add_query_arg(
				array(
					'post_type' => $post->post_type,
					'author'    => (int) $owner->ID,
				),
				admin_url( 'edit.php' )
			);
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $owner->display_name ) . '</a>';
			return;
		}
		if ( 'tta_design_status' === $column ) {
			if ( 'tta_design' !== $post->post_type ) {
				echo '&mdash;';
				return;
			}
			$status = $this->get_design_status( $post_id );
			$options = $this->get_design_status_options();
			$rejection_reason = $this->sanitize_design_rejection_reason( get_post_meta( $post_id, 'design_rejection_reason', true ) );
			echo '<span class="threaddesk-design-status" data-threaddesk-design-status="' . esc_attr( $status ) . '" data-threaddesk-design-rejection-reason="' . esc_attr( $rejection_reason ) . '">' . esc_html( isset( $options[ $status ] ) ? $options[ $status ] : $options['pending'] ) . '</span>';
			return;
		}
		if ( 'tta_layout_status' === $column ) {
			if ( 'tta_layout' !== $post->post_type ) {
				echo '&mdash;';
				return;
			}
			$status = $this->get_layout_status( $post_id );
			$options = $this->get_layout_status_options();
			$rejection_reason = $this->sanitize_layout_rejection_reason( get_post_meta( $post_id, 'layout_rejection_reason', true ) );
			echo '<span class="threaddesk-layout-status" data-threaddesk-layout-status="' . esc_attr( $status ) . '" data-threaddesk-layout-rejection-reason="' . esc_attr( $rejection_reason ) . '">' . esc_html( isset( $options[ $status ] ) ? $options[ $status ] : $options['pending'] ) . '</span>';
			return;
		}
		if ( 'tta_quote_status' === $column ) {
			if ( 'tta_quote' !== $post->post_type ) {
				echo '&mdash;';
				return;
			}
			$status = $this->get_quote_status( $post_id );
			$options = $this->get_quote_status_options();
			echo '<span class="threaddesk-quote-status" data-threaddesk-quote-status="' . esc_attr( $status ) . '">' . esc_html( isset( $options[ $status ] ) ? $options[ $status ] : $options['pending'] ) . '</span>';
			return;
		}
	}

	private function filter_entity_sortable_columns( $columns ) {
		$columns = is_array( $columns ) ? $columns : array();
		$columns['tta_internal_ref'] = 'tta_internal_ref';
		$columns['tta_owner'] = 'tta_owner';
		return $columns;
	}

	public function filter_quote_sortable_columns( $columns ) {
		$columns = $this->filter_entity_sortable_columns( $columns );
		$columns['tta_quote_status'] = 'tta_quote_status';
		return $columns;
	}

	public function filter_design_sortable_columns( $columns ) {
		$columns = $this->filter_entity_sortable_columns( $columns );
		$columns['tta_design_status'] = 'tta_design_status';
		return $columns;
	}

	public function filter_layout_sortable_columns( $columns ) {
		$columns = $this->filter_entity_sortable_columns( $columns );
		$columns['tta_layout_status'] = 'tta_layout_status';
		return $columns;
	}

	public function handle_admin_sorting_queries( $query ) {
		if ( ! is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
			return;
		}
		$post_type = (string) $query->get( 'post_type' );
		if ( ! in_array( $post_type, array( 'tta_quote', 'tta_design', 'tta_layout' ), true ) ) {
			return;
		}
		$orderby = (string) $query->get( 'orderby' );
		if ( 'tta_internal_ref' === $orderby ) {
			$query->set( 'meta_key', 'tta_internal_ref' );
			$query->set( 'orderby', 'meta_value_num' );
			return;
		}
		if ( 'tta_owner' === $orderby ) {
			$query->set( 'orderby', 'author' );
			return;
		}
		if ( 'tta_design_status' === $orderby && 'tta_design' === $post_type ) {
			$query->set( 'meta_key', 'design_status' );
			$query->set( 'orderby', 'meta_value' );
			return;
		}
		if ( 'tta_layout_status' === $orderby && 'tta_layout' === $post_type ) {
			$query->set( 'meta_key', 'layout_status' );
			$query->set( 'orderby', 'meta_value' );
			return;
		}
		if ( 'tta_quote_status' === $orderby && 'tta_quote' === $post_type ) {
			$query->set( 'meta_key', 'status' );
			$query->set( 'orderby', 'meta_value' );
		}
		echo '</select>';
		echo '</label>';
		echo '</div>';
		echo '</fieldset>';
	}

	public function render_quote_quick_edit_status_field( $column_name, $post_type ) {
		if ( 'tta_quote_status' !== $column_name || 'tta_quote' !== $post_type ) {
			return;
		}
		$options = $this->get_quote_status_options();
		wp_nonce_field( 'tta_threaddesk_quote_status_quick_edit', 'tta_threaddesk_quote_status_nonce' );
		echo '<fieldset class="inline-edit-col-right">';
		echo '<div class="inline-edit-col">';
		echo '<label class="inline-edit-group">';
		echo '<span class="title">' . esc_html__( 'Quote status', 'threaddesk' ) . '</span>';
		echo '<select name="threaddesk_quote_status">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</div>';
		echo '</fieldset>';
	}

	public function render_quote_quick_edit_status_script() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-tta_quote' !== $screen->id ) {
			return;
		}
		?>
		<script>
		jQuery(function ($) {
			const $wpInlineEdit = inlineEditPost.edit;
			inlineEditPost.edit = function (postId) {
				$wpInlineEdit.apply(this, arguments);
				let id = 0;
				if (typeof(postId) === 'object') {
					id = parseInt(this.getId(postId), 10);
				} else {
					id = parseInt(postId, 10);
				}
				if (!id) { return; }
				const $editRow = $('#edit-' + id);
				const $postRow = $('#post-' + id);
				const rawStatus = String(($postRow.find('.threaddesk-quote-status').attr('data-threaddesk-quote-status') || 'pending')).toLowerCase();
				const status = rawStatus === 'approved' || rawStatus === 'rejected' ? rawStatus : 'pending';
				$editRow.find('select[name="threaddesk_quote_status"]').val(status);
			};
		});
		</script>
		<?php
	}

	public function render_quote_quick_edit_status_inline_field( $column_name, $post_type ) {
		if ( 'tta_quote_status' !== $column_name || 'tta_quote' !== $post_type ) {
			return;
		}
		$options = $this->get_quote_status_options();
		wp_nonce_field( 'tta_threaddesk_quote_status_quick_edit', 'tta_threaddesk_quote_status_nonce' );
		echo '<fieldset class="inline-edit-col-right">';
		echo '<div class="inline-edit-col">';
		echo '<label class="inline-edit-group">';
		echo '<span class="title">' . esc_html__( 'Quote status', 'threaddesk' ) . '</span>';
		echo '<select name="threaddesk_quote_status">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</div>';
		echo '</fieldset>';
	}

	public function render_quote_quick_edit_status_inline_script() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-tta_quote' !== $screen->id ) {
			return;
		}
		?>
		<script>
		jQuery(function ($) {
			const $wpInlineEdit = inlineEditPost.edit;
			inlineEditPost.edit = function (postId) {
				$wpInlineEdit.apply(this, arguments);
				let id = 0;
				if (typeof(postId) === 'object') {
					id = parseInt(this.getId(postId), 10);
				} else {
					id = parseInt(postId, 10);
				}
				if (!id) { return; }
				const $editRow = $('#edit-' + id);
				const $postRow = $('#post-' + id);
				const rawStatus = String(($postRow.find('.threaddesk-quote-status').attr('data-threaddesk-quote-status') || 'pending')).toLowerCase();
				const status = rawStatus === 'approved' || rawStatus === 'rejected' ? rawStatus : 'pending';
				$editRow.find('select[name="threaddesk_quote_status"]').val(status);
			};
		});
		</script>
		<?php
	}

	public function render_design_quick_edit_status_field( $column_name, $post_type ) {
		if ( 'tta_design_status' !== $column_name || 'tta_design' !== $post_type ) {
			return;
		}
		$options = $this->get_design_status_options();
		$rejection_reason_options = $this->get_design_rejection_reason_options();
		wp_nonce_field( 'tta_threaddesk_design_status_quick_edit', 'tta_threaddesk_design_status_nonce' );
		echo '<fieldset class="inline-edit-col-right">';
		echo '<div class="inline-edit-col">';
		echo '<label class="inline-edit-group">';
		echo '<span class="title">' . esc_html__( 'Design status', 'threaddesk' ) . '</span>';
		echo '<select name="threaddesk_design_status">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '<label class="inline-edit-group" data-threaddesk-quickedit-reason-wrap style="display:none;">';
		echo '<span class="title">' . esc_html__( 'Rejection reason', 'threaddesk' ) . '</span>';
		echo '<select name="threaddesk_design_rejection_reason">';
		echo '<option value="">' . esc_html__( 'Select a reason', 'threaddesk' ) . '</option>';
		foreach ( $rejection_reason_options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</div>';
		echo '</fieldset>';
	}

	public function render_design_quick_edit_status_script() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-tta_design' !== $screen->id ) {
			return;
		}
		?>
		<script>
		jQuery(function ($) {
			const $wpInlineEdit = inlineEditPost.edit;
			const syncReasonVisibility = function ($row) {
				const status = String($row.find('select[name="threaddesk_design_status"]').val() || '').toLowerCase();
				$row.find('[data-threaddesk-quickedit-reason-wrap]').toggle(status === 'rejected');
			};
			inlineEditPost.edit = function (postId) {
				$wpInlineEdit.apply(this, arguments);
				let id = 0;
				if (typeof(postId) === 'object') {
					id = parseInt(this.getId(postId), 10);
				} else {
					id = parseInt(postId, 10);
				}
				if (!id) { return; }
				const $editRow = $('#edit-' + id);
				const $postRow = $('#post-' + id);
				const rawStatus = String(($postRow.find('.threaddesk-design-status').attr('data-threaddesk-design-status') || 'pending')).toLowerCase();
				const rawReason = String(($postRow.find('.threaddesk-design-status').attr('data-threaddesk-design-rejection-reason') || '')).toLowerCase();
				const status = rawStatus === 'approved' || rawStatus === 'rejected' ? rawStatus : 'pending';
				$editRow.find('select[name="threaddesk_design_status"]').val(status);
				$editRow.find('select[name="threaddesk_design_rejection_reason"]').val(rawReason);
				syncReasonVisibility($editRow);
			};

			$(document).on('change', 'tr.inline-editor select[name="threaddesk_design_status"]', function () {
				syncReasonVisibility($(this).closest('tr.inline-editor'));
			});
		});
		</script>
		<?php
	}

	public function render_layout_quick_edit_status_field( $column_name, $post_type ) {
		if ( 'tta_layout_status' !== $column_name || 'tta_layout' !== $post_type ) {
			return;
		}
		$options = $this->get_layout_status_options();
		$rejection_reason_options = $this->get_layout_rejection_reason_options();
		wp_nonce_field( 'tta_threaddesk_layout_status_quick_edit', 'tta_threaddesk_layout_status_nonce' );
		echo '<fieldset class="inline-edit-col-right">';
		echo '<div class="inline-edit-col">';
		echo '<label class="inline-edit-group">';
		echo '<span class="title">' . esc_html__( 'Layout status', 'threaddesk' ) . '</span>';
		echo '<select name="threaddesk_layout_status">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '<label class="inline-edit-group" data-threaddesk-layout-quickedit-reason-wrap style="display:none;">';
		echo '<span class="title">' . esc_html__( 'Rejection reason', 'threaddesk' ) . '</span>';
		echo '<select name="threaddesk_layout_rejection_reason">';
		echo '<option value="">' . esc_html__( 'Select a reason', 'threaddesk' ) . '</option>';
		foreach ( $rejection_reason_options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</div>';
		echo '</fieldset>';
	}

	public function render_layout_quick_edit_status_script() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-tta_layout' !== $screen->id ) {
			return;
		}
		?>
		<script>
		jQuery(function ($) {
			const $wpInlineEdit = inlineEditPost.edit;
			const syncReasonVisibility = function ($row) {
				const status = String($row.find('select[name="threaddesk_layout_status"]').val() || '').toLowerCase();
				$row.find('[data-threaddesk-layout-quickedit-reason-wrap]').toggle(status === 'rejected');
			};
			inlineEditPost.edit = function (postId) {
				$wpInlineEdit.apply(this, arguments);
				let id = 0;
				if (typeof(postId) === 'object') {
					id = parseInt(this.getId(postId), 10);
				} else {
					id = parseInt(postId, 10);
				}
				if (!id) { return; }
				const $editRow = $('#edit-' + id);
				const $postRow = $('#post-' + id);
				const rawStatus = String(($postRow.find('.threaddesk-layout-status').attr('data-threaddesk-layout-status') || 'pending')).toLowerCase();
				const rawReason = String(($postRow.find('.threaddesk-layout-status').attr('data-threaddesk-layout-rejection-reason') || '')).toLowerCase();
				const status = rawStatus === 'approved' || rawStatus === 'rejected' ? rawStatus : 'pending';
				$editRow.find('select[name="threaddesk_layout_status"]').val(status);
				$editRow.find('select[name="threaddesk_layout_rejection_reason"]').val(rawReason);
				syncReasonVisibility($editRow);
			};

			$(document).on('change', 'tr.inline-editor select[name="threaddesk_layout_status"]', function () {
				syncReasonVisibility($(this).closest('tr.inline-editor'));
			});
		});
		</script>
		<?php
	}

	public function handle_quote_status_save( $post_id, $post ) {
		if ( ! $post || 'tta_quote' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['threaddesk_quote_status'] ) ) {
			return;
		}
		$has_quick_edit_nonce = isset( $_POST['tta_threaddesk_quote_status_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_quote_status_nonce'] ) ), 'tta_threaddesk_quote_status_quick_edit' );
		$has_meta_box_nonce   = isset( $_POST['tta_threaddesk_quote_status_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_quote_status_meta_nonce'] ) ), 'tta_threaddesk_quote_status_meta_box' );
		if ( ! $has_quick_edit_nonce && ! $has_meta_box_nonce ) {
			return;
		}
		$status = $this->sanitize_quote_status( wp_unslash( $_POST['threaddesk_quote_status'] ) );
		update_post_meta( $post_id, 'status', $status );
		$label = 'approved' === $status ? __( 'Quote approved', 'threaddesk' ) : ( 'rejected' === $status ? __( 'Quote rejected', 'threaddesk' ) : __( 'Quote marked pending', 'threaddesk' ) );
		$this->log_user_activity( (int) $post->post_author, sprintf( __( '%1$s: %2$s', 'threaddesk' ), $label, get_the_title( $post_id ) ), 'quote' );
	}

	public function handle_design_status_save( $post_id, $post ) {
		if ( ! $post || 'tta_design' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['threaddesk_design_status'] ) ) {
			return;
		}
		$has_quick_edit_nonce = isset( $_POST['tta_threaddesk_design_status_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_design_status_nonce'] ) ), 'tta_threaddesk_design_status_quick_edit' );
		$has_meta_box_nonce   = isset( $_POST['tta_threaddesk_design_status_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_design_status_meta_nonce'] ) ), 'tta_threaddesk_design_status_meta_box' );
		if ( ! $has_quick_edit_nonce && ! $has_meta_box_nonce ) {
			return;
		}
		$status = $this->sanitize_design_status( wp_unslash( $_POST['threaddesk_design_status'] ) );
		$reason = isset( $_POST['threaddesk_design_rejection_reason'] ) ? $this->sanitize_design_rejection_reason( wp_unslash( $_POST['threaddesk_design_rejection_reason'] ) ) : '';
		if ( 'rejected' === $status && '' === $reason ) {
			return;
		}
		update_post_meta( $post_id, 'design_status', $status );
		if ( 'rejected' === $status ) {
			update_post_meta( $post_id, 'design_rejection_reason', $reason );
		} else {
			delete_post_meta( $post_id, 'design_rejection_reason' );
		}
		$label = 'approved' === $status ? __( 'Design approved', 'threaddesk' ) : ( 'rejected' === $status ? __( 'Design rejected', 'threaddesk' ) : __( 'Design marked pending', 'threaddesk' ) );
		$this->log_user_activity( (int) $post->post_author, sprintf( __( '%1$s: %2$s', 'threaddesk' ), $label, get_the_title( $post_id ) ), 'design' );
	}

	public function handle_layout_status_save( $post_id, $post ) {
		if ( ! $post || 'tta_layout' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['threaddesk_layout_status'] ) ) {
			return;
		}
		$has_quick_edit_nonce = isset( $_POST['tta_threaddesk_layout_status_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_layout_status_nonce'] ) ), 'tta_threaddesk_layout_status_quick_edit' );
		$has_meta_box_nonce   = isset( $_POST['tta_threaddesk_layout_status_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_threaddesk_layout_status_meta_nonce'] ) ), 'tta_threaddesk_layout_status_meta_box' );
		if ( ! $has_quick_edit_nonce && ! $has_meta_box_nonce ) {
			return;
		}
		$status = $this->sanitize_layout_status( wp_unslash( $_POST['threaddesk_layout_status'] ) );
		$reason = isset( $_POST['threaddesk_layout_rejection_reason'] ) ? $this->sanitize_layout_rejection_reason( wp_unslash( $_POST['threaddesk_layout_rejection_reason'] ) ) : '';
		if ( 'rejected' === $status && '' === $reason ) {
			return;
		}
		update_post_meta( $post_id, 'layout_status', $status );
		if ( 'rejected' === $status ) {
			update_post_meta( $post_id, 'layout_rejection_reason', $reason );
		} else {
			delete_post_meta( $post_id, 'layout_rejection_reason' );
		}
		$label = 'approved' === $status ? __( 'Layout approved', 'threaddesk' ) : ( 'rejected' === $status ? __( 'Layout rejected', 'threaddesk' ) : __( 'Layout marked pending', 'threaddesk' ) );
		$this->log_user_activity( (int) $post->post_author, sprintf( __( '%1$s: %2$s', 'threaddesk' ), $label, get_the_title( $post_id ) ), 'layout' );
	}


	public function handle_entity_updated_activity( $post_id, $post_after, $post_before ) {
		if ( ! $post_after instanceof WP_Post || ! $post_before instanceof WP_Post ) {
			return;
		}
		if ( 'tta_quote' !== $post_after->post_type ) {
			return;
		}
		$author_id = (int) $post_after->post_author;
		if ( $author_id <= 0 ) {
			return;
		}
		if ( $post_after->post_title === $post_before->post_title && $post_after->post_status === $post_before->post_status ) {
			return;
		}
		$this->log_user_activity( $author_id, sprintf( __( 'Quote updated: %s', 'threaddesk' ), $post_after->post_title ), 'quote' );
	}

	public function handle_entity_deleted_activity( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'tta_quote' !== $post->post_type ) {
			return;
		}
		$author_id = (int) $post->post_author;
		if ( $author_id <= 0 ) {
			return;
		}
		$this->log_user_activity( $author_id, sprintf( __( 'Quote deleted: %s', 'threaddesk' ), $post->post_title ), 'quote' );
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
		$this->merge_guest_drafts_to_user( (int) $user->ID );
		$this->auth_login_success = true;
	}
}
