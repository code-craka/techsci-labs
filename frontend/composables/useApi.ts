/**
 * API Composable for TechSci Labs Email Testing Platform
 * Provides reactive API client with loading states and error handling
 */

import { ref, computed, type Ref } from 'vue'
import { 
  apiClient, 
  isApiError, 
  getErrorMessage, 
  getValidationErrors,
  type ApiClientError 
} from '~/utils/api'
import type {
  ApiCollection,
  Domain,
  EmailAccount,
  Mailbox,
  Message,
  Attachment,
  Token,
  CreateDomainRequest,
  UpdateDomainRequest,
  CreateAccountRequest,
  UpdateAccountRequest,
  SendMessageRequest,
  CreateTokenRequest,
  DomainFilters,
  AccountFilters,
  MessageFilters,
  DomainStats,
  AccountStats,
  SystemStats
} from '~/types/api'

// Loading state interface
interface LoadingState {
  [key: string]: boolean
}

// Error state interface
interface ErrorState {
  [key: string]: string | null
}

/**
 * Main API composable
 */
export function useApi() {
  const config = useRuntimeConfig()
  
  // Update API client base URL from runtime config
  if (config.public.apiBase) {
    apiClient['baseURL'] = config.public.apiBase.replace(/\/$/, '')
  }

  // Reactive states
  const loading: Ref<LoadingState> = ref({})
  const errors: Ref<ErrorState> = ref({})

  // Helper to manage loading state
  const withLoading = async <T>(
    key: string,
    operation: () => Promise<T>
  ): Promise<T> => {
    loading.value[key] = true
    errors.value[key] = null

    try {
      const result = await operation()
      return result
    } catch (error) {
      errors.value[key] = getErrorMessage(error)
      throw error
    } finally {
      loading.value[key] = false
    }
  }

  // Computed getters for common loading states
  const isLoading = computed(() => (key?: string) => {
    if (key) return loading.value[key] || false
    return Object.values(loading.value).some(Boolean)
  })

  const getError = computed(() => (key: string) => {
    return errors.value[key] || null
  })

  const hasError = computed(() => (key?: string) => {
    if (key) return !!errors.value[key]
    return Object.values(errors.value).some(Boolean)
  })

  // Clear error for specific operation
  const clearError = (key: string) => {
    errors.value[key] = null
  }

  // Clear all errors
  const clearAllErrors = () => {
    errors.value = {}
  }

  return {
    // Client instance
    client: apiClient,

    // State management
    loading: readonly(loading),
    errors: readonly(errors),
    isLoading,
    getError,
    hasError,
    clearError,
    clearAllErrors,

    // Helper utilities
    withLoading,
    isApiError,
    getErrorMessage,
    getValidationErrors
  }
}

/**
 * Domain API composable
 */
export function useDomainApi() {
  const { withLoading, client } = useApi()

  return {
    // Get all domains
    async getDomains(filters?: DomainFilters): Promise<ApiCollection<Domain>> {
      return withLoading('domains.list', () =>
        client.get<ApiCollection<Domain>>('/domains', filters)
      )
    },

    // Get single domain
    async getDomain(id: string): Promise<Domain> {
      return withLoading(`domains.${id}`, () =>
        client.get<Domain>(`/domains/${id}`)
      )
    },

    // Create domain
    async createDomain(data: CreateDomainRequest): Promise<Domain> {
      return withLoading('domains.create', () =>
        client.post<Domain>('/domains', data)
      )
    },

    // Update domain
    async updateDomain(id: string, data: UpdateDomainRequest): Promise<Domain> {
      return withLoading(`domains.${id}.update`, () =>
        client.put<Domain>(`/domains/${id}`, data)
      )
    },

    // Delete domain
    async deleteDomain(id: string): Promise<void> {
      return withLoading(`domains.${id}.delete`, () =>
        client.delete(`/domains/${id}`)
      )
    },

    // Get domain statistics
    async getDomainStats(id: string): Promise<DomainStats> {
      return withLoading(`domains.${id}.stats`, () =>
        client.get<DomainStats>(`/domains/${id}/stats`)
      )
    }
  }
}

/**
 * Email Account API composable
 */
export function useAccountApi() {
  const { withLoading, client } = useApi()

  return {
    // Get all accounts
    async getAccounts(filters?: AccountFilters): Promise<ApiCollection<EmailAccount>> {
      return withLoading('accounts.list', () =>
        client.get<ApiCollection<EmailAccount>>('/email_accounts', filters)
      )
    },

    // Get single account
    async getAccount(id: string): Promise<EmailAccount> {
      return withLoading(`accounts.${id}`, () =>
        client.get<EmailAccount>(`/email_accounts/${id}`)
      )
    },

    // Create account
    async createAccount(data: CreateAccountRequest): Promise<EmailAccount> {
      return withLoading('accounts.create', () =>
        client.post<EmailAccount>('/email_accounts', data)
      )
    },

    // Update account
    async updateAccount(id: string, data: UpdateAccountRequest): Promise<EmailAccount> {
      return withLoading(`accounts.${id}.update`, () =>
        client.put<EmailAccount>(`/email_accounts/${id}`, data)
      )
    },

    // Delete account
    async deleteAccount(id: string): Promise<void> {
      return withLoading(`accounts.${id}.delete`, () =>
        client.delete(`/email_accounts/${id}`)
      )
    },

    // Get account statistics
    async getAccountStats(id: string): Promise<AccountStats> {
      return withLoading(`accounts.${id}.stats`, () =>
        client.get<AccountStats>(`/email_accounts/${id}/stats`)
      )
    },

    // Get account mailboxes
    async getAccountMailboxes(id: string): Promise<ApiCollection<Mailbox>> {
      return withLoading(`accounts.${id}.mailboxes`, () =>
        client.get<ApiCollection<Mailbox>>(`/email_accounts/${id}/mailboxes`)
      )
    }
  }
}

