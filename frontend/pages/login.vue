<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <img 
          src="/logo.svg" 
          alt="TechSci Labs" 
          class="h-12 w-auto"
        >
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
        Sign in to your account
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
        Or
        <NuxtLink 
          to="/register" 
          class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
        >
          create a new account
        </NuxtLink>
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Email address
            </label>
            <div class="mt-1">
              <UInput
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                :disabled="isLoading"
                placeholder="Enter your email"
                :ui="{ 
                  base: 'w-full',
                  color: { 
                    red: { 
                      outline: 'shadow-sm bg-red-50 dark:bg-red-950 text-red-900 dark:text-red-100 ring-1 ring-inset ring-red-300 dark:ring-red-700 placeholder:text-red-400 focus:ring-2 focus:ring-inset focus:ring-red-500'
                    }
                  }
                }"
                :color="validationErrors.email ? 'red' : 'white'"
              />
              <p v-if="validationErrors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ validationErrors.email }}
              </p>
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Password
            </label>
            <div class="mt-1">
              <UInput
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                required
                :disabled="isLoading"
                placeholder="Enter your password"
                :ui="{ 
                  base: 'w-full',
                  color: { 
                    red: { 
                      outline: 'shadow-sm bg-red-50 dark:bg-red-950 text-red-900 dark:text-red-100 ring-1 ring-inset ring-red-300 dark:ring-red-700 placeholder:text-red-400 focus:ring-2 focus:ring-inset focus:ring-red-500'
                    }
                  }
                }"
                :color="validationErrors.password ? 'red' : 'white'"
              />
              <p v-if="validationErrors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ validationErrors.password }}
              </p>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <UCheckbox
                id="remember-me"
                v-model="form.rememberMe"
                :disabled="isLoading"
                label="Remember me"
              />
            </div>

            <div class="text-sm">
              <NuxtLink 
                to="/forgot-password" 
                class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
              >
                Forgot your password?
              </NuxtLink>
            </div>
          </div>

          <!-- Error Message -->
          <UAlert 
            v-if="error" 
            color="red" 
            variant="soft"
            :title="'Login Failed'"
            :description="error"
            :close-button="{ color: 'red', variant: 'link' }"
            @close="clearError"
          />

          <!-- Submit Button -->
          <div>
            <UButton
              type="submit"
              :loading="isLoading"
              :disabled="!isFormValid || isLoading"
              block
              size="lg"
              class="flex w-full justify-center"
            >
              {{ isLoading ? 'Signing in...' : 'Sign in' }}
            </UButton>
          </div>
        </form>

        <!-- Social Login (Optional) -->
        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300 dark:border-gray-600" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                Or continue with
              </span>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <UButton
              variant="outline"
              color="gray"
              :disabled="isLoading"
              @click="handleGoogleLogin"
            >
              <template #leading>
                <Icon name="logos:google-icon" class="w-5 h-5" />
              </template>
              Google
            </UButton>

            <UButton
              variant="outline"
              color="gray"
              :disabled="isLoading"
              @click="handleGithubLogin"
            >
              <template #leading>
                <Icon name="logos:github-icon" class="w-5 h-5" />
              </template>
              GitHub
            </UButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { z } from 'zod'
import { useAuthStore } from '~/stores/auth'
import { getValidationErrors, isApiError } from '~/utils/api'
import type { LoginRequest } from '~/types/api'

// Meta and SEO
definePageMeta({
  layout: 'auth',
  middleware: 'guest'
})

useSeoMeta({
  title: 'Login - TechSci Labs',
  description: 'Sign in to your TechSci Labs email testing account',
  ogTitle: 'Login - TechSci Labs',
  ogDescription: 'Sign in to your TechSci Labs email testing account'
})

// Validation Schema
const loginSchema = z.object({
  email: z.string()
    .min(1, 'Email is required')
    .email('Please enter a valid email address'),
  password: z.string()
    .min(1, 'Password is required')
    .min(6, 'Password must be at least 6 characters'),
  rememberMe: z.boolean().optional()
})

// State
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

// Form state
const form = ref({
  email: '',
  password: '',
  rememberMe: false
})

const validationErrors = ref<Record<string, string>>({})
const isLoading = computed(() => authStore.isLoading)
const error = computed(() => authStore.error)

// Computed
const isFormValid = computed(() => {
  try {
    loginSchema.parse(form.value)
    return true
  } catch {
    return false
  }
})

// Methods
function validateForm() {
  validationErrors.value = {}
  
  try {
    loginSchema.parse(form.value)
    return true
  } catch (error) {
    if (error instanceof z.ZodError) {
      error.errors.forEach(err => {
        if (err.path[0]) {
          validationErrors.value[err.path[0] as string] = err.message
        }
      })
    }
    return false
  }
}

async function handleLogin() {
  if (!validateForm()) {
    return
  }

  try {
    const credentials: LoginRequest = {
      email: form.value.email,
      password: form.value.password
    }

    await authStore.login(credentials)

    // Show success message
    toast.add({
      title: 'Welcome back!',
      description: `Successfully signed in as ${form.value.email}`,
      color: 'green',
      timeout: 3000
    })

    // Redirect to intended page or dashboard
    const redirectTo = route.query.redirect as string || '/dashboard'
    await router.push(redirectTo)

  } catch (error) {
    console.error('Login error:', error)
    
    // Handle validation errors
    if (isApiError(error)) {
      const apiValidationErrors = getValidationErrors(error)
      if (Object.keys(apiValidationErrors).length > 0) {
        validationErrors.value = { ...validationErrors.value, ...apiValidationErrors }
        return
      }
    }

    // Show error toast for unexpected errors
    toast.add({
      title: 'Login Failed',
      description: error instanceof Error ? error.message : 'An unexpected error occurred',
      color: 'red',
      timeout: 5000
    })
  }
}

async function handleGoogleLogin() {
  // TODO: Implement Google OAuth login
  toast.add({
    title: 'Coming Soon',
    description: 'Google login will be available soon',
    color: 'blue',
    timeout: 3000
  })
}

async function handleGithubLogin() {
  // TODO: Implement GitHub OAuth login
  toast.add({
    title: 'Coming Soon',
    description: 'GitHub login will be available soon',
    color: 'blue',
    timeout: 3000
  })
}

function clearError() {
  authStore.clearError()
}

// Auto-focus email field on mount
onMounted(() => {
  const emailInput = document.getElementById('email')
  if (emailInput) {
    emailInput.focus()
  }
})

// Redirect if already authenticated
watchEffect(() => {
  if (authStore.isAuthenticated) {
    const redirectTo = route.query.redirect as string || '/dashboard'
    router.push(redirectTo)
  }
})

// Pre-fill email from query params (e.g., from registration)
onMounted(() => {
  if (route.query.email && typeof route.query.email === 'string') {
    form.value.email = route.query.email
  }
})
</script>

<style scoped>
/* Additional styling if needed */
</style>