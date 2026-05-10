<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investment'
import { useAccountStore } from '@/stores/account'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'

const route = useRoute()
const router = useRouter()
const investmentStore = useInvestmentStore()
const accountStore = useAccountStore()

// ── Plan resolution ───────────────────────────────────────────────────────────

const planId = computed(() => {
  const raw = route.query.planId
  return raw ? Number(raw) : null
})

// Extend plan type with durations
interface Duration {
  id: number
  label: string
}

interface PlanWithDurations {
  id: number
  name: string
  description: string
  min_amount_cents: number
  max_amount_cents: number
  roi_percentage: number
  status: string
  durations?: Duration[]
}

const plan = computed<PlanWithDurations | null>(() => {
  if (planId.value == null) return null
  return (investmentStore.plans as PlanWithDurations[]).find((p) => p.id === planId.value) ?? null
})

// ── Form state ────────────────────────────────────────────────────────────────

const selectedAccountId = ref<number | ''>('')
const selectedDurationId = ref<number | null>(null)
const amountInput = ref('')
const termsAccepted = ref(false)
const isSubmitting = ref(false)
const submitError = ref('')
const fieldErrors = ref<Record<string, string>>({})

// Active accounts only
const activeAccounts = computed(() =>
  accountStore.accounts.filter((a) => a.status === 'active'),
)

// ── Validation ────────────────────────────────────────────────────────────────

const amountCents = computed(() => {
  const val = parseFloat(amountInput.value)
  return isNaN(val) ? 0 : Math.round(val * 100)
})

const amountHint = computed(() => {
  if (!plan.value) return ''
  const min = formatUSD(plan.value.min_amount_cents)
  const max = formatUSD(plan.value.max_amount_cents)
  return `${min} – ${max}`
})

const amountError = computed(() => {
  if (!plan.value || !amountInput.value) return ''
  if (amountCents.value < plan.value.min_amount_cents) {
    return `Minimum investment is ${formatUSD(plan.value.min_amount_cents)}`
  }
  if (amountCents.value > plan.value.max_amount_cents) {
    return `Maximum investment is ${formatUSD(plan.value.max_amount_cents)}`
  }
  return ''
})

// ── Submit ────────────────────────────────────────────────────────────────────

