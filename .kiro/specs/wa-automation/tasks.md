# Tasks: WA Automation

## Phase 1: Foundation & Database (REQUIRED)

- [x] 1.1 Setup project configuration dan environment
  - Konfigurasi environment variables (.env) untuk Baileys Gateway URL, webhook secret, database, queue driver, dan mail server
  - Setup Laravel Boost dan MCP integration
  - Konfigurasi queue driver menggunakan database
  - Setup logging dan monitoring

- [x] 1.2 Create database migrations untuk semua tabel
  - Migration untuk tabel `tenants` dengan status dan trial tracking
  - Migration untuk tabel `users` dengan tenant association dan role-based access
  - Migration untuk tabel `plans` dengan fitur dan kuota configuration
  - Migration untuk tabel `subscriptions` dengan status dan quota tracking
  - Migration untuk tabel `invoices` dengan billing records
  - Migration untuk tabel `devices` dengan gateway integration
  - Migration untuk tabel `templates` dengan variable support
  - Migration untuk tabel `broadcasts` dengan progress tracking
  - Migration untuk tabel `message_logs` dengan comprehensive logging
  - Migration untuk tabel `reminders` dengan scheduling configuration
  - Migration untuk tabel `reminder_logs` untuk mencegah duplikasi
  - Migration untuk tabel `keyword_rules` dengan priority ordering
  - Migration untuk tabel `auto_reply_logs` dengan matching tracking
  - Migration untuk tabel `auto_reply_cooldowns` untuk throttling
  - Migration untuk tabel `api_tokens` dengan secure hashing
  - Migration untuk tabel `system_logs` dengan global logging
  - Migration untuk tabel `alerts` dengan severity levels
  - Migration untuk tabel `gateway_instances` untuk multi-instance support
  - Migration untuk tabel `system_configs` dengan key-value configuration

- [x] 1.3 Create Eloquent models dengan relationships
  - Model `Tenant` dengan relationships ke users, subscriptions, devices, templates, broadcasts, message_logs, reminders, keyword_rules, api_tokens, invoices, alerts
  - Model `User` dengan relationships ke tenant dan roles
  - Model `Plan` dengan relationships ke subscriptions dan invoices
  - Model `Subscription` dengan relationships ke tenant, plan, dan invoices
  - Model `Invoice` dengan relationships ke tenant, plan, subscription, dan recorded_by user
  - Model `Device` dengan relationships ke tenant, message_logs, broadcasts, reminders, keyword_rules, auto_reply_logs
  - Model `Template` dengan relationships ke tenant, broadcasts, reminders, message_logs
  - Model `Broadcast` dengan relationships ke tenant, device, template, message_logs
  - Model `MessageLog` dengan relationships ke tenant, device, broadcast, reminder, template, job_id tracking
  - Model `Reminder` dengan relationships ke tenant, device, template, reminder_logs
  - Model `ReminderLog` dengan relationships ke reminder
  - Model `KeywordRule` dengan relationships ke tenant, device, auto_reply_logs, auto_reply_cooldowns
  - Model `AutoReplyLog` dengan relationships ke tenant, device, keyword_rule
  - Model `AutoReplyCooldown` dengan relationships ke device, keyword_rule
  - Model `ApiToken` dengan relationships ke tenant
  - Model `SystemLog` dengan relationships ke tenant dan user
  - Model `Alert` dengan relationships ke tenant dan resolved_by user
  - Model `GatewayInstance` untuk managing Baileys instances
  - Model `SystemConfig` untuk global configuration

- [x] 1.4 Create factories dan seeders
  - Factory untuk `Tenant` dengan berbagai status (active, trial, suspended, expired)
  - Factory untuk `User` dengan berbagai role (superadmin, admin, member)
  - Factory untuk `Plan` dengan berbagai tier (Starter, Pro, Business, Pay-per-message)
  - Factory untuk `Subscription` dengan berbagai status
  - Factory untuk `Device` dengan berbagai status koneksi
  - Factory untuk `Template` dengan berbagai tipe
  - Factory untuk `Broadcast` dengan berbagai status
  - Factory untuk `MessageLog` dengan berbagai status pengiriman
  - Factory untuk `Reminder` dengan berbagai tipe
  - Factory untuk `KeywordRule` dengan priority variation
  - Factory untuk `ApiToken`
  - Seeder untuk default Plans
  - Seeder untuk default SystemConfigs
  - Seeder untuk demo Tenant dengan users, devices, templates, dan keyword rules

