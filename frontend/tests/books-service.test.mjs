import test from "node:test";
import assert from "node:assert/strict";
import { booksService, mapBook } from "../src/services/booksService.js";

test("book catalogue filters and pagination are sent to the API", async (context) => {
  let requested=""; globalThis.fetch=async(url)=>{requested=url;return{ok:true,status:200,json:async()=>({success:true,data:[],meta:{}})}};context.after(()=>delete globalThis.fetch);
  await booksService.getBooks({search:"power",discipline:"electrical-engineering",author:"test-author",publisher:"test-publisher",min_price:100,max_price:500,featured:1,sort:"price-low-high",page:2});
  const url=new URL(requested); assert.equal(url.pathname,"/api/v1/books"); assert.equal(url.searchParams.get("author"),"test-author"); assert.equal(url.searchParams.get("max_price"),"500"); assert.equal(url.searchParams.get("page"),"2");
});

test("book detail slug is safely encoded and API book maps to existing cart shape", async (context) => {
  let requested=""; globalThis.fetch=async(url)=>{requested=url;return{ok:true,status:200,json:async()=>({success:true,data:{}})}};context.after(()=>delete globalThis.fetch); await booksService.getBook("power systems");assert.match(requested,/\/books\/power%20systems$/);
  const book=mapBook({title:"Power",selling_price:"499.00",mrp:"599.00",author_summary:"A. Author",discipline:{name:"Electrical"},publisher:{name:"E4E"},cover:{url:"/cover.webp"},format:"paperback"});assert.equal(book.price,499);assert.equal(book.author,"A. Author");assert.equal(book.image,"/cover.webp");
});

test("backend inventory status maps to the existing availability UI", () => {
  assert.equal(mapBook({ inventory: { status: "IN_STOCK" } }).stock, "In Stock");
  assert.equal(mapBook({ inventory: { status: "LOW_STOCK" } }).stock, "Low Stock");
  assert.equal(mapBook({ inventory: { status: "OUT_OF_STOCK" } }).stock, "Out of Stock");
});
