import { apiRequest } from './apiClient.js';

const post = (path, body) => apiRequest(path, { method: 'POST', csrf: true, body });
export const adminShipmentService = {
  list: (status = '') => apiRequest(`/admin/shipments${status ? `?status=${encodeURIComponent(status)}` : ''}`),
  show: id => apiRequest(`/admin/shipments/${id}`),
  create: (orderId, body = {}) => post(`/admin/orders/${orderId}/shipment`, body),
  action: (id, action) => post(`/admin/shipments/${id}/${action}`),
  documentUrl: (id, type) => `/api/v1/admin/shipments/${id}/documents/${type}`,
};
