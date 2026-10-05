import axios from 'axios';

const http = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

http.interceptors.request.use((config) => {
    const token = localStorage.getItem('ef_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

http.interceptors.response.use(
    (response) => response,
    (error) => {
        const url = error.config?.url || '';

        if (error.response?.status === 401 && !url.includes('/login') && !url.includes('/register')) {
            localStorage.removeItem('ef_token');

            if (!window.location.pathname.startsWith('/login')) {
                window.location.assign('/login');
            }
        }

        return Promise.reject(error);
    },
);

export default http;
