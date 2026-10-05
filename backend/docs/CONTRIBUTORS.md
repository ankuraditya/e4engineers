# Contributor and faculty module

Phase 06 models educators, engineers, researchers, and technical professionals as contributors. Public URLs use stable lowercase kebab-case slugs. Profiles support designation, qualification, short and sanitized full biography, expertise summary, optional private contact data, public HTTP/HTTPS profile links, active state, featured state, publication time, and deterministic ordering.

Contributors use a many-to-many relationship with existing engineering disciplines. The pivot records one primary discipline and preserves discipline order without duplicating discipline names. Photos belong to the shared media library, and contributor SEO uses the shared polymorphic SEO relation.

Public list filtering supports `search`, discipline slug, featured state, page, and `per_page` up to 100. Public responses exclude email, phone, editor IDs, and internal timestamps. Missing photos return `null`; the React component supplies its existing local fallback avatar.

Public contributor queries and details are cached for one hour using a versioned cache namespace. Create, update, publish, feature, reorder, SEO, and deactivation operations increment the version so every filtered cache becomes stale immediately.

Delete requests safely deactivate the profile. This preserves identifiers for future references. Phase 07 can add an `article_contributor` pivot after the articles table exists. Phase 08 can independently add a `course_contributor` or `course_faculty` pivot with its own teaching-role metadata.

The local/testing-only `ContributorDemoSeeder` creates explicitly labelled prototype profiles. It does not run in production and does not claim any real person or affiliation.
