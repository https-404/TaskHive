import axios from 'axios';
import { localStorageService } from './localstorage.service.js';

const baseUrl = import.meta.env.VITE_API_URL;

const TOKEN_KEYS = {
  access: 'access_token',
  refresh: 'refresh_token',
  user: 'user',
  expiresAt: 'expires_at',
};

function setTokens(data) {
  localStorageService.setItem(TOKEN_KEYS.access, data.access_token);
  localStorageService.setItem(TOKEN_KEYS.refresh, data.refresh_token);
  if (data.expires_in) {
    const expiresAt = Date.now() + data.expires_in * 1000;
    localStorageService.setItem(TOKEN_KEYS.expiresAt, expiresAt);
  }
  if (data.user) {
    localStorageService.setItem(TOKEN_KEYS.user, data.user);
  }
}

function clearTokens() {
  localStorageService.removeItem(TOKEN_KEYS.access);
  localStorageService.removeItem(TOKEN_KEYS.refresh);
  localStorageService.removeItem(TOKEN_KEYS.expiresAt);
  localStorageService.removeItem(TOKEN_KEYS.user);
}

/** @type {import('axios').AxiosInstance | null} */
let apiInstance = null;

function getClient() {
  return apiInstance || axios;
}

const authService = {
  setApi(api) {
    apiInstance = api;
  },

  login: async (email, password) => {
    const url = apiInstance ? '/api/auth/login' : `${baseUrl}/api/auth/login`;
    const { data } = await getClient().post(url, { email, password });
    setTokens(data);
    return data;
  },

  register: async (name, email, password, passwordConfirmation) => {
    const url = apiInstance ? '/api/auth/register' : `${baseUrl}/api/auth/register`;
    const { data } = await getClient().post(url, {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation ?? password,
    });
    setTokens(data);
    return data;
  },

  refresh: async () => {
    const refreshToken = localStorageService.getItem(TOKEN_KEYS.refresh);
    if (!refreshToken) throw new Error('No refresh token');
    const url = apiInstance ? '/api/auth/refresh' : `${baseUrl}/api/auth/refresh`;
    const { data } = await getClient().post(url, { refresh_token: refreshToken });
    setTokens(data);
    return data;
  },

  logout: async () => {
    const refreshToken = localStorageService.getItem(TOKEN_KEYS.refresh);
    const url = apiInstance ? '/api/auth/logout' : `${baseUrl}/api/auth/logout`;
    try {
      await getClient().post(url, { refresh_token: refreshToken });
    } finally {
      clearTokens();
    }
  },

  clearTokens,

  getAccessToken: () => localStorageService.getItem(TOKEN_KEYS.access),
  getRefreshToken: () => localStorageService.getItem(TOKEN_KEYS.refresh),
  getUser: () => localStorageService.getItem(TOKEN_KEYS.user),
  getExpiresAt: () => localStorageService.getItem(TOKEN_KEYS.expiresAt),

  isAccessTokenExpired: () => {
    const expiresAt = localStorageService.getItem(TOKEN_KEYS.expiresAt);
    if (!expiresAt) return true;
    return Date.now() >= expiresAt;
  },

  isAuthenticated: () => {
    return !!(localStorageService.getItem(TOKEN_KEYS.access) || localStorageService.getItem(TOKEN_KEYS.refresh));
  },
};

export default authService;
