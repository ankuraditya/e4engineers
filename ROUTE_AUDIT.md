# Route audit

Status: partial browser audit, 2026-10-04. This document records verified findings and remaining work; it is not a claim that every route passed UAT.

The local homepage, about, engineering, articles, courses, publications, resources, books, search, contact, FAQ, contributors, notices, workshops, gallery, videos, support, careers, legal pages, and all main admin screens rendered in browser checks. Book detail, cart, and guest checkout form were opened. Admin sign-in worked with a disposable local QA account, which was removed after testing. The customer registration/order journey remains uncompleted in the browser.

Fixed in Phase 25: resource-category navigation now uses the resource listing `?type=` filter; book footer links use supported catalogue filters; the Journals link uses the actual `journal` type; unsupported paths now show “Page not found” instead of the homepage; course sidebar enquiry now uses the course's real enquiry URL. Published CMS content now replaces About and legal-page body copy when present; the homepage hero reads the active CMS banner. Verify these again in staging.

Known content/route limitations: published resource detail can show “File unavailable” if no private file is attached. Paid publication/resource purchase actions do not complete a digital checkout. Some public legal content is still draft/fallback because CMS page content is empty. Social footer targets are placeholders. Sitemap and robots files were not found in `frontend/public`.

Run every header/footer link, direct URL refresh, loading/error/empty state, keyboard path, mobile viewport, and browser console check from `UAT_CHECKLIST.md` before closing this audit.
