"""Komposit logo KAMPIUS: emblem AI + wordmark tajam via PIL.
Pakai: <venv python> make-logo.py <emblem_png> <out_dir>
Hasil: logo-kampius.png (lockup horizontal, bg putih), logo-kampius-mark.png (emblem saja).
"""
import sys
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

NAVY = (16, 29, 53)
MUTED = (119, 132, 153)
WHITE = (255, 255, 255)
ARIAL_BD = r'C:\Windows\Fonts\arialbd.ttf'
ARIAL = r'C:\Windows\Fonts\arial.ttf'


def tracked_text(draw, xy, text, font, fill, tracking=0):
    x, y = xy
    for ch in text:
        draw.text((x, y), ch, font=font, fill=fill)
        x += draw.textlength(ch, font=font) + tracking
    return x


def main(emblem_path: str, out_dir: str) -> None:
    out = Path(out_dir)
    out.mkdir(parents=True, exist_ok=True)

    emblem = Image.open(emblem_path).convert('RGB')
    # center-crop square
    w, h = emblem.size
    side = min(w, h)
    emblem = emblem.crop(((w - side) // 2, (h - side) // 2, (w + side) // 2, (h + side) // 2))

    mark = emblem.resize((256, 256), Image.LANCZOS)
    mark.save(out / 'logo-kampius-mark.png')

    # Lockup horizontal: emblem + wordmark
    H, pad = 400, 48
    font_word = ImageFont.truetype(ARIAL_BD, 150)
    font_sub = ImageFont.truetype(ARIAL, 52)
    tmp = Image.new('RGB', (10, 10), WHITE)
    d = ImageDraw.Draw(tmp)
    word = 'KAMPIUS'
    sub = 'UNIVERSITY ERP'
    ww = sum(d.textlength(c, font=font_word) for c in word) + 8 * (len(word) - 1)
    sw = sum(d.textlength(c, font=font_sub) for c in sub) + 14 * (len(sub) - 1)
    text_w = int(max(ww, sw))
    canvas = Image.new('RGB', (pad * 2 + 300 + 48 + text_w, H), WHITE)
    d = ImageDraw.Draw(canvas)
    canvas.paste(emblem.resize((300, 300), Image.LANCZOS), (pad, (H - 300) // 2))
    tx = pad + 300 + 48
    tracked_text(d, (tx, 78), word, font_word, NAVY, tracking=8)
    tracked_text(d, (tx + 4, 250), sub, font_sub, MUTED, tracking=14)
    canvas.save(out / 'logo-kampius.png')
    print('OK:', out / 'logo-kampius.png', out / 'logo-kampius-mark.png')


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
