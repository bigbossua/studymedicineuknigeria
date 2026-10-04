// Builds the site's map illustrations from Natural Earth country outlines (public domain, via the world-atlas npm
// package) and data/geo/cities.json. Output is committed, so the site has no runtime dependency on these packages:
//   resources/data/map-uk.json                 UK + Ireland outline paths and projected city points (pins drawn in Blade)
//   public/images/maps/route-nigeria-uk.svg    Nigeria → United Kingdom route illustration for the homepage hero
// Usage: npm i --no-save world-atlas@2 d3-geo@3 topojson-client@3 && node ops/design/build-maps.mjs
import fs from 'node:fs';
import { createRequire } from 'node:module';
const require = createRequire(process.env.MAP_MODULES ? process.env.MAP_MODULES + '/' : import.meta.url);
const { geoPath, geoTransverseMercator, geoOrthographic, geoInterpolate, geoGraticule10 } = require('d3-geo');
const { feature } = require('topojson-client');
const world50 = require('world-atlas/countries-50m.json');
const world110 = require('world-atlas/countries-110m.json');
const land110 = require('world-atlas/land-110m.json');
const cities = JSON.parse(fs.readFileSync('data/geo/cities.json', 'utf8'));
const byName = (topo, name) => feature(topo, topo.objects.countries).features.find((f) => f.properties.name === name);
const round = (n) => Math.round(n * 10) / 10;

// UK map: transverse Mercator centred on 2°W (as the Ordnance Survey grid), fitted to a 400×520 frame
const gb = byName(world50, 'United Kingdom'); const ie = byName(world50, 'Ireland');
const W = 400, H = 520;
const uk = geoTransverseMercator().rotate([2, 0]).fitExtent([[12, 12], [W - 12, H - 12]], { type: 'FeatureCollection', features: [gb, ie] });
const p = geoPath(uk).digits(1);
const points = {};
for (const [name, ll] of Object.entries(cities)) { if (name.startsWith('_') || ['Lagos', 'Abuja'].includes(name)) continue; const [x, y] = uk([ll[1], ll[0]]); points[name] = [round(x), round(y)]; }
fs.mkdirSync('resources/data', { recursive: true });
fs.writeFileSync('resources/data/map-uk.json', JSON.stringify({ viewBox: `0 0 ${W} ${H}`, gb: p(gb), ie: p(ie), points }, null, 1) + '\n');

// Route illustration: an orthographic globe turned towards the Gulf of Guinea and Britain, countries at 110m
const RW = 560, RH = 560;
const globe = geoOrthographic().rotate([-2, -30]).fitExtent([[20, 20], [RW - 20, RH - 20]], { type: 'Sphere' });
const gp = geoPath(globe).digits(0);
const countries = feature(world110, world110.objects.countries).features;
const ng = countries.find((f) => f.properties.name === 'Nigeria'); const uk110 = countries.find((f) => f.properties.name === 'United Kingdom');
const lagos = [cities.Lagos[1], cities.Lagos[0]]; const london = [cities.London[1], cities.London[0]];
const arc = { type: 'LineString', coordinates: Array.from({ length: 41 }, (_, i) => geoInterpolate(lagos, london)(i / 40)) };
const [lx, ly] = globe(lagos); const [ox, oy] = globe(london);
const others = gp(feature(land110, land110.objects.land));
const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${RW} ${RH}" role="img" aria-labelledby="t d">
<title id="t">From Nigeria to the United Kingdom</title><desc id="d">A globe showing a route line from Lagos in Nigeria to London in the United Kingdom.</desc>
<defs><radialGradient id="g" cx="40%" cy="35%" r="75%"><stop offset="0" stop-color="#15587F"/><stop offset="1" stop-color="#072A40"/></radialGradient></defs>
<path d="${gp({ type: 'Sphere' })}" fill="url(#g)"/>
<path d="${gp(geoGraticule10())}" fill="none" stroke="#6E97B5" stroke-opacity=".18" stroke-width=".6"/>
<path d="${others}" fill="#2A5876" stroke="#3B6A8A" stroke-width=".5"/>
<path d="${gp(ng)}" fill="#008751" stroke="#7FD1A8" stroke-width=".8"/>
<path d="${gp(uk110)}" fill="#E8EEF3" stroke="#FFFFFF" stroke-width=".8"/>
<path d="${gp(arc)}" fill="none" stroke="#F3C969" stroke-width="2.4" stroke-dasharray="6 6" stroke-linecap="round"/>
<circle cx="${round(lx)}" cy="${round(ly)}" r="6" fill="#F3C969" stroke="#072A40" stroke-width="2"/>
<circle cx="${round(ox)}" cy="${round(oy)}" r="6" fill="#F3C969" stroke="#072A40" stroke-width="2"/>
<g font-family="Inter, system-ui, sans-serif" font-size="15" font-weight="600" fill="#FFFFFF">
<text x="${round(lx) + 12}" y="${round(ly) + 5}">Lagos</text><text x="${round(ox) + 12}" y="${round(oy) + 5}">London</text></g>
</svg>
`;
fs.mkdirSync('public/images/maps', { recursive: true });
fs.writeFileSync('public/images/maps/route-nigeria-uk.svg', svg);
console.log(`map-uk.json: ${Object.keys(points).length} points, ${(fs.statSync('resources/data/map-uk.json').size / 1024).toFixed(1)} KB; route svg ${(svg.length / 1024).toFixed(1)} KB`);
