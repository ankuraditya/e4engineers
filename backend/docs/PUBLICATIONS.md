# Journals and publications

Phase 09 models each card/detail record as one issue or standalone document because the approved frontend treats it that way. A parent-journal hierarchy can be introduced later without changing stable publication slugs.

Publications use existing types, disciplines, optional categories, media covers, contributors, and SEO. External authors/editors use fallback text only when structured contributor relations are absent. Free records store no price; paid records require a positive decimal price. Preview text or media is separate from protected full files. `download_ready` remains false until Phase 11 implements entitlements and secure delivery.

Local/testing seeding creates seven demonstration records. Production receives no invented publications.
