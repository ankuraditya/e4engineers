# Engineering Articles

Phase 07 stores technical content as sanitized HTML. Heading IDs are generated from H2/H3 text, and the public detail resource derives its table of contents from those IDs. Formula source is stored as inert LaTeX-compatible text inside `formula-block` elements; executable MathJax scripts are never stored. Technical images use media-library records.

Articles have one primary engineering discipline, optional article-compatible category and topic, many tags, and ordered contributors with one primary author. `author_name` is used only when no contributor profile is assigned. Reading time is recalculated at 220 words per minute whenever content changes.

Public APIs only expose published records whose publication date has arrived. Related articles initially use matching discipline or topic. Previous and next navigation follows publication time. Public list/detail payloads are cached for one hour under a versioned namespace invalidated by every article, relationship, featured, status, deletion, or SEO mutation.

The seeded articles are explicitly local/testing demonstration content. Production seeding creates no invented editorial records.
