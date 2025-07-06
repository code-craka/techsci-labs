<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
      <NuxtLink to="/dashboard/domains" class="text-gray-600 hover:text-gray-800 mr-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </NuxtLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ domain.name }}</h1>
        <p class="text-sm text-gray-600 mt-1">Domain Management</p>
      </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Domain Status -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Domain Status</h2>
            <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">{{ domain.status }}</span>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
              <div class="text-2xl font-bold text-gray-900">{{ domain.emailCount }}</div>
              <div class="text-sm text-gray-600">Email Accounts</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
              <div class="text-2xl font-bold text-gray-900">{{ domain.messagesCount }}</div>
              <div class="text-sm text-gray-600">Messages</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
              <div class="text-2xl font-bold text-gray-900">{{ domain.storageUsed }}</div>
              <div class="text-sm text-gray-600">Storage Used</div>
            </div>
          </div>
        </div>
        
        <!-- DNS Configuration -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <h2 class="text-xl font-semibold mb-4">DNS Configuration</h2>
          
          <div class="space-y-4">
            <div class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-medium text-gray-900">MX Record</h3>
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Verified</span>
              </div>
              <div class="bg-gray-50 p-3 rounded font-mono text-sm">
                <div>Priority: 10</div>
                <div>Value: mail.techsci-labs.com</div>
              </div>
            </div>
            
            <div class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-medium text-gray-900">SPF Record</h3>
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Verified</span>
              </div>
              <div class="bg-gray-50 p-3 rounded font-mono text-sm">
                <div>Type: TXT</div>
                <div>Value: v=spf1 include:_spf.techsci-labs.com ~all</div>
              </div>
            </div>
            
            <div class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-medium text-gray-900">DKIM Record</h3>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
              </div>
              <div class="bg-gray-50 p-3 rounded font-mono text-sm">
                <div>Selector: default._domainkey</div>
                <div>Value: v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA...</div>
              </div>
            </div>
            
            <div class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-medium text-gray-900">DMARC Record</h3>
                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Missing</span>
              </div>
              <div class="bg-gray-50 p-3 rounded font-mono text-sm">
                <div>Type: TXT</div>
                <div>Name: _dmarc.{{ domain.name }}</div>
                <div>Value: v=DMARC1; p=quarantine; rua=mailto:dmarc@techsci-labs.com</div>
              </div>
            </div>
          </div>
          
          <div class="mt-4 flex space-x-4">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
              Verify DNS
            </button>
            <button class="px-4 py-2 text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors">
              Download Guide
            </button>
          </div>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Domain Info -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4">Domain Information</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Added:</span>
              <span class="font-medium">{{ domain.createdAt }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Type:</span>
              <span class="font-medium">{{ domain.type }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Catch-all:</span>
              <span class="font-medium">{{ domain.catchAll ? 'Enabled' : 'Disabled' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Last Check:</span>
              <span class="font-medium">{{ domain.lastCheck }}</span>
            </div>
          </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4">Quick Actions</h3>
          <div class="space-y-3">
            <NuxtLink to="/dashboard/accounts/new" class="block w-full px-4 py-2 text-center text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors">
              Add Email Account
            </NuxtLink>
            <button class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
              Test Email Delivery
            </button>
            <button class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
              View Logs
            </button>
          </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4 text-red-600">Danger Zone</h3>
          <div class="space-y-3">
            <button class="w-full px-4 py-2 text-center text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors">
              Reset DNS
            </button>
            <button class="w-full px-4 py-2 text-center text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors">
              Delete Domain
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Domain Details - Dashboard',
  description: 'Manage domain settings and DNS configuration'
})

// Get domain ID from route
const route = useRoute()
const domainId = route.params.id

// Mock domain data - TODO: Replace with API call
const domain = ref({
  id: domainId,
  name: 'example.com',
  status: 'Verified',
  type: 'Production',
  catchAll: true,
  emailCount: 47,
  messagesCount: 1234,
  storageUsed: '2.3 GB',
  createdAt: 'Dec 1, 2024',
  lastCheck: '2 mins ago'
})

// Load domain data
onMounted(async () => {
  try {
    // TODO: Fetch domain data from API
    // const response = await $fetch(`/api/domains/${domainId}`)
    // domain.value = response
  } catch (error) {
    console.error('Error loading domain:', error)
  }
})
</script>