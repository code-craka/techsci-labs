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
            <button 
              @click="handleVerifyDns"
              :disabled="isLoading"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              <span v-if="isLoading">Verifying...</span>
              <span v-else>Verify DNS</span>
            </button>
            <button 
              @click="handleDownloadGuide"
              class="px-4 py-2 text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors"
            >
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
            <button 
              @click="handleTestDelivery"
              class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            >
              Test Email Delivery
            </button>
            <button 
              @click="handleViewLogs"
              class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            >
              View Logs
            </button>
          </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4 text-red-600">Danger Zone</h3>
          <div class="space-y-3">
            <button 
              @click="handleResetDns"
              class="w-full px-4 py-2 text-center text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors"
            >
              Reset DNS
            </button>
            <button 
              @click="handleDeleteDomain"
              class="w-full px-4 py-2 text-center text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors"
            >
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
const domainId = route.params.id as string

const { 
  currentDomain: domain, 
  dnsRecords,
  isLoading, 
  error,
  getDomain,
  getDnsRecords,
  verifyDomain,
  generateDnsGuide,
  testEmailDelivery,
  getDomainLogs,
  resetDomainDns,
  deleteDomain
} = useDomain()

// Load domain data
onMounted(async () => {
  try {
    await getDomain(domainId)
    await getDnsRecords(domainId)
  } catch (err) {
    console.error('Error loading domain:', err)
  }
})

// Action handlers
const handleVerifyDns = async () => {
  try {
    const result = await verifyDomain(domainId)
    
    const toast = useToast()
    if (result.success) {
      toast.add({
        title: 'DNS Verification Successful',
        description: 'All DNS records are configured correctly.',
        color: 'green'
      })
    } else {
      toast.add({
        title: 'DNS Verification Issues',
        description: result.issues?.join(', ') || 'Some DNS records need attention.',
        color: 'yellow'
      })
    }
  } catch (err) {
    const toast = useToast()
    toast.add({
      title: 'Verification Failed',
      description: error.value || 'Failed to verify DNS configuration.',
      color: 'red'
    })
  }
}

const handleDownloadGuide = async () => {
  try {
    const guide = await generateDnsGuide(domainId)
    
    // Create and download file
    const blob = new Blob([guide], { type: 'text/plain' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${domain.value?.name || 'domain'}-dns-guide.txt`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (err) {
    const toast = useToast()
    toast.add({
      title: 'Download Failed',
      description: 'Failed to generate DNS configuration guide.',
      color: 'red'
    })
  }
}

const handleTestDelivery = async () => {
  const testEmail = prompt('Enter test email address:')
  if (!testEmail) return

  try {
    const result = await testEmailDelivery(domainId, testEmail)
    
    const toast = useToast()
    toast.add({
      title: result.success ? 'Test Successful' : 'Test Failed',
      description: result.message,
      color: result.success ? 'green' : 'red'
    })
  } catch (err) {
    const toast = useToast()
    toast.add({
      title: 'Test Failed',
      description: error.value || 'Failed to test email delivery.',
      color: 'red'
    })
  }
}

const handleViewLogs = async () => {
  try {
    const logs = await getDomainLogs(domainId)
    
    // For now, just show count in toast - in real app would open modal/page
    const toast = useToast()
    toast.add({
      title: 'Domain Logs',
      description: `Found ${logs.length} log entries. Check console for details.`,
      color: 'blue'
    })
    
    console.log('Domain logs:', logs)
  } catch (err) {
    const toast = useToast()
    toast.add({
      title: 'Failed to Load Logs',
      description: error.value || 'Could not retrieve domain logs.',
      color: 'red'
    })
  }
}

const handleResetDns = async () => {
  if (!confirm('Are you sure you want to reset DNS configuration? This will regenerate all DNS records.')) {
    return
  }

  try {
    await resetDomainDns(domainId)
    
    const toast = useToast()
    toast.add({
      title: 'DNS Reset',
      description: 'DNS configuration has been reset successfully.',
      color: 'green'
    })
  } catch (err) {
    const toast = useToast()
    toast.add({
      title: 'Reset Failed',
      description: error.value || 'Failed to reset DNS configuration.',
      color: 'red'
    })
  }
}

const handleDeleteDomain = async () => {
  if (!confirm(`Are you sure you want to delete the domain "${domain.value?.name}"? This action cannot be undone.`)) {
    return
  }

  try {
    await deleteDomain(domainId)
    
    const toast = useToast()
    toast.add({
      title: 'Domain Deleted',
      description: 'Domain has been deleted successfully.',
      color: 'green'
    })
    
    // Redirect to domains list
    await navigateTo('/dashboard/domains')
  } catch (err) {
    const toast = useToast()
    toast.add({
      title: 'Delete Failed',
      description: error.value || 'Failed to delete domain.',
      color: 'red'
    })
  }
}
</script>