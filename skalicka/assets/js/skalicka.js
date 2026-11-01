/* Skalička: semafor otevírací doby s živým odpočtem a mobilní menu. */
(function () {
	'use strict';

	var cfg = window.SKALICKA || null;
	var state = null; // poslední spočítaný stav (kvůli odpočtu každou sekundu)

	var DAYS_ACC = ['', 'v pondělí', 'v úterý', 've středu', 've čtvrtek', 'v pátek', 'v sobotu', 'v neděli'];

	// Aktuální čas v Praze: datum, den v týdnu (1 = pondělí) a sekundy od půlnoci.
	function pragueNow() {
		var parts = {};
		new Intl.DateTimeFormat('en-GB', {
			timeZone: 'Europe/Prague', year: 'numeric', month: '2-digit', day: '2-digit',
			weekday: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23'
		}).formatToParts(new Date()).forEach(function (p) { parts[p.type] = p.value; });
		var wd = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6, Sun: 7 }[parts.weekday] || 1;
		var sec = (parseInt(parts.hour, 10) % 24) * 3600 + parseInt(parts.minute, 10) * 60 + parseInt(parts.second, 10);
		return { date: parts.year + '-' + parts.month + '-' + parts.day, wd: wd, sec: sec, min: Math.floor(sec / 60) };
	}

	function parseRange(r) {
		if (!r) { return null; }
		var m = /^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/.exec(r);
		if (!m) { return null; }
		return [parseInt(m[1], 10) * 60 + parseInt(m[2], 10), parseInt(m[3], 10) * 60 + parseInt(m[4], 10)];
	}

	function fmt(min) {
		var h = Math.floor(min / 60), m = min % 60;
		return h + ':' + (m < 10 ? '0' : '') + m;
	}

	function pad(n) { return (n < 10 ? '0' : '') + n; }

	// Odpočet jako digitální hodiny: „02:15:43“, nad 24 h „1 d 02:15:43“.
	function clock(sec) {
		if (sec < 0) { sec = 0; }
		var d = Math.floor(sec / 86400); sec -= d * 86400;
		var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
		return (d ? d + ' d ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
	}

	// Datum Y-m-d posunuté o n dní.
	function addDays(ymd, n) {
		var d = new Date(ymd + 'T12:00:00Z');
		d.setUTCDate(d.getUTCDate() + n);
		return d.toISOString().slice(0, 10);
	}

	// Nejbližší otevření od zítřka dál (dny 1–7) podle otevírací doby a zavírání z kalendáře na 7 dní dopředu.
	function nextOpening(c, now, sameDay) {
		var ahead = c.ahead || {};
		for (var i = 1; i <= 7; i++) {
			var wd = ((now.wd - 1 + i) % 7) + 1;
			var h = parseRange((c.hours || {})[wd]);
			if (!h) { continue; }
			var cl = ahead[addDays(now.date, i)] || [];
			if (i === 1 && sameDay && c.tomorrowClosed && !cl.length) { continue; }
			var start = h[0], moved = true;
			while (moved) {
				moved = false;
				for (var k = 0; k < cl.length; k++) {
					if (cl[k].from <= start && cl[k].to > start) { start = cl[k].to; moved = true; }
				}
			}
			if (start >= h[1]) { continue; }
			var when = i === 1 ? 'zítra' : DAYS_ACC[wd];
			return { atSec: (i * 1440 + start) * 60, text: 'Otevíráme ' + when + ' v ' + fmt(start), short: when + ' od\u00a0' + fmt(start) };
		}
		return null;
	}

	/*
	 * Stav semaforu:
	 * s      – otevreno | brzy | zavreno (barva světla)
	 * t      – krátký text do hlavičky
	 * title  – velký nápis
	 * sub    – podtitulek
	 * target – čas v sekundách od dnešní půlnoci, ke kterému běží odpočet
	 * lbl    – popisek odpočtu („do zavření“ / „do otevření“)
	 */
	function compute(c) {
		var now = pragueNow();
		var soon = typeof c.soon === 'number' ? c.soon : 30;
		// Jiný den, než pro který server poslal data → zavírání z kalendáře neznáme, bereme jen otevírací dobu.
		var sameDay = c.today === now.date;
		var closures = sameDay ? (c.closures || []) : [];
		var hours = parseRange((c.hours || {})[now.wd]);
		var next = nextOpening(c, now, sameDay);
		var toOpen = function (s, title, sub) {
			return { s: s, title: title, sub: sub || (next ? next.text : ''), t: title + (next ? ' · ' + next.short : ''), target: next ? next.atSec : null, lbl: 'do otevření' };
		};

		if (sameDay && c.manual) {
			var r = toOpen('zavreno', 'Dnes zavřeno');
			if (c.manualMsg) { r.sub = c.manualMsg + (next ? ' · ' + next.text.toLowerCase() : ''); r.t = 'Dnes zavřeno · ' + c.manualMsg; }
			return r;
		}
		if (!hours) { return toOpen('zavreno', 'Dnes zavřeno'); }

		var m = now.min, open = hours[0], close = hours[1];

		for (var i = 0; i < closures.length; i++) {
			var cl = closures[i];
			if (cl.from <= open && cl.to >= close) {
				var rc = toOpen('zavreno', 'Dnes zavřeno');
				rc.sub = cl.label + (next ? ' · ' + next.text.toLowerCase() : '');
				rc.t = 'Dnes zavřeno' + (next ? ' · ' + next.short : '');
				return rc;
			}
			if (m >= cl.from && m < cl.to) {
				if (cl.to < close) {
					return { s: 'zavreno', title: 'Zavřeno', sub: cl.label + ' · otevíráme v ' + fmt(cl.to), t: 'Zavřeno · do\u00a0' + fmt(cl.to), target: cl.to * 60, lbl: 'do otevření' };
				}
				var rl = toOpen('zavreno', 'Zavřeno');
				rl.sub = cl.label + (next ? ' · ' + next.text.toLowerCase() : '');
				return rl;
			}
		}
		if (m < open) {
			var s = open - m <= soon ? 'brzy' : 'zavreno';
			var title = s === 'brzy' ? 'Brzy otevíráme' : 'Zavřeno';
			return { s: s, title: title, sub: 'Otevíráme dnes v ' + fmt(open), t: s === 'brzy' ? 'Otevíráme v\u00a0' + fmt(open) : 'Zavřeno · dnes od\u00a0' + fmt(open), target: open * 60, lbl: 'do otevření' };
		}
		if (m >= close) { return toOpen('zavreno', 'Zavřeno'); }

		var until = close, why = '';
		for (var j = 0; j < closures.length; j++) {
			if (closures[j].from > m && closures[j].from < until) { until = closures[j].from; why = closures[j].label; }
		}
		if (until - m <= soon) {
			return { s: 'brzy', title: 'Brzy zavíráme', sub: 'Zavíráme v ' + fmt(until) + (why ? ' · pak ' + why : ''), t: 'Zavíráme v\u00a0' + fmt(until), target: until * 60, lbl: 'do zavření' };
		}
		return { s: 'otevreno', title: 'Otevřeno', sub: 'Dnes do ' + fmt(until) + (why ? ' · pak ' + why : ''), t: 'Otevřeno do\u00a0' + fmt(until), target: until * 60, lbl: 'do zavření' };
	}

	function setAll(sel, fn) { document.querySelectorAll(sel).forEach(fn); }

	function render() {
		if (!cfg) { return; }
		state = compute(cfg);
		state.date = pragueNow().date;
		setAll('.sk-semafor, .sk-sem', function (el) { el.setAttribute('data-stav', state.s); });
		setAll('.sk-semafor-text', function (el) { el.textContent = state.t; });
		setAll('.sk-sem-title', function (el) { el.textContent = state.title; });
		setAll('.sk-sem-sub', function (el) { el.textContent = state.sub || ''; });
		setAll('.sk-sem-clocklbl', function (el) { el.textContent = state.lbl || ''; });
		var adm = document.getElementById('sk-admin-status');
		if (adm) { adm.textContent = state.t; }
		tick();
	}

	// Každou sekundu jen přepíše číslice odpočtu. Když odpočet doběhne, přepočítá celý stav.
	function tick() {
		if (!state) { return; }
		var rows = document.querySelectorAll('.sk-sem-clockrow');
		if (state.target === null || state.target === undefined) {
			rows.forEach(function (el) { el.hidden = true; });
			return;
		}
		var pn = pragueNow();
		if (pn.date !== state.date) { render(); return; } // po půlnoci přepočítat
		var left = state.target - pn.sec;
		if (left <= 0) { render(); return; }
		rows.forEach(function (el) { el.hidden = false; });
		setAll('.sk-sem-clock', function (el) { el.textContent = clock(left); });
	}

	function refresh() {
		if (!cfg || !cfg.rest || !window.fetch) { return; }
		fetch(cfg.rest, { cache: 'no-store', credentials: 'omit' })
			.then(function (res) { return res.ok ? res.json() : null; })
			.then(function (data) { if (data && data.hours) { cfg = data; render(); } })
			.catch(function () {});
	}

	function menu() {
		var header = document.getElementById('sk-header');
		var btn = header ? header.querySelector('.sk-menu-toggle') : null;
		if (btn) {
			btn.addEventListener('click', function () {
				var open = header.classList.toggle('is-open');
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
				btn.setAttribute('aria-label', open ? 'Zavřít menu' : 'Otevřít menu');
			});
		}
		// Rozbalovací Rezervovat: zavřít klepnutím mimo nebo klávesou Esc.
		document.addEventListener('click', function (e) {
			document.querySelectorAll('.sk-rez[open], .sk-mbar details[open]').forEach(function (d) {
				if (!d.contains(e.target)) { d.removeAttribute('open'); }
			});
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				document.querySelectorAll('.sk-rez[open], .sk-mbar details[open]').forEach(function (d) { d.removeAttribute('open'); });
			}
		});
	}

	/* ---------- Počítadlo kilometrů [skalicka_kroky] ---------- */
	var NF = window.Intl ? new Intl.NumberFormat('cs-CZ', { maximumFractionDigits: 0 }) : null;
	function nf(n) { n = Math.floor(n); return NF ? NF.format(n) : String(n); }

	function daysBetween(a, b) {
		return Math.round((Date.parse(b + 'T12:00:00Z') - Date.parse(a + 'T12:00:00Z')) / 86400000);
	}

	function kroky() {
		document.querySelectorAll('.sk-kroky[data-kroky]').forEach(function (box) {
			var k;
			try { k = JSON.parse(box.getAttribute('data-kroky')); } catch (e) { return; }
			var now = pragueNow();
			var entries = k.base;
			var diff = daysBetween(k.date, now.date);
			if (diff > 0) {
				entries += (diff - 1) * k.daily;
				var h = cfg ? parseRange((cfg.hours || {})[now.wd]) : null;
				var closed = cfg && cfg.manual && cfg.today === now.date;
				if (h && !closed) {
					var f = (now.sec - h[0] * 60) / ((h[1] - h[0]) * 60);
					entries += Math.max(0, Math.min(1, f)) * k.daily;
				}
			}
			var run = entries * (1 - k.tod) * k.km;
			var crawl = entries * k.tod * k.kmTod;
			var total = run + crawl;
			box.setAttribute('data-total', total.toFixed(3));
			var q = function (n) { return box.querySelector('[data-k="' + n + '"]'); };
			var set = function (n, t) { var el = q(n); if (el && el.textContent !== t) { el.textContent = t; } };
			set('total', nf(total));
			set('dec', ',' + ('00' + Math.floor((total % 1) * 1000)).slice(-3));
			set('steps', nf(total * 1000 / k.stride));
			set('run', nf(run) + ' km');
			set('crawl', nf(crawl) + ' km');
			var r = k.route, i = 1;
			while (i < r.length - 1 && total >= r[i][1]) { i++; }
			set('from', r[i - 1][2]);
			set('to', r[i][3]);
			set('left', nf(r[i][1] - total + 0.999) + ' km');
			var bar = q('bar');
			if (bar) { bar.style.width = Math.max(0, Math.min(100, (total - r[i - 1][1]) / (r[i][1] - r[i - 1][1]) * 100)).toFixed(2) + '%'; }
			box.querySelectorAll('.sk-trip-list li').forEach(function (li, n) {
				li.className = total >= r[n + 1][1] ? 'is-done' : (n + 1 === i ? 'is-next' : '');
			});
		});
	}

	function start() {
		render();
		refresh();
		kroky();
		setInterval(function () { tick(); kroky(); }, 1000);
		setInterval(render, 60000);
		setInterval(refresh, 10 * 60000);
		menu();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* Konfety v pásu s nabídkou oslav (jen když nevadí pohyb). */
(function () {
	'use strict';
	function konfety() {
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
		document.querySelectorAll('.sk-party').forEach(function (p) {
			var band = p.closest('.sk-pband, .wp-block-group');
			if (!band || band.querySelector('.sk-konfety')) { return; }
			band.classList.add('has-konfety');
			var box = document.createElement('div');
			box.className = 'sk-konfety'; box.setAttribute('aria-hidden', 'true');
			var colors = ['#FECC06', '#E84F37', '#A3CBD6', '#B2DCD9', '#FD8F6B', '#D3B2A8'];
			var count = window.innerWidth < 700 ? 18 : 34;
			for (var i = 0; i < count; i++) {
				var s = document.createElement('span');
				s.className = 'sk-konfeta' + (i % 3 === 0 ? ' k-kruh' : '');
				s.style.left = (Math.random() * 100) + '%';
				s.style.background = colors[i % colors.length];
				s.style.animationDuration = (7 + Math.random() * 7) + 's';
				s.style.animationDelay = (-Math.random() * 14) + 's';
				s.style.setProperty('--dx', (Math.random() * 80 - 40) + 'px');
				box.appendChild(s);
			}
			band.insertBefore(box, band.firstChild);
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', konfety); } else { konfety(); }
})();

/* Odkazy mimo web (Reenio, Stripe, sociální sítě, mapy…) a PDF otevírat v nové záložce. */
(function () {
	'use strict';
	function external(a) {
		if (!/^https?:$/.test(a.protocol)) { return false; }
		if (a.hostname !== location.hostname) { return true; }
		return /\.pdf$/i.test(a.pathname);
	}
	function mark(a) {
		if (a.hasAttribute('target') || a.closest('#wpadminbar') || !external(a)) { return; }
		a.setAttribute('target', '_blank');
		var rel = (a.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
		['noopener'].forEach(function (r) { if (rel.indexOf(r) < 0) { rel.push(r); } });
		a.setAttribute('rel', rel.join(' '));
	}
	function all() { document.querySelectorAll('a[href]').forEach(mark); }
	// I pro odkazy, které se načtou až později (kalendář, Instagram): nastaví se při kliknutí.
	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a[href]') : null;
		if (a) { mark(a); }
	}, true);
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', all); } else { all(); }
})();

/* Akce v přehledech: v čase konání přepnout na „Teď probíhá“ (bez rezervace), po konci skrýt. */
(function () {
	'use strict';
	function tick() {
		var now = Date.now() / 1000;
		document.querySelectorAll('[data-sk-od][data-sk-do]').forEach(function (el) {
			var od = +el.getAttribute('data-sk-od'), konec = +el.getAttribute('data-sk-do');
			if (now >= konec) { el.classList.add('is-konec'); el.classList.remove('is-ted'); }
			else if (now >= od) {
				if (!el.classList.contains('is-ted')) {
					el.classList.add('is-ted');
					if (el.classList.contains('is-rez')) { el.setAttribute('aria-disabled', 'true'); el.setAttribute('tabindex', '-1'); }
				}
			}
		});
	}
	function start() {
		document.querySelectorAll('.sk-sem-akce.is-rez.is-ted').forEach(function (a) { a.setAttribute('aria-disabled', 'true'); a.setAttribute('tabindex', '-1'); });
		tick(); setInterval(tick, 15000);
	}
	// probíhající rezervační štítek nejde otevřít ani klávesnicí
	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('.sk-sem-akce.is-rez.is-ted') : null;
		if (a) { e.preventDefault(); }
	}, true);
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
