<script setup>
import HelloWorld from './components/HelloWorld.vue'
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-8">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-4xl font-bold text-gray-800 mb-8 text-center">TaskHive Frontend</h1>
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex gap-4 justify-center mb-6">
          <a href="https://vite.dev" target="_blank">
            <img src="/vite.svg" class="logo h-24 w-24 hover:scale-110 transition-transform" alt="Vite logo" />
          </a>
          <a href="https://vuejs.org/" target="_blank">
            <img src="./assets/vue.svg" class="logo vue h-24 w-24 hover:scale-110 transition-transform" alt="Vue logo" />
          </a>
        </div>
        <HelloWorld msg="Vite + Vue" />
      </div>
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Tech Stack</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="bg-blue-50 p-4 rounded-lg text-center">
            <p class="font-medium text-blue-700">Vue 3</p>
          </div>
          <div class="bg-green-50 p-4 rounded-lg text-center">
            <p class="font-medium text-green-700">Tailwind CSS</p>
          </div>
          <div class="bg-purple-50 p-4 rounded-lg text-center">
            <p class="font-medium text-purple-700">Vite</p>
          </div>
          <div class="bg-yellow-50 p-4 rounded-lg text-center">
            <p class="font-medium text-yellow-700">Vuex</p>
          </div>
          <div class="bg-red-50 p-4 rounded-lg text-center">
            <p class="font-medium text-red-700">Axios</p>
          </div>
          <div class="bg-indigo-50 p-4 rounded-lg text-center">
            <p class="font-medium text-indigo-700">Vue Router</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.logo {
  height: 6em;
  padding: 1.5em;
  will-change: filter;
  transition: filter 300ms;
}
.logo:hover {
  filter: drop-shadow(0 0 2em #646cffaa);
}
.logo.vue:hover {
  filter: drop-shadow(0 0 2em #42b883aa);
}
</style>
