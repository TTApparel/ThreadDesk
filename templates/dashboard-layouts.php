<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = isset( $context ) ? $context : array();
$user    = isset( $context['user'] ) ? $context['user'] : null;
$cover   = ! empty( $context['cover_image'] ) ? $context['cover_image'] : 'https://via.placeholder.com/1200x240.png?text=ThreadDesk+Cover';
$company = ! empty( $context['company'] ) ? $context['company'] : __( 'Client Company', 'threaddesk' );
$client_name = ! empty( $context['client_name'] ) ? $context['client_name'] : __( 'Client Name', 'threaddesk' );
$avatar_url  = ! empty( $context['avatar_url'] ) ? $context['avatar_url'] : '';
$shipping_address = ! empty( $context['shipping_address'] ) && is_array( $context['shipping_address'] ) ? $context['shipping_address'] : array();
$billing_address  = ! empty( $context['billing_address'] ) && is_array( $context['billing_address'] ) ? $context['billing_address'] : array();
$address_source   = $shipping_address ? $shipping_address : $billing_address;
$map_parts = array_filter(
	array(
		isset( $address_source['address_1'] ) ? $address_source['address_1'] : '',
		isset( $address_source['address_2'] ) ? $address_source['address_2'] : '',
		isset( $address_source['city'] ) ? $address_source['city'] : '',
		isset( $address_source['state'] ) ? $address_source['state'] : '',
		isset( $address_source['postcode'] ) ? $address_source['postcode'] : '',
		isset( $address_source['country'] ) ? $address_source['country'] : '',
	)
);
$map_query = trim( implode( ', ', $map_parts ) );
$formatted_address = ! empty( $address_source['formatted'] ) ? wp_strip_all_tags( $address_source['formatted'] ) : '';
if ( '' === $map_query && $formatted_address ) {
	$map_query = trim( preg_replace( '/\s+/', ' ', str_replace( array( "\r\n", "\r", "\n" ), ', ', $formatted_address ) ) );
}
$map_url   = $map_query ? sprintf( 'https://www.google.com/maps?q=%s&output=embed', rawurlencode( $map_query ) ) : '';
$profile_name = '';
if ( $user ) {
	$profile_name = trim( $user->first_name . ' ' . $user->last_name );
	if ( '' === $profile_name ) {
		$profile_name = $user->display_name;
	}
}
$profile_name = $profile_name ? $profile_name : $client_name;
$profile_username = $user ? $user->user_login : __( 'Username', 'threaddesk' );

$nav_base = trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) );

$layout_category_settings = get_option( 'tta_threaddesk_layout_categories', array() );
$placement_slot_labels = array(
	'left_chest'  => __( 'Left Chest', 'threaddesk' ),
	'right_chest' => __( 'Right Chest', 'threaddesk' ),
	'full_chest'  => __( 'Full Chest', 'threaddesk' ),
	'left_sleeve' => __( 'Left Sleeve', 'threaddesk' ),
	'right_sleeve'=> __( 'Right Sleeve', 'threaddesk' ),
	'back'        => __( 'Back', 'threaddesk' ),
);
$placement_categories    = array();
$saved_designs           = array();

$resolve_image_ratio = function ( $url ) {
	$url = is_string( $url ) ? trim( $url ) : '';
	if ( '' === $url ) {
		return 0;
	}
	$uploads = wp_upload_dir();
	$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
	$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
	if ( '' === $baseurl || '' === $basedir || 0 !== strpos( $url, $baseurl ) ) {
		return 0;
	}
	$path = $basedir . substr( $url, strlen( $baseurl ) );
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$size = @getimagesize( $path );
	if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) {
		return 0;
	}
	return (float) $size[0] / (float) $size[1];
};


if ( ! empty( $context['designs'] ) && is_array( $context['designs'] ) ) {
	foreach ( $context['designs'] as $design ) {
		if ( ! ( $design instanceof WP_Post ) ) {
			continue;
		}

		$design_svg_url    = get_post_meta( $design->ID, 'design_svg_file_url', true );
		$design_preview    = get_post_meta( $design->ID, 'design_preview_url', true );
		$design_mockup_url = get_post_meta( $design->ID, 'design_mockup_file_url', true );
		$design_ratio = $resolve_image_ratio( $design_mockup_url );
		if ( $design_ratio <= 0 ) {
			$design_ratio = $resolve_image_ratio( $design_preview );
		}
		$design_title   = trim( (string) $design->post_title );
		if ( '' === $design_title ) {
			$design_title = __( 'Design', 'threaddesk' );
		}

		$saved_designs[] = array(
			'id'      => (int) $design->ID,
			'title'   => $design_title,
			'svg'     => $design_svg_url ? esc_url_raw( $design_svg_url ) : '',
			'preview' => $design_preview ? esc_url_raw( $design_preview ) : '',
			'mockup'  => $design_mockup_url ? esc_url_raw( $design_mockup_url ) : '',
			'ratio'   => $design_ratio > 0 ? $design_ratio : 0,
		);
	}
}

