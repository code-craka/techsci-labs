<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Guides</h1>
    <div class="bg-white rounded-lg shadow-md p-6">
      <p class="text-gray-600 mb-6">Step-by-step guides to help you get started with the TechSci Labs Email Testing Platform.</p>
      
      <div class="space-y-6">
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Quick Start Guide</h2>
          <p class="text-gray-600 mb-4">Get up and running in minutes with our quick start guide.</p>
          <div class="bg-gray-50 p-4 rounded">
            <p class="text-sm text-gray-700">Coming soon... Complete setup walkthrough including account creation, domain configuration, and first email test.</p>
          </div>
        </div>
        
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Domain Setup</h2>
          <p class="text-gray-600 mb-4">Configure your domains for email testing.</p>
          <div class="bg-gray-50 p-4 rounded">
            <p class="text-sm text-gray-700">Coming soon... DNS configuration, SPF, DKIM, and DMARC setup instructions.</p>
          </div>
        </div>
        
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Email Account Management</h2>
          <p class="text-gray-600 mb-4">Create and manage email accounts and aliases.</p>
          <div class="bg-gray-50 p-4 rounded">
            <p class="text-sm text-gray-700">Coming soon... Account creation, alias configuration, and mailbox management.</p>
          </div>
        </div>
        
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">API Integration</h2>
          <p class="text-gray-600 mb-4">Integrate with our REST API for automated testing.</p>
          <div class="bg-gray-50 p-4 rounded">
            <p class="text-sm text-gray-700">Coming soon... API authentication, endpoints overview, and integration patterns.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  title: 'Guides - TechSci Labs',
  description: 'Step-by-step guides for the TechSci Labs Email Testing Platform'
})
</script>