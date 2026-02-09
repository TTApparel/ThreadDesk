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
$shipping_address = ! empty( $context['shipping_address'] ) ? $context['shipping_address'] : array();
$map_parts = array_filter(
	array(
		isset( $shipping_address['address_1'] ) ? $shipping_address['address_1'] : '',
		isset( $shipping_address['address_2'] ) ? $shipping_address['address_2'] : '',
		isset( $shipping_address['city'] ) ? $shipping_address['city'] : '',
		isset( $shipping_address['state'] ) ? $shipping_address['state'] : '',
		isset( $shipping_address['postcode'] ) ? $shipping_address['postcode'] : '',
		isset( $shipping_address['country'] ) ? $shipping_address['country'] : '',
	)
);
$map_query = trim( implode( ', ', $map_parts ) );
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
						<label class="threaddesk__avatar<?php echo $avatar_url ? ' has-image' : ''; ?>" for="threaddesk_avatar">
							<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr__( 'Company avatar', 'threaddesk' ); ?>" />
							<span class="threaddesk__avatar-overlay"><?php echo esc_html__( 'Upload', 'threaddesk' ); ?></span>
						</label>
						<input class="threaddesk__avatar-input" id="threaddesk_avatar" name="threaddesk_avatar" type="file" accept="image/*" onchange="this.form.submit();" />
						<div class="threaddesk__profile-text">
							<h2><?php echo esc_html( $profile_name ); ?></h2>
							<p><?php echo esc_html( $profile_username ); ?></p>
						</div>
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
						<a href="<?php echo esc_url( $context['account_links']['edit_billing'] ); ?>"><?php echo esc_html__( 'Edit', 'threaddesk' ); ?></a>
					</div>
					<p><?php echo wp_kses_post( nl2br( $context['billing_address'] ) ); ?></p>
				</div>
				<div class="threaddesk__card">
					<div class="threaddesk__card-header">
						<h4><?php echo esc_html__( 'Shipping Details', 'threaddesk' ); ?></h4>
						<a href="<?php echo esc_url( $context['account_links']['edit_shipping'] ); ?>"><?php echo esc_html__( 'Edit', 'threaddesk' ); ?></a>
					</div>
					<p><?php echo wp_kses_post( nl2br( $context['shipping_address'] ) ); ?></p>
				</div>
				<div class="threaddesk__card">
					<div class="threaddesk__card-header">
						<h4><?php echo esc_html__( 'Account Details', 'threaddesk' ); ?></h4>
						<a href="<?php echo esc_url( $context['account_links']['edit_account'] ); ?>"><?php echo esc_html__( 'Edit', 'threaddesk' ); ?></a>
					</div>
					<p><?php echo esc_html( sprintf( __( 'Username: %s', 'threaddesk' ), $context['account_details']['username'] ) ); ?></p>
					<p><?php echo esc_html( sprintf( __( 'Email: %s', 'threaddesk' ), $context['account_details']['email'] ) ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
