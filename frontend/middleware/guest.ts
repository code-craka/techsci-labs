/**
 * Guest Middleware
 * Redirects authenticated users away from guest-only pages
 */

export default defineNuxtRouteMiddleware(() => {
  const auth = useAuth()
  
  // If authenticated, redirect to dashboard
  if (auth.isAuthenticated.value) {
    return navigateTo('/dashboard')
  }
})