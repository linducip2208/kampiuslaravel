import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../api_client.dart';

/// Tagihan + VA dengan deep-link `simak://va?ref=...&no=...` sehingga
/// aplikasi bank/e-wallet bisa dibuka dari tombol bayar.
/// Deep-link VA: `simak://va?ref=...&no=...` untuk dibuka aplikasi bank/e-wallet.
String vaDeepLink(String reference, String vaNumber) =>
    'simak://va?ref=${Uri.encodeComponent(reference)}&no=${Uri.encodeComponent(vaNumber)}';

class TagihanScreen extends StatefulWidget {
  const TagihanScreen({super.key});

  @override
  State<TagihanScreen> createState() => _TagihanScreenState();
}

class _TagihanScreenState extends State<TagihanScreen> {
  dynamic data;
  String _info = '';
  String _deepLink = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ApiClient.get('/tagihan');
    if (mounted) setState(() => data = r['body']);
  }

  Future<void> _buatVa(int id) async {
    final r = await ApiClient.post('/tagihan/$id/va', {});
    if (!mounted) return;
    setState(() {
      if (r['status'] == 201) {
        final ref = '${r['body']['reference']}';
        final no = '${r['body']['va_number']}';
        _deepLink = vaDeepLink(ref, no);
        _info = 'VA: $no (Ref $ref)';
      } else {
        _info = (r['body']['message'] ?? 'Gagal membuat VA').toString();
      }
    });
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final list = (data?['data'] ?? []) as List;
    return Scaffold(
      appBar: AppBar(title: const Text('Tagihan & VA')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_info.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_info),
                    if (_deepLink.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      SelectableText(_deepLink, style: const TextStyle(fontSize: 12)),
                      Row(
                        children: [
                          TextButton.icon(
                            onPressed: () {
                              Clipboard.setData(ClipboardData(text: _deepLink));
                              ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Deep-link VA disalin.')));
                            },
                            icon: const Icon(Icons.copy, size: 16),
                            label: const Text('Salin VA'),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ...list.map((t) => Card(
                  child: ListTile(
                      title: Text('${t['jenis']} — Rp ${t['nominal']}'),
                      subtitle: Text('Status: ${t['status']}'),
                      trailing: t['status'] != 'lunas'
                          ? ElevatedButton(onPressed: () => _buatVa(t['id'] as int), child: const Text('Buat VA'))
                          : null))),
        ],
      ),
    );
  }
}