async function submit() {
  fieldErrors.value = {}
  submitError.value = ''

  // Client-side validation
  if (!selectedAccountId.value) {
    fieldErrors.value.account_id = 'Please select an account.'
    return
  }
  if (!selectedDurationId.value) {
    fieldErrors.value.duration_id = 'Please select a duration.'
    return
  }
  if (amountError.value) {
    fieldErrors.value.amount = amountError.value
    return
  }
  if (!termsAccepted.value) {
    fieldErrors.value.terms = 'You must accept the investment terms.'
    return
  }

  isSubmitting.value = true
  try {
    await investmentStore.createInvestment({
      account_id: Number(selectedAccountId.value),
      plan_id: plan.value!.id,
      duration_id: selectedDurationId.value,
      amount_cents: amountCents.value,
      terms_version: 'current', // TODO: fetch actual terms version
    })
    router.push('/investments')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    if (apiErr?.response?.data?.errors) {
      const errors = apiErr.response.data.errors
      for (const [key, messages] of Object.entries(errors)) {
        fieldErrors.value[key] = Array.isArray(messages) ? messages[0] : String(messages)
      }
    } else if (apiErr?.response?.data?.message) {
      submitError.value = apiErr.response.data.message
    } else {
      submitError.value = 'Something went wrong. Please try again.'
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
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(cents / 100)
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(async () => {
  await Promise.all([
    investmentStore.fetchPlans(),
    accountStore.fetchAccounts(),
  ])
  // Pre-select first duration if available
  if (plan.value?.durations?.length) {
    selectedDurationId.value = plan.value.durations[0].id
  }
})
</script>

<template>
  <div class="invest-view">
    <!-- Plan not found -->
    <template v-if="!investmentStore.isLoading && !plan">
      <div class="invest-view__error">
        <p class="invest-view__error-text">Plan not found or no plan selected.</p>
        <router-link to="/plans" class="invest-view__back-link">← Back to Plans</router-link>
      </div>
    </template>

    <!-- Loading -->
    <template v-else-if="investmentStore.isLoading">
      <div class="invest-view__skeleton">
        <div class="skeleton skeleton--title" />
        <div class="skeleton skeleton--block" />
      </div>
    </template>

    <!-- Invest form -->
    <template v-else-if="plan">
      <router-link to="/plans" class="invest-view__back-link">← Back to Plans</router-link>

      <h1 class="invest-view__title">Invest in {{ plan.name }}</h1>

      <div class="invest-view__layout">
        <!-- Plan summary card -->
        <aside class="plan-summary">
          <h2 class="plan-summary__title">Plan Summary</h2>
          <div class="plan-summary__roi">{{ plan.roi_percentage }}% ROI</div>
          <p class="plan-summary__description">{{ plan.description }}</p>
          <dl class="plan-summary__details">
            <div class="plan-summary__detail-row">
              <dt class="plan-summary__detail-label">Min Amount</dt>
              <dd class="plan-summary__detail-value">{{ formatUSD(plan.min_amount_cents) }}</dd>
            </div>
            <div class="plan-summary__detail-row">
              <dt class="plan-summary__detail-label">Max Amount</dt>
              <dd class="plan-summary__detail-value">{{ formatUSD(plan.max_amount_cents) }}</dd>
            </div>
          </dl>
        </aside>

        <!-- Investment form -->
        <form class="invest-form" @submit.prevent="submit">
          <!-- Global error -->
          <BaseAlert v-if="submitError" variant="danger">{{ submitError }}</BaseAlert>

          <!-- Account selector -->
          <div class="invest-form__field">
            <label class="invest-form__label" for="account-select">Account</label>
            <select
              id="account-select"
              v-model="selectedAccountId"
              class="invest-form__select"
              :class="{ 'invest-form__select--error': fieldErrors.account_id }"
            >
              <option value="" disabled>Select an account</option>
              <option
                v-for="account in activeAccounts"
                :key="account.id"
                :value="account.id"
              >
                #{{ account.id }} — {{ account.type === 'demo' ? 'Demo' : 'Live' }}
                ({{ formatUSD(account.balance_cents) }})
              </option>
            </select>
            <p v-if="fieldErrors.account_id" class="invest-form__error">{{ fieldErrors.account_id }}</p>
            <p v-if="activeAccounts.length === 0" class="invest-form__hint">
              No active accounts found.
              <router-link to="/accounts" class="invest-form__link">Create one</router-link>
            </p>
          </div>

          <!-- Duration selector -->
          <div v-if="plan.durations && plan.durations.length > 0" class="invest-form__field">
            <label class="invest-form__label">Duration</label>
            <div class="invest-form__duration-group">
              <label
                v-for="duration in plan.durations"
                :key="duration.id"
                class="invest-form__duration-pill"
                :class="{ 'invest-form__duration-pill--selected': selectedDurationId === duration.id }"
              >
                <input
                  v-model="selectedDurationId"
                  type="radio"
                  :value="duration.id"
                  class="sr-only"
                />
                {{ duration.label }}
              </label>
            </div>
            <p v-if="fieldErrors.duration_id" class="invest-form__error">{{ fieldErrors.duration_id }}</p>
          </div>

          <!-- Amount input -->
          <div class="invest-form__field">
            <label class="invest-form__label" for="amount-input">Amount (USD)</label>
            <input
              id="amount-input"
              v-model="amountInput"
              type="number"
              step="0.01"
              :min="plan.min_amount_cents / 100"
              :max="plan.max_amount_cents / 100"
              class="invest-form__input"
              :class="{ 'invest-form__input--error': amountError || fieldErrors.amount }"
              placeholder="0.00"
            />
            <p class="invest-form__hint">{{ amountHint }}</p>
            <p v-if="amountError || fieldErrors.amount" class="invest-form__error">
              {{ amountError || fieldErrors.amount }}
            </p>
          </div>

          <!-- Terms acceptance -->
          <div class="invest-form__field">
            <label class="invest-form__checkbox-label">
              <input
                v-model="termsAccepted"
                type="checkbox"
                class="invest-form__checkbox"
              />
              I have read and accept the investment terms
            </label>
            <p v-if="fieldErrors.terms" class="invest-form__error">{{ fieldErrors.terms }}</p>
          </div>

          <!-- Submit -->
          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            :loading="isSubmitting"
            :disabled="!termsAccepted"
            class="invest-form__submit"
          >
            Invest Now
          </BaseButton>
        </form>
      </div>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.invest-view {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__back-link {
    font-size: var(--text-sm);
    color: var(--color-primary);
    text-decoration: none;
    align-self: flex-start;

    &:hover {
      text-decoration: underline;
    }
  }

  &__error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-16);
    text-align: center;
  }

  &__error-text {
    color: var(--color-text-muted);
    font-size: var(--text-base);
    margin: 0;
  }

  &__layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: var(--space-6);
    align-items: start;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  &__skeleton {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }
}

// Plan summary
.plan-summary {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);

  &__title {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--color-text-muted);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: var(--text-xs);
  }

  &__roi {
    font-size: var(--text-3xl);
    font-weight: 800;
    color: var(--color-primary);
  }

  &__description {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
    line-height: 1.6;
  }

  &__details {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin: 0;
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border);
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
    font-weight: 600;
    color: var(--color-text);
  }
}

// Invest form
.invest-form {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

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

  &__hint {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    margin: 0;
  }

  &__error {
    font-size: var(--text-xs);
    color: var(--color-danger);
    margin: 0;
  }

  &__link {
    color: var(--color-primary);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  &__duration-group {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
  }

  &__duration-pill {
    display: inline-flex;
    align-items: center;
    padding: var(--space-1) var(--space-3);
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text-muted);
    cursor: pointer;
    transition: background var(--transition-fast), border-color var(--transition-fast), color var(--transition-fast);

    &:hover {
      border-color: var(--color-primary);
      color: var(--color-primary);
    }

    &--selected {
      background: color-mix(in srgb, var(--color-primary) 15%, transparent);
      border-color: var(--color-primary);
      color: var(--color-primary);
      font-weight: 600;
    }
  }

  &__checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--color-text);
    cursor: pointer;
  }

  &__checkbox {
    accent-color: var(--color-primary);
    width: 1rem;
    height: 1rem;
    cursor: pointer;
    flex-shrink: 0;
  }

  &__submit {
    width: 100%;
  }
}

// Skeleton
.skeleton {
  background: var(--color-surface-2);
  border-radius: var(--radius-sm);
  animation: shimmer 1.5s ease-in-out infinite;

  &--title {
    height: 2.5rem;
    width: 50%;
  }

  &--block {
    height: 24rem;
    width: 100%;
    border-radius: var(--radius-lg);
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>
