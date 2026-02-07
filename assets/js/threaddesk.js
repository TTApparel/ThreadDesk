jQuery(function ($) {
	$('.threaddesk__nav-item').on('click', function () {
		$('.threaddesk__nav-item').removeClass('is-active');
		$(this).addClass('is-active');
	});
});
