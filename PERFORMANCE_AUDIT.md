# Performance audit

Status: baseline only; no representative load test has been run. The latest frontend production build passed with a main JavaScript chunk of about 300 kB before gzip and about 73 kB gzip. The code also emits separate React/vendor and icon chunks. The 36 frontend tests and four Sites packaging tests passed.

The backend caches public books, articles, courses, publications, contributors, master data, and CMS responses, with invalidation in management services. Queue and scheduler definitions exist. This confirms mechanisms in source, not production hit rates or worker health.

Before release, capture mobile and desktop Core Web Vitals, image dimensions/compression and lazy loading, API p50/p95 response times, slow queries and N+1 traces, cache hit/invalidation behavior, queue lag and failed jobs, checkout concurrency, database index plans, and load tests on MariaDB/MySQL. Repeat after enabling realistic content, media, SMTP, payment sandbox, and courier sandbox. Set performance budgets from measured staging results; do not infer them from the local SQLite run.