---

## Phase 2: Core Services (REQUIRED)

- [x] 2.1 Implement DeviceService
  - Interface `DeviceServiceInterface` dengan methods: requestConnection, confirmConnection, checkConnectionStatus, disconnect, canAddDevice
  - Implementation `DeviceService` dengan logic untuk:
    - Validasi batas device berdasarkan paket langganan
    - Request QR code dari Baileys Gateway
    - Confirm koneksi dan simpan session data terenkripsi
    - Health check koneksi setiap 60 detik
    - Update status device saat terputus
    - Disconnect dan cleanup session data
  - Exception handling untuk gateway errors dan connection failures

- [x] 2.2 Implement MessageService
  - Interface `MessageServiceInterface` dengan methods: sendSingle, renderTemplate, dispatchJob
  - Implementation `MessageService` dengan logic untuk:
    - Validasi nomor tujuan format
    - Render template dengan variable substitution
    - Create MessageLog entry
    - Dispatch SendMessageJob ke queue dengan delay
    - Handle template variable missing dengan graceful fallback
  - Integration dengan QuotaService untuk decrement quota

- [x] 2.3 Implement QuotaService
  - Interface `QuotaServiceInterface` dengan methods: getRemainingQuota, decrement, reset, isExhausted, isUnlimited
  - Implementation `QuotaService` dengan logic untuk:
    - Thread-safe quota tracking menggunakan database locks
    - Get remaining quota dari subscription
    - Decrement quota dengan atomic operation
    - Reset quota saat subscription renewal
    - Check unlimited quota untuk Business plan
  - Exception handling untuk QuotaExceededException

- [x] 2.4 Implement BaileysGatewayClient
  - Interface `BaileysGatewayClientInterface` dengan methods: sendMessage, getQrCode, getConnectionStatus, disconnectDevice, restartInstance
  - Implementation `BaileysGatewayClient` dengan logic untuk:
    - HTTP client wrapper untuk Baileys Gateway
    - Send message dengan timeout 30 detik
    - Get QR code untuk device connection
    - Get connection status untuk health check
    - Disconnect device dan cleanup
    - Restart instance untuk error recovery
  - Signature validation untuk webhook requests
  - Error handling dan retry logic untuk network failures

- [x] 2.5 Implement TemplateService
  - Interface `TemplateServiceInterface` dengan methods: render, validate, getVariables
  - Implementation `TemplateService` dengan logic untuk:
    - Parse template dan extract variables
    - Render template dengan context data
    - Validate template format dan length
    - Handle missing variables dengan empty string fallback
    - Log warnings untuk missing variables
  - Support untuk template types: notification, promo, reminder

- [x] 2.6 Implement BroadcastService
  - Interface `BroadcastServiceInterface` dengan methods: createFromCsv, createFromRecipients, dispatch, cancel, getProgress
  - Implementation `BroadcastService` dengan logic untuk:
    - Parse dan validate CSV file (max 5MB, valid phone numbers)
    - Create broadcast session dengan recipient list
    - Validate recipient count vs quota
    - Dispatch SendMessageJob untuk setiap recipient dengan random delay 5-10 detik
    - Track progress: total, sent, failed, pending
    - Cancel broadcast dan remove pending jobs
    - Handle quota exhaustion dengan partial dispatch
  - CSV validation dan error reporting

---

## Phase 3: Queue & Scheduling (REQUIRED)

- [x] 3.1 Create SendMessageJob dengan retry logic
  - Job class `SendMessageJob` dengan properties: tries=3, backoff=[30,60,120], timeout=30
  - Handle method untuk:
    - Validasi subscription status sebelum send
    - Call BaileysGatewayClient::sendMessage
    - Update MessageLog status ke "sent" saat sukses
    - Handle exception dengan retry logic
    - Update MessageLog status ke "failed" setelah max retries
    - Create alert untuk job failed permanent
  - Failed method untuk cleanup dan logging

