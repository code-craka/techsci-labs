<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
      <NuxtLink to="/dashboard/api-keys" class="text-gray-600 hover:text-gray-800 mr-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">Create API Key</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
      <form class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-2">API Key Name</label>
          <input 
            type="text" 
            id="name" 
            v-model="apiKeyForm.name" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="e.g., Production API Key, Development Key"
            required
          >
          <p class="text-sm text-gray-600 mt-1">Choose a descriptive name to identify this API key</p>
        </div>
        
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
          <textarea 
            id="description" 
            v-model="apiKeyForm.description" 
            rows="3" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="Brief description of what this API key will be used for"
          ></textarea>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
          <div class="space-y-3">
            <label class="flex items-start space-x-3">
              <input type="checkbox" v-model="apiKeyForm.permissions.read" class="mt-1">
              <div>
                <span class="text-sm font-medium text-gray-900">Read Access</span>
                <p class="text-xs text-gray-600">Allow reading emails, domains, and account information</p>
              </div>
            </label>
            <label class="flex items-start space-x-3">
              <input type="checkbox" v-model="apiKeyForm.permissions.write" class="mt-1">
              <div>
                <span class="text-sm font-medium text-gray-900">Write Access</span>
                <p class="text-xs text-gray-600">Allow creating and modifying emails, domains, and accounts</p>
              </div>
            </label>
            <label class="flex items-start space-x-3">
              <input type="checkbox" v-model="apiKeyForm.permissions.delete" class="mt-1">
              <div>
                <span class="text-sm font-medium text-gray-900">Delete Access</span>
                <p class="text-xs text-gray-600">Allow deleting emails, domains, and accounts</p>
              </div>
            </label>
            <label class="flex items-start space-x-3">
              <input type="checkbox" v-model="apiKeyForm.permissions.admin" class="mt-1">
              <div>
                <span class="text-sm font-medium text-gray-900">Admin Access</span>
                <p class="text-xs text-gray-600">Full administrative access to all resources</p>
              </div>
            </label>
          </div>
        </div>
        
        <div>
          <label for="rateLimit" class="block text-sm font-medium text-gray-700 mb-2">Rate Limit (requests per hour)</label>
          <select id="rateLimit" v-model="apiKeyForm.rateLimit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="100">100 requests/hour (Development)</option>
            <option value="500">500 requests/hour (Standard)</option>
            <option value="1000">1,000 requests/hour (Professional)</option>
            <option value="5000">5,000 requests/hour (Enterprise)</option>
            <option value="unlimited">Unlimited (Admin only)</option>
          </select>
        </div>
        
        <div>
          <label for="expiresAt" class="block text-sm font-medium text-gray-700 mb-2">Expiration</label>
          <select id="expiresAt" v-model="apiKeyForm.expiresAt" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="never">Never expires</option>
            <option value="30">30 days</option>
            <option value="90">90 days</option>
            <option value="180">6 months</option>
            <option value="365">1 year</option>
            <option value="custom">Custom date</option>
          </select>
          
          <div v-if="apiKeyForm.expiresAt === 'custom'" class="mt-2">
            <input 
              type="date" 
              v-model="apiKeyForm.customExpiryDate" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">IP Restrictions (Optional)</label>
          <div class="space-y-2">
            <div v-for="(ip, index) in apiKeyForm.allowedIPs" :key="index" class="flex items-center space-x-2">
              <input 
                type="text" 
                v-model="apiKeyForm.allowedIPs[index]" 
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                placeholder="192.168.1.1 or 192.168.1.0/24"
              >
              <button type="button" @click="removeIP(index)" class="text-red-600 hover:text-red-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            <button type="button" @click="addIP" class="text-blue-600 hover:text-blue-800 text-sm">
              + Add IP Address
            </button>
          </div>
          <p class="text-sm text-gray-600 mt-1">Restrict API key usage to specific IP addresses or CIDR blocks</p>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <div>
              <h3 class="font-medium text-yellow-900 mb-1">Important Security Notice</h3>
              <p class="text-sm text-yellow-800">Your API key will be shown only once after creation. Make sure to copy and store it securely. You won't be able to see the full key again.</p>
            </div>
          </div>
        </div>
        
        <div class="flex justify-end space-x-4">
          <NuxtLink to="/dashboard/api-keys" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
            Cancel
          </NuxtLink>
          <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
            Create API Key
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Create API Key - Dashboard',
  description: 'Create a new API key for programmatic access'
})

// Reactive form data
const apiKeyForm = ref({
  name: '',
  description: '',
  permissions: {
    read: true,
    write: false,
    delete: false,
    admin: false
  },
  rateLimit: '100',
  expiresAt: 'never',
  customExpiryDate: '',
  allowedIPs: ['']
})

// Methods
const addIP = () => {
  apiKeyForm.value.allowedIPs.push('')
}

const removeIP = (index: number) => {
  if (apiKeyForm.value.allowedIPs.length > 1) {
    apiKeyForm.value.allowedIPs.splice(index, 1)
  }
}

const submitForm = async () => {
  try {
    // TODO: Implement API key creation logic
    console.log('Creating API key:', apiKeyForm.value)
    // Redirect to API keys list after creation
    await navigateTo('/dashboard/api-keys')
  } catch (error) {
    console.error('Error creating API key:', error)
  }
}
</script>