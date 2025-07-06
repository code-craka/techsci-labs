<template>
  <div class="dashboard-home">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
            Dashboard
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Welcome back, {{ auth.getDisplayName() }}
          </p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
          <UButton 
            icon="i-heroicons-plus"
            color="primary"
            @click="router.push('/dashboard/emails')"
          >
            View Emails
          </UButton>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="mt-8">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Total Emails -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <Icon 
                    name="i-heroicons-envelope"
                    class="h-6 w-6 text-gray-400"
                  />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Emails
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.totalEmails }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Unread Emails -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <Icon 
                    name="i-heroicons-bell"
                    class="h-6 w-6 text-blue-500"
                  />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Unread
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.unreadEmails }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Storage Used -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <Icon 
                    name="i-heroicons-circle-stack"
                    class="h-6 w-6 text-yellow-500"
                  />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Storage Used
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ formatBytes(auth.user.value?.quotaUsed || 0) }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Domains -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <Icon 
                    name="i-heroicons-globe-alt"
                    class="h-6 w-6 text-green-500"
                  />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Active Domains
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.activeDomains }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Recent Activity
        </h3>
        <div class="mt-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
          <div class="p-6">
            <div class="text-center text-gray-500 dark:text-gray-400">
              <Icon 
                name="i-heroicons-inbox"
                class="mx-auto h-12 w-12 text-gray-400"
              />
              <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No recent activity
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Start by sending or receiving emails to see activity here.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Quick Actions
        </h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <UCard>
            <template #header>
              <div class="flex items-center">
                <Icon 
                  name="i-heroicons-envelope-open"
                  class="h-5 w-5 text-blue-500 mr-2"
                />
                <h4 class="text-sm font-medium">Compose Email</h4>
              </div>
            </template>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Send a test email to verify your setup.
            </p>
            <template #footer>
              <UButton 
                size="sm" 
                color="blue"
                @click="router.push('/dashboard/emails/compose')"
              >
                Compose
              </UButton>
            </template>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center">
                <Icon 
                  name="i-heroicons-cog-6-tooth"
                  class="h-5 w-5 text-gray-500 mr-2"
                />
                <h4 class="text-sm font-medium">Account Settings</h4>
              </div>
            </template>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Manage your account and preferences.
            </p>
            <template #footer>
              <UButton 
                size="sm" 
                variant="outline"
                @click="router.push('/dashboard/settings')"
              >
                Settings
              </UButton>
            </template>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center">
                <Icon 
                  name="i-heroicons-chart-bar"
                  class="h-5 w-5 text-green-500 mr-2"
                />
                <h4 class="text-sm font-medium">View Reports</h4>
              </div>
            </template>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Analyze your email testing metrics.
            </p>
            <template #footer>
              <UButton 
                size="sm" 
                variant="outline"
                @click="router.push('/dashboard/analytics')"
              >
                View Reports
              </UButton>
            </template>
          </UCard>
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
const router = useRouter()

// Stats data (mock for now - will be replaced with real API calls)
const stats = ref({
  totalEmails: 0,
  unreadEmails: 0,
  activeDomains: 1
})

// Utility function to format bytes
function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// SEO
useHead({
  title: 'Dashboard - TechSci Labs Email Testing Platform'
})

useSeoMeta({
  title: 'Dashboard',
  description: 'Email testing platform dashboard with statistics and quick actions'
})
</script>