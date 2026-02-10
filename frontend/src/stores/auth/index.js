import { defineStore } from 'pinia';
import { initialState } from './state.js';
import { getters } from './getters.js';
import { actions } from './actions.js';

export const useAuthStore = defineStore('auth', {
  state: initialState,
  getters,
  actions,
});
