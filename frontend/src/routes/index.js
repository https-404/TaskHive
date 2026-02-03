import { createRouter, createWebHistory } from "vue-router";

const routes = [
  { path: "/auth", name: 'Login Page' ,component: () => import("../components/AuthPage.vue") },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
