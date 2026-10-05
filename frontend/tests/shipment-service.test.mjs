import test from 'node:test';
import assert from 'node:assert/strict';
import { trackingService } from '../src/services/trackingService.js';
import { adminShipmentService } from '../src/services/adminShipmentService.js';

test('guest tracking encodes order number and sends access token', async context => {
  let call;
  globalThis.fetch = async (url, options) => { call = { url, options }; return { ok: true, status: 200, json: async () => ({ data: { status: 'in_transit' } }) }; };
  context.after(() => { delete globalThis.fetch; });
  await trackingService.track('E4E/100', 'guest-secret');
  assert.match(call.url, /orders\/E4E%2F100\/tracking$/);
  assert.equal(call.options.headers['X-Order-Access-Token'], 'guest-secret');
});

test('admin shipment actions use the expected protected endpoint', async context => {
  const calls = [];
  globalThis.document = { cookie: 'XSRF-TOKEN=test-token' };
  globalThis.fetch = async (url, options) => { calls.push({ url, options }); return { ok: true, status: 200, json: async () => ({ data: {} }) }; };
  context.after(() => { delete globalThis.fetch; delete globalThis.document; });
  await adminShipmentService.action(42, 'refresh-tracking');
  assert.match(calls.at(-1).url, /admin\/shipments\/42\/refresh-tracking$/);
  assert.equal(calls.at(-1).options.method, 'POST');
});
