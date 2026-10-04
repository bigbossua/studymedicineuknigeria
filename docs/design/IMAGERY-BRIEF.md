# Imagery brief and workflow (Unsplash, £0)

Design system 19.7: real UK academic and clinical learning settings and Nigerian students studying; neutral palettes; a slight navy duotone on heroes for cohesion; no AI-generated places; never a photo that implies a partnership with a named university or shows a real client. Photographs support a page's purpose; most pages need none.

## Workflow (owner picks in the open Unsplash tab, the pipeline does the rest)

1. In Unsplash, search the shot list below. Choose landscape images, longest side ≥ 2400 px, free Unsplash License (not Unsplash+).
2. Download the original. Rename it to the slug in the table (`home-hero.jpg`) and place it in `brand/photos/`.
3. Add an entry to `brand/photos/manifest.json` → `photos`:
   ```json
   {"slug": "home-hero", "page": "/ (hero, right column)", "alt": "…descriptive alt text…", "credit": "Photographer Name / Unsplash", "source_url": "https://unsplash.com/photos/…", "licence": "Unsplash License", "focal": "50% 35%"}
   ```
   Alt text describes what is literally in the frame, never who the people are. Attribution is not legally required by the Unsplash License but we always record it.
4. Run `php artisan smukn:images`. It writes WebP + JPEG at 480/960/1440 px and a blurred placeholder to `public/images/photos/` (originals never ship) and refreshes `public/images/photos/manifest.json`.
5. The page uses `<x-photo slug="home-hero" sizes="…" :priority="true" />`. A slot whose photo is not built renders nothing, so pages never break while the set is incomplete.
6. Commit `brand/photos/manifest.json` and the generated `public/images/photos/*` (originals in `brand/photos/*.jpg` are git-ignored to keep the repository small).

## The record kept for every image

Owner directive 2026-10-03: every photograph used on the site has a record. The two manifests together hold it:

| Field | Where | Who fills it |
|---|---|---|
| Source and photographer credit | `brand/photos/manifest.json` → `credit`, `source_url`, `licence` | owner, from the Unsplash page |
| Page and slot it is used on | `manifest.json` → `page` (free text, e.g. `/admissions/ucat (header)`) | owner (the slot table below says where each slug renders) |
| Alt text | `manifest.json` → `alt` (what is literally in the frame; never who the people are) | owner |
| Original dimensions | `public/images/photos/manifest.json` → `width`, `height` | `smukn:images` |
| Filenames and optimisation | `public/images/photos/manifest.json` → `sizes` (WebP and JPEG at 480/960/1440 px, blurred placeholder), `generated_at` | `smukn:images` |

Nothing is published without the `licence` and `source_url` fields (the build command refuses the entry).

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

## Subject image plan (2026-10-04; for subject pages that do not exist yet)

Owner directive: an image plan for the major subject areas. No subject other than Medicine has a page (taxonomy rule: a page exists only at PUBLISHED; see `data/healthcare/subjects.json`), so these slots are **planned, not sourced**. Source a photo only when its subject reaches BUILD and the page is in the asset register; until then nothing is downloaded and no slot renders. Unsplash is not reachable from the build environment, so picks are made in the owner's browser with the workflow above.

| Slug (future) | Page when built | Unsplash search to start from | Look for | Avoid | Alt-text pattern |
|---|---|---|---|---|---|
| `medicine-hub` | Study Medicine in the UK (exists; optional) | "medical student anatomy lecture", "stethoscope notes desk" | a teaching space or study desk; Medicine is the flagship, so this is the first subject slot worth filling | stock "doctor with stethoscope" portraits, operating theatres | "Medical students' notes and a stethoscope on a library desk" |
| `dentistry` | Dentistry (VALIDATED) | "dental training simulation", "dental school clinic" | a phantom-head teaching clinic or dental instruments laid out for teaching | patients in treatment, branded clinics | "Dental training stations with phantom heads in a teaching clinic" |
| `pharmacy` | Pharmacy (VALIDATED) | "pharmacy student laboratory", "pharmacist shelves" | a teaching lab or a dispensary from behind the counter, no readable labels | branded medicine packs, readable prescriptions | "Rows of medicine drawers in a teaching pharmacy" |
| `nursing` | Nursing (VALIDATED) | "nursing student skills lab", "nurse training simulation" | a clinical skills lab with a manikin | real patients, NHS logos that imply endorsement | "A nursing skills lab with a training manikin on a hospital bed" |
| `midwifery` | Midwifery (RESEARCH; availability mostly closed) | "midwifery training", "neonatal manikin" | a simulation suite only | real births, babies, identifiable parents | "A neonatal training manikin in a simulation suite" |
| `physiotherapy` | Physiotherapy (RESEARCH) | "physiotherapy rehabilitation equipment" | rehabilitation equipment or a teaching room | clinic branding, identifiable patients | "Rehabilitation equipment in a physiotherapy teaching room" |
| `radiography` | Radiography (RESEARCH) | "x-ray room", "MRI scanner" | imaging equipment in an empty room | real images with patient data, scanner brand logos as the subject | "An empty X-ray room with the imaging arm lowered" |
| `optometry` | Optometry (RESEARCH) | "optometry equipment", "eye test chart" | a test chart or slit lamp | branded high-street opticians | "A slit lamp and test chart in an eye examination room" |
| `allied-health` | Healthcare courses overview hub (V01, VALIDATED) | "university health sciences building", "clinical skills lab" | a generic skills lab or campus building, not identifiable as one university | named buildings, crests, anything implying a partnership | "A clinical skills teaching room at a UK university" |
| `healthcare-education` | Home or About (optional) | "students studying library UK" | Black students studying in a UK library setting, natural light, faces not the subject | staged "our students" shots, graduation celebrations | "Students working at desks in a university library" |

Rules that apply to every subject slot: never imply a university partnership or show a real client; never use an image that suggests a course is open to international students when the taxonomy says it is not; alt text describes the frame, never who the people are; one image per page at most; WebP and JPEG derivatives at 480/960/1440 px are built by `php artisan smukn:images` and nothing ships un-optimised.

Record for each pick (in `brand/photos/manifest.json`; the build refuses entries without `licence` and `source_url`): `slug`, `page`, `intended_use` (the slot, e.g. "hero, right column"), `alt`, `credit` (photographer / Unsplash), `source_url`, `licence`, `focal`. Dimensions, filenames and optimisation status are written by the build to `public/images/photos/manifest.json`.

## Why not more
Every image costs bandwidth for readers on Nigerian mobile connections. The design system relies on typography and spacing for its premium feel; photography is an accent, not a layer on every page.
