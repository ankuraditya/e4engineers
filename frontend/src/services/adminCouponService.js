import { apiRequest } from './apiClient';

const base = '/admin/coupons';
export const adminCouponService = {
  list: () => apiRequest(`${base}?per_page=100`),
  create: (data) => apiRequest(base, { method: 'POST', csrf: true, body: data }),
  update: (id, data) => apiRequest(`${base}/${id}`, { method: 'PATCH', csrf: true, body: data }),
  setActive: (id, is_active) => apiRequest(`${base}/${id}/status`, { method: 'PATCH', csrf: true, body: { is_active } }),
  remove: (id) => apiRequest(`${base}/${id}`, { method: 'DELETE', csrf: true }),
};
