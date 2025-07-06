# Contributing to TechSci Labs Email Testing Platform

Thank you for your interest in contributing to the TechSci Labs Email Testing Platform! This document provides guidelines and information for contributors.

## 🚀 Quick Start for Contributors

### Prerequisites

- **Node.js** (v18.0+)
- **PHP** (v8.2+)
- **Docker** & **Docker Compose**
- **pnpm** (v8.0+)
- **Git**

### Development Setup

1. **Fork and Clone**

   ```bash
   git clone https://github.com/YOUR_USERNAME/email-testing-platform.git
   cd email-testing-platform
   ```

2. **Install Dependencies**

   ```bash
   pnpm install
   composer install --working-dir=backend
   ```

3. **Start Development Environment**

   ```bash
   docker-compose up -d
   ```

4. **Verify Setup**

   ```bash
   # Check all services are running
   docker-compose ps
   
   # Run tests
   pnpm test
   ```

## 🏗️ Project Architecture

### Tech Stack Overview

- **Frontend**: Nuxt.js 3.x (Vue.js, TypeScript, Tailwind CSS, Nuxt UI)
- **Backend**: Symfony 7.1 + API Platform 3.2 (PHP, MongoDB ODM)
- **Email Server**: Haraka (Node.js SMTP server)
- **Database**: MongoDB 7.0
- **Cache**: Redis 7.2
- **Real-time**: Mercure (Server-Sent Events)
- **Web Server**: Caddy 2.7

### Workspace Structure

```
techsci-email-testing/
├── frontend/           # Nuxt.js 3.x Application
├── backend/            # Symfony/API-Platform
├── email-server/       # Haraka SMTP Server
├── infrastructure/     # Configuration files
├── docs/              # Documentation
└── package.json       # Workspace root
```

## 📝 Development Guidelines

### Code Style & Standards

#### TypeScript/JavaScript (Frontend & Email Server)

- **ESLint**: Follow the configured ESLint rules
- **Prettier**: Code formatting is handled automatically
- **Naming**: Use camelCase for variables/functions, PascalCase for components
- **Components**: Use Vue 3 Composition API with `<script setup>`

```typescript
// ✅ Good
const emailCount = ref(0)
const fetchEmails = async () => { /* ... */ }

// ❌ Bad
const email_count = ref(0)
const FetchEmails = async () => { /* ... */ }
```

#### PHP (Backend)

- **PSR-12**: Follow PSR-12 coding standard
- **PHPStan**: Level 8 static analysis
- **Naming**: Use camelCase for methods, PascalCase for classes
- **Type Hints**: Always use strict typing

```php
<?php
// ✅ Good
declare(strict_types=1);

final class EmailAccount
{
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
}

// ❌ Bad
class emailAccount
{
    public function get_email_address()
    {
        return $this->email_address;
    }
}
```

### Git Workflow

#### Branch Naming

- **Feature**: `feature/description` (e.g., `feature/add-email-filters`)
- **Bug Fix**: `fix/description` (e.g., `fix/smtp-connection-issue`)
- **Documentation**: `docs/description` (e.g., `docs/api-examples`)
- **Refactor**: `refactor/description` (e.g., `refactor/email-service`)

#### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
# Features
feat: add email filtering functionality
feat(api): implement real-time message notifications

# Bug fixes
fix: resolve SMTP connection timeout issue
fix(frontend): correct email list pagination

# Documentation
docs: update API authentication guide
docs(readme): add development setup instructions

# Refactoring
refactor: extract email validation logic
refactor(backend): simplify message repository

# Tests
test: add unit tests for email service
test(e2e): add email sending workflow tests
```

### Testing Requirements

#### Frontend (Vitest)

```bash
# Run tests
pnpm test:frontend

# Run with coverage
pnpm test:frontend:coverage

# Run specific test
pnpm test:frontend --reporter=verbose components/EmailList.test.ts
```

**Test Structure**:

```typescript
// components/EmailList.test.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import EmailList from './EmailList.vue'

describe('EmailList', () => {
  it('renders email items correctly', () => {
    const wrapper = mount(EmailList, {
      props: { emails: mockEmails }
    })
    
    expect(wrapper.find('[data-testid="email-item"]')).toBeTruthy()
  })
})
```

#### Backend (PHPUnit)

```bash
# Run tests
pnpm test:backend

# Run with coverage
composer test:coverage

# Run specific test
./vendor/bin/phpunit tests/Unit/EmailServiceTest.php
```

**Test Structure**:

```php
<?php
// tests/Unit/EmailServiceTest.php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Service\EmailService;

