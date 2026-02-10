export const getters = {
  isAuthenticated: (state) => !!(state.accessToken || state.refreshToken),
  isAccessTokenExpired: (state) => {
    if (!state.expiresAt) return true;
    return Date.now() >= state.expiresAt;
  },
};
