<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = isset( $context ) ? $context : array();
$user    = isset( $context['user'] ) ? $context['user'] : null;
$cover   = ! empty( $context['cover_image'] ) ? $context['cover_image'] : 'https://via.placeholder.com/1200x240.png?text=ThreadDesk+Cover';
$company = ! empty( $context['company'] ) ? $context['company'] : __( 'Client Company', 'threaddesk' );

$nav_base = trailingslashit( wc_get_account_endpoint_url( 'thread-desk' ) );
?>
<div class="threaddesk">
	<div class="threaddesk__sidebar">
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base ); ?>"><?php echo esc_html__( 'Profile', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item is-active" href="<?php echo esc_url( $nav_base . 'designs/' ); ?>"><?php echo esc_html__( 'Designs', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'layouts/' ); ?>"><?php echo esc_html__( 'Layouts', 'threaddesk' ); ?></a>
		<a class="threaddesk__nav-item" href="<?php echo esc_url( $nav_base . 'quotes/' ); ?>"><?php echo esc_html__( 'Quotes', 'threaddesk' ); ?></a>
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
			<h3><?php echo esc_html__( 'Saved Designs', 'threaddesk' ); ?></h3>
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
