<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import GuestLayout from '@/layouts/GuestLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const isSubmitting = ref(false)
const generalError = ref('')
const fieldErrors = ref<Record<string, string>>({})

async function handleSubmit() {
  generalError.value = ''
  fieldErrors.value = {}
  isSubmitting.value = true

  try {
    await authStore.login(email.value, password.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/app'
    await router.push(redirect)
  } catch (error: unknown) {
    const axiosError = error as {
      response?: {
        data?: {
          errors?: Record<string, string[]>
          message?: string
        }
      }
    }

    const errors = axiosError?.response?.data?.errors
    if (errors) {
      const mapped: Record<string, string> = {}
      for (const [field, messages] of Object.entries(errors)) {
        mapped[field] = Array.isArray(messages) ? (messages[0] ?? '') : (messages[0] ?? '')
      }
      fieldErrors.value = mapped
    } else {
      generalError.value =
        axiosError?.response?.data?.message ?? 'An unexpected error occurred. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <GuestLayout>
    <h1 class="login__title">Sign in to your account</h1>

    <div
      v-if="generalError"
      class="login__alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <BaseAlert variant="danger" dismissible @dismiss="generalError = ''">
        {{ generalError }}
      </BaseAlert>
    </div>

    <form
      class="login__form"
      novalidate
      @submit.prevent="handleSubmit"
    >
      <BaseInput
        id="login-email"
        v-model="email"
        label="Email address"
        type="email"
        placeholder="you@example.com"
        :error="fieldErrors.email"
        required
        autocomplete="email"
      />

      <BaseInput
        id="login-password"
        v-model="password"
        label="Password"
        type="password"
        placeholder="••••••••"
        :error="fieldErrors.password"
        required
        autocomplete="current-password"
      />

      <div class="login__forgot">
        <RouterLink to="/forgot-password" class="login__link">
          Forgot password?
        </RouterLink>
      </div>

      <BaseButton
        type="submit"
        variant="primary"
        size="lg"
        :loading="isSubmitting"
        class="login__submit"
      >
        Sign in
      </BaseButton>
    </form>

    <p class="login__register">
      Don't have an account?
      <RouterLink to="/register" class="login__link">Register</RouterLink>
    </p>
  </GuestLayout>
</template>

<style lang="scss" scoped>
.login {
  &__title {
    font-size: var(--text-xl);
    font-weight: 600;
    color: var(--color-text);
    text-align: center;
    margin: 0 0 var(--space-6);
  }

  &__alert {
    margin-bottom: var(--space-4);
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__forgot {
    display: flex;
    justify-content: flex-end;
    margin-top: calc(var(--space-1) * -1);
  }

  &__link {
    font-size: var(--text-sm);
    color: var(--color-primary);
    text-decoration: none;
    transition: color var(--transition-fast);

    &:hover {
      color: var(--color-primary-dark);
      text-decoration: underline;
    }

    &:focus-visible {
      outline: 2px solid var(--color-primary);
      outline-offset: 2px;
      border-radius: var(--radius-sm);
    }
  }

  &__submit {
    width: 100%;
    margin-top: var(--space-2);
  }

  &__register {
    margin-top: var(--space-6);
    text-align: center;
    font-size: var(--text-sm);
    color: var(--color-text-muted);
  }
}
</style>
