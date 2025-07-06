/**
 * Mercure SSE Composable for TechSci Labs Email Testing Platform
 * Provides real-time updates via Server-Sent Events
 */

import { ref, computed, onBeforeUnmount, type Ref } from 'vue'
import { useAuth } from '~/composables/useAuth'
import type { Message, EmailAccount, Domain, WebhookEvent } from '~/types/api'

// SSE Connection States
export type SSEConnectionState = 'disconnected' | 'connecting' | 'connected' | 'error' | 'reconnecting'

// Event Types
export type MercureEventType = 'message.received' | 'message.read' | 'message.starred' | 'account.created' | 'domain.created' | 'connection.status'

// Event Handler Type
export type MercureEventHandler<T = any> = (data: T, event: MessageEvent) => void

// Mercure Event Interface
export interface MercureEvent<T = any> {
  type: MercureEventType
  data: T
  timestamp: string
  accountId?: string
  messageId?: string
}

// Connection Configuration
export interface MercureConfig {
  hubUrl: string
  topics: string[]
  reconnectInterval: number
  maxReconnectAttempts: number
  heartbeatInterval: number
}

// Default Configuration
const DEFAULT_CONFIG: MercureConfig = {
  hubUrl: 'http://localhost:3001/.well-known/mercure',
  topics: [],
  reconnectInterval: 5000,
  maxReconnectAttempts: 10,
  heartbeatInterval: 30000
}

/**
 * Main Mercure SSE Composable
 */
