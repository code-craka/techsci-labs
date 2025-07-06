/**
 * API Client Plugin
 * Initializes the API client with runtime configuration
 */

import { updateApiConfig, apiClient } from '~/utils/api'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  
  // Update API configuration with runtime values if they exist
  if (config.public?.apiBase) {
    updateApiConfig({
      baseURL: config.public.apiBase,
      timeout: config.public.apiTimeout || 30000,
      retries: config.public.apiRetries || 3
    })
    
    // Update the default API client instance
    apiClient.updateConfig({
      baseURL: config.public.apiBase,
      timeout: config.public.apiTimeout || 30000,
      defaultRetries: config.public.apiRetries || 3
    })
  }

  // Log API configuration in development
  if (process.dev) {
    console.log('🔗 API Client initialized:', {
      baseURL: apiClient.baseURL,
      timeout: apiClient.timeout,
      retries: apiClient.defaultRetries
    })
  }

  // Provide API client to the app
  return {
    provide: {
      api: apiClient
    }
  }
})