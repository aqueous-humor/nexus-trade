<script setup lang="ts">
import { ref } from 'vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import { authApi } from '@/api/auth'

const email = ref('')
const isSubmitting = ref(false)
const generalError = ref('')
const emailError = ref('')
const isSuccess = ref(false)

async function handleSubmit() {
  generalError.value = ''
  emailError.value = ''
  isSubmitting.value = true

  try {
    await authApi.forgotPassword({ email: email.value })
    isSuccess.value = true
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
    if (errors?.email) {
      emailError.value = Array.isArray(errors.email) ? errors.email[0] : errors.email
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
    <h1 class="forgot__title">Reset your password</h1>

    <template v-if="isSuccess">
      <div
        class="forgot__success"
        aria-live="assertive"
        aria-atomic="true"
        role="status"
      >
        <BaseAlert variant="success">
          If that email exists, a reset link has been sent.
        </BaseAlert>
      </div>

      <p class="forgot__back">
        <RouterLink to="/login" class="forgot__link">← Back to sign in</RouterLink>
      </p>
    </template>

    <template v-else>
      <p class="forgot__description">
        Enter your email address and we'll send you a link to reset your password.
      </p>

      <div
        v-if="generalError"
        class="forgot__alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <BaseAlert variant="danger" dismissible @dismiss="generalError = ''">
          {{ generalError }}
        </BaseAlert>
      </div>

      <form
        class="forgot__form"
        novalidate
        @submit.prevent="handleSubmit"
      >
        <BaseInput
          id="forgot-email"
          v-model="email"
          label="Email address"
          type="email"
          placeholder="you@example.com"
          :error="emailError"
          required
          autocomplete="email"
        />

        <BaseButton
          type="submit"
          variant="primary"
          size="lg"
          :loading="isSubmitting"
          class="forgot__submit"
        >
          Send reset link
        </BaseButton>
      </form>

      <p class="forgot__back">
        <RouterLink to="/login" class="forgot__link">← Back to sign in</RouterLink>
      </p>
    </template>
  </GuestLayout>
</template>

<style lang="scss" scoped>
.forgot {
  &__title {
    font-size: var(--text-xl);
    font-weight: 600;
    color: var(--color-text);
    text-align: center;
    margin: 0 0 var(--space-4);
  }

  &__description {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    text-align: center;
    margin: 0 0 var(--space-6);
    line-height: 1.5;
  }

  &__alert {
    margin-bottom: var(--space-4);
  }

  &__success {
    margin-bottom: var(--space-6);
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__submit {
    width: 100%;
    margin-top: var(--space-2);
  }

  &__back {
    margin-top: var(--space-6);
    text-align: center;
    font-size: var(--text-sm);
  }

  &__link {
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
}
</style>
