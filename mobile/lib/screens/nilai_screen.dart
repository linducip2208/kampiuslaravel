import 'package:flutter/material.dart';

import '../api_client.dart';
import '../widgets/ipk_chart.dart';
import 'ipk_predictor_screen.dart';

class NilaiScreen extends StatefulWidget {
  const NilaiScreen({super.key});

  @override
  State<NilaiScreen> createState() => _NilaiScreenState();
}

class _NilaiScreenState extends State<NilaiScreen> {
  dynamic data;

  @override
  void initState() {
    super.initState();
    ApiClient.get('/nilai-ringkas').then((r) => mounted ? setState(() => data = r['body']) : null);
  }

  @override
  Widget build(BuildContext context) {
    final nilai = (data?['data']?['nilai'] ?? data?['nilai'] ?? data?['data'] ?? []) as List;
    final ipk = '${data?['data']?['ipk'] ?? data?['ipk'] ?? '-'}';
    final perSemester = ((data?['data']?['per_semester'] ?? []) as List)
        .map((e) => double.tryParse('$e') ?? 0.0)
        .toList();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nilai & IPK'),
        actions: [
          IconButton(
            icon: const Icon(Icons.auto_graph),
            tooltip: 'Prediktor IPK',
            onPressed: () => Navigator.push(
                context, MaterialPageRoute(builder: (_) => const IpkPredictorScreen())),
          ),
        ],
      ),
      body: data == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(child: ListTile(title: const Text('IPK'), subtitle: Text(ipk))),
                if (perSemester.isNotEmpty) IpkChart(perSemester: perSemester),
                ...nilai.map((n) => Card(
                        child: ListTile(
                            title: Text('${n['kode_mk'] ?? n['matakuliah'] ?? '-'}'),
                            trailing: Text('${n['huruf'] ?? '-'}')))),
              ],
            ),
    );
  }
}
