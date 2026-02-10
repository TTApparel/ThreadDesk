<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = isset( $context ) ? $context : array();
$user    = isset( $context['user'] ) ? $context['user'] : null;
$cover   = ! empty( $context['cover_image'] ) ? $context['cover_image'] : 'https://via.placeholder.com/1200x240.png?text=ThreadDesk+Cover';
$company = ! empty( $context['company'] ) ? $context['company'] : __( 'Client Company', 'threaddesk' );
$currency = isset( $context['currency'] ) ? $context['currency'] : 'USD';

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
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'profile', $nav_base ) ); ?>"><?php echo esc_html__( 'Profile', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'designs', $nav_base ) ); ?>"><?php echo esc_html__( 'Designs', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'layouts', $nav_base ) ); ?>"><?php echo esc_html__( 'Layouts', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( add_query_arg( 'td_section', 'quotes', $nav_base ) ); ?>"><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item is-active" href="<?php echo esc_url( add_query_arg( 'td_section', 'invoices', $nav_base ) ); ?>"><?php echo esc_html__( 'Invoices', 'threaddesk' ); ?></a>
	</div>

	<div class="threaddesk__content">
		<div class="threaddesk__header" style="background-image: url('<?php echo esc_url( $cover ); ?>');">
			<div class="threaddesk__profile">
				<div class="threaddesk__avatar"></div>
				<div>
					<h2><?php echo esc_html( $user ? $user->display_name : __( 'Client Name', 'threaddesk' ) ); ?></h2>
					<p><?php echo esc_html( $company ); ?></p>
				</div>
			</div>
		</div>

		<div class="threaddesk__section">
			<h3><?php echo esc_html__( 'Invoices & Orders', 'threaddesk' ); ?></h3>
			<table class="threaddesk__table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Order', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Status', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Actions', 'threaddesk' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $context['orders'] ) ) : ?>
						<?php foreach ( $context['orders'] as $order ) : ?>
							<tr>
								<td>#<?php echo esc_html( $order->get_order_number() ); ?></td>
								<td><?php echo esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) ); ?></td>
								<td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
								<td><?php echo wp_kses_post( $format_price( $order->get_total() ) ); ?></td>
								<td class="threaddesk__actions">
									<a class="threaddesk__link" href="#"><?php echo esc_html__( 'Download Invoice', 'threaddesk' ); ?></a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="tta_threaddesk_reorder" />
										<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
										<?php wp_nonce_field( 'tta_threaddesk_reorder' ); ?>
										<button class="threaddesk__button" type="submit"><?php echo esc_html__( 'Reorder', 'threaddesk' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5"><?php echo esc_html__( 'No orders found yet.', 'threaddesk' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