if ( taxonomy_exists( 'product_cat' ) && is_array( $layout_category_settings ) ) {
	uasort(
		$layout_category_settings,
		function ( $a, $b ) {
			$a_order = isset( $a['order'] ) ? absint( $a['order'] ) : 9999;
			$b_order = isset( $b['order'] ) ? absint( $b['order'] ) : 9999;
			return $a_order - $b_order;
		}
	);

	foreach ( $layout_category_settings as $term_id => $settings ) {
		$term_id   = absint( $term_id );
		$settings  = is_array( $settings ) ? $settings : array();
		if ( ! $term_id || empty( $settings['enabled'] ) ) {
			continue;
		}

		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$thumbnail_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );
		$term_thumb   = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';
		$front_image  = ! empty( $settings['front_image'] ) ? esc_url_raw( $settings['front_image'] ) : '';
		$back_image   = ! empty( $settings['back_image'] ) ? esc_url_raw( $settings['back_image'] ) : '';
		$side_image   = ! empty( $settings['side_image'] ) ? esc_url_raw( $settings['side_image'] ) : '';
		$side_label   = isset( $settings['side_label'] ) && 'right' === $settings['side_label'] ? 'right' : 'left';
		$configured_placements = isset( $settings['placements'] ) && is_array( $settings['placements'] ) ? $settings['placements'] : array_keys( $placement_slot_labels );
		$placements = array();
		foreach ( $configured_placements as $placement_key ) {
			$placement_key = sanitize_key( $placement_key );
			if ( isset( $placement_slot_labels[ $placement_key ] ) ) {
				$placements[] = array(
					'key'   => $placement_key,
					'label' => $placement_slot_labels[ $placement_key ],
				);
			}
		}

		$placement_categories[] = array(
			'label'       => $term->name,
			'image_url'   => $front_image ? $front_image : $term_thumb,
			'front_image' => $front_image,
			'back_image'  => $back_image,
			'side_image'  => $side_image,
			'side_label'  => $side_label,
			'placements'  => $placements,
			'term_id'     => (int) $term->term_id,
			'term_slug'   => $term->slug,
		);
	}
}