final class EmailServiceTest extends TestCase
{
    public function testCreateEmailAccount(): void
    {
        $service = new EmailService();
        $account = $service->createAccount('test@example.com');
        
        $this->assertInstanceOf(EmailAccount::class, $account);
        $this->assertEquals('test@example.com', $account->getAddress());
    }
}
```

#### Email Server (Node.js)

```bash
# Run tests
pnpm test:email

# Run specific test
npm test -- --grep "SMTP connection"
```

### Pull Request Process

1. **Create Feature Branch**

   ```bash
   git checkout -b feature/amazing-new-feature
   ```

2. **Make Changes**
   - Write code following style guidelines
   - Add/update tests
   - Update documentation if needed

3. **Test Your Changes**

   ```bash
   # Run all tests
   pnpm test
   
   # Check linting
   pnpm lint
   
   # Type checking
   pnpm type-check
   ```

4. **Commit Changes**

   ```bash
   git add .
   git commit -m "feat: add amazing new feature"
   ```

5. **Push and Create PR**

   ```bash
   git push origin feature/amazing-new-feature
   ```

6. **PR Requirements**
   - [ ] Tests pass
   - [ ] Code follows style guidelines
   - [ ] Documentation updated
   - [ ] Descriptive title and description
   - [ ] Screenshots for UI changes

### PR Template

```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] 🐛 Bug fix
- [ ] ✨ New feature
- [ ] 💥 Breaking change
- [ ] 📚 Documentation update
- [ ] 🎨 Style/UI changes

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests pass
- [ ] Manual testing completed

## Screenshots (if applicable)
[Add screenshots for UI changes]

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Tests added for new functionality
- [ ] Documentation updated
```

## 🧪 Testing Strategy

### Test Coverage Requirements

- **Frontend**: Minimum 80% coverage
- **Backend**: Minimum 90% coverage
- **Email Server**: Minimum 75% coverage

### Test Types

#### Unit Tests

- Test individual functions/methods
- Mock external dependencies
- Fast execution

#### Integration Tests

- Test component interactions
- Use test database
- Test API endpoints

#### End-to-End Tests

- Test complete workflows
- Use real browser/email client
- Test critical user journeys

### Testing Best Practices

1. **Arrange, Act, Assert** pattern
2. **Descriptive test names** explaining what is being tested
3. **Mock external services** (APIs, databases in unit tests)
4. **Test edge cases** and error conditions
5. **Keep tests fast** and isolated

## 🔧 Component-Specific Guidelines

### Frontend Development (Nuxt.js)

#### Component Structure

```vue
<template>
  <div class="email-list">
    <!-- Template content -->
  </div>
</template>

<script setup lang="ts">
// Imports
import type { Email } from '~/types/email'

// Props
interface Props {
  emails: Email[]
}
const props = defineProps<Props>()

// Composables
const { $api } = useNuxtApp()

// Reactive state
const loading = ref(false)

// Methods
const refreshEmails = async () => {
  loading.value = true
  // Implementation
  loading.value = false
}
</script>

<style scoped>
.email-list {
  /* Styles using Tailwind CSS */
}
</style>
```

#### State Management (Pinia)

```typescript
// stores/email.ts
export const useEmailStore = defineStore('email', () => {
  const emails = ref<Email[]>([])
  const loading = ref(false)

  const fetchEmails = async () => {
    loading.value = true
    try {
      const data = await $fetch('/api/emails')
      emails.value = data
    } finally {
      loading.value = false
    }
  }

  return {
    emails: readonly(emails),
    loading: readonly(loading),
    fetchEmails
  }
})
```

### Backend Development (Symfony/API-Platform)

#### Entity Structure

```php
<?php
// src/Entity/EmailAccount.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document]
#[ApiResource]
class EmailAccount
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Assert\Email]
    #[Assert\NotBlank]
    private string $address;

    // Getters and setters
}
```

#### Service Structure

```php
<?php
// src/Service/EmailService.php
namespace App\Service;

use App\Entity\EmailAccount;
use App\Repository\EmailAccountRepository;

final readonly class EmailService
{
    public function __construct(
        private EmailAccountRepository $repository,
    ) {}

    public function createAccount(string $address): EmailAccount
    {
        $account = new EmailAccount();
        $account->setAddress($address);
        
        $this->repository->save($account);
        
        return $account;
    }
}
```

### Email Server Development (Haraka)

#### Plugin Structure

```javascript
// email-server/plugins/custom_storage.js
const Plugin = require('./plugin');

class CustomStoragePlugin extends Plugin {
    constructor() {
        super();
        this.load_config();
    }

    load_config() {
        this.config = this.config.get('custom_storage.ini');
    }

    hook_queue_outbound(next, connection) {
        // Plugin implementation
        next();
    }
}

