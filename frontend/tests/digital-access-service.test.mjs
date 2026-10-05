import test from "node:test";
import assert from "node:assert/strict";
import { digitalAccessService } from "../src/services/digitalAccessService.js";

test("account library uses authenticated account endpoint and filters", async (context) => {
  let requested = "";
  globalThis.fetch = async (url) => { requested = url; return { ok: true, status: 200, json: async () => ({ success: true, data: [] }) }; };
  context.after(() => { delete globalThis.fetch; });
  await digitalAccessService.getLibrary({ type: "resource", search: "formula" });
  const url = new URL(requested);
  assert.equal(url.pathname, "/api/v1/account/digital-resources");
  assert.equal(url.searchParams.get("type"), "resource");
  assert.equal(url.searchParams.get("search"), "formula");
});

test("account downloads use the customer-owned endpoint", async (context) => {
  let requested = "";
  globalThis.fetch = async (url) => { requested = url; return { ok: true, status: 200, json: async () => ({ success: true, data: [] }) }; };
  context.after(() => { delete globalThis.fetch; });
  await digitalAccessService.getDownloads();
  assert.equal(new URL(requested).pathname, "/api/v1/account/downloads");
});
