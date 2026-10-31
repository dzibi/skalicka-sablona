/* Skalička: fotky na webu jdou otevřít na celou obrazovku a listovat albem (galerií). */
(function () {
	'use strict';

	var SKIP = '.sk-cards, .sk-hero, .sk-party, .sk-mapa, .sk-kroky, .sk-vytah, .sb_instagram, #sb_instagram';

	function fullSrc(img) {
		var best = '', bw = 0;
		(img.getAttribute('srcset') || '').split(',').forEach(function (c) {
			var p = c.trim().split(/\s+/), w = parseInt(p[1], 10) || 0;
			if (p[0] && w > bw) { bw = w; best = p[0]; }
		});
		return best || (img.currentSrc || img.src).replace(/-\d+x\d+(\.\w+)$/, '$1');
	}

	function albums() {
		var groups = [];
		document.querySelectorAll('main .wp-block-gallery').forEach(function (g) {
			if (g.closest(SKIP)) { return; }
			var imgs = [].slice.call(g.querySelectorAll('figure.wp-block-image img')).filter(function (i) { return !i.closest('a'); });
			if (imgs.length) { groups.push(imgs); }
		});
		document.querySelectorAll('main figure.wp-block-image > img').forEach(function (i) {
			if (i.closest('.wp-block-gallery') || i.closest(SKIP)) { return; }
			groups.push([i]);
		});
		return groups;
	}

	var box, imgEl, countEl, prevBtn, nextBtn, list = [], idx = 0, lastFocus = null, sx = 0, sy = 0;

	function build() {
		box = document.createElement('div');
		box.className = 'sk-lb';
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-label', 'Fotka na celou obrazovku');
		box.hidden = true;
		box.innerHTML =
			'<button type="button" class="sk-lb-x" aria-label="Zavřít">×</button>' +
			'<button type="button" class="sk-lb-nav sk-lb-prev" aria-label="Předchozí fotka">‹</button>' +
			'<figure class="sk-lb-fig"><img alt=""></figure>' +
			'<button type="button" class="sk-lb-nav sk-lb-next" aria-label="Další fotka">›</button>' +
			'<p class="sk-lb-count" aria-live="polite"></p>';
		document.body.appendChild(box);
		imgEl = box.querySelector('img');
		countEl = box.querySelector('.sk-lb-count');
		prevBtn = box.querySelector('.sk-lb-prev');
		nextBtn = box.querySelector('.sk-lb-next');
		box.querySelector('.sk-lb-x').addEventListener('click', close);
		prevBtn.addEventListener('click', function () { go(-1); });
		nextBtn.addEventListener('click', function () { go(1); });
		box.addEventListener('click', function (e) { if (e.target === box || e.target.classList.contains('sk-lb-fig')) { close(); } });
		box.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
		box.addEventListener('touchend', function (e) {
			var dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
			if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) { go(dx < 0 ? 1 : -1); }
			else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) { close(); }
		}, { passive: true });
		document.addEventListener('keydown', function (e) {
			if (box.hidden) { return; }
			if (e.key === 'Escape') { close(); }
			else if (e.key === 'ArrowRight') { go(1); }
			else if (e.key === 'ArrowLeft') { go(-1); }
			else if (e.key === 'Tab') {
				var f = [].slice.call(box.querySelectorAll('button')).filter(function (b) { return !b.hidden; });
				var i = f.indexOf(document.activeElement);
				e.preventDefault();
				f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
			}
		});
	}

	function show() {
		var src = list[idx];
		imgEl.classList.add('is-loading');
		imgEl.onload = function () { imgEl.classList.remove('is-loading'); };
		imgEl.src = fullSrc(src);
		imgEl.alt = src.alt || '';
		var many = list.length > 1;
		prevBtn.hidden = nextBtn.hidden = !many;
		countEl.textContent = many ? (idx + 1) + ' / ' + list.length : '';
		if (many) {
			[1, -1].forEach(function (d) { var p = new Image(); p.src = fullSrc(list[(idx + d + list.length) % list.length]); });
		}
	}

	function go(d) { if (list.length > 1) { idx = (idx + d + list.length) % list.length; show(); } }

	function open(group, i) {
		if (!box) { build(); }
		list = group; idx = i; lastFocus = document.activeElement;
		show();
		box.hidden = false;
		document.documentElement.classList.add('sk-lb-open');
		box.querySelector('.sk-lb-x').focus();
	}

	function close() {
		box.hidden = true;
		imgEl.removeAttribute('src');
		document.documentElement.classList.remove('sk-lb-open');
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	function init() {
		albums().forEach(function (group) {
			group.forEach(function (img, i) {
				img.classList.add('sk-zoom');
				img.setAttribute('tabindex', '0');
				img.setAttribute('role', 'button');
				img.setAttribute('aria-label', 'Zvětšit fotku' + (img.alt ? ': ' + img.alt : ''));
				img.addEventListener('click', function () { open(group, i); });
				img.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(group, i); } });
			});
		});
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
