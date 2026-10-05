# Digital Access Security

Paid and login-restricted files are stored on Laravel's non-served `private` disk. A storage path, guessed public URL, client-side login state, or purchase-looking browser value never authorizes access.

Every download resolves a published resource or publication, evaluates its access type in `DigitalAccessService`, verifies the authenticated user's valid entitlement for paid content, confirms the private file still exists, records a successful download, and streams the file with `nosniff` and private/no-store cache headers. Failed or unauthorized requests create no success log.

Free files use the same controlled endpoint and may be downloaded anonymously. Login-required files require authentication. Paid files require authentication plus an active, non-revoked, non-expired entitlement. Entitlement decisions are not cached, so revocation applies immediately.

The morph map accepts only `resource` and `publication` at API boundaries. Admin grant requests select from that allowlist; customers never supply a user ID for library or history access. Account queries always use the authenticated identity, preventing IDOR.

Future verified payment processing must call `EntitlementService::grantPurchasedAccess()` with an idempotent provider/order reference. Future refund policy can call `revokeByPurchaseReference()`. Phase 11 does not invoke either from a browser purchase action.