module.exports = CustomStoragePlugin;
```

## 🐛 Debugging

### Development Tools

#### Frontend Debugging

```bash
# Vue Devtools (browser extension)
# Chrome: Vue.js devtools
# Firefox: Vue.js devtools

# Nuxt DevTools
pnpm dev:frontend
# Open http://localhost:3000/__nuxt_devtools__/
```

#### Backend Debugging

```bash
# Symfony Profiler
# Available at /_profiler when APP_ENV=dev

# Debug routes
php bin/console debug:router

# Debug services
php bin/console debug:container
```

#### Database Debugging

```bash
# MongoDB shell
docker-compose exec mongodb mongosh

# View collections
use techsci
show collections
db.emailAccounts.find()
```

### Common Issues

#### Port Conflicts

```bash
# Check port usage
lsof -i :3000
lsof -i :8000
lsof -i :27017

# Stop conflicting services
sudo systemctl stop mongodb
sudo systemctl stop redis
```

#### Docker Issues

```bash
# Reset Docker environment
docker-compose down -v
docker system prune -f
docker-compose up -d

# View container logs
docker-compose logs -f [service-name]
```

#### Permission Issues

```bash
# Fix file permissions
sudo chown -R $USER:$USER .
chmod -R 755 .

# Docker volume permissions
docker-compose exec backend chown -R www-data:www-data /var/www/html
```

## 📊 Performance Guidelines

### Frontend Performance

#### Bundle Size Optimization

- Use dynamic imports for large components
- Implement proper code splitting
- Optimize images and assets
- Use Nuxt's built-in optimizations

```typescript
// ✅ Good - Dynamic import
const HeavyComponent = defineAsyncComponent(() => import('~/components/HeavyComponent.vue'))

// ❌ Bad - Static import of heavy component
import HeavyComponent from '~/components/HeavyComponent.vue'
```

#### State Management

- Minimize reactive state
- Use `readonly()` for immutable data
- Implement proper caching strategies

### Backend Performance

#### Database Optimization

- Use proper MongoDB indexes
- Implement pagination for large datasets
- Use aggregation pipelines efficiently

```php
// ✅ Good - Indexed query with pagination
#[ODM\Index(keys: ['createdAt' => 'desc'])]
class EmailMessage
{
    // ...
}

// Repository method
public function findPaginated(int $page, int $limit): array
{
    return $this->createQueryBuilder()
        ->sort('createdAt', 'desc')
        ->skip(($page - 1) * $limit)
        ->limit($limit)
        ->getQuery()
        ->execute()
        ->toArray();
}
```

#### API Optimization

- Implement proper HTTP caching
- Use compression for responses
- Optimize serialization

### Email Server Performance

#### Connection Management

- Implement connection pooling
- Use proper timeout settings
- Monitor memory usage

```javascript
// config/smtp.ini
[smtp]
max_connections=100
timeout=30000
```

## 🔒 Security Guidelines

### Authentication & Authorization

#### JWT Token Handling

```typescript
// ✅ Good - Secure token storage
const token = useCookie('auth-token', {
  httpOnly: true,
  secure: true,
  sameSite: 'strict'
})

// ❌ Bad - Insecure storage
localStorage.setItem('token', jwtToken)
```

#### API Security

```php
// ✅ Good - Rate limiting
#[Route('/api/accounts', methods: ['POST'])]
#[RateLimit(limit: 10, window: '1 minute')]
public function createAccount(): Response
{
    // Implementation
}
```

### Input Validation

#### Frontend Validation

```typescript
// Use Zod for schema validation
import { z } from 'zod'

const emailSchema = z.object({
  address: z.string().email(),
  password: z.string().min(8)
})

const validateEmail = (data: unknown) => {
  return emailSchema.safeParse(data)
}
```

#### Backend Validation

```php
// Use Symfony Validator
use Symfony\Component\Validator\Constraints as Assert;

class CreateAccountDto
{
    #[Assert\Email]
    #[Assert\NotBlank]
    public string $address;

    #[Assert\Length(min: 8)]
    #[Assert\NotBlank]
    public string $password;
}
```

### Data Protection

#### Sensitive Data Handling

- Never log passwords or tokens
- Use encryption for sensitive data
- Implement proper data retention policies

```php
// ✅ Good - Sanitized logging
$this->logger->info('Account created', [
    'accountId' => $account->getId(),
    'address' => substr($account->getAddress(), 0, 3) . '***'
]);

