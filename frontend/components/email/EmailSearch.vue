<template>
  <div class="email-search">
    <!-- Search Input -->
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <Icon 
          name="i-heroicons-magnifying-glass" 
          class="h-5 w-5 text-gray-400" 
        />
      </div>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search messages..."
        class="block w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
        @keyup.enter="performSearch"
        @keyup.escape="clearSearch"
      />
      
      <!-- Clear Button -->
      <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
        <button
          @click="clearSearch"
          class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
          aria-label="Clear search"
        >
          <Icon name="i-heroicons-x-mark" class="h-4 w-4 text-gray-400" />
        </button>
      </div>
    </div>

    <!-- Search Suggestions -->
    <div v-if="showSuggestions && suggestions.length > 0" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-auto">
      <ul class="py-1">
        <li
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @click="selectSuggestion(suggestion)"
          class="px-3 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
        >
          <div class="flex items-center">
            <Icon :name="suggestion.icon" class="h-4 w-4 text-gray-400 mr-2" />
            <span>{{ suggestion.text }}</span>
          </div>
        </li>
      </ul>
    </div>

    <!-- Advanced Search Toggle -->
    <div class="mt-4">
      <button
        @click="showAdvanced = !showAdvanced"
        class="flex items-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 transition-colors"
      >
        <Icon 
          :name="showAdvanced ? 'i-heroicons-chevron-up' : 'i-heroicons-chevron-down'"
          class="h-4 w-4 mr-1"
        />
        Advanced Search
      </button>
    </div>

    <!-- Advanced Search Filters -->
    <div v-show="showAdvanced" class="mt-4 space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <!-- From/To Filters -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            From
          </label>
          <input
            v-model="filters.from"
            type="email"
            placeholder="sender@example.com"
            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            To
          </label>
          <input
            v-model="filters.to"
            type="email"
            placeholder="recipient@example.com"
            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Subject Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Subject
        </label>
        <input
          v-model="filters.subject"
          type="text"
          placeholder="Email subject contains..."
          class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
        />
      </div>

      <!-- Date Range -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            From Date
          </label>
          <input
            v-model="filters.dateFrom"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            To Date
          </label>
          <input
            v-model="filters.dateTo"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Mailbox Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Mailbox
        </label>
        <select
          v-model="filters.mailbox"
          class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
        >
          <option value="">All Mailboxes</option>
          <option v-for="mailbox in mailboxes" :key="mailbox.id" :value="mailbox.id">
            {{ mailbox.displayName || mailbox.name }}
          </option>
        </select>
      </div>

      <!-- Quick Filters -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Quick Filters
        </label>
        <div class="flex flex-wrap gap-2">
          <UButton
            v-for="quickFilter in quickFilters"
            :key="quickFilter.key"
            size="sm"
            :variant="isQuickFilterActive(quickFilter.key) ? 'solid' : 'outline'"
            :color="isQuickFilterActive(quickFilter.key) ? 'primary' : 'gray'"
            @click="toggleQuickFilter(quickFilter.key)"
          >
            <Icon :name="quickFilter.icon" class="h-4 w-4 mr-1" />
            {{ quickFilter.label }}
          </UButton>
        </div>
      </div>

      <!-- Tags Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Tags (comma separated)
        </label>
        <input
          v-model="tagsInput"
          type="text"
          placeholder="urgent, work, personal"
          class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
        />
      </div>

      <!-- Search Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-600">
        <button
          @click="clearAllFilters"
          class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
        >
          Clear All Filters
        </button>
        
        <div class="flex space-x-2">
          <UButton
            variant="outline"
            @click="showAdvanced = false"
          >
            Cancel
          </UButton>
          <UButton
            color="primary"
            :loading="isSearching"
            @click="performAdvancedSearch"
          >
            Search
          </UButton>
        </div>
      </div>
    </div>

    <!-- Search Results Summary -->
    <div v-if="hasSearchResults" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <Icon name="i-heroicons-magnifying-glass" class="h-5 w-5 text-blue-500 mr-2" />
          <span class="text-sm text-blue-800 dark:text-blue-200">
            Found {{ searchResults.length }} {{ searchResults.length === 1 ? 'message' : 'messages' }}
            <span v-if="currentSearchQuery">for "{{ currentSearchQuery }}"</span>
          </span>
        </div>
        <button
          @click="clearSearch"
          class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors"
        >
          Clear Search
        </button>
      </div>
    </div>

    <!-- Saved Searches -->
    <div v-if="savedSearches.length > 0" class="mt-4">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Saved Searches</h4>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="saved in savedSearches"
          :key="saved.id"
          @click="loadSavedSearch(saved)"
          class="inline-flex items-center px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          <Icon name="i-heroicons-bookmark" class="h-3 w-3 mr-1" />
          {{ saved.name }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Mailbox, Message } from '~/types/api'
