/**
 * Email Management Composable for TechSci Labs Email Testing Platform
 * Provides email CRUD operations, search, and real-time updates
 */

import { ref, computed, watch, type Ref } from 'vue'
import { apiClient, isApiError, getErrorMessage } from '~/utils/api'
import type { 
  Message, 
  Mailbox,
  Attachment,
  PaginatedResponse,
  MessageSearchRequest,
  MessageCreateRequest,
  MessageUpdateRequest
} from '~/types/api'

// Email state interface
export interface EmailState {
  messages: Message[]
  currentMessage: Message | null
  mailboxes: Mailbox[]
  currentMailbox: Mailbox | null
  searchResults: Message[]
  isLoading: boolean
  isSearching: boolean
  isSending: boolean
  error: string | null
  pagination: {
    currentPage: number
    pageSize: number
    totalItems: number
    totalPages: number
  }
}

// Search filters interface
export interface EmailFilters {
  mailbox?: string
  from?: string
  to?: string
  subject?: string
  dateFrom?: string
  dateTo?: string
  hasAttachments?: boolean
  isRead?: boolean
  isFlagged?: boolean
  tags?: string[]
}

// Global email state (singleton pattern)
let emailState: Ref<EmailState> | null = null

/**
 * Initialize email state
 */
function initializeEmailState(): EmailState {
  return {
    messages: [],
    currentMessage: null,
    mailboxes: [],
    currentMailbox: null,
    searchResults: [],
    isLoading: false,
    isSearching: false,
    isSending: false,
    error: null,
    pagination: {
      currentPage: 1,
      pageSize: 25,
      totalItems: 0,
      totalPages: 0
    }
  }
}

/**
 * Main email management composable
 */
