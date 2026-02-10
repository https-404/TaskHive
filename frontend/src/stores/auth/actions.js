import authService from '../../services/auth.service.js';

export const actions = {
  setApi(api) {
    authService.setApi(api);
  },

  syncFromStorage() {
    this.user = authService.getUser();
    this.accessToken = authService.getAccessToken();
    this.refreshToken = authService.getRefreshToken();
    this.expiresAt = authService.getExpiresAt();
  },

  clearAuth() {
    authService.clearTokens();
    this.user = null;
    this.accessToken = null;
    this.refreshToken = null;
    this.expiresAt = null;
  },

  async login(email, password) {
    const data = await authService.login(email, password);
    this.syncFromStorage();
    return data;
  },

  async register(name, email, password, passwordConfirmation) {
    const data = await authService.register(name, email, password, passwordConfirmation);
    this.syncFromStorage();
    return data;
  },

  async refresh() {
    const data = await authService.refresh();
    this.syncFromStorage();
    return data;
  },

  async logout() {
    await authService.logout();
    this.clearAuth();
  },
};
