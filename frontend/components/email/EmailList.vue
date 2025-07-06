<template>
  <div class="email-list h-full flex flex-col">
    <!-- List Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div class="flex items-center space-x-4">
        <!-- Select All Checkbox -->
        <UCheckbox
          v-model="selectAll"
          :indeterminate="selectedMessages.length > 0 && selectedMessages.length < messages.length"
          @change="handleSelectAll"
          class="text-primary-600"
        />
        
        <!-- Bulk Actions -->
        <div v-if="selectedMessages.length > 0" class="flex items-center space-x-2">
          <UButton
            icon="i-heroicons-envelope-open"
            size="sm"
            variant="outline"
            @click="bulkMarkAsRead(true)"
            :loading="isLoading"
          >
            Mark Read
          </UButton>
          <UButton
            icon="i-heroicons-envelope"
            size="sm"
            variant="outline"
            @click="bulkMarkAsRead(false)"
            :loading="isLoading"
          >
            Mark Unread
          </UButton>
          <UButton
            icon="i-heroicons-archive-box"
            size="sm"
            variant="outline"
            @click="showMoveDialog = true"
            :loading="isLoading"
          >
            Move
          </UButton>
          <UButton
            icon="i-heroicons-trash"
            size="sm"
            color="red"
            variant="outline"
            @click="showDeleteDialog = true"
            :loading="isLoading"
          >
            Delete ({{ selectedMessages.length }})
          </UButton>
        </div>

        <!-- Message Count -->
        <span v-else class="text-sm text-gray-500 dark:text-gray-400">
          {{ totalMessages }} {{ totalMessages === 1 ? 'message' : 'messages' }}
          <span v-if="unreadCount > 0" class="text-primary-600 font-medium">
            ({{ unreadCount }} unread)
          </span>
        </span>
      </div>

      <!-- Actions -->
      <div class="flex items-center space-x-2">
        <!-- Sort Dropdown -->
        <UDropdown :items="sortOptions" :popper="{ placement: 'bottom-end' }">
          <UButton
            icon="i-heroicons-bars-arrow-down"
            size="sm"
            variant="outline"
            color="gray"
          >
            Sort
          </UButton>
        </UDropdown>

        <!-- Refresh Button -->
        <UButton
          icon="i-heroicons-arrow-path"
          size="sm"
          variant="ghost"
          color="gray"
          @click="refreshMessages"
          :loading="isLoading"
          aria-label="Refresh messages"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading && messages.length === 0" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <Icon name="i-heroicons-arrow-path" class="h-8 w-8 text-gray-400 animate-spin mx-auto" />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading messages...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="messages.length === 0" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <Icon name="i-heroicons-inbox" class="h-12 w-12 text-gray-400 mx-auto" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No messages</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          This mailbox is empty.
        </p>
      </div>
    </div>

    <!-- Messages List -->
    <div v-else class="flex-1 overflow-auto">
      <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <EmailListItem
          v-for="message in messages"
          :key="message.id"
          :message="message as any"
          :selected="selectedMessages.includes(message.id)"
          @select="toggleSelect(message.id)"
          @click="selectMessage(message as any)"
          @flag="toggleFlag(message.id)"
          @delete="confirmDelete(message.id)"
          @mark-read="markAsRead(message.id, $event)"
        />
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ startItem }} to {{ endItem }} of {{ totalMessages }} results
        </p>
        <div class="flex items-center space-x-2">
          <UButton
            icon="i-heroicons-chevron-left"
            size="sm"
            variant="outline"
            :disabled="currentPage === 1"
            @click="changePage(currentPage - 1)"
          >
            Previous
          </UButton>
          <span class="text-sm text-gray-700 dark:text-gray-300">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <UButton
            icon="i-heroicons-chevron-right"
            size="sm"
            variant="outline"
            :disabled="currentPage === totalPages"
            @click="changePage(currentPage + 1)"
          >
            Next
          </UButton>
        </div>
      </div>
    </div>

    <!-- Move Dialog -->
    <UModal v-model="showMoveDialog" :ui="{ width: 'sm:max-w-md' }">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          Move Messages
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          Move {{ selectedMessages.length }} selected {{ selectedMessages.length === 1 ? 'message' : 'messages' }} to:
        </p>
        <USelect
          v-model="moveToMailbox"
          :options="mailboxOptions"
          option-attribute="name"
          value-attribute="id"
          placeholder="Select mailbox"
          class="mb-4"
        />
        <div class="flex justify-end space-x-3">
          <UButton
            variant="outline"
            @click="showMoveDialog = false"
          >
            Cancel
          </UButton>
          <UButton
            color="primary"
            :disabled="!moveToMailbox"
            :loading="isLoading"
            @click="handleBulkMove"
          >
            Move
          </UButton>
        </div>
      </div>
    </UModal>

    <!-- Delete Confirmation Dialog -->
    <UModal v-model="showDeleteDialog" :ui="{ width: 'sm:max-w-md' }">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <Icon name="i-heroicons-exclamation-triangle" class="h-6 w-6 text-red-500 mr-3" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Delete Messages
          </h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          Are you sure you want to delete {{ selectedMessages.length }} selected 
          {{ selectedMessages.length === 1 ? 'message' : 'messages' }}? This action cannot be undone.
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
            :loading="isLoading"
            @click="handleBulkDelete"
          >
            Delete
          </UButton>
        </div>
      </div>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { Message, Mailbox } from '~/types/api'
import { useEmail } from '~/composables/useEmail'

// Props
interface Props {
  mailbox?: Mailbox
  searchQuery?: string
}

