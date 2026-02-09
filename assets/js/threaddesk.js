jQuery(function ($) {
	$('.threaddesk__nav-item').on('click', function () {
		$('.threaddesk__nav-item').removeClass('is-active');
		$(this).addClass('is-active');
	});

	const modal = $('.threaddesk-auth-modal');

	if (modal.length) {
		const openModal = function (target) {
			modal.addClass('is-active').attr('aria-hidden', 'false');
			switchAuthPanel(target);
		};

		const closeModal = function () {
			modal.removeClass('is-active').attr('aria-hidden', 'true');
		};

		const switchAuthPanel = function (target) {
			const panel = target || 'login';
			const panels = modal.find('[data-threaddesk-auth-panel]');
			const tabs = modal.find('[data-threaddesk-auth-tab]');

			panels.removeClass('is-active').attr('aria-hidden', 'true');
			panels.filter('[data-threaddesk-auth-panel="' + panel + '"]').addClass('is-active').attr('aria-hidden', 'false');

			tabs.removeClass('is-active').attr('aria-selected', 'false');
			tabs.filter('[data-threaddesk-auth-tab="' + panel + '"]').addClass('is-active').attr('aria-selected', 'true');
		};

		$(document).on('click', '[data-threaddesk-auth]', function (event) {
			event.preventDefault();
			const target = $(this).data('threaddesk-auth');
			openModal(target);
		});

		$(document).on('click', '[data-threaddesk-auth-tab]', function () {
			switchAuthPanel($(this).data('threaddesk-auth-tab'));
		});

		$(document).on('click', '[data-threaddesk-auth-close]', function () {
			closeModal();
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape') {
				closeModal();
			}
		});

		const defaultPanel = modal.data('threaddesk-auth-default');
		if (defaultPanel) {
			openModal(defaultPanel);
		}
	}
});
