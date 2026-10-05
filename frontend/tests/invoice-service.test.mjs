import test from 'node:test';
import assert from 'node:assert/strict';
import { invoiceService } from '../src/services/invoiceService.js';

test('guest invoice metadata uses encoded order number and secure access token',async context=>{let call;globalThis.sessionStorage={getItem:()=> 'secure-token'};globalThis.fetch=async(url,options)=>{call={url,options};return{ok:true,status:200,json:async()=>({data:{invoice_number:'E4E/INV/1'}})}};context.after(()=>{delete globalThis.fetch;delete globalThis.sessionStorage});await invoiceService.show('E4E/100');assert.match(call.url,/orders\/E4E%2F100\/invoice$/);assert.equal(call.options.headers['X-Order-Access-Token'],'secure-token')});
