# IoT Platform

Active source of truth: [docs/BLUEPRINT.md](docs/BLUEPRINT.md)

## Current stack

- Frontend: Next.js 14 in `frontend/`
- API: Laravel in `services/api-laravel`
- Realtime: Socket.IO in `services/websocket`
- Ingestion: Go in `services/ingestion-go`
- Infra: PostgreSQL, InfluxDB, Redis, EMQX, Nginx via Docker Compose

## Repository hygiene

This repository now includes a root `.gitignore` for files that should not go to GitHub:

- all `.env` secrets except `.env.example`
- `vendor/`, `node_modules/`, `.next/`
- Laravel cache, logs, built assets, sqlite local db
- local runtime logs from dev servers
- editor and OS junk
- generated database volume contents

## Port configuration

All public service ports are controlled from the root `.env`.

Important rule:
- values in `.env` are **host/public ports**
- Docker internal ports stay fixed so services can still talk to each other reliably

Main variables:

```env
NGINX_HTTP_PORT=80
NGINX_HTTPS_PORT=443
API_PORT=8000
FRONTEND_PORT=3001
WEBSOCKET_PORT=3000
POSTGRES_PORT=5432
INFLUXDB_PORT=8086
REDIS_PORT=6379
MQTT_PORT=1883
MQTT_WS_PORT=8083
MQTT_WSS_PORT=8084
MQTT_TLS_PORT=8883
EMQX_DASHBOARD_PORT=18083
```

For browser-facing frontend build values:

```env
NEXT_PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api/v1
NEXT_PUBLIC_WS_URL=http://127.0.0.1:3000
```

If you change `API_PORT` or `WEBSOCKET_PORT`, update `NEXT_PUBLIC_API_BASE_URL` and `NEXT_PUBLIC_WS_URL` too.

## Demo account

- email: `admin@demo.local`
- password: `password`

## Development deployment

### Option A: Docker for full stack

1. Copy env:

```powershell
Copy-Item .env.example .env
```

2. Review ports in `.env`.

3. Start all services:

```powershell
docker compose up -d --build
```

4. Run Laravel seed inside API container once stack is up:

```powershell
docker compose exec api php artisan migrate:fresh --seed
```

5. Open services:

- Frontend: `http://127.0.0.1:${FRONTEND_PORT}`
- API: `http://127.0.0.1:${API_PORT}`
- InfluxDB UI: `http://127.0.0.1:${INFLUXDB_PORT}`
- EMQX dashboard: `http://127.0.0.1:${EMQX_DASHBOARD_PORT}`
- Nginx entrypoint: `http://127.0.0.1:${NGINX_HTTP_PORT}`

### Option B: Hybrid local run

Use Docker only for infra, run app services from host.

1. Start infra:

```powershell
docker compose up -d postgres influxdb redis emqx
```

2. Backend API:

```powershell
cd services/api-laravel
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

3. Frontend:

```powershell
cd frontend
npm install
npm run dev -- --hostname 127.0.0.1 --port 3001
```

4. Optional realtime:

```powershell
cd services/websocket
npm install
node index.js
```

## Production deployment

### Recommended baseline

Use Docker Compose with the production override:

```powershell
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### Production checklist

1. Copy `.env.example` to `.env`
2. Set:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - secure `DB_PASSWORD`
   - secure `INFLUXDB_TOKEN`
   - secure `LICENSE_KEY`
   - real public URLs for:
     - `APP_URL`
     - `FRONTEND_URL`
     - `WEBSOCKET_URL`
     - `NEXT_PUBLIC_API_BASE_URL`
     - `NEXT_PUBLIC_WS_URL`
3. Change public ports if needed
4. Put TLS termination in front of Nginx or extend current Nginx config for SSL certs
5. Seed only when bootstrapping a new environment:

```powershell
docker compose exec api php artisan migrate --force
```

### Production smoke test

```powershell
Invoke-WebRequest http://127.0.0.1:${API_PORT}/api/v1/auth/login -Method POST -ContentType 'application/json' -Body '{"email":"admin@demo.local","password":"password"}'
```

## Useful commands

Rebuild one service:

```powershell
docker compose build frontend
docker compose build api
docker compose build websocket
docker compose build ingestion
```

View logs:

```powershell
docker compose logs -f frontend
docker compose logs -f api
docker compose logs -f websocket
docker compose logs -f ingestion
```

Stop stack:

```powershell
docker compose down
```

Stop stack and remove volumes:

```powershell
docker compose down -v
```

## Current known gaps

- Auth implementation is still Sanctum bearer token, while blueprint target says JWT
- Realtime still uses Socket.IO, while blueprint target says Laravel Reverb
- Alert UI is wired, but final alert events API is not normalized yet
- Nginx config is a functional baseline, not a hardened production reverse proxy yet
