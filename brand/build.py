#!/usr/bin/env python3
"""Builds every StudyMedicineUKNigeria brand asset from vector definitions.

    python3 brand/build.py

Outputs: brand/logo/*.svg|png, brand/favicon/*, brand/social/*, brand/email/*.
Requires: fontTools, brotli, Pillow, rsvg-convert.
"""
import os, subprocess, json
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
F = lambda *p: os.path.join(HERE, *p)

# ---------- palette (from docs/architecture/19-design-system.md) ----------
NAVY = "#0B3D5C"; NAVY_2 = "#15587F"; INK = "#0F1E2E"; INK_500 = "#5B6B7B"
PAPER = "#FFFFFF"; RED = "#B8322F"; SALTIRE = "#174B70"

# ---------- fonts ----------
def load(path, **loc):
    f = TTFont(path)
    if "fvar" in f:
        f = instancer.instantiateVariableFont(f, loc, inplace=False)
    return f

SERIF = load(F("fonts/SourceSerif4-normal-latin.woff2"), opsz=40, wght=600)
SANS  = load(F("fonts/Inter-normal-latin.woff2"), wght=560)

def text_path(font, text, size, x=0, y=0, tracking=0.0):
    """Return (svg path d, advance width) for `text` set at `size` px with baseline at y."""
    upm = font["head"].unitsPerEm; scale = size / upm
    cmap = font.getBestCmap(); gs = font.getGlyphSet(); hmtx = font["hmtx"]
    kern = {}
    # flat kern table if present (GPOS pair kerning is ignored; manual pairs below)
    if "kern" in font:
        for st in font["kern"].kernTables:
            kern.update(st.kernTable)
    d = []; pen_x = x; prev = None
    for ch in text:
        g = cmap.get(ord(ch))
        if g is None: pen_x += size * 0.25; prev = None; continue
        if prev is not None:
            pen_x += kern.get((prev, g), 0) * scale
        sp = SVGPathPen(gs)
        tp = TransformPen(sp, (scale, 0, 0, -scale, pen_x, y))
        gs[g].draw(tp)
        d.append(sp.getCommands())
        pen_x += hmtx[g][0] * scale + tracking * size
        prev = g
    return " ".join(d), pen_x - x

# ---------- the symbol ----------
def symbol(size=64, tile=True, fg=PAPER, tile_fill=NAVY, red=RED, saltire=SALTIRE, radius=14):
    """Rod of Asclepius (two-loop serpent on a heavier staff) over a quiet saltire. Grid 64x64."""
    s = size / 64.0
    g = []
    if tile:
        g.append(f'<rect width="{size}" height="{size}" rx="{radius*s}" fill="{tile_fill}"/>')
        g.append(f'<path d="M13.5 15.5 L50.5 52.5 M50.5 15.5 L13.5 52.5" stroke="{saltire}" stroke-width="2.4" stroke-linecap="round" fill="none" transform="scale({s})"/>')
    # serpent segments: A and C pass BEHIND the staff, B passes IN FRONT
    segA = "M23 19 C23 26 41 23 41 30"
    segB = "M41 30 C41 37 23 34 23 41"
    segC = "M23 41 C23 48 41 45 41 52 C41 54.2 38.6 55.4 36.2 54.8"
    sw = 3.6; staff_w = 5.0
    g.append(f'<g transform="scale({s})" fill="none" stroke-linecap="round" stroke-linejoin="round">')
    g.append(f'  <path d="{segA} {segC}" stroke="{fg}" stroke-width="{sw}"/>')
    if tile:
        g.append(f'  <path d="M32 16 L32 56" stroke="{tile_fill}" stroke-width="{staff_w + 2.6}"/>')
    g.append(f'  <path d="M32 16 L32 56" stroke="{fg}" stroke-width="{staff_w}"/>')
    g.append(f'  <path d="{segB}" stroke="{fg}" stroke-width="{sw}"/>')
    g.append(f'  <circle cx="23" cy="18.6" r="2.9" fill="{fg}" stroke="none"/>')   # serpent head
    g.append(f'  <circle cx="32" cy="11.2" r="3.5" fill="{red}" stroke="none"/>')  # staff head: the single red point
    g.append('</g>')
    return "\n".join(g)

def svg(w, h, body, bg=None):
    b = f'<rect width="{w}" height="{h}" fill="{bg}"/>' if bg else ""
    return (f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}" '
            f'role="img" aria-label="Study Medicine UK Nigeria">\n{b}\n{body}\n</svg>\n')

