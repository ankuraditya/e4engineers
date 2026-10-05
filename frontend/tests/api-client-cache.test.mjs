import test from "node:test";
import assert from "node:assert/strict";
import { apiRequest } from "../src/services/apiClient.js";

test("public requests share an in-flight response while account responses remain private", async (context) => {
  const requests = [];
  globalThis.fetch = async (url) => {
    requests.push(url);
    return { ok: true, status: 200, json: async () => ({ data: requests.length }) };
  };
  context.after(() => delete globalThis.fetch);

  const publicPath = "/articles?featured=1&per_page=83";
  const [first, second] = await Promise.all([apiRequest(publicPath), apiRequest(publicPath)]);
  assert.equal(requests.length, 1);
  assert.deepEqual(first, second);
  await apiRequest(publicPath);
  assert.equal(requests.length, 1);

  await apiRequest("/account/profile");
  await apiRequest("/account/profile");
  assert.equal(requests.length, 3);
});
