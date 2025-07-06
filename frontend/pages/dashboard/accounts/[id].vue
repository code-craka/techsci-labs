<template>
  <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
      Account Management
    </h1>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <p class="text-gray-600 dark:text-gray-400">
        This page is under development. Content coming soon...
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
  layout: 'dashboard'
})

useHead({
  title: 'Account Management'
})
</script>
