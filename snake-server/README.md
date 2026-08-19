# Project Redemption Snake Server

Dedicated authoritative WebSocket game server for multiplayer Snake.

- Region target: Frankfurt
- Grid: 30×30
- Movement step: 180 ms
- Win target: 15 segments
- Persistent DB writes are checkpoint/heartbeat only, not game ticks.
- Authentication reuses Project Redemption's `REALTIME_SECRET` HMAC token.
