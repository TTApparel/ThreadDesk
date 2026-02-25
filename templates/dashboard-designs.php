<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = isset( $context ) ? $context : array();
$user    = isset( $context['user'] ) ? $context['user'] : null;
$cover   = ! empty( $context['cover_image'] ) ? $context['cover_image'] : '';
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
$design_status_titles = array(
	'pending'  => array(),
	'approved' => array(),
	'rejected' => array(),
);
$design_status_labels = array(
	'pending'  => __( 'Pending', 'threaddesk' ),
	'approved' => __( 'Approved', 'threaddesk' ),
	'rejected' => __( 'Rejected', 'threaddesk' ),
);
$design_rejection_reason_labels = array(
	'low_resolution' => __( 'The design file is of too low a resolution to proceed to printing.', 'threaddesk' ),
	'copyright_risk' => __( 'The design is copyrighted and is at risk of infringement.', 'threaddesk' ),
	'detail_concerns' => __( 'Gradients/Transparencies/Fine detail concerns', 'threaddesk' ),
	'other'          => __( 'Other (a representative will be in contact with you in the coming days)', 'threaddesk' ),
);


if ( '' === $cover ) {
	$cover_label = trim( (string) $profile_username );
	if ( '' === $cover_label ) {
		$cover_label = (string) __( 'Username', 'threaddesk' );
	}
	$cover_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="240" viewBox="0 0 1200 240" role="img" aria-label="ThreadDesk Cover"><defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#0f1720"/><stop offset="100%" stop-color="#1e2d3f"/></linearGradient></defs><rect width="1200" height="240" fill="url(#bg)"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#e6edf5" font-family="Arial, sans-serif" font-size="52" font-weight="700">' . esc_html( $cover_label ) . '</text></svg>';
	$cover = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode( $cover_svg );
}