export function useMercure(initialConfig?: Partial<MercureConfig>) {
  const config = useRuntimeConfig()
  const auth = useAuth()
  
  // Configuration
  const mercureConfig: MercureConfig = {
    ...DEFAULT_CONFIG,
    hubUrl: config.public.mercureUrl || DEFAULT_CONFIG.hubUrl,
    ...initialConfig
  }

  // State
  const connectionState: Ref<SSEConnectionState> = ref('disconnected')
  const eventSource: Ref<EventSource | null> = ref(null)
  const subscribedTopics: Ref<Set<string>> = ref(new Set())
  const eventHandlers: Ref<Map<string, Set<MercureEventHandler>>> = ref(new Map())
  const reconnectAttempts: Ref<number> = ref(0)
  const lastHeartbeat: Ref<Date | null> = ref(null)
  const error: Ref<string | null> = ref(null)

  // Computed
  const isConnected = computed(() => connectionState.value === 'connected')
  const isConnecting = computed(() => connectionState.value === 'connecting' || connectionState.value === 'reconnecting')
  const canReconnect = computed(() => reconnectAttempts.value < mercureConfig.maxReconnectAttempts)

  /**
   * Build Mercure URL with topics and authorization
   */
  function buildMercureUrl(topics: string[]): string {
    const url = new URL(mercureConfig.hubUrl)
    
    // Add topics as query parameters
    topics.forEach(topic => {
      url.searchParams.append('topic', topic)
    })

    // Add authorization if available
    const token = auth.token.value
    if (token) {
      url.searchParams.append('authorization', `Bearer ${token}`)
    }

    return url.toString()
  }

  /**
   * Parse incoming SSE message
   */
  function parseMessage(event: MessageEvent): MercureEvent | null {
    try {
      const data = JSON.parse(event.data)
      
      // Handle different message formats
      if (data.type && data.data) {
        // Standard webhook event format
        return {
          type: data.type,
          data: data.data,
          timestamp: data.timestamp || new Date().toISOString(),
          accountId: data.accountId,
          messageId: data.messageId
        }
      } else if (data['@type']) {
        // JSON-LD format from API Platform
        return {
          type: 'message.received', // Default type
          data: data,
          timestamp: new Date().toISOString()
        }
      } else {
        // Raw data
        return {
          type: 'message.received',
          data: data,
          timestamp: new Date().toISOString()
        }
      }
    } catch (parseError) {
      console.warn('Failed to parse SSE message:', parseError, event.data)
      return null
    }
  }

  /**
   * Handle incoming SSE messages
   */
  function handleMessage(event: MessageEvent) {
    const mercureEvent = parseMessage(event)
    if (!mercureEvent) return

    // Update heartbeat
    lastHeartbeat.value = new Date()

    // Emit to registered handlers
    const handlers = eventHandlers.value.get(mercureEvent.type) || new Set()
    const allHandlers = eventHandlers.value.get('*') || new Set()

    // Call specific event handlers
    handlers.forEach(handler => {
      try {
        handler(mercureEvent.data, event)
      } catch (error) {
        console.error('Error in Mercure event handler:', error)
      }
    })

    // Call wildcard handlers
    allHandlers.forEach(handler => {
      try {
        handler(mercureEvent, event)
      } catch (error) {
        console.error('Error in Mercure wildcard handler:', error)
      }
    })
  }

  /**
   * Handle SSE connection errors
   */
  function handleError(event: Event) {
    console.error('Mercure SSE error:', event)
    connectionState.value = 'error'
    error.value = 'Connection error occurred'
    
    // Attempt to reconnect
    if (canReconnect.value) {
      scheduleReconnect()
    }
  }

  /**
   * Handle SSE connection open
   */
  function handleOpen(event: Event) {
    console.log('Mercure SSE connected')
    connectionState.value = 'connected'
    reconnectAttempts.value = 0
    lastHeartbeat.value = new Date()
    error.value = null

    // Emit connection status event
    const handlers = eventHandlers.value.get('connection.status') || new Set()
    handlers.forEach(handler => {
      try {
        handler({ status: 'connected' }, event as MessageEvent)
      } catch (error) {
        console.error('Error in connection status handler:', error)
      }
    })
  }

  /**
   * Schedule reconnection attempt
   */
  function scheduleReconnect() {
    if (!canReconnect.value) {
      connectionState.value = 'error'
      error.value = 'Maximum reconnection attempts exceeded'
      return
    }

    connectionState.value = 'reconnecting'
    reconnectAttempts.value++

    const delay = mercureConfig.reconnectInterval * Math.pow(1.5, reconnectAttempts.value - 1)
    
    setTimeout(() => {
      if (connectionState.value === 'reconnecting') {
        connect()
      }
    }, delay)
  }

  /**
   * Connect to Mercure hub
   */
  function connect(topics?: string[]) {
    if (connectionState.value === 'connected' || connectionState.value === 'connecting') {
      return
    }

    const topicsToSubscribe = topics || Array.from(subscribedTopics.value)
    if (topicsToSubscribe.length === 0) {
      console.warn('No topics to subscribe to')
      return
    }

    connectionState.value = 'connecting'
    error.value = null

    try {
      const url = buildMercureUrl(topicsToSubscribe)
      const source = new EventSource(url)

      source.onopen = handleOpen
      source.onerror = handleError
      source.onmessage = handleMessage

      eventSource.value = source
    } catch (connectError) {
      console.error('Failed to create EventSource:', connectError)
      connectionState.value = 'error'
      error.value = 'Failed to establish connection'
    }
  }

  /**
   * Disconnect from Mercure hub
   */
  function disconnect() {
    if (eventSource.value) {
      eventSource.value.close()
      eventSource.value = null
    }

    connectionState.value = 'disconnected'
    reconnectAttempts.value = 0
    lastHeartbeat.value = null
    error.value = null
  }

  /**
   * Subscribe to topic
   */
  function subscribe(topic: string) {
    subscribedTopics.value.add(topic)
    
    // Reconnect if already connected to include new topic
    if (connectionState.value === 'connected') {
      disconnect()
      connect()
    }
  }

  /**
   * Unsubscribe from topic
   */
  function unsubscribe(topic: string) {
    subscribedTopics.value.delete(topic)
    
    // Reconnect if subscribed topics remain and connected
    if (subscribedTopics.value.size > 0 && connectionState.value === 'connected') {
      disconnect()
      connect()
    } else if (subscribedTopics.value.size === 0) {
      disconnect()
    }
  }

  /**
   * Add event handler
   */
  function addEventListener<T = any>(eventType: MercureEventType | '*', handler: MercureEventHandler<T>) {
    if (!eventHandlers.value.has(eventType)) {
      eventHandlers.value.set(eventType, new Set())
    }
    
    eventHandlers.value.get(eventType)!.add(handler as MercureEventHandler)
  }

  /**
   * Remove event handler
   */
  function removeEventListener<T = any>(eventType: MercureEventType | '*', handler: MercureEventHandler<T>) {
    const handlers = eventHandlers.value.get(eventType)
    if (handlers) {
      handlers.delete(handler as MercureEventHandler)
      
      if (handlers.size === 0) {
        eventHandlers.value.delete(eventType)
      }
    }
  }

  /**
   * Start heartbeat monitoring
   */
  function startHeartbeat() {
    setInterval(() => {
      if (connectionState.value === 'connected' && lastHeartbeat.value) {
        const now = new Date()
        const timeSinceHeartbeat = now.getTime() - lastHeartbeat.value.getTime()
        
        // If no heartbeat for too long, reconnect
        if (timeSinceHeartbeat > mercureConfig.heartbeatInterval * 2) {
          console.warn('Mercure heartbeat timeout, reconnecting...')
          disconnect()
          scheduleReconnect()
        }
      }
    }, mercureConfig.heartbeatInterval)
  }

  /**
   * Auto-connect if authenticated
   */
  function autoConnect() {
    if (auth.isAuthenticated.value && subscribedTopics.value.size > 0) {
      connect()
    }
  }

  // Clean up on unmount
  onBeforeUnmount(() => {
    disconnect()
  })

  // Start heartbeat monitoring
  if (typeof window !== 'undefined') {
    startHeartbeat()
  }

  return {
    // State
    connectionState: readonly(connectionState),
    isConnected,
    isConnecting,
    subscribedTopics: readonly(subscribedTopics),
    reconnectAttempts: readonly(reconnectAttempts),
    lastHeartbeat: readonly(lastHeartbeat),
    error: readonly(error),

    // Methods
    connect,
    disconnect,
    subscribe,
    unsubscribe,
    addEventListener,
    removeEventListener,
    autoConnect,

    // Configuration
    config: mercureConfig
  }
}

