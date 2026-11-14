# DESIGN SYSTEM — KAMPUS ERP

Versi 2.0 (2026-11-14): shell terang + topbar + dashboard komposisi baru.

## Token warna (CSS variables, `:root` di layouts/tabler.blade.php)
- Primary `#1769F5`, Primary Dark `#1046A0`
- Navy `#101D35`, Navy Light `#172B4D`
- Cyan `#12C8E8`
- Background `#F5F7FB`, Surface `#FFFFFF`, Teks `#18263D`, Muted `#77849A`
- Success `#16A36A`, Warning `#E9A23B`, Danger `#DC4545`
- Border `#E6EBF3`

## Tipografi
- Font sistem Tabler (inherit), heading tebal navy `#10234B`, body `#18263D`.
- Hierarki: page-title (h2) → card-title (h3) → subheader/small muted.

## Spacing & radius
- Kartu: radius 16px, border 1px `#E8EEF7`, tanpa shadow kecuali hover (metric: 0 12px 28px rgba(23,105,245,.12)).
- Empty state: `.kampius-empty` (dashed border, ikon 52px, judul + ajakan).
- Grid: sistem Tabler `row/row-cards`, breakpoints 360 → 1920 (kolom `col-sm-6 col-lg-*`).

## Komponen
- `.kampius-sidebar` (264px, collapse 76px desktop, drawer + overlay mobile, persist localStorage).
- `.kampius-topbar` sticky: breadcrumb, pencarian global, badge periode, lonceng notifikasi + dropdown, menu akun.
- `.kampius-metric`: ikon 44px + angka 1.8rem + konteks + tautan detail.
- `.kampius-hero`: gradien navy→primary dengan badge peran dan CTA sesuai peran.
- `.kampius-funnel-bar`, `.kampius-queue-num`, `.kampius-empty`, `.kampius-period`.
- Breadcrumb di setiap halaman modul; `prefers-reduced-motion` mematikan semua transisi.
- Fokus keyboard: outline 2px primary pada semua kontrol interaktif.

## Aturan
- Semua angka operasional = query real (dilarang dummy). Lihat `docs/FINAL_AUDIT.md`.
- Semua aksi destruktif memakai konfirmasi (`wire:confirm`/native confirm).
- Semua aksi async memakai indikator loading + tombol disabled.
- Ikon: inline SVG stroke-2 konsisten 20px (sidebar) / 16-18px (topbar).
