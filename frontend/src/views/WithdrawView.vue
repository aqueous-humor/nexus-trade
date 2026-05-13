<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWalletStore } from '@/stores/wallet'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

const router = useRouter()
const walletStore = useWalletStore()

// ── Static data ───────────────────────────────────────────────────────────────

const PROVIDERS = ['Binance', 'KuCoin', 'XT', 'Bank Transfer']

const DAILY_LIMIT = 5000
const MONTHLY_LIMIT = 50000

// ── Form state ────────────────────────────────────────────────────────────────

const step = ref<1 | 2>(1)
const amount = ref<number | ''>('')
const destinationAddress = ref('')
const provider = ref(PROVIDERS[0])
const fieldErrors = ref<Record<string, string>>({})
const isSuccess = ref(false)

// ── Fee breakdown ─────────────────────────────────────────────────────────────

const feeAmount = computed(() => {
  const amt = Number(amount.value) || 0
  return amt * 0.005 // 0.5% fee
})

const netAmount = computed(() => {
  const amt = Number(amount.value) || 0
  return amt - feeAmount.value
})

// ── Limit display (stub) ──────────────────────────────────────────────────────

const dailyRemaining = computed(() => {
  const amt = Number(amount.value) || 0
  return Math.max(0, DAILY_LIMIT - amt)
})

const monthlyRemaining = computed(() => {
  const amt = Number(amount.value) || 0
  return Math.max(0, MONTHLY_LIMIT - amt)
})

// ── Step 1 → Step 2 ───────────────────────────────────────────────────────────

function continueToConfirm() {
  fieldErrors.value = {}

  if (!amount.value || Number(amount.value) < 1) {
    fieldErrors.value.amount = 'Amount must be at least 1'
    return
  }
  if (!destinationAddress.value.trim()) {
    fieldErrors.value.destinationAddress = 'Destination address is required'
    return
  }

  step.value = 2
}

// ── Submit ────────────────────────────────────────────────────────────────────

async function confirmWithdrawal() {
  fieldErrors.value = {}

  try {
    await walletStore.withdraw({
      amount: Number(amount.value),
      currency: 'USD',
      destination_address: destinationAddress.value,
      provider: provider.value,
    })
    isSuccess.value = true
    setTimeout(() => router.push('/app/wallet'), 2000)
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { errors?: Record<string, string[]> } } }
    const errors = apiErr?.response?.data?.errors ?? {}
    for (const [field, messages] of Object.entries(errors)) {
      fieldErrors.value[field] = Array.isArray(messages) ? (messages[0] ?? '') : String(messages)
    }
    // Go back to form if there are field errors
    if (Object.keys(fieldErrors.value).length > 0) {
      step.value = 1
    }
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatUSD(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(value)
}
</script>

