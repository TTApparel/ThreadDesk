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
$stats   = isset( $context['order_stats'] ) ? $context['order_stats'] : array();
$currency = isset( $context['currency'] ) ? $context['currency'] : 'USD';
$shipping_address = ! empty( $context['shipping_address'] ) && is_array( $context['shipping_address'] ) ? $context['shipping_address'] : array();
$billing_address  = ! empty( $context['billing_address'] ) && is_array( $context['billing_address'] ) ? $context['billing_address'] : array();
$address_source   = $shipping_address ? $shipping_address : $billing_address;
$billing_display  = ! empty( $billing_address['formatted'] ) ? $billing_address['formatted'] : __( 'Not provided yet.', 'threaddesk' );
$shipping_display = ! empty( $shipping_address['formatted'] ) ? $shipping_address['formatted'] : __( 'Not provided yet.', 'threaddesk' );
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
$billing_form = array_merge(
	array(
		'first_name' => '',
		'last_name'  => '',
		'company'    => '',
		'address_1'  => '',
		'address_2'  => '',
		'city'       => '',
		'state'      => '',
		'postcode'   => '',
		'country'    => '',
		'phone'      => '',
		'email'      => '',
	),
	$billing_address
);
$shipping_form = array_merge(
	array(
		'first_name' => '',
		'last_name'  => '',
		'company'    => '',
		'address_1'  => '',
		'address_2'  => '',
		'city'       => '',
		'state'      => '',
		'postcode'   => '',
		'country'    => '',
	),
	$shipping_address
);

$nav_base = trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) );

