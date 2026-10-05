import test from "node:test";
import assert from "node:assert/strict";
import { articlesService } from "../src/services/articlesService.js";

test("article filters are sent to the backend",async context=>{let requested="";globalThis.fetch=async url=>{requested=url;return{ok:true,status:200,json:async()=>({success:true,data:[]})}};context.after(()=>delete globalThis.fetch);await articlesService.getArticles({search:"grid",discipline:"power-systems-engineering",featured:1,sort:"latest"});const url=new URL(requested);assert.equal(url.pathname,"/api/v1/articles");assert.equal(url.searchParams.get("search"),"grid");assert.equal(url.searchParams.get("discipline"),"power-systems-engineering");assert.equal(url.searchParams.get("featured"),"1")});
test("article slugs are safely encoded",async context=>{let requested="";globalThis.fetch=async url=>{requested=url;return{ok:true,status:200,json:async()=>({success:true,data:{}})}};context.after(()=>delete globalThis.fetch);await articlesService.getArticle("power systems");assert.match(requested,/\/articles\/power%20systems$/)});
