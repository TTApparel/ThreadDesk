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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_admin_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'maybe_assign_internal_reference' ), 10, 3 );
		add_filter( 'manage_edit-tta_quote_columns', array( $this, 'filter_quote_admin_columns' ) );
		add_filter( 'manage_edit-tta_design_columns', array( $this, 'filter_design_admin_columns' ) );
		add_filter( 'manage_edit-tta_layout_columns', array( $this, 'filter_layout_admin_columns' ) );
		add_action( 'manage_tta_quote_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
		add_action( 'manage_tta_design_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
		add_action( 'manage_tta_layout_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
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
		add_action( 'admin_post_tta_threaddesk_rename_design', array( $this, 'handle_rename_design' ) );
		add_action( 'admin_post_tta_threaddesk_delete_design', array( $this, 'handle_delete_design' ) );
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
		if ( 'toplevel_page_tta-threaddesk' !== $hook && false === strpos( (string) $hook, 'tta-threaddesk-settings' ) ) {
			return;
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
		<script>
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


	private function get_designs_redirect_url() {
		return add_query_arg( 'td_section', 'designs', trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) ) );
	}

	private function get_layouts_redirect_url( $query_args = array() ) {
		$base = add_query_arg( 'td_section', 'layouts', trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) ) );
		return ! empty( $query_args ) && is_array( $query_args ) ? add_query_arg( $query_args, $base ) : $base;
	}


	private function get_user_design_storage( $user_id ) {
		$uploads = wp_upload_dir();
		$user    = get_userdata( $user_id );
		$login   = $user ? $user->user_login : 'user-' . (string) $user_id;
		$folder  = sanitize_file_name( $login );
		if ( '' === $folder ) {
			$folder = 'user-' . (string) $user_id;
		}

		$base_dir = trailingslashit( $uploads['basedir'] ) . 'ThreadDesk/Designs/' . $folder;
		$base_url = trailingslashit( $uploads['baseurl'] ) . 'ThreadDesk/Designs/' . rawurlencode( $folder );

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

	public function handle_save_design() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_save_design' );

		$current_user_id   = get_current_user_id();
		$existing_design_id = isset( $_POST['threaddesk_design_id'] ) ? absint( $_POST['threaddesk_design_id'] ) : 0;
		$design_id         = 0;
		$upload            = null;
		$file_name         = '';
		$title_input       = isset( $_POST['threaddesk_design_title'] ) ? sanitize_text_field( wp_unslash( $_POST['threaddesk_design_title'] ) ) : '';
		$storage           = $this->get_user_design_storage( $current_user_id );
		$return_context   = isset( $_POST['threaddesk_design_return_context'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_design_return_context'] ) ) : '';
		$return_category  = isset( $_POST['threaddesk_design_return_layout_category'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_design_return_layout_category'] ) ) : '';
		$return_placement = isset( $_POST['threaddesk_design_return_layout_placement'] ) ? sanitize_key( wp_unslash( $_POST['threaddesk_design_return_layout_placement'] ) ) : '';
		$redirect_url     = $this->get_designs_redirect_url();
		if ( 'layout_viewer' === $return_context ) {
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
			if ( $existing && 'tta_design' === $existing->post_type && (int) $existing->post_author === $current_user_id ) {
				$design_id = $existing_design_id;
				$file_name = (string) get_post_meta( $design_id, 'design_file_name', true );
			}
		}

		$old_original_path = $design_id ? (string) get_post_meta( $design_id, 'design_original_file_path', true ) : '';
		$old_svg_path      = $design_id ? (string) get_post_meta( $design_id, 'design_svg_file_path', true ) : '';
		$old_mockup_path   = $design_id ? (string) get_post_meta( $design_id, 'design_mockup_file_path', true ) : '';

		if ( ! empty( $_FILES['threaddesk_design_file']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$upload = wp_handle_upload( $_FILES['threaddesk_design_file'], array( 'test_form' => false ) );
			if ( isset( $upload['error'] ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Design upload failed. Please try again.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}
			$file_name = sanitize_file_name( wp_basename( $upload['file'] ) );
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

			$design_id = wp_insert_post(
				array(
					'post_type'   => 'tta_design',
					'post_status' => 'private',
					'post_title'  => $title,
					'post_author' => $current_user_id,
				)
			);

			if ( ! $design_id || is_wp_error( $design_id ) ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Unable to save design right now.', 'threaddesk' ), 'error' );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

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
		if ( $upload && isset( $upload['file'] ) ) {
			$incoming_name      = sanitize_file_name( wp_basename( $upload['file'] ) );
			$incoming_extension = strtolower( pathinfo( $incoming_name, PATHINFO_EXTENSION ) );
			$target_seed_name   = $incoming_name;
			$target_name        = wp_unique_filename( $storage['dir'], $target_seed_name );
			$target_path        = trailingslashit( $storage['dir'] ) . $target_name;
			$target_url         = trailingslashit( $storage['url'] ) . rawurlencode( $target_name );
			$move_succeeded     = @rename( $upload['file'], $target_path );

			if ( ! $move_succeeded ) {
				$move_succeeded = @copy( $upload['file'], $target_path );
				if ( $move_succeeded ) {
					@unlink( $upload['file'] );
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

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Design saved successfully.', 'threaddesk' ), 'success' );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function handle_rename_design() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_rename_design' );

		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;
		$title     = isset( $_POST['design_title'] ) ? sanitize_text_field( wp_unslash( $_POST['design_title'] ) ) : '';
		$title     = trim( (string) $title );
		$design    = get_post( $design_id );

		if ( ! $design || 'tta_design' !== $design->post_type || (int) $design->post_author !== get_current_user_id() ) {
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

		$storage = $this->get_user_design_storage( get_current_user_id() );
		if ( ! $storage ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Unable to access your design storage directory.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_designs_redirect_url() );
			exit;
		}

		wp_update_post( array( 'ID' => $design_id, 'post_title' => $title ) );
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


	public function handle_delete_design() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}

		check_admin_referer( 'tta_threaddesk_delete_design' );
		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;
		$design    = get_post( $design_id );
		if ( ! $design || 'tta_design' !== $design->post_type || (int) $design->post_author !== get_current_user_id() ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Invalid design.', 'threaddesk' ), 'error' );
			}
			wp_safe_redirect( $this->get_designs_redirect_url() );
			exit;
		}

		$this->maybe_delete_design_files_for_post( $design_id );
		wp_delete_post( $design_id, true );
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Design deleted.', 'threaddesk' ), 'success' );
		}
		wp_safe_redirect( $this->get_designs_redirect_url() );
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


	public function render_admin_quotes_page() {
		wp_safe_redirect( admin_url( 'edit.php?post_type=tta_quote' ) );
		exit;
	}

	public function render_admin_users_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'threaddesk' ) );
		}
		$selected_user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		echo '<div class="wrap"><h1>' . esc_html__( 'ThreadDesk Users', 'threaddesk' ) . '</h1>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'User', 'threaddesk' ) . '</th><th>' . esc_html__( 'Email', 'threaddesk' ) . '</th><th>' . esc_html__( 'Designs', 'threaddesk' ) . '</th><th>' . esc_html__( 'Layouts', 'threaddesk' ) . '</th><th>' . esc_html__( 'Quotes', 'threaddesk' ) . '</th></tr></thead><tbody>';
		foreach ( $users as $user ) {
			$designs = count_user_posts( $user->ID, 'tta_design' );
			$layouts = count_user_posts( $user->ID, 'tta_layout' );
			$quotes  = count_user_posts( $user->ID, 'tta_quote' );
			$link = add_query_arg( array( 'page' => 'tta-threaddesk-users', 'user_id' => $user->ID ), admin_url( 'admin.php' ) );
			echo '<tr><td><a href="' . esc_url( $link ) . '">' . esc_html( $user->display_name ) . '</a></td><td>' . esc_html( $user->user_email ) . '</td><td>' . esc_html( (string) $designs ) . '</td><td>' . esc_html( (string) $layouts ) . '</td><td>' . esc_html( (string) $quotes ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( $selected_user_id ) {
			$this->render_selected_user_detail( $selected_user_id );
		}
		echo '</div>';
	}

	private function render_selected_user_detail( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) { return; }
		echo '<h2>' . sprintf( esc_html__( 'User Detail: %s', 'threaddesk' ), esc_html( $user->display_name ) ) . '</h2>';
		$post_types = array( 'tta_design' => __( 'Designs', 'threaddesk' ), 'tta_layout' => __( 'Layouts', 'threaddesk' ), 'tta_quote' => __( 'Quotes', 'threaddesk' ) );
		foreach ( $post_types as $post_type => $label ) {
			$posts = get_posts( array( 'post_type' => $post_type, 'author' => $user_id, 'numberposts' => 50, 'post_status' => array( 'publish', 'draft', 'pending', 'private' ) ) );
			echo '<h3>' . esc_html( $label ) . '</h3>';
			if ( empty( $posts ) ) { echo '<p>' . esc_html__( 'None found.', 'threaddesk' ) . '</p>'; continue; }
			echo '<ul>';
			foreach ( $posts as $post_item ) {
				echo '<li><a href="' . esc_url( get_edit_post_link( $post_item->ID ) ) . '">' . esc_html( $post_item->post_title ?: ('#' . $post_item->ID) ) . '</a></li>';
			}
			echo '</ul>';
		}
	}

	public function register_admin_meta_boxes() {
		add_meta_box( 'threaddesk_design_detail', __( 'ThreadDesk Design Details', 'threaddesk' ), array( $this, 'render_design_admin_meta_box' ), 'tta_design', 'normal', 'high' );
		add_meta_box( 'threaddesk_design_usage', __( 'Used Layouts / Quotes / Invoices', 'threaddesk' ), array( $this, 'render_design_usage_admin_meta_box' ), 'tta_design', 'side', 'default' );
		add_meta_box( 'threaddesk_layout_detail', __( 'ThreadDesk Layout Details', 'threaddesk' ), array( $this, 'render_layout_admin_meta_box' ), 'tta_layout', 'normal', 'high' );
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
		$dimensions = $this->get_image_dimensions_from_url( $mockup_url );
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
		echo '<p><strong>' . esc_html__( 'User', 'threaddesk' ) . ':</strong> ' . esc_html( $owner ? $owner->display_name : __( 'Unknown', 'threaddesk' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Category', 'threaddesk' ) . ':</strong> ' . esc_html( $category ?: __( 'Not set', 'threaddesk' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Created', 'threaddesk' ) . ':</strong> ' . esc_html( $created ?: $post->post_date ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Last edited', 'threaddesk' ) . ':</strong> ' . esc_html( $post->post_modified ) . '</p>';
		$meta = get_post_meta( $post->ID );
		echo '<p><strong>' . esc_html__( 'Design + placement/sizing data', 'threaddesk' ) . ':</strong></p><ul>';
		foreach ( $meta as $key => $values ) {
			if ( false === strpos( $key, 'design' ) && false === strpos( $key, 'placement' ) && false === strpos( $key, 'layout' ) && false === strpos( $key, 'size' ) ) { continue; }
			$value = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : '';
			if ( is_array( $value ) || is_object( $value ) ) { $value = wp_json_encode( $value ); }
			echo '<li><code>' . esc_html( $key ) . '</code>: ' . esc_html( (string) $value ) . '</li>';
		}
		echo '</ul>';
		$related_designs = $this->find_related_posts_by_id_in_meta( $post->ID, 'tta_design', true );
		$related_quotes = $this->find_related_posts_by_id_in_meta( $post->ID, 'tta_quote' );
		$related_invoices = $this->find_related_posts_by_id_in_meta( $post->ID, 'shop_order' );
		echo '<p><strong>' . esc_html__( 'Related items', 'threaddesk' ) . ':</strong></p>';
		echo $this->render_related_post_links_list( $related_designs, __( 'Designs', 'threaddesk' ) );
		echo $this->render_related_post_links_list( $related_quotes, __( 'Quotes', 'threaddesk' ) );
		echo $this->render_related_post_links_list( $related_invoices, __( 'Invoices', 'threaddesk' ) );
	}

	private function get_image_dimensions_from_url( $url ) {
		if ( ! $url ) { return __( 'Unknown', 'threaddesk' ); }
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
		return $this->filter_entity_admin_columns( $columns );
	}

	public function filter_design_admin_columns( $columns ) {
		return $this->filter_entity_admin_columns( $columns );
	}

	public function filter_layout_admin_columns( $columns ) {
		return $this->filter_entity_admin_columns( $columns );
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
	}

	private function filter_entity_sortable_columns( $columns ) {
		$columns = is_array( $columns ) ? $columns : array();
		$columns['tta_internal_ref'] = 'tta_internal_ref';
		$columns['tta_owner'] = 'tta_owner';
		return $columns;
	}

	public function filter_quote_sortable_columns( $columns ) {
		return $this->filter_entity_sortable_columns( $columns );
	}

	public function filter_design_sortable_columns( $columns ) {
		return $this->filter_entity_sortable_columns( $columns );
	}

	public function filter_layout_sortable_columns( $columns ) {
		return $this->filter_entity_sortable_columns( $columns );
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
		}
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
