<template>
  <div class="email-viewer h-full flex flex-col bg-white dark:bg-gray-900">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <Icon name="i-heroicons-arrow-path" class="h-8 w-8 text-gray-400 animate-spin mx-auto" />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading message...</p>
      </div>
    </div>

    <!-- No Message Selected -->
    <div v-else-if="!message" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <Icon name="i-heroicons-envelope-open" class="h-12 w-12 text-gray-400 mx-auto" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No message selected</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Select a message from the list to view its contents.
        </p>
      </div>
    </div>

    <!-- Message Content -->
    <div v-else class="flex flex-col h-full">
      <!-- Header -->
      <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="p-6">
          <!-- Action Buttons -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
              <!-- Back Button (mobile) -->
              <UButton
                icon="i-heroicons-arrow-left"
                size="sm"
                variant="ghost"
                color="gray"
                @click="$emit('close')"
                class="lg:hidden"
              >
                Back
              </UButton>
            </div>

            <div class="flex items-center space-x-2">
              <!-- Flag Toggle -->
              <UButton
                :icon="message.isFlagged ? 'i-heroicons-flag-solid' : 'i-heroicons-flag'"
                size="sm"
                variant="ghost"
                :color="message.isFlagged ? 'yellow' : 'gray'"
                @click="toggleFlag"
                aria-label="Toggle flag"
              />

              <!-- Mark Read/Unread -->
              <UButton
                :icon="message.isRead ? 'i-heroicons-envelope' : 'i-heroicons-envelope-open'"
                size="sm"
                variant="ghost"
                color="gray"
                @click="toggleRead"
                :aria-label="message.isRead ? 'Mark as unread' : 'Mark as read'"
              />

              <!-- Reply -->
              <UButton
                icon="i-heroicons-arrow-uturn-left"
                size="sm"
                variant="outline"
                @click="reply"
              >
                Reply
              </UButton>

              <!-- Forward -->
              <UButton
                icon="i-heroicons-arrow-uturn-right"
                size="sm"
                variant="outline"
                @click="forward"
              >
                Forward
              </UButton>

              <!-- More Actions -->
              <UDropdown :items="moreActions" :popper="{ placement: 'bottom-end' }">
                <UButton
                  icon="i-heroicons-ellipsis-vertical"
                  size="sm"
                  variant="ghost"
                  color="gray"
                />
              </UDropdown>
            </div>
          </div>

          <!-- Subject -->
          <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            {{ message.subject || '(No Subject)' }}
          </h1>

          <!-- Message Info -->
          <div class="space-y-3">
            <!-- From -->
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-sm font-medium">
                  {{ senderInitial }}
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ senderName }}
                  </p>
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    &lt;{{ message.from?.email }}&gt;
                  </span>
                </div>
                <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                  <span>To: {{ recipientsList }}</span>
                  <span>{{ formatFullDate(message.createdAt) }}</span>
                </div>
              </div>
            </div>

            <!-- Additional Recipients (if any) -->
            <div v-if="ccRecipients.length > 0 || bccRecipients.length > 0" class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
              <div v-if="ccRecipients.length > 0">
                <span class="font-medium">CC:</span> {{ ccRecipients.join(', ') }}
              </div>
              <div v-if="bccRecipients.length > 0">
                <span class="font-medium">BCC:</span> {{ bccRecipients.join(', ') }}
              </div>
            </div>

            <!-- Message Tags -->
            <div v-if="message.tags && message.tags.length > 0" class="flex flex-wrap gap-1">
              <UBadge
                v-for="tag in message.tags"
                :key="tag"
                size="xs"
                variant="soft"
                color="primary"
              >
                {{ tag }}
              </UBadge>
            </div>
          </div>
        </div>
      </div>

      <!-- Message Body -->
      <div class="flex-1 overflow-auto">
        <div class="p-6">
          <!-- Security Warning (if any) -->
          <div v-if="hasSecurityWarnings" class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <div class="flex items-center">
              <Icon name="i-heroicons-exclamation-triangle" class="h-5 w-5 text-yellow-500 mr-2" />
              <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Security Warning</span>
            </div>
            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
              This message contains potentially unsafe content. Exercise caution with links and attachments.
            </p>
          </div>

          <!-- Attachments -->
          <div v-if="hasAttachments" class="mb-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
              Attachments ({{ message.attachments.length }})
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <EmailAttachments
                v-for="attachment in message.attachments"
                :key="attachment.id"
                :attachment="attachment"
                @download="downloadAttachment"
                @preview="previewAttachment"
              />
            </div>
          </div>

          <!-- Message Content Tabs -->
          <div class="mb-4">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <nav class="-mb-px flex space-x-8">
                <button
                  v-for="tab in contentTabs"
                  :key="tab.key"
                  @click="activeTab = tab.key"
                  class="py-2 px-1 border-b-2 font-medium text-sm"
                  :class="{
                    'border-primary-500 text-primary-600 dark:text-primary-400': activeTab === tab.key,
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': activeTab !== tab.key
                  }"
                >
                  {{ tab.label }}
                </button>
              </nav>
            </div>
          </div>

          <!-- Content Display -->
          <div class="message-content">
            <!-- HTML Content -->
            <div v-if="activeTab === 'html' && message.htmlBody" class="prose dark:prose-invert max-w-none">
              <div v-html="sanitizedHtmlContent" />
            </div>

            <!-- Plain Text Content -->
            <div v-else-if="activeTab === 'text' && message.textBody" class="whitespace-pre-wrap font-mono text-sm text-gray-900 dark:text-gray-100">
              {{ message.textBody }}
            </div>

            <!-- Raw Source -->
            <div v-else-if="activeTab === 'raw'" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono">{{ rawSource }}</pre>
            </div>

            <!-- No Content -->
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
              <Icon name="i-heroicons-document-text" class="h-8 w-8 mx-auto mb-2" />
              <p class="text-sm">No content available for this view</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <UModal v-model="showDeleteDialog" :ui="{ width: 'sm:max-w-md' }">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <Icon name="i-heroicons-exclamation-triangle" class="h-6 w-6 text-red-500 mr-3" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Delete Message
          </h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          Are you sure you want to delete this message? This action cannot be undone.
        </p>
        <div class="flex justify-end space-x-3">
          <UButton
            variant="outline"
            @click="showDeleteDialog = false"
          >
            Cancel
          </UButton>
          <UButton
            color="red"
            :loading="isDeleting"
            @click="deleteMessage"
          >
            Delete
          </UButton>
        </div>
      </div>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { Message, Attachment, EmailAddress } from '~/types/api'
