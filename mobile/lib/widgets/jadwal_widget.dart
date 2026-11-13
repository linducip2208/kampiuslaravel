import 'package:flutter/material.dart';

/// Widget jadwal hari ini: kartu ringkas untuk Beranda + siap dipasang di
/// Home Widget native (Android) via kanal data yang sama.
class JadwalWidget extends StatelessWidget {
  final dynamic jadwal;
  final VoidCallback? onRefresh;
  const JadwalWidget({super.key, this.jadwal, this.onRefresh});

  static int countOf(dynamic jadwal) {
    if (jadwal is List) return jadwal.length;
    if (jadwal is Map) {
      final d = jadwal['data'];
      if (d is List) return d.length;
    }
    return 0;
  }

  /// Daftar kelas yang dinormalisasi dari berbagai bentuk payload API.
  static List<Map<String, String>> itemsOf(dynamic jadwal) {
    List raw;
    if (jadwal is List) {
      raw = jadwal;
    } else if (jadwal is Map && jadwal['data'] is List) {
      raw = jadwal['data'] as List;
    } else {
      return [];
    }
    return raw.map((e) {
      final m = e is Map ? e : <String, dynamic>{};
      return {
        'matakuliah': '${m['matakuliah'] ?? m['nama'] ?? m['kode_mk'] ?? '-'}',
        'jam': '${m['jam_mulai'] ?? m['jam'] ?? ''}${m['jam_selesai'] != null ? '–${m['jam_selesai']}' : ''}',
        'ruang': '${m['ruang'] ?? m['ruang_nama'] ?? ''}',
      };
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final items = itemsOf(jadwal);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(
                    child: Text('Jadwal hari ini', style: TextStyle(fontWeight: FontWeight.bold))),
                Text('${items.length} kelas'),
                if (onRefresh != null)
                  IconButton(icon: const Icon(Icons.refresh, size: 18), onPressed: onRefresh),
              ],
            ),
            const SizedBox(height: 4),
            if (items.isEmpty)
              const Text('Tidak ada kelas hari ini.', style: TextStyle(color: Colors.grey))
            else
              ...items.take(5).map((k) => ListTile(
                    dense: true,
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.schedule, size: 20),
                    title: Text('${k['matakuliah']}', style: const TextStyle(fontSize: 14)),
                    subtitle: Text('${k['jam']} ${k['ruang'] ?? ''}'.trim(),
                        style: const TextStyle(fontSize: 12)),
                  )),
          ],
        ),
      ),
    );
  }
}
