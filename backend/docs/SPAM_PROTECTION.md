# Spam protection

Phase 23 uses three complementary controls:

- Named rate limits key contact and career requests by IP and email and support/registration requests by authenticated user or IP.
- Public forms include a visually hidden `website` honeypot. A non-empty value fails validation without storing content.
- Every mutation requires a UUID `submission_token`. Repeating the token returns the original record. Contact additionally suppresses an identical email, subject and message submitted within ten minutes; workshop and career duplicates are backed by database unique constraints.

Rate limits are intentionally conservative and can be adjusted in `AppServiceProvider`. Security events and validation failures avoid exposing private record data.
