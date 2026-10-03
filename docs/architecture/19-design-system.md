# 19. Design system

Status: ARCHITECTURE / DESIGN. Derived from brief sections 18, 42, 76, 87, 88, 115. The goal: a Nigerian student's first impression is "an established UK medical-education organisation", achieved through typography, consistency, depth, evidence and polish — never through invented history.

## 19.1 Visual direction

**Editorial, clinical, British, calm.** Think the restraint of a Royal College publication crossed with the clarity of GOV.UK forms: strong serif headlines, highly legible humanist sans for UI and body, generous whitespace, one deep institutional colour, one warm accent used sparingly for the single primary action, data presented in disciplined tables and cards with visible sources.

Explicitly avoided: stock "happy students with laptops" hero collages; gradients and glassmorphism; more than one accent colour; icon soup; carousels; fake badges; university logos (we have no agreements — text names only, `logo_policy=text_only`).

## 19.2 Typography

| Role | Face (self-hosted, open licence) | Fallback | Notes |
|---|---|---|---|
| Display / H1–H2 | **Fraunces** (variable, optical sizes) or **Source Serif 4** | Georgia, serif | serif signals institution and longevity; use opsz for crisp small sizes |
| Body / UI / forms / tables | **Inter** (variable) or **Public Sans** | system-ui, sans-serif | Public Sans is the US-web-design-system face; Inter is more neutral. Pick one; never both |
| Monospace (application numbers, codes) | **JetBrains Mono** | ui-monospace | application numbers, UCAS codes, hashes |

Scale (rem; fluid with `clamp`): 0.75 · 0.875 · 1 · 1.125 · 1.25 · 1.5 · 1.875 · 2.25 · 3 · 3.75. Body 1rem/1.6; tables 0.875rem/1.45; H1 `clamp(2rem, 1.2rem + 3vw, 3.75rem)` with letter-spacing −0.01em. Max line length 68ch for prose.

## 19.3 Colour tokens

```
--c-ink-900: #0F1E2E   (near-black navy; headings, body on light)
--c-ink-700: #2A3B4D   (body text secondary)
--c-ink-500: #5B6B7B   (muted text, metadata)
--c-ink-300: #B8C2CC   (borders)
--c-ink-100: #EEF2F5   (table stripes, subtle fills)
--c-paper:   #FFFFFF
--c-paper-warm: #FAF8F4 (page background alternative; editorial warmth)
--c-primary-700: #0B3D5C  (deep institutional blue — links, nav, headings accent)
--c-primary-500: #15587F
--c-primary-100: #E3EEF5
--c-accent-600: #B8322F   (muted medical red / UK red — ONLY for the primary CTA and critical alerts)
--c-accent-700: #962A27   (hover)
--c-success-600: #1F7A4D  (ACCEPTED, verified)
--c-warning-600: #9A6A00  (REQUIRED, review due)
--c-danger-600:  #B3261E  (REJECTED)
--c-info-600:    #2F5F8F  (UNDER_REVIEW)
--c-nigeria-green: #008751 (used once: a hairline in the Nigeria context strip, never as a theme)
```
All text/background pairs ≥ 4.5:1 (checked). Dark mode: not in v1 for the public site (editorial feel on paper); portal gets `prefers-color-scheme` later.

## 19.4 Spacing, grid, radius, elevation

Spacing scale 4px base: 4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 · 96 · 128. Container 1200px; 12-col grid on ≥1024px; single column below 768px with 16px gutters. Radius: 4px (inputs, chips), 8px (cards), 999px (pills/badges). Elevation: borders over shadows; one soft shadow `0 1px 2px rgba(15,30,46,.06), 0 8px 24px rgba(15,30,46,.06)` for raised cards only.

## 19.5 Components (Blade partials; names fixed)

| Component | Variants / rules |
|---|---|
| `x-button` | `primary` (accent red, white text, uppercase small-caps tracking +0.04em, "APPLY ONLINE"), `secondary` (navy outline), `tertiary` (text link with arrow). Only one primary per viewport. Min 44px tap target |
| `x-card` | default, `school` (name, city, course, international yes/no, fee + year + verified chip, test chip, two actions), `fee`, `tier` (deliverables list, price, gate note) |
| `x-source-box` | "Official source" label, organisation, link with external icon, `Last verified {date}` chip; colour-coded by verification status; never hides `NOT PUBLISHED` |
| `x-verified-badge` | VERIFIED (green), VERIFY-ON-PAGE (grey, internal only), REVIEW DUE (amber, internal only), NOT PUBLISHED (grey outline, public) |
| `x-doc-status` | chip per 14.2 status with icon and plain-English label |
| `x-progress` | ring (dashboard) and bar (steps "Step 3 of 9") |
| `x-steps` | application stepper, current/complete/locked |
| `x-table` | responsive: stacks into definition lists below 640px; sticky first column for compare |
| `x-alert` | info/warning/danger/success; used for "No admission is guaranteed" style notices |
| `x-breadcrumbs` | per 18.8 |
| `x-cta-band` | contextual CTA with one line of context copy + primary button (brief 105 patterns) |
| `x-faq` | accordion with FAQPage markup; links inside answers |
| `x-nav` | desktop: 6 items + Apply Online button; mobile: full-screen sheet, Apply Online pinned |
| `x-footer` | company identity, contact, policies, "Not an agent of any university unless stated", last-reviewed site-wide note |
| `x-floating-cta` | bottom-right on public pages only, hides when a form is focused or within 200px of a footer/CTA band, hidden in portal |
| `x-related` | "You may also need" 4–6 curated links |
| `x-filter-bar` | directory filters; works without JS via GET form |
| `x-compare-table` | up to 4 courses; factual rows only; no "winner" styling |

## 19.6 Forms

Labels above fields, always visible; helper text under label; errors inline + summary at top; input height 48px on mobile; grouped structured inputs for grades (subject select + grade select rows, add/remove); file dropzone with camera capture on mobile; autosave indicator top-right of the step; "Save and continue later" always visible; progress "Step n of N" and the step purpose sentence.

## 19.7 Imagery

Unsplash (licence recorded) of real UK academic/clinical learning settings and Nigerian students studying; captions never attribute a photo to a specific university unless it is that university's own press image with permission. Prefer photography with neutral palettes; apply a consistent slight navy duotone on hero images for cohesion. No AI-generated imagery for places.

## 19.8 Voice and microcopy

British English. Precise, warm, unhurried. We say "published requirement", "official source", "last verified", "confirm with the university". We never say "best", "guaranteed", "top-ranked", "partner" (without an agreement), "fast-track". CTA copy patterns: fees → "Know the costs. Ready to check your application? APPLY ONLINE"; requirements → "Not sure your Nigerian qualifications meet the requirements? CHECK YOUR ELIGIBILITY"; school → "Ready to begin your application? APPLY ONLINE".

## 19.9 Accessibility and QA checklist per component

Contrast, focus ring (2px navy offset 2px), keyboard order, aria for status chips (`aria-label="Document status: under review"`), reduced-motion respected, 200% zoom layout, screen-reader-only text for icon buttons.
