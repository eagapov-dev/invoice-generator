import api from './axios';

export const clientsApi = {
    getAll: (params = {}) => api.get('/clients', { params }),
    get: (id) => api.get(`/clients/${id}`),
    create: (data) => api.post('/clients', data),
    update: (id, data) => api.put(`/clients/${id}`, data),
    delete: (id) => api.delete(`/clients/${id}`),
};

export default clientsApi;
