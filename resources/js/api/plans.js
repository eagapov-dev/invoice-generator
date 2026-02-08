import api from './axios';

const plansApi = {
    getPlans: () => api.get('/plans'),
    getUserLimits: () => api.get('/user/limits'),
    getUserSubscription: () => api.get('/user/subscription'),
};

export default plansApi;
