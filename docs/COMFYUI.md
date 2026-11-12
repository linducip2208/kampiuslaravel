# Generate Gambar — ComfyUI + WAN 2.1

Semua generate gambar di SIMAK memakai **server ComfyUI eksternal** (default model **WAN 2.1**).
Laravel hanya mengirim workflow API-format + menyimpan hasilnya. Tidak ada API key/vendor
yang di-hardcode — semua via `.env` / `config/comfyui.php`.

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