- [x] 3.2 Create ProcessWebhookJob
  - Job class `ProcessWebhookJob` untuk handle incoming webhook dari Baileys Gateway
  - Handle method untuk:
    - Validate webhook signature menggunakan BAILEYS_WEBHOOK_SECRET
    - Parse webhook payload (event, device_id, from, message, timestamp)
    - Call AutoReplyService::processIncomingMessage
    - Log incoming message ke AutoReplyLog
    - Handle matching dan reply dispatch
  - Error handling untuk invalid signatures dan malformed payloads

- [x] 3.3 Create SendReminderJob
  - Job class `SendReminderJob` untuk handle reminder pengiriman
  - Handle method untuk:
    - Get reminder configuration
    - Query data yang memenuhi kondisi reminder
    - Check ReminderLog untuk mencegah duplikasi dalam 24 jam
    - Create MessageLog dan dispatch SendMessageJob untuk setiap recipient
    - Update ReminderLog dengan sent timestamp
  - Support untuk reminder types: spp_due, invoice_unpaid, booking_tomorrow

- [x] 3.4 Create Scheduler commands
  - Command `reminder:process` untuk menjalankan reminder check setiap hari pukul 07:00 WIB
    - Query semua active reminders
    - Dispatch SendReminderJob untuk setiap reminder
    - Log execution summary
  
  - Command `log:cleanup` untuk menjalankan pembersihan log setiap minggu
    - Delete message_logs older than 90 days
    - Delete system_logs older than 180 days
    - Log cleanup summary
  
  - Command `device:health-check` untuk menjalankan health check setiap menit
    - Query semua connected devices
    - Call BaileysGatewayClient::getConnectionStatus
    - Update device status jika terputus
    - Create alert jika device error
  
  - Command `subscription:check-expiry` untuk menjalankan pengecekan expiry setiap hari
    - Query subscriptions yang akan expire dalam 7 hari
    - Send email notification ke tenant
    - Query subscriptions yang sudah expired
    - Update tenant status menjadi expired
    - Send email notification untuk expired subscriptions
  
  - Command `trial:check-expiry` untuk menjalankan pengecekan trial expiry setiap hari
    - Query trials yang akan expire dalam 3 hari
    - Send email dan WhatsApp notification
    - Query trials yang akan expire dalam 1 hari
    - Send reminder notification
    - Query trials yang sudah expired
    - Auto-suspend tenant account
  
  - Command `alert:check` untuk menjalankan alert check setiap 5 menit
    - Check gateway instance health
    - Check failed jobs spike (>50 dalam 1 jam)
    - Check quota usage (>90%)
    - Create alerts untuk kondisi yang terdeteksi
    - Send email notification ke superadmin

---

## Phase 4: Authentication & Authorization (REQUIRED)

- [x] 4.1 Setup RBAC middleware
  - Create middleware `TenantMiddleware` untuk:
    - Validate user belongs to tenant
    - Inject tenant context ke request
    - Prevent cross-tenant data access
  
  - Create middleware `RoleMiddleware` untuk:
    - Check user role (superadmin, admin, member)
    - Authorize access berdasarkan role
    - Return 403 untuk unauthorized access
  
  - Create middleware `FeatureMiddleware` untuk:
    - Check subscription plan features
    - Block access ke fitur yang tidak tersedia di plan
    - Return 403 dengan informasi upgrade

- [x] 4.2 Implement API token authentication
  - Create middleware `ApiTokenMiddleware` untuk:
    - Extract token dari Authorization header
    - Validate token hash terhadap database
    - Check token tidak revoked
    - Inject tenant context dari token
    - Return 401 untuk invalid/revoked token
  
  - Create service untuk:
    - Generate API token dengan random string
    - Hash token menggunakan SHA-256
    - Store token hash di database
    - Revoke token dengan soft delete
    - Track last_used_at untuk audit

