/**
 * Authentication Composable for TechSci Labs Email Testing Platform
 * Provides authentication state management and methods
 */

import { ref, computed, watch, type Ref } from 'vue'
import { apiClient, isApiError, getErrorMessage } from '~/utils/api'
import type { 
  LoginRequest, 
  LoginResponse, 
  EmailAccount,
  RefreshTokenRequest 
} from '~/types/api'

// User state interface
export interface AuthUser {
  id: string
  email: string
  displayName?: string
  domain: string
  isActive: boolean
  quota: number
  quotaUsed: number
  lastLoginAt?: string
  createdAt: string
}

// Auth state interface
export interface AuthState {
  user: AuthUser | null
  token: string | null
  refreshToken: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
}

// Global auth state (singleton pattern)
let authState: Ref<AuthState> | null = null

/**
 * Convert EmailAccount to AuthUser
 */
function emailAccountToAuthUser(account: EmailAccount): AuthUser {
  return {
    id: account.id,
    email: account.email,
    displayName: account.displayName,
    domain: typeof account.domain === 'string' ? account.domain : account.domain.name,
    isActive: account.isActive,
    quota: account.quota,
    quotaUsed: account.quotaUsed,
    lastLoginAt: account.lastLoginAt,
    createdAt: account.createdAt
  }
}

/**
 * Initialize auth state from localStorage
 */
function initializeAuthState(): AuthState {
  const state: AuthState = {
    user: null,
    token: null,
    refreshToken: null,
    isAuthenticated: false,
    isLoading: false,
    error: null
  }

  // Only access localStorage on client side
  if (typeof window !== 'undefined') {
    try {
      // Load tokens from localStorage
      const token = localStorage.getItem('api_token')
      const refreshToken = localStorage.getItem('refresh_token')
      const userJson = localStorage.getItem('auth_user')

      if (token && refreshToken && userJson) {
        const user = JSON.parse(userJson) as AuthUser
        state.token = token
        state.refreshToken = refreshToken
        state.user = user
        state.isAuthenticated = true
      }
    } catch (error) {
      console.warn('Failed to load auth state from localStorage:', error)
      // Clear potentially corrupted data
      if (typeof window !== 'undefined') {
        localStorage.removeItem('api_token')
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('auth_user')
      }
    }
  }

  return state
}

/**
 * Persist auth state to localStorage
 */
