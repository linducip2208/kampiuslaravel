import 'package:flutter/material.dart';

import '../api_client.dart';
import '../l10n/strings.dart';
import '../main.dart';
import '../services/fcm_topics.dart';
import '../services/ktm_cache.dart';
import '../services/locale_prefs.dart';
import '../widgets/jadwal_widget.dart';
import 'ipk_predictor_screen.dart';
import 'ktm_screen.dart';
import 'tugas_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  dynamic jadwal;
  dynamic tagihan;
  dynamic pengumuman;
  dynamic me;
  AppLocale _locale = AppLocale.id;
  String _topic = '-';

  Strings get s => Strings(_locale);

  @override
  void initState() {
    super.initState();
    LocalePrefs.load().then((l) => mounted ? setState(() => _locale = l) : null);
    _load();
  }

  Future<void> _load() async {
    final j = await ApiClient.get('/jadwal-hari-ini');
    final t = await ApiClient.get('/tagihan');
    final p = await ApiClient.get('/pengumuman');
    final m = await ApiClient.get('/me');
    if (mounted) {
      setState(() {
        jadwal = j['body'];
        tagihan = t['body'];
        pengumuman = p['body'];
        me = m['body'];
      });
      if ((m['status'] as int) != 0) {
        await KtmCache.save(m['body']);
        final prodiId = prodiIdFromMe(m['body']);
        if (prodiId != null) {
          if (mounted) setState(() => _topic = prodiTopic(prodiId));
          await ApiClient.registerDeviceToken(token: 'simulated-fcm-token', platform: 'android');
        }
      }
      await ApiClient.flushAntreanAbsensi();
    }
  }

  @override
  Widget build(BuildContext context) {
    final menunggak = (tagihan?['ringkasan']?['total_menunggak'] ?? 0).toString();
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(
              children: [
                Expanded(
                    child: Text(s.get('appTitle'),
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold))),
                TextButton(
                  onPressed: () async {
                    final next = _locale == AppLocale.id ? AppLocale.en : AppLocale.id;
                    await LocalePrefs.save(next);
                    if (mounted) setState(() => _locale = next);
                  },
                  child: Text(_locale == AppLocale.id ? 'EN' : 'ID'),
                ),
              ],
            ),
          ),
          JadwalWidget(jadwal: jadwal, onRefresh: _load),
          Card(child: ListTile(title: Text('${s.get('tagihan')} & VA'), subtitle: Text('Rp $menunggak'))),
          Card(
              child: ListTile(
                  title: const Text('Pengumuman'),
                  subtitle: Text('${(pengumuman?['data']?.length ?? 0)} terbaru • Topik $_topic'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PengumumanRoute())))),
          Card(
              child: ListTile(
                  title: Text(s.get('tugas')),
                  subtitle: const Text('Kumpul berkas via file picker'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () =>
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const TugasScreen())))),
          Card(
              child: ListTile(
                  title: Text(s.get('ktm')),
                  subtitle: const Text('Digital + tersedia offline'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const KtmScreen())))),
          Card(
              child: ListTile(
                  title: Text(s.get('prediktorIpk')),
                  subtitle: const Text('Grafik + simulasi what-if'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.push(
                      context, MaterialPageRoute(builder: (_) => const IpkPredictorScreen())))),
          Card(
              child: ListTile(
                  title: const Text('Bimbingan'),
                  subtitle: const Text('Log bimbingan skripsi'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BimbinganRoute())))),
        ],
      ),
    );
  }
}