- [x] 4.3 Create authorization policies
  - Policy `DevicePolicy` untuk:
    - authorize user dapat view/create/update/delete device
    - Check tenant ownership
  
  - Policy `TemplatePolicy` untuk:
    - authorize user dapat view/create/update/delete template
    - Check tenant ownership
    - Prevent delete jika digunakan oleh active reminder
  
  - Policy `BroadcastPolicy` untuk:
    - authorize user dapat view/create/cancel broadcast
    - Check tenant ownership
  
  - Policy `KeywordRulePolicy` untuk:
    - authorize user dapat view/create/update/delete keyword rule
    - Check tenant ownership
  
  - Policy `ApiTokenPolicy` untuk:
    - authorize user dapat view/create/revoke api token
    - Check tenant ownership

---

## Phase 5: Tenant Dashboard (REQUIRED)

- [x] 5.0 Create authentication and landing pages
  - Page `welcome` (landing page) untuk:
    - Hero section dengan value proposition WA Automation
    - Features section dengan highlight fitur utama
    - Pricing section dengan comparison table untuk semua plans
    - Testimonials section (optional)
    - CTA section dengan link ke WhatsApp untuk order (wa.me/6281529211963)
    - Footer dengan links dan copyright
  
  - Page `auth.login` untuk:
    - Login form dengan email dan password
    - Remember me checkbox
    - Forgot password link
    - Error messages untuk invalid credentials
    - Redirect ke dashboard setelah login sukses
  
  - Page `auth.register` untuk:
    - Registration form dengan: name, email, phone, password, password_confirmation
    - Terms and conditions checkbox
    - Auto-create tenant dengan trial period
    - Auto-login setelah registration sukses
    - Redirect ke dashboard dengan welcome message
  
  - Page `auth.forgot-password` untuk:
    - Form untuk request password reset
    - Input: email
    - Send reset link via email
    - Success message setelah email terkirim
  
  - Page `auth.reset-password` untuk:
    - Form untuk reset password dengan token
    - Input: email, password, password_confirmation, token (hidden)
    - Validate token dan update password
    - Redirect ke login dengan success message
  
  - Page `auth.verify-email` untuk:
    - Email verification notice
    - Resend verification email button
    - Logout button
  
  - Controller `AuthController` atau gunakan Laravel Breeze/Fortify untuk:
    - Handle login, register, logout
    - Handle password reset flow
    - Handle email verification
    - Auto-create tenant saat registration
    - Auto-assign trial subscription

- [x] 5.1 Create device management pages
  - Page `devices.index` untuk:
    - Display list semua devices dengan status
    - Show last_seen_at untuk setiap device
    - Display action buttons: connect, disconnect, delete
    - Show device limit berdasarkan plan
  
  - Page `devices.connect` untuk:
    - Request QR code dari DeviceService
    - Display QR code dengan auto-refresh setiap 5 detik
    - Show connection status
    - Handle timeout jika QR code tidak di-scan dalam 5 menit
  
  - Page `devices.show` untuk:
    - Display device details: name, phone_number, status, last_seen_at
    - Show device usage statistics
    - Display action buttons: rename, disconnect, delete

- [x] 5.2 Create template management pages
  - Page `templates.index` untuk:
    - Display list semua templates dengan type
    - Show template content preview
    - Display action buttons: edit, delete, duplicate
    - Filter by type: notification, promo, reminder
  
  - Page `templates.create` untuk:
    - Form untuk create template baru
    - Input: name, type, content
    - Variable editor dengan syntax highlighting
    - Preview render dengan sample data
    - Validation: name required, content max 4096 chars
  
  - Page `templates.edit` untuk:
    - Form untuk edit template existing
    - Same fields sebagai create
    - Show usage count (broadcasts, reminders)
  
  - Page `templates.show` untuk:
    - Display template details
    - Show list broadcasts/reminders yang menggunakan template
    - Display action buttons: edit, delete, duplicate