const props = withDefaults(defineProps<Props>(), {
  mailbox: undefined,
  searchQuery: ''
})

// Emits
const emit = defineEmits<{
  'message-selected': [message: Message]
}>()

// Composables
const email = useEmail()
const toast = useToast()

// State
const selectedMessages = ref<string[]>([])
const selectAll = ref(false)
const showMoveDialog = ref(false)
const showDeleteDialog = ref(false)
const moveToMailbox = ref('')

// Computed
const messages = computed(() => email.messages.value)
const mailboxes = computed(() => email.mailboxes.value)
const isLoading = computed(() => email.isLoading.value)
const currentPage = computed(() => email.pagination.value.currentPage)
const pageSize = computed(() => email.pagination.value.pageSize)
const totalMessages = computed(() => email.pagination.value.totalItems)
const totalPages = computed(() => email.pagination.value.totalPages)
const unreadCount = computed(() => email.unreadCount.value)

const startItem = computed(() => {
  return ((currentPage.value - 1) * pageSize.value) + 1
})

const endItem = computed(() => {
  return Math.min(currentPage.value * pageSize.value, totalMessages.value)
})

const mailboxOptions = computed(() => {
  return mailboxes.value
    .filter(mb => mb.id !== props.mailbox?.id)
    .map(mb => ({
      id: mb.id,
      name: mb.displayName || mb.name,
      value: mb.id
    }))
})

// Sort options
const sortOptions = [
  [{
    label: 'Newest First',
    icon: 'i-heroicons-arrow-down',
    click: () => changeSortOrder('-createdAt')
  }],
  [{
    label: 'Oldest First',
    icon: 'i-heroicons-arrow-up',
    click: () => changeSortOrder('createdAt')
  }],
  [{
    label: 'Subject A-Z',
    icon: 'i-heroicons-bars-3-bottom-left',
    click: () => changeSortOrder('subject')
  }],
  [{
    label: 'Sender A-Z',
    icon: 'i-heroicons-user',
    click: () => changeSortOrder('from.name')
  }]
]

// Methods
const toggleSelect = (messageId: string) => {
  const index = selectedMessages.value.indexOf(messageId)
  if (index === -1) {
    selectedMessages.value.push(messageId)
  } else {
    selectedMessages.value.splice(index, 1)
  }
  updateSelectAll()
}

const handleSelectAll = () => {
  if (selectAll.value) {
    selectedMessages.value = messages.value.map(m => m.id)
  } else {
    selectedMessages.value = []
  }
}

const updateSelectAll = () => {
  if (selectedMessages.value.length === 0) {
    selectAll.value = false
  } else if (selectedMessages.value.length === messages.value.length) {
    selectAll.value = true
  }
}

const selectMessage = (message: Message) => {
  emit('message-selected', message)
}

const refreshMessages = async () => {
  try {
    await email.loadMessages(props.mailbox?.id)
    selectedMessages.value = []
    selectAll.value = false
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to refresh messages',
      color: 'red'
    })
  }
}

const changePage = async (page: number) => {
  try {
    await email.loadMessages(props.mailbox?.id, page)
    selectedMessages.value = []
    selectAll.value = false
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to load page',
      color: 'red'
    })
  }
}

const changeSortOrder = (sortBy: string) => {
  // TODO: Implement sort functionality
  console.log('Sort by:', sortBy)
}

const bulkMarkAsRead = async (isRead: boolean) => {
  try {
    await email.bulkMarkAsRead(selectedMessages.value, isRead)
    selectedMessages.value = []
    selectAll.value = false
    toast.add({
      title: 'Success',
      description: `Messages marked as ${isRead ? 'read' : 'unread'}`,
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to update messages',
      color: 'red'
    })
  }
}

const handleBulkMove = async () => {
  try {
    await email.bulkMove(selectedMessages.value, moveToMailbox.value)
    selectedMessages.value = []
    selectAll.value = false
    showMoveDialog.value = false
    moveToMailbox.value = ''
    toast.add({
      title: 'Success',
      description: 'Messages moved successfully',
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to move messages',
      color: 'red'
    })
  }
}

const handleBulkDelete = async () => {
  try {
    await email.bulkDelete(selectedMessages.value)
    selectedMessages.value = []
    selectAll.value = false
    showDeleteDialog.value = false
    toast.add({
      title: 'Success',
      description: 'Messages deleted successfully',
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to delete messages',
      color: 'red'
    })
  }
}

const markAsRead = async (messageId: string, isRead: boolean) => {
  try {
    await email.markAsRead(messageId, isRead)
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to update message',
      color: 'red'
    })
  }
}

const toggleFlag = async (messageId: string) => {
  try {
    await email.toggleFlag(messageId)
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to toggle flag',
      color: 'red'
    })
  }
}

const confirmDelete = (messageId: string) => {
  selectedMessages.value = [messageId]
  showDeleteDialog.value = true
}

// Watch for mailbox changes
watch(() => props.mailbox, (newMailbox) => {
  if (newMailbox) {
    email.setCurrentMailbox(newMailbox)
    refreshMessages()
  }
}, { immediate: true })

// Watch for search query changes
watch(() => props.searchQuery, (newQuery) => {
  if (newQuery) {
    // Implement search functionality
    console.log('Search query:', newQuery)
  }
})

// Clear selection when messages change
watch(() => messages.value, () => {
  selectedMessages.value = []
  selectAll.value = false
})
</script>

<style scoped>
.email-list {
  /* Custom styles for email list */
}
</style>