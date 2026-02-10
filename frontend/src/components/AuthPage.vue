<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth/index.js';

const authStore = useAuthStore();
const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLogin = ref(true);
const loading = ref(false);
const error = ref('');
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const title = computed(() => (isLogin.value ? 'Sign in' : 'Create account'));
const subtitle = computed(() =>
  isLogin.value
    ? 'Welcome back to TaskHive'
    : 'Get started and organise your tasks'
);

/** Build a single error string from API message and validation errors */
function formatApiError(e) {
  const data = e.response?.data;
  const message = data?.message || e.message || 'Something went wrong';
  const errors = data?.errors;
  if (errors && typeof errors === 'object' && Object.keys(errors).length > 0) {
    const parts = Object.entries(errors).flatMap(([field, list]) =>
      Array.isArray(list) ? list.map((msg) => `${field}: ${msg}`) : [`${field}: ${list}`]
    );
    return parts.length ? parts.join(' ') : message;
  }
  return message;
}

const handleSubmit = async () => {
  error.value = '';
  loading.value = true;
  try {
    if (isLogin.value) {
      await authStore.login(email.value, password.value);
    } else {
      await authStore.register(name.value, email.value, password.value, passwordConfirmation.value);
    }
    const redirect = router.currentRoute.value.query.redirect;
    router.push(redirect || '/dashboard');
  } catch (e) {
    error.value = formatApiError(e);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden bg-[linear-gradient(145deg,#0f172a_0%,#1e293b_50%,#0f172a_100%)]"
  >
    <div
      class="absolute inset-0 pointer-events-none [background-image:radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(99,102,241,0.15),transparent),radial-gradient(ellipse_60%_40%_at_100%_100%,rgba(139,92,246,0.1),transparent)]"
      aria-hidden="true"
    />
    <div
      class="relative w-full max-w-[400px] py-10 px-8 rounded-2xl bg-white/[0.97] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.4),0_0_0_1px_rgba(255,255,255,0.05)_inset] backdrop-blur-xl"
    >
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 mb-6">
          <span
            class="w-9 h-9 flex items-center justify-center rounded-lg font-bold text-lg text-white bg-gradient-to-br from-indigo-500 to-violet-500"
          >
            T
          </span>
          <span class="text-xl font-bold text-slate-900 tracking-tight">
            TaskHive
          </span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1 tracking-tight">
          {{ title }}
        </h1>
        <p class="text-[0.9375rem] text-slate-500 m-0">
          {{ subtitle }}
        </p>
      </div>

      <!-- Form: 2 fields (login) or 4 fields (signup) -->
      <form class="flex flex-col gap-5" @submit.prevent="handleSubmit">
        <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {{ error }}
        </p>
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="!isLogin"
            key="name"
            class="flex flex-col gap-1.5"
          >
            <label for="name" class="text-[0.8125rem] font-medium text-slate-700">
              Name
            </label>
            <input
              id="name"
              v-model="name"
              type="text"
              autocomplete="name"
              placeholder="Your name"
              class="w-full px-4 py-3 text-[0.9375rem] text-slate-900 bg-slate-50 border border-slate-200 rounded-lg placeholder:text-slate-400 hover:border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-3 focus:ring-indigo-500/20 transition-colors"
            />
          </div>
        </Transition>

        <div class="flex flex-col gap-1.5">
          <label for="email" class="text-[0.8125rem] font-medium text-slate-700">
            Email
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="you@example.com"
            class="w-full px-4 py-3 text-[0.9375rem] text-slate-900 bg-slate-50 border border-slate-200 rounded-lg placeholder:text-slate-400 hover:border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-3 focus:ring-indigo-500/20 transition-colors"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="password" class="text-[0.8125rem] font-medium text-slate-700">
            Password
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              :placeholder="isLogin ? 'Your password' : 'At least 8 characters'"
              class="w-full px-4 py-3 pr-24 text-[0.9375rem] text-slate-900 bg-slate-50 border border-slate-200 rounded-lg placeholder:text-slate-400 hover:border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-3 focus:ring-indigo-500/20 transition-colors"
            />
            <button
              type="button"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-[0.8125rem] font-medium text-indigo-500 hover:text-indigo-600 focus:outline-none"
              @click="showPassword = !showPassword"
            >
              {{ showPassword ? 'Hide' : 'Show' }}
            </button>
          </div>
        </div>

        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="!isLogin"
            key="confirm"
            class="flex flex-col gap-1.5"
          >
            <label for="password-confirm" class="text-[0.8125rem] font-medium text-slate-700">
              Confirm password
            </label>
            <div class="relative">
              <input
                id="password-confirm"
                v-model="passwordConfirmation"
                :type="showPasswordConfirm ? 'text' : 'password'"
                autocomplete="new-password"
                placeholder="Repeat password"
                class="w-full px-4 py-3 pr-24 text-[0.9375rem] text-slate-900 bg-slate-50 border border-slate-200 rounded-lg placeholder:text-slate-400 hover:border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-3 focus:ring-indigo-500/20 transition-colors"
              />
              <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-[0.8125rem] font-medium text-indigo-500 hover:text-indigo-600 focus:outline-none"
                @click="showPasswordConfirm = !showPasswordConfirm"
              >
                {{ showPasswordConfirm ? 'Hide' : 'Show' }}
              </button>
            </div>
          </div>
        </Transition>

        <button
          type="submit"
          :disabled="loading"
          class="w-full mt-2 py-3.5 px-5 text-[0.9375rem] font-semibold text-white rounded-lg border-0 cursor-pointer transition bg-gradient-to-br from-indigo-500 to-indigo-600 hover:shadow-lg hover:shadow-indigo-500/40 active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Please wait…' : title }}
        </button>
      </form>

      <!-- Divider -->
      <div
        class="flex items-center gap-4 my-6 text-[0.8125rem] text-slate-400 before:content-[''] before:flex-1 before:h-px before:bg-slate-200 after:content-[''] after:flex-1 after:h-px after:bg-slate-200"
      >
        or
      </div>

      <!-- Toggle -->
      <p class="text-center text-[0.9375rem] text-slate-500 m-0">
        {{ isLogin ? "New to TaskHive?" : "Already have an account?" }}
        <button
          type="button"
          class="ml-1 p-0 font-semibold text-indigo-500 bg-transparent border-0 cursor-pointer transition hover:text-indigo-600 hover:underline"
          @click="isLogin = !isLogin"
        >
          {{ isLogin ? 'Sign up' : 'Sign in' }}
        </button>
      </p>
    </div>
  </div>
</template>
