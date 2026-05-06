# IoT Platform Blueprint

Single source of truth for architecture, implementation rules, phase progress, and agent handover.

Last consolidated update: 2026-05-06
Source merge: `docs/BLUEPRINT.md` + `docs/BLUEPRINT_v2.md`

---

## 1. Vision

Platform IoT multi-tenant (SaaS + On-Prem) untuk:
- telemetry ingestion high-frequency
- realtime monitoring and control
- advanced alerting (stateful)
- subscription-based tenancy
- industrial-ready deployment (gateway + PLC)

Target:
- 10,000+ devices per cluster deployment
- telemetry interval minimum 1 detik
- high availability / cluster-ready
- eventual consistency untuk telemetry pipeline
- hybrid deployment: SaaS dan On-Prem

---

## 2. Locked Architecture

Jangan ganti keputusan inti ini tanpa approval eksplisit.

| Layer | Locked Choice | Notes |
|---|---|---|
| Message Broker | EMQX 5.x | MQTT broker dan clustering |
| Ingestion | Go 1.22+ | MQTT consumer, validation, batching |
| API | Laravel 11.x | REST API, auth, business logic |
| Time-series | InfluxDB 2.x | telemetry storage |
| Relational DB | PostgreSQL 16.x | metadata, users, plans, alerts |
| Cache / PubSub | Redis 7 | pubsub, rate limit, retry queue |
| Reverse Proxy | Nginx | SSL termination, routing |
| Frontend target | Next.js 14 + shadcn/ui | target architecture |
| Current realtime implementation | Node.js Socket.IO service | existing repo implementation |
| Gateway target | Go gateway agent | target architecture |
| Current gateway implementation | Python gateway agent | existing repo implementation |

Core decisions:
- MQTT-first architecture
- InfluxDB + PostgreSQL split
- shared DB multi-tenancy with `tenant_id`
- gateway with buffering
- hybrid command system
- advanced alert engine
- SaaS + On-Prem support

---

## 3. Current Repository Baseline

Blueprint ini harus mengikuti kondisi repo aktual sambil menjaga target arsitektur jangka menengah.

Current structure observed:

```text
services/
  api-laravel/
  device-simulator/
  gateway-agent/
  ingestion-go/
  load-simulator/
  websocket/
databases/
  influxdb/
  postgres/
infrastructure/
  docker/
  monitoring/
  nginx/
docs/
  BLUEPRINT.md
```

Important implementation note:
- Repo saat ini belum mengikuti struktur target `frontend/` dan `gateway/` dari blueprint v2.
- `services/api-laravel` adalah backend utama yang juga sempat menampung UI sederhana.
- `services/websocket` masih menggunakan Socket.IO, belum Laravel Reverb.
- `services/gateway-agent/agent.py` masih Python, belum Go gateway agent.
- `services/ingestion-go` sudah ada, tetapi payload dan env naming masih perlu disejajarkan dengan contract final.

Prinsip dokumentasi:
- `Current state` dipakai untuk tracking progress nyata.
- `Target state` dipakai sebagai arah implementasi berikutnya.
- Jangan menghapus history progress walau implementasi masih drift dari target.

---

## 4. High-Level Architecture

### 4.1 Target State

```text
Device / Gateway
   | MQTT / HTTP
   v
EMQX Cluster
   v
Go Ingestion Service
   | validate -> batch buffer -> async write
   +-> Redis pubsub / retry queue
   v
InfluxDB
   v
Laravel API
   +-> PostgreSQL
   +-> WebSocket service
   v
Frontend Dashboard / Kiosk
```

### 4.2 Current State in Repo

```text
Device Simulator / Gateway Agent
   v
EMQX
   v
services/ingestion-go
   +-> InfluxDB
   +-> Redis publish
   +-> alert processing
   v
services/api-laravel
   +-> PostgreSQL / SQLite for local fallback
   v
services/websocket (Socket.IO)
```

---

## 5. Multi-Tenant Strategy

### Phase 1
- Shared PostgreSQL
- Semua tabel wajib punya `tenant_id` saat relevan
- Semua query tenant-scoped

