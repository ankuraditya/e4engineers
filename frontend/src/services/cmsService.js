import { apiRequest } from "./apiClient.js";

export const cmsService = {
  page: (slug) => apiRequest(`/pages/${encodeURIComponent(slug)}`),
  banners: (placement) => apiRequest(`/banners?placement=${encodeURIComponent(placement)}`),
  settings: () => apiRequest("/settings/public"),
};