export function useEmail() {
  // Initialize global state if not already done
  if (!emailState) {
    emailState = ref(initializeEmailState())
  }

  const state = emailState!

  // Computed properties
  const messages = computed(() => state.value.messages)
  const currentMessage = computed(() => state.value.currentMessage)
  const mailboxes = computed(() => state.value.mailboxes)
  const currentMailbox = computed(() => state.value.currentMailbox)
  const searchResults = computed(() => state.value.searchResults)
  const isLoading = computed(() => state.value.isLoading)
  const isSearching = computed(() => state.value.isSearching)
  const isSending = computed(() => state.value.isSending)
  const error = computed(() => state.value.error)
  const pagination = computed(() => state.value.pagination)

  // Computed email statistics
  const unreadCount = computed(() => {
    return state.value.messages.filter(msg => !msg.isRead).length
  })

  const flaggedCount = computed(() => {
    return state.value.messages.filter(msg => msg.isFlagged).length
  })

  const totalSize = computed(() => {
    return state.value.messages.reduce((total, msg) => total + (msg.size || 0), 0)
  })

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
   * Load mailboxes for current user
   */
  const loadMailboxes = async (): Promise<Mailbox[]> => {
    setLoading(true)
    setError(null)

    try {
      const { data } = await apiClient.get<PaginatedResponse<Mailbox>>('/mailboxes')
      state.value.mailboxes = data
      
      // Set default mailbox if none selected
      if (!state.value.currentMailbox && data.length > 0) {
        state.value.currentMailbox = data.find(mb => mb.name.toLowerCase() === 'inbox') || data[0]
      }
      
      return data
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Load messages for a specific mailbox
   */
  const loadMessages = async (
    mailboxId?: string, 
    page: number = 1, 
    pageSize: number = 25
  ): Promise<Message[]> => {
    setLoading(true)
    setError(null)

    try {
      const targetMailbox = mailboxId || state.value.currentMailbox?.id
      if (!targetMailbox) {
        throw new Error('No mailbox selected')
      }

      const response = await apiClient.get<PaginatedResponse<Message>>('/messages', {
        mailbox: targetMailbox,
        page,
        limit: pageSize,
        sort: '-createdAt' // Most recent first
      })

      state.value.messages = response.data
      state.value.pagination = {
        currentPage: response.pagination.page,
        pageSize: response.pagination.limit,
        totalItems: response.pagination.total,
        totalPages: response.pagination.pages
      }

      return response.data
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Get specific message by ID
   */
  const getMessage = async (messageId: string): Promise<Message> => {
    setLoading(true)
    setError(null)

    try {
      const message = await apiClient.get<Message>(`/messages/${messageId}`)
      state.value.currentMessage = message
      
      // Mark as read if not already
      if (!message.isRead) {
        await markAsRead(messageId)
      }
      
      return message
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      setLoading(false)
    }
  }

  /**
   * Search messages with filters
   */
  const searchMessages = async (
    query: string, 
    filters: EmailFilters = {}
  ): Promise<Message[]> => {
    state.value.isSearching = true
    setError(null)

    try {
      const searchRequest: MessageSearchRequest = {
        query,
        ...filters,
        page: 1,
        limit: 50 // Larger limit for search
      }

      const response = await apiClient.post<PaginatedResponse<Message>>(
        '/messages/search',
        searchRequest
      )

      state.value.searchResults = response.data
      return response.data
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      state.value.isSearching = false
    }
  }

  /**
   * Send new email
   */
  const sendEmail = async (emailData: MessageCreateRequest): Promise<Message> => {
    state.value.isSending = true
    setError(null)

    try {
      const message = await apiClient.post<Message>('/messages', emailData)
      
      // Add to messages list if it's in the current mailbox
      if (state.value.currentMailbox?.name.toLowerCase() === 'sent') {
        state.value.messages.unshift(message)
      }
      
      return message
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    } finally {
      state.value.isSending = false
    }
  }

  /**
   * Mark message as read/unread
   */
  const markAsRead = async (messageId: string, isRead: boolean = true): Promise<Message> => {
    try {
      const updatedMessage = await apiClient.patch<Message>(`/messages/${messageId}`, {
        isRead
      })

      // Update in messages array
      const index = state.value.messages.findIndex(m => m.id === messageId)
      if (index !== -1) {
        state.value.messages[index] = updatedMessage
      }

      // Update current message if it's the same
      if (state.value.currentMessage?.id === messageId) {
        state.value.currentMessage = updatedMessage
      }

      return updatedMessage
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  /**
   * Toggle message flag
   */
  const toggleFlag = async (messageId: string): Promise<Message> => {
    try {
      const message = state.value.messages.find(m => m.id === messageId)
      if (!message) {
        throw new Error('Message not found')
      }

      const updatedMessage = await apiClient.patch<Message>(`/messages/${messageId}`, {
        isFlagged: !message.isFlagged
      })

      // Update in messages array
      const index = state.value.messages.findIndex(m => m.id === messageId)
      if (index !== -1) {
        state.value.messages[index] = updatedMessage
      }

      // Update current message if it's the same
      if (state.value.currentMessage?.id === messageId) {
        state.value.currentMessage = updatedMessage
      }

      return updatedMessage
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  /**
   * Delete message
   */
  const deleteMessage = async (messageId: string): Promise<void> => {
    try {
      await apiClient.delete(`/messages/${messageId}`)

      // Remove from messages array
      state.value.messages = state.value.messages.filter(m => m.id !== messageId)

      // Clear current message if it's the deleted one
      if (state.value.currentMessage?.id === messageId) {
        state.value.currentMessage = null
      }
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  /**
   * Move message to different mailbox
   */
  const moveMessage = async (messageId: string, targetMailboxId: string): Promise<Message> => {
    try {
      const updatedMessage = await apiClient.patch<Message>(`/messages/${messageId}`, {
        mailbox: targetMailboxId
      })

      // Remove from current messages if moved to different mailbox
      if (targetMailboxId !== state.value.currentMailbox?.id) {
        state.value.messages = state.value.messages.filter(m => m.id !== messageId)
      }

      return updatedMessage
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  /**
   * Download attachment
   */
  const downloadAttachment = async (attachmentId: string): Promise<Blob> => {
    try {
      const response = await apiClient.get(`/attachments/${attachmentId}/download`, {
        responseType: 'blob'
      })
      return response as Blob
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  /**
   * Get attachment preview URL
   */
  const getAttachmentPreviewUrl = (attachmentId: string): string => {
    return `/api/attachments/${attachmentId}/preview`
  }

  /**
   * Bulk operations
   */
  const bulkMarkAsRead = async (messageIds: string[], isRead: boolean = true): Promise<void> => {
    try {
      await apiClient.patch('/messages/bulk', {
        messageIds,
        updates: { isRead }
      })

      // Update messages in state
      state.value.messages = state.value.messages.map(message => 
        messageIds.includes(message.id) 
          ? { ...message, isRead }
          : message
      )
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  const bulkDelete = async (messageIds: string[]): Promise<void> => {
    try {
      await apiClient.post('/messages/bulk-delete', {
        messageIds
      })

      // Remove messages from state
      state.value.messages = state.value.messages.filter(
        message => !messageIds.includes(message.id)
      )
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  const bulkMove = async (messageIds: string[], targetMailboxId: string): Promise<void> => {
    try {
      await apiClient.patch('/messages/bulk', {
        messageIds,
        updates: { mailbox: targetMailboxId }
      })

      // Remove messages from current view if moved to different mailbox
      if (targetMailboxId !== state.value.currentMailbox?.id) {
        state.value.messages = state.value.messages.filter(
          message => !messageIds.includes(message.id)
        )
      }
    } catch (error) {
      const errorMessage = getErrorMessage(error)
      setError(errorMessage)
      throw error
    }
  }

  /**
   * Utility functions
   */
  const clearMessages = () => {
    state.value.messages = []
    state.value.currentMessage = null
  }

  const clearSearchResults = () => {
    state.value.searchResults = []
  }

  const setCurrentMailbox = (mailbox: Mailbox) => {
    state.value.currentMailbox = mailbox
    clearMessages() // Clear messages when changing mailbox
  }

  const formatEmailSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes'
    
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  return {
    // State
    messages: readonly(messages),
    currentMessage: readonly(currentMessage),
    mailboxes: readonly(mailboxes),
    currentMailbox: readonly(currentMailbox),
    searchResults: readonly(searchResults),
    isLoading: readonly(isLoading),
    isSearching: readonly(isSearching),
    isSending: readonly(isSending),
    error: readonly(error),
    pagination: readonly(pagination),

    // Computed statistics
    unreadCount: readonly(unreadCount),
    flaggedCount: readonly(flaggedCount),
    totalSize: readonly(totalSize),

    // Methods
    loadMailboxes,
    loadMessages,
    getMessage,
    searchMessages,
    sendEmail,
    markAsRead,
    toggleFlag,
    deleteMessage,
    moveMessage,
    downloadAttachment,
    getAttachmentPreviewUrl,

    // Bulk operations
    bulkMarkAsRead,
    bulkDelete,
    bulkMove,

    // Utilities
    clearMessages,
    clearSearchResults,
    clearError,
    setCurrentMailbox,
    formatEmailSize,

    // Raw state access (for advanced usage)
    $state: readonly(state)
  }
}

/**
 * Email real-time updates (integrate with Mercure)
 */
export function useEmailRealtime() {
  const email = useEmail()
  const { user } = useAuth()
  const mercure = useMercure()

  // Subscribe to email updates for current user
  watch(
    () => user.value,
    (newUser) => {
      if (newUser) {
        // Subscribe to email events for this account
        mercure.subscribe(`/accounts/${newUser.id}/emails`)
        
        // Handle real-time email events (would need event listener setup)
        // This is a placeholder for real-time functionality
      }
    },
    { immediate: true }
  )

  return {
    // Re-export email methods for convenience
    ...email
  }
}