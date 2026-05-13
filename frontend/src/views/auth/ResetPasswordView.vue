<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import GuestLayout from '@/layouts/GuestLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import { authApi } from '@/api/auth'

const route = useRoute()
const router = useRouter()

const token = computed(() => String(route.query.token ?? ''))
const emailFromQuery = computed(() => String(route.query.email ?? ''))

const password = ref('')
const passwordConfirmation = ref('')

const isSubmitting = ref(false)
const generalError = ref('')
const fieldErrors = ref<Record<string, string>>({})
const isSuccess = ref(false)

async function handleSubmit() {
  generalError.value = ''
  fieldErrors.value = {}
  isSubmitting.value = true

  try {
    await authApi.resetPassword({
      token: token.value,
      email: emailFromQuery.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    isSuccess.value = true

    setTimeout(() => {
      router.push('/login')
    }, 2000)
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
    <h1 class="reset__title">Set a new password</h1>

    <template v-if="isSuccess">
      <div
        class="reset__success"
        aria-live="assertive"
        aria-atomic="true"
        role="status"
      >
        <BaseAlert variant="success">
          Your password has been reset. Redirecting you to sign in…
        </BaseAlert>
      </div>

      <p class="reset__back">
        <RouterLink to="/login" class="reset__link">← Back to sign in</RouterLink>
      </p>
    </template>

    <template v-else>
      <div
        v-if="generalError"
        class="reset__alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <BaseAlert variant="danger" dismissible @dismiss="generalError = ''">
          {{ generalError }}
        </BaseAlert>
      </div>

      <form
        class="reset__form"
        novalidate
        @submit.prevent="handleSubmit"
      >
        <BaseInput
          id="reset-email"
          :model-value="emailFromQuery"
          label="Email address"
          type="email"
          :error="fieldErrors.email"
          readonly
          disabled
          autocomplete="email"
        />

        <BaseInput
          id="reset-password"
          v-model="password"
          label="New password"
          type="password"
          placeholder="••••••••"
          :error="fieldErrors.password"
          required
          autocomplete="new-password"
        />

        <BaseInput
          id="reset-password-confirmation"
          v-model="passwordConfirmation"
          label="Confirm new password"
          type="password"
          placeholder="••••••••"
          :error="fieldErrors.password_confirmation"
          required
          autocomplete="new-password"
        />

        <BaseButton
          type="submit"
          variant="primary"
          size="lg"
          :loading="isSubmitting"
          class="reset__submit"
        >
          Reset password
        </BaseButton>
      </form>

      <p class="reset__back">
        <RouterLink to="/login" class="reset__link">← Back to sign in</RouterLink>
      </p>
    </template>
  </GuestLayout>
</template>

<style lang="scss" scoped>
.reset {
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
