/**
 * Rodeo Vault Product Filter
 * iOS Safari safe: no :has(), abortable requests with timeout, loader always cleared,
 * keyboard blurred before apply, scroll lock released after the off-canvas closes.
 */
(function () {
	'use strict';

	var CFG = window.CPF_CONFIG || {};
	var T = CFG.i18n || {};
	var VISIBLE = 5;

	function $all(root, sel) {
		return Array.prototype.slice.call(root.querySelectorAll(sel));
	}

	function parseJSON(str, fallback) {
		try { return JSON.parse(str); } catch (e) { return fallback; }
	}

	/* ---------- Page-level helpers ---------- */

	function blurActive() {
		var el = document.activeElement;
		if (el && el !== document.body && typeof el.blur === 'function') {
			el.blur();
		}
	}

	/** Some off-canvas/popup scripts leave overflow/position locks on <html>/<body>; iOS then cannot scroll. */
	function releaseScrollLock() {
		var anyOpen = (CFG.offcanvas || []).some(function (sel) {
			var el = document.querySelector(sel);
			return el && el.getAttribute('aria-hidden') === 'false';
		});

		if (anyOpen) { return; }

		var body = document.body;
		var restoreY = body.style.position === 'fixed' ? -parseInt(body.style.top || '0', 10) : null;

		[document.documentElement, body].forEach(function (el) {
			['overflow', 'position', 'top', 'left', 'right', 'width', 'height', 'touchAction']
				.forEach(function (prop) { el.style[prop] = ''; });
		});

		if (restoreY) { window.scrollTo(0, restoreY); }
	}

	/**
	 * Close the Elementor off-canvas the NATIVE way (its own close link / overlay).
	 * Only setting aria-hidden leaves Elementor's state (inert, focus trap, scroll lock)
	 * half-open, which freezes iOS Safari.
	 */
	function closePanels() {
		(CFG.offcanvas || []).forEach(function (sel) {
			var panel = document.querySelector(sel);

			if (!panel || panel.getAttribute('aria-hidden') === 'true') { return; }

			var link = panel.querySelector('a[href*="off_canvas%3Aclose"], a[href*="off_canvas:close"]');
			var overlay = panel.querySelector('.e-off-canvas__overlay');

			if (link) {
				link.click();
			} else if (overlay) {
				overlay.click();
			} else {
				panel.setAttribute('aria-hidden', 'true');
				panel.setAttribute('inert', '');
			}
		});

		// Safety net after the close animation: never leave the page scroll-locked.
		setTimeout(releaseScrollLock, 700);
	}

	/* ---------- Filter instance ---------- */

	function init(filter) {
		if (filter.dataset.cpfInit) { return; }
		filter.dataset.cpfInit = '1';

		var attributes = filter.querySelector('.cpf-dynamic-attributes');
		var status = filter.querySelector('.cpf-status');
		var minInput = filter.querySelector('.cpf-min');
		var maxInput = filter.querySelector('.cpf-max');
		var applyBtn = filter.querySelector('.cpf-apply');
		var clearBtn = filter.querySelector('.cpf-clear');
		var seq = 0;           // ignore stale responses
		var controller = null; // abort previous request

		function setStatus(text) { if (status) { status.textContent = text || ''; } }

		function getCategory() {
			var el = filter.querySelector('.cpf-category:checked');
			return el ? el.value : '0';
		}

		function selectedTerms() {
			var result = {};
			$all(filter, '.cpf-term:checked').forEach(function (input) {
				var tax = input.dataset.taxonomy;
				(result[tax] = result[tax] || []).push(input.value);
			});
			return result;
		}

		/* ----- View more ----- */
		function setupViewMore() {
			$all(filter, '.cpf-options').forEach(function (options) {
				var old = options.querySelector('.cpf-view-more');
				if (old) { old.remove(); }

				var items = Array.prototype.filter.call(options.children, function (el) {
					return el.classList.contains('cpf-option');
				});

				items.forEach(function (el) { el.classList.remove('cpf-hidden'); });

				if (items.length <= VISIBLE) { return; }

				items.forEach(function (el, i) { if (i >= VISIBLE) { el.classList.add('cpf-hidden'); } });

				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'cpf-view-more';
				btn.textContent = T.viewMore;
				btn.addEventListener('click', function () {
					var expanded = btn.dataset.expanded === '1';
					items.forEach(function (el, i) { if (i >= VISIBLE) { el.classList.toggle('cpf-hidden', expanded); } });
					btn.dataset.expanded = expanded ? '0' : '1';
					btn.textContent = expanded ? T.viewMore : T.viewLess;
				});
				options.appendChild(btn);
			});
		}

		/* ----- URL ----- */
		function updateURL(payload) {
			try {
				var url = new URL(window.location.href);
				var pathMode = filter.dataset.pathMode === '1';
				var shopPath = filter.dataset.shopPath || '/shop/';
				var catPaths = parseJSON(filter.dataset.categoryPaths, {});
				var catPath = payload.category !== '0' ? catPaths[payload.category] : '';
				var pathMap = CFG.pathMap || [];

				['wpf_filter_brand', 'wpf_fbv', 'category', 'min_price', 'max_price', 'jewelry_type']
					.concat(pathMap.map(function (p) { return p[1]; }))
					.forEach(function (key) { url.searchParams.delete(key); });

				if (pathMode) { url.pathname = catPath || shopPath; }

				pathMap.forEach(function (pair) {
					var values = payload.terms[pair[1]];
					if (!values || !values.length) { return; }

					if (pathMode) {
						url.pathname += pair[0] + '/' + values.map(encodeURIComponent).join(',') + '/';
					} else if (pair[1] === 'pa_brand') {
						url.searchParams.set('wpf_filter_brand', values.join(','));
						url.searchParams.set('wpf_fbv', '1');
					} else {
						url.searchParams.set(pair[1], values.join(','));
					}
				});

				if (payload.category !== '0' && !(pathMode && catPath)) {
					url.searchParams.set('category', payload.category);
				}
				if (payload.min_price !== '') { url.searchParams.set('min_price', payload.min_price); }
				if (payload.max_price !== '') { url.searchParams.set('max_price', payload.max_price); }

				window.history.replaceState({}, '', url.toString());
			} catch (e) { /* Safari may throw SecurityError; the URL is cosmetic */ }
		}

		/* ----- Request ----- */
		function request(action, data) {
			if (controller) { controller.abort(); }
			controller = ('AbortController' in window) ? new AbortController() : null;

			var timedOut = false;
			var timer = setTimeout(function () {
				timedOut = true;
				if (controller) { controller.abort(); }
			}, CFG.timeout || 25000);

			var body = new FormData();
			body.append('action', action);

			Object.keys(data).forEach(function (key) {
				if (Array.isArray(data[key])) {
					data[key].forEach(function (v) { body.append(key + '[]', v); });
				} else {
					body.append(key, data[key]);
				}
			});

			return fetch(CFG.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
				signal: controller ? controller.signal : undefined
			})
				.then(function (r) { return r.text(); })
				.then(function (text) {
					clearTimeout(timer);
					var json = parseJSON(text, null);
					if (!json) { throw new Error(T.error); }
					return json;
				})
				.catch(function (err) {
					clearTimeout(timer);
					if (err && err.name === 'AbortError') {
						if (timedOut) { throw new Error(T.timeout); }
						err.cpfAborted = true;
					}
					throw err;
				});
		}

		/* ----- Product loop ----- */
		function getLoopContainer(loop) {
			var container = loop.querySelector('.elementor-loop-container');
			if (container) { return container; }

			container = document.createElement('div');
			container.className = 'elementor-loop-container elementor-grid';
			var wrap = loop.querySelector('.elementor-widget-container') || loop;
			wrap.innerHTML = '';
			wrap.appendChild(container);
			return container;
		}

		function showLoader(loop) {
			getLoopContainer(loop);
			if (!loop.querySelector('.cpf-loading-text')) {
				var text = document.createElement('div');
				text.className = 'cpf-loading-text';
				text.textContent = T.loading;
				loop.appendChild(text);
			}
			loop.classList.add('cpf-loop-loading');
		}

		function updateProducts() {
			var loop = document.querySelector(CFG.loopSelector || '#product_loop');

			if (!loop) { setStatus(T.noLoop); return; }

			var mySeq = ++seq;
			var terms = selectedTerms();
			var payload = {
				category: getCategory(),
				min_price: minInput.value.trim(),
				max_price: maxInput.value.trim(),
				terms: terms
			};

			var data = { category: payload.category, min_price: payload.min_price, max_price: payload.max_price };
			Object.keys(terms).forEach(function (tax) { data[tax] = terms[tax]; });

			updateURL(payload);
			showLoader(loop);
			filter.classList.add('cpf-loading');
			setStatus(T.updating);

			request('cpf_filter_products', data)
				.then(function (response) {
					if (mySeq !== seq) { return; }
					if (!response.success) {
						throw new Error(response.data && response.data.message ? response.data.message : T.error);
					}
					var container = getLoopContainer(loop);
					container.innerHTML = response.data.html || '<p class="cpf-no-products">' + T.none + '</p>';
					setStatus(response.data.count + ' ' + T.found);
				})
				.catch(function (err) {
					if (err && err.cpfAborted) { return; }
					if (mySeq === seq) { setStatus(err && err.message ? err.message : T.error); }
				})
				.then(function () {
					// Runs on success AND failure: the loader can never stay stuck.
					if (mySeq === seq) {
						filter.classList.remove('cpf-loading');
						loop.classList.remove('cpf-loop-loading');
					}
				});
		}

		function updateAttributes() {
			attributes.innerHTML = '<p>' + T.filters + '</p>';
			filter.classList.add('cpf-loading');

			request('cpf_get_attributes', { category: getCategory() })
				.then(function (response) {
					if (!response.success) { throw new Error(T.error); }
					attributes.innerHTML = response.data.brand + response.data.size + (response.data.extra || '');
					setupViewMore();
					updateProducts();
				})
				.catch(function (err) {
					if (err && err.cpfAborted) { return; }
					attributes.innerHTML = '';
					setStatus(err && err.message ? err.message : T.error);
				})
				.then(function () { filter.classList.remove('cpf-loading'); });
		}

		/* ----- Events ----- */
		$all(filter, '.cpf-category').forEach(function (input) {
			input.addEventListener('change', function () {
				var paths = parseJSON(filter.dataset.categoryPaths, {});
				if (input.value !== '0' && paths[input.value]) {
					window.location.href = paths[input.value];
				} else if (input.value === '0') {
					window.location.href = filter.dataset.shopPath || '/shop/';
				}
			});
		});

		applyBtn.addEventListener('click', function () {
			blurActive();
			updateProducts();
			closePanels();
		});

		if (CFG.closeSelector) {
			$all(document, CFG.closeSelector).forEach(function (btn) {
				if (btn.dataset.cpfBound) { return; }
				btn.dataset.cpfBound = '1';
				btn.addEventListener('click', function () {
					blurActive();
					updateProducts();
					setTimeout(releaseScrollLock, 700);
				});
			});
		}

		clearBtn.addEventListener('click', function () {
			minInput.value = '';
			maxInput.value = '';
			$all(filter, '.cpf-term').forEach(function (input) { input.checked = false; });
			var all = filter.querySelector('.cpf-category[value="0"]');
			if (all) { all.checked = true; }
			updateAttributes();
		});

		[minInput, maxInput].forEach(function (input) {
			input.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') { e.preventDefault(); blurActive(); updateProducts(); }
			});
		});

		setupViewMore();

		// Server already rendered filtered products via Query ID "cpf_products"; only fetch if the grid is empty.
		if (filter.dataset.hasFilters === '1') {
			var loop = document.querySelector(CFG.loopSelector || '#product_loop');
			var container = loop ? loop.querySelector('.elementor-loop-container') : null;
			if (!container || !container.children.length) { updateProducts(); }
		}
	}

	function boot() {
		$all(document, '.cpf-filter:not([data-cpf-init])').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// Elementor can inject the widget later (off-canvas / popups).
	window.addEventListener('elementor/frontend/init', boot);
	document.addEventListener('click', function () { setTimeout(boot, 50); }, true);
})();
