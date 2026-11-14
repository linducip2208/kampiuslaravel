# SECURITY_AUDIT (ringkas, berbasis bukti — detail: docs/SECURITY.md)

## Mekanisme aktif
- RBAC Spatie (6 role) + middleware `role:` di semua grup sensitif; RouteGuardTest (17 rute, 60+ asersi) mengunci regresi.
- Owner-scope server-side: KHS/transkrip/KTM/kuitansi (IDOR 403 teruji), tagihan/pembayaran owner-atau-keuangan, KRS bimbingan (dosen), nilai per-kelas (dosen pengampu saja), tugas-kumpul anti-spoof (resolve ulang dari Auth), KumpulTugas API wajib mahasiswaId.
- Self-delete ditolak (UserData + DosenData, teruji).
- Upload: mime+size di semua titik (bukti 5MB, CSV 2MB, tugas 10MB, PMB berkas); path traversal dokumen ditutup whitelist + tes traversal.
- Webhook HMAC-SHA256 + secret min 16 (503 bila belum dikonfigurasi); callback payment tolak nominal mismatch.
- Session: regenerate saat login, invalidate+rotate saat logout; cookie httpOnly, sameSite lax; login throttle 10/menit.
- Password: min 8, hash bcrypt, ganti butuh password saat ini.

## Ditemukan & diperbaiki sesi ini
- VA untuk tagihan lunas (422), authorizeTagihan sempit, KumpulTugas spoof, KunciNilai buka-oleh-siapa-saja, ImporCsv timpa nominal berbayar, Api Tugas tanpa scope, list KRS dashboard tak-scoped, DosenData hapus-diri.

## Sisa jujur (bukan blocker rilis, butuh di luar kode/atrofi)
- Tanpa MFA, tanpa WAF/rate-limit global, tanpa pentest pihak ketiga, tanpa pemindai malware upload.
- Kredensial gateway/PDDikti/FCM/WA tidak ada di repo (benar) → integrasi eksternal belum terverifikasi hidup.

## Perintah operasional
- APP_ENV=production, APP_DEBUG=false, SESSION_SECURE_COOKIE=true (lihat DEPLOYMENT).
- Audit trail: tabel audit_logs (akses via admin.audit, role superadmin/admin_prodi/pimpinan).
