/* Skalička: zeměkoule s cestou dětí a raketa na Měsíc (počítadlo kilometrů). */
import { geoOrthographic, geoPath, geoGraticule10, geoInterpolate, geoDistance } from 'd3-geo';
import { feature } from 'topojson-client';
import land110 from 'world-atlas/land-110m.json';

const LAND = feature(land110, land110.objects.land);
const GRAT = geoGraticule10();

// Zastávky cesty: [název, zeměpisná délka, šířka] – stejné pořadí jako v PHP (inc/kroky.php).
const STOPS = [
	['Příbram', 14.01, 49.689], ['Praha', 14.421, 50.087], ['Vídeň', 16.372, 48.208], ['Řím', 12.496, 41.902],
	['Paříž', 2.352, 48.857], ['Londýn', -0.128, 51.507], ['Rovník', 9.467, 0.416], ['Austrálie', 151.209, -33.869],
	['Tokio', 139.69, 35.68], ['Příbram', 14.01, 49.689]
];
const EARTH_KM = 39786; // délka cesty kolem světa podle PHP
const MOON_KM = 424186;
const MARS_KM = 54600000;

const C = { ocean: '#A3CBD6', land: '#FFF8F2', choco: '#3C3231', yellow: '#FECC06', coral: '#E84F37', grat: 'rgba(255,255,255,.45)' };

// Délky úseků v km (podle skutečné vzdálenosti na kouli), aby pozice odpovídala PHP.
const LEGS = [];
let acc = 0;
for (let i = 1; i < STOPS.length; i++) {
	const a = [STOPS[i - 1][1], STOPS[i - 1][2]], b = [STOPS[i][1], STOPS[i][2]];
	const km = geoDistance(a, b) * 6371;
	LEGS.push({ a, b, from: acc, to: acc + km, interp: geoInterpolate(a, b) });
	acc += km;
}
const SCALE = EARTH_KM / acc; // drobné sjednocení s PHP tabulkou

function positionAt(km) {
	const k = km / SCALE;
	for (const L of LEGS) {
		if (k <= L.to) { return L.interp((k - L.from) / (L.to - L.from)); }
	}
	return LEGS[LEGS.length - 1].b;
}

function traveledLine(km) {
	const k = Math.min(km / SCALE, acc);
	const coords = [];
	for (const L of LEGS) {
		const steps = Math.max(2, Math.ceil((L.to - L.from) / 150));
		for (let s = 0; s <= steps; s++) {
			const at = L.from + (L.to - L.from) * (s / steps);
			if (at > k) { coords.push(L.interp((k - L.from) / (L.to - L.from))); return { type: 'LineString', coordinates: coords }; }
			coords.push(L.interp(s / steps));
		}
	}
	return { type: 'LineString', coordinates: coords };
}

function fullRoute() {
	const coords = [];
	for (const L of LEGS) {
		const steps = Math.max(2, Math.ceil((L.to - L.from) / 150));
		for (let s = 0; s <= steps; s++) { coords.push(L.interp(s / steps)); }
	}
	return { type: 'LineString', coordinates: coords };
}
const ROUTE = fullRoute();

