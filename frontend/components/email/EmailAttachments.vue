<template>
  <div class="attachment-item border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
    <div class="flex items-center justify-between">
      <!-- Attachment Info -->
      <div class="flex items-center space-x-3 flex-1 min-w-0">
        <!-- File Type Icon -->
        <div class="flex-shrink-0">
          <Icon 
            :name="fileTypeIcon"
            class="h-8 w-8"
            :class="fileTypeIconColor"
          />
        </div>
        
        <!-- File Details -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ attachment.filename }}
          </p>
          <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ formatFileSize(attachment.size) }}</span>
            <span>"</span>
            <span>{{ attachment.mimeType }}</span>
            <span v-if="attachment.isVirus" class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
              <Icon name="i-heroicons-exclamation-triangle" class="h-3 w-3 mr-1" />
              Virus Detected
            </span>
            <span v-else-if="attachment.isSecure" class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
              <Icon name="i-heroicons-shield-check" class="h-3 w-3 mr-1" />
              Safe
            </span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center space-x-2 flex-shrink-0">
        <!-- Preview Button (for previewable files) -->
        <UButton
          v-if="isPreviewable"
          icon="i-heroicons-eye"
          size="sm"
          variant="ghost"
          color="gray"
          @click="$emit('preview', attachment)"
          aria-label="Preview attachment"
        />

        <!-- Download Button -->
        <UButton
          icon="i-heroicons-arrow-down-tray"
          size="sm"
          variant="ghost"
          color="gray"
          @click="$emit('download', attachment)"
          :disabled="attachment.isVirus"
          aria-label="Download attachment"
        />

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

    <!-- Preview Thumbnail (for images) -->
    <div v-if="showThumbnail && isImage" class="mt-3">
      <img
        :src="thumbnailUrl"
        :alt="attachment.filename"
        class="max-w-full h-32 object-cover rounded border border-gray-200 dark:border-gray-600 cursor-pointer hover:opacity-80 transition-opacity"
        @click="$emit('preview', attachment)"
        @error="handleThumbnailError"
      />
    </div>

    <!-- Warning for potentially dangerous files -->
    <div v-if="isPotentiallyDangerous && !attachment.isVirus" class="mt-3 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
      <div class="flex items-center">
        <Icon name="i-heroicons-exclamation-triangle" class="h-4 w-4 text-yellow-500 mr-2" />
        <span class="text-xs text-yellow-800 dark:text-yellow-200">
          Exercise caution when opening this file type
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Attachment } from '~/types/api'

// Props
interface Props {
  attachment: Attachment
  showThumbnail?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showThumbnail: true
})

// Emits
defineEmits<{
  'download': [attachment: Attachment]
  'preview': [attachment: Attachment]
}>()

// Composables
const toast = useToast()

// State
const thumbnailError = ref(false)

// Computed
const fileExtension = computed(() => {
  const filename = props.attachment.filename
  const lastDotIndex = filename.lastIndexOf('.')
  return lastDotIndex !== -1 ? filename.substring(lastDotIndex + 1).toLowerCase() : ''
})

const fileTypeIcon = computed(() => {
  const ext = fileExtension.value
  const mimeType = props.attachment.mimeType?.toLowerCase() || ''
  
  // Images
  if (mimeType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
    return 'i-heroicons-photo'
  }
  
  // Documents
  if (['pdf'].includes(ext) || mimeType === 'application/pdf') {
    return 'i-heroicons-document-text'
  }
  
  // Word documents
  if (['doc', 'docx'].includes(ext) || mimeType.includes('word')) {
    return 'i-heroicons-document-text'
  }
  
  // Excel
  if (['xls', 'xlsx'].includes(ext) || mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
    return 'i-heroicons-table-cells'
  }
  
  // PowerPoint
  if (['ppt', 'pptx'].includes(ext) || mimeType.includes('presentation')) {
    return 'i-heroicons-presentation-chart-bar'
  }
  
  // Archives
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
    return 'i-heroicons-archive-box'
  }
  
  // Videos
  if (mimeType.startsWith('video/') || ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'].includes(ext)) {
    return 'i-heroicons-video-camera'
  }
  
  // Audio
  if (mimeType.startsWith('audio/') || ['mp3', 'wav', 'flac', 'aac', 'ogg'].includes(ext)) {
    return 'i-heroicons-musical-note'
  }
  
  // Code files
  if (['js', 'ts', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'json', 'xml'].includes(ext)) {
    return 'i-heroicons-code-bracket'
  }
  
  // Text files
  if (mimeType.startsWith('text/') || ['txt', 'md', 'rtf'].includes(ext)) {
    return 'i-heroicons-document-text'
  }
  
  // Default
  return 'i-heroicons-document'
})

