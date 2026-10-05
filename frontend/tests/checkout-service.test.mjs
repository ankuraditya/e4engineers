import test from "node:test";
import assert from "node:assert/strict";
import { checkoutService } from "../src/services/checkoutService.js";

test("guest checkout sends cart identity, idempotency key, and COD without client totals", async (context) => {
  const calls=[];
  globalThis.localStorage={getItem:(key)=>key.includes("guest-cart")?"a".repeat(64):null};
  globalThis.sessionStorage={getItem:()=>null,setItem:()=>{}};
  globalThis.document={cookie:"XSRF-TOKEN=test"};
  globalThis.fetch=async(url,options={})=>{calls.push({url,options});return url.endsWith("csrf-cookie")?{ok:true,status:204}:{ok:true,status:201,json:async()=>({data:{order:{order_number:"E4E-1"}}})}};
  context.after(()=>{delete globalThis.fetch;delete globalThis.localStorage;delete globalThis.sessionStorage;delete globalThis.document});
  await checkoutService.placeOrder({contact:{email:"guest@example.com"},shipping_quote_id:"quote",payment_method:"cod",idempotency_key:"stable-key"});
  assert.equal(calls[1].options.headers["X-Guest-Cart-Token"],"a".repeat(64));
  const body=JSON.parse(calls[1].options.body);assert.equal(body.payment_method,"cod");assert.equal(body.idempotency_key,"stable-key");assert.equal(body.total,undefined);
});

test("guest success access token is encoded", async (context) => {
  let requested="";globalThis.fetch=async(url)=>{requested=url;return{ok:true,status:200,json:async()=>({data:{}})}};context.after(()=>delete globalThis.fetch);
  await checkoutService.orderSuccess("E4E/100", "secret token");
  assert.match(requested,/E4E%2F100\/success\?token=secret%20token$/);
});