function setup(box) {
	const wrap = box.querySelector('.sk-globe');
	if (!wrap) { return; }
	const canvas = document.createElement('canvas');
	wrap.appendChild(canvas);
	const ctx = canvas.getContext('2d');
	const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const test = new URLSearchParams(location.search).get('sk_km');
	let W = 0, H = 0, dpr = 1;
	let rot = null; // aktuální natočení [λ, φ]
	let visible = true;
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(function (en) { visible = en.some(function (e) { return e.isIntersecting; }); }).observe(wrap);
	}

	function size() {
		dpr = Math.min(2, window.devicePixelRatio || 1);
		W = wrap.clientWidth; H = Math.round(W * 0.8);
		canvas.width = W * dpr; canvas.height = H * dpr;
		canvas.style.width = W + 'px'; canvas.style.height = H + 'px';
	}
	size();
	window.addEventListener('resize', size);

	function totalKm() {
		if (test) { return parseFloat(test); }
		const t = parseFloat(box.getAttribute('data-total'));
		return isFinite(t) ? t : 0;
	}

	function drawGlobe(cx, cy, r, center, km, detail) {
		const proj = geoOrthographic().scale(r).translate([cx, cy]).rotate([-center[0], -center[1]]).clipAngle(90);
		const path = geoPath(proj, ctx);
		ctx.beginPath(); ctx.arc(cx, cy, r, 0, 2 * Math.PI); ctx.fillStyle = C.ocean; ctx.fill();
		ctx.beginPath(); path(GRAT); ctx.strokeStyle = C.grat; ctx.lineWidth = 1; ctx.stroke();
		ctx.beginPath(); path(LAND); ctx.fillStyle = C.land; ctx.fill(); ctx.strokeStyle = 'rgba(60,50,49,.35)'; ctx.lineWidth = 0.8; ctx.stroke();
		if (detail) {
			ctx.save(); ctx.setLineDash([4, 6]); ctx.beginPath(); path(ROUTE); ctx.strokeStyle = 'rgba(60,50,49,.45)'; ctx.lineWidth = 2; ctx.stroke(); ctx.restore();
			ctx.beginPath(); path(traveledLine(km)); ctx.strokeStyle = C.choco; ctx.lineWidth = 6; ctx.lineCap = 'round'; ctx.stroke();
			ctx.beginPath(); path(traveledLine(km)); ctx.strokeStyle = C.yellow; ctx.lineWidth = 3.5; ctx.stroke();
			// města na přivrácené straně
			ctx.font = '700 ' + Math.max(11, r / 14) + 'px Nunito, sans-serif';
			ctx.textBaseline = 'middle';
			const placed = [];
			STOPS.slice(0, -1).forEach(function (s) {
				const p = [s[1], s[2]];
				if (geoDistance(p, center) > Math.PI / 2 - 0.05) { return; }
				const xy = proj(p);
				ctx.beginPath(); ctx.arc(xy[0], xy[1], 3.5, 0, 2 * Math.PI); ctx.fillStyle = C.choco; ctx.fill();
				const w = ctx.measureText(s[0]).width, lx = xy[0] + 6, ly = xy[1] - 8;
				const hit = placed.some(function (b) { return lx < b[0] + b[2] + 4 && lx + w + 4 > b[0] && Math.abs(ly - b[1]) < 14; });
				if (hit) { return; }
				placed.push([lx, ly, w]);
				ctx.lineWidth = 3; ctx.strokeStyle = 'rgba(255,248,242,.9)'; ctx.strokeText(s[0], lx, ly);
				ctx.fillStyle = C.choco; ctx.fillText(s[0], lx, ly);
			});
		}
		// okraj a lesk
		ctx.beginPath(); ctx.arc(cx, cy, r, 0, 2 * Math.PI); ctx.strokeStyle = C.choco; ctx.lineWidth = 3; ctx.stroke();
		const g = ctx.createRadialGradient(cx - r * 0.4, cy - r * 0.45, r * 0.05, cx, cy, r);
		g.addColorStop(0, 'rgba(255,255,255,.35)'); g.addColorStop(0.5, 'rgba(255,255,255,0)'); g.addColorStop(1, 'rgba(60,50,49,.12)');
		ctx.beginPath(); ctx.arc(cx, cy, r, 0, 2 * Math.PI); ctx.fillStyle = g; ctx.fill();
		return proj;
	}

	function runner(x, y, t) {
		const pulse = reduce ? 0 : (Math.sin(t / 300) + 1) / 2;
		ctx.beginPath(); ctx.arc(x, y, 10 + pulse * 8, 0, 2 * Math.PI); ctx.fillStyle = 'rgba(254,204,6,' + (0.45 - pulse * 0.3) + ')'; ctx.fill();
		ctx.beginPath(); ctx.arc(x, y, 8, 0, 2 * Math.PI); ctx.fillStyle = C.yellow; ctx.fill(); ctx.lineWidth = 3; ctx.strokeStyle = C.choco; ctx.stroke();
	}

	function rocket(x, y, ang, t) {
		ctx.save(); ctx.translate(x, y); ctx.rotate(ang);
		const f = reduce ? 1 : 0.75 + Math.random() * 0.5;
		ctx.beginPath(); ctx.moveTo(-14, -6); ctx.lineTo(-14 - 18 * f, 0); ctx.lineTo(-14, 6); ctx.fillStyle = C.yellow; ctx.fill();
		ctx.beginPath(); ctx.moveTo(-14, -4); ctx.lineTo(-14 - 10 * f, 0); ctx.lineTo(-14, 4); ctx.fillStyle = C.coral; ctx.fill();
		ctx.beginPath(); ctx.moveTo(-14, -9); ctx.lineTo(10, -9); ctx.quadraticCurveTo(26, 0, 10, 9); ctx.lineTo(-14, 9); ctx.closePath();
		ctx.fillStyle = '#fff'; ctx.fill(); ctx.lineWidth = 2.5; ctx.strokeStyle = C.choco; ctx.stroke();
		ctx.beginPath(); ctx.arc(4, 0, 4, 0, 2 * Math.PI); ctx.fillStyle = C.ocean; ctx.fill(); ctx.stroke();
		ctx.beginPath(); ctx.moveTo(-10, -9); ctx.lineTo(-16, -16); ctx.lineTo(-4, -9); ctx.moveTo(-10, 9); ctx.lineTo(-16, 16); ctx.lineTo(-4, 9); ctx.fillStyle = C.coral; ctx.fill(); ctx.stroke();
		ctx.restore();
	}

	function planet(x, y, r, kind) {
		ctx.beginPath(); ctx.arc(x, y, r, 0, 2 * Math.PI);
		ctx.fillStyle = kind === 'mars' ? '#D9764A' : '#E6E1DA'; ctx.fill();
		ctx.lineWidth = 3; ctx.strokeStyle = C.choco; ctx.stroke();
		ctx.fillStyle = kind === 'mars' ? 'rgba(120,50,20,.35)' : 'rgba(60,50,49,.15)';
		[[-0.35, -0.2, 0.18], [0.25, 0.3, 0.14], [0.15, -0.4, 0.1], [-0.1, 0.45, 0.08]].forEach(function (c) {
			ctx.beginPath(); ctx.arc(x + c[0] * r, y + c[1] * r, c[2] * r, 0, 2 * Math.PI); ctx.fill();
		});
	}

	function stars(t) {
		ctx.fillStyle = 'rgba(255,255,255,.9)';
		for (let i = 0; i < 40; i++) {
			const x = ((i * 97) % 100) / 100 * W, y = ((i * 57) % 100) / 100 * H;
			const tw = reduce ? 1 : 0.5 + 0.5 * Math.sin(t / 500 + i);
			ctx.globalAlpha = 0.4 + 0.6 * tw; ctx.fillRect(x, y, 2, 2);
		}
		ctx.globalAlpha = 1;
	}

	function frame(t) {
		if (!visible || document.hidden) { setTimeout(function () { requestAnimationFrame(frame); }, 400); return; }
		const km = totalKm();
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		ctx.clearRect(0, 0, W, H);
		if (km < EARTH_KM) {
			// Země: natočit plynule na aktuální pozici
			const pos = positionAt(km);
			const target = [pos[0], Math.max(-35, Math.min(35, pos[1] * 0.7))];
			if (!rot) { rot = target.slice(); }
			let dl = target[0] - rot[0]; if (dl > 180) { dl -= 360; } if (dl < -180) { dl += 360; }
			rot[0] += dl * 0.05; rot[1] += (target[1] - rot[1]) * 0.05;
			const r = Math.min(W, H) * 0.46;
			const proj = drawGlobe(W / 2, H / 2, r, rot, km, true);
			const xy = proj(pos);
			if (xy && geoDistance(pos, rot) < Math.PI / 2) { runner(xy[0], xy[1], t); }
		} else {
			// Vesmír: Země → Měsíc → Mars
			const toMars = km >= MOON_KM;
			const from = toMars ? MOON_KM : EARTH_KM, to = toMars ? MARS_KM : MOON_KM;
			const p = Math.max(0, Math.min(1, (km - from) / (to - from)));
			ctx.fillStyle = '#2E2A3A'; ctx.beginPath(); ctx.roundRect ? ctx.roundRect(0, 0, W, H, 22) : ctx.rect(0, 0, W, H); ctx.fill();
			stars(t);
			const ax = W * 0.18, ay = H * 0.68, bx = W * 0.82, by = H * 0.32;
			if (toMars) { planet(ax, ay, H * 0.12, 'moon'); planet(bx, by, H * 0.15, 'mars'); }
			else { drawGlobe(ax, ay, H * 0.17, [14, 30], km, false); planet(bx, by, H * 0.12, 'moon'); }
			// oblouk letu
			const mx = W * 0.5, my = H * 0.12;
			const bez = function (u) { return [(1 - u) * (1 - u) * ax + 2 * (1 - u) * u * mx + u * u * bx, (1 - u) * (1 - u) * ay + 2 * (1 - u) * u * my + u * u * by]; };
			ctx.save(); ctx.setLineDash([3, 7]); ctx.beginPath(); ctx.moveTo(ax, ay); ctx.quadraticCurveTo(mx, my, bx, by); ctx.strokeStyle = 'rgba(255,255,255,.55)'; ctx.lineWidth = 2; ctx.stroke(); ctx.restore();
			const u = 0.08 + p * 0.84;
			const a = bez(u), b = bez(Math.min(1, u + 0.01));
			const wob = reduce ? 0 : Math.sin(t / 250) * 2;
			rocket(a[0], a[1] + wob, Math.atan2(b[1] - a[1], b[0] - a[0]), t);
		}
		if (!reduce) { requestAnimationFrame(frame); } else { setTimeout(function () { requestAnimationFrame(frame); }, 1000); }
	}
	requestAnimationFrame(frame);
}

function init() {
	document.querySelectorAll('.sk-kroky').forEach(function (box) {
		if (!('IntersectionObserver' in window)) { setup(box); return; }
		const io = new IntersectionObserver(function (en) {
			if (en.some(function (e) { return e.isIntersecting; })) { io.disconnect(); setup(box); }
		}, { rootMargin: '200px' });
		io.observe(box);
	});
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