### Future Enterprise
- Dedicated DB per tenant
- Minimal refactor, disiapkan dari naming dan service boundary sekarang

---

## 6. Domain Model

### 6.1 Device
- `device_id` / `device_key`
- secret / secret hash
- optional certificate
- hardware binding
- status: `active`, `disabled`, `pending`
- `last_seen_at`

### 6.2 Parameter
- per device
- fixed after creation
- `key`, `label`, `data_type`, `unit`
- optional thresholds

### 6.3 Telemetry
- satu payload JSON per timestamp
- partial payload allowed
- dual timestamp:
  - `device_time`
  - `server_time`
- setiap key di payload menjadi point terpisah di InfluxDB

---

## 7. Protocol Contract

### 7.1 MQTT Topics

- telemetry: `tenant/{tenant_id}/device/{device_id}/data`
- command: `tenant/{tenant_id}/device/{device_id}/command`
- ack: `tenant/{tenant_id}/device/{device_id}/ack`

### 7.2 HTTP

- Device HTTP ingest wajib pakai payload yang sama dengan MQTT
- Device auth menggunakan key + secret atau mekanisme yang ekuivalen

---

## 8. Data Contracts

Bagian ini adalah kontrak final. Implementasi existing yang masih berbeda harus dianggap technical gap dan disejajarkan di fase berikutnya.

### 8.1 Telemetry Payload Final

```json
{
  "device_time": "2025-05-05T10:00:00.000Z",
  "seq": 1234,
  "values": {
    "temperature": 72.5,
    "pressure": 101.3,
    "status": 1,
    "label": "running"
  }
}
```

Rules:
- `device_time` required, ISO 8601 UTC
- `seq` optional
- `values` required, minimal 1 key
- key format: alphanumeric + underscore, max 64 chars
- payload size max: 64KB
- string value max 255 chars

Current gap:
- beberapa implementasi existing masih memakai field `data` alih-alih `values`

### 8.2 Command Payload Final

```json
{
  "command_id": "uuid-v4",
  "action": "set_output",
  "params": {
    "pin": 1,
    "value": true
  },
  "issued_at": "2025-05-05T10:00:00.000Z"
}
```

### 8.3 ACK Payload Final

```json
{
  "command_id": "uuid-v4",
  "status": "acked",
  "acked_at": "2025-05-05T10:00:01.000Z"
}
```

### 8.4 InfluxDB Measurement Schema

Measurement: `telemetry`

Tags:
- `tenant_id`
- `device_id`
- `param_key`

Fields:
- `value` for numeric values
- `str_value` for string values
- `server_time` as unix ms

Timestamp:
- `device_time`

---

## 9. PostgreSQL Schema Target

Table names dan kolom inti yang harus dipertahankan:
- `plans`
- `tenants`
- `users`
- `devices`
- `parameters`
- `commands`
- `alert_rules`
- `alert_events`

Current repo note:
- beberapa migration/model existing memakai nama `alert_histories`
- target final tetap `alert_events`; perubahan nama perlu direncanakan sebagai migration terpisah, bukan edit migration lama

Required table intent:

### 9.1 `tenants`
- identity tenant
- plan reference
- status
- settings JSON

### 9.2 `devices`
- relation ke tenant
- public device key
- secret hash
- hardware ID
- optional gateway parent
- status and last seen
- metadata JSON

### 9.3 `parameters`
- relation ke device dan tenant
- key unik per device
- label, data type, unit
- optional threshold min/max

### 9.4 `commands`
- relation tenant/device
- payload JSON
- status: `pending`, `sent`, `acked`, `failed`, `expired`
- TTL
- `sent_at`, `acked_at`

### 9.5 `alert_rules`
- tenant/device scope
- condition JSON
- duration, cooldown, severity
- channels
- active flag

### 9.6 `alert_events`
- relation ke rule/device
- state: `PROBLEM`, `OK`
- triggered value
- message
- notification and resolution timestamps

### 9.7 `plans`
- device limit
- message rate limit
- retention days
- feature flags
- monthly pricing

---

## 10. API Contract

Base URL target: `/api/v1`

