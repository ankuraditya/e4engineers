# Invoice settings guide

Open **Admin → Invoices** to configure the legal/display name, GSTIN, PAN, seller address, contact details, invoice prefix and number format, period/reset strategy, terms, declaration, signatory and display options. Logo and signature accept private PNG or JPEG uploads through the settings API.

Number format must include `{SEQUENCE}` and may include `{PREFIX}` and `{PERIOD}`. Examples:

- `{PREFIX}/{PERIOD}/{SEQUENCE}` with prefix `E4E/INV`
- `{PREFIX}-{SEQUENCE}` with no period

Period strategies are `financial_year`, `calendar_year`, and `none`. Sequence reset strategies are `financial_year`, `yearly`, and `never`. Changing any setting affects new invoices only. Issued invoices retain the seller details, presentation options, terms and numbering used at issuance.

Enabling GST columns or HSN display changes presentation only. It does not calculate tax. Populate tax and HSN values only after the business provides approved authoritative rules and checkout stores them in order snapshots.

Invoice PDFs use private application storage. Ensure `storage/app/private` is writable on deployment. Run the Laravel scheduler and queue worker according to the existing deployment plan. Do not expose the invoices directory through a public storage symlink.
