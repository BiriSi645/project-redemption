# Project Redemption

Project Redemption; kişisel üretkenlik, sosyal paylaşım, proje yönetimi ve çok oyunculu oyunları tek bir CodeIgniter 4 uygulamasında birleştiren web platformudur.

Uygulama PHP 8.2 ve MySQL üzerinde çalışır. Ana uygulama ve genel WebSocket servisi Vercel'de, otoriter online Snake sunucusu Render'da, production veritabanı ise Aiven MySQL'de çalışacak şekilde yapılandırılmıştır.

## Öne çıkan özellikler

- E-posta doğrulamalı kayıt, giriş, profil ve güvenli şifre sıfırlama
- Kullanıcıya özel görevler, günlükler, alışkanlık takibi ve takvim hatırlatıcıları
- Public/private notlar, yorumlar, kullanıcı etiketleme ve bildirimler
- Özel mesajlaşma ve sayfa üzerinden açılan Messenger benzeri sohbet paneli
- Proje üyeleri, davetler, görev atamaları, bölümler ve Gantt görünümü
- Seviye/deneyim sistemi, aktif kullanıcılar ve liderlik alanları
- Kronometre, zamanlayıcı ve sesli yazma
- Çevrimdışı Sudoku, Mayın Tarlası, Snake ve Tetris
- Online Sudoku, Mayın Tarlası, Snake, 101 Okey ve Monopoly
- Kullanıcı, toplu bildirim ve audit log yönetimi için admin paneli
- Sistem/açık/koyu tema ve responsive navigasyon

## Teknoloji yığını

- Backend: PHP 8.2+, CodeIgniter 4.7
- Veritabanı: MySQL/MariaDB
- Frontend: server-rendered PHP view'ları ve vanilla JavaScript
- Genel realtime: Vercel WebSocket function, Node.js, `ws`, `mysql2`
- Online Snake: Render üzerinde ayrı Node.js WebSocket servisi
- Test: PHPUnit 10

## Realtime mimarisi

Repoda iki aktif WebSocket sunucusu vardır ve görevleri farklıdır:

| Dosya | Ortam | Sorumluluk |
| --- | --- | --- |
| `api/ws.js` | Vercel Fluid Compute | Mesaj, bildirim, presence ve Sudoku/Mayın Tarlası/Okey/Monopoly oda olayları |
| `snake-server/server.js` | Render | Online Snake için merkezi game loop, yönler, çarpışmalar ve authoritative state |

Ana PHP uygulaması olayları `realtime_events` tablosuna yazar. `api/ws.js` bu tabloyu okuyup ilgili kullanıcılara yayınlar; kritik uygulama durumu WebSocket process belleğinde tutulmaz. WebSocket bağlıyken oyun polling'i kapalıdır. Bağlantı koparsa istemci tek bir 8 saniyelik fallback polling başlatır ve bağlantı geri gelince durdurur.

Online Snake ayrı servis kullanır çünkü hareket döngüsü serverless HTTP veya veritabanı polling'ine bağlı olmamalıdır. Render servisi [`render.yaml`](render.yaml) ile deploy edilir; ayrıntılar [`snake-server/README.md`](snake-server/README.md) içindedir.

Eski PHP/Workerman WebSocket prototipi kaldırılmıştır. Production genel realtime giriş noktası yalnızca `api/ws.js` dosyasıdır.

## Yerel kurulum

Gereksinimler:

- PHP 8.2+ (`intl`, `mbstring`, `mysqli`, `curl`)
- Composer 2
- MySQL veya MariaDB
- Node.js 20+ (WebSocket fonksiyonunu yerelde geliştirecekseniz)

Kurulum:

```powershell
git clone <repository-url>
cd project-redemption
Copy-Item env .env
C:\xampp\php\php.exe composer.phar install
```

`.env` içinde en az aşağıdaki CodeIgniter ayarlarını yapılandırın:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = project_redemption
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306

