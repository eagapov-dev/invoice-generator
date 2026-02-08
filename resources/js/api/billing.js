import api from './axios';

const billingApi = {
    createCheckout: (planSlug, billingPeriod) =>
        api.post('/billing/checkout', { plan_slug: planSlug, billing_period: billingPeriod }),
    getPortalUrl: () => api.get('/billing/portal'),
    getBillingStatus: () => api.get('/billing/status'),
};

export default billingApi;
