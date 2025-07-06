// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Compatibility Date
  compatibilityDate: '2025-07-07',
  
  // App Configuration
  app: {
    head: {
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1',
      title: 'TechSci Labs - Email Testing Platform',
      meta: [
        { name: 'description', content: 'Professional email testing service with temporary email addresses, API access, and real-time monitoring.' },
        { name: 'keywords', content: 'email testing, temporary email, SMTP testing, email development, API testing' },
        { name: 'author', content: 'TechSci Labs' },
        { property: 'og:type', content: 'website' },
        { property: 'og:title', content: 'TechSci Labs - Email Testing Platform' },
        { property: 'og:description', content: 'Professional email testing service with temporary email addresses and API access.' },
        { property: 'og:url', content: 'https://techsci.dev' },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'TechSci Labs - Email Testing Platform' },
        { name: 'twitter:description', content: 'Professional email testing service with temporary email addresses and API access.' }
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' },
        { rel: 'manifest', href: '/site.webmanifest' }
      ]
    }
  },

  // Development Configuration
  devtools: { enabled: true },
  typescript: {
    strict: true,
    typeCheck: true
  },

  // CSS Configuration
  css: [
    '~/assets/css/main.css'
  ],

  // Modules
  modules: [
    '@nuxt/ui',
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/color-mode'
  ],

  // UI Configuration
  ui: {
    global: true
  },

  // Color Mode Configuration
  colorMode: {
    preference: 'dark',
    fallback: 'light',
    hid: 'nuxt-color-mode-script',
    globalName: '__NUXT_COLOR_MODE__',
    componentName: 'ColorScheme',
    classPrefix: '',
    classSuffix: '',
    storageKey: 'nuxt-color-mode'
  },

  // Runtime Configuration
  runtimeConfig: {
    // Private keys (only available on server-side)
    apiSecret: process.env.API_SECRET || 'default-secret',
    jwtSecret: process.env.JWT_SECRET || 'jwt-secret',
    mongoUri: process.env.MONGODB_URI || 'mongodb://localhost:27017/techsci',
    mercureSecret: process.env.MERCURE_JWT_SECRET || 'mercure-secret',
    
    // Public keys (exposed to client-side)
    public: {
      apiBase: process.env.API_BASE_URL || 'https://api.techsci.dev',
      mercureUrl: process.env.MERCURE_URL || 'https://mercure.techsci.dev/.well-known/mercure',
      webmailUrl: process.env.WEBMAIL_URL || 'https://webmail.techsci.dev',
      appEnv: process.env.NODE_ENV || 'development',
      siteUrl: process.env.SITE_URL || 'https://techsci.dev',
      docsUrl: process.env.DOCS_URL || 'https://docs.techsci.dev'
    }
  },

  // Build Configuration
  build: {
    transpile: ['@headlessui/vue']
  },

  // Nitro Configuration
  nitro: {
    preset: 'node-server',
    experimental: {
      wasm: true
    }
  },

  // Server Configuration
  ssr: true,
  
  // Generate Configuration
  generate: {
    routes: ['/']
  },


  // Experimental Features
  experimental: {
    typedPages: true,
    viewTransition: true
  },

  // Security Headers
  routeRules: {
    '/api/**': {
      cors: true,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,HEAD,PUT,PATCH,POST,DELETE',
        'Access-Control-Allow-Headers': 'Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY'
      }
    },
    '/admin/**': { ssr: false, prerender: false },
    '/dashboard/**': { ssr: false, prerender: false },
    '/login': { prerender: true },
    '/register': { prerender: true },
    '/': { prerender: true },
    '/docs/**': { prerender: true }
  },

  // Tailwind CSS Configuration
  tailwindcss: {
    cssPath: '~/assets/css/tailwind.css',
    configPath: 'tailwind.config.ts',
    exposeConfig: false,
    viewer: true
  },


  // Vite Configuration
  vite: {
    define: {
      __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false
    },
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@import "~/assets/scss/variables.scss";'
        }
      }
    }
  },

  // Hooks
  hooks: {
    'build:before': () => {
      console.log('🏗️  Building TechSci Labs Email Testing Platform...')
    },
    'build:done': () => {
      console.log('✅ Build completed successfully!')
    }
  }
})

