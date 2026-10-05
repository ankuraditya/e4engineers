import { API_BASE_URL, ApiError, apiRequest } from "./apiClient.js";

export const digitalAccessService = {
  getLibrary: (params = {}) => apiRequest(`/account/digital-resources?${new URLSearchParams(Object.entries(params).filter(([, value]) => value !== ""))}`),
  getDownloads: () => apiRequest("/account/downloads"),
  async download(type, slug, title) {
    const response = await fetch(`${API_BASE_URL}/${type}s/${encodeURIComponent(slug)}/download`, { credentials: "include", headers: { Accept: "application/octet-stream" } });
    if (!response.ok) { const payload = await response.json().catch(() => ({})); throw new ApiError(payload.message || "Download unavailable.", response.status); }
    const blobUrl = URL.createObjectURL(await response.blob());
    const link = document.createElement("a"); link.href = blobUrl; link.download = title || slug; document.body.append(link); link.click(); link.remove(); URL.revokeObjectURL(blobUrl);
  },
};
