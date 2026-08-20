<?php

declare(strict_types=1);

use App\Libraries\RealtimeSessionAuthenticator;
use CodeIgniter\Config\DotEnv;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Timer;
use Workerman\Worker;

require __DIR__ . '/vendor/autoload.php';
(new DotEnv(__DIR__))->load();
Worker::$logFile = __DIR__ . '/writable/logs/websocket-workerman.log';

$envValue = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : $value;
};
$databaseConfig = [
    'hostname' => (string) $envValue('database.default.hostname', '127.0.0.1'),
    'username' => (string) $envValue('database.default.username', 'root'),
    'password' => (string) $envValue('database.default.password', ''),
    'database' => (string) $envValue('database.default.database', ''),
    'port' => (int) $envValue('database.default.port', 3306),
];
$allowedOrigins = array_filter(array_map('trim', explode(',', (string) $envValue('realtime.allowedOrigins', 'http://localhost:8080,http://127.0.0.1:8080'))));
$authenticator = new RealtimeSessionAuthenticator(__DIR__ . '/writable/session', 'ci_session', 7200);
$connectionsByUser = [];
$database = null;
$lastMessageId = 0;
$lastNotificationId = 0;
$roomVersions = [];
$lastRoomScanAt = 0.0;

$connectDatabase = static function () use (&$database, &$lastMessageId, &$lastNotificationId, &$roomVersions, $databaseConfig): bool {
    mysqli_report(MYSQLI_REPORT_OFF);
    $database = @new mysqli($databaseConfig['hostname'], $databaseConfig['username'], $databaseConfig['password'], $databaseConfig['database'], $databaseConfig['port']);
    if ($database->connect_errno) { $database = null; return false; }
    $database->set_charset('utf8mb4');
    $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM direct_messages');
    $lastMessageId = $result ? (int) $result->fetch_assoc()['last_id'] : 0;
    $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM notifications');
    $lastNotificationId = $result ? (int) $result->fetch_assoc()['last_id'] : 0;
    $roomVersions = [];
    $result = $database->query("SELECT id, version FROM game_rooms WHERE status IN ('waiting','playing','completed')");
    if ($result) while ($row = $result->fetch_assoc()) $roomVersions[(int) $row['id']] = (int) $row['version'];
    return true;
};
$sendJson = static function (TcpConnection $connection, array $payload): void {
    $connection->send(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
};
$broadcastPresence = static function () use (&$connectionsByUser, $sendJson): void {
    $payload = ['type' => 'presence', 'changedAt' => (int) round(microtime(true) * 1000)];
    foreach ($connectionsByUser as $connections) foreach ($connections as $connection) $sendJson($connection, $payload);
};

$server = new Worker('websocket://127.0.0.1:8081');
$server->name = 'ProjectRedemptionRealtime';
$server->count = 1;
$server->onWebSocketConnect = static function (TcpConnection $connection, Request $request) use ($allowedOrigins, $authenticator): void {
    $origin = rtrim((string) $request->header('origin', ''), '/');
    $session = $authenticator->authenticate((string) $request->cookie('ci_session', ''));
    if ($origin === '' || ! in_array($origin, $allowedOrigins, true) || $session === null) { $connection->close(); return; }
    $connection->context->authenticatedUserId = $session['userId'];
};
$server->onWebSocketConnected = static function (TcpConnection $connection) use (&$connectionsByUser, &$database, &$lastMessageId, &$lastNotificationId, $connectDatabase, $sendJson, $broadcastPresence): void {
    $userId = (int) ($connection->context->authenticatedUserId ?? 0);
    if ($userId < 1 || (! $database instanceof mysqli && ! $connectDatabase())) { $connection->close(); return; }
    $activeUser = $database->query("SELECT id FROM users WHERE id = {$userId} AND is_active = 1 LIMIT 1");
    if (! $activeUser || $activeUser->num_rows !== 1) { $connection->close(); return; }
    $wasEmpty = $connectionsByUser === [];
    $connectionsByUser[$userId][$connection->id] = $connection;
    $database->query("UPDATE users SET last_seen_at=NOW() WHERE id={$userId}");
    if ($wasEmpty && $database instanceof mysqli) {
        $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM direct_messages');
        if ($result) $lastMessageId = (int) $result->fetch_assoc()['last_id'];
        $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM notifications');
        if ($result) $lastNotificationId = (int) $result->fetch_assoc()['last_id'];
    }
    $sendJson($connection, ['type' => 'ready', 'userId' => $userId, 'serverAt' => (int) round(microtime(true) * 1000)]);
    $broadcastPresence();
};
$server->onMessage = static function (TcpConnection $connection, string $rawMessage) use ($sendJson): void {
    $message = json_decode($rawMessage, true);
    if (is_array($message) && ($message['type'] ?? null) === 'ping') $sendJson($connection, ['type' => 'pong', 'sentAt' => (int) ($message['sentAt'] ?? 0)]);
};
$server->onClose = static function (TcpConnection $connection) use (&$connectionsByUser, &$database, $broadcastPresence): void {
    $userId = (int) ($connection->context->authenticatedUserId ?? 0);
    unset($connectionsByUser[$userId][$connection->id]);
    if (empty($connectionsByUser[$userId])) unset($connectionsByUser[$userId]);
    if ($userId > 0 && empty($connectionsByUser[$userId]) && $database instanceof mysqli) $database->query("UPDATE users SET last_seen_at=NULL WHERE id={$userId}");
    $broadcastPresence();
};
$server->onWorkerStart = static function () use (&$database, &$lastMessageId, &$lastNotificationId, &$roomVersions, &$lastRoomScanAt, &$connectionsByUser, $connectDatabase, $sendJson): void {
    $connectDatabase();
    Timer::add(0.3, static function () use (&$database, &$lastMessageId, &$lastNotificationId, &$roomVersions, &$lastRoomScanAt, &$connectionsByUser, $connectDatabase, $sendJson): void {
        if ($connectionsByUser === []) return;
        try {
        if (! $database instanceof mysqli || ! @$database->ping()) { if (! $connectDatabase()) return; }
        $statement = $database->prepare('SELECT dm.id, dm.conversation_id, dm.sender_id, dc.user_one_id, dc.user_two_id FROM direct_messages dm INNER JOIN direct_conversations dc ON dc.id = dm.conversation_id WHERE dm.id > ? ORDER BY dm.id ASC LIMIT 100');
        if (! $statement) return;
        $statement->bind_param('i', $lastMessageId);
        if (! $statement->execute()) { $statement->close(); return; }
        $result = $statement->get_result();
        while ($row = $result->fetch_assoc()) {
            $lastMessageId = max($lastMessageId, (int) $row['id']);
            $payload = ['type' => 'direct-message', 'messageId' => (int) $row['id'], 'conversationId' => (int) $row['conversation_id'], 'senderId' => (int) $row['sender_id']];
            foreach (array_unique([(int) $row['user_one_id'], (int) $row['user_two_id']]) as $userId) {
                foreach ($connectionsByUser[$userId] ?? [] as $connection) $sendJson($connection, $payload);
            }
        }
        $statement->close();

        $statement = $database->prepare('SELECT id, user_id, type FROM notifications WHERE id > ? ORDER BY id ASC LIMIT 100');
        if (! $statement) return;
        $statement->bind_param('i', $lastNotificationId);
        if (! $statement->execute()) { $statement->close(); return; }
        $result = $statement->get_result();
        while ($row = $result->fetch_assoc()) {
            $lastNotificationId = max($lastNotificationId, (int) $row['id']);
            $payload = ['type' => 'notification', 'notificationId' => (int) $row['id'], 'notificationType' => $row['type']];
            foreach ($connectionsByUser[(int) $row['user_id']] ?? [] as $connection) $sendJson($connection, $payload);
        }
        $statement->close();

        $now = microtime(true);
        if ($now - $lastRoomScanAt < 0.3) return;
        $lastRoomScanAt = $now;
        $activeRoomIds = [];
        $result = $database->query("SELECT id, code, game, host_user_id, guest_user_id, status, version FROM game_rooms WHERE status IN ('waiting','playing','completed')");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $roomId = (int) $row['id']; $version = (int) $row['version']; $activeRoomIds[$roomId] = true;
                $previousVersion = $roomVersions[$roomId] ?? null; $roomVersions[$roomId] = $version;
                if ($previousVersion === null || $previousVersion === $version) continue;
                $payload = ['type' => 'game-room', 'roomCode' => $row['code'], 'game' => $row['game'], 'status' => $row['status'], 'version' => $version];
                $recipientIds = [(int) $row['host_user_id'], (int) ($row['guest_user_id'] ?? 0)];
                if (in_array($row['game'], ['okey101', 'monopoly'], true)) {
                    $players = $database->query('SELECT user_id FROM game_room_players WHERE room_id=' . $roomId . ' AND user_id IS NOT NULL');
                    if ($players) {
                        while ($player = $players->fetch_assoc()) $recipientIds[] = (int) $player['user_id'];
                        $players->free();
                    }
                }
                foreach (array_unique($recipientIds) as $userId) {
                    if ($userId < 1) continue;
                    foreach ($connectionsByUser[$userId] ?? [] as $connection) $sendJson($connection, $payload);
                }
            }
            foreach (array_keys($roomVersions) as $roomId) if (! isset($activeRoomIds[$roomId])) unset($roomVersions[$roomId]);
        }
        } catch (Throwable $exception) {
            $database = null;
            file_put_contents(
                __DIR__ . '/writable/logs/websocket-runtime-error.log',
                '[' . date('Y-m-d H:i:s') . '] ' . $exception::class . ': ' . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }
    });
};

Worker::runAll();
