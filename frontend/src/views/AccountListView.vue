<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAccountStore } from '@/stores/account'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'

const router = useRouter()
const accountStore = useAccountStore()

// ── Account creation modal state ──────────────────────────────────────────────

const showCreateModal = ref(false)
const isSubmitting = ref(false)
const createSuccess = ref(false)

// TODO: fetch from GET /api/v1/admin/brokers
const brokers = ref<{ id: number; name: string }[]>([])

const LEVERAGE_OPTIONS = [1, 50, 100, 200, 500, 1000]

const form = ref({
  type: 'demo' as 'demo' | 'live',
  broker_id: '' as string | number,
  broker_account_id: '',
  leverage: 1,
})

const fieldErrors = ref<Record<string, string>>({})

function openCreateModal() {
  form.value = { type: 'demo', broker_id: '', broker_account_id: '', leverage: 1 }
  fieldErrors.value = {}
  createSuccess.value = false
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

async function submitCreate() {
  fieldErrors.value = {}
  isSubmitting.value = true
  try {
    await accountStore.createAccount({
      type: form.value.type,
      leverage: form.value.leverage,
      ...(form.value.type === 'live' && {
        broker_id: Number(form.value.broker_id),
        broker_account_id: form.value.broker_account_id,
      }),
    })
    createSuccess.value = true
    setTimeout(() => {
      closeCreateModal()
    }, 1200)
  } catch (err: unknown) {
    // Handle field-level API errors (422)
    const apiErr = err as { response?: { data?: { errors?: Record<string, string[]> } } }
    if (apiErr?.response?.data?.errors) {
      const errors = apiErr.response.data.errors
      for (const [key, messages] of Object.entries(errors)) {
        fieldErrors.value[key] = Array.isArray(messages) ? (messages[0] ?? '') : String(messages)
      }
    }
  } finally {
    isSubmitting.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(cents / 100)
}

function typeBadgeVariant(type: string): 'info' | 'success' {
  return type === 'demo' ? 'info' : 'success'
}

function statusBadgeVariant(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
  if (status === 'active') return 'success'
  if (status === 'suspended') return 'warning'
  if (status === 'deactivated') return 'danger'
  return 'neutral'
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  accountStore.fetchAccounts()
})
</script>

<template>
  <div class="account-list">
    <!-- Page header -->
    <div class="account-list__header">
      <h1 class="account-list__title">My Accounts</h1>
      <BaseButton variant="primary" @click="openCreateModal">New Account</BaseButton>
    </div>

    <!-- Loading skeleton -->
    <div v-if="accountStore.isLoading" class="account-list__grid" aria-busy="true" aria-label="Loading accounts">
      <div v-for="i in 3" :key="i" class="account-card account-card--skeleton">
        <div class="skeleton skeleton--badge" />
        <div class="skeleton skeleton--title" />
        <div class="skeleton skeleton--line" />
        <div class="skeleton skeleton--line skeleton--short" />
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="accountStore.accounts.length === 0"
      class="account-list__empty"
    >
      <p class="account-list__empty-text">You don't have any accounts yet.</p>
      <BaseButton variant="primary" @click="openCreateModal">Create your first account</BaseButton>
    </div>

    <!-- Account cards grid -->
    <div v-else class="account-list__grid">
      <div
        v-for="account in accountStore.accounts"
        :key="account.id"
        class="account-card"
      >
        <div class="account-card__badges">
          <BaseBadge :variant="typeBadgeVariant(account.type)">
            {{ account.type === 'demo' ? 'Demo' : 'Live' }}
          </BaseBadge>
          <BaseBadge :variant="statusBadgeVariant(account.status)">
            {{ account.status.charAt(0).toUpperCase() + account.status.slice(1) }}
          </BaseBadge>
        </div>

        <div class="account-card__balance">
          {{ formatUSD(account.balance_cents) }}
        </div>

        <dl class="account-card__details">
          <div v-if="account.broker" class="account-card__detail-row">
            <dt class="account-card__detail-label">Broker</dt>
            <dd class="account-card__detail-value">{{ account.broker.name }}</dd>
          </div>
          <div class="account-card__detail-row">
            <dt class="account-card__detail-label">Leverage</dt>
            <dd class="account-card__detail-value">1:{{ account.leverage }}</dd>
          </div>
        </dl>

        <div class="account-card__footer">
          <router-link :to="`/accounts/${account.id}`" class="account-card__link">
            View Details →
          </router-link>
        </div>
      </div>
    </div>

    <!-- Account creation modal -->
    <BaseModal v-model="showCreateModal" title="Create Account" size="md">
      <form class="create-form" @submit.prevent="submitCreate">
        <!-- Success message -->
        <div v-if="createSuccess" class="create-form__success" role="status">
          Account created successfully!
        </div>

        <!-- Type selector -->
        <fieldset class="create-form__fieldset">
          <legend class="create-form__legend">Account Type</legend>
          <div class="create-form__radio-group">
            <label class="create-form__radio-label">
              <input
                v-model="form.type"
                type="radio"
                value="demo"
                class="create-form__radio"
              />
              Demo
            </label>
            <label class="create-form__radio-label">
              <input
                v-model="form.type"
                type="radio"
                value="live"
                class="create-form__radio"
              />
              Live
            </label>
          </div>
        </fieldset>

        <!-- Broker dropdown (live only) -->
        <div v-if="form.type === 'live'" class="create-form__field">
          <label class="create-form__label" for="broker-select">Broker</label>
          <select
            id="broker-select"
            v-model="form.broker_id"
            class="create-form__select"
            :class="{ 'create-form__select--error': fieldErrors.broker_id }"
          >
            <option value="" disabled>Select a broker</option>
            <option v-for="broker in brokers" :key="broker.id" :value="broker.id">
              {{ broker.name }}
            </option>
          </select>
          <p v-if="fieldErrors.broker_id" class="create-form__error">{{ fieldErrors.broker_id }}</p>
        </div>

        <!-- Broker Account ID (live only) -->
        <div v-if="form.type === 'live'" class="create-form__field">
          <label class="create-form__label" for="broker-account-id">Broker Account ID</label>
          <input
            id="broker-account-id"
            v-model="form.broker_account_id"
            type="text"
            class="create-form__input"
            :class="{ 'create-form__input--error': fieldErrors.broker_account_id }"
            placeholder="e.g. MT4-123456"
          />
          <p v-if="fieldErrors.broker_account_id" class="create-form__error">
            {{ fieldErrors.broker_account_id }}
          </p>
        </div>

        <!-- Leverage selector -->
        <div class="create-form__field">
          <label class="create-form__label" for="leverage-select">Leverage</label>
          <select
            id="leverage-select"
            v-model="form.leverage"
            class="create-form__select"
            :class="{ 'create-form__select--error': fieldErrors.leverage }"
          >
            <option v-for="lev in LEVERAGE_OPTIONS" :key="lev" :value="lev">
              1:{{ lev }}
            </option>
          </select>
          <p v-if="fieldErrors.leverage" class="create-form__error">{{ fieldErrors.leverage }}</p>
        </div>
      </form>

      <template #footer>
        <BaseButton variant="secondary" :disabled="isSubmitting" @click="closeCreateModal">
          Cancel
        </BaseButton>
        <BaseButton variant="primary" :loading="isSubmitting" @click="submitCreate">
          Create
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.account-list {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
  }

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-4);

    @media (max-width: 1024px) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (max-width: 600px) {
      grid-template-columns: 1fr;
    }
  }

  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-16) var(--space-4);
    text-align: center;
  }

  &__empty-text {
    color: var(--color-text-muted);
    font-size: var(--text-base);
    margin: 0;
  }
}

