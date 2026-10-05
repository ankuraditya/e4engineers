import { apiRequest } from "./apiClient.js";

const query = (params = {}) => {
  const values = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== "" && value !== undefined && value !== null) values.set(key, value);
  });
  return values.size ? `?${values}` : "";
};

export const resourcesService = {
  getResources: (params) => apiRequest(`/resources${query(params)}`),
  getResource: (slug) => apiRequest(`/resources/${encodeURIComponent(slug)}`),
  getFeaturedResources: () => apiRequest("/resources?featured=1&sort=featured&per_page=6"),
  getResourceTypes: () => apiRequest("/resource-types"),
  getDisciplines: () => apiRequest("/engineering-disciplines"),
};
