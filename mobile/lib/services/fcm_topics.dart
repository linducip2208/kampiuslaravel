/// Topik FCM per prodi: `prodi_{id}`. Satu fungsi murni agar mudah diuji.
library;

String prodiTopic(Object? prodiId) => 'prodi_$prodiId';

/// Ekstrak prodi_id dari payload /me yang bentuknya longgar.
Object? prodiIdFromMe(dynamic me) {
  final user = me is Map ? (me['data'] ?? me) : null;
  if (user is! Map) return null;
  return user['prodi_id'] ?? (user['mahasiswa'] is Map ? (user['mahasiswa'] as Map)['prodi_id'] : null);
}
