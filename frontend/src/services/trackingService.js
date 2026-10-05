import { apiRequest } from './apiClient.js';

export const trackingService = {
  track: (orderNumber, token = '') => apiRequest(`/orders/${encodeURIComponent(orderNumber)}/tracking`, {
    headers: token ? { 'X-Order-Access-Token': token } : {},
  }),
};
