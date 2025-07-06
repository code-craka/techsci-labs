export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated } = useAuth()
  
  // If authenticated, redirect to dashboard
  if (isAuthenticated.value) {
    return navigateTo('/dashboard')
  }
})