import authService from '../../services/auth.service.js';

export function initialState() {
  return {
    user: authService.getUser(),
    accessToken: authService.getAccessToken(),
    refreshToken: authService.getRefreshToken(),
    expiresAt: authService.getExpiresAt(),
  };
}
