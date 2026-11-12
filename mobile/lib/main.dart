import 'package:flutter/material.dart';

import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'screens/nilai_screen.dart';
import 'screens/qr_scan_screen.dart';
import 'screens/tagihan_screen.dart';

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
        '/home': (_) => const HomeScreen(),
        '/qr': (_) => const QrScanScreen(),
        '/nilai': (_) => const NilaiScreen(),
        '/tagihan': (_) => const TagihanScreen(),
      },
    );
  }
}
