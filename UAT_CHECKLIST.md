# Staging UAT checklist

Use synthetic identities and provider test/sandbox credentials. Record pass/fail, evidence URL, and issue ID for each item. Do not place real charges or book real pickups without explicit authorization.

## Customer

- [ ] Register, verify contact method, sign in/out, restore session, reset password, and reject unsafe post-login redirects.
- [ ] New account shows zero orders and cannot open another customer's order, invoice, payment, address, ticket, download, or tracking record.
- [ ] Edit profile, add/edit/default/delete address, change password, and save notification preferences.
- [ ] Browse every public listing/detail, filter/search, and open every header/footer link; check empty, loading, and error states.
- [ ] Download a free resource and reject an unauthorized premium/private file request.
- [ ] Add a book to guest cart, change quantity, apply/remove a coupon, estimate shipping, refresh, and merge after login.
- [ ] Complete guest COD checkout; verify one order, correct server-calculated totals, automatic account creation, setup email, inventory change, invoice, and admin visibility.
- [ ] Retry/double-submit checkout and confirm no duplicate order, coupon use, payment, or stock movement.
- [ ] Test failed/successful online payment and retry in each enabled gateway sandbox; confirm webhook signature and final order status.
- [ ] Track an order with the correct customer or guest token and reject access from another customer.
- [ ] Submit contact, support, workshop registration, and career application with valid/invalid data.

## Administrator

- [ ] Sign in as each role; verify allowed actions and server-side rejection of forbidden actions.
- [ ] Publish/edit/unpublish a page, homepage banner, notice, gallery album, video, article, course, publication, resource, and book; verify public change and cache invalidation.
- [ ] Upload a book cover, public image, private resource file, resume, and attachment; verify limits and private access.
- [ ] Change book price and stock; verify listing/cart/checkout use current server values.
- [ ] Configure COD, shipping, pickup location, test courier, SMTP, and invoice details; verify values persist and affect behavior.
- [ ] Process order status, payment attempt, invoice, shipment booking, AWB, tracking update, support ticket, workshop, and career application.
- [ ] Confirm queue jobs, scheduled expiration/tracking jobs, webhook retries, logs, and alerts.

## Quality and launch

- [ ] Repeat key flows at mobile, tablet, and desktop sizes with keyboard and screen reader checks.
- [ ] Check Chrome, Firefox, and Edge; confirm no console errors or failed requests.
- [ ] Complete MariaDB/MySQL full suite, production build, dependency audit, API/security audit, performance budget, SEO metadata, sitemap, and robots checks.
- [ ] Run backup/restore drill and post-deploy smoke tests before a go-live decision.
