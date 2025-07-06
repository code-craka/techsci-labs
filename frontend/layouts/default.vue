<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <!-- Logo -->
          <div class="flex items-center">
            <NuxtLink to="/" class="flex items-center space-x-2">
              <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <span class="text-xl font-bold text-gray-900 dark:text-white">TechSci Labs</span>
            </NuxtLink>
          </div>

          <!-- Navigation -->
          <nav class="hidden md:flex items-center space-x-8">
            <NuxtLink 
              to="/features" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
            >
              Features
            </NuxtLink>
            <NuxtLink 
              to="/pricing" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
            >
              Pricing
            </NuxtLink>
            <NuxtLink 
              to="/docs" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
            >
              Documentation
            </NuxtLink>
            <NuxtLink 
              to="/about" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
            >
              About
            </NuxtLink>
          </nav>

          <!-- Actions -->
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

            <!-- Auth buttons -->
            <div v-if="!auth.isAuthenticated.value" class="flex items-center space-x-4">
              <NuxtLink 
                to="/login" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
              >
                Sign In
              </NuxtLink>
              <NuxtLink 
                to="/register" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium transition-colors"
              >
                Get Started
              </NuxtLink>
            </div>

            <!-- User menu -->
            <div v-else class="relative" ref="userMenuRef">
              <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
              >
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                  {{ auth.getInitials() }}
                </div>
                <span class="hidden sm:block font-medium">{{ auth.getDisplayName() }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown menu -->
              <div
                v-if="userMenuOpen"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
              >
                <NuxtLink 
                  to="/dashboard" 
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="userMenuOpen = false"
                >
                  Dashboard
                </NuxtLink>
                <NuxtLink 
                  to="/dashboard/settings/profile" 
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="userMenuOpen = false"
                >
                  Profile Settings
                </NuxtLink>
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                <button
                  @click="handleLogout"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  Sign Out
                </button>
              </div>
            </div>

            <!-- Mobile menu button -->
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mobile menu -->
        <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 dark:border-gray-700 py-4">
          <div class="space-y-3">
            <NuxtLink 
              to="/features" 
              class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
              @click="mobileMenuOpen = false"
            >
              Features
            </NuxtLink>
            <NuxtLink 
              to="/pricing" 
              class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
              @click="mobileMenuOpen = false"
            >
              Pricing
            </NuxtLink>
            <NuxtLink 
              to="/docs" 
              class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
              @click="mobileMenuOpen = false"
            >
              Documentation
            </NuxtLink>
            <NuxtLink 
              to="/about" 
              class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
              @click="mobileMenuOpen = false"
            >
              About
            </NuxtLink>
            
            <div v-if="!auth.isAuthenticated.value" class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-3">
              <NuxtLink 
                to="/login" 
                class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium"
                @click="mobileMenuOpen = false"
              >
                Sign In
              </NuxtLink>
              <NuxtLink 
                to="/register" 
                class="block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium transition-colors text-center"
                @click="mobileMenuOpen = false"
              >
                Get Started
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Company info -->
          <div class="space-y-4">
            <div class="flex items-center space-x-2">
              <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <span class="text-xl font-bold text-gray-900 dark:text-white">TechSci Labs</span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
              Professional email testing platform for developers and QA teams.
            </p>
          </div>

          <!-- Product links -->
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Product</h3>
            <div class="space-y-2">
              <NuxtLink to="/features" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Features
              </NuxtLink>
              <NuxtLink to="/pricing" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Pricing
              </NuxtLink>
              <NuxtLink to="/integrations" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Integrations
              </NuxtLink>
            </div>
          </div>

          <!-- Support links -->
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Support</h3>
            <div class="space-y-2">
              <NuxtLink to="/docs" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Documentation
              </NuxtLink>
              <NuxtLink to="/help" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Help Center
              </NuxtLink>
              <NuxtLink to="/contact" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Contact Us
              </NuxtLink>
            </div>
          </div>

          <!-- Company links -->
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Company</h3>
            <div class="space-y-2">
              <NuxtLink to="/about" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                About Us
              </NuxtLink>
              <NuxtLink to="/blog" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Blog
              </NuxtLink>
              <NuxtLink to="/privacy" class="block text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm">
                Privacy Policy
              </NuxtLink>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 flex flex-col sm:flex-row justify-between items-center">
          <p class="text-gray-600 dark:text-gray-400 text-sm">
            © {{ new Date().getFullYear() }} TechSci Labs. All rights reserved.
          </p>
          <div class="flex space-x-6 mt-4 sm:mt-0">
            <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
              <span class="sr-only">Twitter</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84" />
              </svg>
            </a>
            <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
              <span class="sr-only">GitHub</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd" />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
const auth = useAuth()
const colorMode = useColorMode()

// Menu state
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref<HTMLElement>()

// Actions
const handleLogout = async () => {
  await auth.logout()
  userMenuOpen.value = false
  await navigateTo('/')
}

const toggleDarkMode = () => {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}

// Close user menu when clicking outside
onClickOutside(userMenuRef, () => {
  userMenuOpen.value = false
})

// Close mobile menu when route changes
const route = useRoute()
watch(() => route.path, () => {
  mobileMenuOpen.value = false
  userMenuOpen.value = false
})
</script>