# CMS, media, settings, and SEO architecture

Phase 05 manages static website content without changing the React layout. Pages contain safe rich text and ordered structured sections. Fixed routes are seeded as system pages and cannot be deleted or have their slugs changed. Publication states are `draft`, `published`, and `archived`; only published pages are public.

Rich text is sanitized server-side to a small allowlist of headings, paragraphs, lists, emphasis, blockquotes, and safe links. Scripts, event attributes, unsafe elements, and unsafe link schemes are removed. Section `settings` accepts only documented presentation hints.

Website settings use a controlled typed key registry in `config/cms.php`. Unknown keys are rejected. The public endpoint returns only records marked public. Contact values are supported but remain empty until verified business data is supplied. Social URLs require HTTP or HTTPS.

Media files use Laravel Storage. The database stores disk and relative path; URLs are generated at response time so storage can move to S3/CDN later. The default disk is `public`, the upload limit is 5 MiB, and accepted types are JPG, JPEG, PNG, WebP, and PDF. Client filenames never determine storage paths. MIME/extension validation, randomized Storage filenames, dimensions for images, authorization, and reference checks protect uploads and deletion. SVG and executable formats are rejected. Run `php artisan storage:link` when a public-disk symlink is not present.

SEO metadata uses a polymorphic `seo_meta` relation, initially attached to pages and reusable by future modules. It supports title, description, canonical URL, Open Graph fields, controlled robots values, optional media, and JSON structured data. JSON-LD is stored as data and must be serialized safely by the frontend. The React SPA currently remains client-rendered; prerendering or SSR should be assessed later for crawl-critical production pages.

Redirect records accept internal source and target paths only, with 301, 302, 307, or 308 status. Phase 05 exposes protected management APIs but deliberately does not install global redirect middleware.

Published pages, public settings, social links, and public banners are cached for one hour. Every relevant admin mutation clears its cache. Scheduled banners are filtered by active status and start/end time.

Frontend integration is deferred to a focused UI integration phase. Existing approved static content remains the fallback, while the Phase 05 APIs are production-ready. Future integration should use centralized `cmsService`, `settingsService`, `bannerService`, and SEO handling, preserving existing section order, dimensions, typography, color, and responsive behavior.
