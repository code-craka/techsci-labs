<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
      <NuxtLink to="/dashboard/accounts" class="text-gray-600 hover:text-gray-800 mr-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">Create Email Account</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
      <form class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
            <input 
              id="username" 
              v-model="accountForm.username" 
              type="text" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
              placeholder="john.doe"
              required
            >
            <p class="text-sm text-gray-600 mt-1">The part before @ in the email address</p>
          </div>
          
          <div>
            <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Domain</label>
            <select id="domain" v-model="accountForm.domain" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <option value="">Select a domain</option>
              <option value="example.com">example.com</option>
              <option value="test.dev">test.dev</option>
            </select>
          </div>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="text-gray-900 font-medium">Email Address Preview:</span>
            <span class="font-mono text-blue-600">{{ emailPreview }}</span>
          </div>
        </div>
        
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input 
            id="password" 
            v-model="accountForm.password" 
            type="password" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="Enter a secure password"
            required
          >
          <p class="text-sm text-gray-600 mt-1">Minimum 8 characters with uppercase, lowercase, and numbers</p>
        </div>
        
        <div>
          <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
          <input 
            id="confirmPassword" 
            v-model="accountForm.confirmPassword" 
            type="password" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="Confirm your password"
            required
          >
        </div>
        
        <div>
          <label for="displayName" class="block text-sm font-medium text-gray-700 mb-2">Display Name (Optional)</label>
          <input 
            id="displayName" 
            v-model="accountForm.displayName" 
            type="text" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="John Doe"
          >
          <p class="text-sm text-gray-600 mt-1">Name that appears in the "From" field when sending emails</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Account Type</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input v-model="accountForm.type" type="radio" value="standard" class="mr-2">
              <span class="text-sm text-gray-900">Standard Account</span>
            </label>
            <label class="flex items-center">
              <input v-model="accountForm.type" type="radio" value="alias" class="mr-2">
              <span class="text-sm text-gray-900">Alias (forwards to another account)</span>
            </label>
            <label class="flex items-center">
              <input v-model="accountForm.type" type="radio" value="distribution" class="mr-2">
              <span class="text-sm text-gray-900">Distribution List</span>
            </label>
          </div>
        </div>
        
        <div v-if="accountForm.type === 'alias'">
          <label for="forwardTo" class="block text-sm font-medium text-gray-700 mb-2">Forward To</label>
          <input 
            id="forwardTo" 
            v-model="accountForm.forwardTo" 
            type="email" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
            placeholder="destination@example.com"
          >
        </div>
        
        <div v-if="accountForm.type === 'distribution'">
          <label class="block text-sm font-medium text-gray-700 mb-2">Distribution List Members</label>
          <div class="space-y-2">
            <div v-for="(member, index) in accountForm.distributionMembers" :key="index" class="flex items-center space-x-2">
              <input 
                v-model="accountForm.distributionMembers[index]" 
                type="email" 
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                placeholder="member@example.com"
              >
              <button type="button" class="text-red-600 hover:text-red-800" @click="removeMember(index)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <button type="button" class="text-blue-600 hover:text-blue-800 text-sm" @click="addMember">
              + Add Member
            </button>
          </div>
        </div>
        
        <div class="space-y-3">
          <label class="flex items-center">
            <input v-model="accountForm.options.enablePlusAliasing" type="checkbox" class="mr-2">
            <span class="text-sm text-gray-900">Enable plus-sign aliasing (e.g., user+tag@domain.com)</span>
          </label>
          <label class="flex items-center">
            <input v-model="accountForm.options.enableAutoResponder" type="checkbox" class="mr-2">
            <span class="text-sm text-gray-900">Enable auto-responder</span>
          </label>
          <label class="flex items-center">
            <input v-model="accountForm.options.enableSpamFilter" type="checkbox" class="mr-2">
            <span class="text-sm text-gray-900">Enable spam filtering</span>
          </label>
        </div>
        
        <div>
          <label for="quotaLimit" class="block text-sm font-medium text-gray-700 mb-2">Storage Quota</label>
          <select id="quotaLimit" v-model="accountForm.quotaLimit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="100">100 MB</option>
            <option value="500">500 MB</option>
            <option value="1000">1 GB</option>
            <option value="5000">5 GB</option>
            <option value="unlimited">Unlimited</option>
          </select>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="font-medium text-blue-900 mb-2">Account Summary</h3>
          <div class="text-sm text-blue-800 space-y-1">
            <p><strong>Email:</strong> {{ emailPreview }}</p>
            <p><strong>Type:</strong> {{ accountForm.type === 'standard' ? 'Standard Account' : accountForm.type === 'alias' ? 'Email Alias' : 'Distribution List' }}</p>
            <p><strong>Storage:</strong> {{ accountForm.quotaLimit === 'unlimited' ? 'Unlimited' : accountForm.quotaLimit + ' MB' }}</p>
            <p><strong>Plus Aliasing:</strong> {{ accountForm.options.enablePlusAliasing ? 'Enabled' : 'Disabled' }}</p>
          </div>
        </div>
        
        <div class="flex justify-end space-x-4">
          <NuxtLink to="/dashboard/accounts" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
            Cancel
          </NuxtLink>
          <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
            Create Account
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Create Email Account - Dashboard',
  description: 'Create a new email account or alias'
})

// Reactive form data
const accountForm = ref({
  username: '',
  domain: '',
  password: '',
  confirmPassword: '',
  displayName: '',
  type: 'standard',
  forwardTo: '',
  distributionMembers: [''],
  options: {
    enablePlusAliasing: true,
    enableAutoResponder: false,
    enableSpamFilter: true
  },
  quotaLimit: '1000'
})

// Computed email preview
const emailPreview = computed(() => {
  if (accountForm.value.username && accountForm.value.domain) {
    return `${accountForm.value.username}@${accountForm.value.domain}`
  }
  return 'username@domain.com'
})

// Methods
const addMember = () => {
  accountForm.value.distributionMembers.push('')
}

const removeMember = (index: number) => {
  if (accountForm.value.distributionMembers.length > 1) {
    accountForm.value.distributionMembers.splice(index, 1)
  }
}

const submitForm = async () => {
  try {
    // TODO: Implement account creation logic
    console.log('Creating account:', accountForm.value)
    // Redirect to accounts list after creation
    await navigateTo('/dashboard/accounts')
  } catch (error) {
    console.error('Error creating account:', error)
  }
}
</script>