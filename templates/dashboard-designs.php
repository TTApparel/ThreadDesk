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

		<div class="threaddesk__section">
			<div class="threaddesk__card-header threaddesk-designer__heading">
				<h3><?php echo esc_html__( 'Saved Designs', 'threaddesk' ); ?></h3>
				<button type="button" class="threaddesk__button" data-threaddesk-design-open><?php echo esc_html__( 'Choose Design', 'threaddesk' ); ?></button>
			</div>
			<p><?php echo esc_html__( 'Designs are placeholders for now. This area will list saved assets and approvals.', 'threaddesk' ); ?></p>
			<div class="threaddesk__cards">
				<?php if ( ! empty( $context['designs'] ) ) : ?>
					<?php foreach ( $context['designs'] as $design ) : ?>
						<div class="threaddesk__card">
							<h4><?php echo esc_html( $design->post_title ); ?></h4>
							<p><?php echo esc_html__( 'Status: In review', 'threaddesk' ); ?></p>
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
			<form class="threaddesk-auth-modal__form-inner" method="post" action="#">
				<div class="threaddesk-designer__design-image" data-threaddesk-design-preview>
					<img class="threaddesk-designer__design-image-upload" data-threaddesk-design-upload-preview alt="<?php echo esc_attr__( 'Uploaded design preview', 'threaddesk' ); ?>" />
					<div class="threaddesk-designer__design-image-overlay" aria-hidden="true"></div>
					<svg viewBox="0 0 320 210" role="img" aria-label="<?php echo esc_attr__( 'Design preview', 'threaddesk' ); ?>">
						<rect x="0" y="0" width="320" height="210" rx="14" fill="#f4f4f4"></rect>
						<path d="M58 168L99 56h35l41 112h-27l-8-24H93l-8 24H58z" fill="#111111" data-threaddesk-preview-layer="0"></path>
						<path d="M110 124h28l-14-42-14 42z" fill="#ffffff" data-threaddesk-preview-layer="1"></path>
						<circle cx="217" cy="98" r="44" fill="#1f1f1f" data-threaddesk-preview-layer="2"></circle>
						<rect x="187" y="142" width="60" height="18" rx="9" fill="#3a3a3a" data-threaddesk-preview-layer="3"></rect>
					</svg>
				</div>

				<input type="file" id="threaddesk_design_file" accept=".png,.jpg,.jpeg,.pdf,.svg,.ai" data-threaddesk-design-file hidden />
				<small class="threaddesk-designer__file-name" data-threaddesk-design-file-name><?php echo esc_html__( 'No file selected', 'threaddesk' ); ?></small>

				<div class="threaddesk-designer__controls">
					<div class="threaddesk-designer__control-head">
						<span><?php echo esc_html__( 'Color Count', 'threaddesk' ); ?></span>
						<div class="threaddesk-designer__color-counter" data-threaddesk-color-counter>
							<button type="button" class="threaddesk-designer__counter-btn" data-threaddesk-color-decrease>-</button>
							<strong data-threaddesk-color-count>1</strong>
							<button type="button" class="threaddesk-designer__counter-btn" data-threaddesk-color-increase>+</button>
						</div>
					</div>
					<div class="threaddesk-designer__swatches" data-threaddesk-color-swatches>
						<label>
							<span><?php echo esc_html__( 'Color 1', 'threaddesk' ); ?></span>
							<input type="color" value="#000000" />
						</label>
					</div>
				</div>

				<p class="threaddesk-auth-modal__submit">
					<button type="button" class="threaddesk-auth-modal__button" data-threaddesk-design-close>
						<?php echo esc_html__( 'Apply Settings', 'threaddesk' ); ?>
					</button>
				</p>
			</form>
		</div>
	</div>
</div>
