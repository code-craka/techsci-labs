# Claude AI Development Guidelines

## Project Overview

**TechSci Labs Email Testing Platform** - A developer-focused email testing platform built with Nuxt.js, API-Platform, and Mercure for real-time communication.

## AI Assistance Scope

### ✅ What Claude Can Help With

#### Frontend Development (Nuxt.js)

- **Nuxt.js 3.x Implementation**: Pages, layouts, composables, middleware
- **Vue.js 3 Features**: Composition API, script setup, reactive patterns
- **Tailwind CSS Integration**: Utility classes, responsive design, dark/light themes
- **TypeScript**: Strict typing, component interfaces, composable patterns
- **Component Architecture**: Reusable components, design system implementation
- **State Management**: Pinia store patterns, reactive state

#### Backend Integration

- **API-Platform Integration**: RESTful APIs, Hydra JSON-LD, OpenAPI documentation
- **Mercure SSE**: Real-time updates, event subscriptions, connection handling
- **Authentication**: JWT handling, session management, API key authentication
- **Performance**: Caching strategies, lazy loading, bundle optimization

#### Email Infrastructure

- **Haraka Integration**: SMTP server configuration, email parsing, plugin development
- **Email Processing**: Plus-sign aliasing, catch-all domains, attachment handling
- **MongoDB Schema**: Document design, aggregation pipelines, indexing strategies

#### Development Workflow

- **Code Review**: Best practices, security considerations, performance optimization
- **Testing Strategy**: Vitest for unit tests, Playwright for E2E testing
- **Build Optimization**: Nitro engine, server-side rendering, static generation
- **Documentation**: Technical docs, API documentation, component stories

### ❌ What Claude Cannot Do

#### Infrastructure & Deployment

- Direct RockyLinux server configuration
- Caddy web server setup and SSL management
- MongoDB cluster deployment and sharding
- DNS configuration or domain management
- Production environment provisioning

#### External Service Integration

- Haraka SMTP server installation and configuration
- Mercure hub deployment and scaling
- Email service provider configuration
- Third-party API key generation

### 🔧 Development Context

#### Current Tech Stack

```json
{
  "frontend": {
    "framework": "Nuxt.js 3.x",
    "vue": "3.x",
    "typescript": "5.x",
    "styling": "Tailwind CSS",
    "stateManagement": "Pinia"
  },
  "backend": {
    "api": "API-Platform (Symfony)",
    "database": "MongoDB",
    "realtime": "Mercure",
    "language": "PHP 8.x"
  },
  "infrastructure": {
    "emailServer": "Haraka (Node.js)",
    "webServer": "Caddy",
    "os": "RockyLinux"
  }
}
```

#### Project Structure Knowledge

```
techsci-labs/
├── frontend/               # Nuxt.js application
│   ├── pages/             # Vue.js pages (auto-routing)
│   ├── components/        # Reusable Vue components
│   ├── composables/       # Vue composition functions
│   ├── stores/            # Pinia state management
│   └── middleware/        # Route middleware
├── backend/               # API-Platform (Symfony)
│   ├── src/Entity/        # MongoDB entities
│   ├── src/Controller/    # API controllers
│   └── config/            # Symfony configuration
├── email-server/          # Haraka SMTP server
└── infrastructure/        # Server configuration
```

### 📋 Code Standards & Guidelines

#### Vue.js/Nuxt.js Standards

```vue
<!-- ✅ Preferred: Composition API with script setup -->
<script setup lang="ts">
interface EmailAccount {
  id: string
  address: string
  isActive: boolean
  createdAt: Date
}

const props = defineProps<{
  accountId: string
}>()

const { data: emails } = await $fetch<EmailAccount[]>(`/api/accounts/${props.accountId}/emails`)
</script>

<template>
  <div class="space-y-4">
    <EmailCard 
      v-for="email in emails" 
      :key="email.id" 
      :email="email" 
    />
  </div>
</template>
```

#### API-Platform Entity Patterns

```php
<?php
// ✅ Preferred: Proper API-Platform annotations
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post()
    ],
    normalizationContext: ['groups' => ['email_account:read']],
    denormalizationContext: ['groups' => ['email_account:write']]
)]
class EmailAccount
{
    #[Groups(['email_account:read'])]
    public string $id;
    
    #[Groups(['email_account:read', 'email_account:write'])]
    public string $address;
    
    #[Groups(['email_account:write'])]
    public string $password;
}
```

#### Mercure SSE Integration

