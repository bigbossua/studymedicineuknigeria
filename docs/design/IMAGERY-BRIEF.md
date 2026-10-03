# Imagery brief and workflow (Unsplash, £0)

Design system 19.7: real UK academic and clinical learning settings and Nigerian students studying; neutral palettes; a slight navy duotone on heroes for cohesion; no AI-generated places; never a photo that implies a partnership with a named university or shows a real client. Photographs support a page's purpose; most pages need none.

## Workflow (owner picks in the open Unsplash tab, the pipeline does the rest)

1. In Unsplash, search the shot list below. Choose landscape images, longest side ≥ 2400 px, free Unsplash License (not Unsplash+).
2. Download the original. Rename it to the slug in the table (`home-hero.jpg`) and place it in `brand/photos/`.
3. Add an entry to `brand/photos/manifest.json` → `photos`:
   ```json
   {"slug": "home-hero", "alt": "…descriptive alt text…", "credit": "Photographer Name / Unsplash", "source_url": "https://unsplash.com/photos/…", "licence": "Unsplash License", "focal": "50% 35%"}
   ```
   Alt text describes what is literally in the frame, never who the people are. Attribution is not legally required by the Unsplash License but we always record it.
4. Run `php artisan smukn:images`. It writes WebP + JPEG at 480/960/1440 px and a blurred placeholder to `public/images/photos/` (originals never ship) and refreshes `public/images/photos/manifest.json`.
5. The page uses `<x-photo slug="home-hero" sizes="…" :priority="true" />`. A slot whose photo is not built renders nothing, so pages never break while the set is incomplete.
6. Commit `brand/photos/manifest.json` and the generated `public/images/photos/*` (originals in `brand/photos/*.jpg` are git-ignored to keep the repository small).

## Shot list (priority order)

| Slug | Page and slot | What to look for | Avoid |
|---|---|---|---|
| `home-hero` | Home, hero right column (replaces nothing; sits above the "where most applicants start" card on large screens) | a UK university library, lecture theatre or anatomy teaching space, calm light, no logos | hospital emergencies, stock "doctor with stethoscope", identifiable university crests |
| `nigeria-guide` | Study Medicine in the UK from Nigeria, after the lede | a Nigerian student studying (laptop, notes), natural light | staged "graduation celebration" shots, flags |
| `directory` | Medical schools directory header | an exterior of a university quadrangle or medical school building that is not identifiable as one specific school | named buildings, signage |
| `requirements` | Requirements hub | exam hall or certificates on a desk (WAEC-style documents must not be real personal documents) | any readable personal data |
| `ucat` | UCAT page | a test centre or a student at a computer | Pearson VUE branding |
| `fees` | Fees guide | a calm UK street or campus in daylight to ground living-cost content | pound notes, piles of money |
| `apply` | Apply Online landing | a person completing a form on a laptop, hands only | faces reading as "our students" |
| `about` | About | a tidy desk with reference books; no people | anything that implies an office we do not have |

Hero and landing images: `sizes="(min-width: 1024px) 40vw, 100vw"`, `priority` on the home hero only. All others lazy-load (default).

## Why not more
Every image costs bandwidth for readers on Nigerian mobile connections. The design system relies on typography and spacing for its premium feel; photography is an accent, not a layer on every page.
