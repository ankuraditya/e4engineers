# E4ENGINEERS administration handoff

Local frontend: http://localhost:5173/

Local administrator: http://localhost:5173/admin/login

The approved public design is retained. The administrator workspace is divided by business task:

| Area | What staff can manage |
| --- | --- |
| Website | Pages, banners, media, notices, gallery albums and videos |
| Publishing | Articles, courses, publications and study resources, with image selection/upload and article rich text |
| Store | Book titles, covers/gallery, authors, publishers, prices, status, stock and low-stock thresholds |
| Orders | Search/view orders, change status, issue invoices and create shipments |
| Payments & gateways | Settings, gateway credentials/status, attempts, transactions and reconciliation |
| Shipping | Provider credentials, pickup locations, service rules and shipments |
| Customers | Enquiries, support tickets/replies, workshops and careers |
| System | Notifications and delivery settings |

To sell live, the business must supply and configure valid payment-gateway credentials, shipping-provider credentials, pickup location, tax/invoice details, email transport, and production hosting for the Laravel API and its persistent database/storage. Test credentials and sample catalogue content are not live commerce configuration.

The frontend uses `VITE_API_BASE_URL` when set. Otherwise it uses port 8000 for local development, and same-origin `/api/v1` on a deployed domain. Configure the deployed domain to route `/api/v1` and `/sanctum` to Laravel.

The test suite uses an isolated in-memory SQLite database through `backend/phpunit.xml`; do not point tests at the local or production database.
