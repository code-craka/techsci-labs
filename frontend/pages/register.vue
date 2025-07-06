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
        Create your account
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
        Already have an account?
        <NuxtLink 
          to="/login" 
          class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
        >
          Sign in here
        </NuxtLink>
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Display Name Field -->
          <div>
            <label for="displayName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Display Name (Optional)
            </label>
            <div class="mt-1">
              <UInput
                id="displayName"
                v-model="form.displayName"
                type="text"
                autocomplete="name"
                :disabled="isLoading"
                placeholder="Enter your display name"
                :ui="{ 
                  base: 'w-full',
                  color: { 
                    red: { 
                      outline: 'shadow-sm bg-red-50 dark:bg-red-950 text-red-900 dark:text-red-100 ring-1 ring-inset ring-red-300 dark:ring-red-700 placeholder:text-red-400 focus:ring-2 focus:ring-inset focus:ring-red-500'
                    }
                  }
                }"
                :color="validationErrors.displayName ? 'red' : 'white'"
              />
              <p v-if="validationErrors.displayName" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ validationErrors.displayName }}
              </p>
            </div>
          </div>

          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Email address *
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

          <!-- Domain Selection -->
          <div>
            <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Domain *
            </label>
            <div class="mt-1">
              <USelectMenu
                id="domain"
                v-model="form.domain"
                :options="domainOptions"
                :loading="domainsLoading"
                :disabled="isLoading || domainsLoading"
                placeholder="Select a domain"
                :ui="{ 
                  base: 'w-full',
                  color: { 
                    red: { 
                      outline: 'shadow-sm bg-red-50 dark:bg-red-950 text-red-900 dark:text-red-100 ring-1 ring-inset ring-red-300 dark:ring-red-700 placeholder:text-red-400 focus:ring-2 focus:ring-inset focus:ring-red-500'
                    }
                  }
                }"
                :color="validationErrors.domain ? 'red' : 'white'"
                value-attribute="value"
                option-attribute="label"
              />
              <p v-if="validationErrors.domain" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ validationErrors.domain }}
              </p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Your full email will be: {{ form.email }}@{{ selectedDomainName }}
              </p>
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Password *
            </label>
            <div class="mt-1">
              <UInput
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                required
                :disabled="isLoading"
                placeholder="Create a password"
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
              <!-- Password Strength Indicator -->
              <div class="mt-2">
                <div class="flex space-x-1">
                  <div 
                    v-for="i in 4" 
                    :key="i"
                    class="h-1 flex-1 rounded-full"
                    :class="getPasswordStrengthColor(i)"
                  />
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ passwordStrengthText }}
                </p>
              </div>
            </div>
          </div>

          <!-- Confirm Password Field -->
          <div>
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Confirm Password *
            </label>
            <div class="mt-1">
              <UInput
                id="confirmPassword"
                v-model="form.confirmPassword"
                type="password"
                autocomplete="new-password"
                required
                :disabled="isLoading"
                placeholder="Confirm your password"
                :ui="{ 
                  base: 'w-full',
                  color: { 
                    red: { 
                      outline: 'shadow-sm bg-red-50 dark:bg-red-950 text-red-900 dark:text-red-100 ring-1 ring-inset ring-red-300 dark:ring-red-700 placeholder:text-red-400 focus:ring-2 focus:ring-inset focus:ring-red-500'
                    }
                  }
                }"
                :color="validationErrors.confirmPassword ? 'red' : 'white'"
              />
              <p v-if="validationErrors.confirmPassword" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ validationErrors.confirmPassword }}
              </p>
            </div>
          </div>

          <!-- Terms and Privacy -->
          <div>
            <div class="flex items-start">
              <UCheckbox
                id="terms"
                v-model="form.acceptTerms"
                :disabled="isLoading"
                :color="validationErrors.acceptTerms ? 'red' : 'primary'"
              />
              <div class="ml-3 text-sm">
                <label for="terms" class="text-gray-700 dark:text-gray-300">
                  I agree to the 
                  <NuxtLink 
                    to="/terms" 
                    target="_blank"
                    class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                  >
                    Terms of Service
                  </NuxtLink>
                  and
                  <NuxtLink 
                    to="/privacy" 
                    target="_blank"
                    class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                  >
                    Privacy Policy
                  </NuxtLink>
                </label>
                <p v-if="validationErrors.acceptTerms" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ validationErrors.acceptTerms }}
                </p>
              </div>
            </div>
          </div>

          <!-- Error Message -->
          <UAlert 
            v-if="error" 
            color="red" 
            variant="soft"
            :title="'Registration Failed'"
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
              {{ isLoading ? 'Creating account...' : 'Create account' }}
            </UButton>
          </div>
        </form>

        <!-- Social Registration (Optional) -->
        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300 dark:border-gray-600" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                Or register with
              </span>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <UButton
              variant="outline"
              color="gray"
              :disabled="isLoading"
              @click="handleGoogleRegister"
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
              @click="handleGithubRegister"
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
import { useDomainApi } from '~/composables/useApi'
import { getValidationErrors, isApiError } from '~/utils/api'
import type { Domain } from '~/types/api'

// Meta and SEO
definePageMeta({
  layout: 'auth',
  middleware: 'guest'
})

