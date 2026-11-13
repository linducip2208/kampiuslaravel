import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:simak_mobile/main.dart';
import 'package:simak_mobile/screens/login_screen.dart';

void main() {
  testWidgets('Login menampilkan form NIM/email + password', (tester) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));
    expect(find.text('Login SIMAK'), findsOneWidget);
    expect(find.byType(TextField), findsNWidgets(2));
    expect(find.text('Masuk'), findsOneWidget);
  });

  testWidgets('Shell punya 5 tab navigasi', (tester) async {
    await tester.pumpWidget(const MaterialApp(home: MainShell()));
    expect(find.text('Beranda'), findsOneWidget);
    expect(find.text('Nilai'), findsOneWidget);
    expect(find.text('Tagihan'), findsOneWidget);
    expect(find.text('Notif'), findsOneWidget);
    expect(find.text('Profil'), findsOneWidget);
  });
}
