import test from "node:test";
import assert from "node:assert/strict";
import { resourcesService } from "../src/services/resourcesService.js";

test("resource filters and pagination are sent to the backend", async (context) => {
  let requested = "";
  globalThis.fetch = async (url) => { requested = url; return { ok: true, status: 200, json: async () => ({ success: true, data: [], meta: {} }) }; };
  context.after(() => { delete globalThis.fetch; });
  await resourcesService.getResources({ search: "formula", discipline: "engineering-mathematics", type: "formula-sheets", access: "free", page: 2 });
  const url = new URL(requested);
  assert.equal(url.pathname, "/api/v1/resources");
  assert.equal(url.searchParams.get("discipline"), "engineering-mathematics");
  assert.equal(url.searchParams.get("type"), "formula-sheets");
  assert.equal(url.searchParams.get("access"), "free");
  assert.equal(url.searchParams.get("page"), "2");
});

test("resource detail slugs are safely encoded", async (context) => {
  let requested = "";
  globalThis.fetch = async (url) => { requested = url; return { ok: true, status: 200, json: async () => ({ success: true, data: {} }) }; };
  context.after(() => { delete globalThis.fetch; });
  await resourcesService.getResource("formula sheet");
  assert.match(requested, /\/resources\/formula%20sheet$/);
});
