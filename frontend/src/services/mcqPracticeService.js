import { apiRequest } from './apiClient.js';

export const mcqPracticeService = {
  list: (subject = '', page = 1) => apiRequest(`/mcq-practice?${new URLSearchParams({ subject, page })}`),
  show: (slug) => apiRequest(`/mcq-practice/${encodeURIComponent(slug)}`),
  submit: (slug, answers) => apiRequest(`/mcq-practice/${encodeURIComponent(slug)}/submit`, { method: 'POST', csrf: true, body: { answers } }),
  adminList: (search = '', page = 1) => apiRequest(`/admin/mcq-practice?${new URLSearchParams({ search, page })}`),
  create: (body) => apiRequest('/admin/mcq-practice', { method: 'POST', csrf: true, body }),
  update: (id, body) => apiRequest(`/admin/mcq-practice/${id}`, { method: 'PUT', csrf: true, body }),
  archive: (id) => apiRequest(`/admin/mcq-practice/${id}`, { method: 'DELETE', csrf: true }),
};
