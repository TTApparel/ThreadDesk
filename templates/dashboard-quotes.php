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
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base ); ?>"><?php echo esc_html__( 'Profile', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'designs/' ); ?>"><?php echo esc_html__( 'Designs', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'layouts/' ); ?>"><?php echo esc_html__( 'Layouts', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item is-active" href="<?php echo esc_url( $nav_base . 'quotes/' ); ?>"><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'invoices/' ); ?>"><?php echo esc_html__( 'Invoices', 'threaddesk' ); ?></a>
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
			<h3><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></h3>
			<table class="threaddesk__table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Quote', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Status', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'threaddesk' ); ?></th>
						<th><?php echo esc_html__( 'Actions', 'threaddesk' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $context['quotes'] ) ) : ?>
						<?php foreach ( $context['quotes'] as $quote ) : ?>
							<?php
								$status = get_post_meta( $quote->ID, 'status', true );
								$total  = get_post_meta( $quote->ID, 'total', true );
							?>
							<tr>
								<td><?php echo esc_html( $quote->post_title ); ?></td>
								<td><?php echo esc_html( ucfirst( $status ? $status : __( 'draft', 'threaddesk' ) ) ); ?></td>
								<td><?php echo wp_kses_post( $format_price( $total ? $total : 0 ) ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="tta_threaddesk_request_order" />
										<input type="hidden" name="quote_id" value="<?php echo esc_attr( $quote->ID ); ?>" />
										<?php wp_nonce_field( 'tta_threaddesk_request_order' ); ?>
										<button class="threaddesk__button" type="submit"><?php echo esc_html__( 'Request Order', 'threaddesk' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__( 'No quotes found yet.', 'threaddesk' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