const fileTypeIconColor = computed(() => {
  const ext = fileExtension.value
  const mimeType = props.attachment.mimeType?.toLowerCase() || ''
  
  if (props.attachment.isVirus) {
    return 'text-red-500'
  }
  
  // Images
  if (mimeType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
    return 'text-blue-500'
  }
  
  // Documents
  if (['pdf'].includes(ext)) {
    return 'text-red-500'
  }
  
  if (['doc', 'docx'].includes(ext)) {
    return 'text-blue-600'
  }
  
  if (['xls', 'xlsx'].includes(ext)) {
    return 'text-green-600'
  }
  
  if (['ppt', 'pptx'].includes(ext)) {
    return 'text-orange-500'
  }
  
  // Archives
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
    return 'text-yellow-600'
  }
  
  // Videos
  if (mimeType.startsWith('video/')) {
    return 'text-purple-500'
  }
  
  // Audio
  if (mimeType.startsWith('audio/')) {
    return 'text-pink-500'
  }
  
  // Code
  if (['js', 'ts', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'json', 'xml'].includes(ext)) {
    return 'text-green-500'
  }
  
  return 'text-gray-500'
})

const isImage = computed(() => {
  const mimeType = props.attachment.mimeType?.toLowerCase() || ''
  const ext = fileExtension.value
  return mimeType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)
})

const isPreviewable = computed(() => {
  const mimeType = props.attachment.mimeType?.toLowerCase() || ''
  const ext = fileExtension.value
  
  // Images
  if (isImage.value) return true
  
  // PDFs
  if (ext === 'pdf' || mimeType === 'application/pdf') return true
  
  // Text files
  if (mimeType.startsWith('text/') || ['txt', 'md', 'json', 'xml', 'csv'].includes(ext)) return true
  
  return false
})

const isPotentiallyDangerous = computed(() => {
  const ext = fileExtension.value
  const dangerousExtensions = [
    'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'app', 'deb', 'pkg', 'dmg'
  ]
  return dangerousExtensions.includes(ext)
})

const thumbnailUrl = computed(() => {
  if (!isImage.value || thumbnailError.value) return ''
  // This would typically be a backend endpoint that generates thumbnails
  return `/api/attachments/${props.attachment.id}/thumbnail`
})

const moreActions = computed(() => [
  [{
    label: 'Copy File Name',
    icon: 'i-heroicons-clipboard',
    click: () => copyFileName()
  }],
  [{
    label: 'View Details',
    icon: 'i-heroicons-information-circle',
    click: () => showDetails()
  }]
])

// Methods
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleThumbnailError = () => {
  thumbnailError.value = true
}

const copyFileName = async () => {
  try {
    await navigator.clipboard.writeText(props.attachment.filename)
    toast.add({
      title: 'Copied',
      description: 'File name copied to clipboard',
      color: 'green'
    })
  } catch (error) {
    toast.add({
      title: 'Error',
      description: 'Failed to copy file name',
      color: 'red'
    })
  }
}

const showDetails = () => {
  // TODO: Implement attachment details modal
  console.log('Show attachment details:', props.attachment)
}
</script>

<style scoped>
.attachment-item {
  /* Custom styles for attachment items */
}
</style>