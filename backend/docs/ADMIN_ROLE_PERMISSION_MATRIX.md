# Admin role and permission matrix

| Role | Coupon access |
|---|---|
| Super Admin | View, create, update, delete, activate, usage view |
| Administrator | View, create, update, delete, activate, usage view |
| Book Manager | View, create, update |
| Order Manager | View and usage view |
| Support Manager | View and usage view |
| Content, Course, Publication Managers | None |
| Customer | None |

Permissions: `coupons.view`, `coupons.create`, `coupons.update`, `coupons.delete`, `coupons.activate`, and `coupon-usages.view`. Admin routes also require an authenticated admin role. Coupon redemption is deferred to Phase 18.

## Shipping

| Role | Shipping access |
|---|---|
| Super Admin | Full settings, credentials, toggles, tests, rates, serviceability |
| Administrator | Full settings, credentials, toggles, tests, rates, serviceability |
| Order Manager | View settings/providers, rates and serviceability |
| Support Manager | View providers, rates and serviceability |
| Other managers and customers | None |

Credential changes require `shipping.providers.configure`; ordinary shipping visibility never grants secret access.
# Phase 18 order permissions

| Role | View orders | Update status | Cancel | Customer-safe detail | Status history |
|---|---:|---:|---:|---:|---:|
| Super Admin | Yes | Yes | Yes | Yes | Yes |
| Administrator | Yes | Yes | Yes | Yes | Yes |
| Order Manager | Yes | Yes | Yes | Yes | Yes |
| Support Manager | Yes | No | No | Yes | Yes |
| Customer | Own orders only | No | No | Own orders only | Own order detail |

# Phase 20 invoice permissions

| Capability | Administrator | Order manager | Support manager |
|---|---:|---:|---:|
| View invoice settings | Yes | Yes | No |
| Update invoice settings | Yes | No | No |
| View/download invoices | Yes | Yes | No |
| Issue/regenerate invoices | Yes | Yes | No |
| Void invoices | Yes | No | No |


## Phase 21 shipment permissions

Administrators receive all shipment permissions. Order managers receive shipment view/create/update/cancel, AWB, pickup, document, tracking, reconciliation, attempt, event and webhook permissions. Customer tracking is protected by order ownership or guest access token and does not use admin permissions.

## Phase 22 notification permissions

Administrators receive notification settings, SMTP test, template and log permissions. Order managers can view logs. Support managers can view and retry logs. Content managers can view and update templates. SMTP credential access remains limited to administrators.
