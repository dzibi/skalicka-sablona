/* Skalička: cookie lišta, měření po souhlasu a vlastní statistika bez cookies. */
(function () {
	'use strict';

	var T = window.SKALICKA_TRACK || {};
	var KEY = 'sk_souhlas';
	var MAX_AGE = 180 * 24 * 3600; // 6 měsíců
	var loggedIn = document.body && document.body.classList.contains('logged-in');

	/* ---------- Souhlas ---------- */
	function readConsent() {
		var m = document.cookie.match(/(?:^|; )sk_souhlas=([^;]*)/);
		if (!m) { return null; }
		try { return JSON.parse(decodeURIComponent(m[1])); } catch (e) { return null; }
	}
	function saveConsent(c) {
		c.v = 2; c.d = new Date().toISOString().slice(0, 10);
		document.cookie = KEY + '=' + encodeURIComponent(JSON.stringify(c)) + '; max-age=' + MAX_AGE + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
	}
	function hasTools() { return !!(T.ga || T.pixel || T.clarity); }

	/* ---------- Načtení nástrojů ---------- */
	var loaded = {};
	function script(src) { var s = document.createElement('script'); s.async = true; s.src = src; document.head.appendChild(s); }

	function loadGA() {
		if (!T.ga || loaded.ga) { return; } loaded.ga = true;
		window.dataLayer = window.dataLayer || [];
		window.gtag = function () { window.dataLayer.push(arguments); };
		window.gtag('js', new Date());
		window.gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'granted' });
		window.gtag('config', T.ga, { anonymize_ip: true });
		script('https://www.googletagmanager.com/gtag/js?id=' + T.ga);
	}
	function loadClarity() {
		if (!T.clarity || loaded.clarity) { return; } loaded.clarity = true;
		(function (c, l, a, r, i) {
			c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
			var t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
			var y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
		})(window, document, 'clarity', 'script', T.clarity);
	}
	function loadPixel() {
		if (!T.pixel || loaded.pixel) { return; } loaded.pixel = true;
		/* eslint-disable */
		!function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
		/* eslint-enable */
		window.fbq('init', T.pixel);
		window.fbq('track', 'PageView');
		if (/\/clenstvi\/dekujeme\/?$/.test(location.pathname)) { window.fbq('track', 'Purchase', { value: 549, currency: 'CZK' }); }
	}
	function apply(c) {
		if (!c) { return; }
		if (c.a) { loadGA(); loadClarity(); }
		if (c.m) {
			loadPixel();
			if (window.gtag) { window.gtag('consent', 'update', { ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted' }); }
		}
	}

	/* ---------- Lišta ---------- */
	function bar(showSettings) {
		var old = document.getElementById('sk-cookie'); if (old) { old.remove(); }
		var c = readConsent() || { a: false, m: false };
		var el = document.createElement('div');
		el.id = 'sk-cookie'; el.className = 'sk-cookie'; el.setAttribute('role', 'dialog'); el.setAttribute('aria-label', 'Nastavení cookies');
		el.innerHTML =
			'<div class="sk-cookie-in">' +
			'<p class="sk-cookie-title">Cookies</p>' +
			'<p>Rádi bychom věděli, co na webu hledáte' + (T.pixel ? ', a mohli vám ukazovat naše akce na Facebooku a Instagramu' : ', abychom ho mohli vylepšovat') + '. K tomu potřebujeme váš souhlas s cookies. <a href="' + (T.policy || '/cookies/') + '">Víc o cookies</a></p>' +
			'<div class="sk-cookie-set"' + (showSettings ? '' : ' hidden') + '>' +
			'<label><input type="checkbox" checked disabled> Nezbytné (web bez nich nefunguje)</label>' +
			'<label><input type="checkbox" id="sk-c-a"' + (c.a ? ' checked' : '') + '> Analytické (' + (T.clarity ? 'Google Analytics, Microsoft Clarity' : 'Google Analytics') + ')</label>' +
			(T.pixel ? '<label><input type="checkbox" id="sk-c-m"' + (c.m ? ' checked' : '') + '> Marketingové (Meta Pixel)</label>' : '') +
			'</div>' +
			'<div class="sk-cookie-btns">' +
			'<button type="button" class="sk-btn sk-btn--zluta" data-c="all">Přijmout vše</button>' +
			'<button type="button" class="sk-btn sk-btn--svetla" data-c="none">Jen nezbytné</button>' +
			'<button type="button" class="sk-btn sk-btn--svetla" data-c="' + (showSettings ? 'save' : 'set') + '">' + (showSettings ? 'Uložit výběr' : 'Nastavení') + '</button>' +
			'</div></div>';
		document.body.appendChild(el);
		el.addEventListener('click', function (e) {
			var b = e.target.closest('button[data-c]'); if (!b) { return; }
			var k = b.getAttribute('data-c');
			if (k === 'set') { bar(true); return; }
			var n = k === 'all' ? { a: true, m: true } : k === 'none' ? { a: false, m: false } : { a: !!document.getElementById('sk-c-a').checked, m: !!(document.getElementById('sk-c-m') && document.getElementById('sk-c-m').checked) };
			var prev = readConsent();
			saveConsent(n);
			el.remove();
			// Odvolání souhlasu: už načtené nástroje vypne až nové načtení stránky.
			if (prev && ((prev.a && !n.a) || (prev.m && !n.m))) { location.reload(); return; }
			apply(n);
		});
	}

	/* ---------- Vlastní statistika bez cookies ---------- */
	function beacon(data) {
		if (loggedIn || !T.stats) { return; }
		var body = JSON.stringify(data);
		if (navigator.sendBeacon) { navigator.sendBeacon(T.stats, new Blob([body], { type: 'application/json' })); }
		else if (window.fetch) { fetch(T.stats, { method: 'POST', body: body, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'omit' }); }
	}

	function clickKind(a) {
		var h = a.getAttribute('href') || '';
		if (/reenio\./i.test(h)) { return /pohybovk/i.test(h) ? 'rezervace_pohybovky' : (/oslav|party|narozen/i.test(h) ? 'rezervace_oslavy' : 'rezervace_jine'); }
		if (/buy\.stripe\.com/i.test(h)) { return 'clenstvi_nakup'; }
		if (/billing\.stripe\.com/i.test(h)) { return 'clenstvi_portal'; }
		if (/^tel:/i.test(h)) { return 'telefon'; }
		if (/^mailto:/i.test(h)) { return 'email'; }
		if (/google\.[a-z.]+\/maps|maps\.app\.goo|mapy\.cz/i.test(h)) { return 'navigace'; }
		if (/instagram\.com/i.test(h)) { return 'instagram'; }
		if (/facebook\.com/i.test(h)) { return 'facebook'; }
		if (/kudyznudy\.cz/i.test(h)) { return 'kudyznudy'; }
		return '';
	}

	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a[href]') : null;
		if (a) {
			var k = clickKind(a);
			if (k) {
				beacon({ t: 'click', k: k });
				if (window.gtag && loaded.ga) { window.gtag('event', k, { link_url: a.href, link_text: (a.textContent || '').trim().slice(0, 60) }); }
				if (window.fbq && loaded.pixel) {
					if (k === 'clenstvi_nakup') { window.fbq('track', 'InitiateCheckout'); }
					else if (k.indexOf('rezervace') === 0) { window.fbq('track', 'Schedule'); }
					else if (k === 'telefon' || k === 'navigace') { window.fbq('track', 'Contact'); }
				}
			}
		}
		var any = e.target.closest ? e.target.closest('a[href],button') : null;
		if (any && !any.closest('#sk-cookie')) {
			var l = (any.getAttribute('aria-label') || any.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 50);
			if (!l && any.getAttribute('href')) { l = any.getAttribute('href').slice(0, 50); }
			if (l) { beacon({ t: 'ck', p: location.pathname, l: l }); }
		}
		var open = e.target.closest ? e.target.closest('[data-sk-cookies]') : null;
		if (open) { e.preventDefault(); bar(true); }
	});

	function utm() {
		try {
			var q = new URLSearchParams(location.search), src = q.get('utm_source');
			return src ? (src + ' / ' + (q.get('utm_campaign') || q.get('utm_medium') || '-')).slice(0, 80) : '';
		} catch (e) { return ''; }
	}

	/* Čas, kdy je stránka opravdu vidět, a jak daleko ji člověk dočetl. Odesílá se jednou, při odchodu. */
	var visMs = 0, visFrom = document.visibilityState === 'visible' ? Date.now() : 0, maxScroll = 0, tmSent = false;
	function scrollPct() {
		var h = document.documentElement, max = Math.max(1, h.scrollHeight - window.innerHeight);
		return Math.min(100, Math.round((window.scrollY || h.scrollTop || 0) / max * 100));
	}
	function sendTime() {
		if (visFrom) { visMs += Date.now() - visFrom; visFrom = 0; }
		if (tmSent) { return; }
		var sec = Math.round(visMs / 1000);
		if (sec < 1) { return; }
		tmSent = true;
		if (document.documentElement.scrollHeight <= window.innerHeight + 50) { maxScroll = 100; }
		beacon({ t: 'tm', p: location.pathname, s: Math.min(sec, 1800), d: maxScroll });
	}
	window.addEventListener('scroll', function () { var p = scrollPct(); if (p > maxScroll) { maxScroll = p; } }, { passive: true });
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'visible') { visFrom = Date.now(); } else { sendTime(); }
	});
	window.addEventListener('pagehide', sendTime);

	function start() {
		beacon({ t: 'pv', p: location.pathname, r: document.referrer || '', m: window.matchMedia && window.matchMedia('(max-width: 760px)').matches ? 1 : 0, u: utm() });
		var c = readConsent();
		if (c) {
			apply(c);
			// Souhlas z doby před Meta Pixelem (v1): zeptat se znovu, ať může souhlasit i s marketingem.
			if (T.pixel && (c.v || 1) < 2) { bar(false); }
		}
		else if (hasTools()) { bar(false); }
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
