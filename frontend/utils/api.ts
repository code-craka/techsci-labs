/**
 * API Client Utilities for TechSci Labs Email Testing Platform
 * Provides centralized API communication with authentication and error handling
 */

import type { 
  ApiResponse, 
  ApiCollection, 
  ApiError,
  LoginRequest,
  LoginResponse,
  RefreshTokenRequest,
  RefreshTokenResponse
} from '~/types/api'

// API Configuration
export const API_CONFIG = {
  baseURL: 'http://localhost:8000/api',
  timeout: 30000,
  retries: 3,
  retryDelay: 1000
}

// HTTP Status Codes
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE_ENTITY: 422,
  INTERNAL_SERVER_ERROR: 500
} as const

// API Error Class
export class ApiClientError extends Error {
  public status: number
  public code: string
  public violations?: Array<{
    propertyPath: string
    message: string
    code?: string
  }>

  constructor(
    message: string,
    status: number = 500,
    code: string = 'UNKNOWN_ERROR',
    violations?: Array<{
      propertyPath: string
      message: string
      code?: string
    }>
  ) {
    super(message)
    this.name = 'ApiClientError'
    this.status = status
    this.code = code
    this.violations = violations
  }

  static fromApiError(error: ApiError, status: number = 500): ApiClientError {
    return new ApiClientError(
      error['hydra:description'] || error['hydra:title'],
      status,
      error['hydra:title'],
      error.violations
    )
  }
}

// Token Storage Interface
export interface TokenStorage {
  getToken(): string | null
  setToken(token: string): void
  getRefreshToken(): string | null
  setRefreshToken(token: string): void
  clearTokens(): void
}

// Default Token Storage (localStorage)
export const defaultTokenStorage: TokenStorage = {
  getToken(): string | null {
    if (typeof window === 'undefined') return null
    return localStorage.getItem('api_token')
  },

  setToken(token: string): void {
    if (typeof window === 'undefined') return
    localStorage.setItem('api_token', token)
  },

  getRefreshToken(): string | null {
    if (typeof window === 'undefined') return null
    return localStorage.getItem('refresh_token')
  },

  setRefreshToken(token: string): void {
    if (typeof window === 'undefined') return
    localStorage.setItem('refresh_token', token)
  },

  clearTokens(): void {
    if (typeof window === 'undefined') return
    localStorage.removeItem('api_token')
    localStorage.removeItem('refresh_token')
  }
}

// Request Options Interface
export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  headers?: Record<string, string>
  body?: any
  query?: Record<string, any>
  auth?: boolean
  retries?: number
  timeout?: number
}

// API Client Class
export class ApiClient {
  private baseURL: string
  private timeout: number
  private defaultRetries: number
  private tokenStorage: TokenStorage
  private refreshPromise: Promise<string> | null = null

  constructor(
    baseURL: string = API_CONFIG.baseURL,
    tokenStorage: TokenStorage = defaultTokenStorage
  ) {
    this.baseURL = baseURL.replace(/\/$/, '')
    this.timeout = API_CONFIG.timeout
    this.defaultRetries = API_CONFIG.retries
    this.tokenStorage = tokenStorage
  }

