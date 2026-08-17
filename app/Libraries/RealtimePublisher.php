<?php

namespace App\Libraries;

use Throwable;

final class RealtimePublisher
{
    public function user(int|array $userIds, string $eventType, array $payload = []): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $userIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return;
        }

        $events = [];
        foreach ($ids as $userId) {
            $events[] = [
                'recipientUserId' => $userId,
                'eventType' => $eventType,
                'payload' => $payload,
            ];
        }

        $this->events($events);
    }

    public function broadcast(string $eventType, array $payload = []): void
    {
        $this->events([[
            'recipientUserId' => null,
            'eventType' => $eventType,
            'payload' => $payload,
        ]]);
    }

    /**
     * @param list<array{recipientUserId:int|null,eventType:string,payload?:array}> $events
     */
    public function events(array $events): void
    {
        if ($events === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach ($events as $event) {
            $eventType = trim((string) ($event['eventType'] ?? ''));
            if ($eventType === '') {
                continue;
            }

            $recipient = $event['recipientUserId'] ?? null;
            $recipient = $recipient === null ? null : (int) $recipient;
            if ($recipient !== null && $recipient < 1) {
                continue;
            }

            $rows[] = [
                'recipient_user_id' => $recipient,
                'event_type' => mb_substr($eventType, 0, 64),
                'payload' => $this->encodePayload((array) ($event['payload'] ?? [])),
                'created_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        try {
            db_connect()->table('realtime_events')->insertBatch($rows);
        } catch (Throwable $exception) {
            log_message(
                'warning',
                'Realtime event yayınlanamadı: '
                . $exception::class
                . ' | '
                . $exception->getMessage()
            );
        }
    }

    private function encodePayload(array $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return '{}';
        }
    }
}
