/*
 * Style and font scan — paste into the browser console on the all-widgets
 * page (06-all-widgets.php builds it), then call pfhStyleScan().
 *
 * Four things no static check can answer, each of which caught a real bug:
 *
 *   1. fonts    — which face every piece of text actually computes to.
 *                 .pfh-arch { font-family: inherit } loaded after pfh-base
 *                 at equal specificity, so the whole archive rendered in
 *                 the theme's Manrope.
 *   2. tokens   — every var(--pfh-*) used WITHOUT a fallback, checked on the
 *                 elements it styles. An undefined one drops the whole
 *                 declaration silently; three info-widget layout rules were
 *                 dead this way.
 *   3. leaks    — whether a widget's stylesheet reaches another widget's
 *                 markup. pfh-info styled bare .pfh-media-left, which the
 *                 featured sections also use on their own roots.
 *   4. overflow — anything escaping its own root, and whether the page
 *                 scrolls sideways. .pfh-cta__card had aspect-ratio plus
 *                 min-height and no width cap, so it resolved a 1110px
 *                 width inside any narrower container.
 *
 * Must be served from the same host as the stylesheets — cssRules throws on
 * a cross-origin sheet and the scan then silently finds nothing. Browsing
 * 127.0.0.1 while WordPress's address says localhost is exactly that trap.
 */
