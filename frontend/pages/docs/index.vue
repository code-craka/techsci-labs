<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Documentation</h1>
    <div class="bg-white rounded-lg shadow-md p-6">
      <p class="text-gray-600 mb-4">Welcome to the TechSci Labs Email Testing Platform documentation.</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="border rounded-lg p-4">
          <h3 class="font-semibold text-lg mb-2">Getting Started</h3>
          <p class="text-gray-600 mb-4">Learn how to set up your email testing environment.</p>
          <NuxtLink to="/docs/guides" class="text-blue-600 hover:text-blue-800">View Guides ’</NuxtLink>
        </div>
        <div class="border rounded-lg p-4">
          <h3 class="font-semibold text-lg mb-2">API Reference</h3>
          <p class="text-gray-600 mb-4">Comprehensive API documentation for developers.</p>
          <NuxtLink to="/docs/api" class="text-blue-600 hover:text-blue-800">View API Docs ’</NuxtLink>
        </div>
        <div class="border rounded-lg p-4">
          <h3 class="font-semibold text-lg mb-2">Examples</h3>
          <p class="text-gray-600 mb-4">Practical examples and use cases.</p>
          <NuxtLink to="/docs/examples" class="text-blue-600 hover:text-blue-800">View Examples ’</NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  title: 'Documentation - TechSci Labs',
  description: 'Complete documentation for the TechSci Labs Email Testing Platform'
})
</script>