$nav_base = trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) );
?>
<div class="threaddesk">
	<div class="threaddesk__sidebar">
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'profile', $nav_base ) ); ?>"><?php echo esc_html__( 'Profile', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item is-active" href="<?php echo esc_url( add_query_arg( 'td_section', 'designs', $nav_base ) ); ?>"><?php echo esc_html__( 'Designs', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'layouts', $nav_base ) ); ?>"><?php echo esc_html__( 'Placements', 'threaddesk' ); ?></a>
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

		<div class="threaddesk__content-inner">
			<div class="threaddesk__main">
				<div class="threaddesk__section">
					<div class="threaddesk__card-header threaddesk-designer__heading">
						<h3><?php echo esc_html__( 'Saved Designs', 'threaddesk' ); ?></h3>
						<button type="button" class="threaddesk__button" data-threaddesk-design-open><?php echo esc_html__( 'Add Design', 'threaddesk' ); ?></button>
					</div>
					<div class="threaddesk__cards">
						<?php if ( ! empty( $context['designs'] ) ) : ?>
					<?php foreach ( $context['designs'] as $design ) : ?>
					<?php $design_preview = get_post_meta( $design->ID, 'design_preview_url', true ); ?>
					<?php $design_file_name = get_post_meta( $design->ID, 'design_file_name', true ); ?>
					<?php $design_palette = get_post_meta( $design->ID, 'design_palette', true ); ?>
					<?php $design_settings = get_post_meta( $design->ID, 'design_analysis_settings', true ); ?>
					<?php $design_svg_url = get_post_meta( $design->ID, 'design_svg_file_url', true ); ?>
					<?php $design_svg_name = get_post_meta( $design->ID, 'design_svg_file_name', true ); ?>
					<?php $design_mockup_png = get_post_meta( $design->ID, 'design_mockup_file_url', true ); ?>
					<?php $design_status_mockup = $design_mockup_png ? $design_mockup_png : $design_preview; ?>
					<?php $design_status = sanitize_key( (string) get_post_meta( $design->ID, 'design_status', true ) ); ?>
					<?php $design_rejection_reason = sanitize_key( (string) get_post_meta( $design->ID, 'design_rejection_reason', true ) ); ?>
					<?php $design_rejection_reason_text = isset( $design_rejection_reason_labels[ $design_rejection_reason ] ) ? $design_rejection_reason_labels[ $design_rejection_reason ] : ''; ?>
					<?php if ( ! in_array( $design_status, array( 'pending', 'approved', 'rejected' ), true ) ) : ?>
						<?php $design_status = 'pending'; ?>
					<?php endif; ?>
					<?php $design_palette_values = json_decode( (string) $design_palette, true ); ?>
					<?php $design_palette_values = is_array( $design_palette_values ) ? $design_palette_values : array(); ?>
					<?php $design_palette_values = array_map( function ( $color ) { return strtoupper( trim( (string) $color ) ); }, $design_palette_values ); ?>
					<?php $design_palette_values = array_values( array_filter( $design_palette_values, function ( $color ) { return '' !== $color && 'TRANSPARENT' !== $color; } ) ); ?>
					<?php $design_color_count = count( array_unique( $design_palette_values ) ); ?>
					<?php $design_title = trim( (string) $design->post_title ); ?>
					<?php if ( '' === $design_title && ! empty( $design_file_name ) ) : ?>
						<?php $design_title = trim( (string) preg_replace( '/\.[^.]+$/', '', (string) $design_file_name ) ); ?>
					<?php endif; ?>
					<?php if ( '' === $design_title ) : ?>
						<?php $design_title = __( 'Design', 'threaddesk' ); ?>
					<?php endif; ?>
					<?php $design_status_titles[ $design_status ][] = array(
						'title'  => $design_title,
						'mockup' => $design_status_mockup,
						'reason' => $design_rejection_reason_text,
					); ?>
					<div class="threaddesk__card threaddesk__card--design">
						<form class="threaddesk__card-delete" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tta_threaddesk_delete_design" />
							<input type="hidden" name="design_id" value="<?php echo esc_attr( $design->ID ); ?>" />
							<?php wp_nonce_field( 'tta_threaddesk_delete_design' ); ?>
							<button type="submit" class="threaddesk__card-delete-button" aria-label="<?php echo esc_attr__( 'Delete design', 'threaddesk' ); ?>">&times;</button>
						</form>
						<?php if ( $design_preview ) : ?>
							<div class="threaddesk__card-design-preview threaddesk__card-design-preview--checker">
								<img class="threaddesk__card-design-preview-svg" src="<?php echo esc_url( $design_preview ); ?>" alt="<?php echo esc_attr( $design_title ); ?>" />
								<img class="threaddesk__card-design-preview-original" src="<?php echo esc_url( $design_preview ); ?>" alt="" aria-hidden="true" />
							</div>
						<?php endif; ?>
						<form class="threaddesk__card-title-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tta_threaddesk_rename_design" />
							<input type="hidden" name="design_id" value="<?php echo esc_attr( $design->ID ); ?>" />
							<?php wp_nonce_field( 'tta_threaddesk_rename_design' ); ?>
							<h5 class="threaddesk__card-title"><input class="threaddesk__card-title-input" type="text" name="design_title" value="<?php echo esc_attr( $design_title ); ?>" maxlength="120" data-threaddesk-design-title-card-input aria-label="<?php echo esc_attr__( 'Design title', 'threaddesk' ); ?>" /></h5>
							<p class="threaddesk__card-design-color-count"><span><?php echo esc_html( sprintf( __( 'Color count: %d', 'threaddesk' ), $design_color_count ) ); ?></span><span class="threaddesk__card-design-status threaddesk__card-design-status--<?php echo esc_attr( $design_status ); ?>"><?php echo esc_html( isset( $design_status_labels[ $design_status ] ) ? $design_status_labels[ $design_status ] : $design_status_labels['pending'] ); ?></span></p>
						</form>
						<div class="threaddesk__card-design-actions">
							<button
								type="button"
								class="threaddesk__button threaddesk__button--small"
								data-threaddesk-design-edit
								data-threaddesk-design-id="<?php echo esc_attr( $design->ID ); ?>"
								data-threaddesk-design-title="<?php echo esc_attr( $design_title ); ?>"
								data-threaddesk-design-preview-url="<?php echo esc_url( $design_preview ); ?>"
								data-threaddesk-design-file-name="<?php echo esc_attr( $design_file_name ); ?>"
								data-threaddesk-design-palette="<?php echo esc_attr( $design_palette ? $design_palette : '[]' ); ?>"
								data-threaddesk-design-settings="<?php echo esc_attr( $design_settings ? $design_settings : '{}' ); ?>">
								<?php echo esc_html__( 'Adjust Design', 'threaddesk' ); ?>
							</button>
						</div>
					</div>
					<?php endforeach; ?>
						<?php else : ?>
							<div class="threaddesk__card">
								<p><?php echo esc_html__( 'No designs found yet.', 'threaddesk' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="threaddesk__aside">
				<?php foreach ( $design_status_labels as $status_key => $status_label ) : ?>
					<div class="threaddesk__card">
						<div class="threaddesk__card-header">
							<h4><?php echo esc_html( $status_label ); ?></h4>
						</div>
						<?php if ( ! empty( $design_status_titles[ $status_key ] ) ) : ?>
							<ul class="threaddesk__status-list">
								<?php foreach ( $design_status_titles[ $status_key ] as $status_item ) : ?>
									<?php
									$status_title = isset( $status_item['title'] ) ? (string) $status_item['title'] : '';
									$status_mockup = isset( $status_item['mockup'] ) ? (string) $status_item['mockup'] : '';
									$status_reason = isset( $status_item['reason'] ) ? (string) $status_item['reason'] : '';
									$show_hover_mockup = in_array( $status_key, array( 'pending', 'approved' ), true ) && '' !== $status_mockup;
									$show_hover_reason = 'rejected' === $status_key && '' !== $status_reason;
									?>
									<li class="threaddesk__status-list-item<?php echo $show_hover_mockup ? ' has-mockup' : ''; ?><?php echo $show_hover_reason ? ' has-reason' : ''; ?>">
										<span class="threaddesk__status-list-title"><?php echo esc_html( $status_title ); ?></span>
										<?php if ( $show_hover_mockup ) : ?>
											<span class="threaddesk__status-list-mockup-tag" role="tooltip" aria-hidden="true">
												<img src="<?php echo esc_url( $status_mockup ); ?>" alt="" />
											</span>
										<?php endif; ?>
										<?php if ( $show_hover_reason ) : ?>
											<span class="threaddesk__status-list-reason-tag" role="tooltip" aria-hidden="true"><?php echo esc_html( $status_reason ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="threaddesk__status-empty"><?php echo esc_html__( 'No designs', 'threaddesk' ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
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
