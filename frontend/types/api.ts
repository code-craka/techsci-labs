/**
 * API Types for TechSci Labs Email Testing Platform
 * Based on the Symfony API Platform backend implementation
 */

// Base API Response Structure
export interface ApiResponse<T = unknown> {
  '@context': string
  '@id': string
  '@type': string
  data?: T
}

export interface ApiCollection<T = unknown> {
  '@context': string
  '@id': string
  '@type': string
  'hydra:member': T[]
  'hydra:totalItems': number
  'hydra:view'?: {
    '@id': string
    '@type': string
    'hydra:first'?: string
    'hydra:last'?: string
    'hydra:previous'?: string
    'hydra:next'?: string
  }
}

export interface ApiError {
  '@context': string
  '@type': string
  'hydra:title': string
  'hydra:description': string
  detail?: string
  status?: number
  violations?: Array<{
    propertyPath: string
    message: string
    code?: string
  }>
}

// Authentication Types
export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
  refresh_token: string
  user: EmailAccount
}

export interface RefreshTokenRequest {
  refresh_token: string
}

export interface RefreshTokenResponse {
  token: string
  refresh_token: string
}

// Domain Entity Types
export interface Domain {
  '@id': string
  id: string
  name: string
  isActive: boolean
  isCatchAll: boolean
  dnsRecords: readonly DnsRecord[]
  smtpSettings: SmtpSettings
  accountCount: number
  messageCount: number
  quotaUsed: number
  quotaLimit: number
  createdAt: string
  updatedAt: string
  // Additional computed properties for domain details view
  status: 'active' | 'inactive' | 'pending' | 'error'
  emailCount: number
  messagesCount: number
  storageUsed: number
  type: 'domain' | 'subdomain' | 'alias'
  catchAll: boolean // Alias for isCatchAll to match component usage
  lastCheck: string
}

export interface DnsRecord {
  type: 'MX' | 'TXT' | 'A' | 'CNAME'
  name: string
  value: string
  priority?: number
  ttl: number
}

export interface SmtpSettings {
  hostname: string
  port: number
  encryption: 'tls' | 'ssl' | 'none'
  username?: string
  password?: string
}

// Email Account Entity Types
export interface EmailAccount {
  '@id': string
  id: string
  email: string
  password?: string
  domain: Domain | string
  displayName?: string
  isActive: boolean
  isCatchAll: boolean
  quota: number
  quotaUsed: number
  lastLoginAt?: string
  mailboxes: readonly Mailbox[] | readonly string[]
  createdAt: string
  updatedAt: string
}

// Mailbox Entity Types
export interface Mailbox {
  '@id': string
  id: string
  name: string
  displayName?: string
  account: EmailAccount | string
  messageCount: number
  unreadCount: number
  isTrash: boolean
  isSpam: boolean
  isDrafts: boolean
  isSent: boolean
  isArchive: boolean
  createdAt: string
  updatedAt: string
}

// Message Entity Types
export interface Message {
  '@id': string
  id: string
  messageId: string
  account: EmailAccount | string
  mailbox: Mailbox | string
  from: EmailAddress
  to: readonly EmailAddress[] | EmailAddress[] | EmailAddress | string
  cc?: readonly EmailAddress[] | EmailAddress[] | EmailAddress | string
  bcc?: readonly EmailAddress[] | EmailAddress[] | EmailAddress | string
  replyTo?: readonly EmailAddress[] | EmailAddress[]
  subject: string
  body: string
  textBody?: string
  htmlBody?: string
  isRead: boolean
  isStarred: boolean
  isFlagged: boolean
  isSpam: boolean
  priority: 'low' | 'normal' | 'high'
  size: number
  attachments: readonly Attachment[] | Attachment[]
  headers: Record<string, string>
  tags?: string[]
  receivedAt: string
  createdAt: string
}

export interface EmailAddress {
  email: string
  name?: string
  isPlusAlias?: boolean
  originalEmail?: string
}

// Attachment Entity Types
export interface Attachment {
  '@id': string
  id: string
  message: Message | string
  filename: string
  originalFilename: string
  mimeType: string
  size: number
  isInline: boolean
  contentId?: string
  isVirusFree: boolean
  isVirus: boolean
  isSecure: boolean
  downloadUrl: string
  createdAt: string
}

// Token Entity Types
export interface Token {
  '@id': string
  id: string
  account: EmailAccount | string
  name: string
  token: string
  scopes: string[]
  lastUsedAt?: string
  expiresAt?: string
  isActive: boolean
  createdAt: string
}

// Request Types
export interface CreateDomainRequest {
  name: string
  isActive?: boolean
  isCatchAll?: boolean
}

export interface UpdateDomainRequest {
  name?: string
  isActive?: boolean
  isCatchAll?: boolean
  smtpSettings?: Partial<SmtpSettings>
}

export interface CreateAccountRequest {
  email: string
  password: string
  domain: string
  displayName?: string
  isActive?: boolean
  isCatchAll?: boolean
  quota?: number
}

export interface UpdateAccountRequest {
  displayName?: string
  isActive?: boolean
  isCatchAll?: boolean
  quota?: number
  password?: string
}

export interface SendMessageRequest {
  from: string
  to: EmailAddress[]
  cc?: EmailAddress[]
  bcc?: EmailAddress[]
  subject: string
  body: string
  htmlBody?: string
  attachments?: string[]
}

export interface CreateTokenRequest {
  name: string
  scopes: string[]
  expiresAt?: string
}

// Filter and Pagination Types
export interface PaginationParams {
  page?: number
  itemsPerPage?: number
}

export interface MessageFilters extends PaginationParams {
  mailbox?: string
  isRead?: boolean
  isStarred?: boolean
  isSpam?: boolean
  from?: string
  to?: string
  subject?: string
  dateFrom?: string
  dateTo?: string
  hasAttachments?: boolean
}

export interface AccountFilters extends PaginationParams {
  domain?: string
  isActive?: boolean
  isCatchAll?: boolean
  search?: string
}

export interface DomainFilters extends PaginationParams {
  isActive?: boolean
  isCatchAll?: boolean
  search?: string
}

// Statistics Types
export interface DomainStats {
  accountCount: number
  messageCount: number
  quotaUsed: number
  quotaLimit: number
  dailyMessages: Array<{
    date: string
    count: number
  }>
}

export interface AccountStats {
  messageCount: number
  unreadCount: number
  quotaUsed: number
  quotaLimit: number
  lastActivity?: string
}

export interface SystemStats {
  totalDomains: number
  totalAccounts: number
  totalMessages: number
  storageUsed: number
  storageLimit: number
}

// Webhook Types
export interface WebhookEvent {
  type: 'message.received' | 'account.created' | 'domain.created'
  data: Message | EmailAccount | Domain
  timestamp: string
}

// Pagination Response Types
export interface PaginatedResponse<T = unknown> {
  data: T[]
  pagination: {
    page: number
    limit: number
    total: number
    pages: number
  }
}

// Search and Filter Types
export interface MessageSearchRequest {
  query?: string
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
  page?: number
  limit?: number
}

export interface MessageCreateRequest {
  from: string
  to: EmailAddress[]
  cc?: EmailAddress[]
  bcc?: EmailAddress[]
  subject: string
  body: string
  textBody?: string
  htmlBody?: string
  attachments?: string[]
  priority?: 'low' | 'normal' | 'high'
}

export interface MessageUpdateRequest {
  isRead?: boolean
  isFlagged?: boolean
  isSpam?: boolean
  mailbox?: string
  tags?: string[]
}