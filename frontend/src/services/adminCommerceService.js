import { apiRequest } from './apiClient';

const request = (path, options) => apiRequest(`/admin${path}`, options);

export const adminCommerceService = {
  books: (params = {}) => request(`/books?${new URLSearchParams({ per_page: 100, ...params })}`),
  book: (id) => request(`/books/${encodeURIComponent(id)}`),
  saveBook: (data, id) => request(id ? `/books/${encodeURIComponent(id)}` : '/books', { method: id ? 'PATCH' : 'POST', csrf: true, body: data }),
  deleteBook: (id) => request(`/books/${encodeURIComponent(id)}`, { method: 'DELETE', csrf: true }),
  inventory: (id) => request(`/books/${encodeURIComponent(id)}/inventory`),
  adjustStock: (id, data) => request(`/books/${encodeURIComponent(id)}/inventory/adjust`, { method: 'POST', csrf: true, body: data }),
  setLowStockThreshold: (id, value) => request(`/books/${encodeURIComponent(id)}/inventory/threshold`, { method: 'PATCH', csrf: true, body: { low_stock_threshold: value } }),
  authors: () => request('/authors?per_page=100'),
  createAuthor: (name) => request('/authors', { method: 'POST', csrf: true, body: { name } }),
  publishers: () => request('/publishers?per_page=100'),
  createPublisher: (name) => request('/publishers', { method: 'POST', csrf: true, body: { name } }),
  disciplines: () => request('/engineering-disciplines?per_page=100'),
  categories: () => request('/categories?per_page=100'),
  media: () => request('/media?per_page=100'),
  uploadMedia: (file, altText = '') => {
    const body = new FormData();
    body.append('file', file);
    body.append('storage', 'public');
    body.append('alt_text', altText);
    return request('/media', { method: 'POST', csrf: true, body });
  },
};
