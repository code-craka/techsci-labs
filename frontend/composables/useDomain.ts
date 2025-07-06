/**
 * Domain Management Composable
 * Provides domain-related operations and DNS management
 */

import { ref, computed } from 'vue'
import { apiClient, getErrorMessage } from '~/utils/api'
import type { Domain, PaginatedResponse } from '~/types/api'

export interface CreateDomainRequest {
  name: string
  type: 'production' | 'testing'
  catchAll?: boolean
  description?: string
}

export interface UpdateDomainRequest {
  name?: string
  type?: 'production' | 'testing'
  catchAll?: boolean
  description?: string
  isActive?: boolean
}

export interface DomainListOptions {
  page?: number
  limit?: number
  search?: string
  type?: 'production' | 'testing' | 'all'
  status?: 'verified' | 'pending' | 'failed' | 'all'
}

export interface DnsRecord {
  type: 'MX' | 'TXT' | 'CNAME' | 'A'
  name: string
  value: string
  priority?: number
  status: 'verified' | 'pending' | 'failed'
  lastChecked?: string
}

export interface DomainStats {
  emailCount: number
  messagesCount: number
  storageUsed: string
  totalDomains: number
  verifiedDomains: number
  pendingDomains: number
}

/**
 * Domain management composable
 */
export function useDomain() {
  // State
  const domains = ref<Domain[]>([])
  const currentDomain = ref<Domain | null>(null)
  const dnsRecords = ref<DnsRecord[]>([])
  const domainStats = ref<DomainStats | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const total = ref(0)
  const pagination = ref({
    page: 1,
    limit: 20,
    pages: 0
  })

  // Computed
  const hasDomains = computed(() => domains.value.length > 0)
  const totalPages = computed(() => pagination.value.pages)
  const currentPage = computed(() => pagination.value.page)
  const verifiedDomains = computed(() => domains.value.filter(d => d.isActive))
  const pendingDomains = computed(() => domains.value.filter(d => !d.isActive))

  // Helper functions
  const setLoading = (loading: boolean) => {
    isLoading.value = loading
  }

  const setError = (err: string | null) => {
    error.value = err
  }

  const clearError = () => {
    error.value = null
  }

  /**
   * Create new domain
   */
  const createDomain = async (data: CreateDomainRequest): Promise<Domain> => {
    setLoading(true)
    setError(null)

    try {
      const domain = await apiClient.post<Domain>('/domains', data)
      
      // Add to local domains list if loaded
      if (domains.value.length > 0) {
        domains.value.unshift(domain)
        total.value++
      }

      return domain
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Get list of domains with pagination
   */
  const getDomains = async (options: DomainListOptions = {}): Promise<PaginatedResponse<Domain>> => {
    setLoading(true)
    setError(null)

    try {
      const query: Record<string, string | number | boolean> = {
        page: options.page || 1,
        itemsPerPage: options.limit || 20
      }

      if (options.search) {
        query['name'] = options.search
      }

      if (options.type && options.type !== 'all') {
        query['type'] = options.type
      }

      if (options.status && options.status !== 'all') {
        query['isActive'] = options.status === 'verified'
      }

      const response = await apiClient.get<PaginatedResponse<Domain>>('/domains', query)
      
      // Update state
      domains.value = response.data
      total.value = response.pagination.total
      pagination.value = {
        page: response.pagination.page,
        limit: response.pagination.limit,
        pages: response.pagination.pages
      }

      return response
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Get domain by ID
   */
  const getDomain = async (id: string): Promise<Domain> => {
    setLoading(true)
    setError(null)

    try {
      const domain = await apiClient.get<Domain>(`/domains/${id}`)
      currentDomain.value = domain
      return domain
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Update domain
   */
  const updateDomain = async (id: string, data: UpdateDomainRequest): Promise<Domain> => {
    setLoading(true)
    setError(null)

    try {
      const domain = await apiClient.put<Domain>(`/domains/${id}`, data)
      
      // Update in local list
      const index = domains.value.findIndex(d => d.id === id)
      if (index !== -1) {
        domains.value[index] = domain
      }

      // Update current domain if it's the same
      if (currentDomain.value?.id === id) {
        currentDomain.value = domain
      }

      return domain
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Delete domain
   */
  const deleteDomain = async (id: string): Promise<void> => {
    setLoading(true)
    setError(null)

    try {
      await apiClient.delete(`/domains/${id}`)
      
      // Remove from local list
      const index = domains.value.findIndex(d => d.id === id)
      if (index !== -1) {
        domains.value.splice(index, 1)
        total.value--
      }

      // Clear current domain if it's the same
      if (currentDomain.value?.id === id) {
        currentDomain.value = null
      }
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Verify domain DNS configuration
   */
  const verifyDomain = async (id: string): Promise<{ success: boolean; issues?: string[] }> => {
    setLoading(true)
    setError(null)

    try {
      const result = await apiClient.post<{ success: boolean; issues?: string[] }>(`/domains/${id}/verify`)
      
      // Refresh domain data if verification was successful
      if (result.success && currentDomain.value?.id === id) {
        await getDomain(id)
      }

      return result
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Get DNS records for domain
   */
  const getDnsRecords = async (domainId: string): Promise<DnsRecord[]> => {
    setLoading(true)
    setError(null)

    try {
      const records = await apiClient.get<DnsRecord[]>(`/domains/${domainId}/dns`)
      dnsRecords.value = records
      return records
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Generate DNS configuration guide
   */
  const generateDnsGuide = async (domainId: string): Promise<string> => {
    setLoading(true)
    setError(null)

    try {
      const guide = await apiClient.get<{ content: string }>(`/domains/${domainId}/dns-guide`)
      return guide.content
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Reset domain DNS configuration
   */
  const resetDomainDns = async (id: string): Promise<void> => {
    setLoading(true)
    setError(null)

    try {
      await apiClient.post(`/domains/${id}/reset-dns`)
      
      // Refresh domain data
      if (currentDomain.value?.id === id) {
        await getDomain(id)
      }
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Test email delivery for domain
   */
  const testEmailDelivery = async (domainId: string, testEmail: string): Promise<{ success: boolean; message: string }> => {
    setLoading(true)
    setError(null)

    try {
      const result = await apiClient.post<{ success: boolean; message: string }>(`/domains/${domainId}/test-delivery`, {
        testEmail
      })
      return result
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Get domain statistics
   */
  const getDomainStats = async (): Promise<DomainStats> => {
    setLoading(true)
    setError(null)

    try {
      const stats = await apiClient.get<DomainStats>('/domains/stats')
      domainStats.value = stats
      return stats
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Search domains
   */
  const searchDomains = async (query: string, options: Omit<DomainListOptions, 'search'> = {}): Promise<Domain[]> => {
    const response = await getDomains({ ...options, search: query })
    return response.data
  }

  /**
   * Refresh domains list
   */
  const refreshDomains = async (): Promise<void> => {
    await getDomains({
      page: pagination.value.page,
      limit: pagination.value.limit
    })
  }

  /**
   * Get domain logs
   */
  const getDomainLogs = async (domainId: string, options: { 
    limit?: number
    type?: 'dns' | 'email' | 'verification' | 'all'
  } = {}): Promise<{
    id: string
    type: string
    message: string
    timestamp: string
    status: 'success' | 'error' | 'warning'
  }[]> => {
    setLoading(true)
    setError(null)

    try {
      const query: Record<string, string | number> = {
        limit: options.limit || 50
      }

      if (options.type && options.type !== 'all') {
        query.type = options.type
      }

      const logs = await apiClient.get<{
        id: string
        type: string
        message: string
        timestamp: string
        status: 'success' | 'error' | 'warning'
      }[]>(`/domains/${domainId}/logs`, query)
      return logs
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Reset state
   */
  const reset = () => {
    domains.value = []
    currentDomain.value = null
    dnsRecords.value = []
    domainStats.value = null
    error.value = null
    total.value = 0
    pagination.value = {
      page: 1,
      limit: 20,
      pages: 0
    }
  }

  return {
    // State
    domains: readonly(domains),
    currentDomain: readonly(currentDomain),
    dnsRecords: readonly(dnsRecords),
    domainStats: readonly(domainStats),
    isLoading: readonly(isLoading),
    error: readonly(error),
    total: readonly(total),
    pagination: readonly(pagination),

    // Computed
    hasDomains,
    totalPages,
    currentPage,
    verifiedDomains,
    pendingDomains,

    // Methods
    createDomain,
    getDomains,
    getDomain,
    updateDomain,
    deleteDomain,
    verifyDomain,
    getDnsRecords,
    generateDnsGuide,
    resetDomainDns,
    testEmailDelivery,
    getDomainStats,
    searchDomains,
    refreshDomains,
    getDomainLogs,
    reset,

    // Utilities
    clearError
  }
}