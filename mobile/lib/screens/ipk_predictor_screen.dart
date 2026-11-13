import 'package:flutter/material.dart';

import '../api_client.dart';
import '../widgets/ipk_chart.dart';

/// Prediktor IPK: tren per semester + simulasi nilai rencana (what-if).
class IpkPredictorScreen extends StatefulWidget {
  const IpkPredictorScreen({super.key});

  @override
  State<IpkPredictorScreen> createState() => _IpkPredictorScreenState();
}

class _IpkPredictorScreenState extends State<IpkPredictorScreen> {
  dynamic data;
  final _rencana = <Map<String, String>>[];
  final _huruf = TextEditingController(text: 'A');
  final _sks = TextEditingController(text: '3');

  @override
  void initState() {
    super.initState();
    ApiClient.get('/nilai-ringkas').then((r) => mounted ? setState(() => data = r['body']) : null);
  }

  List<Map<String, dynamic>> get _riwayat {
    final raw = (data?['data']?['nilai'] ?? data?['nilai'] ?? []) as List;
    return raw.map((n) {
      final m = n is Map ? n : <String, dynamic>{};
      final sks = m['sks'] ?? m['bobot_sks'];
      return {
        'huruf': '${m['huruf'] ?? 'B'}',
        'sks': sks is num ? sks.toInt() : int.tryParse('$sks') ?? 0,
      };
    }).toList();
  }

  List<double> get _perSemester {
    final raw = (data?['data']?['per_semester'] ?? []) as List;
    return raw.map((e) => double.tryParse('$e') ?? 0.0).toList();
  }

  @override
  Widget build(BuildContext context) {
    final ipk = double.tryParse('${data?['data']?['ipk'] ?? data?['ipk'] ?? 0}') ?? 0.0;
    final prediksi = IpkMath.prediksi(_riwayat, _rencana);
    return Scaffold(
      appBar: AppBar(title: const Text('Prediktor IPK')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(child: ListTile(title: const Text('IPK saat ini'), trailing: Text(ipk.toStringAsFixed(2)))),
          IpkChart(perSemester: _perSemester.isEmpty ? [ipk] : _perSemester, pembanding: prediksi),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Simulasi: IPK ${prediksi.toStringAsFixed(2)}',
                      style: const TextStyle(fontWeight: FontWeight.bold)),
                  Row(
                    children: [
                      Expanded(
                          child: TextField(
                              controller: _huruf, decoration: const InputDecoration(labelText: 'Huruf (A/B/C)'))),
                      const SizedBox(width: 8),
                      Expanded(
                          child: TextField(
                              controller: _sks,
                              decoration: const InputDecoration(labelText: 'SKS'),
                              keyboardType: TextInputType.number)),
                      IconButton(
                        icon: const Icon(Icons.add_circle),
                        onPressed: () => setState(
                            () => _rencana.add({'huruf': _huruf.text.trim().toUpperCase(), 'sks': _sks.text.trim()})),
                      ),
                    ],
                  ),
                  ..._rencana.map((r) => ListTile(
                        dense: true,
                        title: Text('Nilai ${r['huruf']} — ${r['sks']} SKS'),
                        trailing: IconButton(
                          icon: const Icon(Icons.delete_outline),
                          onPressed: () => setState(() => _rencana.remove(r)),
                        ),
                      )),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