useSeoMeta({
  title: 'Register - TechSci Labs',
  description: 'Create your TechSci Labs email testing account',
  ogTitle: 'Register - TechSci Labs',
  ogDescription: 'Create your TechSci Labs email testing account'
})

// Validation Schema
const registerSchema = z.object({
  displayName: z.string().optional(),
  email: z.string()
    .min(1, 'Email is required')
    .email('Please enter a valid email address')
    .max(50, 'Email must be less than 50 characters'),
  domain: z.string()
    .min(1, 'Domain is required'),
  password: z.string()
    .min(8, 'Password must be at least 8 characters')
    .max(100, 'Password must be less than 100 characters')
    .regex(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/, 'Password must contain at least one lowercase letter, one uppercase letter, and one number'),
  confirmPassword: z.string()
    .min(1, 'Please confirm your password'),
  acceptTerms: z.boolean()
    .refine(val => val === true, 'You must accept the terms and conditions')
}).refine(data => data.password === data.confirmPassword, {
  message: "Passwords don't match",
  path: ["confirmPassword"]
})

// State
const authStore = useAuthStore()
const domainApi = useDomainApi()
const router = useRouter()
const route = useRoute()
const toast = useToast()

// Form state
const form = ref({
  displayName: '',
  email: '',
  domain: '',
  password: '',
  confirmPassword: '',
  acceptTerms: false
})

const validationErrors = ref<Record<string, string>>({})
const domains = ref<Domain[]>([])
const domainsLoading = ref(false)

// Computed
const isLoading = computed(() => authStore.isLoading)
const error = computed(() => authStore.error)

const isFormValid = computed(() => {
  try {
    registerSchema.parse(form.value)
    return true
  } catch {
    return false
  }
})

const domainOptions = computed(() => {
  return domains.value
    .filter(domain => domain.isActive)
    .map(domain => ({
      value: domain.id,
      label: domain.name,
      domain: domain
    }))
})

const selectedDomainName = computed(() => {
  const selected = domainOptions.value.find(option => option.value === form.value.domain)
  return selected ? selected.label : ''
})

const passwordStrength = computed(() => {
  const password = form.value.password
  if (!password) return 0
  
  let strength = 0
  
  // Length check
  if (password.length >= 8) strength++
  if (password.length >= 12) strength++
  
  // Character variety checks
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++
  if (/\d/.test(password)) strength++
  if (/[^a-zA-Z\d]/.test(password)) strength++
  
  return Math.min(strength, 4)
})

const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value
  const texts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong']
  return texts[strength] || 'Very Weak'
})

// Methods
function getPasswordStrengthColor(position: number) {
  const strength = passwordStrength.value
  if (position <= strength) {
    if (strength <= 1) return 'bg-red-500'
    if (strength <= 2) return 'bg-yellow-500'
    if (strength <= 3) return 'bg-blue-500'
    return 'bg-green-500'
  }
  return 'bg-gray-200 dark:bg-gray-700'
}

function validateForm() {
  validationErrors.value = {}
  
  try {
    registerSchema.parse(form.value)
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

async function loadDomains() {
  domainsLoading.value = true
  
  try {
    const response = await domainApi.getDomains({ isActive: true })
    domains.value = response['hydra:member'] || []
  } catch (error) {
    console.error('Failed to load domains:', error)
    toast.add({
      title: 'Error',
      description: 'Failed to load available domains',
      color: 'red',
      timeout: 5000
    })
  } finally {
    domainsLoading.value = false
  }
}

async function handleRegister() {
  if (!validateForm()) {
    return
  }

  try {
    const registrationData = {
      email: `${form.value.email}@${selectedDomainName.value}`,
      password: form.value.password,
      domain: form.value.domain,
      displayName: form.value.displayName || undefined
    }

    await authStore.register(registrationData)

    // Show success message
    toast.add({
      title: 'Account Created!',
      description: 'Welcome to TechSci Labs! You have been automatically signed in.',
      color: 'green',
      timeout: 5000
    })

    // Redirect to dashboard
    await router.push('/dashboard')

  } catch (error) {
    console.error('Registration error:', error)
    
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
      title: 'Registration Failed',
      description: error instanceof Error ? error.message : 'An unexpected error occurred',
      color: 'red',
      timeout: 5000
    })
  }
}

async function handleGoogleRegister() {
  // TODO: Implement Google OAuth registration
  toast.add({
    title: 'Coming Soon',
    description: 'Google registration will be available soon',
    color: 'blue',
    timeout: 3000
  })
}

async function handleGithubRegister() {
  // TODO: Implement GitHub OAuth registration
  toast.add({
    title: 'Coming Soon',
    description: 'GitHub registration will be available soon',
    color: 'blue',
    timeout: 3000
  })
}

function clearError() {
  authStore.clearError()
}

// Lifecycle
onMounted(async () => {
  await loadDomains()
  
  // Auto-focus display name field
  const displayNameInput = document.getElementById('displayName')
  if (displayNameInput) {
    displayNameInput.focus()
  }
})

// Redirect if already authenticated
watchEffect(() => {
  if (authStore.isAuthenticated) {
    const redirectTo = route.query.redirect as string || '/dashboard'
    router.push(redirectTo)
  }
})
</script>

<style scoped>
/* Additional styling if needed */
</style>