  /**
   * Build URL with query parameters
   */
  private buildURL(endpoint: string, query?: Record<string, any>): string {
    const url = `${this.baseURL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`
    
    if (!query || Object.keys(query).length === 0) {
      return url
    }

    const params = new URLSearchParams()
    Object.entries(query).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        params.append(key, String(value))
      }
    })

    return `${url}?${params.toString()}`
  }

  /**
   * Get authorization headers
   */
  private getAuthHeaders(): Record<string, string> {
    const token = this.tokenStorage.getToken()
    return token ? { Authorization: `Bearer ${token}` } : {}
  }

  /**
   * Refresh authentication token
   */
  private async refreshToken(): Promise<string> {
    if (this.refreshPromise) {
      return this.refreshPromise
    }

    this.refreshPromise = this.performTokenRefresh()
    
    try {
      const newToken = await this.refreshPromise
      return newToken
    } finally {
      this.refreshPromise = null
    }
  }

  private async performTokenRefresh(): Promise<string> {
    const refreshToken = this.tokenStorage.getRefreshToken()
    
    if (!refreshToken) {
      throw new ApiClientError('No refresh token available', HTTP_STATUS.UNAUTHORIZED)
    }

    try {
      const response = await this.makeRequest('/auth/refresh', {
        method: 'POST',
        body: { refresh_token: refreshToken } as RefreshTokenRequest,
        auth: false
      })

      const data = response as RefreshTokenResponse
      this.tokenStorage.setToken(data.token)
      this.tokenStorage.setRefreshToken(data.refresh_token)
      
      return data.token
    } catch (error) {
      this.tokenStorage.clearTokens()
      throw new ApiClientError('Token refresh failed', HTTP_STATUS.UNAUTHORIZED)
    }
  }

  /**
   * Make HTTP request with retry logic
   */
  private async makeRequest(
    endpoint: string,
    options: RequestOptions = {}
  ): Promise<any> {
    const {
      method = 'GET',
      headers = {},
      body,
      query,
      auth = true,
      retries = this.defaultRetries,
      timeout = this.timeout
    } = options

    const url = this.buildURL(endpoint, query)
    const authHeaders = auth ? this.getAuthHeaders() : {}
    
    const requestInit: RequestInit = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/ld+json',
        ...authHeaders,
        ...headers
      },
      signal: AbortSignal.timeout(timeout)
    }

    if (body && ['POST', 'PUT', 'PATCH'].includes(method)) {
      requestInit.body = JSON.stringify(body)
    }

    let lastError: Error | null = null

    for (let attempt = 0; attempt <= retries; attempt++) {
      try {
        const response = await fetch(url, requestInit)
        
        // Handle successful responses
        if (response.ok) {
          const contentType = response.headers.get('content-type')
          
          if (contentType?.includes('application/json') || contentType?.includes('application/ld+json')) {
            return await response.json()
          }
          
          if (response.status === HTTP_STATUS.NO_CONTENT) {
            return null
          }
          
          return await response.text()
        }

        // Handle unauthorized responses with token refresh
        if (response.status === HTTP_STATUS.UNAUTHORIZED && auth && attempt === 0) {
          try {
            await this.refreshToken()
            // Retry with new token
            continue
          } catch (refreshError) {
            throw refreshError
          }
        }

        // Handle API errors
        const errorData = await response.json().catch(() => ({}))
        const apiError = errorData as ApiError
        
        throw ApiClientError.fromApiError(apiError, response.status)

      } catch (error) {
        lastError = error instanceof Error ? error : new Error(String(error))
        
        // Don't retry on client errors (4xx) except 401
        if (error instanceof ApiClientError && 
            error.status >= 400 && 
            error.status < 500 && 
            error.status !== HTTP_STATUS.UNAUTHORIZED) {
          throw error
        }
        
        // Wait before retry
        if (attempt < retries) {
          await new Promise(resolve => setTimeout(resolve, API_CONFIG.retryDelay * (attempt + 1)))
        }
      }
    }

    throw lastError || new ApiClientError('Request failed after retries')
  }

  /**
   * GET request
   */
  async get<T = any>(
    endpoint: string,
    query?: Record<string, any>,
    options: Omit<RequestOptions, 'method' | 'body' | 'query'> = {}
  ): Promise<T> {
    return this.makeRequest(endpoint, {
      ...options,
      method: 'GET',
      query
    })
  }

  /**
   * POST request
   */
  async post<T = any>(
    endpoint: string,
    body?: any,
    options: Omit<RequestOptions, 'method' | 'body'> = {}
  ): Promise<T> {
    return this.makeRequest(endpoint, {
      ...options,
      method: 'POST',
      body
    })
  }

  /**
   * PUT request
   */
  async put<T = any>(
    endpoint: string,
    body?: any,
    options: Omit<RequestOptions, 'method' | 'body'> = {}
  ): Promise<T> {
    return this.makeRequest(endpoint, {
      ...options,
      method: 'PUT',
      body
    })
  }

  /**
   * PATCH request
   */
  async patch<T = any>(
    endpoint: string,
    body?: any,
    options: Omit<RequestOptions, 'method' | 'body'> = {}
  ): Promise<T> {
    return this.makeRequest(endpoint, {
      ...options,
      method: 'PATCH',
      body
    })
  }

  /**
   * DELETE request
   */
  async delete<T = any>(
    endpoint: string,
    options: Omit<RequestOptions, 'method' | 'body'> = {}
  ): Promise<T> {
    return this.makeRequest(endpoint, {
      ...options,
      method: 'DELETE'
    })
  }

  /**
   * Authentication methods
   */
  async login(credentials: LoginRequest): Promise<LoginResponse> {
    const response = await this.post<LoginResponse>('/auth/login', credentials, { auth: false })
    
    this.tokenStorage.setToken(response.token)
    this.tokenStorage.setRefreshToken(response.refresh_token)
    
    return response
  }

  async logout(): Promise<void> {
    try {
      await this.post('/auth/logout')
    } catch (error) {
      // Ignore logout errors
    } finally {
      this.tokenStorage.clearTokens()
    }
  }

  /**
   * Check if user is authenticated
   */
  isAuthenticated(): boolean {
    return !!this.tokenStorage.getToken()
  }

  /**
   * Get current user
   */
  async getCurrentUser(): Promise<any> {
    return this.get('/auth/me')
  }
}

// Default API client instance
export const apiClient = new ApiClient()

// Utility functions
export function isApiError(error: any): error is ApiClientError {
  return error instanceof ApiClientError
}

export function getErrorMessage(error: any): string {
  if (isApiError(error)) {
    return error.message
  }
  
  if (error instanceof Error) {
    return error.message
  }
  
  return 'An unexpected error occurred'
}

export function getValidationErrors(error: any): Record<string, string> {
  if (isApiError(error) && error.violations) {
    return error.violations.reduce((acc, violation) => {
      acc[violation.propertyPath] = violation.message
      return acc
    }, {} as Record<string, string>)
  }
  
  return {}
}