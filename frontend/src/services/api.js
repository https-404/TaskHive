import axios from 'axios';

// Use empty string in dev so Vite proxy handles /api; set VITE_API_URL for production or no proxy
const baseURL = import.meta.env.VITE_API_URL ?? '';

/** Routes that must not get Bearer token and must not trigger refresh on 401 */
const AUTH_EXEMPT_PATTERNS = [
  /\/auth\/login$/,
  /\/auth\/register$/,
  /\/auth\/refresh$/,
];

function isAuthExempt(url) {
  if (!url) return true;
  const path = typeof url === 'string' ? url : url.toString();
  return AUTH_EXEMPT_PATTERNS.some((re) => re.test(path));
}

/** Single in-flight refresh to avoid multiple refresh calls on concurrent 401s */
let refreshPromise = null;

/**
 * Create the shared axios instance and attach interceptors that use authService.
 * Call this once from main.js after authService and router are available.
 * @param {import('./auth.service.js').default} authService
 * @param {{ onUnauthenticated?: () => void, onClearAuth?: () => void }} options
 * @returns {import('axios').AxiosInstance}
 */
/** Shared API instance after createApi() has been called (e.g. from main.js). */
let sharedApi = null;

export function createApi(authService, options = {}) {
  const onClearAuth = options.onClearAuth || (() => {});
  const onUnauthenticated = options.onUnauthenticated || (() => {
    if (typeof window !== 'undefined' && window.location && !window.location.pathname.startsWith('/auth')) {
      window.location.href = '/auth';
    }
  });

  const api = axios.create({
    baseURL,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  });

  api.interceptors.request.use(
    (config) => {
      if (isAuthExempt(config.url)) return config;
      const token = authService.getAccessToken();
      if (token) config.headers.Authorization = `Bearer ${token}`;
      return config;
    },
    (error) => Promise.reject(error)
  );

  api.interceptors.response.use(
    (response) => response,
    async (error) => {
      const originalRequest = error.config;

      if (error.response?.status !== 401) {
        return Promise.reject(error);
      }

      if (isAuthExempt(originalRequest.url)) {
        return Promise.reject(error);
      }

      if (originalRequest._retryAfterRefresh) {
        authService.clearTokens();
        onClearAuth();
        onUnauthenticated();
        return Promise.reject(error);
      }

      try {
        if (!refreshPromise) {
          refreshPromise = authService.refresh();
        }
        await refreshPromise;
        refreshPromise = null;
        const newToken = authService.getAccessToken();
        if (newToken) originalRequest.headers.Authorization = `Bearer ${newToken}`;
        originalRequest._retryAfterRefresh = true;
        return api(originalRequest);
      } catch {
        refreshPromise = null;
        authService.clearTokens();
        onClearAuth();
        onUnauthenticated();
        return Promise.reject(error);
      }
    }
  );

  sharedApi = api;
  return api;
}

/** Get the shared API instance (available after createApi has been called). */
export function getApi() {
  return sharedApi;
}

export { isAuthExempt };
