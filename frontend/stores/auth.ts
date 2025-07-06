/**
 * Auth Store for TechSci Labs Email Testing Platform
 * Pinia store for centralized authentication state management
 */

import { defineStore } from 'pinia'
import { useAuth, type AuthUser, type AuthState } from '~/composables/useAuth'
import type { LoginRequest } from '~/types/api'

export interface AuthStoreState {
  user: AuthUser | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  lastActivity: Date | null
  sessionTimeout: number // in minutes
}

export interface AuthStoreActions {
  login: (credentials: LoginRequest) => Promise<AuthUser>
  register: (data: {
    email: string
    password: string
    domain: string
    displayName?: string
  }) => Promise<AuthUser>
  logout: () => Promise<void>
  refreshUser: () => Promise<AuthUser | null>
  updateProfile: (data: {
    displayName?: string
    password?: string
  }) => Promise<AuthUser>
  clearError: () => void
  updateActivity: () => void
  checkSessionTimeout: () => void
  validateSession: () => Promise<boolean>
}

export interface AuthStoreGetters {
  displayName: string
  initials: string
  isSessionExpired: boolean
  timeUntilExpiry: number
  hasPermission: (permission: string) => boolean
  canAccess: (resource: string, action?: string) => boolean
}

/**
 * Main authentication store
 */
