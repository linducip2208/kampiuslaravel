import 'package:flutter/material.dart';

/// Matematika IPK murni (diuji unit): bobot huruf -> angka, IPK, prediksi.
class IpkMath {
  static const bobot = {'A': 4.0, 'AB': 3.5, 'B': 3.0, 'BC': 2.5, 'C': 2.0, 'D': 1.0, 'E': 0.0};

  static double ipkOf(List<Map<String, dynamic>> nilai) {
    double total = 0;
    int sks = 0;
    for (final n in nilai) {
      final b = bobot['${n['huruf']}'] ?? 0.0;
      final raw = n['sks'];
      final s = raw is num ? raw.toInt() : int.tryParse('$raw') ?? 0;
      total += b * s;
      sks += s;
    }
    return sks == 0 ? 0 : total / sks;
  }

  /// Prediksi IPK akhir bila `rencana` (huruf + sks) ditambah ke riwayat.
  static double prediksi(List<Map<String, dynamic>> riwayat, List<Map<String, String>> rencana) {
    final gabung = [
      ...riwayat,
      ...rencana.map((r) => {'huruf': r['huruf'] ?? 'B', 'sks': int.tryParse('${r['sks']}') ?? 0}),
    ];
    return ipkOf(gabung);
  }
}

/// Grafik batang IPK per semester (CustomPainter, tanpa dependensi chart).
class IpkChart extends StatelessWidget {
  final List<double> perSemester;
  final double? pembanding;
  const IpkChart({super.key, required this.perSemester, this.pembanding});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Tren IPK per semester', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            SizedBox(
              height: 140,
              child: CustomPaint(
                painter: _BarPainter(perSemester, pembanding),
                child: Container(),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BarPainter extends CustomPainter {
  final List<double> values;
  final double? mark;
  _BarPainter(this.values, this.mark);

  @override
  void paint(Canvas canvas, Size size) {
    if (values.isEmpty) return;
    const max = 4.0;
    final n = values.length;
    final gap = 8.0;
    final barW = (size.width - gap * (n + 1)) / n;
    final barPaint = Paint()..color = const Color(0xFF3F51B5);
    final markPaint = Paint()
      ..color = const Color(0xFFE91E63)
      ..strokeWidth = 2;
    final textPainter = TextPainter(textDirection: TextDirection.ltr);
    for (var i = 0; i < n; i++) {
      final h = (values[i].clamp(0, max) / max) * (size.height - 24);
      final x = gap + i * (barW + gap);
      canvas.drawRRect(
        RRect.fromRectAndRadius(Rect.fromLTWH(x, size.height - 20 - h, barW, h), const Radius.circular(4)),
        barPaint,
      );
      textPainter.text = TextSpan(
          text: values[i].toStringAsFixed(2), style: const TextStyle(fontSize: 9, color: Colors.black87));
      textPainter.layout();
      textPainter.paint(canvas, Offset(x, size.height - 16));
    }
    if (mark != null) {
      final y = size.height - 20 - (mark!.clamp(0, max) / max) * (size.height - 24);
      canvas.drawLine(Offset(0, y), Offset(size.width, y), markPaint);
    }
  }

  @override
  bool shouldRepaint(covariant _BarPainter old) => old.values != values || old.mark != mark;
}
