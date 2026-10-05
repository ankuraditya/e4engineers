# Customer account architecture

The existing `users` table remains the sole customer identity source for name, email, mobile, authentication, verification, and account status. No duplicate customer profile table is used. Avatar upload is deferred because the approved customer UI only renders an initial placeholder; customers are not granted Media Library permissions.

Profile endpoints always use the authenticated user and accept only name, email, and mobile. Email is lowercased, mobile is normalized to a ten-digit Indian number, and changing either identity field clears its corresponding verification timestamp. Roles, status, passwords, and arbitrary user IDs cannot be changed through this API.

`customer_addresses` stores structured Indian delivery addresses. The first address becomes default. Setting or creating another default transactionally clears the previous default. Deleting a default assigns the most recently updated remaining address; deleting the last leaves none. All reads and mutations verify ownership and return 404 for another customer's address, preventing identifier probing.

React uses one `CustomerContext` backed by `accountService` for Profile, My Addresses, and Checkout. Checkout selects the default address without changing it and uses the same profile for contact prefill. Phase 18 must copy the selected recipient name, mobile, address lines, landmark, city, state, postal code, and country into an immutable order-address snapshot. Orders must never depend only on an editable live address.
