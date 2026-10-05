# Prototype Instructions

Run the local server yourself and open the preview in the browser available to this environment. Do not give the user server-start instructions when you can run it.

Before making substantial visual changes, use the Product Design plugin's `get-context` skill when the visual source is unclear or no longer matches the current goal. When the user gives durable prototype-specific design feedback, preferences, or decisions, record them in `AGENTS.md`.

When implementing from a selected generated mock, treat that image as the source of truth for layout, component anatomy, density, spacing, color, typography, visible content, and hierarchy.

Build app UI in `src/`. Keep `.openai/hosting.json`, `worker/index.js`, `scripts/prepare-sites-build.mjs`, and `tests/sites-worker.test.mjs` intact so the same local prototype can be handed to Sites. Before a Sites handoff, run `npm run build` and `npm run test:sites`; the build must leave `dist/client/index.html`, `dist/server/index.js`, and `dist/.openai/hosting.json`.

# E4ENGINEERS client feedback
The public customer frontend design is client-approved. Preserve its visual layout while making business actions functional. The admin must distinguish website presentation, editorial publishing, and ecommerce operations, with usable product imagery, pricing, inventory, orders, payments, and shipping management.

Homepage content cards need short previews with a word limit and visual line clamp; full imported descriptions belong on detail pages.

Scan & Pay is an optional admin-configured checkout method. Customers must upload a payment screenshot, and an administrator must verify it before an order is marked paid.

