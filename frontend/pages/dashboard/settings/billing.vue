<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Billing & Subscription</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">Current Plan</h2>
      
      <div class="border rounded-lg p-4 bg-blue-50">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-blue-900">Professional Plan</h3>
            <p class="text-sm text-blue-700">10,000 emails/month " Unlimited domains " API access</p>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-blue-900">$29</p>
            <p class="text-sm text-blue-700">per month</p>
          </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-blue-200">
          <div class="flex items-center justify-between text-sm">
            <span class="text-blue-700">Current billing period:</span>
            <span class="text-blue-900 font-medium">Dec 1, 2024 - Jan 1, 2025</span>
          </div>
          <div class="flex items-center justify-between text-sm mt-2">
            <span class="text-blue-700">Emails used this month:</span>
            <span class="text-blue-900 font-medium">3,247 / 10,000</span>
          </div>
        </div>
      </div>
      
      <div class="mt-4 flex space-x-4">
        <button class="px-4 py-2 text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors">
          Change Plan
        </button>
        <button class="px-4 py-2 text-red-600 border border-red-600 rounded-md hover:bg-red-50 transition-colors">
          Cancel Subscription
        </button>
      </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">Payment Method</h2>
      
      <div class="border rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
              </svg>
            </div>
            <div>
              <p class="font-medium text-gray-900">"""" """" """" 4242</p>
              <p class="text-sm text-gray-600">Expires 12/26</p>
            </div>
          </div>
          <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            Update
          </button>
        </div>
      </div>
      
      <div class="mt-4">
        <button class="px-4 py-2 text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 transition-colors">
          Add Payment Method
        </button>
      </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">Billing History</h2>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b">
              <th class="text-left py-2">Date</th>
              <th class="text-left py-2">Description</th>
              <th class="text-left py-2">Amount</th>
              <th class="text-left py-2">Status</th>
              <th class="text-right py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b">
              <td class="py-3 text-sm">Dec 1, 2024</td>
              <td class="py-3 text-sm">Professional Plan - Monthly</td>
              <td class="py-3 text-sm font-medium">$29.00</td>
              <td class="py-3">
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Paid</span>
              </td>
              <td class="py-3 text-right">
                <button class="text-blue-600 hover:text-blue-800 text-sm">
                  Download
                </button>
              </td>
            </tr>
            <tr class="border-b">
              <td class="py-3 text-sm">Nov 1, 2024</td>
              <td class="py-3 text-sm">Professional Plan - Monthly</td>
              <td class="py-3 text-sm font-medium">$29.00</td>
              <td class="py-3">
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Paid</span>
              </td>
              <td class="py-3 text-right">
                <button class="text-blue-600 hover:text-blue-800 text-sm">
                  Download
                </button>
              </td>
            </tr>
            <tr class="border-b">
              <td class="py-3 text-sm">Oct 1, 2024</td>
              <td class="py-3 text-sm">Professional Plan - Monthly</td>
              <td class="py-3 text-sm font-medium">$29.00</td>
              <td class="py-3">
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Paid</span>
              </td>
              <td class="py-3 text-right">
                <button class="text-blue-600 hover:text-blue-800 text-sm">
                  Download
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Billing & Subscription - Settings',
  description: 'Manage your subscription and billing information'
})
</script>