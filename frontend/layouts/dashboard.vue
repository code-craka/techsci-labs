<template>
  <div class="flex h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Sidebar -->
    <div 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center justify-center h-16 px-4 border-b border-gray-200 dark:border-gray-700">
        <NuxtLink to="/dashboard" class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <span class="text-xl font-bold text-gray-900 dark:text-white">TechSci Labs</span>
        </NuxtLink>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 py-6 space-y-2">
        <!-- Dashboard -->
        <NuxtLink 
          to="/dashboard"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="isActiveRoute('/dashboard') ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v2zm0 0V5a2 2 0 012-2h6a2 2 0 012 2v2H3z" />
          </svg>
          Dashboard
        </NuxtLink>

        <!-- Emails -->
        <NuxtLink 
          to="/dashboard/emails"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="isActiveRoute('/dashboard/emails') ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          Emails
          <span v-if="unreadCount > 0" class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ unreadCount }}</span>
        </NuxtLink>

        <!-- Accounts -->
        <NuxtLink 
          to="/dashboard/accounts"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="isActiveRoute('/dashboard/accounts') ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
          </svg>
          Accounts
        </NuxtLink>

        <!-- Domains -->
        <NuxtLink 
          to="/dashboard/domains"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="isActiveRoute('/dashboard/domains') ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9-9a9 9 0 00-9 9m0 0l5.657 5.657M12 3l5.657 5.657" />
          </svg>
          Domains
        </NuxtLink>

        <!-- API Keys -->
        <NuxtLink 
          to="/dashboard/api-keys"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="isActiveRoute('/dashboard/api-keys') ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
          API Keys
        </NuxtLink>

        <!-- Divider -->
        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

        <!-- Settings -->
        <div class="space-y-2">
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Settings
          </div>
          
          <NuxtLink 
            to="/dashboard/settings"
            class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
            :class="isActiveRoute('/dashboard/settings') ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
          </NuxtLink>
        </div>
      </nav>

      <!-- User Profile -->
      <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
            {{ auth.getInitials() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ auth.getDisplayName() }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ auth.user.value?.email }}
            </p>
          </div>
          <button
            @click="handleLogout"
            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            title="Logout"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Sidebar overlay (mobile) -->
    <div 
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
    ></div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
      <!-- Top header -->
      <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between px-4 py-4">
          <!-- Mobile menu button -->
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Page title -->
          <div class="flex-1 lg:flex lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ pageTitle }}
              </h1>
              <div v-if="breadcrumbs.length > 0" class="hidden sm:flex items-center space-x-2 text-sm">
                <span class="text-gray-400">/</span>
                <span v-for="(crumb, index) in breadcrumbs" :key="index" class="text-gray-500 dark:text-gray-400">
                  {{ crumb }}
                  <span v-if="index < breadcrumbs.length - 1" class="ml-2 text-gray-400">/</span>
                </span>
              </div>
            </div>

            <!-- Right side actions -->
            <div class="flex items-center space-x-4">
              <!-- Dark mode toggle -->
              <button
                @click="toggleDarkMode"
                class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                title="Toggle dark mode"
              >
                <svg v-if="colorMode.value === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
              </button>

              <!-- Notifications -->
              <button class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5.5-5.5m7.5 5.5v-13a2 2 0 00-2-2h-9a2 2 0 00-2 2v1m-4 12h4m-4 0v-8a6 6 0 0112 0v8m-12 0H6a2 2 0 00-2-2v2a2 2 0 002 2h1z" />
                </svg>
                <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                  {{ notificationCount > 9 ? '9+' : notificationCount }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
const auth = useAuth()
const colorMode = useColorMode()
const route = useRoute()

// Sidebar state
const sidebarOpen = ref(false)

// Mock notification counts - in real app these would come from API/store
const unreadCount = ref(3)
const notificationCount = ref(2)

// Page title and breadcrumbs
const pageTitle = computed(() => {
  const routeName = route.name as string
  const titles: Record<string, string> = {
    'dashboard': 'Dashboard',
    'dashboard-emails': 'Emails',
    'dashboard-emails-id': 'Email Details',
    'dashboard-accounts': 'Accounts',
    'dashboard-accounts-id': 'Account Details',
    'dashboard-accounts-new': 'Create Account',
    'dashboard-domains': 'Domains',
    'dashboard-domains-id': 'Domain Details',
    'dashboard-domains-new': 'Add Domain',
    'dashboard-api-keys': 'API Keys',
    'dashboard-api-keys-new': 'Create API Key',
    'dashboard-settings': 'Settings',
    'dashboard-settings-profile': 'Profile Settings',
    'dashboard-settings-billing': 'Billing',
    'dashboard-settings-team': 'Team Settings'
  }
  return titles[routeName] || 'Dashboard'
})

const breadcrumbs = computed(() => {
  const path = route.path
  const segments = path.split('/').filter(Boolean)
  
  if (segments.length <= 2) return []
  
  return segments.slice(2).map(segment => {
    return segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' ')
  })
})

// Navigation helpers
const isActiveRoute = (routePath: string): boolean => {
  if (routePath === '/dashboard') {
    return route.path === '/dashboard'
  }
  return route.path.startsWith(routePath)
}

// Actions
const handleLogout = async () => {
  await auth.logout()
  await navigateTo('/login')
}

const toggleDarkMode = () => {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}

// Close sidebar when route changes (mobile)
watch(() => route.path, () => {
  sidebarOpen.value = false
})

// Ensure user is authenticated
onMounted(() => {
  if (!auth.isAuthenticated.value) {
    navigateTo('/login')
  }
})
</script>