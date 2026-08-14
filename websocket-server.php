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

$connectDatabase = static function () use (&$database, &$lastMessageId, &$lastNotificationId, $databaseConfig): bool {
    mysqli_report(MYSQLI_REPORT_OFF);
    $database = @new mysqli($databaseConfig['hostname'], $databaseConfig['username'], $databaseConfig['password'], $databaseConfig['database'], $databaseConfig['port']);
    if ($database->connect_errno) { $database = null; return false; }
    $database->set_charset('utf8mb4');
    $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM direct_messages');
    $lastMessageId = $result ? (int) $result->fetch_assoc()['last_id'] : 0;
    $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM notifications');
    $lastNotificationId = $result ? (int) $result->fetch_assoc()['last_id'] : 0;
    return true;
};
$sendJson = static function (TcpConnection $connection, array $payload): void {
    $connection->send(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
$server->onWebSocketConnected = static function (TcpConnection $connection) use (&$connectionsByUser, &$database, &$lastMessageId, &$lastNotificationId, $sendJson): void {
    $userId = (int) ($connection->context->authenticatedUserId ?? 0);
    if ($userId < 1 || ! $database instanceof mysqli) { $connection->close(); return; }
    $activeUser = $database->query("SELECT id FROM users WHERE id = {$userId} AND is_active = 1 LIMIT 1");
    if (! $activeUser || $activeUser->num_rows !== 1) { $connection->close(); return; }
    $wasEmpty = $connectionsByUser === [];
    $connectionsByUser[$userId][$connection->id] = $connection;
    if ($wasEmpty && $database instanceof mysqli) {
        $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM direct_messages');
        if ($result) $lastMessageId = (int) $result->fetch_assoc()['last_id'];
        $result = $database->query('SELECT COALESCE(MAX(id), 0) AS last_id FROM notifications');
        if ($result) $lastNotificationId = (int) $result->fetch_assoc()['last_id'];
    }
    $sendJson($connection, ['type' => 'ready', 'userId' => $userId, 'serverAt' => (int) round(microtime(true) * 1000)]);
};
$server->onMessage = static function (TcpConnection $connection, string $rawMessage) use ($sendJson): void {
    $message = json_decode($rawMessage, true);
    if (is_array($message) && ($message['type'] ?? null) === 'ping') $sendJson($connection, ['type' => 'pong', 'sentAt' => (int) ($message['sentAt'] ?? 0)]);
};
$server->onClose = static function (TcpConnection $connection) use (&$connectionsByUser): void {
    $userId = (int) ($connection->context->authenticatedUserId ?? 0);
    unset($connectionsByUser[$userId][$connection->id]);
    if (empty($connectionsByUser[$userId])) unset($connectionsByUser[$userId]);
};
$server->onWorkerStart = static function () use (&$database, &$lastMessageId, &$lastNotificationId, &$connectionsByUser, $connectDatabase, $sendJson): void {
    $connectDatabase();
    Timer::add(0.25, static function () use (&$database, &$lastMessageId, &$lastNotificationId, &$connectionsByUser, $connectDatabase, $sendJson): void {
        if ($connectionsByUser === []) return;
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
    });
};

Worker::runAll();
