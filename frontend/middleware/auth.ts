export default defineNuxtRouteMiddleware((to, from) => {
  const { user, isAuthenticated } = useAuth()
  
  // If not authenticated, redirect to login with return URL
  if (!isAuthenticated.value) {
    return navigateTo({
      path: '/login',
      query: { redirect: to.fullPath }
    })
  }
  
  // If user is not loaded yet, wait for it
  if (!user.value) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Authentication required'
    })
  }
})

function defineNuxtRouteMiddleware(arg0: (to: any, from: any) => any) {
    throw new Error("Function not implemented.")
}


function useAuth(): { user: any; isAuthenticated: any } {
    throw new Error("Function not implemented.")
}


function createError(arg0: { statusCode: number; statusMessage: string }) {
    throw new Error("Function not implemented.")
}
