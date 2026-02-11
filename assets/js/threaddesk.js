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
		const allowedSwatchPalette = [
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
			{ name: 'Dolphin Grey', hex: '#A7A8AA' },
			{ name: 'Shark Grey', hex: '#A7A8AA' },
			{ name: 'Bora Bora Sand', hex: '#F2E9DB' },
		];
		const minimumPercent = 0.5;
		const mergeThreshold = 22;
		const maxAnalysisDimension = 2000;
		const maxSwatches = 8;
		let uploadedPreviewUrl = null;
		let recolorTimer = null;
		let reanalyzeTimer = null;
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
				maximumColorCount: 4,
			},
			hasUserAdjustedMax: false,
			activeSwatchIndex: 0,
			showPaletteOptions: false,
		};

		const previewContainer = designModal.find('[data-threaddesk-design-preview]');
		const previewImage = designModal.find('[data-threaddesk-design-upload-preview]');
		const previewCanvas = designModal.find('[data-threaddesk-design-canvas]');
		const previewSvg = previewContainer.find('svg');
		const maxColorInput = designModal.find('[data-threaddesk-max-colors]');
		const colorCountOutput = designModal.find('[data-threaddesk-color-count]');
		const statusEl = designModal.find('[data-threaddesk-design-status]');
		const designIdField = designModal.find('[data-threaddesk-design-id-field]');
		const initialPreviewHeight = Math.round(previewContainer.outerHeight() || 0);
		if (initialPreviewHeight > 0) {
			previewContainer.css('--threaddesk-preview-max-height', initialPreviewHeight + 'px');
		}

		const clamp = function (value, min, max) {
			return Math.max(min, Math.min(max, value));
		};

		const hexToRgb = function (hex) {
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
		const findClosestAllowedColor = function (hex) {
			const rgb = hexToRgb(hex);
			let best = allowedSwatchPalette[0] || { hex: '#111111', name: 'Black' };
			let bestDist = Number.POSITIVE_INFINITY;
			allowedSwatchPalette.forEach(function (option) {
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
			designModal.find('[data-threaddesk-design-settings]').val(JSON.stringify(state.analysisSettings));
		};

		const renderVectorFallback = function () {
			const colors = state.palette.length ? state.palette : [defaultPalette[0]];
			designModal.find('[data-threaddesk-preview-layer]').each(function (index) {
				$(this).attr('fill', colors[index] || colors[0]);
			});
			previewContainer.css('--threaddesk-preview-accent', colors[0] || defaultPalette[0]);
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
				const color = paletteRgb[label] || paletteRgb[0] || [0, 0, 0];
				output.data[px] = color[0];
				output.data[px + 1] = color[1];
				output.data[px + 2] = color[2];
				output.data[px + 3] = alpha;
			}
			ctx.putImageData(output, 0, 0);
			previewContainer.attr('data-threaddesk-preview-mode', 'quantized');
			previewContainer.css('--threaddesk-preview-accent', state.palette[0] || defaultPalette[0]);
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
					.attr('title', currentAllowed.name + ' ' + currentAllowed.hex)
					.attr('aria-label', 'Color ' + (index + 1) + ' ' + currentAllowed.name)
					.attr('data-color-name', currentAllowed.name)
					.css('background', currentAllowed.hex);
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
					.attr('aria-label', option.name + ' ' + option.hex)
					.attr('data-color-name', option.name)
					.css('background', option.hex);
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

			const counts = Array.from({ length: k }, function () { return 0; });
			for (let i = 0; i < labels.length; i += 1) {
				counts[labels[i]] += 1;
			}
			const minCount = Math.max(1, Math.floor((minimumPercent / 100) * pixels.length));
			let survivors = [];
			for (let c = 0; c < k; c += 1) {
				if (counts[c] >= minCount) {
					survivors.push({ color: centroids[c].slice(0, 3), count: counts[c], from: [c] });
				}
			}
			if (!survivors.length) {
				const strongest = counts.indexOf(Math.max.apply(null, counts));
				survivors = [{ color: centroids[strongest].slice(0, 3), count: counts[strongest], from: [strongest] }];
			}

			const merged = [];
			survivors.sort(function (a, b) { return b.count - a.count; });
			survivors.forEach(function (candidate) {
				let mergedInto = null;
				for (let i = 0; i < merged.length; i += 1) {
					if (Math.sqrt(colorDistanceSq(candidate.color, merged[i].color)) <= mergeThreshold) {
						mergedInto = merged[i];
						break;
					}
				}
				if (!mergedInto) {
					merged.push({ color: candidate.color.slice(0, 3), count: candidate.count, from: candidate.from.slice(0) });
					return;
				}
				const total = mergedInto.count + candidate.count;
				mergedInto.color = [
					((mergedInto.color[0] * mergedInto.count) + (candidate.color[0] * candidate.count)) / total,
					((mergedInto.color[1] * mergedInto.count) + (candidate.color[1] * candidate.count)) / total,
					((mergedInto.color[2] * mergedInto.count) + (candidate.color[2] * candidate.count)) / total,
				];
				mergedInto.count = total;
				mergedInto.from = mergedInto.from.concat(candidate.from);
			});

			merged.sort(function (a, b) { return b.count - a.count; });
			const finalClusters = merged.slice(0, k);
			const oldToFinal = {};
			for (let i = 0; i < finalClusters.length; i += 1) {
				finalClusters[i].from.forEach(function (oldIndex) { oldToFinal[oldIndex] = i; });
			}

			const fullLabels = new Uint8Array(totalPixels);
			const fullCounts = Array.from({ length: finalClusters.length }, function () { return 0; });
			for (let i = 0; i < labels.length; i += 1) {
				let finalIndex = oldToFinal[labels[i]];
				if (typeof finalIndex === 'undefined') {
					let nearest = 0;
					let nearestDist = Number.POSITIVE_INFINITY;
					for (let c = 0; c < finalClusters.length; c += 1) {
						const dist = colorDistanceSq(pixels[i], finalClusters[c].color);
						if (dist < nearestDist) {
							nearestDist = dist;
							nearest = c;
						}
					}
					finalIndex = nearest;
				}
				fullLabels[opaqueIndices[i]] = finalIndex;
				fullCounts[finalIndex] += 1;
			}

			const totalOpaque = fullCounts.reduce(function (sum, v) { return sum + v; }, 0);
			const percentages = fullCounts.map(function (count) {
				return totalOpaque ? (count / totalOpaque) * 100 : 0;
			});

			return {
				palette: finalClusters.map(function (cluster) { return rgbToHex(cluster.color); }),
				percentages: percentages,
				labels: fullLabels,
			};
		};

		const loadImageFromFile = function (file) {
			return new Promise(function (resolve, reject) {
				const isSvg = file.type === 'image/svg+xml' || /\.svg$/i.test(file.name || '');
				const img = new Image();
				img.onload = function () { resolve({ image: img, isSvg: isSvg }); };
				img.onerror = function () { reject(new Error('Failed to load image')); };
				if (isSvg) {
					const reader = new FileReader();
					reader.onload = function () {
						const svgBlob = new Blob([reader.result], { type: 'image/svg+xml' });
						const objectUrl = URL.createObjectURL(svgBlob);
						img.onload = function () {
							URL.revokeObjectURL(objectUrl);
							resolve({ image: img, isSvg: true });
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

		const createAnalysisBuffer = function (image) {
			const scale = Math.min(1, maxAnalysisDimension / Math.max(image.naturalWidth || image.width, image.naturalHeight || image.height));
			const width = Math.max(1, Math.round((image.naturalWidth || image.width) * scale));
			const height = Math.max(1, Math.round((image.naturalHeight || image.height) * scale));
			const canvas = document.createElement('canvas');
			canvas.width = width;
			canvas.height = height;
			const ctx = canvas.getContext('2d', { willReadFrequently: true });
			ctx.clearRect(0, 0, width, height);
			ctx.drawImage(image, 0, 0, width, height);
			return { width: width, height: height, imageData: ctx.getImageData(0, 0, width, height) };
		};

		const analyzeCurrentImage = async function () {
			if (!state.sourcePixels || !state.width || !state.height) {
				return;
			}
			setStatus('Analyzing colors…');
			await new Promise(function (resolve) { window.setTimeout(resolve, 0); });
			const pixels = [];
			const opaqueIndices = [];
			for (let i = 0; i < state.sourcePixels.length; i += 4) {
				const alpha = state.sourcePixels[i + 3];
				if (alpha < 8) {
					continue;
				}
				pixels.push([state.sourcePixels[i], state.sourcePixels[i + 1], state.sourcePixels[i + 2]]);
				opaqueIndices.push(i / 4);
			}
			if (!pixels.length) {
				state.palette = [];
				state.percentages = [];
				state.labels = null;
				renderColorSwatches();
				setStatus('No colors detected');
				return;
			}

			const maxColors = clamp(parseInt(maxColorInput.val(), 10) || 4, 1, maxSwatches);
			state.analysisSettings.maximumColorCount = maxColors;
			const quantized = quantizeColors(pixels, opaqueIndices, state.width * state.height, maxColors);
			if (!quantized || !quantized.palette.length) {
				state.palette = [];
				state.percentages = [];
				state.labels = null;
				renderColorSwatches();
				setStatus('No colors detected');
				return;
			}

			if (!state.hasUserAdjustedMax) {
				const recommended = clamp(quantized.palette.length || 4, 1, maxSwatches);
				state.analysisSettings.maximumColorCount = recommended;
				maxColorInput.val(String(recommended));
			}
			state.palette = normalizePaletteToAllowed(quantized.palette);
			state.percentages = quantized.percentages;
			state.labels = quantized.labels;
			renderColorSwatches();
			queueRecolor();
			setStatus('Detected ' + state.palette.length + ' colors');
		};

		$(document).on('click', '[data-threaddesk-design-open]', function (event) {
			event.preventDefault();
			openDesignModal();
			designIdField.val('0');

			const designFileInput = designModal.find('[data-threaddesk-design-file]').get(0);
			if (designFileInput) {
				designFileInput.click();
			}
		});


		$(document).on('click', '[data-threaddesk-design-edit]', function (event) {
			event.preventDefault();
			openDesignModal();
			const designId = parseInt($(this).attr('data-threaddesk-design-id'), 10) || 0;
			const previewUrl = $(this).attr('data-threaddesk-design-preview-url') || '';
			const fileName = $(this).attr('data-threaddesk-design-file-name') || 'No file selected';
			const paletteRaw = $(this).attr('data-threaddesk-design-palette') || '[]';
			const settingsRaw = $(this).attr('data-threaddesk-design-settings') || '{}';
			designIdField.val(String(designId));
			designModal.find('[data-threaddesk-design-file-name]').text(fileName);
			previewContainer.removeAttr('data-threaddesk-preview-mode');
			if (previewUrl) {
				previewImage.attr('src', previewUrl);
				previewContainer.addClass('has-upload');
				previewSvg.attr('aria-hidden', 'true');
			}
			let palette = [];
			let settings = {};
			try { palette = JSON.parse(paletteRaw); } catch (e) {}
			try { settings = JSON.parse(settingsRaw); } catch (e) {}
			state.palette = normalizePaletteToAllowed(Array.isArray(palette) && palette.length ? palette : defaultPalette.slice(0, 4));
			state.analysisSettings.maximumColorCount = clamp(parseInt(settings.maximumColorCount, 10) || state.palette.length || 4, 1, maxSwatches);
			maxColorInput.val(String(state.analysisSettings.maximumColorCount));
			state.labels = null;
			state.sourcePixels = null;
			state.percentages = [];
			state.showPaletteOptions = false;
			renderColorSwatches();
			renderVectorFallback();
			setStatus('Editing saved design');
		});
		$(document).on('click', '[data-threaddesk-design-close]', function () {
			closeDesignModal();
		});

		$(document).on('input change', '[data-threaddesk-max-colors]', function () {
			const value = clamp(parseInt($(this).val(), 10) || 4, 1, maxSwatches);
			colorCountOutput.text(String(value));
			state.analysisSettings.maximumColorCount = value;
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
			previewContainer.removeAttr('data-threaddesk-preview-mode');

			if (uploadedPreviewUrl) {
				URL.revokeObjectURL(uploadedPreviewUrl);
				uploadedPreviewUrl = null;
			}

			if (!file) {
				previewImage.attr('src', '');
				previewContainer.removeClass('has-upload');
				previewSvg.removeAttr('aria-hidden');
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

			const supported = /image\/(png|jpeg|svg\+xml)/i.test(file.type) || /\.(png|jpe?g|svg)$/i.test(file.name || '');
			if (!supported) {
				setStatus('Unsupported file type. Please upload PNG, JPG, or SVG.');
				return;
			}

			try {
				setStatus('Loading image…');
				const loaded = await loadImageFromFile(file);
				uploadedPreviewUrl = URL.createObjectURL(file);
				previewImage.attr('src', uploadedPreviewUrl);
				previewContainer.addClass('has-upload');
				previewSvg.attr('aria-hidden', 'true');

				const analysis = createAnalysisBuffer(loaded.image);
				state.width = analysis.width;
				state.height = analysis.height;
				state.sourcePixels = analysis.imageData.data;
				state.fileType = loaded.isSvg ? 'svg' : 'raster';
				await analyzeCurrentImage();
			} catch (error) {
				state.palette = [];
				state.percentages = [];
				state.labels = null;
				setStatus('No colors detected');
				renderColorSwatches();
			}
			state.palette[index] = hex;
			persistDesignMetadata();
			queueRecolor();
			renderColorSwatches();
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
			const hex = ($(this).attr('data-color-hex') || '').toUpperCase();
			if (Number.isNaN(index) || index < 0 || !/^#[0-9A-F]{6}$/.test(hex)) {
				return;
			}
			state.palette[index] = hex;
			state.showPaletteOptions = false;
			persistDesignMetadata();
			queueRecolor();
			renderColorSwatches();
		});

		$(document).on('keyup', function (event) {
			if (event.key === 'Escape') {
				closeDesignModal();
			}
		});

		maxColorInput.val('4');
		colorCountOutput.text('4');
		state.palette = normalizePaletteToAllowed(defaultPalette.slice(0, 4));
		state.showPaletteOptions = false;
		designModal.removeClass('is-palette-selecting');
		designIdField.val('0');
		renderColorSwatches();
		renderVectorFallback();
		persistDesignMetadata();
	}
});
