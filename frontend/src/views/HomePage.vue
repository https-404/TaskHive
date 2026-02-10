<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth/index.js';

const router = useRouter();
const authStore = useAuthStore();

function goToAuth() {
  router.push('/auth');
}
function goToDashboard() {
  router.push('/dashboard');
}
</script>

<template>
  <div class="home">
    <h1>TaskHive</h1>
    <p>Welcome.</p>
    <button v-if="!authStore.isAuthenticated" @click="goToAuth">Log in</button>
    <button v-else @click="goToDashboard">Go to Dashboard</button>
  </div>
</template>

<style scoped>
.home { padding: 2rem; }
button { margin: 0 0.5rem; }
</style>