import type { EmailFilters } from '~/composables/useEmail'

// Props
interface Props {
  mailboxes?: Mailbox[]
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  mailboxes: () => [],
  placeholder: 'Search messages...'
})

// Emits
const emit = defineEmits<{
  'search': [query: string, filters: EmailFilters]
  'clear': []
  'results': [results: Message[]]
}>()

// Composables
const email = useEmail()

// State
const searchQuery = ref('')
const showAdvanced = ref(false)
const showSuggestions = ref(false)
const currentSearchQuery = ref('')
const tagsInput = ref('')

const filters = ref<EmailFilters>({
  from: '',
  to: '',
  subject: '',
  dateFrom: '',
  dateTo: '',
  mailbox: '',
  hasAttachments: undefined,
  isRead: undefined,
  isFlagged: undefined,
  tags: []
})

const activeQuickFilters = ref<Set<string>>(new Set())

// Computed
const isSearching = computed(() => email.isSearching.value)
const searchResults = computed(() => email.searchResults.value)
const hasSearchResults = computed(() => searchResults.value.length > 0)

const suggestions = computed(() => {
  if (!searchQuery.value || searchQuery.value.length < 2) return []
  
  const query = searchQuery.value.toLowerCase()
  const suggestions = []
  
  // Search in recent senders
  suggestions.push({
    icon: 'i-heroicons-user',
    text: `from:${query}`,
    type: 'from'
  })
  
  // Search in subject
  suggestions.push({
    icon: 'i-heroicons-chat-bubble-left-right',
    text: `subject:${query}`,
    type: 'subject'
  })
  
  // Search in content
  suggestions.push({
    icon: 'i-heroicons-document-text',
    text: `${query}`,
    type: 'content'
  })
  
  return suggestions.slice(0, 5)
})

const quickFilters = [
  {
    key: 'unread',
    label: 'Unread',
    icon: 'i-heroicons-envelope'
  },
  {
    key: 'flagged',
    label: 'Flagged',
    icon: 'i-heroicons-flag'
  },
  {
    key: 'attachments',
    label: 'Has Attachments',
    icon: 'i-heroicons-paper-clip'
  },
  {
    key: 'today',
    label: 'Today',
    icon: 'i-heroicons-calendar-days'
  },
  {
    key: 'week',
    label: 'This Week',
    icon: 'i-heroicons-calendar'
  }
]

// Mock saved searches (would come from backend)
const savedSearches = ref([
  { id: '1', name: 'Urgent emails', query: 'urgent', filters: { tags: ['urgent'] } },
  { id: '2', name: 'Unread from last week', query: '', filters: { isRead: false, dateFrom: getLastWeekDate() } }
])

