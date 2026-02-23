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

$placement_category_names = array( 'Tshirts', 'Long Sleeves', 'Hoodies', 'Tank Tops', 'Jackets', 'Hats', 'Bags' );
$placement_categories     = array();

if ( taxonomy_exists( 'product_cat' ) ) {
	$category_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);

	if ( ! is_wp_error( $category_terms ) ) {
		$indexed_categories = array();

		foreach ( $category_terms as $term ) {
			$indexed_categories[ sanitize_title( $term->name ) ] = $term;
			$indexed_categories[ sanitize_title( $term->slug ) ] = $term;
		}

		foreach ( $placement_category_names as $category_name ) {
			$key  = sanitize_title( $category_name );
			$term = isset( $indexed_categories[ $key ] ) ? $indexed_categories[ $key ] : null;

			$image_url = '';
			if ( $term ) {
				$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				if ( $thumbnail_id ) {
					$image_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
				}
			}

			$placement_categories[] = array(
				'label'     => $category_name,
				'image_url' => $image_url,
				'term_id'   => $term ? (int) $term->term_id : 0,
				'term_slug' => $term ? $term->slug : '',
			);
		}
	}
}

if ( empty( $placement_categories ) ) {
	foreach ( $placement_category_names as $category_name ) {
		$placement_categories[] = array(
			'label'     => $category_name,
			'image_url' => '',
			'term_id'   => 0,
			'term_slug' => '',
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

<div class="threaddesk-layout-modal" aria-hidden="true">
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
			<div class="threaddesk-layout-modal__content">
				<h3><?php echo esc_html__( 'Create a placement layout', 'threaddesk' ); ?></h3>
				<p><?php echo esc_html__( 'Choose a product category to start your layout.', 'threaddesk' ); ?></p>
				<div class="threaddesk-layout-modal__grid">
					<?php foreach ( $placement_categories as $placement_category ) : ?>
						<button type="button" class="threaddesk-layout-modal__option" data-threaddesk-layout-category="<?php echo esc_attr( $placement_category['term_slug'] ); ?>" data-threaddesk-layout-category-id="<?php echo esc_attr( $placement_category['term_id'] ); ?>">
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
				</div>
			</div>
		</div>
	</div>
</div>
