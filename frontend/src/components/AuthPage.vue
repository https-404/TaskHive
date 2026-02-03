<script setup>
import { ref, computed } from 'vue';

const email = ref('');
const password = ref('');
const isLogin = ref(true);

const title = computed(() => (isLogin.value ? 'Login' : 'Signup'));

const toggleMode = () => {
  isLogin.value = !isLogin.value;
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-blue-400">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-md">

      <!-- Title -->
      <div class="flex items-center justify-center mb-6">
        <h1 class="text-xl font-semibold">{{ title }}</h1>
      </div>

      <!-- Inputs -->
      <div class="space-y-4">
        <input
          v-model="email"
          type="email"
          placeholder="Enter your Email Address"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />

        <input
          v-model="password"
          type="password"
          placeholder="Enter your Password"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <!-- Submit -->
      <div class="pt-6">
        <button
          type="button"
          class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition"
        >
          {{ title }}
        </button>
      </div>

      <!-- Toggle -->
      <div class="pt-2 flex items-center justify-center space-x-1 text-sm">
        <span>New to TaskHive?</span>
        <button
          type="button"
          @click="toggleMode"
          class="text-indigo-600 hover:underline font-medium"
        >
          {{ isLogin ? 'Signup' : 'Login' }} here
        </button>
      </div>

    </div>
  </div>
</template>
