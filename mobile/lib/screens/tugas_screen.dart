import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../api_client.dart';

const _allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];

/// Daftar tugas + kumpul berkas via file picker native.
class TugasScreen extends StatefulWidget {
  const TugasScreen({super.key});

  @override
  State<TugasScreen> createState() => _TugasScreenState();
}

class _TugasScreenState extends State<TugasScreen> {
  dynamic data;
  String _info = '';
  int? _busyId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ApiClient.get('/tugas');
    if (mounted) setState(() => data = r['body']);
  }

  Future<void> _kumpul(int id) async {
    final files = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: _allowed,
    );
    if (files.isEmpty) return;
    final f = files.first;
    late final List<int> bytes;
    try {
      bytes = (await f.readAsBytes()).toList();
    } catch (_) {
      if (mounted) setState(() => _info = 'Berkas tidak terbaca.');
      return;
    }
    setState(() => _busyId = id);
    final r = await ApiClient.uploadTugas(id, f.name, bytes.toList());
    if (!mounted) return;
    setState(() {
      _busyId = null;
      final status = r['status'] as int;
      _info = (status == 201 || status == 200)
          ? 'Terkumpul: ${f.name}'
          : (status == 0 ? 'Offline — coba lagi nanti.' : '${r['body']['message'] ?? 'Gagal mengumpul'}');
    });
  }

  @override
  Widget build(BuildContext context) {
    final list = (data?['data'] ?? []) as List;
    return Scaffold(
      appBar: AppBar(title: const Text('Tugas')),
      body: data == null
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_info.isNotEmpty)
                    Card(child: Padding(padding: const EdgeInsets.all(12), child: Text(_info))),
                  if (list.isEmpty)
                    const Card(child: ListTile(title: Text('Belum ada tugas.')))
                  else
                    ...list.map((t) => Card(
                            child: ListTile(
                                title: Text('${t['judul'] ?? t['nama'] ?? 'Tugas #${t['id']}'}'),
                                subtitle: Text(
                                    'Deadline: ${t['deadline'] ?? t['batas'] ?? '-'} • ${_allowed.join(', ')}'),
                                trailing: _busyId == t['id']
                                    ? const SizedBox(
                                        width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                                    : IconButton(
                                        icon: const Icon(Icons.upload_file),
                                        onPressed: () => _kumpul((t['id'] as num).toInt()),
                                      )))),
                ],
              ),
            ),
    );
  }
}
