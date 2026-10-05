import { apiRequest } from "./apiClient.js";

function query(params={}){const q=new URLSearchParams();Object.entries(params).forEach(([k,v])=>{if(v!==undefined&&v!==null&&v!==""&&v!=="All")q.set(k,String(v))});return q.size?`?${q}`:""}
export const articlesService={
  getArticles(params={}){return apiRequest(`/articles${query(params)}`)},
  getArticle(slug){return apiRequest(`/articles/${encodeURIComponent(slug)}`)},
  getDisciplines(){return apiRequest("/engineering-disciplines")},
  getCategories(){return apiRequest("/categories?context=article")},
  getTopics(){return apiRequest("/topics")},
};