# ---------- lockups ----------
def wordmark(x, y, ink=INK, sub=INK_500, size=34):
    """Two-line wordmark: 'Study Medicine' (serif) over 'UK · NIGERIA' (sans, tracked caps). Returns (markup, width, height)."""
    d1, w1 = text_path(SERIF, "Study Medicine", size, x, y)
    sub_size = size * 0.36
    d2, w2 = text_path(SANS, "UK · NIGERIA", sub_size, x + size*0.03, y + size*0.62, tracking=0.16)
    m = f'<path d="{d1}" fill="{ink}"/>\n<path d="{d2}" fill="{sub}"/>'
    return m, max(w1, w2), size*0.62 + sub_size*0.8

def lockup_horizontal(ink=INK, sub=INK_500, bg=None, symbol_tile=NAVY, fg=PAPER, saltire=SALTIRE):
    S = 64; pad = 0; gap = 20
    wm, ww, wh = wordmark(S + gap, 38, ink, sub, size=34)
    W = int(S + gap + ww + 2); H = S
    body = f'<g>{symbol(S, tile_fill=symbol_tile, fg=fg, saltire=saltire)}</g>\n{wm}'
    return svg(W, H, body, bg), W, H

def lockup_stacked(ink=INK, sub=INK_500, bg=None):
    S = 96
    wm, ww, wh = wordmark(0, 0, ink, sub, size=40)
    W = int(max(ww, S) + 8); H = int(S + 28 + wh + 10)
    sx = (W - S) / 2
    wmx = (W - ww) / 2
    wm2, _, _ = wordmark(wmx, S + 28 + 30, ink, sub, size=40)
    body = f'<g transform="translate({sx},0)">{symbol(S)}</g>\n{wm2}'
    return svg(W, H, body, bg), W, H

def wordmark_only(ink=INK, sub=INK_500):
    wm, ww, wh = wordmark(0, 34, ink, sub, size=34)
    return svg(int(ww + 2), 60, wm), int(ww + 2), 60

def write(path, content):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w") as fh: fh.write(content)

def png(svg_path, out, w=None, h=None, bg=None):
    cmd = ["rsvg-convert", svg_path, "-o", out]
    if w: cmd += ["-w", str(w)]
    if h: cmd += ["-h", str(h)]
    if bg: cmd += ["-b", bg]
    subprocess.run(cmd, check=True)

