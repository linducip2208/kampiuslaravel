import 'package:shared_preferences/shared_preferences.dart';

import '../l10n/strings.dart';

/// Preferensi bahasa EN/ID, persisten via SharedPreferences.
class LocalePrefs {
  static const key = 'app_locale';

  static Future<AppLocale> load() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(key) == 'en' ? AppLocale.en : AppLocale.id;
  }

  static Future<void> save(AppLocale locale) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(key, locale == AppLocale.en ? 'en' : 'id');
  }
}
