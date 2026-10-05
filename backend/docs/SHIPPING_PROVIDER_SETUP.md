# Shipping provider setup

1. Sign in as a Super Admin or Administrator and open `/admin/shipping`.
2. Configure a default pickup location through the shipping pickup-location API.
3. Enter the provider's dedicated API-user credentials. Secret fields stay blank when editing; blank input retains the encrypted value.
4. Select **Test connection**. The provider cannot be enabled until the connection status is `connected`.
5. Enable the provider and choose a default. Optionally configure a different fallback provider and automatic fallback.
6. Enable shipping and select live, flat, free, or hybrid mode.

For Shiprocket, create a dedicated API user under Shiprocket Settings → API and use its API email and password. Laravel obtains and refreshes bearer tokens internally. See [official Shiprocket API documentation](https://apidocs.shiprocket.in/).

For NimbusPost, use the API-user credentials issued for the account and verify the currently documented fields in the official NimbusPost Postman/API material supplied by NimbusPost. Laravel obtains temporary authentication internally. No credentials or tokens belong in frontend variables, source control, documentation, or browser storage.

Both providers are seeded disabled and unconfigured. Real connection QA requires valid provider sandbox or production credentials. Testing credentials can be rotated from the admin module without deployment.
