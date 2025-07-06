/**
 * Account Management Composable
 * Provides account-related operations and state management
 */

import { ref, computed } from 'vue'
import { apiClient, getErrorMessage } from '~/utils/api'
import type { EmailAccount, PaginatedResponse } from '~/types/api'

export interface CreateAccountRequest {
  email: string
  password: string
  domain: string
  displayName?: string
  type: 'standard' | 'alias' | 'distribution'
  forwardTo?: string
  distributionMembers?: string[]
  quotaLimit?: number | null
  options?: {
    enablePlusAliasing?: boolean
    enableAutoResponder?: boolean
    enableSpamFilter?: boolean
  }
}

export interface UpdateAccountRequest {
  displayName?: string
  password?: string
  isActive?: boolean
  quotaLimit?: number | null
  options?: {
    enablePlusAliasing?: boolean
    enableAutoResponder?: boolean
    enableSpamFilter?: boolean
  }
}

export interface AccountListOptions {
  page?: number
  limit?: number
  search?: string
  domain?: string
  type?: string
  status?: 'active' | 'inactive' | 'all'
}

/**
 * Account management composable
 */
export function useAccount() {
  // State
  const accounts = ref<EmailAccount[]>([])
  const currentAccount = ref<EmailAccount | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const total = ref(0)
  const pagination = ref({
    page: 1,
    limit: 20,
    pages: 0
  })

  // Computed
  const hasAccounts = computed(() => accounts.value.length > 0)
  const totalPages = computed(() => pagination.value.pages)
  const currentPage = computed(() => pagination.value.page)

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
   * Create new email account
   */
  const createAccount = async (data: CreateAccountRequest): Promise<EmailAccount> => {
    setLoading(true)
    setError(null)

    try {
      const account = await apiClient.post<EmailAccount>('/email_accounts', data)
      
      // Add to local accounts list if loaded
      if (accounts.value.length > 0) {
        accounts.value.unshift(account)
        total.value++
      }

      return account
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Get list of accounts with pagination
   */
  const getAccounts = async (options: AccountListOptions = {}): Promise<PaginatedResponse<EmailAccount>> => {
    setLoading(true)
    setError(null)

    try {
      const query: Record<string, string | number | boolean> = {
        page: options.page || 1,
        itemsPerPage: options.limit || 20
      }

      if (options.search) {
        query['email'] = options.search
      }

      if (options.domain) {
        query['domain'] = options.domain
      }

      if (options.type) {
        query['type'] = options.type
      }

      if (options.status && options.status !== 'all') {
        query['isActive'] = options.status === 'active'
      }

      const response = await apiClient.get<PaginatedResponse<EmailAccount>>('/email_accounts', query)
      
      // Update state
      accounts.value = response.data
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
   * Get account by ID
   */
  const getAccount = async (id: string): Promise<EmailAccount> => {
    setLoading(true)
    setError(null)

    try {
      const account = await apiClient.get<EmailAccount>(`/email_accounts/${id}`)
      currentAccount.value = account
      return account
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Update account
   */
  const updateAccount = async (id: string, data: UpdateAccountRequest): Promise<EmailAccount> => {
    setLoading(true)
    setError(null)

    try {
      const account = await apiClient.put<EmailAccount>(`/email_accounts/${id}`, data)
      
      // Update in local list
      const index = accounts.value.findIndex(a => a.id === id)
      if (index !== -1) {
        accounts.value[index] = account
      }

      // Update current account if it's the same
      if (currentAccount.value?.id === id) {
        currentAccount.value = account
      }

      return account
    } catch (err) {
      const errorMessage = getErrorMessage(err)
      setError(errorMessage)
      throw err
    } finally {
      setLoading(false)
    }
  }

  /**
   * Delete account
   */
  const deleteAccount = async (id: string): Promise<void> => {
    setLoading(true)
    setError(null)

    try {
      await apiClient.delete(`/email_accounts/${id}`)
      
      // Remove from local list
      const index = accounts.value.findIndex(a => a.id === id)
      if (index !== -1) {
        accounts.value.splice(index, 1)
        total.value--
      }

      // Clear current account if it's the same
      if (currentAccount.value?.id === id) {
        currentAccount.value = null
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
   * Change account password
   */
  const changePassword = async (id: string, newPassword: string): Promise<void> => {
    await updateAccount(id, { password: newPassword })
  }

  /**
   * Toggle account status
   */
  const toggleAccountStatus = async (id: string): Promise<EmailAccount> => {
    const account = accounts.value.find(a => a.id === id) || currentAccount.value
    if (!account) {
      throw new Error('Account not found')
    }

    return updateAccount(id, { isActive: !account.isActive })
  }

  /**
   * Get account statistics
   */
  const getAccountStats = async () => {
    setLoading(true)
    setError(null)

    try {
      const stats = await apiClient.get('/email_accounts/stats')
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
   * Search accounts
   */
  const searchAccounts = async (query: string, options: Omit<AccountListOptions, 'search'> = {}): Promise<EmailAccount[]> => {
    const response = await getAccounts({ ...options, search: query })
    return response.data
  }

  /**
   * Refresh accounts list
   */
  const refreshAccounts = async (): Promise<void> => {
    await getAccounts({
      page: pagination.value.page,
      limit: pagination.value.limit
    })
  }

  /**
   * Reset state
   */
  const reset = () => {
    accounts.value = []
    currentAccount.value = null
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
    accounts: readonly(accounts),
    currentAccount: readonly(currentAccount),
    isLoading: readonly(isLoading),
    error: readonly(error),
    total: readonly(total),
    pagination: readonly(pagination),

    // Computed
    hasAccounts,
    totalPages,
    currentPage,

    // Methods
    createAccount,
    getAccounts,
    getAccount,
    updateAccount,
    deleteAccount,
    changePassword,
    toggleAccountStatus,
    getAccountStats,
    searchAccounts,
    refreshAccounts,
    reset,

    // Utilities
    clearError
  }
}