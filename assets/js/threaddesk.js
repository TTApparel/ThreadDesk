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
			updatePreviewMaxHeight();
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
		const maxSwatches = 8;
		const potraceTurdsize = 2;
		const potraceAlphamax = 1.0;
		const potraceOpticurve = true;
		const potraceOpttolerance = 0.2;
		const multiScanSmooth = false;
		const multiScanStack = true;
		const designPreviewMaxDimension = 960;
		const designCardMaxDimension = 420;
		const exportVectorMaxDimension = 2400;
		const savedVectorMatchPreviewMaxDimension = designPreviewMaxDimension;
		const previewVectorMaxPixels = 260000;
		const exportVectorMaxPixels = 4000000;
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


		const syncPreviewBackgroundColor = function () {
			const hasWhite = (state.palette || []).some(function (hex) {
				return String(hex || '').toUpperCase() === '#FFFFFF';
			});
			previewContainer.css('--threaddesk-preview-bg', hasWhite ? '#F6F6F6' : '#FFFFFF');
		};

		const renderVectorFallback = function () {
			const colors = state.palette.length ? state.palette : [defaultPalette[0]];
			designModal.find('[data-threaddesk-preview-layer]').each(function (index) {
				$(this).attr('fill', colors[index] || colors[0]);
			});
			previewContainer.css('--threaddesk-preview-accent', colors[0] || defaultPalette[0]);
			syncPreviewBackgroundColor();
		};


		const buildVectorPathByColor = function (labels, sourcePixels, width, height, palette, maxPixels, vectorSettings) {
			if (!labels || !sourcePixels || !palette || !palette.length || !width || !height) {
				return '';
			}
			const pixelLimit = Math.max(1, parseInt(maxPixels, 10) || previewVectorMaxPixels);
			if ((width * height) > pixelLimit) {
				return '';
			}

			const toKey = function (x, y) {
				return String(x) + ',' + String(y);
			};

			const parseKey = function (key) {
				const parts = key.split(',');
				return [parseInt(parts[0], 10) || 0, parseInt(parts[1], 10) || 0];
			};

			const isOpaquePixel = function (pixelIndex) {
				return (sourcePixels[(pixelIndex * 4) + 3] || 0) >= 8;
			};

			const createBinaryMaskForLabel = function (targetLabel) {
				const mask = new Uint8Array(width * height);
				for (let pixelIndex = 0; pixelIndex < labels.length; pixelIndex += 1) {
					if (!isOpaquePixel(pixelIndex)) {
						continue;
					}
					if ((labels[pixelIndex] || 0) === targetLabel) {
						mask[pixelIndex] = 1;
					}
				}
				return mask;
			};

			const hasMaskPixel = function (x, y, mask) {
				if (x < 0 || y < 0 || x >= width || y >= height) {
					return false;
				}
				return !!mask[(y * width) + x];
			};

			const polygonArea = function (points) {
				if (!points || points.length < 3) {
					return 0;
				}
				let area = 0;
				for (let i = 0; i < points.length; i += 1) {
					const current = points[i];
					const next = points[(i + 1) % points.length];
					area += (current[0] * next[1]) - (next[0] * current[1]);
				}
				return Math.abs(area) / 2;
			};

			const removeCollinearPoints = function (points, epsilon) {
				if (points.length < 3) {
					return points;
				}
				const tolerance = Math.max(0, Number(epsilon) || 0);
				const cleaned = [];
				for (let i = 0; i < points.length; i += 1) {
					const prev = points[(i - 1 + points.length) % points.length];
					const current = points[i];
					const next = points[(i + 1) % points.length];
					const dx1 = current[0] - prev[0];
					const dy1 = current[1] - prev[1];
					const dx2 = next[0] - current[0];
					const dy2 = next[1] - current[1];
					const cross = Math.abs((dx1 * dy2) - (dy1 * dx2));
					if (cross <= tolerance) {
						continue;
					}
					cleaned.push(current);
				}
				return cleaned.length >= 3 ? cleaned : points;
			};

			const pointToSegmentDistance = function (point, start, end) {
				const vx = end[0] - start[0];
				const vy = end[1] - start[1];
				const wx = point[0] - start[0];
				const wy = point[1] - start[1];
				const c1 = (vx * wx) + (vy * wy);
				if (c1 <= 0) {
					return Math.hypot(point[0] - start[0], point[1] - start[1]);
				}
				const c2 = (vx * vx) + (vy * vy);
				if (c2 <= c1) {
					return Math.hypot(point[0] - end[0], point[1] - end[1]);
				}
				const t = c1 / c2;
				const projX = start[0] + (t * vx);
				const projY = start[1] + (t * vy);
				return Math.hypot(point[0] - projX, point[1] - projY);
			};

			const simplifyOpenPolyline = function (points, epsilon) {
				if (!points || points.length < 3) {
					return points || [];
				}
				const tolerance = Math.max(0, Number(epsilon) || 0);
				if (!tolerance) {
					return points.slice(0);
				}
				const first = points[0];
				const last = points[points.length - 1];
				let maxDist = -1;
				let idx = -1;
				for (let i = 1; i < points.length - 1; i += 1) {
					const dist = pointToSegmentDistance(points[i], first, last);
					if (dist > maxDist) {
						maxDist = dist;
						idx = i;
					}
				}
				if (maxDist <= tolerance || idx < 0) {
					return [first, last];
				}
				const left = simplifyOpenPolyline(points.slice(0, idx + 1), tolerance);
				const right = simplifyOpenPolyline(points.slice(idx), tolerance);
				return left.slice(0, -1).concat(right);
			};

			const simplifyClosedLoop = function (points, epsilon) {
				if (!points || points.length < 4) {
					return points || [];
				}
				const tolerance = Math.max(0, Number(epsilon) || 0);
				if (!tolerance) {
					return points.slice(0);
				}
				const polyline = points.concat([points[0]]);
				const simplified = simplifyOpenPolyline(polyline, tolerance);
				const reopened = simplified.slice(0, -1);
				return reopened.length >= 3 ? reopened : points;
			};

			const smoothClosedLoopPath = function (points) {
				if (!points || points.length < 3) {
					return '';
				}
				const cornerScale = clamp(Number(state.analysisSettings.potraceAlphamax), 0, 1.334) * 0.375;
				const maxRadius = 0.45;
				const entries = [];
				const exits = [];
				for (let i = 0; i < points.length; i += 1) {
					const prev = points[(i - 1 + points.length) % points.length];
					const current = points[i];
					const next = points[(i + 1) % points.length];
					const inDx = current[0] - prev[0];
					const inDy = current[1] - prev[1];
					const outDx = next[0] - current[0];
					const outDy = next[1] - current[1];
					const inLen = Math.hypot(inDx, inDy);
					const outLen = Math.hypot(outDx, outDy);
					if (!inLen || !outLen) {
						entries.push([current[0], current[1]]);
						exits.push([current[0], current[1]]);
						continue;
					}
					const radius = Math.min(maxRadius, inLen * cornerScale, outLen * cornerScale);
					entries.push([
						current[0] - ((inDx / inLen) * radius),
						current[1] - ((inDy / inLen) * radius),
					]);
					exits.push([
						current[0] + ((outDx / outLen) * radius),
						current[1] + ((outDy / outLen) * radius),
					]);
				}

				const commands = [];
				commands.push('M' + entries[0][0].toFixed(3) + ' ' + entries[0][1].toFixed(3));
				for (let i = 0; i < points.length; i += 1) {
					const corner = points[i];
					const exit = exits[i];
					const nextEntry = entries[(i + 1) % points.length];
					commands.push('Q' + corner[0].toFixed(3) + ' ' + corner[1].toFixed(3) + ' ' + exit[0].toFixed(3) + ' ' + exit[1].toFixed(3));
					commands.push('L' + nextEntry[0].toFixed(3) + ' ' + nextEntry[1].toFixed(3));
				}
				commands.push('Z');
				return commands.join('');
			};

			const buildLoopsForLabel = function (targetLabel, mask) {
				const outgoing = new Map();
				const edges = [];
				const speckleThreshold = Math.max(0, parseInt(state.analysisSettings.potraceTurdsize, 10) || potraceTurdsize);
				const optimizeTolerance = Math.max(0, Number(state.analysisSettings.potraceOpttolerance));
				const simplifyTolerance = optimizeTolerance;
				const addEdge = function (x1, y1, x2, y2) {
					const edge = { start: toKey(x1, y1), end: toKey(x2, y2), used: false };
					edges.push(edge);
					if (!outgoing.has(edge.start)) {
						outgoing.set(edge.start, []);
					}
					outgoing.get(edge.start).push(edge);
				};

				for (let y = 0; y < height; y += 1) {
					for (let x = 0; x < width; x += 1) {
						if (!hasMaskPixel(x, y, mask)) {
							continue;
						}
						if (!hasMaskPixel(x, y - 1, mask)) {
							addEdge(x, y, x + 1, y);
						}
						if (!hasMaskPixel(x + 1, y, mask)) {
							addEdge(x + 1, y, x + 1, y + 1);
						}
						if (!hasMaskPixel(x, y + 1, mask)) {
							addEdge(x + 1, y + 1, x, y + 1);
						}
						if (!hasMaskPixel(x - 1, y, mask)) {
							addEdge(x, y + 1, x, y);
						}
					}
				}

				const loops = [];
				edges.forEach(function (edge) {
					if (edge.used) {
						return;
					}
					const loop = [];
					let current = edge;
					const loopStart = edge.start;
					while (current && !current.used) {
						current.used = true;
						loop.push(parseKey(current.start));
						if (current.end === loopStart) {
							break;
						}
						const nextCandidates = outgoing.get(current.end) || [];
						let nextEdge = null;
						for (let i = 0; i < nextCandidates.length; i += 1) {
							if (!nextCandidates[i].used) {
								nextEdge = nextCandidates[i];
								break;
							}
						}
						current = nextEdge;
					}
					if (loop.length >= 3) {
						const simplified = simplifyClosedLoop(removeCollinearPoints(loop, optimizeTolerance), simplifyTolerance);
						if (polygonArea(simplified) >= speckleThreshold) {
							loops.push(simplified);
						}
					}
				});
				return loops;
			};

			const paths = [];
			for (let i = 0; i < palette.length; i += 1) {
				const binaryMask = createBinaryMaskForLabel(i);
				const loops = buildLoopsForLabel(i, binaryMask);
				if (!loops.length) {
					continue;
				}
				const useOpticurve = state.analysisSettings.potraceOpticurve !== false;
				const loopPaths = loops.map(function (loop) {
					if (useOpticurve) {
						return smoothClosedLoopPath(loop);
					}
					return 'M' + loop.map(function (point, pointIndex) {
						return (pointIndex ? 'L' : '') + point[0].toFixed(3) + ' ' + point[1].toFixed(3);
					}).join('') + 'Z';
				}).filter(Boolean);
				if (!loopPaths.length) {
					continue;
				}
				paths.push('<path fill="' + palette[i] + '" d="' + loopPaths.join('') + '" fill-rule="evenodd"/>');
			}
			return paths.join('');
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

			const vectorPaths = buildVectorPathByColor(state.labels, state.sourcePixels, state.width, state.height, state.palette, previewVectorMaxPixels, state.analysisSettings);
			if (vectorPaths && previewVector.length) {
				previewVector.attr('viewBox', '0 0 ' + state.width + ' ' + state.height);
				previewVector.html(vectorPaths);
				previewContainer.attr('data-threaddesk-preview-mode', 'quantized-vector');
			} else {
				previewContainer.attr('data-threaddesk-preview-mode', 'quantized');
			}
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

		const createQuantizationPixels = function (sourcePixels, width, height, useSmooth) {
			if (!sourcePixels || !width || !height) {
				return { pixels: [], opaqueIndices: [] };
			}
			const working = useSmooth ? blurRgbaSource(sourcePixels, width, height) : sourcePixels;
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
			const safeWidth = Math.max(1, parseInt(width, 10) || 1);
			const safeHeight = Math.max(1, parseInt(height, 10) || 1);
			if (!labels || !sourcePixels || !paletteHex || !paletteHex.length) {
				return '';
			}
			if ((safeWidth * safeHeight) > 160000) {
				return '';
			}

			const rects = [];
			for (let y = 0; y < safeHeight; y += 1) {
				let runStart = 0;
				let runLabel = -1;
				let runAlpha = -1;
				for (let x = 0; x <= safeWidth; x += 1) {
					let label = -1;
					let alpha = 0;
					if (x < safeWidth) {
						const pixelIndex = (y * safeWidth) + x;
						const offset = pixelIndex * 4;
						alpha = sourcePixels[offset + 3] || 0;
						if (alpha >= 8) {
							label = labels[pixelIndex] || 0;
						}
					}
					const shouldFlush = x === safeWidth || label !== runLabel || alpha !== runAlpha;
					if (!shouldFlush) {
						continue;
					}
					if (runLabel >= 0 && runAlpha >= 8) {
						const color = paletteHex[runLabel] || paletteHex[0] || '#111111';
						const runWidth = x - runStart;
						if (runAlpha >= 254) {
							rects.push('<rect x="' + runStart + '" y="' + y + '" width="' + runWidth + '" height="1" fill="' + color + '"/>');
						} else {
							rects.push('<rect x="' + runStart + '" y="' + y + '" width="' + runWidth + '" height="1" fill="' + color + '" fill-opacity="' + (runAlpha / 255).toFixed(3) + '"/>');
						}
					}
					runStart = x;
					runLabel = label;
					runAlpha = alpha;
				}
			}
			if (!rects.length) {
				return '';
			}
			const svgMarkup = '<svg xmlns="http://www.w3.org/2000/svg" width="' + safeWidth + '" height="' + safeHeight + '" viewBox="0 0 ' + safeWidth + ' ' + safeHeight + '" shape-rendering="crispEdges">' + rects.join('') + '</svg>';
			return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svgMarkup);
		};

		const buildVectorSvgMarkup = function (labels, sourcePixels, width, height, palette, maxPixels, vectorSettings) {
			const vectorPaths = buildVectorPathByColor(labels, sourcePixels, width, height, palette, maxPixels, vectorSettings);
			if (!vectorPaths) {
				return '';
			}
			return '<svg xmlns="http://www.w3.org/2000/svg" width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '" shape-rendering="crispEdges">' + vectorPaths + '</svg>';
		};

		const buildRectVectorSvgMarkup = function (labels, sourcePixels, width, height, palette, maxPixels) {
			const safeWidth = Math.max(1, parseInt(width, 10) || 1);
			const safeHeight = Math.max(1, parseInt(height, 10) || 1);
			const pixelLimit = Math.max(1, parseInt(maxPixels, 10) || exportVectorMaxPixels);
			if (!labels || !sourcePixels || !palette || !palette.length || (safeWidth * safeHeight) > pixelLimit) {
				return '';
			}
			const rects = [];
			for (let y = 0; y < safeHeight; y += 1) {
				let runStart = 0;
				let runLabel = -1;
				let runAlpha = -1;
				for (let x = 0; x <= safeWidth; x += 1) {
					let label = -1;
					let alpha = 0;
					if (x < safeWidth) {
						const pixelIndex = (y * safeWidth) + x;
						const offset = pixelIndex * 4;
						alpha = sourcePixels[offset + 3] || 0;
						if (alpha >= 8) {
							label = labels[pixelIndex] || 0;
						}
					}
					const flush = x === safeWidth || label !== runLabel || alpha !== runAlpha;
					if (!flush) {
						continue;
					}
					if (runLabel >= 0 && runAlpha >= 8) {
						const color = palette[runLabel] || palette[0] || '#111111';
						const runWidth = x - runStart;
						if (runAlpha >= 254) {
							rects.push('<rect x="' + runStart + '" y="' + y + '" width="' + runWidth + '" height="1" fill="' + color + '"/>');
						} else {
							rects.push('<rect x="' + runStart + '" y="' + y + '" width="' + runWidth + '" height="1" fill="' + color + '" fill-opacity="' + (runAlpha / 255).toFixed(3) + '"/>');
						}
					}
					runStart = x;
					runLabel = label;
					runAlpha = alpha;
				}
			}
			if (!rects.length) {
				return '';
			}
			return '<svg xmlns="http://www.w3.org/2000/svg" width="' + safeWidth + '" height="' + safeHeight + '" viewBox="0 0 ' + safeWidth + ' ' + safeHeight + '" shape-rendering="crispEdges">' + rects.join('') + '</svg>';
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
			const maxColors = clamp(parseInt(settings.maximumColorCount, 10) || normalizedPalette.length || 4, 1, maxSwatches);
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
				settings.multiScanSmooth === true
			);
			if (!quantSource.pixels.length) {
				return '';
			}
			const quantized = quantizeColors(quantSource.pixels, quantSource.opaqueIndices, analysis.width * analysis.height, maxColors);
			if (!quantized || !quantized.labels) {
				return '';
			}
			const pathSvg = buildVectorSvgMarkup(quantized.labels, analysis.imageData.data, analysis.width, analysis.height, normalizedPalette, previewVectorMaxPixels, settings);
			if (pathSvg) {
				return pathSvg;
			}
			return buildRectVectorSvgMarkup(quantized.labels, analysis.imageData.data, analysis.width, analysis.height, normalizedPalette, previewVectorMaxPixels * 2);
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
			const maxColors = clamp(parseInt(settings.maximumColorCount, 10) || normalizedPalette.length || 4, 1, maxSwatches);
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

			const analysis = createAnalysisBuffer(image, svgDimensions, { maxDimension: designCardMaxDimension });
			const quantSource = createQuantizationPixels(
				analysis.imageData.data,
				analysis.width,
				analysis.height,
				settings.multiScanSmooth === true
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
			const quantSource = createQuantizationPixels(
				state.sourcePixels,
				state.width,
				state.height,
				state.analysisSettings.multiScanSmooth === true
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
			openDesignModal();
			designIdField.val('0');
			state.hasUserAdjustedMax = false;
			const designFileInput = designModal.find('[data-threaddesk-design-file]').get(0);
			if (!designFileInput) {
				return;
			}
			try {
				designFileInput.click();
			} catch (error) {
				setStatus('Unable to open file picker. Please click the chooser again.');
			}
		};

		$('[data-threaddesk-design-open]').on('click', function (event) {
			event.preventDefault();
			openAndPromptDesignUpload();
		});


		$(document).on('click', '[data-threaddesk-design-edit]', async function (event) {
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
				await loadCanvasFromPreviewUrl(previewUrl);
			}
			let palette = [];
			let settings = {};
			try { palette = JSON.parse(paletteRaw); } catch (e) {}
			try { settings = JSON.parse(settingsRaw); } catch (e) {}
			state.palette = normalizePaletteToAllowed(Array.isArray(palette) && palette.length ? palette : defaultPalette.slice(0, 4));
			state.analysisSettings.maximumColorCount = clamp(parseInt(settings.maximumColorCount, 10) || state.palette.length || 4, 1, maxSwatches);
			state.analysisSettings.potraceTurdsize = Math.max(0, parseInt(settings.potraceTurdsize, 10) || parseInt(settings.traceSpeckles, 10) || potraceTurdsize);
			state.analysisSettings.potraceAlphamax = clamp(Number(settings.potraceAlphamax), 0, 1.334);
			if (!Number.isFinite(state.analysisSettings.potraceAlphamax)) {
				state.analysisSettings.potraceAlphamax = clamp(Number(settings.traceSmoothCorners), 0, 1.334);
			}
			if (!Number.isFinite(state.analysisSettings.potraceAlphamax)) {
				state.analysisSettings.potraceAlphamax = potraceAlphamax;
			}
			state.analysisSettings.potraceOpticurve = settings.potraceOpticurve !== false;
			state.analysisSettings.potraceOpttolerance = Math.max(0, Number(settings.potraceOpttolerance));
			if (!Number.isFinite(state.analysisSettings.potraceOpttolerance)) {
				state.analysisSettings.potraceOpttolerance = Math.max(0, Number(settings.traceOptimize));
			}
			if (!Number.isFinite(state.analysisSettings.potraceOpttolerance)) {
				state.analysisSettings.potraceOpttolerance = potraceOpttolerance;
			}
			state.analysisSettings.multiScanSmooth = settings.multiScanSmooth === true;
			state.analysisSettings.multiScanStack = settings.multiScanStack !== false;
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
				setStatus('Unsupported file type. Please upload PNG or JPG.');
				return;
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
			const cardImage = trigger.closest('.threaddesk__card').find('.threaddesk__card-design-preview img').get(0);
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


		if (designForm.length) {
			designForm.on('submit', function () {
				const svgField = designForm.find('[data-threaddesk-design-svg-markup]');
				if (!svgField.length) {
					return;
				}
				let svgMarkup = '';
				if (state.labels && state.sourcePixels && state.palette.length && state.width && state.height) {
					svgMarkup = buildVectorSvgMarkup(state.labels, state.sourcePixels, state.width, state.height, state.palette, exportVectorMaxPixels, state.analysisSettings);
					if (!svgMarkup) {
						svgMarkup = buildRectVectorSvgMarkup(state.labels, state.sourcePixels, state.width, state.height, state.palette, exportVectorMaxPixels * 2);
					}
				}
				svgField.val(svgMarkup || '');
			});
		}

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
		renderColorSwatches();
		renderVectorFallback();
		persistDesignMetadata();
	}
});