def main():
    # --- logo ---
    s, W, H = lockup_horizontal();                      write(F("logo/smukn-logo-horizontal.svg"), s)
    s2, _, _ = lockup_horizontal(ink=PAPER, sub="#B9CBDA", symbol_tile=PAPER, fg=NAVY, saltire="#D7E3EC"); write(F("logo/smukn-logo-horizontal-reverse.svg"), s2)
    st, SW, SH = lockup_stacked();                      write(F("logo/smukn-logo-stacked.svg"), st)
    wo, _, _ = wordmark_only();                         write(F("logo/smukn-wordmark.svg"), wo)
    write(F("logo/smukn-symbol.svg"), svg(64, 64, symbol(64)))
    write(F("logo/smukn-symbol-mono.svg"), svg(64, 64, symbol(64, tile=False, fg=INK, red=INK)))
    for name, w in [("smukn-logo-horizontal", 1200), ("smukn-logo-horizontal-reverse", 1200), ("smukn-logo-stacked", 800)]:
        png(F(f"logo/{name}.svg"), F(f"logo/{name}@2x.png"), w=w)
    png(F("logo/smukn-logo-horizontal.svg"), F("logo/smukn-logo-horizontal.png"), w=W*2)
    png(F("logo/smukn-symbol.svg"), F("logo/smukn-symbol-512.png"), w=512)
    # preview sheet on light & dark
    prev = svg(1400, 760, f'''
<rect width="1400" height="380" fill="#FAF8F4"/><rect y="380" width="1400" height="380" fill="{NAVY}"/>
<g transform="translate(60,80) scale(2.2)">{lockup_horizontal()[0].split(">",1)[1].rsplit("</svg>",1)[0]}</g>
<g transform="translate(60,460) scale(2.2)">{s2.split(">",1)[1].rsplit("</svg>",1)[0]}</g>
<g transform="translate(1000,60) scale(3.2)">{symbol(64)}</g>
<g transform="translate(1000,560)">{symbol(16)}</g><g transform="translate(1030,556)">{symbol(24)}</g><g transform="translate(1070,552)">{symbol(32)}</g><g transform="translate(1120,544)">{symbol(48)}</g><g transform="translate(1190,520)">{symbol(72)}</g>
''')
    write(F("logo/preview-sheet.svg"), prev); png(F("logo/preview-sheet.svg"), F("logo/preview-sheet.png"))

    # --- favicon set ---
    write(F("favicon/favicon.svg"), svg(64, 64, symbol(64)))
    for n in (16, 32, 48, 180, 192, 512):
        png(F("favicon/favicon.svg"), F(f"favicon/icon-{n}.png"), w=n, h=n)
    # apple touch: opaque navy, square (iOS masks the corners itself)
    write(F("favicon/apple-touch.svg"), svg(180, 180, symbol(180, radius=0)))
    png(F("favicon/apple-touch.svg"), F("favicon/apple-touch-icon.png"), w=180, h=180)
    # maskable: safe zone = central 80%; pad the symbol inside a full-bleed navy square
    write(F("favicon/maskable.svg"), svg(512, 512, f'<rect width="512" height="512" fill="{NAVY}"/><g transform="translate(76,76)">{symbol(360, radius=0)}</g>'))
    png(F("favicon/maskable.svg"), F("favicon/maskable-512.png"), w=512, h=512)
    ico = [Image.open(F(f"favicon/icon-{n}.png")).convert("RGBA") for n in (16, 32, 48)]
    ico[0].save(F("favicon/favicon.ico"), format="ICO", sizes=[(16,16),(32,32),(48,48)], append_images=ico[1:])
    with open(F("favicon/site.webmanifest"), "w") as fh:
        json.dump({"name": "Study Medicine UK Nigeria", "short_name": "SMUKN", "start_url": "/",
                   "display": "standalone", "background_color": PAPER, "theme_color": NAVY,
                   "icons": [{"src": "/icon-192.png", "sizes": "192x192", "type": "image/png"},
                             {"src": "/icon-512.png", "sizes": "512x512", "type": "image/png"},
                             {"src": "/maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable"}]}, fh, indent=2)

    # --- social / OG default 1200×630 ---
    wm, ww, wh = wordmark(0, 0, PAPER, "#B9CBDA", size=64)
    wmk, _, _ = wordmark(330, 300, PAPER, "#B9CBDA", size=64)
    tag, tw = text_path(SERIF, "Evidence. Guidance. Application.", 36, 330, 392)
    url, uw = text_path(SANS, "studymedicineuknigeria.com", 22, 330, 560, tracking=0.02)
    og = svg(1200, 630, f'''
<rect width="1200" height="630" fill="{NAVY}"/>
<path d="M0 0 L1200 630" stroke="{SALTIRE}" stroke-width="1" opacity="0.6"/>
<path d="M1200 0 L0 630" stroke="{SALTIRE}" stroke-width="1" opacity="0.6"/>
<rect x="0" y="0" width="1200" height="630" fill="url(#g)"/>
<defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{NAVY}" stop-opacity="0"/><stop offset="1" stop-color="#072A40" stop-opacity="0.9"/></linearGradient></defs>
<g transform="translate(100,215)">{symbol(200, radius=44)}</g>
{wmk}
<path d="{tag}" fill="#E8EEF3"/>
<path d="{url}" fill="#9DB7CB"/>
<rect x="330" y="470" width="64" height="3" fill="{RED}"/>
''')
    write(F("social/og-default.svg"), og); png(F("social/og-default.svg"), F("social/og-default.png"), w=1200, h=630)
    # square avatar for social profiles
    write(F("social/avatar-1024.svg"), svg(1024, 1024, symbol(1024, radius=0)))
    png(F("social/avatar-1024.svg"), F("social/avatar-1024.png"), w=1024, h=1024)

    # --- email header 600×120 (light, retina 1200×240) ---
    s3, W3, H3 = lockup_horizontal()
    inner = s3.split(">",1)[1].rsplit("</svg>",1)[0]
    em = svg(600, 120, f'<rect width="600" height="120" fill="{PAPER}"/><g transform="translate(24,28)">{inner}</g><rect x="0" y="118" width="600" height="2" fill="{NAVY}"/>')
    write(F("email/email-header.svg"), em); png(F("email/email-header.svg"), F("email/email-header@2x.png"), w=1200, h=240)
    print("built")

if __name__ == "__main__":
    main()