import { format } from 'date-fns'
import DOMPurify from 'dompurify'

// Props
interface Props {
  message?: Message
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  message: undefined,
  isLoading: false
})

// Emits
const emit = defineEmits<{
  'close': []
  'reply': [message: Message]
  'forward': [message: Message]
  'delete': [messageId: string]
  'flag': [messageId: string]
  'mark-read': [messageId: string, isRead: boolean]
}>()

// Composables
const email = useEmail()
const toast = useToast()

// State
const activeTab = ref('html')
const showDeleteDialog = ref(false)
const isDeleting = ref(false)

// Computed
const senderName = computed(() => {
  if (!props.message?.from) return 'Unknown Sender'
  return props.message.from.name || props.message.from.email || 'Unknown Sender'
})

const senderInitial = computed(() => {
  const name = senderName.value
  if (name.includes('@')) {
    return name.charAt(0).toUpperCase()
  }
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase()
  }
  return name.charAt(0).toUpperCase()
})

const recipientsList = computed(() => {
  if (!props.message?.to) return 'Unknown Recipients'
  
  if (typeof props.message.to === 'string') {
    return props.message.to
  }
  
  if (Array.isArray(props.message.to)) {
    return props.message.to.map(recipient => 
      typeof recipient === 'string' ? recipient : recipient.email
    ).join(', ')
  }
  
  // Single EmailAddress object
  return (props.message.to as EmailAddress).email
})

const ccRecipients = computed(() => {
  if (!props.message?.cc) return []
  
  if (typeof props.message.cc === 'string') {
    return [props.message.cc]
  }
  
  if (Array.isArray(props.message.cc)) {
    return props.message.cc.map(recipient => 
      typeof recipient === 'string' ? recipient : recipient.email
    )
  }
  
  // Single EmailAddress object
  return [(props.message.cc as EmailAddress).email]
})

const bccRecipients = computed(() => {
  if (!props.message?.bcc) return []
  
  if (typeof props.message.bcc === 'string') {
    return [props.message.bcc]
  }
  
  if (Array.isArray(props.message.bcc)) {
    return props.message.bcc.map(recipient => 
      typeof recipient === 'string' ? recipient : recipient.email
    )
  }
  
  // Single EmailAddress object
  return [(props.message.bcc as EmailAddress).email]
})

const hasAttachments = computed(() => {
  return props.message?.attachments && props.message.attachments.length > 0
})

