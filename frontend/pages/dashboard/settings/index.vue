<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Settings</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Account Settings</h2>
        <div class="space-y-4">
          <NuxtLink to="/dashboard/settings/profile" class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <h3 class="font-medium text-gray-900">Profile Settings</h3>
            <p class="text-sm text-gray-600">Update your personal information and preferences</p>
          </NuxtLink>
          
          <NuxtLink to="/dashboard/settings/billing" class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <h3 class="font-medium text-gray-900">Billing & Subscription</h3>
            <p class="text-sm text-gray-600">Manage your subscription and billing information</p>
          </NuxtLink>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Team Settings</h2>
        <div class="space-y-4">
          <NuxtLink to="/dashboard/settings/team" class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
            <h3 class="font-medium text-gray-900">Team Management</h3>
            <p class="text-sm text-gray-600">Manage team members and permissions</p>
          </NuxtLink>
        </div>
      </div>
    </div>
    
    <div class="mt-8 bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <button class="p-4 text-left border rounded-lg hover:bg-gray-50 transition-colors">
          <h3 class="font-medium text-gray-900">Export Data</h3>
          <p class="text-sm text-gray-600">Export your email data and settings</p>
        </button>
        
        <button class="p-4 text-left border rounded-lg hover:bg-gray-50 transition-colors">
          <h3 class="font-medium text-gray-900">API Keys</h3>
          <p class="text-sm text-gray-600">Manage your API access keys</p>
        </button>
        
        <button class="p-4 text-left border rounded-lg hover:bg-gray-50 transition-colors">
          <h3 class="font-medium text-gray-900">Support</h3>
          <p class="text-sm text-gray-600">Get help and contact support</p>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Settings - Dashboard',
  description: 'Manage your account and team settings'
})
</script>