// Methods
const performSearch = async () => {
  if (!searchQuery.value.trim()) {
    clearSearch()
    return
  }
  
  currentSearchQuery.value = searchQuery.value
  showSuggestions.value = false
  
  const searchFilters: EmailFilters = {
    ...filters.value,
    tags: tagsInput.value ? tagsInput.value.split(',').map(tag => tag.trim()) : []
  }
  
  try {
    const results = await email.searchMessages(searchQuery.value, searchFilters)
    emit('search', searchQuery.value, searchFilters)
    emit('results', results)
  } catch (error) {
    console.error('Search failed:', error)
  }
}

const performAdvancedSearch = async () => {
  const searchFilters: EmailFilters = {
    ...filters.value,
    tags: tagsInput.value ? tagsInput.value.split(',').map(tag => tag.trim()) : [],
    ...getQuickFilterValues()
  }
  
  try {
    const results = await email.searchMessages(searchQuery.value || '', searchFilters)
    currentSearchQuery.value = searchQuery.value || 'Advanced Search'
    emit('search', searchQuery.value || '', searchFilters)
    emit('results', results)
    showAdvanced.value = false
  } catch (error) {
    console.error('Advanced search failed:', error)
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  currentSearchQuery.value = ''
  showSuggestions.value = false
  clearAllFilters()
  email.clearSearchResults()
  emit('clear')
}

const clearAllFilters = () => {
  filters.value = {
    from: '',
    to: '',
    subject: '',
    dateFrom: '',
    dateTo: '',
    mailbox: '',
    hasAttachments: undefined,
    isRead: undefined,
    isFlagged: undefined,
    tags: []
  }
  tagsInput.value = ''
  activeQuickFilters.value.clear()
}

const selectSuggestion = (suggestion: any) => {
  if (suggestion.type === 'from') {
    filters.value.from = suggestion.text.replace('from:', '')
    showAdvanced.value = true
  } else if (suggestion.type === 'subject') {
    filters.value.subject = suggestion.text.replace('subject:', '')
    showAdvanced.value = true
  } else {
    searchQuery.value = suggestion.text
  }
  showSuggestions.value = false
  performSearch()
}

const toggleQuickFilter = (filterKey: string) => {
  if (activeQuickFilters.value.has(filterKey)) {
    activeQuickFilters.value.delete(filterKey)
  } else {
    activeQuickFilters.value.add(filterKey)
  }
}

const isQuickFilterActive = (filterKey: string): boolean => {
  return activeQuickFilters.value.has(filterKey)
}

const getQuickFilterValues = (): Partial<EmailFilters> => {
  const quickFilterValues: Partial<EmailFilters> = {}
  
  if (activeQuickFilters.value.has('unread')) {
    quickFilterValues.isRead = false
  }
  
  if (activeQuickFilters.value.has('flagged')) {
    quickFilterValues.isFlagged = true
  }
  
  if (activeQuickFilters.value.has('attachments')) {
    quickFilterValues.hasAttachments = true
  }
  
  if (activeQuickFilters.value.has('today')) {
    const today = new Date().toISOString().split('T')[0]
    quickFilterValues.dateFrom = today
    quickFilterValues.dateTo = today
  }
  
  if (activeQuickFilters.value.has('week')) {
    quickFilterValues.dateFrom = getLastWeekDate()
  }
  
  return quickFilterValues
}

const loadSavedSearch = (saved: any) => {
  searchQuery.value = saved.query
  Object.assign(filters.value, saved.filters)
  performSearch()
}

function getLastWeekDate(): string {
  const date = new Date()
  date.setDate(date.getDate() - 7)
  return date.toISOString().split('T')[0]
}

// Watch for search input changes
watch(searchQuery, (newQuery) => {
  showSuggestions.value = newQuery.length >= 2
  if (!newQuery) {
    clearSearch()
  }
})

// Hide suggestions when clicking outside
onMounted(() => {
  document.addEventListener('click', (event) => {
    const target = event.target as HTMLElement
    if (!target.closest('.email-search')) {
      showSuggestions.value = false
    }
  })
})
</script>

<style scoped>
.email-search {
  position: relative;
}
</style>