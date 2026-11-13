import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:simak_mobile/l10n/strings.dart';
import 'package:simak_mobile/screens/tagihan_screen.dart';
import 'package:simak_mobile/services/fcm_topics.dart';
import 'package:simak_mobile/services/ktm_cache.dart';
import 'package:simak_mobile/services/offline_queue.dart';
import 'package:simak_mobile/widgets/ipk_chart.dart';
import 'package:simak_mobile/widgets/jadwal_widget.dart';

void main() {
  SharedPreferences.setMockInitialValues({});

  group('EN/ID', () {
    test('kosa kata inti tersedia dua bahasa', () {
      expect(Strings.raw('masuk', AppLocale.id), 'Masuk');
      expect(Strings.raw('masuk', AppLocale.en), 'Sign in');
      expect(Strings.raw('ktm', AppLocale.id), 'KTM');
      expect(Strings.raw('ktm', AppLocale.en), 'Student card');
      expect(Strings.raw('antreanOffline', AppLocale.en), 'Offline queue');
    });
  });

  group('IPK', () {
    test('ipkOf menghitung rata-rata berbobot', () {
      final ipk = IpkMath.ipkOf([
        {'huruf': 'A', 'sks': 3},
        {'huruf': 'C', 'sks': 3},
      ]);
      expect(ipk, 3.0);
    });

    test('prediksi what-if menaikkan IPK', () {
      final base = IpkMath.ipkOf([
        {'huruf': 'C', 'sks': 3},
      ]);
      final pred = IpkMath.prediksi([
      {'huruf': 'C', 'sks': '3'}
    ], [
      {'huruf': 'A', 'sks': '3'}
    ]);
      expect(pred, greaterThan(base));
      expect(pred, 3.0);
    });

    testWidgets('IpkChart tampil dengan data', (tester) async {
      await tester.pumpWidget(const MaterialApp(
          home: Scaffold(body: IpkChart(perSemester: [3.0, 3.5]))));
      expect(find.text('Tren IPK per semester'), findsOneWidget);
    });
  });

  group('JadwalWidget', () {
    test('countOf mendukung list & map', () {
      expect(JadwalWidget.countOf([1, 2]), 2);
      expect(JadwalWidget.countOf({'data': [1]}), 1);
      expect(JadwalWidget.countOf(null), 0);
    });

    testWidgets('menampilkan kelas', (tester) async {
      await tester.pumpWidget(MaterialApp(
        home: Scaffold(
            body: JadwalWidget(jadwal: {
          'data': [
            {'matakuliah': 'Algoritma', 'jam_mulai': '08:00', 'jam_selesai': '10:00', 'ruang': 'R1'}
          ]
        })),
      ));
      expect(find.text('Jadwal hari ini'), findsOneWidget);
      expect(find.text('Algoritma'), findsOneWidget);
    });
  });

  group('FCM topik per prodi', () {
    test('format prodi_{id}', () {
      expect(prodiTopic(7), 'prodi_7');
      expect(prodiIdFromMe({'data': {'prodi_id': 7}}), 7);
      expect(prodiIdFromMe({'data': {'mahasiswa': {'prodi_id': 3}}}), 3);
      expect(prodiIdFromMe(null), isNull);
    });
  });

  group('VA deep-link', () {
    test('format simak://va', () {
      expect(vaDeepLink('REF1', '123'), 'simak://va?ref=REF1&no=123');
    });
  });

  group('Offline queue', () {
    test('enqueue + pending + removeKeys', () async {
      await OfflineQueue.clear();
      final k1 = await OfflineQueue.enqueue('QR1');
      await OfflineQueue.enqueue('QR2');
      expect((await OfflineQueue.pending()).length, 2);
      expect(OfflineQueue.newKey() == OfflineQueue.newKey(), isFalse);
      await OfflineQueue.removeKeys({k1});
      final rest = await OfflineQueue.pending();
      expect(rest.length, 1);
      expect(rest.first['qr_token'], 'QR2');
      await OfflineQueue.clear();
    });
  });

  group('KTM cache', () {
    test('save + load snapshot', () async {
      await KtmCache.save({
        'data': {'name': 'Budi', 'nim': '123'}
      });
      final loaded = await KtmCache.load();
      expect(loaded?['data']['name'], 'Budi');
    });
  });
}
