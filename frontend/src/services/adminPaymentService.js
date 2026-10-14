import { API_BASE_URL, apiRequest } from "./apiClient.js";

export async function loadPaymentProof(attemptId) {
  let response;
  try {
    response = await fetch(`${API_BASE_URL}/admin/payments/attempts/${encodeURIComponent(attemptId)}/proof`, {
      credentials: "include",
      headers: { Accept: "image/png,image/jpeg,image/webp" },
    });
  } catch {
    throw new Error("Unable to connect to the payment screenshot. Please try again.");
  }

  if (!response.ok) {
    if (response.status === 401 || response.status === 403) throw new Error("Your admin session has expired. Sign in again to view the screenshot.");
    if (response.status === 404) throw new Error("This payment screenshot is no longer available. Please contact support before reviewing the payment.");
    throw new Error("Unable to load the payment screenshot. Please try again.");
  }

  const blob = await response.blob();
  if (!blob.type.startsWith("image/") || !blob.size) throw new Error("The payment screenshot could not be displayed.");
  return blob;
}

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
