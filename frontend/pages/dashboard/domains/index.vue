<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Domains</h1>
      <NuxtLink to="/dashboard/domains/new" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        Add Domain
      </NuxtLink>
    </div>
    
    <div class="bg-white rounded-lg shadow-md">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold">Your Domains</h2>
            <p class="text-sm text-gray-600">Manage your domains and DNS settings</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="relative">
              <input type="text" placeholder="Search domains..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="all">All Domains</option>
              <option value="verified">Verified</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="divide-y">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">example.com</h3>
                <p class="text-sm text-gray-600">Added on Dec 1, 2024</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Verified</span>
              <span class="text-sm text-gray-600">47 accounts</span>
              <NuxtLink to="/dashboard/domains/1" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">test.dev</h3>
                <p class="text-sm text-gray-600">Added on Nov 28, 2024</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
              <span class="text-sm text-gray-600">2 accounts</span>
              <NuxtLink to="/dashboard/domains/2" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">failed-domain.com</h3>
                <p class="text-sm text-gray-600">Added on Nov 25, 2024</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Failed</span>
              <span class="text-sm text-gray-600">0 accounts</span>
              <NuxtLink to="/dashboard/domains/3" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
      
      <div class="p-6 bg-gray-50">
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Showing 3 of 3 domains</p>
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
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Domains - Dashboard',
  description: 'Manage your email domains and DNS settings'
})
</script>