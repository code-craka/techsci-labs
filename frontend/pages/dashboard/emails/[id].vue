<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
      <NuxtLink to="/dashboard/emails" class="text-gray-600 hover:text-gray-800 mr-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </NuxtLink>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ email.subject }}</h1>
        <p class="text-sm text-gray-600 mt-1">Email Details</p>
      </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Email Content -->
      <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow-md">
          <!-- Email Header -->
          <div class="p-6 border-b">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <span class="font-medium text-gray-900">From:</span>
                  <span class="text-gray-700">{{ email.from.name }} &lt;{{ email.from.email }}&gt;</span>
                </div>
                <div class="flex items-center space-x-2 mb-2">
                  <span class="font-medium text-gray-900">To:</span>
                  <span class="text-gray-700">{{ email.to.map(t => `${t.name} <${t.email}>`).join(', ') }}</span>
                </div>
                <div class="flex items-center space-x-2 mb-2" v-if="email.cc.length > 0">
                  <span class="font-medium text-gray-900">CC:</span>
                  <span class="text-gray-700">{{ email.cc.map(c => `${c.name} <${c.email}>`).join(', ') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="font-medium text-gray-900">Date:</span>
                  <span class="text-gray-700">{{ email.date }}</span>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">{{ email.status }}</span>
                <button class="text-gray-400 hover:text-gray-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                  </svg>
                </button>
              </div>
            </div>
            
            <h2 class="text-xl font-semibold text-gray-900">{{ email.subject }}</h2>
          </div>
          
          <!-- Email Body -->
          <div class="p-6">
            <div class="prose max-w-none" v-html="email.htmlContent || email.textContent"></div>
          </div>
          
          <!-- Attachments -->
          <div class="p-6 border-t" v-if="email.attachments.length > 0">
            <h3 class="font-medium text-gray-900 mb-3">Attachments ({{ email.attachments.length }})</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div v-for="attachment in email.attachments" :key="attachment.id" class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50">
                <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center">
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ attachment.filename }}</p>
                  <p class="text-xs text-gray-600">{{ attachment.size }}</p>
                </div>
                <button class="text-blue-600 hover:text-blue-800 text-sm">
                  Download
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Actions -->
        <div class="mt-6 flex space-x-4">
          <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Reply
          </button>
          <button class="px-4 py-2 text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors">
            Forward
          </button>
          <button class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
            Archive
          </button>
          <button class="px-4 py-2 text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors">
            Delete
          </button>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Email Info -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4">Email Information</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Message ID:</span>
              <span class="font-mono text-xs">{{ email.messageId }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Size:</span>
              <span class="font-medium">{{ email.size }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Priority:</span>
              <span class="font-medium">{{ email.priority }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Encoding:</span>
              <span class="font-medium">{{ email.encoding }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Spam Score:</span>
              <span class="font-medium">{{ email.spamScore }}/10</span>
            </div>
          </div>
        </div>
        
        <!-- Security Info -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4">Security</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">SPF</span>
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Pass</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">DKIM</span>
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Valid</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">DMARC</span>
              <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Quarantine</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Virus Scan</span>
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Clean</span>
            </div>
          </div>
        </div>
        
        <!-- Raw Email -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="font-semibold mb-4">Raw Email</h3>
          <div class="space-y-3">
            <button class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
              View Headers
            </button>
            <button class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
              Download EML
            </button>
            <button class="w-full px-4 py-2 text-center text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
              View Source
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Email Details - Dashboard',
  description: 'View email details and content'
})

// Get email ID from route
const route = useRoute()
const emailId = route.params.id

// Mock email data - TODO: Replace with API call
const email = ref({
  id: emailId,
  subject: 'Welcome to TechSci Labs Email Testing Platform',
  from: { name: 'TechSci Labs', email: 'welcome@techsci-labs.com' },
  to: [{ name: 'John Doe', email: 'john.doe@example.com' }],
  cc: [],
  date: 'Dec 6, 2024 at 2:30 PM',
  status: 'Delivered',
  messageId: '<abc123@techsci-labs.com>',
  size: '24.5 KB',
  priority: 'Normal',
  encoding: 'UTF-8',
  spamScore: 2,
  htmlContent: `
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <h2 style="color: #2563eb;">Welcome to TechSci Labs!</h2>
      <p>Thank you for signing up for our Email Testing Platform. We're excited to help you streamline your email testing workflow.</p>
      <p>Here's what you can do with your new account:</p>
      <ul>
        <li>Set up custom domains for email testing</li>
        <li>Create unlimited email accounts and aliases</li>
        <li>Monitor email delivery and performance</li>
        <li>Access our comprehensive API for automation</li>
      </ul>
      <p>If you have any questions, don't hesitate to reach out to our support team.</p>
      <p>Best regards,<br>The TechSci Labs Team</p>
    </div>
  `,
  textContent: '',
  attachments: [
    { id: 1, filename: 'welcome-guide.pdf', size: '2.1 MB' },
    { id: 2, filename: 'api-documentation.pdf', size: '1.8 MB' }
  ]
})

// Load email data
onMounted(async () => {
  try {
    // TODO: Fetch email data from API
    // const response = await $fetch(`/api/messages/${emailId}`)
    // email.value = response
  } catch (error) {
    console.error('Error loading email:', error)
  }
})
</script>