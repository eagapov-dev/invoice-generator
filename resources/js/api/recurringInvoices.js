import api from './axios';

const recurringInvoicesApi = {
    getAll: (params = {}) => api.get('/recurring-invoices', { params }),
    get: (id) => api.get(`/recurring-invoices/${id}`),
    create: (data) => api.post('/recurring-invoices', data),
    update: (id, data) => api.put(`/recurring-invoices/${id}`, data),
    delete: (id) => api.delete(`/recurring-invoices/${id}`),
    toggleActive: (id) => api.patch(`/recurring-invoices/${id}/toggle`),
};

export default recurringInvoicesApi;
