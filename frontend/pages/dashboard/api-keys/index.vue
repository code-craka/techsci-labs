<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-900">API Keys</h1>
      <NuxtLink to="/dashboard/api-keys/new" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        Create API Key
      </NuxtLink>
    </div>
    
    <div class="bg-white rounded-lg shadow-md">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold">Your API Keys</h2>
            <p class="text-sm text-gray-600">Manage your API keys for programmatic access</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="relative">
              <input type="text" placeholder="Search API keys..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
      
      <div class="divide-y">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2m-2-2h10m-9 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h2a2 2 0 012 2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v1M7 7h3v3H7V7z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">Production API Key</h3>
                <p class="text-sm text-gray-600">Created on Dec 1, 2024 " Last used 2 hours ago</p>
                <p class="text-xs text-gray-500 font-mono">tsl_*********************xyz123</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
              <div class="flex items-center space-x-2">
                <button class="text-gray-400 hover:text-gray-600" title="Copy API Key">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                </button>
                <button class="text-gray-400 hover:text-gray-600" title="Edit API Key">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                  </svg>
                </button>
                <button class="text-red-400 hover:text-red-600" title="Delete API Key">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <div class="mt-4 pt-4 border-t">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Permissions:</span>
                <span class="ml-1 font-medium">Read, Write</span>
              </div>
              <div>
                <span class="text-gray-600">Requests this month:</span>
                <span class="ml-1 font-medium">15,847</span>
              </div>
              <div>
                <span class="text-gray-600">Rate limit:</span>
                <span class="ml-1 font-medium">1000/hour</span>
              </div>
              <div>
                <span class="text-gray-600">Expires:</span>
                <span class="ml-1 font-medium">Never</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2m-2-2h10m-9 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h2a2 2 0 012 2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v1M7 7h3v3H7V7z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">Development API Key</h3>
                <p class="text-sm text-gray-600">Created on Nov 28, 2024 " Last used 1 day ago</p>
                <p class="text-xs text-gray-500 font-mono">tsl_*********************abc456</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Limited</span>
              <div class="flex items-center space-x-2">
                <button class="text-gray-400 hover:text-gray-600" title="Copy API Key">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                </button>
                <button class="text-gray-400 hover:text-gray-600" title="Edit API Key">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                  </svg>
                </button>
                <button class="text-red-400 hover:text-red-600" title="Delete API Key">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <div class="mt-4 pt-4 border-t">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Permissions:</span>
                <span class="ml-1 font-medium">Read Only</span>
              </div>
              <div>
                <span class="text-gray-600">Requests this month:</span>
                <span class="ml-1 font-medium">234</span>
              </div>
              <div>
                <span class="text-gray-600">Rate limit:</span>
                <span class="ml-1 font-medium">100/hour</span>
              </div>
              <div>
                <span class="text-gray-600">Expires:</span>
                <span class="ml-1 font-medium">Jan 1, 2025</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="p-6 bg-gray-50">
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Showing 2 of 2 API keys</p>
          <div class="flex items-center space-x-2">
            <button class="px-3 py-1 text-sm text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50" disabled>
              Previous
            </button>
            <button class="px-3 py-1 text-sm text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50" disabled>
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
      <div class="flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
          <h3 class="font-medium text-blue-900 mb-1">API Key Security</h3>
          <p class="text-sm text-blue-800 mb-2">Keep your API keys secure and never share them publicly. Each key should be used for a specific purpose and environment.</p>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>" Store keys securely in environment variables</li>
            <li>" Use different keys for production and development</li>
            <li>" Rotate keys regularly for enhanced security</li>
            <li>" Monitor usage to detect unauthorized access</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'API Keys - Dashboard',
  description: 'Manage your API keys for programmatic access'
})
</script>