# Online Snake Server

Bu dizin yalnızca Project Redemption'ın online Snake modu için kullanılan otoriter WebSocket oyun sunucusudur. Mesaj, bildirim veya diğer online oyun odalarını yönetmez; bu görev production'da kökteki `api/ws.js` Vercel function'ına aittir.

## Neden ayrı servis?

Snake sabit aralıklı merkezi bir game loop gerektirir. Oyuncu hareketleri ve çarpışmalar istemcilere veya HTTP polling'e bırakılmaz. Sunucu:

- 30×30 oyun alanını yönetir.
- Varsayılan olarak 180 ms'de bir ilerler.
- Yön komutlarını doğrular.
- Yem, çarpışma, kaybetme ve hedef uzunluk kurallarını uygular.
- Canlı state'i bellekte, checkpoint/heartbeat verisini MySQL'de tutar.

## Çalıştırma

```powershell
cd snake-server
npm install
npm start
```

Sözdizimi kontrolü:

```powershell
npm run check
```

## Ortam değişkenleri

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_SSL_CA_BASE64
REALTIME_SECRET
ALLOWED_ORIGINS
DB_SSL=true
SNAKE_STEP_MS=180
SNAKE_GRID=30
SNAKE_TARGET_LENGTH=15
```

`REALTIME_SECRET`, ana PHP uygulamasındaki değerle aynı olmalıdır. `ALLOWED_ORIGINS` yalnızca uygulamanın izin verilen HTTPS origin'lerini içermelidir.

## Deployment

Servis [`../render.yaml`](../render.yaml) ile Render'a deploy edilir. Render tarafından verilen HTTPS adresi `wss://` biçimine çevrilerek ana uygulamanın `SNAKE_WEBSOCKET_URL` environment variable'ına yazılır.

Sağlık kontrolü: `GET /health`.
