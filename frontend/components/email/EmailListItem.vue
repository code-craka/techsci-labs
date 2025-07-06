<template>
  <div 
    class="email-list-item group cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
    :class="{
      'bg-blue-50 dark:bg-blue-900/20': selected,
      'font-normal': message.isRead,
      'font-semibold': !message.isRead
    }"
    @click="$emit('click')"
  >
    <div class="flex items-center p-4 space-x-4">
      <!-- Selection Checkbox -->
      <UCheckbox
        :modelValue="selected"
        @update:modelValue="$emit('select')"
        @click.stop
        class="text-primary-600"
      />

      <!-- Flag Button -->
      <button
        @click.stop="$emit('flag')"
        class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
        :class="{
          'text-yellow-500': message.isFlagged,
          'text-gray-400 hover:text-yellow-500': !message.isFlagged
        }"
        aria-label="Toggle flag"
      >
        <Icon 
          :name="message.isFlagged ? 'i-heroicons-flag-solid' : 'i-heroicons-flag'" 
          class="h-4 w-4" 
        />
      </button>

      <!-- Avatar or Sender Initial -->
      <div class="flex-shrink-0">
        <div 
          class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-sm font-medium"
        >
          {{ senderInitial }}
        </div>
      </div>

      <!-- Message Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <!-- Sender and Subject -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
              <!-- Sender Name -->
              <p 
                class="text-sm truncate"
                :class="{
                  'text-gray-900 dark:text-white': !message.isRead,
                  'text-gray-600 dark:text-gray-400': message.isRead
                }"
              >
                {{ senderName }}
              </p>
              
              <!-- Unread Indicator -->
              <div 
                v-if="!message.isRead"
                class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"
              />
            </div>
            
            <!-- Subject -->
            <p 
              class="text-sm truncate mt-1"
              :class="{
                'text-gray-900 dark:text-white': !message.isRead,
                'text-gray-600 dark:text-gray-400': message.isRead
              }"
            >
              {{ message.subject || '(No Subject)' }}
            </p>
          </div>

          <!-- Date and Actions -->
          <div class="flex items-center space-x-2 flex-shrink-0 ml-4">
            <!-- Attachments Indicator -->
            <Icon 
              v-if="hasAttachments"
              name="i-heroicons-paper-clip"
              class="h-4 w-4 text-gray-400"
              title="Has attachments"
            />

            <!-- Message Date -->
            <span 
              class="text-xs whitespace-nowrap"
              :class="{
                'text-gray-900 dark:text-white': !message.isRead,
                'text-gray-500 dark:text-gray-400': message.isRead
              }"
            >
              {{ formatDate(message.createdAt) }}
            </span>

            <!-- Quick Actions (shown on hover) -->
            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1">
              <!-- Mark Read/Unread -->
              <button
                @click.stop="$emit('mark-read', !message.isRead)"
                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                :title="message.isRead ? 'Mark as unread' : 'Mark as read'"
              >
                <Icon 
                  :name="message.isRead ? 'i-heroicons-envelope' : 'i-heroicons-envelope-open'"
                  class="h-4 w-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                />
              </button>

              <!-- Delete -->
              <button
                @click.stop="$emit('delete')"
                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                title="Delete message"
              >
                <Icon 
                  name="i-heroicons-trash"
                  class="h-4 w-4 text-gray-400 hover:text-red-500"
                />
              </button>
            </div>
          </div>
        </div>

        <!-- Preview Text -->
        <div v-if="showPreview" class="mt-2">
          <p 
            class="text-xs truncate"
            :class="{
              'text-gray-700 dark:text-gray-300': !message.isRead,
              'text-gray-500 dark:text-gray-400': message.isRead
            }"
          >
            {{ previewText }}
          </p>
        </div>

        <!-- Message Tags/Labels -->
        <div v-if="message.tags && message.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
          <UBadge
            v-for="tag in message.tags.slice(0, 3)"
            :key="tag"
            size="xs"
            variant="soft"
            color="primary"
          >
            {{ tag }}
          </UBadge>
          <UBadge
            v-if="message.tags.length > 3"
            size="xs"
            variant="soft"
            color="gray"
          >
            +{{ message.tags.length - 3 }}
          </UBadge>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Message } from '~/types/api'
import { format, isToday, isYesterday, isThisYear } from 'date-fns'

// Props
interface Props {
  message: Message
  selected?: boolean
  showPreview?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
  showPreview: true
})

// Emits
defineEmits<{
  'click': []
  'select': []
  'flag': []
  'delete': []
  'mark-read': [isRead: boolean]
}>()

// Computed
const senderName = computed(() => {
  if (props.message.from?.name) {
    return props.message.from.name
  }
  if (props.message.from?.email) {
    return props.message.from.email
  }
  return 'Unknown Sender'
})

const senderInitial = computed(() => {
  const name = senderName.value
  if (name.includes('@')) {
    // Email address - use first letter
    return name.charAt(0).toUpperCase()
  }
  // Name - use first letter of first word
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase()
  }
  return name.charAt(0).toUpperCase()
})

const hasAttachments = computed(() => {
  return props.message.attachments && props.message.attachments.length > 0
})

const previewText = computed(() => {
  if (!props.message.textBody) return ''
  
  // Remove HTML tags and extra whitespace
  const cleanText = props.message.textBody
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
  
  // Return first 150 characters
  return cleanText.length > 150 
    ? cleanText.substring(0, 150) + '...'
    : cleanText
})

// Date formatting
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  
  if (isToday(date)) {
    return format(date, 'HH:mm')
  }
  
  if (isYesterday(date)) {
    return 'Yesterday'
  }
  
  if (isThisYear(date)) {
    return format(date, 'MMM dd')
  }
  
  return format(date, 'MM/dd/yy')
}
</script>

<style scoped>
.email-list-item {
  /* Custom styles for email list items */
}
</style>