- [x] 5.3 Create broadcast pages
  - Page `broadcasts.create` untuk:
    - Form untuk create broadcast baru
    - Select device, template, recipient source (CSV/database)
    - CSV upload dengan validation
    - Preview recipient list dengan validation errors
    - Confirm dialog dengan quota warning jika needed
    - Submit button untuk dispatch broadcast
  
  - Page `broadcasts.index` untuk:
    - Display list semua broadcasts dengan status
    - Show progress: total, sent, failed, pending
    - Filter by status: draft, queued, running, completed, cancelled, failed
    - Display action buttons: view, cancel, retry
  
  - Page `broadcasts.show` untuk:
    - Display broadcast details dan progress
    - Real-time progress update menggunakan polling/websocket
    - Display message_logs untuk broadcast
    - Show statistics: sent, failed, pending
    - Display action buttons: cancel, retry failed

- [x] 5.4 Create message log pages
  - Page `message-logs.index` untuk:
    - Display list semua message logs
    - Filter by: date range, status, device, recipient, source
    - Show columns: recipient, message preview, status, sent_at, device
    - Display action buttons: view, retry (untuk failed)
    - Export to CSV functionality
  
  - Page `message-logs.show` untuk:
    - Display message log details
    - Show full message content
    - Show error message jika failed
    - Show retry history
    - Display action buttons: retry, delete

- [x] 5.5 Create subscription/pricing pages
  - Page `subscription.index` untuk:
    - Display current subscription status
    - Show plan name, start date, end date, remaining quota
    - Display plan features comparison table
    - Show available plans untuk upgrade/downgrade
    - Display action buttons: upgrade, downgrade, renew
    - Show trial status jika dalam trial period
  
  - Page `subscription.plans` untuk:
    - Display all active plans dengan pricing
    - Show plan features: quota, devices, reminder, api
    - Display "Subscribe" button untuk setiap plan
    - Link ke WhatsApp contact untuk ordering

- [x] 5.6 Create API token management pages
  - Page `api-tokens.index` untuk:
    - Display list semua API tokens
    - Show token name, created_at, last_used_at
    - Display action buttons: copy, revoke, delete
    - Display "Create Token" button
  
  - Page `api-tokens.create` untuk:
    - Form untuk create API token baru
    - Input: token name
    - Generate token dan display plaintext hanya sekali
    - Show copy button untuk copy token
    - Warning: token tidak dapat ditampilkan kembali
  
  - Page `api-tokens.show` untuk:
    - Display token details
    - Show usage statistics: total requests, last_used_at
    - Display action buttons: revoke, delete

---

## Phase 6: Superadmin Dashboard (REQUIRED)

- [x] 6.1 Create tenant management pages
  - Page `admin.tenants.index` untuk:
    - Display list semua tenants dengan status
    - Show columns: name, email, plan, status, created_at, message_usage
    - Filter by: status, plan, created_date_range
    - Display action buttons: view, edit, suspend, activate, delete
  
  - Page `admin.tenants.show` untuk:
    - Display tenant details: name, email, phone, status
    - Show subscription info: plan, start_date, end_date, quota_used
    - Show device count dan status
    - Show message statistics: total sent, total failed
    - Display action buttons: edit, suspend, activate, delete, extend_trial
  
  - Page `admin.tenants.edit` untuk:
    - Form untuk edit tenant data
    - Input: name, email, phone, status
    - Suspend/activate functionality dengan reason input
  
  - Page `admin.tenants.create` untuk:
    - Form untuk create tenant baru
    - Input: name, email, phone
    - Auto-assign trial period

- [x] 6.2 Create plan management pages
  - Page `admin.plans.index` untuk:
    - Display list semua plans
    - Show columns: name, price, quota, devices, features, is_active
    - Display action buttons: edit, delete, toggle_active
  
  - Page `admin.plans.create` untuk:
    - Form untuk create plan baru
    - Input: name, slug, price, message_quota, max_devices, features
    - Checkbox untuk features: reminder, api, multi_device
    - Validation: name required, price >= 0, quota >= 0 atau unlimited
  
  - Page `admin.plans.edit` untuk:
    - Form untuk edit plan existing
    - Same fields sebagai create
    - Warning: changes hanya apply ke subscription baru

