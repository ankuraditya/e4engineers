# Phase 10 Study Resources

`digital_resources` stores study-resource content and metadata. Each record belongs to an existing Resource Type and Engineering Discipline, can optionally reference a resource-compatible Category and Discipline Topic, and has many Tags through `digital_resource_tag`.

Access values are `free`, `login_required`, and `paid`. Paid records require a positive decimal price; other access types store no price. These values describe access requirements only. Phase 10 never grants an entitlement or exposes a download.

Thumbnails and previews reference public Media records. Full resource files reference Media records stored on the non-served `private` disk. Public API resources expose `has_file` and `download_available: false`, never the disk, path, object key, or permanent URL. Replacing a file changes the reference transactionally and deliberately retains the old Media object for later audited cleanup; metadata updates never delete files.

Supported private upload extensions are PDF, DOC, DOCX, PNG, JPG/JPEG, and ZIP, with a 50 MiB request limit. Executable and script formats are rejected. File size and normalized format are derived from the selected Media record.

Preview values are `none`, `text`, `media`, and `sample_file`. Rich text is sanitized. Preview media must be public and safe; a premium full file must never be selected as preview media.

Preview mutations require `resources.preview.manage`; private file selection and upload require `resources.file.manage`.

Public lists and details are cached for one hour behind a versioned namespace. Create, update, status, feature, file-reference, preview, thumbnail, SEO, and delete changes invalidate the namespace.

## Phase 11 handoff

Phase 11 must add customer resource entitlements, login-required and paid authorization, a controlled download endpoint, temporary or signed delivery where suitable, download logs, and optional download limits. It must authorize every request server-side. Account Digital Resources and Downloads remain disconnected until that work exists.
