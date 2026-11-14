"""Komposit logo KAMPUS v2: emblem WAN 2.1 + wordmark tajam via PIL."""
import sys
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageChops

NAVY = (16, 29, 53)
MUTED = (119, 132, 153)
ACCENT = (245, 158, 11)
WHITE = (255, 255, 255)
ARIAL_BD = r'C:\Windows\Fonts\arialbd.ttf'
ARIAL = r'C:\Windows\Fonts\arial.ttf'

def trim_white(im, thresh=248, margin=40):
    bg = Image.new('RGB', im.size, WHITE)
    diff = ImageChops.difference(im, bg).convert('L')
    bbox = diff.point(lambda v: 255 if v > (255 - thresh + 200) else 0).getbbox()
    if not bbox:
        return im
    l, u, r, b = bbox
    l = max(0, l - margin); u = max(0, u - margin)
    r = min(im.size[0], r + margin); b = min(im.size[1], b + margin)
    return im.crop((l, u, r, b))

def tracked_text(draw, xy, text, font, fill, tracking=0):
    x, y = xy
    for ch in text:
        draw.text((x, y), ch, font=font, fill=fill)
        x += draw.textlength(ch, font=font) + tracking
    return x

def main(emblem_path, out_dir):
    out = Path(out_dir); out.mkdir(parents=True, exist_ok=True)
    emblem = trim_white(Image.open(emblem_path).convert('RGB'))
    side = min(emblem.size)
    w, h = emblem.size
    emblem_sq = emblem.crop(((w - side)//2, (h - side)//2, (w + side)//2, (h + side)//2))
    mark = emblem_sq.resize((256, 256), Image.LANCZOS)
    mark.save(out / 'logo-kampus-mark.png')

    H, pad = 400, 48
    font_word = ImageFont.truetype(ARIAL_BD, 150)
    font_sub = ImageFont.truetype(ARIAL, 50)
    tmp = Image.new('RGB', (10, 10), WHITE)
    d = ImageDraw.Draw(tmp)
    word, sub = 'KAMPUS', 'UNIVERSITY ERP'
    ww = sum(d.textlength(c, font=font_word) for c in word) + 10 * (len(word) - 1)
    sw = sum(d.textlength(c, font=font_sub) for c in sub) + 16 * (len(sub) - 1)
    text_w = int(max(ww, sw))
    div_gap = 48
    canvas_w = pad*2 + 300 + div_gap + 2 + div_gap + text_w
    canvas = Image.new('RGB', (canvas_w, H), WHITE)
    d = ImageDraw.Draw(canvas)
    canvas.paste(emblem_sq.resize((300, 300), Image.LANCZOS), (pad, (H-300)//2))
    # divider vertikal tipis
    dx = pad + 300 + div_gap
    d.line([(dx, 80), (dx, H-80)], fill=(226, 232, 240), width=2)
    tx = dx + div_gap
    tracked_text(d, (tx, 78), word, font_word, NAVY, tracking=10)
    # aksen orange pendek
    d.rectangle([(tx+4, 238), (tx+84, 244)], fill=ACCENT)
    tracked_text(d, (tx+4, 258), sub, font_sub, MUTED, tracking=16)
    canvas.save(out / 'logo-kampus.png')
    # kompatibel: timpa file lama agar URL lama ikut benar
    canvas.save(out / 'logo-kampius.png')
    Image.open(out / 'logo-kampus-mark.png').save(out / 'logo-kampius-mark.png')
    print('OK:', out / 'logo-kampus.png')

if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
