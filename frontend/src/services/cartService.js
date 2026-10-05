import { apiRequest } from "./apiClient.js";

const tokenKey = "e4engineers-guest-cart-token";
const token = () => localStorage.getItem(tokenKey) || "";
const request = async (path, options = {}) => {
  const response = await apiRequest(path, { ...options, headers: token() ? { "X-Guest-Cart-Token": token() } : {} });
  const issued = response.data?.meta?.guest_cart_token;
  if (issued) localStorage.setItem(tokenKey, issued);
  return response;
};

export const cartService = {
  get: () => request("/cart"),
  add: (bookId, quantity = 1) => request("/cart/items", { method: "POST", csrf: true, body: { book_id: bookId, quantity } }),
  update: (itemId, quantity) => request(`/cart/items/${itemId}`, { method: "PATCH", csrf: true, body: { quantity } }),
  remove: (itemId) => request(`/cart/items/${itemId}`, { method: "DELETE", csrf: true }),
  clear: () => request("/cart", { method: "DELETE", csrf: true }),
  applyCoupon: (code) => request("/cart/coupon", { method: "POST", csrf: true, body: { code } }),
  removeCoupon: () => request("/cart/coupon", { method: "DELETE", csrf: true }),
  async merge() { const response = await request("/cart/merge", { method: "POST", csrf: true }); localStorage.removeItem(tokenKey); return response; },
};