- [x] 6.3 Create billing/invoice management pages
  - Page `admin.invoices.index` untuk:
    - Display list semua invoices
    - Show columns: tenant, plan, amount, paid_at, recorded_by
    - Filter by: tenant, plan, date_range
    - Display action buttons: view, edit, delete
  
  - Page `admin.invoices.create` untuk:
    - Form untuk record pembayaran baru
    - Select: tenant, plan, duration_days
    - Input: amount, paid_at, notes
    - Auto-calculate subscription period
    - Submit button untuk activate/extend subscription
  
  - Page `admin.invoices.show` untuk:
    - Display invoice details
    - Show subscription yang dibuat dari invoice
    - Display action buttons: edit, delete, resend_email

- [x] 6.4 Create monitoring dashboard
  - Page `admin.dashboard` untuk:
    - Display real-time statistics:
      - Total messages sent today
      - Total active tenants
      - Total connected devices
      - Total revenue this month
    - Display graphs:
      - Message sent trend (30 days)
      - Revenue trend (30 days)
      - Top 10 tenants by message usage
    - Display alerts section dengan active alerts
    - Display gateway status section

- [x] 6.5 Create gateway management pages
  - Page `admin.gateways.index` untuk:
    - Display list semua gateway instances
    - Show columns: name, base_url, status, last_checked_at
    - Display status indicator: active (green), inactive (gray), error (red)
    - Display action buttons: view, restart, delete
  
  - Page `admin.gateways.show` untuk:
    - Display gateway details
    - Show last error message jika ada
    - Show health check history
    - Display action buttons: restart, delete

- [x] 6.6 Create system configuration pages
  - Page `admin.config.index` untuk:
    - Display list semua system configs
    - Show columns: key, value, type, description
    - Display action buttons: edit
  
  - Page `admin.config.edit` untuk:
    - Form untuk edit config value
    - Input type berdasarkan config type: integer, string, boolean, json
    - Validation: value dalam range yang valid
    - Submit button untuk save

- [x] 6.7 Create alert management pages
  - Page `admin.alerts.index` untuk:
    - Display list semua alerts
    - Show columns: type, severity, message, created_at, status
    - Filter by: status, severity, type, date_range
    - Display action buttons: view, resolve, delete
  
  - Page `admin.alerts.show` untuk:
    - Display alert details
    - Show context information
    - Display action buttons: resolve, delete

- [x] 6.8 Create system logs viewer
  - Page `admin.logs.index` untuk:
    - Display list semua system logs
    - Show columns: timestamp, tenant, type, severity, message
    - Filter by: tenant, type, severity, date_range, keyword
    - Display action buttons: view
    - Export to CSV functionality
  
  - Page `admin.logs.show` untuk:
    - Display log details
    - Show full context information
    - Show related logs

---

## Phase 7: API Endpoints (REQUIRED)

- [x] 7.1 Implement POST /api/v1/send-message
  - Route definition dengan API token middleware
  - Request validation: device_id, to, message, template_id (optional)
  - Validate phone number format
  - Check subscription active dan quota available
  - Call MessageService::sendSingle
  - Return 202 dengan job_id dan status "queued"
  - Error handling: 401 unauthorized, 422 validation error, 403 feature not allowed

- [x] 7.2 Implement POST /api/v1/send-bulk
  - Route definition dengan API token middleware
  - Request validation: device_id, recipients (array), message, template_id (optional)
  - Validate recipients count vs quota
  - Call BroadcastService::createFromRecipients
  - Dispatch broadcast
  - Return 202 dengan broadcast_id, total_recipients, status "queued"
  - Error handling: 401 unauthorized, 422 validation error, 403 feature not allowed

- [x] 7.3 Implement GET /api/v1/message-status/{job_id}
  - Route definition dengan API token middleware
  - Query MessageLog berdasarkan job_id
  - Return message status: pending, sent, failed, cancelled
  - Return 404 jika job_id tidak ditemukan
  - Error handling: 401 unauthorized

- [x] 7.4 Implement POST /webhook/baileys
  - Route definition tanpa authentication (signature validation di handler)
  - Validate webhook signature menggunakan BAILEYS_WEBHOOK_SECRET
  - Parse webhook payload
  - Dispatch ProcessWebhookJob
  - Return 200 OK
  - Error handling: 401 invalid signature, 400 malformed payload

