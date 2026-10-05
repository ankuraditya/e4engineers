# Invoice architecture

Phase 20 issues one immutable invoice per eligible physical-book order. COD orders are eligible after confirmed checkout. Online orders are eligible only after Phase 19 has verified the provider signature, marked payment paid, finalized inventory, and confirmed the order.

`InvoiceService::issueForOrder()` locks the order, returns an existing invoice idempotently, checks eligibility, locks a period-specific sequence, and copies seller, customer, address, item, pricing and payment data into invoice snapshots. Invoice rendering reads only these snapshots. It never reads current book prices, coupons, shipping rates, addresses, or gateway calculations.

The default number is `{PREFIX}/{PERIOD}/{SEQUENCE}`. The period can be Indian financial year, calendar year, or omitted. Reset can be financial-year, calendar-year, or never. `invoice_sequences.sequence_key` is unique and incremented under a database lock. Existing numbers never change after settings change.

GST fields and HSN/SAC fields exist for future approved tax data. This phase does not derive a rate, place of supply, taxable value, or classification. Without configured GST identity the document label is `INVOICE`; with GSTIN it is `TAX INVOICE`, while monetary tax still comes only from the order snapshot.

DOMPDF renders the dedicated A4 Blade template. Files are stored on Laravel's private `local` disk under `invoices/{id}/invoice.pdf`; no public URL exists. Customer endpoints authorize the authenticated order owner or the secure guest order token. Admin routes require invoice permissions. Missing PDFs are regenerated from the immutable invoice, and explicit regeneration never rebuilds snapshots.

Voiding preserves the invoice number and all financial snapshots, records actor, reason and time, and regenerates a visibly VOID PDF. Credit notes and refund accounting remain future work. Email attachment support can attach the private generated file after issuance; no email delivery behavior was introduced in this phase.
