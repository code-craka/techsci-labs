import { useAuth } from "../composables/useAuth"

export default defineNuxtRouteMiddleware((to) => {
  const { user, isAuthenticated, canAccess } = useAuth()
  
  // First check if authenticated
  if (!isAuthenticated.value) {
    return navigateTo({
      path: '/login',
      query: { redirect: to.fullPath }
    })
  }
  
  // Check if user has admin access
  if (!canAccess('admin')) {
    throw createError({
      statusCode: 403,
      statusMessage: 'Access denied - Admin permission required'
    })
  }
  
  // Additional check for user object
  if (!user.value) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Authentication required'
    })
  }
})