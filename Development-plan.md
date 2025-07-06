# 🚀 TechSci Labs Email Testing Platform - Claude Code Development Plan

## 📋 Project Overview for Claude Code

**Project**: TechSci Labs Email Testing Platform  
**Status**: Phase 3 Implementation (Core Features)  
**Architecture**: Multi-service monorepo with Symfony API, Nuxt.js frontend, Haraka email server  
**Integration**: Laravel Nightwatch monitoring

## 🎯 Current Project State

### ✅ Completed

- Project structure with workspaces (frontend, backend, email-server, shared)
- Backend Symfony dependencies installed
- Docker infrastructure (MongoDB, Redis, Mercure)
- JWT keys generated
- All PHP class designs (entities, repositories, services)
- Configuration files designed
- Package.json workspace setup

### 🔄 Current Phase: Implementation

Need to create all PHP files, configure services, and integrate components

## 📁 Project Structure

```
techsci-labs/                     # Root monorepo
├── backend/                      # Symfony 7.1 + API Platform 3.2
│   ├── src/
│   │   ├── Entity/              # 7 entity classes (Domain, Account, Mailbox, Message, EmailAddress, Attachment, Token)
│   │   ├── Repository/          # 6 repository classes
│   │   ├── Service/             # 3 core services (EmailProcessor, AuthService, MercurePublisher, NightwatchService)
│   │   ├── EventListener/       # 2 event listeners (MessageListener, AuthListener)
│   │   ├── Controller/          # API controllers (to be created)
│   │   ├── Security/            # Authentication classes
│   │   └── Command/             # Console commands
│   ├── config/packages/         # Configuration files
│   ├── composer.json            # PHP dependencies with Nightwatch
│   └── .env.local              # Environment variables
├── frontend/                    # Nuxt.js 3.x
├── email-server/               # Haraka SMTP server
├── shared/                     # Shared TypeScript types
├── docker-compose.yml          # Infrastructure services
└── package.json               # Workspace configuration
```

## 🎯 Development Phases for Claude Code

### Phase 1: Core Backend Implementation (Priority)

**Goal**: Complete all PHP classes and get API working

#### 1.1 Create All Entity Classes

**Location**: `backend/src/Entity/`
**Files needed**:

- `Domain.php` - Domain management entity
- `Account.php` - Email account with aliasing/catch-all
- `Mailbox.php` - Email folder management
- `Message.php` - Email message with attachments
- `EmailAddress.php` - Embeddable email address
- `Attachment.php` - File attachment management
- `Token.php` - API authentication tokens

**Key requirements**:

- MongoDB ODM annotations
- API Platform attributes
- Validation constraints
- Serialization groups
- Proper relationships

#### 1.2 Create Repository Classes

**Location**: `backend/src/Repository/`
**Files needed**:

- `DomainRepository.php`
- `AccountRepository.php`
- `MailboxRepository.php`
- `MessageRepository.php`
- `AttachmentRepository.php`
- `TokenRepository.php`

**Key requirements**:

- Extend ServiceDocumentRepository
- MongoDB aggregation queries
- Email resolution logic (plus-aliasing, catch-all)
- User authentication interfaces

#### 1.3 Create Service Classes

**Location**: `backend/src/Service/`
**Files needed**:

- `EmailProcessor.php` - Process incoming emails
- `AuthService.php` - Authentication and tokens
- `MercurePublisher.php` - Real-time notifications
- `NightwatchService.php` - Laravel Nightwatch integration

**Key requirements**:

- Dependency injection
- Error handling and logging
- Nightwatch integration
- Real-time event publishing

#### 1.4 Create Event Listeners

**Location**: `backend/src/EventListener/`
**Files needed**:

- `MessageListener.php` - Message lifecycle events
- `AuthListener.php` - Authentication events

#### 1.5 Configuration Files

**Location**: `backend/config/packages/`
**Files needed**:

- `api_platform.yaml`
- `doctrine_mongodb.yaml`
- `mercure.yaml`
- `security.yaml`
- `lexik_jwt_authentication.yaml`
- `nightwatch.yaml`

### Phase 2: API Controllers and Security

**Goal**: Create REST API endpoints and authentication

#### 2.1 Security Classes

**Location**: `backend/src/Security/`

- `ApiKeyAuthenticator.php`
- `JwtAuthenticator.php`

