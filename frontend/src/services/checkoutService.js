import { apiRequest } from "./apiClient.js";

const cartHeaders = () => {
  const token = localStorage.getItem("e4engineers-guest-cart-token");
  return token ? { "X-Guest-Cart-Token": token } : {};
};

export const checkoutService = {
  accountDashboard: () => apiRequest('/account/dashboard'),
  requestWithdrawal: (amount_rupees, upi_id) => apiRequest('/account/referral-withdrawals', { method: 'POST', csrf: true, body: { amount_rupees, upi_id } }),
  placeOrder: (payload) => apiRequest("/checkout/place-order", { method: "POST", csrf: true, headers: cartHeaders(), body: payload }),
  orderSuccess: (orderNumber, token = "") => apiRequest(`/orders/${encodeURIComponent(orderNumber)}/success${token ? `?token=${encodeURIComponent(token)}` : ""}`),
  orders: ({ status = "", search = "" } = {}) => apiRequest(`/account/orders?${new URLSearchParams({ ...(status ? { status } : {}), ...(search ? { search } : {}) })}`),
  order: (orderNumber) => apiRequest(`/account/orders/${encodeURIComponent(orderNumber)}`),
  saveAccess(orderNumber, token) { if (token) sessionStorage.setItem(`e4engineers-order-${orderNumber}`, token); },
  readAccess: (orderNumber) => sessionStorage.getItem(`e4engineers-order-${orderNumber}`) || "",
};
