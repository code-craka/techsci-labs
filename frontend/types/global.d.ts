// Global type definitions for TechSci Labs Email Testing Platform

declare global {
  interface Window {
    gtag?: (...args: any[]) => void
    dataLayer?: any[]
  }

  namespace NodeJS {
    interface ProcessEnv {
      NODE_ENV: 'development' | 'production' | 'test'
      API_BASE_URL?: string
      MERCURE_URL?: string
      SITE_URL?: string
      DOCS_URL?: string
      WEBMAIL_URL?: string
    }
  }
}

// Nuxt 3 module declarations
declare module '#app' {
  interface NuxtApp {}
}

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $toast: import('vue-toastification').ToastInterface
  }
}

// Auto-import types for Nuxt UI
declare module '@nuxt/ui' {
  export * from '@nuxt/ui/dist/runtime/types'
}

export {}