#### 2.2 API Controllers

**Location**: `backend/src/Controller/Api/`

- `DomainController.php`
- `AccountController.php`
- `AuthController.php`
- `NightwatchWebhookController.php`

#### 2.3 Console Commands

**Location**: `backend/src/Command/`

- `NightwatchSyncCommand.php`

### Phase 3: Email Server Integration

**Goal**: Setup Haraka email server with MongoDB integration

#### 3.1 Haraka Plugins

**Location**: `email-server/plugins/`

- `mongodb-storage.js`
- `account-resolver.js`
- `plus-aliasing.js`
- `catch-all.js`
- `mercure-notifier.js`

#### 3.2 Email Server Configuration

**Location**: `email-server/config/`

- SMTP settings
- Plugin configuration
- Logging setup

### Phase 4: Frontend Dashboard

**Goal**: Create Nuxt.js dashboard for email management

#### 4.1 Core Components

**Location**: `frontend/components/`

- Email viewer and listing
- Account management
- Real-time notifications
- Authentication forms

#### 4.2 Pages and Layouts

**Location**: `frontend/pages/`

- Dashboard home
- Email management
- Account settings
- API documentation

### Phase 5: Testing and Deployment

**Goal**: Comprehensive testing and production setup

#### 5.1 Backend Tests

- Unit tests for entities and services
- Integration tests for API endpoints
- Nightwatch integration tests

#### 5.2 Frontend Tests

- Component tests
- E2E tests
- API integration tests

## 🛠️ Claude Code Instructions

### For Each Development Phase

1. **File Creation**:
   - Check existing file structure
   - Create files with proper namespaces
   - Ensure PSR-4 autoloading compliance
   - Add proper file headers and documentation

2. **Code Quality**:
   - Use PHP 8.2+ features (attributes, typed properties)
   - Follow Symfony best practices
   - Implement proper error handling
   - Add comprehensive logging

3. **Integration Points**:
   - MongoDB ODM integration
   - API Platform annotations
   - Mercure real-time events
   - Nightwatch monitoring calls

4. **Testing Strategy**:
   - Unit tests for business logic
   - Integration tests for API endpoints
   - Mock external services (Nightwatch)

### Current Environment Setup

- **PHP**: 8.4.8 ✅
- **Composer**: 2.8.3 ✅  
- **Node.js**: 22.15.0 ✅
- **pnpm**: 8.15.1 ✅
- **Docker**: 28.2.2 ✅
- **Services**: MongoDB, Redis, Mercure running via Docker Compose

### Key Dependencies Installed

- Symfony 7.1 framework
- API Platform 3.2
- MongoDB ODM Bundle 5.0
- JWT Authentication Bundle 3.1
- Mercure Bundle 0.3.7
- Laravel Nightwatch 2.1
- Guzzle HTTP 7.8

## 🎯 Immediate Next Steps for Claude Code

1. **Start with Entity Creation**: Begin with `Domain.php` entity and work through all 7 entities
2. **Database Integration**: Ensure MongoDB connection and schema work
3. **API Testing**: Test each endpoint as it's created
4. **Nightwatch Integration**: Connect monitoring from the beginning
5. **Real-time Features**: Implement Mercure SSE for live updates

## 📊 Success Metrics

- [ ] All 7 entities created and working
- [ ] All 6 repositories with proper queries
- [ ] All 4 services with business logic
- [ ] API endpoints responding correctly
- [ ] MongoDB collections created and indexed
- [ ] JWT authentication working
- [ ] Nightwatch integration active
- [ ] Real-time notifications via Mercure
- [ ] Email processing pipeline functional

## 🚨 Critical Integration Points

1. **MongoDB Schema**: Ensure proper indexing for performance
2. **API Platform**: Correct annotations for automatic REST API
3. **Nightwatch**: Proper error handling for external service
4. **Mercure**: Real-time event publishing
5. **Email Processing**: Haraka to MongoDB to API integration

## 📝 Development Notes

- All PHP classes need proper `declare(strict_types=1);`
- Use MongoDB ODM annotations, not Doctrine ORM
- API Platform 3.2 uses PHP attributes, not annotations
- Nightwatch integration should be optional (fail gracefully)
- All services should implement proper logging
- Real-time events should be published for all major operations

This plan provides Claude Code with the complete roadmap to implement TechSci Labs efficiently and correctly! 🚀
