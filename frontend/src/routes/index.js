import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth/index.js';

const routes = [
  { path: '/', name: 'Home', component: () => import('../views/HomePage.vue'), meta: { requiresAuth: false } },
  { path: '/auth', name: 'Auth', component: () => import('../components/AuthPage.vue'), meta: { requiresAuth: false } },
  { path: '/dashboard', name: 'Dashboard', component: () => import('../views/DashboardPage.vue'), meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) {
    return true;
  }

  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    return { path: '/auth', query: { redirect: to.fullPath } };
  }

  if (authStore.isAccessTokenExpired && authStore.refreshToken) {
    try {
      await authStore.refresh();
    } catch {
      authStore.clearAuth();
      return { path: '/auth', query: { redirect: to.fullPath } };
    }
  }

  return true;
});

export default router;
