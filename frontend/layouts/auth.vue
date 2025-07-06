<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-gradient-to-br from-primary-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 opacity-50" />
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10" />
    
    <!-- Main Content -->
    <div class="relative z-10">
      <slot />
    </div>

    <!-- Footer -->
    <footer class="relative z-10 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center space-y-4">
          <!-- Logo -->
          <div class="flex items-center space-x-2">
            <img 
              src="/logo.svg" 
              alt="TechSci Labs" 
              class="h-8 w-auto"
            >
            <span class="text-lg font-semibold text-gray-900 dark:text-white">
              TechSci Labs
            </span>
          </div>

          <!-- Navigation Links -->
          <nav class="flex flex-wrap justify-center space-x-6 text-sm">
            <NuxtLink 
              to="/about" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            >
              About
            </NuxtLink>
            <NuxtLink 
              to="/features" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            >
              Features
            </NuxtLink>
            <NuxtLink 
              to="/pricing" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            >
              Pricing
            </NuxtLink>
            <NuxtLink 
              to="/docs" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            >
              Documentation
            </NuxtLink>
            <NuxtLink 
              to="/support" 
              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            >
              Support
            </NuxtLink>
          </nav>

          <!-- Legal Links -->
          <div class="flex flex-wrap justify-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
            <NuxtLink 
              to="/terms" 
              class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            >
              Terms of Service
            </NuxtLink>
            <span>"</span>
            <NuxtLink 
              to="/privacy" 
              class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            >
              Privacy Policy
            </NuxtLink>
            <span>"</span>
            <NuxtLink 
              to="/cookies" 
              class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            >
              Cookie Policy
            </NuxtLink>
          </div>

          <!-- Copyright -->
          <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
            � {{ currentYear }} TechSci Labs. All rights reserved.
          </p>

          <!-- Social Links -->
          <div class="flex space-x-4">
            <a 
              href="https://twitter.com/techscilabs" 
              target="_blank" 
              rel="noopener noreferrer"
              class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
              aria-label="Follow us on Twitter"
            >
              <Icon name="mdi:twitter" class="w-5 h-5" />
            </a>
            <a 
              href="https://github.com/techsci-labs" 
              target="_blank" 
              rel="noopener noreferrer"
              class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
              aria-label="View our GitHub"
            >
              <Icon name="mdi:github" class="w-5 h-5" />
            </a>
            <a 
              href="https://linkedin.com/company/techsci-labs" 
              target="_blank" 
              rel="noopener noreferrer"
              class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
              aria-label="Connect on LinkedIn"
            >
              <Icon name="mdi:linkedin" class="w-5 h-5" />
            </a>
            <a 
              href="mailto:contact@techsci.dev"
              class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
              aria-label="Contact us"
            >
              <Icon name="mdi:email" class="w-5 h-5" />
            </a>
          </div>
        </div>
      </div>
    </footer>

    <!-- Color Mode Toggle -->
    <div class="fixed top-4 right-4 z-20">
      <UButton
        variant="ghost"
        color="gray"
        size="sm"
        square
        @click="toggleColorMode"
        :icon="colorMode.value === 'dark' ? 'i-heroicons-sun' : 'i-heroicons-moon'"
        aria-label="Toggle color mode"
      />
    </div>

    <!-- Development Notice (only in development) -->
    <div 
      v-if="isDevelopment" 
      class="fixed bottom-4 left-4 z-20 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-3 py-2 rounded-lg text-sm font-medium"
    >
      Development Mode
    </div>
  </div>
</template>

<script setup lang="ts">
// Color mode composable
const colorMode = useColorMode()

// Environment check
const config = useRuntimeConfig()
const isDevelopment = computed(() => config.public.appEnv === 'development')

// Current year for copyright
const currentYear = computed(() => new Date().getFullYear())

// Toggle color mode
function toggleColorMode() {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}

// SEO and meta tags for auth pages
useHead({
  bodyAttrs: {
    class: 'auth-layout'
  }
})

useSeoMeta({
  robots: 'noindex, nofollow' // Don't index auth pages
})
</script>

<style scoped>
/* Additional auth layout specific styles */
.auth-layout {
  /* Custom styles for auth layout if needed */
}
</style>