import test from "node:test";
import assert from "node:assert/strict";
import { accountDestination, protectedLoginUrl, safeRedirectTarget } from "../src/services/authNavigation.js";

test("protected routes preserve their internal destination", () => {
  assert.equal(protectedLoginUrl("/checkout", ""), "/login?redirect=%2Fcheckout");
  assert.equal(protectedLoginUrl("/account/orders", "?page=2"), "/login?redirect=%2Faccount%2Forders%3Fpage%3D2");
});

test("post-login redirects reject external and protocol-relative destinations", () => {
  assert.equal(safeRedirectTarget("/checkout"), "/checkout");
  assert.equal(safeRedirectTarget("https://malicious.example/path"), "/account");
  assert.equal(safeRedirectTarget("//malicious.example/path"), "/account");
});

test("header account destination reflects restored authentication state", () => {
  assert.equal(accountDestination(false), "/login");
  assert.equal(accountDestination(true), "/account");
});
