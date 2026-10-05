import test from "node:test";
import assert from "node:assert/strict";
import { contributorsService } from "../src/services/contributorsService.js";

test("contributor filters are sent to the backend API", async (context) => {
  const calls = [];
  globalThis.fetch = async (url) => {
    calls.push(url);
    return { ok: true, status: 200, json: async () => ({ success: true, data: [], meta: { total: 0 } }) };
  };
  context.after(() => { delete globalThis.fetch; });

  await contributorsService.getContributors({ search: "power", discipline: "electrical-engineering", featured: 1, per_page: 12 });

  const url = new URL(calls[0]);
  assert.equal(url.pathname, "/api/v1/contributors");
  assert.equal(url.searchParams.get("search"), "power");
  assert.equal(url.searchParams.get("discipline"), "electrical-engineering");
  assert.equal(url.searchParams.get("featured"), "1");
});

test("contributor detail slugs are safely encoded", async (context) => {
  let requested = "";
  globalThis.fetch = async (url) => {
    requested = url;
    return { ok: true, status: 200, json: async () => ({ success: true, data: {} }) };
  };
  context.after(() => { delete globalThis.fetch; });

  await contributorsService.getContributor("prototype contributor");
  assert.match(requested, /\/contributors\/prototype%20contributor$/);
});
