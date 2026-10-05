import { apiRequest } from "./apiClient.js";

export const adminPaymentService = {
  providers: () => apiRequest("/admin/payments/providers"),
  settings: () => apiRequest("/admin/payments/settings"),
  saveSettings: data => apiRequest("/admin/payments/settings", { method: "PUT", csrf: true, body: data }),
  credentials: (id, credentials, clear = []) => apiRequest(`/admin/payments/providers/${id}/credentials`, { method: "PUT", csrf: true, body: { credentials, clear } }),
  scanCode: (id, form) => apiRequest(`/admin/payments/providers/${id}/scan-code`, { method: "POST", csrf: true, body: form }),
  environment: (id, environment) => apiRequest(`/admin/payments/providers/${id}/environment`, { method: "PATCH", csrf: true, body: { environment } }),
  test: id => apiRequest(`/admin/payments/providers/${id}/test-connection`, { method: "POST", csrf: true }),
  toggle: (id, enabled) => apiRequest(`/admin/payments/providers/${id}/toggle`, { method: "PATCH", csrf: true, body: { enabled } }),
  makeDefault: id => apiRequest(`/admin/payments/providers/${id}/default`, { method: "PATCH", csrf: true }),
  attempts: () => apiRequest("/admin/payments/attempts"),
  transactions: () => apiRequest("/admin/payments/transactions"),
  reconcile: id => apiRequest(`/admin/payments/attempts/${id}/reconcile`, { method: "POST", csrf: true }),
  review: (id, decision, note = "") => apiRequest(`/admin/payments/attempts/${id}/review`, { method: "POST", csrf: true, body: { decision, note } }),
};
