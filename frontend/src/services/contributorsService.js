import { apiRequest } from "./apiClient.js";

function query(params = {}) {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "" && value !== "All") search.set(key, String(value));
  });
  const suffix = search.toString();
  return suffix ? `?${suffix}` : "";
}

export const contributorsService = {
  getContributors(params = {}) { return apiRequest(`/contributors${query(params)}`); },
  getContributor(slug) { return apiRequest(`/contributors/${encodeURIComponent(slug)}`); },
  getDisciplines() { return apiRequest("/engineering-disciplines"); },
};
