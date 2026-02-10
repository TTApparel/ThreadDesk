jQuery(function ($) {
	$('.threaddesk__nav-item').on('click', function () {
		$('.threaddesk__nav-item').removeClass('is-active');
		$(this).addClass('is-active');
	});

	const modal = $('.threaddesk-auth-modal');

	if (modal.length) {
		const openModal = function (target) {
			modal.addClass('is-active').attr('aria-hidden', 'false');
			$('body').addClass('threaddesk-modal-open');
			switchAuthPanel(target);
		};

		const closeModal = function () {
			modal.removeClass('is-active').attr('aria-hidden', 'true');
			$('body').removeClass('threaddesk-modal-open');
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

	const addressModal = $('.threaddesk-address-modal');

	if (addressModal.length) {
		const openAddressModal = function (target) {
			addressModal.addClass('is-active').attr('aria-hidden', 'false');
			$('body').addClass('threaddesk-modal-open');
			switchAddressPanel(target);
		};

		const closeAddressModal = function () {
			addressModal.removeClass('is-active').attr('aria-hidden', 'true');
			$('body').removeClass('threaddesk-modal-open');
		};

		const switchAddressPanel = function (target) {
			const panel = target || 'billing';
			const panels = addressModal.find('[data-threaddesk-address-panel]');
			const tabs = addressModal.find('[data-threaddesk-address-tab]');

			panels.removeClass('is-active').attr('aria-hidden', 'true');
			panels.filter('[data-threaddesk-address-panel="' + panel + '"]').addClass('is-active').attr('aria-hidden', 'false');

			tabs.removeClass('is-active').attr('aria-selected', 'false');
			tabs.filter('[data-threaddesk-address-tab="' + panel + '"]').addClass('is-active').attr('aria-selected', 'true');
		};

		$(document).on('click', '[data-threaddesk-address]', function (event) {
			event.preventDefault();
			const target = $(this).data('threaddesk-address');
			openAddressModal(target);
		});

		$(document).on('click', '[data-threaddesk-address-tab]', function () {
			switchAddressPanel($(this).data('threaddesk-address-tab'));
		});

		$(document).on('click', '[data-threaddesk-address-close]', function () {
			closeAddressModal();
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape') {
				closeAddressModal();
			}
		});
	}

	const designModal = $('.threaddesk-design-modal');

	if (designModal.length) {
		const openDesignModal = function () {
			designModal.addClass('is-active').attr('aria-hidden', 'false');
			$('body').addClass('threaddesk-modal-open');
		};

		const closeDesignModal = function () {
			designModal.removeClass('is-active').attr('aria-hidden', 'true');
			$('body').removeClass('threaddesk-modal-open');
		};

		const defaultPalette = ['#111111', '#ffffff', '#1f1f1f', '#3a3a3a', '#f24c3d', '#3366ff', '#21b573', '#f6b200'];

		const applyDesignPreviewColors = function () {
			const colors = [];
			designModal.find('[data-threaddesk-color-swatches] input[type="color"]').each(function () {
				colors.push($(this).val());
			});

			designModal.find('[data-threaddesk-preview-layer]').each(function (index) {
				const color = colors[index] || colors[0] || defaultPalette[0];
				$(this).attr('fill', color);
			});
		};

		const renderColorSwatches = function (count) {
			const swatches = designModal.find('[data-threaddesk-color-swatches]');
			const total = Math.max(1, Math.min(12, count));
			const existing = [];
			swatches.find('input[type="color"]').each(function () {
				existing.push($(this).val());
			});
			swatches.empty();

			for (let i = 1; i <= total; i += 1) {
				const row = $('<label></label>');
				const value = existing[i - 1] || defaultPalette[i - 1] || '#000000';
				row.append($('<span></span>').text('Color ' + i));
				row.append($('<input type="color" />').val(value));
				swatches.append(row);
			}

			designModal.find('[data-threaddesk-color-count]').text(total);
			applyDesignPreviewColors();
		};

		$(document).on('click', '[data-threaddesk-design-open]', function (event) {
			event.preventDefault();
			openDesignModal();
		});

		$(document).on('click', '[data-threaddesk-design-close]', function () {
			closeDesignModal();
		});

		$(document).on('click', '[data-threaddesk-color-increase]', function () {
			const count = parseInt(designModal.find('[data-threaddesk-color-count]').text(), 10) || 1;
			renderColorSwatches(count + 1);
		});

		$(document).on('click', '[data-threaddesk-color-decrease]', function () {
			const count = parseInt(designModal.find('[data-threaddesk-color-count]').text(), 10) || 1;
			renderColorSwatches(count - 1);
		});

		$(document).on('change', '[data-threaddesk-design-file]', function () {
			const fileName = this.files && this.files.length ? this.files[0].name : 'No file selected';
			designModal.find('[data-threaddesk-design-file-name]').text(fileName);
		});

		$(document).on('input change', '[data-threaddesk-color-swatches] input[type="color"]', function () {
			applyDesignPreviewColors();
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape') {
				closeDesignModal();
			}
		});

		renderColorSwatches(1);
	}
});