```typescript
// ✅ Real-time email notifications
export const useEmailSubscription = (accountId: string) => {
  const emails = ref<Email[]>([])
  
  onMounted(() => {
    const url = new URL('https://mercure.techsci.dev/.well-known/mercure')
    url.searchParams.append('topic', `/accounts/${accountId}`)
    
    const eventSource = new EventSource(url.toString(), {
      headers: {
        Authorization: `Bearer ${useAuthStore().apiKey}`
      }
    })
    
    eventSource.onmessage = (event) => {
      const data = JSON.parse(event.data)
      if (data.type === 'Message') {
        emails.value.unshift(data.message)
      }
    }
    
    onUnmounted(() => eventSource.close())
  })
  
  return { emails }
}
```

### 🚀 Feature Development Approach

#### MongoDB Schema Design

```php
// Email Account Document
{
  "_id": ObjectId,
  "address": "user@example.com",
  "password_hash": "hashed_password",
  "quota": 1073741824,
  "used": 0,
  "is_active": true,
  "is_deleted": false,
  "mailboxes": [
    {
      "id": "inbox",
      "path": "INBOX",
      "total_messages": 5,
      "unread_messages": 2
    }
  ],
  "created_at": ISODate,
  "updated_at": ISODate
}
```

#### Haraka Plugin Development

```javascript
// ✅ Email processing plugin
exports.hook_data_post = function(next, connection) {
  const email = connection.transaction.mail_from
  const recipients = connection.transaction.rcpt_to
  
  // Parse plus-sign aliasing
  recipients.forEach(rcpt => {
    const [localPart, domain] = rcpt.address().split('@')
    const [baseAddress, tag] = localPart.split('+')
    
    // Store email in MongoDB
    this.storeEmail({
      from: email.address(),
      to: rcpt.address(),
      tag: tag || null,
      content: connection.transaction.message_stream
    })
  })
  
  next()
}
```

### 📖 Development Workflow

#### Before Starting New Features

1. **Review API Documentation**: Check API-Platform specs and Hydra contexts
2. **Plan Vue Components**: Design component hierarchy and props interface
3. **Consider Real-time**: Identify Mercure subscription requirements
4. **Performance Review**: Evaluate MongoDB queries and caching needs

#### Code Review Checklist

- [ ] Vue.js composition API compliance
- [ ] API-Platform annotations correct
- [ ] Mercure event handling implemented
- [ ] MongoDB schema validation
- [ ] TypeScript strict mode compliance
- [ ] Accessibility considerations
- [ ] Mobile responsiveness

#### Testing Strategy

```typescript
// Unit tests for composables
import { describe, it, expect } from 'vitest'
import { useEmailValidation } from '~/composables/useEmailValidation'

describe('useEmailValidation', () => {
  it('validates email addresses correctly', () => {
    const { isValid } = useEmailValidation()
    
    expect(isValid('user@example.com')).toBe(true)
    expect(isValid('user+tag@example.com')).toBe(true)
    expect(isValid('invalid-email')).toBe(false)
  })
})
```

### 🔍 Performance Considerations

#### Nuxt.js Optimization

- Use server-side rendering for initial page load
- Implement code splitting with dynamic imports
- Optimize images with Nuxt Image module
- Enable Nitro caching for API responses

#### MongoDB Performance

- Index frequently queried fields
- Use aggregation pipelines for complex queries
- Implement proper connection pooling
- Monitor query performance with profiling

### 🔒 Security Guidelines

#### API-Platform Security

```php
// ✅ Secure entity with proper validation
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(security: "is_granted('ROLE_USER') and object.owner == user"),
        new Post(securityPostDenormalize: "is_granted('ROLE_USER')")
    ]
)]
class EmailAccount
{
    // Entity implementation
}
```

#### Frontend Security

- Store JWT tokens securely (httpOnly cookies)
- Validate all user inputs with Zod schemas
- Implement CSRF protection
- Use environment variables for sensitive data

### 📚 Resources & References

#### Documentation Links

- [Nuxt.js 3 Documentation](https://nuxt.com/docs)
- [API-Platform Documentation](https://api-platform.com/docs)
- [Mercure Documentation](https://mercure.rocks/docs)
- [Haraka Documentation](https://haraka.github.io/manual)
- [Vue.js 3 Documentation](https://vuejs.org/guide)

#### Project-Specific Resources

- API Documentation: `/docs/api.md`
- Component Library: `/docs/components.md`
- Deployment Guide: `/docs/deployment.md`
- Infrastructure Setup: `/docs/infrastructure.md`

---

*This document should be updated as the project evolves and new patterns are established.*