function persistAuthState(state: AuthState) {
  if (typeof window === 'undefined') return

  try {
    if (state.token && state.refreshToken && state.user) {
      localStorage.setItem('api_token', state.token)
      localStorage.setItem('refresh_token', state.refreshToken)
      localStorage.setItem('auth_user', JSON.stringify(state.user))
    } else {
      localStorage.removeItem('api_token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('auth_user')
    }
  } catch (error) {
    console.warn('Failed to persist auth state to localStorage:', error)
  }
}

/**
 * Main authentication composable
 */
export function useAuth() {
  // Initialize global state if not already done
  if (!authState) {
    authState = ref(initializeAuthState())
    
    // Watch for changes and persist to localStorage
    watch(
      authState,
      (newState) => {
        persistAuthState(newState)
      },
      { deep: true }
    )
  }

  const state = authState!

  // Computed properties
  const user = computed(() => state.value.user)
  const isAuthenticated = computed(() => state.value.isAuthenticated)
  const isLoading = computed(() => state.value.isLoading)
  const error = computed(() => state.value.error)
  const token = computed(() => state.value.token)

  // Helper to set loading state
  const setLoading = (loading: boolean) => {
    state.value.isLoading = loading
  }

  // Helper to set error state
  const setError = (error: string | null) => {
    state.value.error = error
  }

  // Clear error
  const clearError = () => {
    state.value.error = null
  }

  /**
   * Login with email and password
   */
  const login = async (credentials: LoginRequest): Promise<AuthUser> => {
    setLoading(true)
    setError(null)

    try {
      const response: LoginResponse = await apiClient.login(credentials)
      
      const authUser = emailAccountToAuthUser(response.user)
      
      // Update state
      state.value.user = authUser
      state.value.token = response.token
      state.value.refreshToken = response.refresh_token
      state.value.isAuthenticated = true

      return authUser
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Register new user account
   */
  const register = async (data: {
    email: string
    password: string
    domain: string
    displayName?: string
  }): Promise<AuthUser> => {
    setLoading(true)
    setError(null)

    try {
      // Create account via API
      const account = await apiClient.post<EmailAccount>('/email_accounts', {
        email: data.email,
        password: data.password,
        domain: data.domain,
        displayName: data.displayName,
        isActive: true
      })

      // Auto-login after successful registration
      const authUser = await login({
        email: data.email,
        password: data.password
      })

      return authUser
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Logout current user
   */
  const logout = async (): Promise<void> => {
    setLoading(true)
    setError(null)

    try {
      // Call logout endpoint (ignore errors)
      await apiClient.logout()
    } catch (error) {
      // Ignore logout errors - we'll clear local state anyway
      console.warn('Logout error (ignored):', error)
    } finally {
      // Clear state regardless of API response
      state.value.user = null
      state.value.token = null
      state.value.refreshToken = null
      state.value.isAuthenticated = false
      setLoading(false)
    }
  }

  /**
   * Refresh current user data
   */
  const refreshUser = async (): Promise<AuthUser | null> => {
    if (!state.value.isAuthenticated) {
      return null
    }

    setLoading(true)
    setError(null)

    try {
      const account = await apiClient.getCurrentUser() as EmailAccount
      const authUser = emailAccountToAuthUser(account)
      
      state.value.user = authUser
      return authUser
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      
      // If unauthorized, clear auth state
      if (isApiError(error) && error.status === 401) {
        await logout()
      }
      
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Check if current user has specific permission
   */
  const hasPermission = (permission: string): boolean => {
    if (!state.value.user) return false
    
    // For now, all authenticated users have basic permissions
    // This can be extended with role-based permissions later
    const basicPermissions = [
      'email.read',
      'email.send',
      'account.read',
      'account.update'
    ]
    
    return basicPermissions.includes(permission)
  }

  /**
   * Check if current user can access a specific resource
   */
  const canAccess = (resource: string, action: string = 'read'): boolean => {
    if (!state.value.user) return false
    
    // Basic access control - can be extended
    switch (resource) {
      case 'admin':
        return false // No admin access for now
      case 'account':
        return true // Users can access their own account
      case 'email':
        return true // Users can access their emails
      case 'domain':
        return action === 'read' // Users can read domain info
      default:
        return false
    }
  }

  /**
   * Get user's display name or fallback to email
   */
  const getDisplayName = (): string => {
    if (!state.value.user) return 'Guest'
    return state.value.user.displayName || state.value.user.email
  }

  /**
   * Get user's initials for avatar
   */
  const getInitials = (): string => {
    if (!state.value.user) return 'G'
    
    const name = state.value.user.displayName || state.value.user.email
    const parts = name.split(/[\s@]+/)
    
    if (parts.length >= 2) {
      return (parts[0][0] + parts[1][0]).toUpperCase()
    }
    
    return name.substring(0, 2).toUpperCase()
  }

  /**
   * Update user profile
   */
  const updateProfile = async (data: {
    displayName?: string
    password?: string
  }): Promise<AuthUser> => {
    if (!state.value.user) {
      throw new Error('No authenticated user')
    }

    setLoading(true)
    setError(null)

    try {
      const updatedAccount = await apiClient.put<EmailAccount>(
        `/email_accounts/${state.value.user.id}`,
        data
      )
      
      const authUser = emailAccountToAuthUser(updatedAccount)
      state.value.user = authUser
      
      return authUser
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Validate current session
   */
  const validateSession = async (): Promise<boolean> => {
    if (!state.value.token) {
      return false
    }

    try {
      await apiClient.getCurrentUser()
      return true
    } catch (error) {
      // Session is invalid
      await logout()
      return false
    }
  }

  return {
    // State
    user: readonly(user),
    isAuthenticated: readonly(isAuthenticated),
    isLoading: readonly(isLoading),
    error: readonly(error),
    token: readonly(token),

    // Methods
    login,
    register,
    logout,
    refreshUser,
    updateProfile,
    validateSession,
    
    // Utilities
    hasPermission,
    canAccess,
    getDisplayName,
    getInitials,
    clearError,

    // Raw state access (for advanced usage)
    $state: readonly(state)
  }
}

/**
 * Provide auth context (for use in app.vue or plugins)
 */
export function provideAuth() {
  const auth = useAuth()
  
  // Automatically validate session on app start
  if (typeof window !== 'undefined' && auth.isAuthenticated.value) {
    auth.validateSession().catch(() => {
      // Session validation failed - already handled by logout
    })
  }
  
  return auth
}

/**
 * Auth guard for navigation
 */
export function requireAuth(): Promise<AuthUser> {
  const auth = useAuth()
  
  if (!auth.isAuthenticated.value) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Authentication required'
    })
  }
  
  return Promise.resolve(auth.user.value!)
}

/**
 * Guest guard for navigation (redirect if already authenticated)
 */
export function requireGuest(): Promise<void> {
  const auth = useAuth()
  
  if (auth.isAuthenticated.value) {
    throw createError({
      statusCode: 403,
      statusMessage: 'Already authenticated'
    })
  }
  
  return Promise.resolve()
}