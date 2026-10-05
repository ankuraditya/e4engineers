# E4ENGINEERS Admin Role and Permission Matrix

The backend uses one `users` table, Sanctum sessions, and Spatie Laravel Permission with the `web` guard. Role names are lowercase kebab-case. Permission names use `resource.action`.

| Role | Current permissions | Intended future scope |
|---|---|---|
| `super-admin` | All current and future abilities through centralized Gate bypass | Entire platform and authorization administration |
| `administrator` | Previous administration plus all contributor permissions | Broad operational administration without unrestricted bypass |
| `content-manager` | CMS/media permissions plus complete contributor management | Editorial content, contributors, and media |
| `course-manager` | Course master scope plus contributor view | Courses, curricula, instructors, course FAQs |
| `publication-manager` | Publication master scope plus contributor view | Journals, publications, digital resources |
| `book-manager` | Book preparation scope plus contributor view | Books, authors, publishers, inventory when granted |
| `order-manager` | Dashboard | Orders, fulfilment, shipments, invoices when granted |
| `support-manager` | Dashboard | Support, enquiries, registrations, customer support |
| `customer` | None | Customer-facing APIs only |

## Phase 03 permission catalogue

`admin.dashboard.access`, `admin-users.view`, `admin-users.create`, `admin-users.update`, `admin-users.activate`, `admin-users.suspend`, `roles.view`, `roles.create`, `roles.update`, `roles.assign`, `permissions.view`, `permissions.assign`.

## Phase 04 permission catalogue

Master permissions use `engineering-disciplines`, `categories`, `topics`, `tags`, `course-levels`, `resource-types`, and `publication-types` with `.view`, `.create`, `.update`, and reserved `.delete` actions. Ordered masters also define `.reorder`. Phase 04 exposes no delete routes; the delete permissions reserve the future policy vocabulary while records are retired through the update-protected status endpoint.

## Protection rules

- Public registration always assigns `customer`; request payloads cannot assign roles or permissions.
- Customer-only users cannot authenticate through the admin endpoint or access `/api/v1/admin/*`.
- Admin-only users cannot authenticate through the customer login endpoint; a deliberately dual-role account may use both entry points.
- Only a Super Admin can assign or remove `super-admin`.
- `super-admin` and `customer` cannot be renamed or have permissions synchronized through the API.
- The final active Super Admin cannot be suspended, deactivated, or stripped of that role.
- Administrators cannot suspend themselves.
- Seeders add missing controlled assignments and reset Spatie's permission cache; they do not delete future permissions.

Future modules should add narrowly scoped `resource.action` permissions to the controlled catalogue and use policies for record-level decisions.

## Phase 05 permission catalogue

`pages.view|create|update|delete|publish`, `banners.view|create|update|delete|reorder`, `media.view|upload|update|delete`, `settings.view|update`, `seo.view|update`, `social-links.view|create|update|delete|reorder`, and `redirects.view|create|update|delete`.

## Phase 06 permission catalogue

`contributors.view`, `contributors.create`, `contributors.update`, `contributors.delete`, `contributors.publish`, `contributors.feature`, and `contributors.reorder`. Administrator and Content Manager receive all. Course, Publication, and Book Managers receive view only. Order Manager, Support Manager, and Customer receive none.

## Phase 07 permission catalogue

`articles.view`, `articles.create`, `articles.update`, `articles.delete`, `articles.publish`, and `articles.feature`. Administrator and Content Manager receive all. Course, Publication, and Book Managers receive view only. Order Manager, Support Manager, and Customer receive none.

## Phase 08 permission catalogue

`courses.view`, `courses.create`, `courses.update`, `courses.delete`, `courses.publish`, `courses.feature`, `courses.curriculum.manage`, `courses.faculty.manage`, and `courses.faq.manage`. Administrator and Course Manager receive all. Content, Publication, and Book Managers receive view only.

## Phase 09 permission catalogue

`publications.view`, `publications.create`, `publications.update`, `publications.delete`, `publications.publish`, `publications.feature`, `publications.preview.manage`, and `publications.contributors.manage`. Administrator and Publication Manager receive all. Content, Course, and Book Managers receive view only.

## Phase 10 permission catalogue

`resources.view`, `resources.create`, `resources.update`, `resources.delete`, `resources.publish`, `resources.feature`, `resources.file.manage`, and `resources.preview.manage`. Administrator and Publication Manager receive all. Content, Course, and Book Managers receive view only. Private file uploads additionally require `resources.file.manage`; preview mutations require `resources.preview.manage`.

## Phase 11 permission catalogue

`entitlements.view`, `entitlements.grant`, `entitlements.revoke`, and `downloads.view`. Administrator and Publication Manager receive all. Support Manager receives view-only entitlement and download visibility. Customers have no admin entitlement permission and access only their authenticated library/history endpoints.
# Phase 12 book catalogue

| Role | Book access |
|---|---|
| Super Admin / Administrator | All book, author, publisher, publishing, featured, gallery, and SEO operations |
| Book Manager | All book, author, publisher, publishing, featured, gallery, and SEO operations |
| Content/Course/Publication/Order/Support Manager | `books.view` only |
| Customer | None |

Permissions: `books.view/create/update/delete/publish/feature/images.manage`, `authors.view/create/update/delete`, and `publishers.view/create/update/delete`.

## Inventory

Book Manager and Administrator have all inventory permissions. Order Manager has inventory and movement read access for future order integration. Support Manager has inventory read access. Content, Course, and Publication Managers cannot mutate inventory. Customers have no inventory administration access.

# Phase 23 permissions

`enquiries.view/manage`, `support-tickets.view/manage`, `workshops.view/manage`, and `careers.view/manage` are granted to super-admin and administrator. Support-manager receives all eight operational permissions. Customer accounts receive none and use customer-scoped support endpoints.

# Phase 24 permissions
Notice, gallery and video permissions separate view/create/update/delete from publish, feature, pin and image management. Super-admin and administrator receive all through the existing role policy; content-manager receives all Phase 24 content permissions.

