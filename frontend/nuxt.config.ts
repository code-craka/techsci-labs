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
  devtools: { enabled: false },
  typescript: {
    strict: true,
    typeCheck: false // Disable for faster dev startup
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
      // Use local development URLs for development
      apiBase: process.env.API_BASE_URL || (process.env.NODE_ENV === 'production' ? 'https://api.techsci.dev' : 'http://localhost:8000/api'),
      mercureUrl: process.env.MERCURE_URL || (process.env.NODE_ENV === 'production' ? 'https://mercure.techsci.dev/.well-known/mercure' : 'http://localhost:3001/.well-known/mercure'),
      webmailUrl: process.env.WEBMAIL_URL || (process.env.NODE_ENV === 'production' ? 'https://webmail.techsci.dev' : 'http://localhost:8080'),
      appEnv: process.env.NODE_ENV || 'development',
      siteUrl: process.env.SITE_URL || (process.env.NODE_ENV === 'production' ? 'https://techsci.dev' : 'http://localhost:3000'),
      docsUrl: process.env.DOCS_URL || 'https://docs.techsci.dev',
      
      // API Configuration
      apiTimeout: parseInt(process.env.API_TIMEOUT || '30000'),
      apiRetries: parseInt(process.env.API_RETRIES || '3'),
      
      // Feature flags
      enableRealtime: process.env.ENABLE_REALTIME !== 'false',
      enableNotifications: process.env.ENABLE_NOTIFICATIONS !== 'false',
      enableDebug: process.env.NODE_ENV === 'development'
    }
  },

  // Build Configuration
  build: {
    transpile: ['@headlessui/vue'],
    // Code splitting optimization
    splitChunks: {
      layouts: true,
      pages: true,
      commons: true
    }
  },

  // Nitro Configuration
  nitro: {
    preset: 'node-server',
    experimental: {
      wasm: true
    },
    devStorage: {
      redis: {
        driver: 'redis',
        // Optional config
      }
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

  // Security Headers & CSP
  routeRules: {
    '/**': {
      headers: {
        // Content Security Policy for email content protection
        'Content-Security-Policy': [
          "default-src 'self'",
          "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
          "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
          "img-src 'self' data: https: blob:",
          "font-src 'self' https://fonts.gstatic.com data:",
          "connect-src 'self' ws: wss: http://localhost:* https://api.techsci.dev https://mercure.techsci.dev",
          "media-src 'self' data: blob:",
          "object-src 'none'",
          "base-uri 'self'",
          "form-action 'self'",
          "frame-ancestors 'none'",
          "upgrade-insecure-requests"
        ].join('; '),
        // Additional security headers
        'X-Frame-Options': 'DENY',
        'X-Content-Type-Options': 'nosniff',
        'X-XSS-Protection': '1; mode=block',
        'Referrer-Policy': 'strict-origin-when-cross-origin',
        'Permissions-Policy': 'camera=(), microphone=(), geolocation=(), payment=()'
      }
    },
    '/api/**': {
      cors: true,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,HEAD,PUT,PATCH,POST,DELETE',
        'Access-Control-Allow-Headers': 'Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY'
      }
    },
    // Email content iframe with relaxed CSP for safe rendering
    '/email-content/**': {
      headers: {
        'Content-Security-Policy': [
          "default-src 'none'",
          "img-src 'self' data: https: blob:",
          "style-src 'self' 'unsafe-inline'",
          "font-src 'self' data:",
          "media-src 'self' data: blob:",
          "object-src 'none'",
          "script-src 'none'",
          "frame-ancestors 'self'"
        ].join('; '),
        'X-Frame-Options': 'SAMEORIGIN'
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
    },
    optimizeDeps: {
      include: ['vue', '@headlessui/vue', '@heroicons/vue/24/outline']
    },
    build: {
      // Advanced code splitting
      rollupOptions: {
        output: {
          manualChunks: {
            // Vendor chunks
            'vendor-vue': ['vue', '@vue/runtime-core', '@vue/runtime-dom'],
            'vendor-ui': ['@headlessui/vue', '@heroicons/vue/24/outline'],
            'vendor-utils': ['@vueuse/core', '@vueuse/nuxt'],
            // Feature-based chunks
            'email-components': ['~/components/email/**'],
            'dashboard-components': ['~/components/dashboard/**'],
            'auth-components': ['~/components/auth/**']
          }
        }
      },
      // Chunk size warnings
      chunkSizeWarningLimit: 1000,
      // CSS code splitting
      cssCodeSplit: true,
      // Source maps for debugging
      sourcemap: process.env.NODE_ENV === 'development'
    }
  },

  // Hooks
  hooks: {
    'build:before': () => {
      // eslint-disable-next-line no-console
      console.log('🏗️  Building TechSci Labs Email Testing Platform...')
    },
    'build:done': () => {
      // eslint-disable-next-line no-console
      console.log('✅ Build completed successfully!')
    }
  }
})

