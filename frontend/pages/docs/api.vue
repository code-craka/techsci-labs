<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">API Reference</h1>
    <div class="bg-white rounded-lg shadow-md p-6">
      <p class="text-gray-600 mb-6">Complete API documentation for the TechSci Labs Email Testing Platform.</p>
      
      <div class="mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="font-semibold text-blue-900 mb-2">Interactive API Documentation</h3>
          <p class="text-blue-800 mb-3">Access our interactive Swagger/OpenAPI documentation for testing API endpoints.</p>
          <a href="http://localhost:8000/api/docs" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
            Open API Docs ’
          </a>
        </div>
      </div>
      
      <div class="space-y-6">
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Authentication</h2>
          <p class="text-gray-600 mb-4">JWT-based authentication for secure API access.</p>
          <div class="bg-gray-50 p-4 rounded">
            <code class="text-sm text-gray-700">Authorization: Bearer {your_jwt_token}</code>
          </div>
        </div>
        
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Email Endpoints</h2>
          <p class="text-gray-600 mb-4">Send, receive, and manage emails via REST API.</p>
          <ul class="space-y-2 text-sm text-gray-600">
            <li><code class="bg-gray-100 px-2 py-1 rounded">GET /api/messages</code> - List emails</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">POST /api/messages</code> - Send email</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">GET /api/messages/{id}</code> - Get email details</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">DELETE /api/messages/{id}</code> - Delete email</li>
          </ul>
        </div>
        
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Domain Endpoints</h2>
          <p class="text-gray-600 mb-4">Manage domains and DNS configuration.</p>
          <ul class="space-y-2 text-sm text-gray-600">
            <li><code class="bg-gray-100 px-2 py-1 rounded">GET /api/domains</code> - List domains</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">POST /api/domains</code> - Add domain</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">GET /api/domains/{id}</code> - Get domain details</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">PUT /api/domains/{id}</code> - Update domain</li>
          </ul>
        </div>
        
        <div class="border rounded-lg p-4">
          <h2 class="text-xl font-semibold mb-3">Account Endpoints</h2>
          <p class="text-gray-600 mb-4">Manage email accounts and aliases.</p>
          <ul class="space-y-2 text-sm text-gray-600">
            <li><code class="bg-gray-100 px-2 py-1 rounded">GET /api/email_accounts</code> - List accounts</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">POST /api/email_accounts</code> - Create account</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">GET /api/email_accounts/{id}</code> - Get account details</li>
            <li><code class="bg-gray-100 px-2 py-1 rounded">PUT /api/email_accounts/{id}</code> - Update account</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  title: 'API Reference - TechSci Labs',
  description: 'Complete API documentation for the TechSci Labs Email Testing Platform'
})
</script>