REALTIME_SECRET = replace-with-at-least-32-random-characters
```

Veritabanını hazırlayıp uygulamayı başlatın:

```powershell
C:\xampp\php\php.exe spark migrate
C:\xampp\php\php.exe spark serve
```

Uygulama varsayılan olarak `http://localhost:8080` adresinde açılır. Genel WebSocket yerelde çalışmıyorsa mesaj ve oyun state güncellemeleri fallback mekanizmasıyla çalışmaya devam eder.

## Vercel deployment

[`vercel.json`](vercel.json) iki function tanımlar:

- `api/index.php`: CodeIgniter uygulaması
- `api/ws.js`: genel WebSocket servisi

Vercel'de hem PHP uygulamasının `database.default.*` değerleri hem de Node WebSocket fonksiyonunun aşağıdaki değerleri tanımlanmalıdır:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
REALTIME_SECRET
DB_SSL_CA_BASE64
```

`REALTIME_SECRET`, PHP ve her iki Node WebSocket servisi için aynı olmalı ve en az 32 karakterlik rastgele bir değer olmalıdır.

Production'da `api/ws.js` veritabanı sertifikasını doğrular. Aiven'in `ca.pem` dosyasını Base64'e çevirmek için:

```powershell
[Convert]::ToBase64String([IO.File]::ReadAllBytes("C:\path\to\ca.pem"))
```

Çıktıyı Vercel'de `DB_SSL_CA_BASE64` olarak kaydedin. Sertifika olmadan production WebSocket servisi bilinçli olarak DB bağlantısı açmaz.

Production/custom domain için Vercel'de `APP_BASE_URL=https://example.com/` tanımlayın. Bunu yalnızca Production ortamına uygulayın; Preview deployment'lar Vercel'in sağladığı `VERCEL_URL` değerini otomatik kullanır.

Kod sürümü production'da sırasıyla `APP_VERSION`, `VERCEL_GIT_COMMIT_SHA` veya deployment'a özel `VERCEL_URL` üzerinden belirlenir. Normal Vercel Git deployment'ında ayrıca ayar gerekmez; Git dışı deployment kullanıyorsanız `APP_VERSION` değerini her yayında değişen release/commit kimliği olarak tanımlayın.

## Online Snake deployment

Render Blueprint için [`render.yaml`](render.yaml) kullanılır. Gerekli secret/env değerleri:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_SSL_CA_BASE64
REALTIME_SECRET
ALLOWED_ORIGINS
```

Render servis URL'si Vercel/PHP ortamında `SNAKE_WEBSOCKET_URL` olarak tanımlanmalıdır. Örnek: `wss://project-redemption-snake.onrender.com`.

## Test ve kalite kontrolleri

Tüm testleri çalıştırmak için:

```powershell
C:\xampp\php\php.exe vendor\bin\phpunit
```

JavaScript sözdizimi kontrolleri:

```powershell
node --check api/ws.js
node --check snake-server/server.js
```

Test paketi authentication güvenliği, deadline/notification kuralları, oyun motorları, bot zinciri, rematch ve session lock davranışlarını kapsar.

## Yararlı bakım komutları

```powershell
# Migration durumu ve yeni migration'lar
C:\xampp\php\php.exe spark migrate:status
C:\xampp\php\php.exe spark migrate

# Eski audit loglarını temizle
C:\xampp\php\php.exe spark logs:prune --days 180

# Eski oyun odalarını temizle
C:\xampp\php\php.exe spark games:prune-rooms
```

## Güvenlik notları

- Parolalar `password_hash()` ile saklanır ve yeni parolalarda minimum 10 karakter aranır.
- E-posta doğrulama kodları hash'li, reset tokenları SHA-256 hash'li tutulur.
- Login sonrası session ID yenilenir ve authentication endpointlerinde rate limiting uygulanır.
- E-posta debugger çıktısı loglanmaz; reset tokenları ve doğrulama kodları loglara yazılmaz.
- State-changing formlar CSRF korumalıdır.
- Kullanıcıya özel içerik kontrolleri controller/service katmanında uygulanır.
- Secret, parola, CA ve production `.env` değerleri repoya commit edilmemelidir.

## Lisans

Bu proje [`LICENSE`](LICENSE) dosyasındaki MIT lisansı altında sunulur.