export const useAuthStore = defineStore('auth', {
  state: (): AuthStoreState => ({
    user: null,
    isAuthenticated: false,
    isLoading: false,
    error: null,
    lastActivity: null,
    sessionTimeout: 120 // 2 hours in minutes
  }),

  getters: {
    /**
     * Get user's display name
     */
    displayName(): string {
      const auth = useAuth()
      return auth.getDisplayName()
    },

    /**
     * Get user's initials for avatar
     */
    initials(): string {
      const auth = useAuth()
      return auth.getInitials()
    },

    /**
     * Check if session is expired based on last activity
     */
    isSessionExpired(): boolean {
      if (!this.lastActivity || !this.isAuthenticated) {
        return false
      }

      const now = new Date()
      const expireTime = new Date(this.lastActivity.getTime() + (this.sessionTimeout * 60 * 1000))
      
      return now > expireTime
    },

    /**
     * Get time until session expires (in minutes)
     */
    timeUntilExpiry(): number {
      if (!this.lastActivity || !this.isAuthenticated) {
        return 0
      }

      const now = new Date()
      const expireTime = new Date(this.lastActivity.getTime() + (this.sessionTimeout * 60 * 1000))
      const diffMs = expireTime.getTime() - now.getTime()
      
      return Math.max(0, Math.floor(diffMs / (60 * 1000)))
    },

    /**
     * Check if user has specific permission
     */
    hasPermission(): (permission: string) => boolean {
      return (permission: string) => {
        const auth = useAuth()
        return auth.hasPermission(permission)
      }
    },

    /**
     * Check if user can access resource
     */
    canAccess(): (resource: string, action?: string) => boolean {
      return (resource: string, action: string = 'read') => {
        const auth = useAuth()
        return auth.canAccess(resource, action)
      }
    }
  },

  actions: {
    /**
     * Initialize store with auth composable state
     */
    async initialize() {
      const auth = useAuth()
      
      // Sync store state with composable state
      this.user = auth.user.value
      this.isAuthenticated = auth.isAuthenticated.value
      this.isLoading = auth.isLoading.value
      this.error = auth.error.value

      // Set last activity if authenticated
      if (this.isAuthenticated) {
        this.lastActivity = new Date()
        
        // Validate existing session
        const isValid = await this.validateSession()
        if (!isValid) {
          await this.logout()
        }
      }
    },

    /**
     * Login user with credentials
     */
    async login(credentials: LoginRequest): Promise<AuthUser> {
      const auth = useAuth()
      
      this.isLoading = true
      this.error = null

      try {
        const user = await auth.login(credentials)
        
        // Update store state
        this.user = user
        this.isAuthenticated = true
        this.lastActivity = new Date()
        
        return user
      } catch (error) {
        this.error = auth.error.value
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Register new user
     */
    async register(data: {
      email: string
      password: string
      domain: string
      displayName?: string
    }): Promise<AuthUser> {
      const auth = useAuth()
      
      this.isLoading = true
      this.error = null

      try {
        const user = await auth.register(data)
        
        // Update store state
        this.user = user
        this.isAuthenticated = true
        this.lastActivity = new Date()
        
        return user
      } catch (error) {
        this.error = auth.error.value
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Logout current user
     */
    async logout(): Promise<void> {
      const auth = useAuth()
      
      this.isLoading = true

      try {
        await auth.logout()
      } finally {
        // Clear store state
        this.user = null
        this.isAuthenticated = false
        this.lastActivity = null
        this.error = null
        this.isLoading = false
      }
    },

    /**
     * Refresh current user data
     */
    async refreshUser(): Promise<AuthUser | null> {
      const auth = useAuth()
      
      this.isLoading = true
      this.error = null

      try {
        const user = await auth.refreshUser()
        
        if (user) {
          this.user = user
          this.lastActivity = new Date()
        }
        
        return user
      } catch (error) {
        this.error = auth.error.value
        
        // If refresh failed due to auth, logout
        if (!auth.isAuthenticated.value) {
          await this.logout()
        }
        
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Update user profile
     */
    async updateProfile(data: {
      displayName?: string
      password?: string
    }): Promise<AuthUser> {
      const auth = useAuth()
      
      this.isLoading = true
      this.error = null

      try {
        const user = await auth.updateProfile(data)
        
        this.user = user
        this.lastActivity = new Date()
        
        return user
      } catch (error) {
        this.error = auth.error.value
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Validate current session
     */
    async validateSession(): Promise<boolean> {
      const auth = useAuth()
      
      try {
        const isValid = await auth.validateSession()
        
        if (isValid) {
          this.lastActivity = new Date()
        } else {
          // Session invalid, clear state
          this.user = null
          this.isAuthenticated = false
          this.lastActivity = null
        }
        
        return isValid
      } catch (error) {
        return false
      }
    },

    /**
     * Clear error state
     */
    clearError() {
      this.error = null
      const auth = useAuth()
      auth.clearError()
    },

    /**
     * Update last activity timestamp
     */
    updateActivity() {
      if (this.isAuthenticated) {
        this.lastActivity = new Date()
      }
    },

    /**
     * Check if session has timed out and logout if necessary
     */
    async checkSessionTimeout() {
      if (this.isSessionExpired) {
        await this.logout()
        
        // Show timeout notification
        const toast = useToast()
        toast.add({
          title: 'Session Expired',
          description: 'Your session has expired. Please log in again.',
          color: 'yellow',
          timeout: 5000
        })
      }
    },

    /**
     * Set session timeout duration
     */
    setSessionTimeout(minutes: number) {
      this.sessionTimeout = minutes
    }
  }
})

/**
 * Auto-initialize auth store on first use
 */
let initialized = false

export function useAuthStoreWithInit() {
  const store = useAuthStore()
  
  if (!initialized && typeof window !== 'undefined') {
    initialized = true
    store.initialize()
    
    // Set up activity tracking
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    const updateActivity = () => store.updateActivity()
    
    events.forEach(event => {
      document.addEventListener(event, updateActivity, { passive: true })
    })
    
    // Check session timeout every minute
    setInterval(() => {
      if (store.isAuthenticated) {
        store.checkSessionTimeout()
      }
    }, 60000)
  }
  
  return store
}

/**
 * Provide auth store (for use in app.vue or plugins)
 */
export function provideAuthStore() {
  return useAuthStoreWithInit()
}

/**
 * Auth plugin for Nuxt
 */
export default defineNuxtPlugin(() => {
  // Initialize auth store on app start
  const store = useAuthStoreWithInit()
  
  // Watch for route changes and update activity
  const router = useRouter()
  router.afterEach(() => {
    store.updateActivity()
  })
  
  return {
    provide: {
      authStore: store
    }
  }
})