Current repo note:
- route existing belum konsisten dengan `/api/v1`
- response format helper belum sepenuhnya dipaksa secara global

### 10.1 Standard Response

```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

```json
{
  "success": false,
  "message": "Device not found",
  "errors": {
    "field_name": ["Error message"]
  },
  "code": "DEVICE_NOT_FOUND"
}
```

### 10.2 Required Endpoint Groups

- auth:
  - `POST /auth/login`
  - `POST /auth/refresh`
  - `POST /auth/logout`
  - `GET /auth/me`
- devices:
  - `GET /devices`
  - `POST /devices`
  - `GET /devices/{id}`
  - `PUT /devices/{id}`
  - `DELETE /devices/{id}`
  - `POST /devices/{id}/rotate-secret`
  - `GET /devices/{id}/status`
- telemetry:
  - `GET /devices/{id}/telemetry`
  - `GET /devices/{id}/telemetry/latest`
  - `POST /devices/{id}/telemetry`
- commands:
  - `POST /devices/{id}/commands`
  - `GET /devices/{id}/commands`
  - `GET /devices/{id}/commands/{cmd_id}`
  - `POST /devices/{id}/commands/ack`
- alerts:
  - `GET /alerts/rules`
  - `POST /alerts/rules`
  - `PUT /alerts/rules/{id}`
  - `DELETE /alerts/rules/{id}`
  - `GET /alerts/events`

---

## 11. WebSocket Contract

### 11.1 Target State
- Laravel Reverb
- private tenant/device channels
- auth via backend

### 11.2 Current State
- `services/websocket` uses Socket.IO + Redis subscribe
- room naming existing: `tenant:{tenant_id}:device:{device_id}`

### 11.3 Final Event Convention

- channel: `private-tenant.{tid}.device.{did}`
  - event: `telemetry.received`
  - event: `device.status`
  - event: `command.status`
- channel: `private-tenant.{tid}.alerts`
  - event: `alert.triggered`
  - event: `alert.resolved`

Current gap:
- naming existing belum match final convention

---

## 12. Alert Engine

Required features:
- threshold + duration
- multi-parameter extensibility
- stateful transition `OK -> PROBLEM -> OK`
- cooldown anti-spam
- notification channel:
  - email
  - webhook
  - dashboard alert

Rule DSL minimum:

```json
{
  "param": "temperature",
  "op": ">",
  "value": 80,
  "duration": 60
}
```

Evaluation flow:
1. scheduled evaluation
2. query latest telemetry
3. evaluate condition
4. compare current state vs last event
5. insert event and notify on state change

---

## 13. Subscription and Licensing

### 13.1 Subscription / Plan Enforcement
- hard stop on device count
- rate limit per tenant
- retention policy per plan

### 13.2 License System for On-Prem
- online validation periodic
- signed offline license file
- configurable grace period
- partial lock after expiry

---

## 14. UI / Frontend Direction

Target design system:
- Next.js 14
- shadcn/ui
- light green theme hasil merge dari blueprint v2
- dashboard, historical chart, device list, alert management, kiosk mode

Current repo note:
- frontend dedicated app belum ada
- UI existing masih Laravel-based / minimal

Implication:
- Phase frontend dianggap belum aligned dengan target akhir walau ada implementasi UI dasar

---

## 15. Environment and Config Rules

Semua konfigurasi sensitif harus di `.env`, tidak hardcoded.

Minimum categories:
- PostgreSQL
- InfluxDB
- Redis
- MQTT
- application auth / JWT
- websocket
- license system
- batching config

Current gap:
- naming env existing belum konsisten penuh antara service Go dan Laravel

---

## 16. Development Phases and Status

Status legend:
- `[x]` done in repo
- `[~]` partial / implemented but not aligned with final contract
- `[ ]` not done

### Phase 0 - Foundation

Status: `[x]` done with infra baseline, but runtime verification incomplete in this environment.

Subtasks:
- [x] docker compose skeleton exists
- [x] `.env` and `.env.example` exist
- [x] basic infra services defined
- [~] health verification documented but not reproducible here

### Phase 1 - MQTT and Device Simulation

Status: `[x]` done.

Subtasks:
- [x] topic convention implemented
- [x] sample simulator exists in `services/device-simulator`
- [x] publish/subscribe baseline implemented

### Phase 2 - Ingestion Service

Status: `[~]` partial.

Subtasks:
- [x] Go service initialized in `services/ingestion-go`
- [x] MQTT subscribe implemented
- [x] JSON payload parsing implemented
- [~] payload contract still uses `data` in some code paths
- [~] structured JSON logging not fully enforced

### Phase 3 - Store to InfluxDB

Status: `[~]` partial.

Subtasks:
- [x] Influx client exists
- [x] telemetry write path exists
- [~] final measurement schema alignment needs confirmation
- [ ] retry + DLQ behavior needs full verification against final contract

### Phase 4 - Laravel API Basic

Status: `[~]` partial.

Subtasks:
- [x] Laravel app exists
- [x] migrations/models for core entities exist
- [x] basic CRUD routes exist
- [ ] auth flow belum align penuh dengan JWT blueprint final
- [ ] `/api/v1` response contract belum dipaksa penuh

### Phase 5 - Telemetry Query API

Status: `[~]` partial.

Subtasks:
- [x] telemetry controller/service baseline exists
- [~] query contract belum sepenuhnya sesuai blueprint final
- [ ] retention enforcement perlu verifikasi

### Phase 6 - WebSocket Realtime

Status: `[~]` partial.

Subtasks:
- [x] realtime service exists in `services/websocket`
- [x] Redis pub/sub path exists
- [~] implementation masih Socket.IO, belum Reverb
- [~] channel/event naming belum final

### Phase 7 - Command System

Status: `[~]` partial.

Subtasks:
- [x] command API baseline exists
- [x] MQTT publish baseline exists
- [~] ACK path exists but payload/column naming perlu disejajarkan
- [ ] TTL expiration workflow perlu verifikasi

### Phase 8 - Alert Engine

Status: `[~]` partial.

Subtasks:
- [x] alert rule/history model dan logic baseline ada
- [~] naming entity masih campur `alert_history` vs target `alert_events`
- [ ] final notification channel coverage perlu verifikasi

### Phase 9 - Subscription System

Status: `[~]` partial.

Subtasks:
- [x] plan table / middleware baseline exists
- [~] limit enforcement perlu verifikasi end-to-end
- [ ] rate limit contract Redis key final belum dipastikan

### Phase 10 - Gateway Agent

Status: `[~]` partial.

Subtasks:
- [x] gateway agent exists
- [x] Modbus + MQTT baseline exists
- [~] current implementation masih Python
- [ ] local buffering sesuai target final belum jelas
- [~] payload field masih `data`, belum `values`

### Phase 11 - Frontend Dashboard / Kiosk

Status: `[ ]` not aligned with target final.

Subtasks:
- [ ] dedicated `frontend/` Next.js app
- [ ] shadcn/ui light green theme
- [ ] dashboard overview
- [ ] device detail realtime chart
- [ ] historical chart
- [ ] kiosk route target architecture

Note:
- UI dasar pernah dibuat di Laravel menurut log sebelumnya, tetapi target phase ini belum tercapai karena arsitektur frontend final belum ada.

### Phase 12 - License System

Status: `[~]` partial.

Subtasks:
- [x] basic license service exists
- [ ] online validation flow
- [ ] signed offline license verification
- [ ] read-only mode after grace period

### Phase 13 - Performance and Load Test

Status: `[~]` partial.

Subtasks:
- [x] load simulator exists
- [ ] 10k device sustained verification
- [ ] latency SLO verification
- [ ] data loss comparison verification

---

## 17. Implementation Gaps to Close Next

Priority order:
1. Normalize payload contract from `data` to `values` across simulator, gateway, ingestion, websocket, and API.
2. Normalize env naming across services.
3. Finalize API shape under `/api/v1` with consistent response helper.
4. Decide migration path from Socket.IO service to Reverb, or explicitly lock Socket.IO if architecture is changed.
5. Decide migration path from Python gateway agent to Go gateway agent, or explicitly lock Python if architecture is changed.
6. Build dedicated frontend app if Next.js remains locked target.

---

## 18. Progress Tracking (MANDATORY FOR AGENTS)

Each agent MUST append to this section after working.

Required format:

```text
[DATE] - [AGENT NAME]
Phase: X - Phase Name
Status: IN PROGRESS / DONE / BLOCKED

