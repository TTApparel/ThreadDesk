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



		$(document).on('click', '[data-threaddesk-layout-save-placement]', function () {
			if (!designOverlay.attr('src')) {
				return;
			}
			const button = $(this);
			button.text('Placement Saved');
			setTimeout(function () { button.text('Save Placement'); }, 1400);
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


		$(document).on('click', '[data-threaddesk-layout-save-placement]', function () {
			if (!designOverlay.attr('src')) {
				return;
			}
			const button = $(this);
			button.text('Placement Saved');
			setTimeout(function () { button.text('Save Placement'); }, 1400);
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape') {
				closeAddressModal();
			}
		});
	}


	const layoutModal = $('.threaddesk-layout-modal');

	if (layoutModal.length) {
		let lastLayoutTrigger = null;
		const chooserStep = layoutModal.find('[data-threaddesk-layout-step="chooser"]');
		const viewerStep = layoutModal.find('[data-threaddesk-layout-step="viewer"]');
		const stage = layoutModal.find('.threaddesk-layout-viewer__stage');
		const mainImage = layoutModal.find('[data-threaddesk-layout-main-image]');
		const angleButtons = layoutModal.find('[data-threaddesk-layout-angle]');
		const angleButtonsByKey = {
			front: layoutModal.find('[data-threaddesk-layout-angle="front"]'),
			left: layoutModal.find('[data-threaddesk-layout-angle="left"]'),
			back: layoutModal.find('[data-threaddesk-layout-angle="back"]'),
			right: layoutModal.find('[data-threaddesk-layout-angle="right"]'),
		};
		const angleImages = {
			front: layoutModal.find('[data-threaddesk-layout-angle-image="front"]'),
			left: layoutModal.find('[data-threaddesk-layout-angle-image="left"]'),
			back: layoutModal.find('[data-threaddesk-layout-angle-image="back"]'),
			right: layoutModal.find('[data-threaddesk-layout-angle-image="right"]'),
		};
		const placementList = layoutModal.find('[data-threaddesk-layout-placement-list]');
		const placementEmpty = layoutModal.find('[data-threaddesk-layout-placement-empty]');
		const placementPanelStep = layoutModal.find('[data-threaddesk-layout-panel-step="placements"]');
		const designPanelStep = layoutModal.find('[data-threaddesk-layout-panel-step="designs"]');
		const adjustPanelStep = layoutModal.find('[data-threaddesk-layout-panel-step="adjust"]');
		const designList = layoutModal.find('[data-threaddesk-layout-design-list]');
		const designEmpty = layoutModal.find('[data-threaddesk-layout-design-empty]');
		const designHeading = layoutModal.find('[data-threaddesk-layout-design-heading]');
		const designOverlay = layoutModal.find('[data-threaddesk-layout-design-overlay]');
		const sizeSlider = layoutModal.find('[data-threaddesk-layout-size-slider]');
		const sizeReading = layoutModal.find('[data-threaddesk-layout-size-reading]');
		const selectedPlacementBox = layoutModal.find('[data-threaddesk-layout-selected-placement]');
		const selectedDesignNameEl = layoutModal.find('[data-threaddesk-layout-selected-design]');
		const layoutDesignsRaw = layoutModal.attr('data-threaddesk-layout-designs') || '[]';
		let layoutDesigns = [];
		try { layoutDesigns = JSON.parse(layoutDesignsRaw); } catch (e) { layoutDesigns = []; }

		let selectedPlacementLabel = '';
		let selectedPlacementKey = '';
		let selectedDesignName = '';
		let selectedBaseWidthPct = 34;
		let selectedDesignSourceUrl = '';
		let selectedDesignAspectRatio = 1;
		let currentAngle = 'front';
		let currentAngles = { front: '', left: '', back: '', right: '' };
		let visibleAngles = ['front', 'left', 'back', 'right'];
		let sideConfiguredAsRight = false;
		let dragState = null;
		let currentOverlayConfig = null;
		const savedPlacementsByAngle = { front: {}, left: {}, back: {}, right: {} };
		const overlayRenderScale = 0.75;
		const designRatioCache = {};

		const placementStyleMap = {
			left_chest: { top: 36, left: 40, width: 18, approx: 4.5 },
			right_chest: { top: 36, left: 60, width: 18, approx: 4.5 },
			full_chest: { top: 38, left: 50, width: 34, approx: 10 },
			left_sleeve: { top: 38, left: 24, width: 13, approx: 3.5 },
			right_sleeve: { top: 38, left: 76, width: 13, approx: 3.5 },
			back: { top: 38, left: 50, width: 34, approx: 10 },
		};

		const setPanelStep = function (panel) {
			placementPanelStep.prop('hidden', panel !== 'placements');
			designPanelStep.prop('hidden', panel !== 'designs');
			adjustPanelStep.prop('hidden', panel !== 'adjust');
		};

		const setDesignRatioFromUrl = function (url) {
			const source = String(url || '').trim();
			if (!source) { return; }
			if (Object.prototype.hasOwnProperty.call(designRatioCache, source)) {
				selectedDesignAspectRatio = Number(designRatioCache[source]) > 0 ? Number(designRatioCache[source]) : 1;
				updateSizeReading();
				return;
			}
			const img = new Image();
			img.onload = function () {
				const w = Number(img.naturalWidth || 0);
				const h = Number(img.naturalHeight || 0);
				const ratio = (w > 0 && h > 0) ? (w / h) : 1;
				designRatioCache[source] = ratio;
				selectedDesignAspectRatio = ratio > 0 ? ratio : 1;
				updateSizeReading();
			};
			img.onerror = function () {
				designRatioCache[source] = 1;
				selectedDesignAspectRatio = 1;
				updateSizeReading();
			};
			img.src = source;
		};
		const updateSizeReading = function () {
			const sliderPercent = Number(sizeSlider.val() || 100) / 100;
			const preset = placementStyleMap[selectedPlacementKey] || placementStyleMap.full_chest;
			const ratio = Number(selectedDesignAspectRatio || 1);
			const maxDimension = preset.approx * overlayRenderScale * sliderPercent;
			let widthInches = maxDimension;
			let heightInches = maxDimension;
			if (ratio > 1) {
				heightInches = maxDimension / ratio;
			} else if (ratio > 0 && ratio < 1) {
				widthInches = maxDimension * ratio;
			}
			sizeReading.text('Approx. size: ' + widthInches.toFixed(1) + '" W x ' + heightInches.toFixed(1) + '" H');
		};

		const hideOverlay = function () {
			designOverlay.attr('src', '').prop('hidden', true).removeAttr('style');
			selectedDesignSourceUrl = '';
			currentOverlayConfig = null;
		};

		const applyOverlayStyle = function (cfg) {
			currentOverlayConfig = {
				top: Number(cfg.top),
				left: Number(cfg.left),
				width: Number(cfg.width),
			};
			designOverlay.css({
				top: currentOverlayConfig.top.toFixed(2) + '%',
				left: currentOverlayConfig.left.toFixed(2) + '%',
				width: currentOverlayConfig.width.toFixed(2) + '%',
				transform: 'translate(-50%, -50%)',
				background: 'transparent'
			});
		};

		const getOverlayConfig = function () {
			if (currentOverlayConfig) {
				return {
					top: currentOverlayConfig.top,
					left: currentOverlayConfig.left,
					width: currentOverlayConfig.width,
				};
			}
			return null;
		};

		const applySelectedDesign = function (sourceUrl, configOverride) {
			const url = String(sourceUrl || '').trim();
			if (!url) {
				hideOverlay();
				return;
			}

			const preset = placementStyleMap[selectedPlacementKey] || placementStyleMap.full_chest;
			const sliderPercent = Number(sizeSlider.val() || 100) / 100;
			const cfg = configOverride || {
				top: preset.top,
				left: preset.left,
				width: selectedBaseWidthPct * overlayRenderScale * sliderPercent,
			};

			designOverlay.attr('src', url);
			applyOverlayStyle(cfg);
			designOverlay.prop('hidden', false);
			selectedDesignSourceUrl = url;
			updateSizeReading();
		};

		const restoreSavedPlacementForCurrentAngle = function () {
			if (!selectedPlacementKey) {
				hideOverlay();
				return;
			}
			const saved = savedPlacementsByAngle[currentAngle] && savedPlacementsByAngle[currentAngle][selectedPlacementKey];
			if (!saved || !saved.url) {
				hideOverlay();
				return;
			}
			selectedDesignName = saved.designName || selectedDesignName;
			selectedDesignNameEl.text((selectedDesignName || 'No design selected').toUpperCase());
			selectedBaseWidthPct = Number(saved.baseWidth || selectedBaseWidthPct);
			selectedDesignAspectRatio = Number(saved.designRatio || selectedDesignAspectRatio || 1);
			sizeSlider.val(saved.sliderValue || 100);
			applySelectedDesign(saved.url, { top: saved.top, left: saved.left, width: saved.width });
		};

		const showChooserStep = function () {
			chooserStep.addClass('is-active').prop('hidden', false).attr('aria-hidden', 'false');
			viewerStep.removeClass('is-active').prop('hidden', true).attr('aria-hidden', 'true');
			placementList.empty();
			placementEmpty.hide();
			designList.empty();
			designEmpty.hide();
			selectedPlacementLabel = '';
			selectedPlacementKey = '';
			selectedDesignName = '';
			selectedBaseWidthPct = 34;
			sizeSlider.val(100);
			selectedPlacementBox.text('Placement');
			selectedDesignNameEl.text('No design selected');
			Object.keys(savedPlacementsByAngle).forEach(function (angle) { savedPlacementsByAngle[angle] = {}; });
			hideOverlay();
			updateSizeReading();
		};

		const showViewerStep = function () {
			chooserStep.removeClass('is-active').prop('hidden', true).attr('aria-hidden', 'true');
			viewerStep.addClass('is-active').prop('hidden', false).attr('aria-hidden', 'false');
		};

		const renderPlacementOptions = function (placements) {
			placementList.empty();
			const items = Array.isArray(placements) ? placements : [];
			if (!items.length) {
				placementEmpty.show();
				return;
			}
			placementEmpty.hide();
			items.forEach(function (placement) {
				const label = String((placement && placement.label) || '').trim();
				if (!label) { return; }
				const placementKey = String((placement && placement.key) || '').trim();
				const btn = $('<button type="button" class="threaddesk-layout-viewer__placement-option"></button>')
					.text(label.toUpperCase())
					.attr('data-threaddesk-layout-placement-label', label)
					.attr('data-threaddesk-layout-placement-key', placementKey);
				placementList.append(btn);
			});
			if (!placementList.children().length) { placementEmpty.show(); }
		};

		const renderDesignOptions = function () {
			designList.empty();
			const items = Array.isArray(layoutDesigns) ? layoutDesigns : [];
			if (!items.length) { designEmpty.show(); return; }
			designEmpty.hide();
			items.forEach(function (design) {
				const title = String((design && design.title) || '').trim() || 'Design';
				const svg = String((design && design.svg) || '').trim();
				const preview = String((design && design.preview) || '').trim();
				const mockup = String((design && design.mockup) || '').trim();
				const ratio = Number((design && design.ratio) || 0);
				const displayImage = mockup || preview || svg;
				const option = $('<button type="button" class="threaddesk-layout-viewer__design-option"></button>')
					.attr('data-threaddesk-layout-design-name', title)
					.attr('data-threaddesk-layout-design-svg', svg)
					.attr('data-threaddesk-layout-design-preview', preview)
					.attr('data-threaddesk-layout-design-mockup', mockup)
					.attr('data-threaddesk-layout-design-ratio', ratio > 0 ? String(ratio) : '');
				if (displayImage) {
					option.append($('<img class="threaddesk-layout-viewer__design-option-image" alt="" aria-hidden="true" />').attr('src', displayImage));
				}
				option.append($('<span class="threaddesk-layout-viewer__design-option-title"></span>').text(title));
				designList.append(option);
			});
		};

		const openLayoutModal = function (triggerEl) {
			lastLayoutTrigger = triggerEl || document.activeElement || lastLayoutTrigger;
			layoutModal.addClass('is-active').attr('aria-hidden', 'false');
			$('body').addClass('threaddesk-modal-open');
			showChooserStep();
			setPanelStep('placements');
		};

		const closeLayoutModal = function () {
			layoutModal.removeClass('is-active').attr('aria-hidden', 'true');
			$('body').removeClass('threaddesk-modal-open');
			showChooserStep();
			setPanelStep('placements');
			if (lastLayoutTrigger && typeof lastLayoutTrigger.focus === 'function') {
				try { lastLayoutTrigger.focus(); } catch (e) {}
			}
		};

		const setMainImage = function (angle) {
			const preferred = angle || 'front';
			const target = visibleAngles.indexOf(preferred) > -1 ? preferred : (visibleAngles[0] || 'front');
			currentAngle = target;
			const url = currentAngles[target] || '';
			let transform = 'none';
			if (target === 'left') { transform = sideConfiguredAsRight ? 'scaleX(-1)' : 'none'; }
			else if (target === 'right') { transform = sideConfiguredAsRight ? 'none' : 'scaleX(-1)'; }
			if (url) {
				mainImage.attr('src', url).attr('alt', target + ' view').css('transform', transform).show();
			} else {
				mainImage.attr('src', '').css('transform', 'none').hide();
			}
			angleButtons.removeClass('is-active');
			angleButtonsByKey[target].addClass('is-active');
			restoreSavedPlacementForCurrentAngle();
		};

		showChooserStep();

		$(document).on('click', '[data-threaddesk-layout-open]', function () { openLayoutModal(this); });
		$(document).on('click', '[data-threaddesk-layout-close]', function () { closeLayoutModal(); });
		$(document).on('click', '[data-threaddesk-layout-angle]', function () { setMainImage($(this).data('threaddesk-layout-angle')); });

		$(document).on('click', '[data-threaddesk-layout-category]', function () {
			const rawFront = $(this).data('threaddesk-layout-front-image') || '';
			const rawBack = $(this).data('threaddesk-layout-back-image') || '';
			const rawSide = $(this).data('threaddesk-layout-side-image') || '';
			const sideLabel = String($(this).data('threaddesk-layout-side-label') || 'left').toLowerCase();
			let placements = $(this).attr('data-threaddesk-layout-placements') || '[]';
			try { placements = JSON.parse(placements); } catch (e) { placements = []; }
			const sideIsRight = sideLabel === 'right';
			sideConfiguredAsRight = sideIsRight;
			currentAngles = { front: rawFront, left: rawSide, back: rawBack, right: rawSide };
			const hasFront = !!rawFront; const hasBack = !!rawBack; const hasSide = !!rawSide;
			angleButtonsByKey.front.prop('hidden', !hasFront).toggle(hasFront);
			angleButtonsByKey.back.prop('hidden', !hasBack).toggle(hasBack);
			angleButtonsByKey.left.prop('hidden', !hasSide).toggle(hasSide);
			angleButtonsByKey.right.prop('hidden', !hasSide).toggle(hasSide);
			visibleAngles = [];
			if (hasFront) { visibleAngles.push('front'); }
			if (hasSide) { visibleAngles.push('left'); }
			if (hasBack) { visibleAngles.push('back'); }
			if (hasSide) { visibleAngles.push('right'); }
			angleImages.front.attr('src', currentAngles.front).css('transform', 'none');
			angleImages.back.attr('src', currentAngles.back).css('transform', 'none');
			angleImages.left.attr('src', currentAngles.left).css('transform', sideIsRight ? 'scaleX(-1)' : 'none');
			angleImages.right.attr('src', currentAngles.right).css('transform', sideIsRight ? 'none' : 'scaleX(-1)');
			renderPlacementOptions(placements);
			setPanelStep('placements');
			showViewerStep();
			setMainImage('front');
		});

		$(document).on('click', '.threaddesk-layout-viewer__placement-option', function () {
			selectedPlacementLabel = String($(this).attr('data-threaddesk-layout-placement-label') || '').trim();
			selectedPlacementKey = String($(this).attr('data-threaddesk-layout-placement-key') || '').trim();
			if (!selectedPlacementLabel) { selectedPlacementLabel = 'Placement'; }
			designHeading.text('Choose Design for ' + selectedPlacementLabel);
			sizeSlider.val(100);
			selectedDesignName = '';
			selectedDesignAspectRatio = 1;
			selectedDesignNameEl.text('No design selected');
			selectedPlacementBox.text(selectedPlacementLabel.toUpperCase());
			renderDesignOptions();
			hideOverlay();
			updateSizeReading();
			setPanelStep('designs');
		});

		$(document).on('click', '[data-threaddesk-layout-back-to-placements]', function () { setPanelStep('placements'); });
		$(document).on('click', '[data-threaddesk-layout-back-to-designs]', function () { setPanelStep('designs'); });

		$(document).on('input change', '[data-threaddesk-layout-size-slider]', function () {
			if (!selectedDesignSourceUrl) { updateSizeReading(); return; }
			const overlayCfg = getOverlayConfig();
			if (overlayCfg) {
				const sliderPercent = Number(sizeSlider.val() || 100) / 100;
				overlayCfg.width = selectedBaseWidthPct * overlayRenderScale * sliderPercent;
				applySelectedDesign(selectedDesignSourceUrl, overlayCfg);
			}
		});

		$(document).on('click', '.threaddesk-layout-viewer__design-option', function () {
			const name = String($(this).attr('data-threaddesk-layout-design-name') || '').trim() || 'Design';
			const svgUrl = String($(this).attr('data-threaddesk-layout-design-svg') || '').trim();
			const previewUrl = String($(this).attr('data-threaddesk-layout-design-preview') || '').trim();
			const mockupUrl = String($(this).attr('data-threaddesk-layout-design-mockup') || '').trim();
			const url = mockupUrl || previewUrl || svgUrl;
			const ratioAttr = parseFloat(String($(this).attr('data-threaddesk-layout-design-ratio') || '').trim());
			selectedDesignAspectRatio = (Number.isFinite(ratioAttr) && ratioAttr > 0) ? ratioAttr : 1;
			const preset = placementStyleMap[selectedPlacementKey] || placementStyleMap.full_chest;
			selectedBaseWidthPct = Number(preset.width) || 34;
			selectedDesignName = name;
			selectedPlacementBox.text((selectedPlacementLabel || 'Placement').toUpperCase());
			selectedDesignNameEl.text(selectedDesignName.toUpperCase());
			applySelectedDesign(url);
			setDesignRatioFromUrl(url);
			setPanelStep('adjust');
		});

		const updateDragPosition = function (pageX, pageY) {
			const rect = stage.get(0).getBoundingClientRect();
			if (!rect.width || !rect.height) { return; }
			const x = Math.min(Math.max(pageX - rect.left, 0), rect.width);
			const y = Math.min(Math.max(pageY - rect.top, 0), rect.height);
			const cfg = getOverlayConfig() || { width: selectedBaseWidthPct };
			cfg.left = (x / rect.width) * 100;
			cfg.top = (y / rect.height) * 100;
			applySelectedDesign(selectedDesignSourceUrl, cfg);
		};

		const startDrag = function (event) {
			if (!selectedDesignSourceUrl) { return; }
			dragState = { active: true };
			designOverlay.addClass('is-dragging');
			event.preventDefault();
		};

		designOverlay.on('mousedown', function (event) { startDrag(event); });
		designOverlay.on('touchstart', function (event) { startDrag(event); });

		$(document).on('mousemove', function (event) {
			if (!dragState || !dragState.active) { return; }
			updateDragPosition(event.pageX, event.pageY);
		});
		$(document).on('touchmove', function (event) {
			if (!dragState || !dragState.active) { return; }
			const touch = event.originalEvent.touches && event.originalEvent.touches[0];
			if (!touch) { return; }
			updateDragPosition(touch.pageX, touch.pageY);
		});
		$(document).on('mouseup touchend touchcancel', function () {
			if (!dragState || !dragState.active) { return; }
			dragState = null;
			designOverlay.removeClass('is-dragging');
		});

		$(document).on('click', '[data-threaddesk-layout-save-placement]', function () {
			if (!selectedDesignSourceUrl || !selectedPlacementKey) { return; }
			const cfg = getOverlayConfig();
			if (!cfg) { return; }
			savedPlacementsByAngle[currentAngle][selectedPlacementKey] = {
				url: selectedDesignSourceUrl,
				designName: selectedDesignName,
				top: cfg.top,
				left: cfg.left,
				width: cfg.width,
				baseWidth: selectedBaseWidthPct,
				sliderValue: Number(sizeSlider.val() || 100),
				designRatio: Number(selectedDesignAspectRatio || 1),
			};
			const button = $(this);
			button.text('Placement Saved');
			setTimeout(function () { button.text('Save Placement'); }, 1400);
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape' && layoutModal.hasClass('is-active')) {
				closeLayoutModal();
			}
		});
	}

	const designModal = $('.threaddesk-design-modal');

	if (designModal.length) {
		let lastDesignTrigger = null;
		const openDesignModal = function (triggerEl) {
			lastDesignTrigger = triggerEl || document.activeElement || lastDesignTrigger;
			designModal.addClass('is-active').attr('aria-hidden', 'false');
			$('body').addClass('threaddesk-modal-open');
			updatePreviewMaxHeight();
		};

		const closeDesignModal = function () {
			const focused = document.activeElement;
			if (focused && designModal.get(0) && designModal.get(0).contains(focused)) {
				try { focused.blur(); } catch (e) {}
			}
			designModal.removeClass('is-active').attr('aria-hidden', 'true');
			$('body').removeClass('threaddesk-modal-open');
			if (lastDesignTrigger && typeof lastDesignTrigger.focus === 'function') {
				try { lastDesignTrigger.focus(); } catch (e) {}
			}
		};

		const defaultPalette = ['#111111', '#ffffff', '#1f1f1f', '#3a3a3a', '#f24c3d', '#3366ff', '#21b573', '#f6b200'];
		const allowedSwatchPalette = [
			{ name: 'Transparent', hex: 'transparent' },
			{ name: 'White', hex: '#FFFFFF' },
			{ name: 'Black', hex: '#000000' },
			{ name: 'Lemon Yellow', hex: '#FEDB00' },
			{ name: 'Rich Yellow', hex: '#FED141' },
			{ name: 'Gold Yellow', hex: '#FFB81C' },
			{ name: 'Orange', hex: '#FF6A39' },
			{ name: 'Athletic Orange', hex: '#E38331' },
			{ name: 'Academy Orange', hex: '#BE531C' },
			{ name: 'Vivid Red', hex: '#C8102E' },
			{ name: 'Scarlet Red', hex: '#D22730' },
			{ name: 'Bold Red', hex: '#BE3A34' },
			{ name: 'Cardinal Red', hex: '#A6192E' },
			{ name: 'Maroon', hex: '#A50034' },
			{ name: 'Pure Pink', hex: '#FF85BD' },
			{ name: 'Lilac', hex: '#BA9CC5' },
			{ name: 'Violet', hex: '#512D6D' },
			{ name: 'Magenta', hex: '#833177' },
			{ name: 'Dark Purple', hex: '#351F65' },
			{ name: 'Ultramarine', hex: '#10069F' },
			{ name: 'Navy Blue', hex: '#131F29' },
			{ name: 'Light Navy Blue', hex: '#28334A' },
			{ name: 'Royal Blue', hex: '#002D72' },
			{ name: 'Moon Blue', hex: '#004C97' },
			{ name: 'Columbia Blue', hex: '#0076A8' },
			{ name: 'Sky Blue', hex: '#8BBEE8' },
			{ name: 'Ocean Blue', hex: '#0092CB' },
			{ name: 'Caribbean Blue', hex: '#00AFD7' },
			{ name: 'Aqua Blue', hex: '#007C80' },
			{ name: 'Jungle Green', hex: '#007A53' },
			{ name: 'Light Green', hex: '#00AD50' },
			{ name: 'Forest Green', hex: '#249E6B' },
			{ name: 'Pine Green', hex: '#00664F' },
			{ name: 'Dark Green', hex: '#304F42' },
			{ name: 'Dark Brown', hex: '#4E3629' },
			{ name: 'Light Brown', hex: '#7B4D35' },
			{ name: 'Khaki', hex: '#D3BC8D' },
			{ name: 'Vegas Gold', hex: '#D5CB9F' },
			{ name: 'Dolphin Grey', hex: '#B1B3B3' },
			{ name: 'Shark Grey', hex: '#A7A8AA' },
			{ name: 'Bora Bora Sand', hex: '#F2E9DB' },
		];
		const minimumPercent = 0.5;
		const mergeThreshold = 22;
		const maxSwatches = 8;
		const potraceTurdsize = 2;
		const potraceAlphamax = 1.0;
		const potraceOpticurve = true;
		const potraceOpttolerance = 0.2;
		const multiScanSmooth = true;
		const multiScanStack = true;
		const designPreviewMaxDimension = 960;
		const exportVectorMaxDimension = 2400;
		const savedVectorMatchPreviewMaxDimension = designPreviewMaxDimension;
		const previewVectorMaxPixels = 260000;
		const exportVectorMaxPixels = 4000000;
		const highResVectorMaxPixels = 36000000;
		let uploadedPreviewUrl = null;
		let recolorTimer = null;
		let reanalyzeTimer = null;
		let shouldOpenModalAfterChoose = false;
		const state = {
			palette: [],
			percentages: [],
			labels: null,
			sourcePixels: null,
			width: 0,
			height: 0,
			fileType: '',
			analysisSettings: {
				minimumPercent: minimumPercent,
				mergeThreshold: mergeThreshold,
				potraceTurdsize: potraceTurdsize,
				potraceAlphamax: potraceAlphamax,
				potraceOpticurve: potraceOpticurve,
				potraceOpttolerance: potraceOpttolerance,
				multiScanSmooth: multiScanSmooth,
				multiScanStack: multiScanStack,
				maximumColorCount: 8,
			},
			hasUserAdjustedMax: false,
			activeSwatchIndex: 0,
			showPaletteOptions: false,
		};

		const previewContainer = designModal.find('[data-threaddesk-design-preview]');
		const previewImage = designModal.find('[data-threaddesk-design-upload-preview]');
		const previewCanvas = designModal.find('[data-threaddesk-design-canvas]');
		const previewSvg = previewContainer.find('svg');
		const previewVector = $('<svg class="threaddesk-designer__design-vector-preview" aria-hidden="true" focusable="false"></svg>');
		previewContainer.append(previewVector);
		const maxColorInput = designModal.find('[data-threaddesk-max-colors]');
		const colorCountOutput = designModal.find('[data-threaddesk-color-count]');
		const statusEl = designModal.find('[data-threaddesk-design-status]');
		const designIdField = designModal.find('[data-threaddesk-design-id-field]');
		const designForm = designModal.find('form.threaddesk-auth-modal__form-inner').first();
		const designTitleInput = designModal.find('[data-threaddesk-design-title-input]');
		const updatePreviewMaxHeight = function () {
			const panelHeight = Math.round(designModal.find('.threaddesk-auth-modal__panel').innerHeight() || 0);
			if (panelHeight <= 0) {
				return;
			}
			const targetHeight = Math.max(120, Math.floor(panelHeight / 3));
			previewContainer.css('--threaddesk-preview-max-height', targetHeight + 'px');
		};
		updatePreviewMaxHeight();
		$(window).on('resize', updatePreviewMaxHeight);
		previewContainer.css('--threaddesk-preview-bg', '#FFFFFF');

		const clamp = function (value, min, max) {
			return Math.max(min, Math.min(max, value));
		};

		const resolveTraceSettings = function (settings) {
			const raw = settings || {};
			const maximumColorCount = clamp(parseInt(raw.maximumColorCount, 10) || 8, 1, maxSwatches);
			return {
				MS_scans: maximumColorCount,
				maximumColorCount: maximumColorCount,
				potraceTurdsize: potraceTurdsize,
				potraceAlphamax: potraceAlphamax,
				potraceOpticurve: potraceOpticurve,
				potraceOpttolerance: potraceOpttolerance,
				multiScanSmooth: raw.multiScanSmooth !== false,
				multiScanStack: true,
				exportReverseOrder: raw.exportReverseOrder === true,
			};
		};

		const isTransparentColor = function (value) {
			return String(value || '').toLowerCase() === 'transparent';
		};

		const hexToRgb = function (hex) {
			if (isTransparentColor(hex)) {
				return [255, 255, 255];
			}
			const normalized = (hex || '').replace('#', '');
			if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
				return [17, 17, 17];
			}
			return [
				parseInt(normalized.substring(0, 2), 16),
				parseInt(normalized.substring(2, 4), 16),
				parseInt(normalized.substring(4, 6), 16),
			];
		};

		const rgbToHex = function (color) {
			return '#' + color.map(function (v) {
				return clamp(Math.round(v), 0, 255).toString(16).padStart(2, '0');
			}).join('');
		};

		const colorDistanceSq = function (a, b) {
			const dr = a[0] - b[0];
			const dg = a[1] - b[1];
			const db = a[2] - b[2];
			return (dr * dr) + (dg * dg) + (db * db);
		};
		const blendRgbOverWhite = function (r, g, b, alpha) {
			const a = clamp((Number(alpha) || 0) / 255, 0, 1);
			const inv = 1 - a;
			return [
				Math.round((r * a) + (255 * inv)),
				Math.round((g * a) + (255 * inv)),
				Math.round((b * a) + (255 * inv)),
			];
		};
		const findClosestAllowedColor = function (hex) {
			if (isTransparentColor(hex)) {
				return allowedSwatchPalette.find(function (option) {
					return isTransparentColor(option.hex);
				}) || { hex: 'transparent', name: 'Transparent' };
			}
			const rgb = hexToRgb(hex);
			let best = allowedSwatchPalette.find(function (option) {
				return !isTransparentColor(option.hex);
			}) || { hex: '#111111', name: 'Black' };
			let bestDist = Number.POSITIVE_INFINITY;
			allowedSwatchPalette.forEach(function (option) {
				if (isTransparentColor(option.hex)) {
					return;
				}
				const dist = colorDistanceSq(rgb, hexToRgb(option.hex));
				if (dist < bestDist) {
					bestDist = dist;
					best = option;
				}
			});
			return best;
		};

		const normalizePaletteToAllowed = function (palette) {
			return (palette || []).map(function (hex) {
				return findClosestAllowedColor(hex).hex;
			});
		};

		const seedFromPixels = function (pixels) {
			let hash = 2166136261;
			for (let i = 0; i < pixels.length; i += Math.max(1, Math.floor(pixels.length / 512))) {
				const p = pixels[i];
				hash ^= (p[0] << 16) ^ (p[1] << 8) ^ p[2];
				hash = Math.imul(hash, 16777619);
			}
			return hash >>> 0;
		};

		const createRng = function (seed) {
			let value = seed || 123456789;
			return function () {
				value = (Math.imul(value, 1664525) + 1013904223) >>> 0;
				return value / 4294967296;
			};
		};

		const setStatus = function (message) {
			statusEl.text(message || '');
		};

		const persistDesignMetadata = function () {
			designModal.find('[data-threaddesk-design-palette]').val(JSON.stringify(state.palette));
			designModal.find('[data-threaddesk-design-color-count]').val(String(state.palette.length || 0));
			state.analysisSettings.MS_scans = clamp(parseInt(state.analysisSettings.maximumColorCount, 10) || 1, 1, maxSwatches);
			designModal.find('[data-threaddesk-design-settings]').val(JSON.stringify(state.analysisSettings));
		};


		const syncPreviewBackgroundColor = function () {
			const hasWhite = (state.palette || []).some(function (hex) {
				return String(hex || '').toUpperCase() === '#FFFFFF';
			});
			previewContainer.css('--threaddesk-preview-bg', hasWhite ? '#F6F6F6' : '#FFFFFF');
		};

		const renderVectorFallback = function () {
			const colors = state.palette.length ? state.palette : [defaultPalette[0]];
			designModal.find('[data-threaddesk-preview-layer]').each(function (index) {
				const color = colors[index] || colors[0];
				$(this).attr('fill', isTransparentColor(color) ? 'none' : color);
			});
			previewContainer.css('--threaddesk-preview-accent', colors[0] || defaultPalette[0]);
			syncPreviewBackgroundColor();
		};


		const buildTraceBitmapSvgMarkup = function (labels, sourcePixels, width, height, palette, maxPixels, vectorSettings) {
			const safeWidth = Math.max(1, parseInt(width, 10) || 1);
			const safeHeight = Math.max(1, parseInt(height, 10) || 1);
			const pixelLimit = Math.max(1, parseInt(maxPixels, 10) || exportVectorMaxPixels);
			if (!labels || !sourcePixels || !palette || !palette.length || (safeWidth * safeHeight) > pixelLimit) {
				return '';
			}

			const traceSettings = resolveTraceSettings(vectorSettings || {});
			const scans = clamp(parseInt(traceSettings.MS_scans, 10) || palette.length || 1, 1, Math.min(maxSwatches, palette.length));
			const totalPixels = safeWidth * safeHeight;
			const cumulativeMask = new Uint8Array(totalPixels);
			const layerMarkup = [];
			const speckleThreshold = Math.max(2, parseInt(traceSettings.potraceTurdsize, 10) || 2);
			const smoothPasses = traceSettings.multiScanSmooth === true ? 1 : 0;

			const removeSmallConnectedComponents = function (mask, minArea) {
				if (!mask || minArea <= 1) {
					return;
				}
				const visited = new Uint8Array(mask.length);
				const neighbors = [-1, 1, -safeWidth, safeWidth];
				for (let i = 0; i < mask.length; i += 1) {
					if (mask[i] !== 1 || visited[i] === 1) {
						continue;
					}
					const stack = [i];
					const component = [];
					visited[i] = 1;
					while (stack.length) {
						const current = stack.pop();
						component.push(current);
						const x = current % safeWidth;
						for (let n = 0; n < neighbors.length; n += 1) {
							const next = current + neighbors[n];
							if (next < 0 || next >= mask.length || visited[next] === 1 || mask[next] !== 1) {
								continue;
							}
							if ((neighbors[n] === -1 && x === 0) || (neighbors[n] === 1 && x === safeWidth - 1)) {
								continue;
							}
							visited[next] = 1;
							stack.push(next);
						}
					}
					if (component.length <= minArea) {
						for (let c = 0; c < component.length; c += 1) {
							mask[component[c]] = 0;
						}
					}
				}
			};

			const smoothMaskEdges = function (mask) {
				if (!mask || smoothPasses <= 0) {
					return mask;
				}
				let working = mask.slice();
				for (let pass = 0; pass < smoothPasses; pass += 1) {
					const next = working.slice();
					for (let y = 1; y < safeHeight - 1; y += 1) {
						for (let x = 1; x < safeWidth - 1; x += 1) {
							const index = (y * safeWidth) + x;
							let count = 0;
							for (let oy = -1; oy <= 1; oy += 1) {
								for (let ox = -1; ox <= 1; ox += 1) {
									if (ox === 0 && oy === 0) {
										continue;
									}
									const nidx = ((y + oy) * safeWidth) + (x + ox);
									count += working[nidx] === 1 ? 1 : 0;
								}
							}
							const left = working[index - 1] === 1;
							const right = working[index + 1] === 1;
							const up = working[index - safeWidth] === 1;
							const down = working[index + safeWidth] === 1;
							const isLikelyTextStroke = (left && right && !up && !down) || (!left && !right && up && down);
							if (working[index] === 1) {
								if (!isLikelyTextStroke && count <= 2) {
									next[index] = 0;
								}
							} else if (count >= 6) {
								next[index] = 1;
							}
						}
					}
					working = next;
				}
				return working;
			};

			const maskToPath = function (mask) {
				const segments = [];
				for (let y = 0; y < safeHeight; y += 1) {
					const rowOffset = y * safeWidth;
					let x = 0;
					while (x < safeWidth) {
						if (mask[rowOffset + x] !== 1) {
							x += 1;
							continue;
						}
						const runStart = x;
						x += 1;
						while (x < safeWidth && mask[rowOffset + x] === 1) {
							x += 1;
						}
						segments.push('M' + runStart + ' ' + y + 'H' + x + 'V' + (y + 1) + 'H' + runStart + 'Z');
					}
				}
				return segments.join('');
			};

			for (let colorIndex = 0; colorIndex < scans; colorIndex += 1) {
				const currentLayerMask = new Uint8Array(totalPixels);
				for (let pixelIndex = 0; pixelIndex < totalPixels; pixelIndex += 1) {
					if ((labels[pixelIndex] || 0) !== colorIndex) {
						continue;
					}
					const alpha = sourcePixels[(pixelIndex * 4) + 3] || 0;
					if (alpha < 8) {
						continue;
					}
					currentLayerMask[pixelIndex] = 1;
				}
				removeSmallConnectedComponents(currentLayerMask, speckleThreshold);
				for (let pixelIndex = 0; pixelIndex < totalPixels; pixelIndex += 1) {
					if (currentLayerMask[pixelIndex] === 1) {
						cumulativeMask[pixelIndex] = 1;
					}
				}
				const smoothedMask = smoothMaskEdges(cumulativeMask);
				const d = maskToPath(smoothedMask);
				if (!d) {
					continue;
				}
				const fillColor = palette[colorIndex] || palette[palette.length - 1] || '#111111';
				const transparentAsWhite = vectorSettings && vectorSettings.transparentAsWhite === true;
				if (isTransparentColor(fillColor) && !transparentAsWhite) {
					continue;
				}
				layerMarkup.push('<path fill="' + (isTransparentColor(fillColor) ? '#FFFFFF' : fillColor) + '" d="' + d + '"/>');
			}

			if (!layerMarkup.length) {
				return '';
			}
			const orderedLayerMarkup = traceSettings.exportReverseOrder === true ? layerMarkup.slice().reverse() : layerMarkup;
			return '<svg xmlns="http://www.w3.org/2000/svg" width="' + safeWidth + '" height="' + safeHeight + '" viewBox="0 0 ' + safeWidth + ' ' + safeHeight + '" shape-rendering="geometricPrecision">' + orderedLayerMarkup.join('') + '</svg>';
		};


		const renderQuantizedPreview = function () {
			if (!state.labels || !state.sourcePixels || !state.palette.length || !previewCanvas.length) {
				return;
			}
			const canvas = previewCanvas.get(0);
			canvas.width = state.width;
			canvas.height = state.height;
			const ctx = canvas.getContext('2d', { willReadFrequently: true });
			const output = ctx.createImageData(state.width, state.height);
			const paletteRgb = state.palette.map(hexToRgb);
			for (let i = 0; i < state.labels.length; i += 1) {
				const px = i * 4;
				const label = state.labels[i];
				const alpha = state.sourcePixels[px + 3];
				if (!alpha) {
					output.data[px + 3] = 0;
					continue;
				}
				const sourceColor = state.palette[label] || state.palette[0] || '#000000';
				if (isTransparentColor(sourceColor)) {
					output.data[px + 3] = 0;
					continue;
				}
				const color = paletteRgb[label] || paletteRgb[0] || [0, 0, 0];
				output.data[px] = color[0];
				output.data[px + 1] = color[1];
				output.data[px + 2] = color[2];
				output.data[px + 3] = alpha;
			}
			ctx.putImageData(output, 0, 0);

			if (previewVector.length) {
				previewVector.empty();
			}
			previewContainer.attr('data-threaddesk-preview-mode', 'quantized');
			previewContainer.css('--threaddesk-preview-accent', state.palette[0] || defaultPalette[0]);
			syncPreviewBackgroundColor();
		};

		const queueRecolor = function () {
			if (recolorTimer) {
				window.cancelAnimationFrame(recolorTimer);
			}
			recolorTimer = window.requestAnimationFrame(function () {
				if (state.labels && state.palette.length) {
					renderQuantizedPreview();
				} else {
					renderVectorFallback();
				}
			});
		};

		const renderColorSwatches = function () {
			const swatches = designModal.find('[data-threaddesk-color-swatches]');
			swatches.empty();
			const palette = state.palette.slice(0, maxSwatches);
			if (!palette.length) {
				designModal.find('.threaddesk-designer__controls').removeClass('is-palette-selecting');
				designModal.removeClass('is-palette-selecting');
				swatches.append($('<p></p>').text('No colors detected'));
				setStatus('No colors detected');
				colorCountOutput.text(String(state.analysisSettings.maximumColorCount));
				persistDesignMetadata();
				return;
			}

			if (state.activeSwatchIndex >= palette.length) {
				state.activeSwatchIndex = 0;
			}

			const activeIndex = Math.max(0, state.activeSwatchIndex || 0);
			const activeColor = findClosestAllowedColor(palette[activeIndex]);
			designModal.find('.threaddesk-designer__controls').toggleClass('is-palette-selecting', !!state.showPaletteOptions);
			designModal.toggleClass('is-palette-selecting', !!state.showPaletteOptions);

			const panel = $('<div class="threaddesk-designer__palette-panel"></div>');

			const inUse = $('<div class="threaddesk-designer__palette-in-use"></div>');
			inUse.append($('<p class="threaddesk-designer__palette-title"></p>').text('Colors In Use'));
			const inUseRow = $('<div class="threaddesk-designer__palette-row"></div>');
			palette.forEach(function (hex, index) {
				const currentAllowed = findClosestAllowedColor(hex);
				const btn = $('<button type="button" class="threaddesk-designer__palette-dot" data-threaddesk-inuse-color></button>')
					.attr('data-threaddesk-swatch-index', index)
					.attr('title', currentAllowed.name + (isTransparentColor(currentAllowed.hex) ? '' : (' ' + currentAllowed.hex)))
					.attr('aria-label', 'Color ' + (index + 1) + ' ' + currentAllowed.name)
					.attr('data-color-name', currentAllowed.name);
				if (isTransparentColor(currentAllowed.hex)) {
					btn.css('background-image', 'linear-gradient(45deg, #d7d7d7 25%, transparent 25%, transparent 75%, #d7d7d7 75%, #d7d7d7), linear-gradient(45deg, #d7d7d7 25%, transparent 25%, transparent 75%, #d7d7d7 75%, #d7d7d7)')
						.css('background-size', '10px 10px')
						.css('background-position', '0 0, 5px 5px')
						.css('background-color', '#ffffff');
				} else {
					btn.css('background', currentAllowed.hex);
				}
				if (index === activeIndex) {
					btn.addClass('is-active');
				}
				inUseRow.append(btn);
			});
			inUse.append(inUseRow);
			panel.append(inUse);

			const optionsWrap = $('<div class="threaddesk-designer__palette-options" data-threaddesk-palette-options></div>');
			if (!state.showPaletteOptions) {
				optionsWrap.attr('hidden', true);
			}
			optionsWrap.append($('<p class="threaddesk-designer__palette-title"></p>').text('Colors'));
			const optionsGrid = $('<div class="threaddesk-designer__palette-grid"></div>');
			allowedSwatchPalette.forEach(function (option) {
				const opt = $('<button type="button" class="threaddesk-designer__palette-dot" data-threaddesk-palette-option></button>')
					.attr('data-color-hex', option.hex)
					.attr('title', option.name)
					.attr('aria-label', option.name + (isTransparentColor(option.hex) ? '' : (' ' + option.hex)))
					.attr('data-color-name', option.name);
				if (isTransparentColor(option.hex)) {
					opt.css('background-image', 'linear-gradient(45deg, #d7d7d7 25%, transparent 25%, transparent 75%, #d7d7d7 75%, #d7d7d7), linear-gradient(45deg, #d7d7d7 25%, transparent 25%, transparent 75%, #d7d7d7 75%, #d7d7d7)')
						.css('background-size', '10px 10px')
						.css('background-position', '0 0, 5px 5px')
						.css('background-color', '#ffffff');
				} else {
					opt.css('background', option.hex);
				}
				if (option.hex.toLowerCase() === findClosestAllowedColor(palette[activeIndex]).hex.toLowerCase()) {
					opt.addClass('is-active');
				}
				optionsGrid.append(opt);
			});
			optionsWrap.append(optionsGrid);
			panel.append(optionsWrap);

			swatches.append(panel);
			colorCountOutput.text(String(state.analysisSettings.maximumColorCount));
			persistDesignMetadata();
		};

		const chooseInitialCentroids = function (pixels, k, seed) {
			const rng = createRng(seed);
			const centroids = [];
			const first = Math.floor(rng() * pixels.length);
			centroids.push(pixels[first].slice(0, 3));
			while (centroids.length < k) {
				let farthestIndex = 0;
				let farthestDist = -1;
				for (let i = 0; i < pixels.length; i += 1) {
					let nearest = Number.POSITIVE_INFINITY;
					for (let c = 0; c < centroids.length; c += 1) {
						const dist = colorDistanceSq(pixels[i], centroids[c]);
						if (dist < nearest) {
							nearest = dist;
						}
					}
					if (nearest > farthestDist) {
						farthestDist = nearest;
						farthestIndex = i;
					}
				}
				centroids.push(pixels[farthestIndex].slice(0, 3));
			}
			return centroids;
		};

		const createQuantizationPixels = function (sourcePixels, width, height, useSmooth) {
			if (!sourcePixels || !width || !height) {
				return { pixels: [], opaqueIndices: [] };
			}
			const working = useSmooth ? blurRgbaSource(blurRgbaSource(sourcePixels, width, height), width, height) : sourcePixels;
			const pixels = [];
			const opaqueIndices = [];
			for (let i = 0; i < working.length; i += 4) {
				const alpha = sourcePixels[i + 3];
				if (alpha < 8) {
					continue;
				}
				const blended = blendRgbOverWhite(working[i], working[i + 1], working[i + 2], alpha);
				pixels.push(blended);
				opaqueIndices.push(i / 4);
			}
			return { pixels: pixels, opaqueIndices: opaqueIndices };
		};

		const blurRgbaSource = function (sourcePixels, width, height) {
			const out = new Uint8ClampedArray(sourcePixels.length);
			const kernel = [1, 2, 1];
			for (let y = 0; y < height; y += 1) {
				for (let x = 0; x < width; x += 1) {
					const dst = ((y * width) + x) * 4;
					out[dst + 3] = sourcePixels[dst + 3];
					for (let channel = 0; channel < 3; channel += 1) {
						let sum = 0;
						let weight = 0;
						for (let oy = -1; oy <= 1; oy += 1) {
							const yy = clamp(y + oy, 0, height - 1);
							for (let ox = -1; ox <= 1; ox += 1) {
								const xx = clamp(x + ox, 0, width - 1);
								const w = kernel[ox + 1] * kernel[oy + 1];
								const src = ((yy * width) + xx) * 4;
								sum += sourcePixels[src + channel] * w;
								weight += w;
							}
						}
						out[dst + channel] = Math.round(sum / Math.max(1, weight));
					}
				}
			}
			return out;
		};

		const quantizeColors = function (pixels, opaqueIndices, totalPixels, maxColors) {
			if (!pixels.length) {
				return null;
			}
			const k = clamp(maxColors, 1, maxSwatches);
			const seed = seedFromPixels(pixels);
			let centroids = chooseInitialCentroids(pixels, k, seed);
			const labels = new Uint8Array(pixels.length);

			for (let iter = 0; iter < 7; iter += 1) {
				const sums = Array.from({ length: k }, function () {
					return { r: 0, g: 0, b: 0, count: 0 };
				});
				for (let i = 0; i < pixels.length; i += 1) {
					let nearest = 0;
					let nearestDist = Number.POSITIVE_INFINITY;
					for (let c = 0; c < k; c += 1) {
						const dist = colorDistanceSq(pixels[i], centroids[c]);
						if (dist < nearestDist) {
							nearestDist = dist;
							nearest = c;
						}
					}
					labels[i] = nearest;
					sums[nearest].r += pixels[i][0];
					sums[nearest].g += pixels[i][1];
					sums[nearest].b += pixels[i][2];
					sums[nearest].count += 1;
				}
				for (let c = 0; c < k; c += 1) {
					if (sums[c].count > 0) {
						centroids[c] = [
							sums[c].r / sums[c].count,
							sums[c].g / sums[c].count,
							sums[c].b / sums[c].count,
						];
					}
				}
			}

			const fullLabels = new Uint8Array(totalPixels);
			const fullCounts = Array.from({ length: k }, function () { return 0; });
			for (let i = 0; i < labels.length; i += 1) {
				const colorIndex = labels[i] || 0;
				fullLabels[opaqueIndices[i]] = colorIndex;
				fullCounts[colorIndex] += 1;
			}

			const totalOpaque = fullCounts.reduce(function (sum, v) { return sum + v; }, 0);
			const percentages = fullCounts.map(function (count) {
				return totalOpaque ? (count / totalOpaque) * 100 : 0;
			});

			return {
				palette: centroids.map(function (clusterColor) { return rgbToHex(clusterColor); }),
				percentages: percentages,
				labels: fullLabels,
			};
		};


		const parseSvgLength = function (value) {
			if (typeof value !== 'string') {
				return 0;
			}
			const trimmed = value.trim();
			if (!trimmed || trimmed.endsWith('%')) {
				return 0;
			}
			const numeric = parseFloat(trimmed);
			return Number.isFinite(numeric) && numeric > 0 ? numeric : 0;
		};

		const parseSvgDimensionsFromText = function (svgText) {
			if (!svgText || typeof svgText !== 'string') {
				return null;
			}
			try {
				const parser = new DOMParser();
				const doc = parser.parseFromString(svgText, 'image/svg+xml');
				const svg = doc && doc.documentElement && doc.documentElement.nodeName.toLowerCase() === 'svg'
					? doc.documentElement
					: doc.querySelector('svg');
				if (!svg) {
					return null;
				}
				let width = parseSvgLength(svg.getAttribute('width') || '');
				let height = parseSvgLength(svg.getAttribute('height') || '');
				const viewBox = (svg.getAttribute('viewBox') || '').trim();
				if ((!width || !height) && viewBox) {
					const parts = viewBox.split(/[\s,]+/).map(function (part) { return parseFloat(part); });
					if (parts.length === 4 && Number.isFinite(parts[2]) && Number.isFinite(parts[3])) {
						width = width || Math.max(0, parts[2]);
						height = height || Math.max(0, parts[3]);
					}
				}
				if (!width || !height) {
					return null;
				}
				return { width: Math.round(width), height: Math.round(height) };
			} catch (error) {
				return null;
			}
		};

		const getSvgTextFromUrl = async function (url) {
			if (!url) {
				return '';
			}
			try {
				if (/^data:image\/svg\+xml/i.test(url)) {
					const comma = url.indexOf(',');
					if (comma < 0) {
						return '';
					}
					const header = url.slice(0, comma);
					const body = url.slice(comma + 1);
					return /;base64/i.test(header) ? atob(body) : decodeURIComponent(body);
				}
				const response = await fetch(url, { credentials: 'same-origin' });
				if (!response.ok) {
					return '';
				}
				return await response.text();
			} catch (error) {
				return '';
			}
		};

		const getSvgDimensionsFromUrl = async function (url) {
			if (!url) {
				return null;
			}
			const svgText = await getSvgTextFromUrl(url);
			if (!svgText) {
				return null;
			}
			return parseSvgDimensionsFromText(svgText);
		};

		const loadImageFromFile = function (file) {
			return new Promise(function (resolve, reject) {
				const isSvg = file.type === 'image/svg+xml' || /\.svg$/i.test(file.name || '');
				const img = new Image();
				img.onload = function () { resolve({ image: img, isSvg: isSvg, svgDimensions: null }); };
				img.onerror = function () { reject(new Error('Failed to load image')); };
				if (isSvg) {
					const reader = new FileReader();
					reader.onload = function () {
						const svgDimensions = parseSvgDimensionsFromText(String(reader.result || ''));
						const svgBlob = new Blob([reader.result], { type: 'image/svg+xml' });
						const objectUrl = URL.createObjectURL(svgBlob);
						img.onload = function () {
							URL.revokeObjectURL(objectUrl);
							resolve({ image: img, isSvg: true, svgDimensions: svgDimensions });
						};
						img.onerror = function () {
							URL.revokeObjectURL(objectUrl);
							reject(new Error('Failed to parse SVG'));
						};
						img.src = objectUrl;
					};
					reader.onerror = function () { reject(new Error('Failed to read SVG')); };
					reader.readAsText(file);
					return;
				}
				img.src = URL.createObjectURL(file);
			});
		};

		const createAnalysisBuffer = function (image, preferredDimensions, options) {
			const preferredWidth = preferredDimensions && preferredDimensions.width ? preferredDimensions.width : 0;
			const preferredHeight = preferredDimensions && preferredDimensions.height ? preferredDimensions.height : 0;
			const sourceWidth = Math.max(1, Math.round(image.naturalWidth || image.width || 1));
			const sourceHeight = Math.max(1, Math.round(image.naturalHeight || image.height || 1));
			const baseWidth = Math.max(1, Math.round(preferredWidth || sourceWidth));
			const baseHeight = Math.max(1, Math.round(preferredHeight || sourceHeight));
			const maxDimension = Math.max(64, parseInt(options && options.maxDimension, 10) || designPreviewMaxDimension);
			const initialScale = Math.min(1, maxDimension / Math.max(baseWidth, baseHeight));
			const canvas = document.createElement('canvas');
			let scale = initialScale;
			let lastError = null;
			for (let attempt = 0; attempt < 5; attempt += 1) {
				const width = Math.max(1, Math.round(baseWidth * scale));
				const height = Math.max(1, Math.round(baseHeight * scale));
				try {
					canvas.width = width;
					canvas.height = height;
					const ctx = canvas.getContext('2d', { willReadFrequently: true });
					if (!ctx) {
						throw new Error('Canvas 2D context unavailable');
					}
					ctx.clearRect(0, 0, width, height);
					ctx.drawImage(image, 0, 0, width, height);
					const imageData = ctx.getImageData(0, 0, width, height);
					return { width: width, height: height, imageData: imageData };
				} catch (error) {
					lastError = error;
					if (width <= 1 || height <= 1) {
						break;
					}
					scale *= 0.75;
				}
			}
			throw lastError || new Error('Unable to create analysis buffer');
		};

		const loadCanvasFromPreviewUrl = async function (url) {
			const svgDimensions = await getSvgDimensionsFromUrl(url);
			return new Promise(function (resolve) {
				if (!url || !previewCanvas.length) {
					resolve(false);
					return;
				}
				const img = new Image();
				img.crossOrigin = 'anonymous';
				img.onload = function () {
					const analysis = createAnalysisBuffer(img, svgDimensions, { maxDimension: designPreviewMaxDimension });
					state.width = analysis.width;
					state.height = analysis.height;
					state.sourcePixels = analysis.imageData.data;
					state.fileType = 'raster';
					const canvas = previewCanvas.get(0);
					canvas.width = analysis.width;
					canvas.height = analysis.height;
					const ctx = canvas.getContext('2d', { willReadFrequently: true });
					ctx.clearRect(0, 0, analysis.width, analysis.height);
					ctx.drawImage(img, 0, 0, analysis.width, analysis.height);
					previewContainer.attr('data-threaddesk-preview-mode', 'quantized');
					resolve(true);
				};
				img.onerror = function () {
					resolve(false);
				};
				img.src = url;
			});
		};


		const labelsToVectorSvgDataUrl = function (labels, sourcePixels, width, height, paletteHex) {
			const svgMarkup = buildTraceBitmapSvgMarkup(labels, sourcePixels, width, height, paletteHex, 160000, state.analysisSettings);
			if (!svgMarkup) {
				return '';
			}
			return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svgMarkup);
		};

		const buildVectorSvgMarkup = function (labels, sourcePixels, width, height, palette, maxPixels, vectorSettings) {
			return buildTraceBitmapSvgMarkup(labels, sourcePixels, width, height, palette, maxPixels, vectorSettings);
		};

		const buildSmoothExportSvgMarkup = function (labels, sourcePixels, width, height, palette, maxPixels, vectorSettings) {
			const exportSettings = $.extend({}, vectorSettings || {}, { exportReverseOrder: true });
			return buildVectorSvgMarkup(labels, sourcePixels, width, height, palette, maxPixels, exportSettings);
		};

		const buildRectVectorSvgMarkup = function (labels, sourcePixels, width, height, palette, maxPixels, vectorSettings) {
			const exportSettings = $.extend({}, vectorSettings || {}, { exportReverseOrder: true });
			return buildTraceBitmapSvgMarkup(labels, sourcePixels, width, height, palette, maxPixels, exportSettings);
		};


		const createSavedDesignVectorMarkup = async function (previewUrl, paletteRaw, settingsRaw) {
			if (!previewUrl) {
				return '';
			}
			let palette = [];
			let settings = {};
			try { palette = JSON.parse(paletteRaw || '[]'); } catch (e) {}
			try { settings = JSON.parse(settingsRaw || '{}'); } catch (e) {}
			const normalizedPalette = normalizePaletteToAllowed(Array.isArray(palette) && palette.length ? palette : defaultPalette.slice(0, 4));
			try {
				const traceSettings = resolveTraceSettings(settings);
				const maxColors = clamp(parseInt(traceSettings.MS_scans, 10) || normalizedPalette.length || 4, 1, maxSwatches);
				const image = new Image();
				image.crossOrigin = 'anonymous';
				const svgDimensions = await getSvgDimensionsFromUrl(previewUrl);
				const loaded = await new Promise(function (resolve) {
					image.onload = function () { resolve(true); };
					image.onerror = function () { resolve(false); };
					image.src = previewUrl;
				});
				if (!loaded) {
					return '';
				}
				const analysis = createAnalysisBuffer(image, svgDimensions, { maxDimension: savedVectorMatchPreviewMaxDimension });
				const quantSource = createQuantizationPixels(
					analysis.imageData.data,
					analysis.width,
					analysis.height,
					traceSettings.multiScanSmooth === true
				);
				if (!quantSource.pixels.length) {
					return '';
				}
				const quantized = quantizeColors(quantSource.pixels, quantSource.opaqueIndices, analysis.width * analysis.height, maxColors);
				if (!quantized || !quantized.labels) {
					return '';
				}
				const exportSettings = $.extend({}, traceSettings, { transparentAsWhite: false });
				const smoothSvg = buildSmoothExportSvgMarkup(quantized.labels, analysis.imageData.data, analysis.width, analysis.height, normalizedPalette, highResVectorMaxPixels, exportSettings);
				if (smoothSvg) {
					return smoothSvg;
				}
				return buildRectVectorSvgMarkup(quantized.labels, analysis.imageData.data, analysis.width, analysis.height, normalizedPalette, highResVectorMaxPixels * 2, exportSettings);
			} catch (error) {
				return '';
			}
		};

		const recolorCardPreview = async function (imgEl, previewUrl, paletteRaw, settingsRaw) {
			if (!imgEl || !previewUrl) {
				return;
			}

			let palette = [];
			let settings = {};
			try { palette = JSON.parse(paletteRaw || '[]'); } catch (e) {}
			try { settings = JSON.parse(settingsRaw || '{}'); } catch (e) {}

			const normalizedPalette = normalizePaletteToAllowed(Array.isArray(palette) && palette.length ? palette : defaultPalette.slice(0, 4));
			const traceSettings = resolveTraceSettings(settings);
			const maxColors = clamp(parseInt(traceSettings.MS_scans, 10) || normalizedPalette.length || 4, 1, maxSwatches);
			const image = new Image();
			image.crossOrigin = 'anonymous';
			const svgDimensions = await getSvgDimensionsFromUrl(previewUrl);
			const loaded = await new Promise(function (resolve) {
				image.onload = function () { resolve(true); };
				image.onerror = function () { resolve(false); };
				image.src = previewUrl;
			});

			if (!loaded) {
				return;
			}

			const analysis = createAnalysisBuffer(image, svgDimensions, { maxDimension: savedVectorMatchPreviewMaxDimension });
			const quantSource = createQuantizationPixels(
				analysis.imageData.data,
				analysis.width,
				analysis.height,
				traceSettings.multiScanSmooth === true
			);

			if (!quantSource.pixels.length) {
				return;
			}

			const quantized = quantizeColors(quantSource.pixels, quantSource.opaqueIndices, analysis.width * analysis.height, maxColors);
			if (!quantized || !quantized.labels) {
				return;
			}

			const vectorDataUrl = labelsToVectorSvgDataUrl(
				quantized.labels,
				analysis.imageData.data,
				analysis.width,
				analysis.height,
				normalizedPalette
			);
			if (vectorDataUrl) {
				imgEl.src = vectorDataUrl;
				return;
			}

			const canvas = document.createElement('canvas');
			canvas.width = analysis.width;
			canvas.height = analysis.height;
			const ctx = canvas.getContext('2d');
			const output = new Uint8ClampedArray(analysis.imageData.data.length);
			const paletteRgb = normalizedPalette.map(hexToRgb);
			for (let pixelIndex = 0; pixelIndex < quantized.labels.length; pixelIndex += 1) {
				const offset = pixelIndex * 4;
				const alpha = analysis.imageData.data[offset + 3];
				if (alpha < 8) {
					output[offset + 3] = 0;
					continue;
				}
				const label = quantized.labels[pixelIndex] || 0;
				const sourceColor = normalizedPalette[label] || normalizedPalette[0] || '#000000';
				if (isTransparentColor(sourceColor)) {
					output[offset + 3] = 0;
					continue;
				}
				const color = paletteRgb[label] || paletteRgb[0] || [0, 0, 0];
				output[offset] = color[0];
				output[offset + 1] = color[1];
				output[offset + 2] = color[2];
				output[offset + 3] = alpha;
			}
			ctx.putImageData(new ImageData(output, analysis.width, analysis.height), 0, 0);
			imgEl.src = canvas.toDataURL('image/png');
		};

		const analyzeCurrentImage = async function (forceDetectedMax) {
			if (!state.sourcePixels || !state.width || !state.height) {
				return;
			}
			setStatus('Analyzing colors…');
			await new Promise(function (resolve) { window.setTimeout(resolve, 0); });
			const traceSettings = resolveTraceSettings(state.analysisSettings);
			const quantSource = createQuantizationPixels(
				state.sourcePixels,
				state.width,
				state.height,
				traceSettings.multiScanSmooth === true
			);
			if (!quantSource.pixels.length) {
				state.palette = [];
				state.percentages = [];
				state.labels = null;
				renderColorSwatches();
				setStatus('No colors detected');
				return;
			}

			const maxColors = clamp(parseInt(maxColorInput.val(), 10) || 4, 1, maxSwatches);
			state.analysisSettings.maximumColorCount = maxColors;
			state.analysisSettings.MS_scans = maxColors;
			const quantized = quantizeColors(quantSource.pixels, quantSource.opaqueIndices, state.width * state.height, maxColors);
			if (!quantized || !quantized.palette.length) {
				state.palette = [];
				state.percentages = [];
				state.labels = null;
				renderColorSwatches();
				setStatus('No colors detected');
				return;
			}

			if (forceDetectedMax || !state.hasUserAdjustedMax) {
				const recommended = clamp(quantized.palette.length || 4, 1, maxSwatches);
				state.analysisSettings.maximumColorCount = recommended;
				state.analysisSettings.MS_scans = recommended;
				maxColorInput.val(String(recommended));
			}
			state.palette = normalizePaletteToAllowed(quantized.palette);
			state.percentages = quantized.percentages;
			state.labels = quantized.labels;
			renderColorSwatches();
			queueRecolor();
			setStatus('Detected ' + state.palette.length + ' colors');
		};

		const openAndPromptDesignUpload = function () {
			shouldOpenModalAfterChoose = true;
			const designFileInput = designModal.find('[data-threaddesk-design-file]').get(0);
			if (!designFileInput) {
				shouldOpenModalAfterChoose = false;
				return;
			}
			try {
				if (typeof designFileInput.showPicker === 'function') {
					designFileInput.showPicker();
					return;
				}
				const hadHiddenAttr = designFileInput.hasAttribute('hidden');
				const previousStyle = designFileInput.getAttribute('style') || '';
				if (hadHiddenAttr) {
					designFileInput.removeAttribute('hidden');
				}
				designFileInput.setAttribute('style', 'position:fixed;left:-9999px;top:0;opacity:0;pointer-events:none;');
				designFileInput.click();
				window.setTimeout(function () {
					if (hadHiddenAttr) {
						designFileInput.setAttribute('hidden', 'hidden');
					}
					if (previousStyle) {
						designFileInput.setAttribute('style', previousStyle);
					} else {
						designFileInput.removeAttribute('style');
					}
				}, 0);
			} catch (error) {
				setStatus('Unable to open file picker. Please click the chooser again.');
			}
		};

		$(document).on('click', '[data-threaddesk-design-open]', function (event) {
			event.preventDefault();
			openAndPromptDesignUpload();
		});


		$(document).on('click', '[data-threaddesk-design-edit]', async function (event) {
			event.preventDefault();
			shouldOpenModalAfterChoose = false;
			openDesignModal(this);
			const designId = parseInt($(this).attr('data-threaddesk-design-id'), 10) || 0;
			const title = $(this).attr('data-threaddesk-design-title') || '';
			const previewUrl = $(this).attr('data-threaddesk-design-preview-url') || '';
			const fileName = $(this).attr('data-threaddesk-design-file-name') || 'No file selected';
			const paletteRaw = $(this).attr('data-threaddesk-design-palette') || '[]';
			const settingsRaw = $(this).attr('data-threaddesk-design-settings') || '{}';
			designIdField.val(String(designId));
			designTitleInput.val(title);
			designModal.find('[data-threaddesk-design-file-name]').text(fileName);
			previewContainer.removeAttr('data-threaddesk-preview-mode');
			if (previewUrl) {
				previewImage.attr('src', previewUrl);
				previewContainer.addClass('has-upload');
				previewSvg.attr('aria-hidden', 'true');
				await loadCanvasFromPreviewUrl(previewUrl);
			}
			let palette = [];
			let settings = {};
			try { palette = JSON.parse(paletteRaw); } catch (e) {}
			try { settings = JSON.parse(settingsRaw); } catch (e) {}
			state.palette = normalizePaletteToAllowed(Array.isArray(palette) && palette.length ? palette : defaultPalette.slice(0, 4));
			state.analysisSettings.maximumColorCount = clamp(parseInt(settings.maximumColorCount, 10) || state.palette.length || 4, 1, maxSwatches);
			state.analysisSettings.MS_scans = state.analysisSettings.maximumColorCount;
			const traceSettings = resolveTraceSettings(settings);
			state.analysisSettings.potraceTurdsize = traceSettings.potraceTurdsize;
			state.analysisSettings.potraceAlphamax = traceSettings.potraceAlphamax;
			state.analysisSettings.potraceOpticurve = traceSettings.potraceOpticurve;
			state.analysisSettings.potraceOpttolerance = traceSettings.potraceOpttolerance;
			state.analysisSettings.multiScanSmooth = traceSettings.multiScanSmooth;
			state.analysisSettings.multiScanStack = traceSettings.multiScanStack;
			maxColorInput.val(String(state.analysisSettings.maximumColorCount));
			state.hasUserAdjustedMax = true;
			state.percentages = [];
			state.showPaletteOptions = false;
			if (state.sourcePixels) {
				await analyzeCurrentImage();
				state.palette = normalizePaletteToAllowed(Array.isArray(palette) && palette.length ? palette : state.palette);
				queueRecolor();
			}
			renderColorSwatches();
			renderVectorFallback();
			setStatus('Editing saved design');
		});
		$(document).on('click', '[data-threaddesk-design-close]', function () {
			shouldOpenModalAfterChoose = false;
			closeDesignModal();
		});

		$(document).on('input change', '[data-threaddesk-max-colors]', function () {
			const value = clamp(parseInt($(this).val(), 10) || 4, 1, maxSwatches);
			colorCountOutput.text(String(value));
			state.analysisSettings.maximumColorCount = value;
			state.analysisSettings.MS_scans = value;
			state.hasUserAdjustedMax = true;
			persistDesignMetadata();
			if (!state.sourcePixels) {
				return;
			}
			window.clearTimeout(reanalyzeTimer);
			reanalyzeTimer = window.setTimeout(function () {
				analyzeCurrentImage();
			}, 180);
		});

		$(document).on('change', '[data-threaddesk-design-file]', async function () {
			const file = this.files && this.files.length ? this.files[0] : null;
			const fileName = file ? file.name : 'No file selected';
			designModal.find('[data-threaddesk-design-file-name]').text(fileName);
			if (file && (parseInt(designIdField.val(), 10) || 0) === 0) {
				designTitleInput.val((file.name || '').replace(/\.[^.]+$/, ''));
			}
			previewContainer.removeAttr('data-threaddesk-preview-mode');

			if (uploadedPreviewUrl) {
				URL.revokeObjectURL(uploadedPreviewUrl);
				uploadedPreviewUrl = null;
			}

			if (!file) {
				shouldOpenModalAfterChoose = false;
				previewImage.attr('src', '');
				previewContainer.removeClass('has-upload');
				previewSvg.removeAttr('aria-hidden');
				previewVector.empty();
				state.palette = normalizePaletteToAllowed(defaultPalette.slice(0, 4));
				state.percentages = [];
				state.labels = null;
				state.sourcePixels = null;
				renderColorSwatches();
				renderVectorFallback();
				setStatus('');
				state.showPaletteOptions = false;
				designIdField.val('0');
				designModal.removeClass('is-palette-selecting');
				return;
			}

			const supported = /image\/(png|jpeg)/i.test(file.type) || /\.(png|jpe?g)$/i.test(file.name || '');
			if (!supported) {
				shouldOpenModalAfterChoose = false;
				setStatus('Unsupported file type. Please upload PNG or JPG.');
				return;
			}

			if (shouldOpenModalAfterChoose) {
				openDesignModal(document.activeElement);
				designIdField.val('0');
				state.hasUserAdjustedMax = false;
				shouldOpenModalAfterChoose = false;
			}

			try {
				setStatus('Loading image…');
				const loaded = await loadImageFromFile(file);
				uploadedPreviewUrl = URL.createObjectURL(file);
				previewImage.attr('src', uploadedPreviewUrl);
				previewContainer.addClass('has-upload');
				previewSvg.attr('aria-hidden', 'true');

				const analysis = createAnalysisBuffer(loaded.image, loaded.svgDimensions || null, { maxDimension: designPreviewMaxDimension });
				state.width = analysis.width;
				state.height = analysis.height;
				state.sourcePixels = analysis.imageData.data;
				state.fileType = loaded.isSvg ? 'svg' : 'raster';
				state.hasUserAdjustedMax = false;
				await analyzeCurrentImage(true);
			} catch (error) {
				state.palette = [];
				state.percentages = [];
				state.labels = null;
				setStatus('No colors detected');
				renderColorSwatches();
			}
		});

		$('[data-threaddesk-design-edit]').each(function () {
			const trigger = $(this);
			const previewUrl = trigger.attr('data-threaddesk-design-preview-url') || '';
			const paletteRaw = trigger.attr('data-threaddesk-design-palette') || '[]';
			const settingsRaw = trigger.attr('data-threaddesk-design-settings') || '{}';
			const cardImage = trigger.closest('.threaddesk__card').find('.threaddesk__card-design-preview-svg').get(0);
			recolorCardPreview(cardImage, previewUrl, paletteRaw, settingsRaw).catch(function () {});
		});

		$(document).on('click', '[data-threaddesk-inuse-color]', function (event) {
			event.preventDefault();
			const index = parseInt($(this).attr('data-threaddesk-swatch-index'), 10);
			if (Number.isNaN(index) || index < 0) {
				return;
			}
			state.activeSwatchIndex = index;
			state.showPaletteOptions = true;
			renderColorSwatches();
		});

		$(document).on('click', '[data-threaddesk-palette-option]', function (event) {
			event.preventDefault();
			const index = Math.max(0, state.activeSwatchIndex || 0);
			const rawColor = ($(this).attr('data-color-hex') || '');
			const isTransparent = rawColor.toLowerCase() === 'transparent';
			const hex = isTransparent ? 'transparent' : rawColor.toUpperCase();
			if (Number.isNaN(index) || index < 0 || (!isTransparent && !/^#[0-9A-F]{6}$/.test(hex))) {
				return;
			}
			state.palette[index] = hex;
			state.showPaletteOptions = false;
			persistDesignMetadata();
			queueRecolor();
			renderColorSwatches();
		});

		$(document).on('click', '[data-threaddesk-design-download-svg]', async function (event) {
			event.preventDefault();
			const trigger = $(this);
			const persistedSvgUrl = trigger.attr('data-threaddesk-design-svg-url') || '';
			const persistedSvgName = trigger.attr('data-threaddesk-design-svg-name') || '';
			const previewUrl = trigger.attr('data-threaddesk-design-preview-url') || '';
			const paletteRaw = trigger.attr('data-threaddesk-design-palette') || '[]';
			const settingsRaw = trigger.attr('data-threaddesk-design-settings') || '{}';
			const fileNameRaw = trigger.attr('data-threaddesk-design-file-name') || 'design';
			const baseName = fileNameRaw.replace(/\.[^.]+$/, '') || 'design';
			trigger.prop('disabled', true);
			try {
				if (persistedSvgUrl) {
					const link = document.createElement('a');
					link.href = persistedSvgUrl;
					link.download = persistedSvgName || (baseName + '-vector.svg');
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
					return;
				}
				const svgMarkup = await createSavedDesignVectorMarkup(previewUrl, paletteRaw, settingsRaw);
				if (!svgMarkup) {
					setStatus('Unable to generate vector for this design');
					return;
				}
				const blob = new Blob([svgMarkup], { type: 'image/svg+xml;charset=utf-8' });
				const objectUrl = URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.href = objectUrl;
				link.download = baseName + '-vector.svg';
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				URL.revokeObjectURL(objectUrl);
			} finally {
				trigger.prop('disabled', false);
			}
		});


		const getCurrentMockupPngData = function () {
			if (previewCanvas.length) {
				const canvasEl = previewCanvas.get(0);
				if (canvasEl && canvasEl.width > 0 && canvasEl.height > 0) {
					try {
						return canvasEl.toDataURL('image/png');
					} catch (error) {
						return '';
					}
				}
			}
			return '';
		};

		if (designForm.length) {
			designForm.on('submit', async function (event) {
				if (designForm.data('threaddeskSubmitting')) {
					return;
				}
				event.preventDefault();
				const svgField = designForm.find('[data-threaddesk-design-svg-markup]');
				const mockupPngField = designForm.find('[data-threaddesk-design-mockup-png]');
				if (!svgField.length) {
					designForm.data('threaddeskSubmitting', true);
					designForm.get(0).submit();
					return;
				}
				const submitButton = designForm.find('[type="submit"]').first();
				submitButton.prop('disabled', true);
				let svgMarkup = '';
				const saveVectorSettings = $.extend({}, state.analysisSettings || {}, { transparentAsWhite: false });
				if (state.labels && state.sourcePixels && state.palette.length && state.width && state.height) {
					svgMarkup = buildSmoothExportSvgMarkup(state.labels, state.sourcePixels, state.width, state.height, state.palette, exportVectorMaxPixels, saveVectorSettings);
					if (!svgMarkup) {
						svgMarkup = buildRectVectorSvgMarkup(state.labels, state.sourcePixels, state.width, state.height, state.palette, exportVectorMaxPixels * 2, saveVectorSettings);
					}
				}
				if (!svgMarkup) {
					const currentPreviewUrl = (previewImage.attr('src') || '').trim();
					if (currentPreviewUrl) {
						svgMarkup = await createSavedDesignVectorMarkup(
							currentPreviewUrl,
							JSON.stringify(state.palette || []),
							JSON.stringify(state.analysisSettings || {})
						);
					}
				}
				svgField.val(svgMarkup || '');
				if (mockupPngField.length) {
					mockupPngField.val(getCurrentMockupPngData());
				}
				designForm.data('threaddeskSubmitting', true);
				designForm.get(0).submit();
			});
		}


		$(document).on('click', '[data-threaddesk-layout-save-placement]', function () {
			if (!designOverlay.attr('src')) {
				return;
			}
			const button = $(this);
			button.text('Placement Saved');
			setTimeout(function () { button.text('Save Placement'); }, 1400);
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape') {
				closeDesignModal();
			}
		});

		maxColorInput.val('8');
		colorCountOutput.text('8');
		state.palette = normalizePaletteToAllowed(defaultPalette.slice(0, 8));
		state.showPaletteOptions = false;
		designModal.removeClass('is-palette-selecting');
		designIdField.val('0');
		designTitleInput.val('');
		renderColorSwatches();
		renderVectorFallback();
		persistDesignMetadata();

		const submitCardTitleForm = function (input) {
			const field = $(input);
			const form = field.closest('form');
			if (!form.length) {
				return;
			}
			const value = (field.val() || '').trim();
			if (!value) {
				const fallback = (field.attr('value') || '').trim() || 'Design';
				field.val(fallback);
				return;
			}
			if (value === (field.attr('value') || '').trim()) {
				return;
			}
			form.trigger('submit');
		};

		$(document).on('keydown', '[data-threaddesk-design-title-card-input]', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				submitCardTitleForm(this);
			}
		});

		$(document).on('blur', '[data-threaddesk-design-title-card-input]', function () {
			submitCardTitleForm(this);
		});
	}
});
