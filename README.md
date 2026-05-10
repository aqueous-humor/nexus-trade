# NexusTrade

Full-stack Forex broker platform built with Laravel 11 + Vue 3.

## Quick Start

```bash
docker compose up -d
```

This starts the full local stack (API, frontend, MySQL, Redis, Soketi, Mailpit, Horizon).

## Project Structure

- [`backend/`](./backend/) — Laravel 11 REST API
- [`frontend/`](./frontend/) — Vue 3 + Vite SPA

## Tech Stack

- **Laravel 11** — PHP 8.3 REST API, Sanctum auth, Horizon queue worker
- **Vue 3 + Vite** — SPA with Composition API and TypeScript
- **MySQL 8** — Primary relational database
- **Redis 7** — Caching, rate limiting, pub/sub
- **Soketi** — Pusher-compatible WebSocket server
- **Mailpit** — Local email testing (dev only)