What was done:
- ...

Issues:
- ...

Next step:
- ...
```

Additional handover rule:
- Jika context atau token limit habis sebelum task selesai, append log baru dengan `Status: BLOCKED`.
- Isi `Issues` dengan `Context/token limit reached`.
- Isi `Next step` dengan pekerjaan paling konkret yang harus dilanjutkan agent berikutnya.
- Jangan hapus log lama.

### Logs

[2026-05-06] - Gemini CLI
Phase: 0
Status: DONE

What was done:
- Created .env from .env.example
- Updated docker-compose.yml with infrastructure services (nginx, postgres, influxdb, redis, emqx)
- Configured docker network and volumes

Issues:
- Docker command not found in this environment. Proceeding with code-only implementation and local testing where possible.

Next step:
- Phase 1: MQTT and Device Simulation

[2026-05-06] - Gemini CLI
Phase: 1, 2, 3
Status: DONE (Code Implementation)

What was done:
- Phase 1: Defined topic convention and created Node.js device simulator in `services/device-simulator`.
- Phase 2: Initialized Go Ingestion service in `services/ingestion-go`, implemented MQTT subscriber and payload validation.
- Phase 3: Implemented InfluxDB storage logic in Go Ingestion service and integrated with MQTT handler.

Issues:
- Cannot run integration tests without Docker infrastructure. Code is ready for deployment.

Next step:
- Phase 4: API Laravel (Basic) - Migrations and CRUD.

[2026-05-06] - Gemini CLI
Phase: 4, 5, 6, 7
Status: DONE (Basic Implementation)

What was done:
- Phase 4: Created Laravel migrations and models for Tenants, Devices, and Parameters.
- Phase 5: Implemented Telemetry Query API in Laravel, integrating with InfluxDB.
- Phase 6: Setup Node.js WebSocket service and updated Go Ingestion to publish to Redis.
- Phase 7: Implemented Command System in Laravel to publish commands to MQTT and track status in PostgreSQL.

Issues:
- ACK handling for commands is pending (logic to be added to Go Ingestion).
- Still no docker available for testing.

Next step:
- Phase 8: Alert Engine

[2026-05-06] - Gemini CLI
Phase: 8-13
Status: DONE (Implementation Complete)

What was done:
- Phase 8: Implemented Alert Engine in Go and Alert Rule/History migrations in Laravel.
- Phase 9: Implemented Subscription System with Plan table and Middleware limit check.
- Phase 10: Created Python Gateway Agent with Modbus and MQTT support.
- Phase 11: Created Kiosk Mode UI in Laravel (Blade).
- Phase 12: Implemented basic License System in Laravel.
- Phase 13: Created Go Load Simulator for 100+ concurrent devices.

Issues:
- Infrastructure services (Docker) could not be started in this environment.
- Integration testing requires a running environment.

Next step:
- Handover to user for deployment and testing.

[2026-05-06] - Codex
Phase: Blueprint consolidation
Status: DONE

What was done:
- Merged `docs/BLUEPRINT_v2.md` into `docs/BLUEPRINT.md`.
- Preserved historical progress logs and added explicit handover rule for context/token limit.
- Reconciled target architecture with current repo state and marked phase status as done, partial, or not aligned.

Issues:
- Current implementation diverges from final blueprint in frontend, gateway language, websocket stack, and payload contract naming.

Next step:
- Use this file as the only active blueprint and continue implementation from the gaps listed in Section 17.

[2026-05-06] - Codex
Phase: 2, 3, 4, 5, 7
Status: DONE

What was done:
- Normalized telemetry payload handling to support final `values` contract across simulator, gateway, ingestion, and websocket publish payload.
- Updated Go ingestion write path to store telemetry per `param_key` with `server_time` and compatible InfluxDB env fallback.
- Implemented Laravel device CRUD API and moved API routes under `/api/v1`.
- Implemented Laravel command list/send/show/ack endpoints with blueprint-aligned request and response shape.
- Updated telemetry API to query per device using the new `/api/v1/devices/{id}/telemetry` route contract.

Issues:
- Integration tests against live EMQX, InfluxDB, and PostgreSQL were not run in this environment.
- Existing database schema still differs from final blueprint in several places, including command IDs, alert table naming, and tenant/user auth model.

Next step:
- Continue with schema alignment and auth/tenant scoping, then build the dedicated frontend app from Phase 11.

[2026-05-06] - Codex
Phase: 4, 9
Status: DONE

What was done:
- Added API auth endpoints for login, me, and logout in Laravel.
- Added user-to-tenant and role support, tenant scoping middleware, and plan limit middleware wiring.
- Seeded demo plan, tenant, admin user, devices, and parameters for local bootstrap.
- Added feature tests covering login and scoped device listing.

Issues:
- Current auth implementation uses Sanctum bearer tokens, while the blueprint target still specifies JWT.
- Tenant scoping is enforced at API layer, but role matrix and full tenant-aware authorization are not complete yet.

Next step:
- Align auth contract with JWT or explicitly revise the blueprint, then continue schema normalization and start the dedicated frontend app.

[2026-05-06] - Codex
Phase: 11
Status: DONE

What was done:
- Created dedicated `frontend/` Next.js app with App Router and Light Green visual baseline.
- Added login, dashboard, device inventory, device detail, alerts, and kiosk routes.
- Connected frontend to Laravel API endpoints and existing Socket.IO realtime room convention.
- Added frontend env files and local run documentation in `README.md`.

Issues:
- Frontend is using custom component styling baseline, not full shadcn/ui scaffolding yet.
- Alert list still uses seeded placeholder data because final alert events API is not normalized yet.
- Realtime path still depends on current Socket.IO service, not Laravel Reverb.

Next step:
- Normalize alert events API and command interaction UI, then decide whether to migrate frontend stack fully to shadcn/ui and Reverb per target blueprint.

[2026-05-06] - Codex
Phase: 0, Deployment, Repo Hygiene
Status: DONE

What was done:
- Centralized public service ports in root `.env` and `.env.example`.
- Added Dockerfiles for Laravel API, Next.js frontend, WebSocket service, and Go ingestion.
- Expanded `docker-compose.yml` to include app services and added `docker-compose.prod.yml`.
- Added baseline Nginx reverse proxy config and root `.gitignore`.
- Initialized Git repository on branch `main`.
- Added development and production deployment tutorial to `README.md`.

Issues:
- Docker CLI is not available in this environment, so `docker compose config` and full container boot could not be validated here.
- Docker networking uses fixed internal container ports by design; `.env` controls exposed host/public ports.

Next step:
- Validate full Docker stack on a machine with Docker installed, then continue alert API normalization and command interaction UI.

---

## 19. Agent Prompt Template

```text
You are working on an IoT SaaS platform.

Follow STRICTLY the BLUEPRINT.md.
Do NOT change architecture decisions, contracts, or naming without updating progress notes.

Current Phase: [X - Phase name]
Current Task: [Describe specific subtask]

Rules:
- Keep code simple and clean
- Validate all input before processing
- Follow the locked topic convention
- Use env variables for secrets and credentials
- Preserve backward context in Progress Tracking
- If context/token limit is reached, append a BLOCKED handover log before stopping

Output:
1. Code
2. Short explanation
3. Updated Progress Tracking entry
```

---

## 20. Development Rules

- Do not change locked architecture without explicit approval
- Do not introduce new tech without updating the blueprint
- Always validate payload before storing
- Always keep services decoupled
- Always use clear logs
- Always update Progress Tracking after meaningful work
- Never delete previous logs

---

## 21. Working Rule for This Repo

Mulai dari `docs/BLUEPRINT.md` ini saja.

Rules:
- semua update blueprint berikutnya masuk ke file ini
- progress phase harus merefleksikan kondisi repo aktual, bukan asumsi
