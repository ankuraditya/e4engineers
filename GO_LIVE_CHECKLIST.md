# Go-live checklist

Recommendation on 2026-10-04: **NOT READY**. Check a box only with evidence from the production-like staging environment. Passing SQLite tests alone does not satisfy this list.

- [ ] Client approves final legal, contact, book, course, and resource content; remove or unpublish placeholders and attach missing files.
- [ ] Resolve all blocker/critical security, checkout, payment, shipping, and customer-data findings.
- [ ] Test the complete suite on the intended MariaDB/MySQL version.
- [ ] Back up database, public media, private files, environment, and current deployed release; prove restore access.
- [ ] Set final DNS, TLS, HTTPS redirects, secure cookies, CORS, Sanctum domains, and `APP_DEBUG=false`.
- [ ] Run migrations on staging and production with a reviewed backup and rollback window.
- [ ] Verify frontend deep links, `/api/v1/health`, customer and admin sign-in, logout, and CSRF.
- [ ] Configure and test SMTP, email templates, queue worker, failed-job handling, and scheduler cron.
- [ ] Configure invoice issuer/tax settings and validate generated PDFs and private access.
- [ ] Configure COD, shipping rules, pickup address, couriers, and test-mode gateway credentials.
- [ ] Verify payment and shipping webhook URLs, signatures, idempotency, retries, and event logs.
- [ ] Complete guest COD order, account creation, inventory, invoice, shipment, tracking, cancellation, and refund UAT.
- [ ] Complete safe test-mode Razorpay, PayU, and Cashfree success/failure/retry UAT where enabled.
- [ ] Check mobile, keyboard accessibility, browser console, performance, sitemap/robots, and SEO metadata.
- [ ] Review log access, HTTP errors, queue failures, webhook failures, disk capacity, and database monitoring.
- [ ] Remove demo-only data without deleting client-approved content.
- [ ] Confirm no private files or live secrets are publicly accessible.
- [ ] Obtain an explicit production cutover decision; do not infer approval from this checklist.