// ❌ Bad - Logging sensitive data
$this->logger->info('Account created', [
    'password' => $password
]);
```

## 🌍 Internationalization (i18n)

### Frontend i18n (Nuxt.js)

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: ['@nuxtjs/i18n'],
  i18n: {
    locales: ['en', 'fr', 'es'],
    defaultLocale: 'en'
  }
})

// Use in components
const { t } = useI18n()
const title = t('email.list.title')
```

### Backend i18n (Symfony)

```php
// src/Controller/EmailController.php
use Symfony\Contracts\Translation\TranslatorInterface;

public function __construct(
    private TranslatorInterface $translator
) {}

public function createAccount(): Response
{
    $message = $this->translator->trans('account.created.success');
    // ...
}
```

## 📚 Documentation Standards

### Code Documentation

#### TypeScript/JavaScript

```typescript
/**
 * Fetches emails for a specific account with pagination
 * @param accountId - The unique account identifier
 * @param options - Pagination and filtering options
 * @returns Promise resolving to paginated email list
 * @throws {APIError} When account is not found or access denied
 */
async function fetchEmails(
  accountId: string,
  options: {
    page?: number
    limit?: number
    filter?: EmailFilter
  } = {}
): Promise<PaginatedEmails> {
  // Implementation
}
```

#### PHP

```php
/**
 * Creates a new email account with the specified address
 *
 * @param string $address The email address for the new account
 * @param string $password The account password
 * 
 * @return EmailAccount The created account entity
 * 
 * @throws InvalidArgumentException When address format is invalid
 * @throws DuplicateAccountException When account already exists
 */
public function createAccount(string $address, string $password): EmailAccount
{
    // Implementation
}
```

### API Documentation

#### OpenAPI Annotations

```php
use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/accounts',
    summary: 'Create a new email account',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                'address' => new OA\Property(property: 'address', type: 'string', format: 'email'),
                'password' => new OA\Property(property: 'password', type: 'string', minLength: 8)
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Account created successfully'),
        new OA\Response(response: 400, description: 'Invalid input'),
        new OA\Response(response: 409, description: 'Account already exists')
    ]
)]
```

## 🚨 Error Handling

### Frontend Error Handling

```typescript
// composables/useError.ts
export const useError = () => {
  const handleError = (error: Error | APIError) => {
    console.error('Application error:', error)
    
    if (error instanceof APIError) {
      // Handle API errors
      showToast({
        type: 'error',
        message: error.message
      })
    } else {
      // Handle general errors
      showToast({
        type: 'error',
        message: 'An unexpected error occurred'
      })
    }
  }

  return { handleError }
}
```

### Backend Error Handling

```php
// src/EventListener/ExceptionListener.php
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        $response = new JsonResponse([
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode()
            ]
        ], 500);
        
        $event->setResponse($response);
    }
}
```

## 🎯 Issue Reporting

### Bug Reports

When reporting bugs, please include:

1. **Environment Information**
   - OS and version
   - Node.js version
   - PHP version
   - Docker version

2. **Steps to Reproduce**
   - Detailed step-by-step instructions
   - Expected vs actual behavior
   - Screenshots/videos if applicable

3. **Error Information**
   - Full error messages
   - Relevant log excerpts
   - Browser console errors

### Feature Requests

For feature requests, please include:

1. **Problem Statement**
   - What problem does this solve?
   - Who would benefit from this feature?

2. **Proposed Solution**
   - Detailed description of the feature
   - Alternative solutions considered

3. **Additional Context**
   - Examples or mockups
   - Related issues or discussions

## 🏆 Recognition

### Contributors

We appreciate all contributions! Contributors will be:

- Listed in the project README
- Mentioned in release notes for significant contributions
- Eligible for special contributor badges

### Types of Contributions

- 🐛 **Bug fixes**
- ✨ **New features**
- 📚 **Documentation improvements**
- 🎨 **UI/UX enhancements**
- 🧪 **Test coverage improvements**
- 🔧 **Infrastructure/tooling**
- 🌍 **Translations**

## 📞 Getting Help

### Community Support

- **GitHub Discussions**: For general questions and discussions
- **GitHub Issues**: For bug reports and feature requests
- **Discord**: Real-time chat with the community
- **Stack Overflow**: Tag questions with `techsci-labs`

### Maintainer Contact

- **Email**: <dev@techsci.dev>
- **Twitter**: @techscilabs
- **LinkedIn**: TechSci Labs

## 📋 Checklist for New Contributors

Before your first contribution:

- [ ] Read this contributing guide
- [ ] Set up development environment
- [ ] Run all tests successfully
- [ ] Join our Discord community
- [ ] Introduce yourself in GitHub Discussions
- [ ] Look for "good first issue" labels

---

**Thank you for contributing to TechSci Labs Email Testing Platform! 🚀**

Your contributions help make email testing better for developers worldwide.