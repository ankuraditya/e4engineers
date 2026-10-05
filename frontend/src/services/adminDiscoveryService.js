import { apiRequest } from './apiClient';
const request = (path, options) => apiRequest(`/admin${path}`, options);
const paths = { notices: '/notices', gallery: '/gallery/albums', videos: '/videos' };
export const adminDiscoveryService = {
  list: type => request(`${paths[type]}?per_page=100`),
  album: id => request(`/gallery/albums/${id}`),
  save: (type, data, id) => request(id ? `${paths[type]}/${id}` : paths[type], { method: id ? 'PUT' : 'POST', csrf: true, body: data }),
  remove: (type, id) => request(`${paths[type]}/${id}`, { method: 'DELETE', csrf: true }),
  media: () => request('/media?type=image&per_page=100'),
  upload: file => { const body = new FormData(); body.append('file', file); body.append('storage', 'public'); return request('/media', { method: 'POST', csrf: true, body }); },
  addImages: (id, images) => request(`/gallery/albums/${id}/images`, { method: 'POST', csrf: true, body: { images } }),
  removeImage: id => request(`/gallery/images/${id}`, { method: 'DELETE', csrf: true }),
};
