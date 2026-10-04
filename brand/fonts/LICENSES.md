# Fonts

All typefaces are licensed under the SIL Open Font License 1.1 and are self-hosted (never hot-linked).

| Family | Use | Source |
|---|---|---|
| Source Serif 4 (variable: opsz 8–60, wght 200–900) | wordmark, display headings | https://github.com/adobe-fonts/source-serif |
| Inter (variable: wght 100–900) | interface, body, forms, tables, logo descriptor line | https://github.com/rsms/inter |
| JetBrains Mono (variable) | application numbers, codes | https://github.com/JetBrains/JetBrainsMono |

Files here are the Google Fonts latin and latin-ext woff2 subsets.

# Icons and map data

- **Icons**: [Lucide](https://lucide.dev) (ISC licence, Copyright (c) Lucide Icons and Contributors), baked into `resources/views/components/icon.blade.php` by `ops/design/build-icons.mjs`. Permission to use, copy, modify and distribute is granted provided the copyright notice and permission notice appear in copies.
- **Maps**: country and land outlines from [Natural Earth](https://www.naturalearthdata.com) (public domain), via the `world-atlas` package (ISC), projected by `ops/design/build-maps.mjs` into `resources/data/map-uk.json` and `public/images/maps/route-nigeria-uk.svg`. City positions are approximate city-centre coordinates in `data/geo/cities.json`; the maps are illustrations, not survey data.
