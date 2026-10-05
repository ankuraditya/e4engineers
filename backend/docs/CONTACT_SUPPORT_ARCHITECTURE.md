# Contact and support architecture

Contact enquiries and support tickets are persisted separately. Every accepted submission receives a stable reference number and an idempotency token prevents browser retries from creating a second record. A hidden honeypot and endpoint-specific rate limit reject common automated submissions.

Support tickets contain a conversation timeline. Customer queries always scope tickets and attachments to the authenticated customer, returning 404 for another customer's identifiers. A guest may create a ticket but cannot attach an order; authenticated order references are accepted only when the order belongs to that customer. Attachments use the private local disk and are available only through authorized download endpoints.

Admins can search, filter, assign, prioritize, resolve and close enquiries and tickets. Internal support notes are never returned to customers. Customer acknowledgements and staff alerts are emitted as operational events and handed to the Phase 22 notification manager, so submission transactions do not depend on SMTP availability.
