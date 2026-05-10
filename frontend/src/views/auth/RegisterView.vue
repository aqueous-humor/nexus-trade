<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import GuestLayout from '@/layouts/GuestLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const phoneNumber = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const isSubmitting = ref(false)
const generalError = ref('')
const fieldErrors = ref<Record<string, string>>({})
const successMessage = ref('')

async function handleSubmit() {
  generalError.value = ''
  fieldErrors.value = {}
  successMessage.value = ''
  isSubmitting.value = true

  try {
    await authStore.register({
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      phone_number: phoneNumber.value || undefined,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    successMessage.value = 'Check your email to verify your account.'

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
        mapped[field] = Array.isArray(messages) ? messages[0] : messages
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
    <h1 class="register__title">Create your account</h1>

    <div
      v-if="successMessage"
      class="register__alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <BaseAlert variant="success">
        {{ successMessage }}
      </BaseAlert>
    </div>

    <div
      v-if="generalError"
      class="register__alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <BaseAlert variant="danger" dismissible @dismiss="generalError = ''">
        {{ generalError }}
      </BaseAlert>
    </div>

    <form
      v-if="!successMessage"
      class="register__form"
      novalidate
      @submit.prevent="handleSubmit"
    >
      <div class="register__row">
        <BaseInput
          id="register-first-name"
          v-model="firstName"
          label="First name"
          type="text"
          placeholder="Jane"
          :error="fieldErrors.first_name"
          required
          autocomplete="given-name"
        />

        <BaseInput
          id="register-last-name"
          v-model="lastName"
          label="Last name"
          type="text"
          placeholder="Doe"
          :error="fieldErrors.last_name"
          required
          autocomplete="family-name"
        />
      </div>

      <BaseInput
        id="register-email"
        v-model="email"
        label="Email address"
        type="email"
        placeholder="you@example.com"
        :error="fieldErrors.email"
        required
        autocomplete="email"
      />

      <BaseInput
        id="register-phone"
        v-model="phoneNumber"
        label="Phone number (optional)"
        type="tel"
        placeholder="+1 555 000 0000"
        :error="fieldErrors.phone_number"
        autocomplete="tel"
      />

      <BaseInput
        id="register-password"
        v-model="password"
        label="Password"
        type="password"
        placeholder="••••••••"
        :error="fieldErrors.password"
        required
        autocomplete="new-password"
      />

      <BaseInput
        id="register-password-confirmation"
        v-model="passwordConfirmation"
        label="Confirm password"
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
        class="register__submit"
      >
        Create account
      </BaseButton>
    </form>

    <p class="register__login">
      Already have an account?
      <RouterLink to="/login" class="register__link">Sign in</RouterLink>
    </p>
  </GuestLayout>
</template>

<style lang="scss" scoped>
.register {
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

  &__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-3);
  }

  &__submit {
    width: 100%;
    margin-top: var(--space-2);
  }

  &__login {
    margin-top: var(--space-6);
    text-align: center;
    font-size: var(--text-sm);
    color: var(--color-text-muted);
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
