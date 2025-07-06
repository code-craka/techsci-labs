<template>
  <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between mb-8">
      <div class="min-w-0 flex-1">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Dashboard
        </h1>
        <p class="mt-1 text-lg text-gray-600 dark:text-gray-400">
          Welcome back, {{ auth.getDisplayName() }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-500">
          Last login: {{ formatDate(auth.user.value?.lastLoginAt) }}
        </p>
      </div>
      <div class="mt-4 flex space-x-3 md:ml-4 md:mt-0">
        <button
          @click="goToEmails"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium"
        >
          <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          View Emails
        </button>
        <button
          @click="refreshStats"
          :disabled="isLoading"
          class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium disabled:opacity-50"
        >
          <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <!-- Total Emails -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Emails</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalEmails.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <!-- Unread Emails -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5.5-5.5m7.5 5.5v-13a2 2 0 00-2-2h-9a2 2 0 00-2 2v1m-4 12h4m-4 0v-8a6 6 0 0112 0v8m-12 0H6a2 2 0 00-2-2v2a2 2 0 002 2h1z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unread</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.unreadEmails.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <!-- Storage Used -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Storage Used</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatBytes(auth.user.value?.quotaUsed || 0) }}</p>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
              <div 
                class="bg-purple-600 h-2 rounded-full transition-all duration-300" 
                :style="{ width: `${storagePercentage}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Domains -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9-9a9 9 0 00-9 9m0 0l5.657 5.657M12 3l5.657 5.657" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Domains</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.activeDomains.toLocaleString() }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="mb-8">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div v-if="recentActivity.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No recent activity</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start by sending or receiving emails to see activity here.</p>
        </div>
        <div v-else class="space-y-4">
          <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <div :class="[
                'w-8 h-8 rounded-full flex items-center justify-center',
                activity.type === 'email' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'
              ]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="activity.type === 'email'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.title }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ activity.description }}</p>
            </div>
            <div class="flex-shrink-0 text-sm text-gray-500 dark:text-gray-400">
              {{ formatDate(activity.timestamp) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Create Account -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Create Account</h3>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Set up a new email account for testing.</p>
          <button
            @click="goToNewAccount"
            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium"
          >
            Create Account
          </button>
        </div>

        <!-- Add Domain -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9-9a9 9 0 00-9 9m0 0l5.657 5.657M12 3l5.657 5.657" />
              </svg>
            </div>
            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Add Domain</h3>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Configure a new domain for email testing.</p>
          <button
            @click="goToNewDomain"
            class="w-full border border-gray-300 text-gray-700 dark:text-gray-300 dark:border-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium"
          >
            Add Domain
          </button>
        </div>

        <!-- View Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Settings</h3>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Manage your account and preferences.</p>
          <button
            @click="goToSettings"
            class="w-full border border-gray-300 text-gray-700 dark:text-gray-300 dark:border-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium"
          >
            View Settings
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Authentication required
definePageMeta({
  middleware: 'auth',
  layout: 'dashboard'
})

// Composables
const auth = useAuth()
const { getDomainStats } = useDomain()
const { getAccountStats } = useAccount()

// State
const isLoading = ref(false)
const stats = ref({
  totalEmails: 0,
  unreadEmails: 0,
  activeDomains: 0,
  totalAccounts: 0
})

const recentActivity = ref([
  {
    id: 1,
    type: 'email',
    title: 'New email received',
    description: 'From test@example.com to user@domain.com',
    timestamp: new Date().toISOString()
  },
  {
    id: 2,
    type: 'account',
    title: 'Account created',
    description: 'New email account setup@test.dev',
    timestamp: new Date(Date.now() - 3600000).toISOString()
  }
])

// Computed
const storagePercentage = computed(() => {
  const used = auth.user.value?.quotaUsed || 0
  const limit = auth.user.value?.quota || 1000000000 // 1GB default
  return Math.min((used / limit) * 100, 100)
})

// Methods
const refreshStats = async () => {
  isLoading.value = true
  try {
    // Load stats from multiple sources
    const [domainStats, accountStats] = await Promise.allSettled([
      getDomainStats(),
      getAccountStats()
    ])

    // Update stats based on API responses
    if (domainStats.status === 'fulfilled') {
      stats.value.activeDomains = domainStats.value.verifiedDomains || 0
    }

    if (accountStats.status === 'fulfilled') {
      stats.value.totalAccounts = accountStats.value.totalAccounts || 0
      stats.value.totalEmails = accountStats.value.totalEmails || 0
      stats.value.unreadEmails = accountStats.value.unreadEmails || 0
    }
  } catch (error) {
    console.error('Failed to refresh stats:', error)
  } finally {
    isLoading.value = false
  }
}

// Navigation methods
const goToEmails = () => navigateTo('/dashboard/emails')
const goToNewAccount = () => navigateTo('/dashboard/accounts/new')
const goToNewDomain = () => navigateTo('/dashboard/domains/new')
const goToSettings = () => navigateTo('/dashboard/settings')

// Utility functions
const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString?: string): string => {
  if (!dateString) return 'Never'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    if (diffHours === 0) {
      const diffMinutes = Math.floor(diffMs / (1000 * 60))
      return diffMinutes <= 1 ? 'Just now' : `${diffMinutes} minutes ago`
    }
    return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`
  } else if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

// Load initial data
onMounted(() => {
  refreshStats()
})

// SEO
useHead({
  title: 'Dashboard - TechSci Labs Email Testing Platform'
})

useSeoMeta({
  title: 'Dashboard',
  description: 'Email testing platform dashboard with statistics and quick actions'
})
</script>