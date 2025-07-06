/**
 * Authentication Plugin
 * Initializes authentication state and provides global auth management
 */

export default defineNuxtPlugin(() => {
  // Simple initialization without complex async operations
  if (process.dev) {
    console.log('🔐 Authentication plugin initialized')
  }

  // The auth composable will be available throughout the app
  // Router guards and session validation will be handled in middleware
})