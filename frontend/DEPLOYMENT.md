# E4ENGINEERS frontend deployment

The previous frontend-only demo used `https://advom.uiprocorp.com/` and cPanel document root `/home/advomuiprocorp/public_html`. That history does not establish that the current Laravel API is deployed or that the full commerce system is production-ready.

Build and verify the frontend with `npm test`, `npm run test:sites`, and `npm run build`. The static client output is `dist/client`. It requires a reachable Laravel API at `/api/v1` on the same origin, or a correctly configured `VITE_API_BASE_URL` with credentialed CORS and Sanctum stateful domains. Preserve SPA deep-link rewrites and route API requests to Laravel before applying the SPA fallback.

Before uploading, use the project root production configuration and go-live checklists. Verify homepage, a direct inner-page URL, API health, customer sign-in, admin sign-in, book/cart/checkout, and asset loading on staging. Do not activate live payment or courier credentials as part of a frontend upload.

Do not commit credentials or copy the local `.env` to a server.
