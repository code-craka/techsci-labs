<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
      <NuxtLink to="/dashboard/domains" class="text-gray-600 hover:text-gray-800 mr-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">Add New Domain</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
      <form class="space-y-6">
        <div>
          <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Domain Name</label>
          <input 
            type="text" 
            id="domain" 
            v-model="domainForm.name" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="example.com"
            required
          >
          <p class="text-sm text-gray-600 mt-1">Enter your domain name without www or http://</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Domain Type</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="radio" v-model="domainForm.type" value="production" class="mr-2">
              <span class="text-sm text-gray-900">Production Domain</span>
            </label>
            <label class="flex items-center">
              <input type="radio" v-model="domainForm.type" value="testing" class="mr-2">
              <span class="text-sm text-gray-900">Testing Domain</span>
            </label>
          </div>
        </div>
        
        <div>
          <label class="flex items-center">
            <input type="checkbox" v-model="domainForm.catchAll" class="mr-2">
            <span class="text-sm text-gray-900">Enable catch-all emails</span>
          </label>
          <p class="text-sm text-gray-600 mt-1">Receive emails sent to any address at this domain</p>
        </div>
        
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
          <textarea 
            id="description" 
            v-model="domainForm.description" 
            rows="3" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="Brief description of this domain's purpose"
          ></textarea>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="font-medium text-blue-900 mb-2">Next Steps</h3>
          <p class="text-sm text-blue-800 mb-2">After adding your domain, you'll need to:</p>
          <ul class="text-sm text-blue-800 space-y-1 ml-4">
            <li>1. Configure DNS records (MX, SPF, DKIM)</li>
            <li>2. Verify domain ownership</li>
            <li>3. Set up email accounts</li>
          </ul>
        </div>
        
        <div class="flex justify-end space-x-4">
          <NuxtLink to="/dashboard/domains" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
            Cancel
          </NuxtLink>
          <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
            Add Domain
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Add Domain - Dashboard',
  description: 'Add a new domain for email testing'
})

// Reactive form data
const domainForm = ref({
  name: '',
  type: 'testing',
  catchAll: false,
  description: ''
})

// Form submission
const submitForm = async () => {
  try {
    // TODO: Implement domain creation logic
    console.log('Creating domain:', domainForm.value)
    // Redirect to domain management after creation
    await navigateTo('/dashboard/domains')
  } catch (error) {
    console.error('Error creating domain:', error)
  }
}
</script>