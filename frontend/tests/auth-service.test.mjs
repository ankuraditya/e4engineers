import test from "node:test";
import assert from "node:assert/strict";
import { authService } from "../src/services/authService.js";

test("login initializes CSRF and submits credentials through the centralized client", async (context) => {
  const calls = [];
  globalThis.document = { cookie: "XSRF-TOKEN=test-token" };
  globalThis.fetch = async (url, options = {}) => {
    calls.push({ url, options });
    if (url.endsWith("/sanctum/csrf-cookie")) return { ok: true, status: 204 };
    return { ok: true, status: 200, json: async () => ({ success: true, data: { user: { id: 1 } } }) };
  };
  context.after(() => { delete globalThis.fetch; delete globalThis.document; });

  const response = await authService.login({ identity: "customer@example.com", password: "password123", remember: true });

  assert.equal(response.data.user.id, 1);
  assert.equal(calls.length, 2);
  assert.match(calls[0].url, /\/sanctum\/csrf-cookie$/);
  assert.match(calls[1].url, /\/api\/v1\/auth\/login$/);
  assert.equal(calls[1].options.credentials, "include");
  assert.equal(calls[1].options.headers["X-XSRF-TOKEN"], "test-token");
  assert.deepEqual(JSON.parse(calls[1].options.body), { login: "customer@example.com", password: "password123", remember: true });
});

test("logout uses the same cookie-based authenticated client", async (context) => {
  const calls = [];
  globalThis.document = { cookie: "XSRF-TOKEN=test-token" };
  globalThis.fetch = async (url, options = {}) => {
    calls.push({ url, options });
    return url.endsWith("/sanctum/csrf-cookie")
      ? { ok: true, status: 204 }
      : { ok: true, status: 200, json: async () => ({ success: true, data: null }) };
  };
  context.after(() => { delete globalThis.fetch; delete globalThis.document; });

  await authService.logout();
  assert.match(calls[1].url, /\/api\/v1\/auth\/logout$/);
  assert.equal(calls[1].options.method, "POST");
});
