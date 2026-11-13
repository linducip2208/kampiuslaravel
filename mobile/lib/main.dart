import 'package:flutter/material.dart';

import 'screens/bimbingan_screen.dart';
import 'screens/home_screen.dart';
import 'screens/ipk_predictor_screen.dart';
import 'screens/ktm_screen.dart';
import 'screens/login_screen.dart';
import 'screens/nilai_screen.dart';
import 'screens/notifikasi_screen.dart';
import 'screens/pengumuman_screen.dart';
import 'screens/profil_screen.dart';
import 'screens/qr_scan_screen.dart';
import 'screens/tagihan_screen.dart';
import 'screens/tugas_screen.dart';

void main() {
  runApp(const SimakApp());
}

class SimakApp extends StatelessWidget {
  const SimakApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SIMAK Mobile',
      theme: ThemeData(useMaterial3: true, colorSchemeSeed: Colors.indigo),
      initialRoute: '/login',
      routes: {
        '/login': (_) => const LoginScreen(),
        '/shell': (_) => const MainShell(),
        '/qr': (_) => const QrScanScreen(),
        '/tugas': (_) => const TugasScreen(),
        '/ktm': (_) => const KtmScreen(),
        '/ipk': (_) => const IpkPredictorScreen(),
      },
    );
  }
}

/// Bottom navigation: Beranda, Nilai, Tagihan, Notif, Profil.
class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;

  static const _pages = [
    HomeScreen(),
    NilaiScreen(),
    TagihanScreen(),
    NotifikasiScreen(),
    ProfilScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Beranda'),
          NavigationDestination(icon: Icon(Icons.grade_outlined), selectedIcon: Icon(Icons.grade), label: 'Nilai'),
          NavigationDestination(icon: Icon(Icons.receipt_outlined), selectedIcon: Icon(Icons.receipt), label: 'Tagihan'),
          NavigationDestination(icon: Icon(Icons.notifications_outlined), selectedIcon: Icon(Icons.notifications), label: 'Notif'),
          NavigationDestination(icon: Icon(Icons.person_outlined), selectedIcon: Icon(Icons.person), label: 'Profil'),
        ],
      ),
      floatingActionButton: _index == 0
          ? FloatingActionButton.extended(
              onPressed: () => Navigator.pushNamed(context, '/qr'),
              icon: const Icon(Icons.qr_code_scanner),
              label: const Text('Absen'),
            )
          : null,
    );
  }
}

class PengumumanRoute extends StatelessWidget {
  const PengumumanRoute({super.key});

  @override
  Widget build(BuildContext context) => const PengumumanScreen();
}

class BimbinganRoute extends StatelessWidget {
  const BimbinganRoute({super.key});

  @override
  Widget build(BuildContext context) => const BimbinganScreen();
}
