# Changelog

All notable changes to `muon/module-sms-notification` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-06-15

### Added
- **Resilient retry queue**: redesigned `muon_sms_retry_queue` (status / attempt_number /
  store_id / last_error / claim_token / locked_at / updated_at); cron now atomically claims a
  bounded batch so overlapping runs can no longer double-send.
- **Dead-letter**: messages that exhaust their retry budget are preserved with `status=dead`
  instead of being dropped.
- **Admin grid** at *Muon → SMS Retry Queue* (ACL `Muon_SMSNotification::retry_queue`) with
  **Re-queue** and **Delete** mass actions.
- **Vonage transport** (Vonage REST, no SDK dependency), selectable per store alongside Twilio.
- **Send Test SMS** admin button that exercises the configured transport.
- **Rate limiting** (`rate_limit_per_minute`, 0 = unlimited) — over-limit messages are deferred,
  not dropped — and **message length/segmentation control** (`max_length`) with segment-count logging.
- **Customer-facing recipient mode** (send order/shipment SMS to the order's own phone) and a new
  **`sales_order_shipment_save_after`** shipment notification.

### Changed
- Order/registration observers are now best-effort: a notification failure can no longer break or
  roll back checkout or registration.
- The queue handler catches all throwables and routes them to retry instead of killing the consumer.
- Logs mask the recipient phone and omit the message body (PII).

### Fixed
- Invalid phone numbers are dropped permanently instead of consuming the whole retry budget.
- Admin config validation: numeric fields use `validate-digits`; `send_to_phone` is validated as
  E.164 by a backend model.
- SMS Retry Queue grid rendered with an empty left column.

## [1.0.0] - 2026-06-15

### Added
- Initial implementation: asynchronous SMS notifications for order placement and customer
  registration via Twilio, with a message queue and cron-based retry.

[1.1.0]: https://github.com/muon-m2/module-sms-notification/releases/tag/Muon_SMSNotification-1.1.0
[1.0.0]: https://github.com/muon-m2/module-sms-notification/releases/tag/Muon_SMSNotification-1.0.0
