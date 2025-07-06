/**
 * Authentication Middleware
 * Protects routes that require authentication
 */

export default defineNuxtRouteMiddleware((to, _from) => {
  const auth = useAuth()
  
  // If not authenticated, redirect to login with return URL
  if (!auth.isAuthenticated.value) {
    return navigateTo({
      path: '/login',
      query: { redirect: to.fullPath }
    })
  }
  
  // If user is not loaded yet, wait for it
  if (!auth.user.value) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Authentication required'
    })
  }
})
