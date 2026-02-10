import { createApp } from 'vue';
import { createPinia } from 'pinia';
import './style.css';
import App from './App.vue';
import router from './routes';
import authService from './services/auth.service.js';
import { useAuthStore } from './stores/auth/index.js';
import { createApi } from './services/api.js';

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

const api = createApi(authService, {
  onUnauthenticated: () => router.push('/auth'),
  onClearAuth: () => {
    const authStore = useAuthStore();
    authStore.clearAuth();
  },
});
authService.setApi(api);

app.use(router);
app.mount('#app');
