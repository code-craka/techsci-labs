<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Email Accounts</h1>
      <NuxtLink to="/dashboard/accounts/new" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        Create Account
      </NuxtLink>
    </div>
    
    <div class="bg-white rounded-lg shadow-md">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold">Your Email Accounts</h2>
            <p class="text-sm text-gray-600">Manage email accounts and aliases</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="relative">
              <input type="text" placeholder="Search accounts..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="all">All Domains</option>
              <option value="example.com">example.com</option>
              <option value="test.dev">test.dev</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="divide-y">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 font-semibold text-sm">JD</span>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">john.doe@example.com</h3>
                <p class="text-sm text-gray-600">Primary account " Created on Dec 1, 2024</p>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Primary</span>
                  <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right text-sm">
                <p class="font-medium">1,234 emails</p>
                <p class="text-gray-600">15.2 MB used</p>
              </div>
              <NuxtLink to="/dashboard/accounts/1" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-green-600 font-semibold text-sm">SP</span>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">support@example.com</h3>
                <p class="text-sm text-gray-600">Support team account " Created on Nov 28, 2024</p>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Shared</span>
                  <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">3 Aliases</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right text-sm">
                <p class="font-medium">789 emails</p>
                <p class="text-gray-600">8.7 MB used</p>
              </div>
              <NuxtLink to="/dashboard/accounts/2" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                <span class="text-purple-600 font-semibold text-sm">NO</span>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">noreply@example.com</h3>
                <p class="text-sm text-gray-600">System notifications " Created on Nov 25, 2024</p>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">System</span>
                  <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                  <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">Send Only</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right text-sm">
                <p class="font-medium">2,456 emails</p>
                <p class="text-gray-600">1.2 MB used</p>
              </div>
              <NuxtLink to="/dashboard/accounts/3" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <span class="text-yellow-600 font-semibold text-sm">TE</span>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">test@test.dev</h3>
                <p class="text-sm text-gray-600">Development testing " Created on Nov 20, 2024</p>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Testing</span>
                  <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right text-sm">
                <p class="font-medium">45 emails</p>
                <p class="text-gray-600">156 KB used</p>
              </div>
              <NuxtLink to="/dashboard/accounts/4" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
      
      <div class="p-6 bg-gray-50">
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">Showing 4 of 4 accounts</p>
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
    
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="font-semibold mb-2">Total Accounts</h3>
        <p class="text-3xl font-bold text-blue-600">4</p>
        <p class="text-sm text-gray-600">Across 2 domains</p>
      </div>
      <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="font-semibold mb-2">Storage Used</h3>
        <p class="text-3xl font-bold text-green-600">25.3 MB</p>
        <p class="text-sm text-gray-600">of 10 GB limit</p>
      </div>
      <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="font-semibold mb-2">Total Emails</h3>
        <p class="text-3xl font-bold text-purple-600">4,524</p>
        <p class="text-sm text-gray-600">This month</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Email Accounts - Dashboard',
  description: 'Manage your email accounts and aliases'
})
</script>