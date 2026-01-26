import api from './axios';

export const invoicesApi = {
    getAll: (params = {}) => api.get('/invoices', { params }),
    get: (id) => api.get(`/invoices/${id}`),
    create: (data) => api.post('/invoices', data),
    update: (id, data) => api.put(`/invoices/${id}`, data),
    delete: (id) => api.delete(`/invoices/${id}`),
    updateStatus: (id, status) => api.patch(`/invoices/${id}/status`, { status }),
    send: (id) => api.post(`/invoices/${id}/send`),
    getPdfUrl: (id) => api.get(`/invoices/${id}/pdf-url`),
};

export default invoicesApi;
