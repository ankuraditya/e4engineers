# Tracking architecture

Carrier updates arrive through signed provider webhooks or scheduled polling. Both paths pass through `ShipmentService::handleTracking`, normalize carrier-specific text into the internal shipment state machine, append a deduplicated event, and advance the shipment only when the event is current and has equal or greater precedence.

Supported states include booking, booked, AWB assigned, pickup scheduled, picked up, in transit, out for delivery, delivered, delivery failed, RTO initiated/in transit/delivered, cancelled, booking failed, and unknown.

Customer tracking uses `GET /api/v1/orders/{orderNumber}/tracking`. Access requires the authenticated order owner or the original guest order access token in `X-Order-Access-Token`. Unauthorized requests return 404 to avoid revealing order numbers. Responses expose normalized courier, AWB, estimate, last-update time, and timeline data without raw provider payloads.

Webhook requests use the configured encrypted provider secret in `x-api-key`. Raw payload hashes and sanitized payload subsets support replay protection and operations review. Full secrets and authorization headers are never stored.