---

## Phase 8: Advanced Features (REQUIRED)

- [x] 8.1 Implement AutoReplyService
  - Interface `AutoReplyServiceInterface` dengan methods: processIncomingMessage, matchKeyword, canReply
  - Implementation dengan logic untuk:
    - Parse incoming message dari webhook
    - Query active keyword rules untuk device
    - Match message content dengan keywords (case-insensitive)
    - Select highest priority rule jika multiple matches
    - Check cooldown untuk mencegah loop (1 reply per nomor per keyword per 60 menit)
    - Create MessageLog dan dispatch SendMessageJob untuk reply
    - Log incoming message ke AutoReplyLog
  - Cooldown management dengan auto-cleanup expired cooldowns

- [x] 8.2 Implement SubscriptionService
  - Interface `SubscriptionServiceInterface` dengan methods: activate, extend, isFeatureAllowed, checkExpiry
  - Implementation dengan logic untuk:
    - Create subscription dari invoice
    - Set subscription period dan quota
    - Extend subscription dengan additional days
    - Check feature availability berdasarkan plan
    - Check subscription expiry dan update tenant status
    - Send expiry notifications
  - Integration dengan QuotaService untuk reset quota

- [x] 8.3 Implement BillingService
  - Interface `BillingServiceInterface` dengan methods: recordPayment, activateSubscription, extendSubscription, getRevenue
  - Implementation dengan logic untuk:
    - Create invoice dari payment record
    - Activate subscription setelah payment
    - Extend subscription untuk renewal
    - Calculate revenue per period/plan/tenant
    - Send billing notifications
  - Integration dengan SubscriptionService

- [x] 8.4 Implement AlertService
  - Interface `AlertServiceInterface` dengan methods: create, resolve, notifySuperadmin
  - Implementation dengan logic untuk:
    - Create alert dengan type, severity, message, context
    - Resolve alert dengan resolved_by user
    - Send email notification ke superadmin
    - Track alert status dan resolution time
  - Support untuk alert types: gateway.down, quota.90pct, jobs.failed_spike, subscription.expiring, trial.expiring

- [x] 8.5 Implement ReminderService
  - Interface `ReminderServiceInterface` dengan methods: processReminders, checkCondition, sendReminder
  - Implementation dengan logic untuk:
    - Query active reminders
    - Check condition untuk setiap reminder type
    - Query recipients yang memenuhi kondisi
    - Check ReminderLog untuk mencegah duplikasi
    - Create MessageLog dan dispatch SendMessageJob
    - Update ReminderLog dengan sent timestamp
  - Support untuk reminder types: spp_due, invoice_unpaid, booking_tomorrow

---

## Phase 9: Testing (REQUIRED)

- [x] 9.1 Write unit tests untuk services
  - Unit tests untuk `DeviceService`:
    - Test requestConnection dengan valid/invalid input
    - Test confirmConnection dengan session data
    - Test checkConnectionStatus
    - Test disconnect
    - Test canAddDevice berdasarkan plan
  
  - Unit tests untuk `MessageService`:
    - Test sendSingle dengan valid/invalid recipient
    - Test renderTemplate dengan variables
    - Test renderTemplate dengan missing variables
    - Test dispatchJob dengan delay
  
  - Unit tests untuk `QuotaService`:
    - Test getRemainingQuota
    - Test decrement dengan thread safety
    - Test reset
    - Test isExhausted
    - Test isUnlimited
  
  - Unit tests untuk `TemplateService`:
    - Test render dengan variables
    - Test render dengan missing variables
    - Test validate template format
    - Test getVariables extraction
  
  - Unit tests untuk `BroadcastService`:
    - Test createFromCsv dengan valid/invalid file
    - Test createFromRecipients
    - Test dispatch dengan quota check
    - Test cancel
    - Test getProgress
  
  - Unit tests untuk `AutoReplyService`:
    - Test matchKeyword dengan case-insensitive matching
    - Test priority selection
    - Test cooldown check
    - Test canReply
  
  - Unit tests untuk `SubscriptionService`:
    - Test activate subscription
    - Test extend subscription
    - Test isFeatureAllowed
    - Test checkExpiry
  
  - Unit tests untuk `BillingService`:
    - Test recordPayment
    - Test activateSubscription
    - Test extendSubscription
    - Test getRevenue
  
  - Unit tests untuk `AlertService`:
    - Test create alert
    - Test resolve alert
    - Test notifySuperadmin
  
  - Unit tests untuk `ReminderService`:
    - Test processReminders
    - Test checkCondition untuk setiap type
    - Test sendReminder