// Account card
.account-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast);

  &:hover {
    box-shadow: var(--shadow-md);
  }

  &--skeleton {
    pointer-events: none;
  }

  &__badges {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
  }

  &__balance {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--color-text);
  }

  &__details {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin: 0;
  }

  &__detail-row {
    display: flex;
    justify-content: space-between;
    gap: var(--space-2);
  }

  &__detail-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
  }

  &__detail-value {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text);
  }

  &__footer {
    margin-top: auto;
    padding-top: var(--space-2);
    border-top: 1px solid var(--color-border);
  }

  &__link {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-primary);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}

// Skeleton shimmer
.skeleton {
  background: var(--color-surface-2);
  border-radius: var(--radius-sm);
  animation: shimmer 1.5s ease-in-out infinite;

  &--badge {
    height: 1.25rem;
    width: 4rem;
    border-radius: var(--radius-full);
  }

  &--title {
    height: 2rem;
    width: 70%;
  }

  &--line {
    height: 1rem;
    width: 100%;
  }

  &--short {
    width: 50%;
  }
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}

// Create form
.create-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);

  &__success {
    padding: var(--space-3) var(--space-4);
    background: color-mix(in srgb, var(--color-success) 15%, transparent);
    color: var(--color-success);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    font-weight: 500;
  }

  &__fieldset {
    border: none;
    padding: 0;
    margin: 0;
  }

  &__legend {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text);
    margin-bottom: var(--space-2);
  }

  &__radio-group {
    display: flex;
    gap: var(--space-6);
  }

  &__radio-label {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--color-text);
    cursor: pointer;
  }

  &__radio {
    accent-color: var(--color-primary);
    width: 1rem;
    height: 1rem;
    cursor: pointer;
  }

  &__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__label {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text);
  }

  &__select,
  &__input {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    color: var(--color-text);
    outline: none;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }

    &--error {
      border-color: var(--color-danger);

      &:focus {
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-danger) 20%, transparent);
      }
    }
  }

  &__error {
    font-size: var(--text-xs);
    color: var(--color-danger);
    margin: 0;
  }
}
</style>