<template>
  <div class="withdraw">
    <!-- Back link -->
    <router-link to="/app/wallet" class="withdraw__back">← Back to Wallet</router-link>

    <h1 class="withdraw__title">Withdraw Funds</h1>

    <!-- Success state -->
    <div v-if="isSuccess" class="withdraw__success" role="alert">
      <p class="withdraw__success-title">Withdrawal submitted!</p>
      <p class="withdraw__success-body">
        Your withdrawal of {{ formatUSD(Number(amount)) }} has been submitted. Redirecting to wallet…
      </p>
    </div>

    <!-- Step 1: Form -->
    <form
      v-else-if="step === 1"
      class="withdraw__form"
      novalidate
      @submit.prevent="continueToConfirm"
    >
      <!-- Amount -->
      <BaseInput
        v-model="amount"
        label="Amount (USD)"
        type="number"
        placeholder="0.00"
        :error="fieldErrors.amount"
        required
      />

      <!-- Limit display -->
      <div class="withdraw__limits">
        <div class="withdraw__limit-box">
          <span class="withdraw__limit-label">Daily remaining</span>
          <span class="withdraw__limit-value">
            {{ formatUSD(dailyRemaining) }} / {{ formatUSD(DAILY_LIMIT) }}
          </span>
        </div>
        <div class="withdraw__limit-box">
          <span class="withdraw__limit-label">Monthly remaining</span>
          <span class="withdraw__limit-value">
            {{ formatUSD(monthlyRemaining) }} / {{ formatUSD(MONTHLY_LIMIT) }}
          </span>
        </div>
      </div>

      <!-- Destination address -->
      <BaseInput
        v-model="destinationAddress"
        label="Destination Address"
        type="text"
        placeholder="Enter destination address"
        :error="fieldErrors.destinationAddress"
        required
      />

      <!-- Provider selector -->
      <div class="withdraw__field">
        <label class="withdraw__label" for="withdraw-provider">Provider</label>
        <select id="withdraw-provider" v-model="provider" class="withdraw__select">
          <option v-for="p in PROVIDERS" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>

      <!-- Fee breakdown -->
      <div v-if="Number(amount) > 0" class="withdraw__fee-breakdown">
        <div class="withdraw__fee-row">
          <span class="withdraw__fee-label">Fee (0.5%)</span>
          <span class="withdraw__fee-value">{{ formatUSD(feeAmount) }}</span>
        </div>
        <div class="withdraw__fee-row withdraw__fee-row--net">
          <span class="withdraw__fee-label">You receive</span>
          <span class="withdraw__fee-value withdraw__fee-value--net">{{ formatUSD(netAmount) }}</span>
        </div>
      </div>

      <BaseButton type="submit" variant="primary">
        Continue
      </BaseButton>
    </form>

    <!-- Step 2: Confirmation -->
    <div v-else class="withdraw__confirm">
      <div class="withdraw__summary">
        <h2 class="withdraw__summary-title">Confirm Withdrawal</h2>

        <dl class="withdraw__summary-list">
          <div class="withdraw__summary-row">
            <dt class="withdraw__summary-label">Amount</dt>
            <dd class="withdraw__summary-value">{{ formatUSD(Number(amount)) }}</dd>
          </div>
          <div class="withdraw__summary-row">
            <dt class="withdraw__summary-label">Fee (0.5%)</dt>
            <dd class="withdraw__summary-value">{{ formatUSD(feeAmount) }}</dd>
          </div>
          <div class="withdraw__summary-row withdraw__summary-row--net">
            <dt class="withdraw__summary-label">Net amount</dt>
            <dd class="withdraw__summary-value withdraw__summary-value--net">{{ formatUSD(netAmount) }}</dd>
          </div>
          <div class="withdraw__summary-row">
            <dt class="withdraw__summary-label">Destination</dt>
            <dd class="withdraw__summary-value withdraw__summary-value--mono">{{ destinationAddress }}</dd>
          </div>
          <div class="withdraw__summary-row">
            <dt class="withdraw__summary-label">Provider</dt>
            <dd class="withdraw__summary-value">{{ provider }}</dd>
          </div>
        </dl>

        <div class="withdraw__warning" role="alert">
          ⚠ This action cannot be undone
        </div>
      </div>

      <div class="withdraw__confirm-actions">
        <BaseButton
          variant="primary"
          :loading="walletStore.isLoading"
          @click="confirmWithdrawal"
        >
          Confirm Withdrawal
        </BaseButton>
        <BaseButton variant="secondary" @click="step = 1">
          Back
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.withdraw {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
  max-width: 480px;

  &__back {
    font-size: var(--text-sm);
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
    align-self: flex-start;

    &:hover {
      text-decoration: underline;
    }
  }

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
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

  &__select {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    color: var(--color-text);
    cursor: pointer;
    outline: none;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }

  // Limits
  &__limits {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-3);
  }

  &__limit-box {
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-3);
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__limit-label {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  &__limit-value {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
    font-variant-numeric: tabular-nums;
  }

  // Fee breakdown
  &__fee-breakdown {
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__fee-row {
    display: flex;
    justify-content: space-between;
    align-items: center;

    &--net {
      padding-top: var(--space-2);
      border-top: 1px solid var(--color-border);
    }
  }

  &__fee-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
  }

  &__fee-value {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
    font-variant-numeric: tabular-nums;

    &--net {
      color: var(--color-primary);
    }
  }

  // Confirmation
  &__confirm {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
  }

  &__summary {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__summary-title {
    font-size: var(--text-xl);
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
  }

  &__summary-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    margin: 0;
  }

  &__summary-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: var(--space-4);

    &--net {
      padding-top: var(--space-3);
      border-top: 1px solid var(--color-border);
    }
  }

  &__summary-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    flex-shrink: 0;
  }

  &__summary-value {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
    font-variant-numeric: tabular-nums;
    text-align: right;
    word-break: break-all;

    &--net {
      color: var(--color-primary);
    }

    &--mono {
      font-family: var(--font-mono);
      font-size: var(--text-xs);
    }
  }

  &__warning {
    background: color-mix(in srgb, var(--color-warning) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-warning) 30%, transparent);
    border-radius: var(--radius-md);
    padding: var(--space-3) var(--space-4);
    font-size: var(--text-sm);
    color: var(--color-warning);
    font-weight: 500;
  }

  &__confirm-actions {
    display: flex;
    gap: var(--space-3);
  }

  // Success state
  &__success {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-8);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__success-title {
    font-size: var(--text-xl);
    font-weight: 700;
    color: var(--color-success);
    margin: 0;
  }

  &__success-body {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
  }
}
</style>