/**
 * Mailbox API composable
 */
export function useMailboxApi() {
  const { withLoading, client } = useApi()

  return {
    // Get all mailboxes
    async getMailboxes(): Promise<ApiCollection<Mailbox>> {
      return withLoading('mailboxes.list', () =>
        client.get<ApiCollection<Mailbox>>('/mailboxes')
      )
    },

    // Get single mailbox
    async getMailbox(id: string): Promise<Mailbox> {
      return withLoading(`mailboxes.${id}`, () =>
        client.get<Mailbox>(`/mailboxes/${id}`)
      )
    },

    // Create mailbox
    async createMailbox(data: { name: string; account: string }): Promise<Mailbox> {
      return withLoading('mailboxes.create', () =>
        client.post<Mailbox>('/mailboxes', data)
      )
    },

    // Delete mailbox
    async deleteMailbox(id: string): Promise<void> {
      return withLoading(`mailboxes.${id}.delete`, () =>
        client.delete(`/mailboxes/${id}`)
      )
    },

    // Get mailbox messages
    async getMailboxMessages(id: string, filters?: MessageFilters): Promise<ApiCollection<Message>> {
      return withLoading(`mailboxes.${id}.messages`, () =>
        client.get<ApiCollection<Message>>(`/mailboxes/${id}/messages`, filters)
      )
    }
  }
}

/**
 * Message API composable
 */
export function useMessageApi() {
  const { withLoading, client } = useApi()

  return {
    // Get all messages
    async getMessages(filters?: MessageFilters): Promise<ApiCollection<Message>> {
      return withLoading('messages.list', () =>
        client.get<ApiCollection<Message>>('/messages', filters)
      )
    },

    // Get single message
    async getMessage(id: string): Promise<Message> {
      return withLoading(`messages.${id}`, () =>
        client.get<Message>(`/messages/${id}`)
      )
    },

    // Send message
    async sendMessage(data: SendMessageRequest): Promise<Message> {
      return withLoading('messages.send', () =>
        client.post<Message>('/messages', data)
      )
    },

    // Mark message as read
    async markAsRead(id: string): Promise<Message> {
      return withLoading(`messages.${id}.read`, () =>
        client.patch<Message>(`/messages/${id}`, { isRead: true })
      )
    },

    // Mark message as unread
    async markAsUnread(id: string): Promise<Message> {
      return withLoading(`messages.${id}.unread`, () =>
        client.patch<Message>(`/messages/${id}`, { isRead: false })
      )
    },

    // Star message
    async starMessage(id: string): Promise<Message> {
      return withLoading(`messages.${id}.star`, () =>
        client.patch<Message>(`/messages/${id}`, { isStarred: true })
      )
    },

    // Unstar message
    async unstarMessage(id: string): Promise<Message> {
      return withLoading(`messages.${id}.unstar`, () =>
        client.patch<Message>(`/messages/${id}`, { isStarred: false })
      )
    },

    // Mark as spam
    async markAsSpam(id: string): Promise<Message> {
      return withLoading(`messages.${id}.spam`, () =>
        client.patch<Message>(`/messages/${id}`, { isSpam: true })
      )
    },

    // Delete message
    async deleteMessage(id: string): Promise<void> {
      return withLoading(`messages.${id}.delete`, () =>
        client.delete(`/messages/${id}`)
      )
    },

    // Get message attachments
    async getMessageAttachments(id: string): Promise<ApiCollection<Attachment>> {
      return withLoading(`messages.${id}.attachments`, () =>
        client.get<ApiCollection<Attachment>>(`/messages/${id}/attachments`)
      )
    }
  }
}

/**
 * Attachment API composable
 */
export function useAttachmentApi() {
  const { withLoading, client } = useApi()

  return {
    // Get single attachment
    async getAttachment(id: string): Promise<Attachment> {
      return withLoading(`attachments.${id}`, () =>
        client.get<Attachment>(`/attachments/${id}`)
      )
    },

    // Download attachment
    async downloadAttachment(id: string): Promise<Blob> {
      return withLoading(`attachments.${id}.download`, async () => {
        const response = await fetch(`${client['baseURL']}/attachments/${id}/download`, {
          headers: client['getAuthHeaders']()
        })
        
        if (!response.ok) {
          throw new Error('Failed to download attachment')
        }
        
        return response.blob()
      })
    }
  }
}

/**
 * Token API composable
 */
export function useTokenApi() {
  const { withLoading, client } = useApi()

  return {
    // Get all tokens
    async getTokens(): Promise<ApiCollection<Token>> {
      return withLoading('tokens.list', () =>
        client.get<ApiCollection<Token>>('/tokens')
      )
    },

    // Get single token
    async getToken(id: string): Promise<Token> {
      return withLoading(`tokens.${id}`, () =>
        client.get<Token>(`/tokens/${id}`)
      )
    },

    // Create token
    async createToken(data: CreateTokenRequest): Promise<Token> {
      return withLoading('tokens.create', () =>
        client.post<Token>('/tokens', data)
      )
    },

    // Delete token
    async deleteToken(id: string): Promise<void> {
      return withLoading(`tokens.${id}.delete`, () =>
        client.delete(`/tokens/${id}`)
      )
    }
  }
}

/**
 * Statistics API composable
 */
export function useStatsApi() {
  const { withLoading, client } = useApi()

  return {
    // Get system statistics
    async getSystemStats(): Promise<SystemStats> {
      return withLoading('stats.system', () =>
        client.get<SystemStats>('/stats/system')
      )
    }
  }
}