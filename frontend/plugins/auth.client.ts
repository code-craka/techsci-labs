/**
 * Authentication Plugin
 * Initializes auth state on client-side app startup
 */

export default defineNuxtPlugin(async () => {
  const { provideAuth } = await import('~/composables/useAuth')
  
  // Initialize auth state
  const auth = provideAuth()
  
  // Log authentication status (development only)
  if (process.dev) {
    console.log('Auth initialized:', {
      isAuthenticated: auth.isAuthenticated.value,
      user: auth.user.value?.email || null
    })
  }
  
  return {
    provide: {
      auth
    }
  }
})