$format_price = function ( $amount ) use ( $currency ) {
	if ( function_exists( 'wc_price' ) ) {
		return wc_price( $amount, array( 'currency' => $currency ) );
	}

	return esc_html( number_format_i18n( (float) $amount, 2 ) . ' ' . $currency );
};
?>
<div class="threaddesk">
	<div class="threaddesk__sidebar">
		<a class="threaddesk__nav-item is-active" href="<?php echo esc_url( $nav_base ); ?>"><?php echo esc_html__( 'Profile', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'designs/' ); ?>"><?php echo esc_html__( 'Designs', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'layouts/' ); ?>"><?php echo esc_html__( 'Layouts', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'quotes/' ); ?>"><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'invoices/' ); ?>"><?php echo esc_html__( 'Invoices', 'threaddesk' ); ?></a>
	</div>

	<div class="threaddesk__content">
		<div class="threaddesk__content-inner">
			<div class="threaddesk__main">
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

				<div class="threaddesk__stats">
					<div class="threaddesk__stat">
						<span><?php echo esc_html__( 'Last Order', 'threaddesk' ); ?></span>
						<strong><?php echo esc_html( ! empty( $stats['last_order'] ) ? $stats['last_order'] : __( 'No orders yet', 'threaddesk' ) ); ?></strong>
					</div>
					<div class="threaddesk__stat">
						<span><?php echo esc_html__( 'Average Order', 'threaddesk' ); ?></span>
						<strong><?php echo wp_kses_post( $format_price( isset( $stats['avg_order'] ) ? $stats['avg_order'] : 0 ) ); ?></strong>
						<small><?php echo esc_html( sprintf( __( '%d orders', 'threaddesk' ), isset( $stats['order_count'] ) ? $stats['order_count'] : 0 ) ); ?></small>
					</div>
					<div class="threaddesk__stat">
						<span><?php echo esc_html__( 'Lifetime Spend', 'threaddesk' ); ?></span>
						<strong><?php echo wp_kses_post( $format_price( isset( $stats['lifetime'] ) ? $stats['lifetime'] : 0 ) ); ?></strong>
					</div>
					<div class="threaddesk__stat">
						<span><?php echo esc_html__( 'Outstanding Balance', 'threaddesk' ); ?></span>
						<strong><?php echo wp_kses_post( $format_price( isset( $context['outstanding_total'] ) ? $context['outstanding_total'] : 0 ) ); ?></strong>
						<small class="threaddesk__stat-note"><?php echo esc_html__( 'All Paid Up', 'threaddesk' ); ?></small>
					</div>
				</div>

				<div class="threaddesk__secondary-stats">
					<div>
						<span><?php echo esc_html__( 'Number of Designs', 'threaddesk' ); ?></span>
						<strong><?php echo esc_html( (int) $context['design_count'] ); ?></strong>
					</div>
					<div>
						<span><?php echo esc_html__( 'Saved Layouts', 'threaddesk' ); ?></span>
						<strong><?php echo esc_html( (int) $context['layout_count'] ); ?></strong>
					</div>
					<div>
						<span><?php echo esc_html__( 'Artwork Approvals', 'threaddesk' ); ?></span>
						<strong><?php echo esc_html__( '0', 'threaddesk' ); ?></strong>
						<small><?php echo esc_html__( '0 unapproved', 'threaddesk' ); ?></small>
					</div>
					<div>
						<span><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></span>
						<strong><?php echo esc_html( (int) $context['quotes_count'] ); ?></strong>
						<small><?php echo esc_html__( '2 unapproved', 'threaddesk' ); ?></small>
					</div>
				</div>

				<h3><?php echo esc_html__( 'Recent Activity', 'threaddesk' ); ?></h3>
				<table class="threaddesk__table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Activity', 'threaddesk' ); ?></th>
							<th><?php echo esc_html__( 'Date', 'threaddesk' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $context['recent_activity'] as $activity ) : ?>
							<tr>
								<td><?php echo esc_html( $activity['label'] ); ?></td>
								<td><?php echo esc_html( $activity['date'] ); ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if ( empty( $context['recent_activity'] ) ) : ?>
							<tr>
								<td colspan="2"><?php echo esc_html__( 'No recent activity.', 'threaddesk' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="threaddesk__aside">
					<div class="threaddesk__card">
						<div class="threaddesk__card-header">
							<h4><?php echo esc_html__( 'Billing Details', 'threaddesk' ); ?></h4>
							<button type="button" class="threaddesk__link-button" data-threaddesk-address="billing"><?php echo esc_html__( 'Edit', 'threaddesk' ); ?></button>
						</div>
						<p><?php echo wp_kses_post( nl2br( $billing_display ) ); ?></p>
					</div>
					<div class="threaddesk__card">
						<div class="threaddesk__card-header">
							<h4><?php echo esc_html__( 'Shipping Details', 'threaddesk' ); ?></h4>
							<button type="button" class="threaddesk__link-button" data-threaddesk-address="shipping"><?php echo esc_html__( 'Edit', 'threaddesk' ); ?></button>
						</div>
						<p><?php echo wp_kses_post( nl2br( $shipping_display ) ); ?></p>
					</div>
					<div class="threaddesk__card">
						<div class="threaddesk__card-header">
							<h4><?php echo esc_html__( 'Account Details', 'threaddesk' ); ?></h4>
							<button type="button" class="threaddesk__link-button" data-threaddesk-address="account"><?php echo esc_html__( 'Edit', 'threaddesk' ); ?></button>
						</div>
						<p><?php echo esc_html( sprintf( __( 'Username: %s', 'threaddesk' ), $context['account_details']['username'] ) ); ?></p>
						<p><?php echo esc_html( sprintf( __( 'Email: %s', 'threaddesk' ), $context['account_details']['email'] ) ); ?></p>
					</div>
			</div>
		</div>
	</div>
	<div class="threaddesk-address-modal" aria-hidden="true">
		<div class="threaddesk-auth-modal__overlay" data-threaddesk-address-close></div>
		<div class="threaddesk-auth-modal__panel" role="dialog" aria-label="<?php echo esc_attr__( 'Update address', 'threaddesk' ); ?>" aria-modal="true">
			<div class="threaddesk-auth-modal__actions">
				<button type="button" class="threaddesk-auth-modal__close" data-threaddesk-address-close aria-label="<?php echo esc_attr__( 'Close address modal', 'threaddesk' ); ?>">
					<svg class="threaddesk-auth-modal__close-icon" width="12" height="12" viewBox="0 0 15 15" aria-hidden="true" focusable="false">
						<path d="M1 15a1 1 0 01-.71-.29 1 1 0 010-1.41l5.8-5.8-5.8-5.8A1 1 0 011.7.29l5.8 5.8 5.8-5.8a1 1 0 011.41 1.41l-5.8 5.8 5.8 5.8a1 1 0 01-1.41 1.41l-5.8-5.8-5.8 5.8A1 1 0 011 15z"></path>
					</svg>
				</button>
			</div>
			<div class="threaddesk-auth-modal__content">
				<div class="threaddesk-auth-modal__tabs" role="tablist">
					<button type="button" class="threaddesk-auth-modal__tab is-active" role="tab" aria-selected="true" data-threaddesk-address-tab="billing">
						<?php echo esc_html__( 'Billing', 'threaddesk' ); ?>
					</button>
					<button type="button" class="threaddesk-auth-modal__tab" role="tab" aria-selected="false" data-threaddesk-address-tab="shipping">
						<?php echo esc_html__( 'Shipping', 'threaddesk' ); ?>
					</button>
					<button type="button" class="threaddesk-auth-modal__tab" role="tab" aria-selected="false" data-threaddesk-address-tab="account">
						<?php echo esc_html__( 'Account', 'threaddesk' ); ?>
					</button>
				</div>
				<div class="threaddesk-auth-modal__forms">
					<div class="threaddesk-auth-modal__form is-active" data-threaddesk-address-panel="billing" aria-hidden="false">
						<form class="threaddesk-auth-modal__form-inner" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tta_threaddesk_update_address" />
							<input type="hidden" name="address_type" value="billing" />
							<?php wp_nonce_field( 'tta_threaddesk_update_address' ); ?>
							<p>
								<label for="threaddesk_billing_address_1"><?php echo esc_html__( 'Address Line 1', 'threaddesk' ); ?></label>
								<input type="text" name="billing_address_1" id="threaddesk_billing_address_1" value="<?php echo esc_attr( $billing_form['address_1'] ); ?>" />
							</p>
							<p>
								<label for="threaddesk_billing_address_2"><?php echo esc_html__( 'Address Line 2', 'threaddesk' ); ?></label>
								<input type="text" name="billing_address_2" id="threaddesk_billing_address_2" value="<?php echo esc_attr( $billing_form['address_2'] ); ?>" />
							</p>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_billing_city"><?php echo esc_html__( 'City', 'threaddesk' ); ?></label>
									<input type="text" name="billing_city" id="threaddesk_billing_city" value="<?php echo esc_attr( $billing_form['city'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_billing_state"><?php echo esc_html__( 'State/Province', 'threaddesk' ); ?></label>
									<input type="text" name="billing_state" id="threaddesk_billing_state" value="<?php echo esc_attr( $billing_form['state'] ); ?>" />
								</p>
							</div>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_billing_postcode"><?php echo esc_html__( 'Postal Code', 'threaddesk' ); ?></label>
									<input type="text" name="billing_postcode" id="threaddesk_billing_postcode" value="<?php echo esc_attr( $billing_form['postcode'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_billing_country"><?php echo esc_html__( 'Country', 'threaddesk' ); ?></label>
									<input type="text" name="billing_country" id="threaddesk_billing_country" value="<?php echo esc_attr( $billing_form['country'] ); ?>" />
								</p>
							</div>
							<p class="threaddesk-auth-modal__submit">
								<button type="submit" class="threaddesk-auth-modal__button">
									<?php echo esc_html__( 'Save Billing', 'threaddesk' ); ?>
								</button>
							</p>
						</form>
					</div>
					<div class="threaddesk-auth-modal__form" data-threaddesk-address-panel="shipping" aria-hidden="true">
						<form class="threaddesk-auth-modal__form-inner" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tta_threaddesk_update_address" />
							<input type="hidden" name="address_type" value="shipping" />
							<?php wp_nonce_field( 'tta_threaddesk_update_address' ); ?>
							<p>
								<label for="threaddesk_shipping_address_1"><?php echo esc_html__( 'Address Line 1', 'threaddesk' ); ?></label>
								<input type="text" name="shipping_address_1" id="threaddesk_shipping_address_1" value="<?php echo esc_attr( $shipping_form['address_1'] ); ?>" />
							</p>
							<p>
								<label for="threaddesk_shipping_address_2"><?php echo esc_html__( 'Address Line 2', 'threaddesk' ); ?></label>
								<input type="text" name="shipping_address_2" id="threaddesk_shipping_address_2" value="<?php echo esc_attr( $shipping_form['address_2'] ); ?>" />
							</p>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_shipping_city"><?php echo esc_html__( 'City', 'threaddesk' ); ?></label>
									<input type="text" name="shipping_city" id="threaddesk_shipping_city" value="<?php echo esc_attr( $shipping_form['city'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_shipping_state"><?php echo esc_html__( 'State/Province', 'threaddesk' ); ?></label>
									<input type="text" name="shipping_state" id="threaddesk_shipping_state" value="<?php echo esc_attr( $shipping_form['state'] ); ?>" />
								</p>
							</div>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_shipping_postcode"><?php echo esc_html__( 'Postal Code', 'threaddesk' ); ?></label>
									<input type="text" name="shipping_postcode" id="threaddesk_shipping_postcode" value="<?php echo esc_attr( $shipping_form['postcode'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_shipping_country"><?php echo esc_html__( 'Country', 'threaddesk' ); ?></label>
									<input type="text" name="shipping_country" id="threaddesk_shipping_country" value="<?php echo esc_attr( $shipping_form['country'] ); ?>" />
								</p>
							</div>
							<p class="threaddesk-auth-modal__submit">
								<button type="submit" class="threaddesk-auth-modal__button">
									<?php echo esc_html__( 'Save Shipping', 'threaddesk' ); ?>
								</button>
							</p>
						</form>
					</div>
					<div class="threaddesk-auth-modal__form" data-threaddesk-address-panel="account" aria-hidden="true">
						<form class="threaddesk-auth-modal__form-inner" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tta_threaddesk_update_address" />
							<input type="hidden" name="address_type" value="account" />
							<?php wp_nonce_field( 'tta_threaddesk_update_address' ); ?>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_account_billing_first_name"><?php echo esc_html__( 'Billing First Name', 'threaddesk' ); ?></label>
									<input type="text" name="billing_first_name" id="threaddesk_account_billing_first_name" value="<?php echo esc_attr( $billing_form['first_name'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_account_billing_last_name"><?php echo esc_html__( 'Billing Last Name', 'threaddesk' ); ?></label>
									<input type="text" name="billing_last_name" id="threaddesk_account_billing_last_name" value="<?php echo esc_attr( $billing_form['last_name'] ); ?>" />
								</p>
							</div>
							<p>
								<label for="threaddesk_account_billing_company"><?php echo esc_html__( 'Billing Company', 'threaddesk' ); ?></label>
								<input type="text" name="billing_company" id="threaddesk_account_billing_company" value="<?php echo esc_attr( $billing_form['company'] ); ?>" />
							</p>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_account_billing_phone"><?php echo esc_html__( 'Billing Phone', 'threaddesk' ); ?></label>
									<input type="text" name="billing_phone" id="threaddesk_account_billing_phone" value="<?php echo esc_attr( $billing_form['phone'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_account_billing_email"><?php echo esc_html__( 'Billing Email', 'threaddesk' ); ?></label>
									<input type="email" name="billing_email" id="threaddesk_account_billing_email" value="<?php echo esc_attr( $billing_form['email'] ); ?>" />
								</p>
							</div>
							<div class="threaddesk-auth-modal__form-row">
								<p>
									<label for="threaddesk_account_shipping_first_name"><?php echo esc_html__( 'Shipping First Name', 'threaddesk' ); ?></label>
									<input type="text" name="shipping_first_name" id="threaddesk_account_shipping_first_name" value="<?php echo esc_attr( $shipping_form['first_name'] ); ?>" />
								</p>
								<p>
									<label for="threaddesk_account_shipping_last_name"><?php echo esc_html__( 'Shipping Last Name', 'threaddesk' ); ?></label>
									<input type="text" name="shipping_last_name" id="threaddesk_account_shipping_last_name" value="<?php echo esc_attr( $shipping_form['last_name'] ); ?>" />
								</p>
							</div>
							<p>
								<label for="threaddesk_account_shipping_company"><?php echo esc_html__( 'Shipping Company', 'threaddesk' ); ?></label>
								<input type="text" name="shipping_company" id="threaddesk_account_shipping_company" value="<?php echo esc_attr( $shipping_form['company'] ); ?>" />
							</p>
							<p class="threaddesk-auth-modal__submit">
								<button type="submit" class="threaddesk-auth-modal__button">
									<?php echo esc_html__( 'Save Account', 'threaddesk' ); ?>
								</button>
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