- [x] 9.2 Write feature tests untuk API endpoints
  - Feature tests untuk `POST /api/v1/send-message`:
    - Test dengan valid token dan parameters
    - Test dengan invalid token
    - Test dengan invalid phone number
    - Test dengan exhausted quota
    - Test dengan inactive subscription
    - Test dengan feature not allowed
  
  - Feature tests untuk `POST /api/v1/send-bulk`:
    - Test dengan valid token dan recipients
    - Test dengan recipients exceeding quota
    - Test dengan invalid recipients
    - Test dengan feature not allowed
  
  - Feature tests untuk `GET /api/v1/message-status/{job_id}`:
    - Test dengan valid job_id
    - Test dengan invalid job_id
    - Test dengan invalid token
  
  - Feature tests untuk `POST /webhook/baileys`:
    - Test dengan valid signature
    - Test dengan invalid signature
    - Test dengan malformed payload
    - Test dengan valid incoming message

- [x] 9.3 Write integration tests untuk message flow
  - Integration test untuk complete broadcast flow:
    - Create broadcast dengan CSV
    - Dispatch broadcast
    - Process SendMessageJob
    - Verify MessageLog status
    - Verify quota decremented
  
  - Integration test untuk auto-reply flow:
    - Send incoming message via webhook
    - Match keyword rule
    - Dispatch reply
    - Verify MessageLog created
    - Verify cooldown set
  
  - Integration test untuk reminder flow:
    - Create reminder configuration
    - Run reminder:process command
    - Verify MessageLog created
    - Verify ReminderLog created
    - Verify no duplicate dalam 24 jam
  
  - Integration test untuk subscription lifecycle:
    - Create tenant dengan trial
    - Record payment
    - Activate subscription
    - Verify quota reset
    - Extend subscription
    - Verify quota maintained

- [x] 9.4 Write property-based tests untuk correctness properties
  - Property tests untuk quota management:
    - Quota never goes negative
    - Quota decrements correctly untuk setiap message
    - Quota resets pada subscription renewal
  
  - Property tests untuk message delivery:
    - All messages dalam broadcast eventually processed
    - Message status transitions valid (pending -> sent/failed)
    - Retry count never exceeds max retries
  
  - Property tests untuk auto-reply:
    - Keyword matching case-insensitive
    - Cooldown prevents duplicate replies
    - Priority selection deterministic
  
  - Property tests untuk reminder:
    - No duplicate reminders dalam 24 jam
    - All matching recipients get reminder
    - Reminder status tracking accurate

---

## Phase 10: Documentation & Deployment (OPTIONAL)

- [x] 10.1 Create API documentation
  - OpenAPI/Swagger specification untuk semua endpoints
  - Request/response examples
  - Error codes dan messages
  - Rate limiting documentation
  - Authentication documentation

- [x] 10.2 Create deployment guide
  - Environment setup instructions
  - Database migration steps
  - Queue worker setup
  - Scheduler setup
  - Baileys Gateway integration
  - SSL/TLS configuration
  - Monitoring setup

- [x] 10.3 Create user guide untuk Tenant Dashboard
  - Getting started guide
  - Device connection tutorial
  - Template creation guide
  - Broadcast sending guide
  - Auto-reply setup guide
  - API token generation guide
  - Troubleshooting guide

- [ ] 10.4 Create admin guide untuk Superadmin Dashboard
  - Tenant management guide
  - Plan configuration guide
  - Billing management guide
  - Gateway management guide
  - Alert management guide
  - System configuration guide
  - Monitoring guide
