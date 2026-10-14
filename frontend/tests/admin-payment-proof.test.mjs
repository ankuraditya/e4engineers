import test from "node:test";
import assert from "node:assert/strict";
import { loadPaymentProof } from "../src/services/adminPaymentService.js";

test("admin screenshot preview requests the protected image with session credentials", async context => {
  let request;
  globalThis.fetch = async (url, options) => {
    request = { url, options };
    return { ok: true, blob: async () => new Blob(["image"], { type: "image/png" }) };
  };
  context.after(() => { delete globalThis.fetch; });

  const proof = await loadPaymentProof("attempt-123");
  assert.equal(new URL(request.url).pathname, "/api/v1/admin/payments/attempts/attempt-123/proof");
  assert.equal(request.options.credentials, "include");
  assert.equal(proof.type, "image/png");
});

test("admin screenshot preview reports an expired session", async context => {
  globalThis.fetch = async () => ({ ok: false, status: 401 });
  context.after(() => { delete globalThis.fetch; });
  await assert.rejects(loadPaymentProof("attempt-123"), /admin session has expired/i);
});