?>
<div class="threaddesk">
	<div class="threaddesk__sidebar">
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'profile', $nav_base ) ); ?>"><?php echo esc_html__( 'Profile', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'designs', $nav_base ) ); ?>"><?php echo esc_html__( 'Designs', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item is-active" href="<?php echo esc_url( add_query_arg( 'td_section', 'layouts', $nav_base ) ); ?>"><?php echo esc_html__( 'Placements', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'quotes', $nav_base ) ); ?>"><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'invoices', $nav_base ) ); ?>"><?php echo esc_html__( 'Invoices', 'threaddesk' ); ?></a>
	</div>

	<div class="threaddesk__content">
		<div class="threaddesk__header" style="background-image: url('<?php echo esc_url( $cover ); ?>');">

			<?php if ( $map_url ) : ?>
				<div class="threaddesk__header-map" aria-hidden="true">
					<iframe
						title="<?php echo esc_attr__( 'Shipping address map', 'threaddesk' ); ?>"
						src="<?php echo esc_url( $map_url ); ?>"
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"></iframe>
				</div>
			<?php endif; ?>
			<form class="threaddesk__profile" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="tta_threaddesk_avatar_upload" />
				<?php wp_nonce_field( 'tta_threaddesk_avatar_upload' ); ?>
				<div class="threaddesk__profile-details">
					<label class="threaddesk__avatar<?php echo $avatar_url ? ' has-image' : ''; ?>" for="threaddesk_avatar">
						<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr__( 'Company avatar', 'threaddesk' ); ?>" />
						<span class="threaddesk__avatar-overlay"><?php echo esc_html__( 'Upload', 'threaddesk' ); ?></span>
					</label>
					<div class="threaddesk__profile-text">
						<h2><?php echo esc_html( $profile_name ); ?></h2>
						<p><?php echo esc_html( $profile_username ); ?></p>
					</div>
				</div>
				<input class="threaddesk__avatar-input" id="threaddesk_avatar" name="threaddesk_avatar" type="file" accept="image/*" onchange="this.form.submit();" />
			</form>
		</div>

		<div class="threaddesk__section">
			<div class="threaddesk__card-header threaddesk-designer__heading">
				<h3><?php echo esc_html__( 'Saved Placements', 'threaddesk' ); ?></h3>
				<button type="button" class="threaddesk__button" data-threaddesk-layout-open><?php echo esc_html__( 'Add Placement', 'threaddesk' ); ?></button>
			</div>
			<p><?php echo esc_html__( 'Placements are placeholders for now. This area will list approved placements for reuse.', 'threaddesk' ); ?></p>
			<div class="threaddesk__cards">
				<?php if ( ! empty( $context['layouts'] ) ) : ?>
					<?php foreach ( $context['layouts'] as $layout ) : ?>
						<div class="threaddesk__card">
							<h4><?php echo esc_html( $layout->post_title ); ?></h4>
							<p><?php echo esc_html__( 'Status: Ready', 'threaddesk' ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="threaddesk__card">
						<p><?php echo esc_html__( 'No placements found yet.', 'threaddesk' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>
</div>

<div class="threaddesk-layout-modal" aria-hidden="true" data-threaddesk-layout-designs="<?php echo esc_attr( wp_json_encode( $saved_designs ) ); ?>">
	<div class="threaddesk-auth-modal__overlay" data-threaddesk-layout-close></div>
	<div class="threaddesk-auth-modal__panel" role="dialog" aria-label="<?php echo esc_attr__( 'Choose a placement category', 'threaddesk' ); ?>" aria-modal="true">
		<div class="threaddesk-auth-modal__actions">
			<button type="button" class="threaddesk-auth-modal__close" data-threaddesk-layout-close aria-label="<?php echo esc_attr__( 'Close placement modal', 'threaddesk' ); ?>">
				<svg class="threaddesk-auth-modal__close-icon" width="12" height="12" viewBox="0 0 15 15" aria-hidden="true" focusable="false">
					<path d="M1 15a1 1 0 01-.71-.29 1 1 0 010-1.41l5.8-5.8-5.8-5.8A1 1 0 011.7.29l5.8 5.8 5.8-5.8a1 1 0 011.41 1.41l-5.8 5.8 5.8 5.8a1 1 0 01-1.41 1.41l-5.8-5.8-5.8 5.8A1 1 0 011 15z"></path>
				</svg>
			</button>
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
						<button type="button" class="threaddesk-layout-viewer__angle is-active" data-threaddesk-layout-angle="front">
							<div class="threaddesk-layout-viewer__angle-image-wrap">
								<img src="" alt="" data-threaddesk-layout-angle-image="front" />
							</div>
							<span><?php echo esc_html__( 'Front', 'threaddesk' ); ?></span>
						</button>
						<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-layout-angle="left">
							<div class="threaddesk-layout-viewer__angle-image-wrap">
								<img src="" alt="" data-threaddesk-layout-angle-image="left" />
							</div>
							<span><?php echo esc_html__( 'Left', 'threaddesk' ); ?></span>
						</button>
						<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-layout-angle="back">
							<div class="threaddesk-layout-viewer__angle-image-wrap">
								<img src="" alt="" data-threaddesk-layout-angle-image="back" />
							</div>
							<span><?php echo esc_html__( 'Back', 'threaddesk' ); ?></span>
						</button>
						<button type="button" class="threaddesk-layout-viewer__angle" data-threaddesk-layout-angle="right">
							<div class="threaddesk-layout-viewer__angle-image-wrap">
								<img src="" alt="" data-threaddesk-layout-angle-image="right" />
							</div>
							<span><?php echo esc_html__( 'Right', 'threaddesk' ); ?></span>
						</button>
					</div>
				</div>
				<div class="threaddesk-layout-viewer__design-panel">
					<div class="threaddesk-layout-viewer__panel-step" data-threaddesk-layout-panel-step="placements">
						<h4><?php echo esc_html__( 'Choose Placement', 'threaddesk' ); ?></h4>
						<div class="threaddesk-layout-viewer__placement-list" data-threaddesk-layout-placement-list></div>
						<p class="threaddesk-layout-viewer__placement-empty" data-threaddesk-layout-placement-empty><?php echo esc_html__( 'No placements available for this category.', 'threaddesk' ); ?></p>
					</div>
					<div class="threaddesk-layout-viewer__panel-step" data-threaddesk-layout-panel-step="designs" hidden>
						<h4 data-threaddesk-layout-design-heading><?php echo esc_html__( 'Choose Design', 'threaddesk' ); ?></h4>
						<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-layout-back-to-placements><?php echo esc_html__( '← Back to placements', 'threaddesk' ); ?></button>
						<div class="threaddesk-layout-viewer__design-list" data-threaddesk-layout-design-list></div>
						<p class="threaddesk-layout-viewer__placement-empty" data-threaddesk-layout-design-empty><?php echo esc_html__( 'No saved designs yet. Add designs from the Designs panel first.', 'threaddesk' ); ?></p>
						<button type="button" class="threaddesk-layout-viewer__add-design-button" data-threaddesk-design-open><?php echo esc_html__( 'Add New Design', 'threaddesk' ); ?></button>
					</div>
					<div class="threaddesk-layout-viewer__panel-step" data-threaddesk-layout-panel-step="adjust" hidden>
						<h4><?php echo esc_html__( 'Adjust Placement', 'threaddesk' ); ?></h4>
						<button type="button" class="threaddesk-layout-viewer__back-button" data-threaddesk-layout-back-to-designs><?php echo esc_html__( '← Change design', 'threaddesk' ); ?></button>
						<p class="threaddesk-layout-viewer__selection-name" data-threaddesk-layout-selected-design><?php echo esc_html__( 'No design selected', 'threaddesk' ); ?></p>
						<div class="threaddesk-layout-viewer__selection-placement" data-threaddesk-layout-selected-placement><?php echo esc_html__( 'Placement', 'threaddesk' ); ?></div>
						<label class="threaddesk-layout-viewer__size-label" for="threaddesk-layout-size-slider"><?php echo esc_html__( 'Size', 'threaddesk' ); ?></label>
						<input id="threaddesk-layout-size-slider" type="range" min="60" max="140" value="100" class="threaddesk-layout-viewer__size-slider" data-threaddesk-layout-size-slider />
						<p class="threaddesk-layout-viewer__size-reading" data-threaddesk-layout-size-reading><?php echo esc_html__( 'Approx. size: --', 'threaddesk' ); ?></p>
						<div class="threaddesk-layout-viewer__adjust-actions">
							<button type="button" class="threaddesk-layout-viewer__save-button" data-threaddesk-layout-save-placement><?php echo esc_html__( 'Save Placement', 'threaddesk' ); ?></button>
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
				<label class="threaddesk-designer__title-field" for="threaddesk_design_title"><?php echo esc_html__( 'Title', 'threaddesk' ); ?></label>
				<input type="text" id="threaddesk_design_title" name="threaddesk_design_title" data-threaddesk-design-title-input maxlength="120" value="" />
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

				<input type="file" id="threaddesk_design_file" accept=".png,.jpg,.jpeg,image/png,image/jpeg" name="threaddesk_design_file" data-threaddesk-design-file hidden />
				<small class="threaddesk-designer__file-name" data-threaddesk-design-file-name><?php echo esc_html__( 'No file selected', 'threaddesk' ); ?></small>
				<input type="hidden" name="threaddesk_design_palette" data-threaddesk-design-palette value="[]" />
				<input type="hidden" name="threaddesk_design_color_count" data-threaddesk-design-color-count value="0" />
				<input type="hidden" name="threaddesk_design_analysis_settings" data-threaddesk-design-settings value="{}" />
				<input type="hidden" name="threaddesk_design_svg_markup" data-threaddesk-design-svg-markup value="" />
					<input type="hidden" name="threaddesk_design_mockup_png_data" data-threaddesk-design-mockup-png value="" />

				<div class="threaddesk-designer__controls">
					<div class="threaddesk-designer__control-head">
						<label for="threaddesk_design_max_colors"><?php echo esc_html__( 'Maximum color count', 'threaddesk' ); ?></label>
						<div class="threaddesk-designer__color-counter">
							<input type="range" id="threaddesk_design_max_colors" min="1" max="8" value="8" data-threaddesk-max-colors />
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
					<button type="submit" class="threaddesk-auth-modal__button">
						<?php echo esc_html__( 'Save Design', 'threaddesk' ); ?>
					</button>
				</p>
			</form>
		</div>
	</div>
</div>
