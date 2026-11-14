# Generate Gambar — ComfyUI WAN 2.1 (server lokal, tanpa API eksternal)

Semua generate gambar di KAMPIUS memakai **server ComfyUI lokal**
(`http://127.0.0.1:8188`, model WAN 2.1). Laravel hanya mengirim workflow
API-format lalu menyimpan hasilnya. Tidak ada kredensial/API key di repo —
semua via `.env` / halaman admin Integrasi.

## Status model di server lokal (D:\ComfyUI, RTX 4060 8GB) — UPDATE 2026-11-14

- WAN 2.1 T2V 1.3B fp16 (`diffusion_models/wan2.1_t2v_1.3B_fp16.safetensors`, UNETLoader `weight_dtype=fp8_e4m3fn`)
- Text encoder BENAR: `umt5_xxl_fp8_e4m3fn_scaled.safetensors` (6.27 GB di `D:\AI_Models\text_encoders`, ikut ter-index via extra paths).
  Jangan pakai `umt5-xxl-enc-fp8_e4m3fn.safetensors` (varian city96): seluruh bobotnya tidak terpetakan ("clip missing") sehingga encode gagal dengan `Cannot copy out of meta tensor`.
- VAE: `wan_2.1_vae.safetensors`.
- Workflow still-image: `storage/app/comfyui/wan21_still_api.json` (T2V length=13 → `LatentFromBatch` ambil frame pertama → `SaveImage`). Server dijalankan dengan `--lowvram`.
- Dependensi fp8: `torchao` 0.18.0 terinstal di venv (dependensi komputasi fp8 standar; server harus di-restart setelah instalasi agar terdeteksi).
- Fallback SD1.5 TIDAK LAGI DIPAKAI — WAN 2.1 terverifikasi end-to-end 2026-11-14:
  smoke 512px/2-step lolos ±15 dtk; batch produksi 6 aset selesai ±25 mnt
  (lihat manifest `storage/app/public/generated/manifest-kampius.json`).
- Cara jalan server: `D:\ComfyUI\venv\Scripts\python.exe D:\ComfyUI\main.py --listen 127.0.0.1 --port 8188 --lowvram --preview-method none`
  (server harus di-restart setiap instal paket venv baru agar terdeteksi).
- Aset produksi tersimpan di `public/img/` (`logo-kampius*.png`, `*-wan.png`);
  skrip batch: `gen-assets.php`, komposit logo: `make-logo.py` (PIL, font Arial sistem).

## 1. Siapkan ComfyUI + WAN 2.1 (sekali saja)

1. Install ComfyUI portable, jalankan → `http://127.0.0.1:8188`.
2. Download model WAN 2.1 (T2I atau T2V) + text encoder + VAE-nya ke
   `ComfyUI/models/diffusion_models|text_encoders|vae` (lihat halaman rilis resmi WAN 2.1).
3. Di ComfyUI: aktifkan **Dev mode → menu → Export (API Format)**, simpan sebagai mis.
   `storage/app/comfyui/wan21_t2i_api.json`.
4. Catat node ID untuk: prompt positif, negative, sampler/seed, ukuran latent.
   Isi ke `.env`:

```env
COMFYUI_URL=http://127.0.0.1:8188
COMFYUI_MODEL=wan2.1
COMFYUI_WORKFLOW_FILE=/full/path/storage/app/comfyui/wan21_t2i_api.json
COMFYUI_PROMPT_NODE=6
COMFYUI_NEGATIVE_NODE=7
COMFYUI_SEED_NODE=3
COMFYUI_SIZE_NODE=5
```

Tanpa `COMFYUI_WORKFLOW_FILE`, app memakai template T2I generik bawaan
(`CheckpointLoaderSimple` dengan `ckpt_name = COMFYUI_MODEL`) — cukup untuk testing koneksi.

## 2. Pakai

Web (login dulu): **Generate Gambar** di sidebar → isi prompt → antre.
Atau CLI:

```bash
php artisan comfyui:generate "mahasiswa wisuda di kampus, fotorealistik" --width 832 --height 1216
php artisan comfyui:generate "poster PMB jurusan TI" --pengumuman=1 --queue
php artisan queue:work   # proses antrean GenerateMediaJob
```

Hasil tersimpan di `storage/app/public/generated/` (akses via `asset('storage/...')`).
Perlu `php artisan storage:link` sekali agar `storage/` publik bisa diakses.

## 3. Catatan WAN 2.1

WAN 2.1 aslinya model **text-to-video / image-to-video**. Untuk still image ada 2 opsi:
- (a) pakai workflow T2V WAN 2.1 lalu ambil frame pertama (atur di workflow export kamu),
- (b) pakai checkpoint T2I (SD1.5/SDXL) untuk gambar + WAN 2.1 khusus saat butuh video/animasi.

Service `ComfyUIService` agnostik terhadap workflow — ganti file JSON + node ID saja.
