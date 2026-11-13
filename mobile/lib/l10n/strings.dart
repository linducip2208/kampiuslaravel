/// Kosa kata EN/ID minimal untuk SIMAK Mobile (tanpa codegen).
library;

enum AppLocale { id, en }

class Strings {
  final AppLocale locale;
  const Strings(this.locale);

  static const _t = <String, Map<AppLocale, String>>{
    'appTitle': {AppLocale.id: 'SIMAK Mobile', AppLocale.en: 'SIMAK Mobile'},
    'login': {AppLocale.id: 'Login SIMAK', AppLocale.en: 'SIMAK Login'},
    'nimEmail': {AppLocale.id: 'NIM / Email', AppLocale.en: 'NIM / Email'},
    'password': {AppLocale.id: 'Password', AppLocale.en: 'Password'},
    'masuk': {AppLocale.id: 'Masuk', AppLocale.en: 'Sign in'},
    'masukBiometrik': {AppLocale.id: 'Masuk dengan biometrik', AppLocale.en: 'Sign in with biometrics'},
    'beranda': {AppLocale.id: 'Beranda', AppLocale.en: 'Home'},
    'nilai': {AppLocale.id: 'Nilai', AppLocale.en: 'Grades'},
    'tagihan': {AppLocale.id: 'Tagihan', AppLocale.en: 'Billing'},
    'notif': {AppLocale.id: 'Notif', AppLocale.en: 'Alerts'},
    'profil': {AppLocale.id: 'Profil', AppLocale.en: 'Profile'},
    'jadwalHariIni': {AppLocale.id: 'Jadwal hari ini', AppLocale.en: "Today's schedule"},
    'kelas': {AppLocale.id: 'kelas', AppLocale.en: 'classes'},
    'absensiQr': {AppLocale.id: 'Absensi QR', AppLocale.en: 'QR Attendance'},
    'kirimKehadiran': {AppLocale.id: 'Kirim Kehadiran', AppLocale.en: 'Submit attendance'},
    'antreanOffline': {AppLocale.id: 'Antrean offline', AppLocale.en: 'Offline queue'},
    'tugas': {AppLocale.id: 'Tugas', AppLocale.en: 'Assignments'},
    'ktm': {AppLocale.id: 'KTM', AppLocale.en: 'Student card'},
    'prediktorIpk': {AppLocale.id: 'Prediktor IPK', AppLocale.en: 'GPA predictor'},
    'buatVa': {AppLocale.id: 'Buat VA', AppLocale.en: 'Create VA'},
    'salinVa': {AppLocale.id: 'Salin VA', AppLocale.en: 'Copy VA'},
    'keluar': {AppLocale.id: 'Keluar', AppLocale.en: 'Sign out'},
  };

  String get(String key) => _t[key]?[locale] ?? key;

  /// Nilai mentah untuk unit test.
  static String raw(String key, AppLocale locale) => _t[key]?[locale] ?? key;
}
