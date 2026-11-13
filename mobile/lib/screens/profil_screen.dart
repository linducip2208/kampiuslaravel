import 'package:flutter/material.dart';

import '../api_client.dart';
import '../l10n/strings.dart';
import '../services/fcm_topics.dart';
import '../services/locale_prefs.dart';
import 'ktm_screen.dart';

class ProfilScreen extends StatefulWidget {
  const ProfilScreen({super.key});

  @override
  State<ProfilScreen> createState() => _ProfilScreenState();
}

class _ProfilScreenState extends State<ProfilScreen> {
  dynamic me;
  AppLocale _locale = AppLocale.id;

  @override
  void initState() {
    super.initState();
    LocalePrefs.load().then((l) => mounted ? setState(() => _locale = l) : null);
    ApiClient.get('/me').then((r) => mounted ? setState(() => me = r['body']) : null);
  }

  @override
  Widget build(BuildContext context) {
    final s = Strings(_locale);
    final user = me?['data'] ?? me ?? {};
    final prodiId = prodiIdFromMe(me);
    return Scaffold(
      appBar: AppBar(
        title: Text(s.get('profil')),
        actions: [
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
      body: me == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: ListTile(
                    leading: CircleAvatar(child: Text('${(user['name'] ?? '?').toString().substring(0, 1)}')),
                    title: Text('${user['name'] ?? '-'}'),
                    subtitle: Text('${user['email'] ?? '-'}'),
                  ),
                ),
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.notifications_active_outlined),
                    title: const Text('Topik FCM'),
                    subtitle: Text(prodiId == null ? '-' : prodiTopic(prodiId)),
                  ),
                ),
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.badge_outlined),
                    title: Text(s.get('ktm')),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () =>
                        Navigator.push(context, MaterialPageRoute(builder: (_) => const KtmScreen())),
                  ),
                ),
                const SizedBox(height: 8),
                ElevatedButton.icon(
                  onPressed: () async {
                    await ApiClient.post('/logout', {});
                    await ApiClient.clearToken();
                    if (context.mounted) Navigator.pushReplacementNamed(context, '/login');
                  },
                  icon: const Icon(Icons.logout),
                  label: Text(s.get('keluar')),
                ),
              ],
            ),
    );
  }
}