/**
 * Account-specific Mercure subscription
 */
export function useAccountMercure(accountId?: string) {
  const mercure = useMercure()
  const auth = useAuth()

  const currentAccountId = computed(() => accountId || auth.user.value?.id)

  // Auto-subscribe to account topic when authenticated
  watchEffect(() => {
    if (currentAccountId.value && auth.isAuthenticated.value) {
      const topic = `/accounts/${currentAccountId.value}`
      mercure.subscribe(topic)
      
      // Auto-connect if not already connected
      if (!mercure.isConnected.value && !mercure.isConnecting.value) {
        mercure.autoConnect()
      }
    }
  })

  return {
    ...mercure,
    accountId: currentAccountId
  }
}

/**
 * Email-specific Mercure events
 */
export function useEmailMercure() {
  const accountMercure = useAccountMercure()
  
  // Email-specific event handlers
  const onNewMessage = (handler: MercureEventHandler<Message>) => {
    accountMercure.addEventListener('message.received', handler)
  }

  const onMessageRead = (handler: MercureEventHandler<Message>) => {
    accountMercure.addEventListener('message.read', handler)
  }

  const onMessageStarred = (handler: MercureEventHandler<Message>) => {
    accountMercure.addEventListener('message.starred', handler)
  }

  return {
    ...accountMercure,
    onNewMessage,
    onMessageRead,
    onMessageStarred
  }
}

/**
 * Global Mercure provider (for app-wide usage)
 */
export function provideMercure() {
  const mercure = useMercure()
  
  // Auto-connect when authenticated
  const auth = useAuth()
  watch(auth.isAuthenticated, (isAuthenticated) => {
    if (isAuthenticated) {
      mercure.autoConnect()
    } else {
      mercure.disconnect()
    }
  })

  return mercure
}