const hasSecurityWarnings = computed(() => {
  // Simple security checks - can be enhanced
  if (!props.message) return false
  
  const content = (props.message.htmlBody || props.message.textBody || '').toLowerCase()
  const warnings = [
    'urgent action required',
    'verify your account',
    'click here immediately',
    'suspended account'
  ]
  
  return warnings.some(warning => content.includes(warning))
})

const sanitizedHtmlContent = computed(() => {
  if (!props.message?.htmlBody) return ''
  
  // Sanitize HTML content to prevent XSS
  return DOMPurify.sanitize(props.message.htmlBody, {
    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'div', 'span'],
    ALLOWED_ATTR: ['style', 'class'],
    ALLOW_DATA_ATTR: false
  })
})

const rawSource = computed(() => {
  if (!props.message) return ''
  
  // Construct raw email source (mock implementation)
  let raw = `Subject: ${props.message.subject || '(No Subject)'}\n`
  raw += `From: ${props.message.from?.email || 'unknown@example.com'}\n`
  raw += `To: ${recipientsList.value}\n`
  raw += `Date: ${formatFullDate(props.message.createdAt)}\n`
  
  if (props.message.cc && ccRecipients.value.length > 0) {
    raw += `CC: ${ccRecipients.value.join(', ')}\n`
  }
  
  raw += `\n${props.message.textBody || props.message.htmlBody || '(No content)'}`
  
  return raw
})

const contentTabs = computed(() => {
  const tabs = []
  
  if (props.message?.htmlBody) {
    tabs.push({ key: 'html', label: 'HTML' })
  }
  
  if (props.message?.textBody) {
    tabs.push({ key: 'text', label: 'Plain Text' })
  }
  
  tabs.push({ key: 'raw', label: 'Raw' })
  
  return tabs
})

const moreActions = computed(() => [
  [{
    label: 'Move to...',
    icon: 'i-heroicons-archive-box',
    click: () => moveMessage()
  }],
  [{
    label: 'Copy Message ID',
    icon: 'i-heroicons-clipboard',
    click: () => copyMessageId()
  }],
  [{
    label: 'Delete',
    icon: 'i-heroicons-trash',
    click: () => showDeleteDialog.value = true
  }]
])

// Methods
const formatFullDate = (dateString: string): string => {
  return format(new Date(dateString), 'PPpp')
}

const toggleFlag = async () => {
  if (!props.message) return
  
  try {
    await email.toggleFlag(props.message.id)
    emit('flag', props.message.id)
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to toggle flag',
      color: 'red'
    })
  }
}

const toggleRead = async () => {
  if (!props.message) return
  
  try {
    await email.markAsRead(props.message.id, !props.message.isRead)
    emit('mark-read', props.message.id, !props.message.isRead)
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to update message',
      color: 'red'
    })
  }
}

const reply = () => {
  if (props.message) {
    emit('reply', props.message)
  }
}

const forward = () => {
  if (props.message) {
    emit('forward', props.message)
  }
}

const deleteMessage = async () => {
  if (!props.message) return
  
  isDeleting.value = true
  try {
    await email.deleteMessage(props.message.id)
    emit('delete', props.message.id)
    showDeleteDialog.value = false
    toast.add({
      title: 'Success',
      description: 'Message deleted successfully',
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to delete message',
      color: 'red'
    })
  } finally {
    isDeleting.value = false
  }
}

const moveMessage = () => {
  // TODO: Implement move functionality
  console.log('Move message')
}

const copyMessageId = async () => {
  if (!props.message) return
  
  try {
    await navigator.clipboard.writeText(props.message.id)
    toast.add({
      title: 'Copied',
      description: 'Message ID copied to clipboard',
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to copy message ID',
      color: 'red'
    })
  }
}

const downloadAttachment = async (attachment: Attachment) => {
  try {
    const blob = await email.downloadAttachment(attachment.id)
    
    // Create download link
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = attachment.filename
    link.click()
    
    // Cleanup
    window.URL.revokeObjectURL(url)
    
    toast.add({
      title: 'Downloaded',
      description: `${attachment.filename} downloaded successfully`,
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to download attachment',
      color: 'red'
    })
  }
}

const previewAttachment = (attachment: Attachment) => {
  // TODO: Implement attachment preview
  console.log('Preview attachment:', attachment)
}

// Set default tab based on available content
watch(() => props.message, (newMessage) => {
  if (newMessage) {
    if (newMessage.htmlBody) {
      activeTab.value = 'html'
    } else if (newMessage.textBody) {
      activeTab.value = 'text'
    } else {
      activeTab.value = 'raw'
    }
  }
}, { immediate: true })
</script>

<style scoped>
.message-content {
  /* Custom styles for message content */
}

.prose img {
  max-width: 100%;
  height: auto;
}
</style>