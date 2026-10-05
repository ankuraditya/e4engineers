# Workshop architecture

Published workshops are available through the public API; drafts, cancelled records and future publications are excluded. Registration opening and closing timestamps, capacity, mode, price and status are administered independently.

Registration locks the workshop row inside a database transaction before counting registrations. This prevents simultaneous requests from exceeding capacity. A database unique key prevents a repeated email for one workshop, while the submission token makes network retries idempotent. Private online meeting URLs are encrypted and omitted from public workshop responses.

Successful registrations emit customer and administrator notification events through the shared notification manager.
