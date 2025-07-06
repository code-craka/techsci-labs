// Nuxt 3 type definitions
export {}

declare global {
  const defineNuxtConfig: typeof import('nuxt/config')['defineNuxtConfig']
  const defineNuxtPlugin: typeof import('#app')['defineNuxtPlugin']
  const definePageMeta: typeof import('#app')['definePageMeta']
  const defineNuxtRouteMiddleware: typeof import('#app')['defineNuxtRouteMiddleware']
}

// Extend Nuxt runtime config
declare module 'nuxt/schema' {
  interface RuntimeConfig {
    apiSecret: string
    jwtSecret: string
    mongoUri: string
    mercureSecret: string
  }

  interface PublicRuntimeConfig {
    apiBase: string
    mercureUrl: string
    webmailUrl: string
    appEnv: string
    siteUrl: string
    docsUrl: string
  }
}

// Extend app context
declare module '#app' {
  interface NuxtApp {
    $toast: import('vue-toastification').ToastInterface
    $auth: ReturnType<typeof import('~/composables/useAuth')['useAuth']>
  }
}

// Component meta types
declare module 'vue-router' {
  interface RouteMeta {
    auth?: boolean
    roles?: string[]
    layout?: string
    middleware?: string | string[]
    title?: string
    description?: string
  }
}