import test from "node:test";
import assert from "node:assert/strict";
import { cartService } from "../src/services/cartService.js";

function storage(initial = {}) {
  const values = new Map(Object.entries(initial));
  return { getItem: key => values.get(key) || null, setItem: (key, value) => values.set(key, value), removeItem: key => values.delete(key) };
}

test("cart writes send only authoritative identifiers and quantity with the guest token", async context => {
  globalThis.localStorage = storage({ "e4engineers-guest-cart-token": "a".repeat(64) });
  let request;
  globalThis.fetch = async (url, options) => { request = { url, options }; return { ok: true, status: 201, json: async () => ({ data: { items: [], summary: {}, meta: { guest_cart_token: "a".repeat(64) } } }) }; };
  context.after(() => { delete globalThis.fetch; delete globalThis.localStorage; });
  await cartService.add(42, 3);
  assert.equal(new URL(request.url).pathname, "/api/v1/cart/items");
  assert.deepEqual(JSON.parse(request.options.body), { book_id: 42, quantity: 3 });
  assert.equal(request.options.headers["X-Guest-Cart-Token"], "a".repeat(64));
});

test("authenticated merge removes the obsolete guest token", async context => {
  globalThis.localStorage = storage({ "e4engineers-guest-cart-token": "b".repeat(64) });
  globalThis.fetch = async () => ({ ok: true, status: 200, json: async () => ({ data: { meta: {} } }) });
  context.after(() => { delete globalThis.fetch; delete globalThis.localStorage; });
  await cartService.merge();
  assert.equal(globalThis.localStorage.getItem("e4engineers-guest-cart-token"), null);
});

test("coupon application sends only the code and removal uses the shared cart API", async context => {
  globalThis.localStorage = storage(); const calls = [];
  globalThis.fetch = async (url, options) => { calls.push({ url, options }); return { ok: true, status: 200, json: async () => ({ data: { meta: {} } }) }; };
  context.after(() => { delete globalThis.fetch; delete globalThis.localStorage; });
  await cartService.applyCoupon("E4SAVE10"); await cartService.removeCoupon();
  const writes = calls.filter(call => new URL(call.url).pathname === "/api/v1/cart/coupon");
  assert.deepEqual(JSON.parse(writes[0].options.body), { code: "E4SAVE10" });
  assert.equal(writes[0].options.method, "POST"); assert.equal(writes[1].options.method, "DELETE");
});