window.pfhStyleScan = async function () {
	await document.fonts.ready;

	const PLUGIN_FACES = /^(Outfit|Playfair Display)$/;
	const ours = s => s.href && s.href.includes('pfh-bricks-widgets');
	const widgetOf = el =>
		el.closest('.pfh-probe')?.dataset.widget ||
		(el.closest('.pfh-ck') ? 'consent' : el.closest('.pfh-bdg') ? 'badge' : null);

	/* 1. fonts ---------------------------------------------------------- */
	const foreign = [];
	for (const probe of document.querySelectorAll('.pfh-probe, .pfh-ck, .pfh-bdg')) {
		const name = probe.dataset?.widget || probe.className.split(' ')[0];
		for (const el of probe.querySelectorAll('*')) {
			if (!el.offsetParent) continue;
			// .pfh-probe__label is this harness's own caption, not plugin
			// markup — it is deliberately left in the theme's font.
			if (el.closest('.pfh-probe__label')) continue;
			const hasText = [...el.childNodes].some(
				n => n.nodeType === 3 && n.textContent.trim().length > 1
			);
			if (!hasText) continue;
			const face = getComputedStyle(el).fontFamily.split(',')[0].replace(/["']/g, '');
			if (!PLUGIN_FACES.test(face)) foreign.push({ widget: name, face, el: el.className });
		}
	}

	/* 2. tokens --------------------------------------------------------- */
	const re = /var\(\s*(--pfh-[a-z0-9-]+)\s*([,)])/gi;
	const refs = new Map();
	const walk = list => {
		for (const r of list) {
			if (r.cssRules && r.cssRules.length) walk(r.cssRules);
			if (!r.style || !r.selectorText) continue;
			for (const prop of r.style) {
				const val = r.style.getPropertyValue(prop);
				if (!val.includes('var(')) continue;
				re.lastIndex = 0;
				let m;
				while ((m = re.exec(val))) {
					if (m[2] === ',') continue;               // has a fallback
					refs.set(r.selectorText + '' + m[1] + '' + prop, 1);
				}
			}
		}
	};
	let unreadable = 0;
	for (const sheet of document.styleSheets) {
		if (!ours(sheet)) continue;
		try { walk(sheet.cssRules); } catch (e) { unreadable++; }
	}
	const unresolved = [];
	for (const key of refs.keys()) {
		const [sel, name, prop] = key.split('');
		let els;
		try { els = document.querySelectorAll(sel); } catch (e) { continue; }
		for (const el of els) {
			if (getComputedStyle(el).getPropertyValue(name).trim() === '') {
				unresolved.push({ selector: sel, variable: name, property: prop });
				break;
			}
		}
	}

	/* 3. leaks ---------------------------------------------------------- */
	const OWNS = {
		'pfh-header.css': ['pfh-header'], 'pfh-footer.css': ['pfh-footer'],
		'pfh-hero.css': ['pfh-hero'], 'pfh-categories.css': ['pfh-categories'],
		// pfh-recent extends the slider, so the slider's stylesheet is its own.
		'pfh-products.css': ['pfh-products', 'pfh-product-grid', 'pfh-archive', 'pfh-recent'],
		'pfh-pgrid.css': ['pfh-product-grid'], 'pfh-archive.css': ['pfh-archive'],
		'pfh-quickadd.css': ['pfh-products', 'pfh-product-grid', 'pfh-archive', 'pfh-recent'],
		'pfh-shop.css': ['pfh-shophead', 'pfh-shopdesc', 'pfh-notice', 'pfh-counter', 'pfh-faq'],
		'pfh-info.css': ['pfh-info'], 'pfh-rating.css': ['pfh-rating'],
		'pfh-cta.css': ['pfh-cta'], 'pfh-features.css': ['pfh-features'],
		'pfh-reviews.css': ['pfh-reviews'],
		'pfh-featured.css': ['pfh-featured', 'pfh-featured-olive'],
		'pfh-highlight.css': ['pfh-highlight'],
		'pfh-consent.css': ['consent'], 'pfh-badge.css': ['badge'],
	};
	const leaks = [];
	for (const sheet of document.styleSheets) {
		if (!ours(sheet)) continue;
		const file = sheet.href.split('/').pop().split('?')[0];
		const allowed = OWNS[file];
		if (!allowed) continue;                       // pfh-base applies everywhere
		let rules;
		try { rules = sheet.cssRules; } catch (e) { continue; }
		const scan = list => {
			for (const r of list) {
				if (r.cssRules && r.cssRules.length) scan(r.cssRules);
				if (!r.style || !r.selectorText) continue;
				let els;
				try { els = document.querySelectorAll(r.selectorText); } catch (e) { continue; }
				for (const el of els) {
					const w = widgetOf(el);
					if (w && !allowed.includes(w)) {
						leaks.push({ stylesheet: file, selector: r.selectorText, reaches: w });
						return;
					}
				}
			}
		};
		scan(rules);
	}

	/* 4. overflow ------------------------------------------------------- */
	const vw = document.documentElement.clientWidth;
	const escapes = [];
	for (const probe of document.querySelectorAll('.pfh-probe')) {
		const root = probe.querySelector('[class*="pfh-"]:not(.pfh-probe__label)');
		if (!root) continue;
		const right = root.getBoundingClientRect().right;
		for (const el of root.querySelectorAll('*')) {
			if (!el.offsetParent) continue;
			const past = Math.round(el.getBoundingClientRect().right - right);
			if (past <= 2) continue;
			// A marquee or slider track is meant to be wider than its frame.
			let a = el.parentElement, contained = false;
			while (a && a !== document.body) {
				if (/hidden|clip|auto|scroll/.test(getComputedStyle(a).overflowX)) {
					contained = true;
					break;
				}
				a = a.parentElement;
			}
			if (!contained) {
				escapes.push({ widget: probe.dataset.widget, el: el.className.split(' ')[0], past });
				break;
			}
		}
	}

	const report = {
		viewport: vw,
		sheetsUnreadable: unreadable,
		foreignFonts: foreign,
		tokenRefsChecked: refs.size,
		unresolvedTokens: unresolved,
		crossWidgetLeaks: leaks,
		escapesOwnRoot: escapes,
		pageScrollsSideways: document.documentElement.scrollWidth > vw + 1,
	};
	report.pass =
		unreadable === 0 && !foreign.length && !unresolved.length &&
		!leaks.length && !escapes.length && !report.pageScrollsSideways;
	return report;
};

/* The same overflow question at every breakpoint the plugin uses. */
window.pfhBreakpointScan = async function () {
	const widths = [360, 375, 414, 420, 421, 430, 431, 560, 561, 575, 576, 600, 601,
		640, 641, 720, 721, 767, 768, 782, 783, 860, 861, 991, 992, 1023, 1024, 1025,
		1100, 1140, 1199, 1200, 1201, 1366, 1440, 1920];
	const frame = document.createElement('iframe');
	frame.style.cssText = 'position:fixed;left:-99999px;top:0;height:2200px;border:0';
	document.body.appendChild(frame);
	const out = [];
	for (const w of widths) {
		frame.style.width = w + 'px';
		frame.src = location.pathname;
		await new Promise(res => { frame.onload = res; setTimeout(res, 3500); });
		await new Promise(r => setTimeout(r, 250));
		const d = frame.contentDocument;
		const vw = d.documentElement.clientWidth;
		const sw = d.documentElement.scrollWidth;
		out.push({ width: w, overflow: sw > vw + 1 ? sw - vw : 0 });
	}
	frame.remove();
	return { tested: out.length, failures: out.filter(r => r.overflow > 0) };
};
