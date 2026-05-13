<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useWalletStore } from '@/stores/wallet'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

const router = useRouter()
const walletStore = useWalletStore()

// ── Static data ───────────────────────────────────────────────────────────────

const FIAT_CURRENCIES = ['USD', 'EUR', 'GBP']
const CRYPTO_CURRENCIES = ['BTC', 'ETH', 'USDT', 'BNB']
const NETWORKS: Record<string, string[]> = {
  ETH: ['ERC-20'],
  USDT: ['ERC-20', 'BEP-20', 'TRC-20'],
  BNB: ['BEP-20'],
  BTC: ['Bitcoin'],
}
const PROVIDERS = ['Binance', 'KuCoin', 'XT', 'Bank Transfer']

// ── Form state ────────────────────────────────────────────────────────────────

const currencyType = ref<'fiat' | 'crypto'>('fiat')
const currency = ref('USD')
const network = ref('')
const provider = ref<string>(PROVIDERS[0] ?? 'Binance')
const amount = ref<number | ''>('')
const fieldErrors = ref<Record<string, string>>({})

// ── Derived ───────────────────────────────────────────────────────────────────

const isCrypto = computed(() => currencyType.value === 'crypto')

const currencyOptions = computed(() =>
  isCrypto.value ? CRYPTO_CURRENCIES : FIAT_CURRENCIES,
)

const networkOptions = computed(() =>
  isCrypto.value && currency.value ? (NETWORKS[currency.value] ?? []) : [],
)

// Reset currency and network when type changes
watch(currencyType, (type) => {
  currency.value = type === 'fiat' ? (FIAT_CURRENCIES[0] ?? 'USD') : (CRYPTO_CURRENCIES[0] ?? 'BTC')
  network.value = ''
})

// Reset network when currency changes
watch(currency, () => {
  network.value = networkOptions.value[0] ?? ''
})

// ── Fee breakdown ─────────────────────────────────────────────────────────────

const feeAmount = computed(() => {
  const amt = Number(amount.value) || 0
  return amt * 0.01 // 1% fee
})

const netAmount = computed(() => {
  const amt = Number(amount.value) || 0
  return amt - feeAmount.value
})

// ── Mock wallet address ───────────────────────────────────────────────────────

const MOCK_ADDRESS = '0x742d35Cc6634C0532925a3b8D4C9C2...'
const copied = ref(false)

async function copyAddress() {
  try {
    await navigator.clipboard.writeText(MOCK_ADDRESS)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    // clipboard not available
  }
}

// ── Submit ────────────────────────────────────────────────────────────────────

const isSuccess = ref(false)

async function submit() {
  fieldErrors.value = {}

  if (!amount.value || Number(amount.value) < 1) {
    fieldErrors.value.amount = 'Amount must be at least 1'
    return
  }

  try {
    await walletStore.deposit({
      amount: Number(amount.value),
      currency: currency.value,
      provider: provider.value,
      ...(isCrypto.value && { network: network.value }),
    })
    isSuccess.value = true
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { errors?: Record<string, string[]> } } }
    const errors = apiErr?.response?.data?.errors ?? {}
    for (const [field, messages] of Object.entries(errors)) {
      fieldErrors.value[field] = Array.isArray(messages) ? (messages[0] ?? '') : String(messages)
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
  <div class="deposit">
    <!-- Back link -->
    <router-link to="/app/wallet" class="deposit__back">← Back to Wallet</router-link>

    <h1 class="deposit__title">Deposit Funds</h1>

    <!-- Success state -->
    <div v-if="isSuccess" class="deposit__success" role="alert">
      <p class="deposit__success-title">Deposit initiated!</p>
      <p class="deposit__success-body">
        Your deposit of {{ formatUSD(Number(amount)) }} has been submitted and is being processed.
      </p>
      <BaseButton variant="primary" @click="router.push('/app/wallet')">
        Back to Wallet
      </BaseButton>
    </div>

    <!-- Form -->
    <form v-else class="deposit__form" novalidate @submit.prevent="submit">

      <!-- Step 1: Currency type toggle -->
      <div class="deposit__field">
        <label class="deposit__label">Currency Type</label>
        <div class="deposit__toggle" role="group" aria-label="Currency type">
          <button
            type="button"
            class="deposit__toggle-btn"
            :class="{ 'deposit__toggle-btn--active': currencyType === 'fiat' }"
            @click="currencyType = 'fiat'"
          >
            Fiat
          </button>
          <button
            type="button"
            class="deposit__toggle-btn"
            :class="{ 'deposit__toggle-btn--active': currencyType === 'crypto' }"
            @click="currencyType = 'crypto'"
          >
            Crypto
          </button>
        </div>
      </div>

      <!-- Step 2: Currency selector -->
      <div class="deposit__field">
        <label class="deposit__label" for="deposit-currency">Currency</label>
        <select id="deposit-currency" v-model="currency" class="deposit__select">
          <option v-for="c in currencyOptions" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>

      <!-- Step 3: Network selector (crypto only) -->
      <div v-if="isCrypto" class="deposit__field">
        <label class="deposit__label" for="deposit-network">Network</label>
        <select id="deposit-network" v-model="network" class="deposit__select">
          <option v-for="n in networkOptions" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>

      <!-- Step 4: Provider selector -->
      <div class="deposit__field">
        <label class="deposit__label" for="deposit-provider">Provider</label>
        <select id="deposit-provider" v-model="provider" class="deposit__select">
          <option v-for="p in PROVIDERS" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>

      <!-- Step 5: Amount -->
      <BaseInput
        v-model="amount"
        label="Amount"
        type="number"
        placeholder="0.00"
        :error="fieldErrors.amount"
        required
      />

      <!-- Step 6: Wallet address (crypto only) -->
      <div v-if="isCrypto" class="deposit__field">
        <label class="deposit__label">Deposit Address</label>
        <div class="deposit__address-box">
          <span class="deposit__address-text">{{ MOCK_ADDRESS }}</span>
          <button
            type="button"
            class="deposit__copy-btn"
            :aria-label="copied ? 'Copied!' : 'Copy address'"
            @click="copyAddress"
          >
            {{ copied ? '✓ Copied' : 'Copy' }}
          </button>
        </div>
      </div>

      <!-- Fee breakdown -->
      <div v-if="Number(amount) > 0" class="deposit__fee-breakdown">
        <div class="deposit__fee-row">
          <span class="deposit__fee-label">Network fee (1%)</span>
          <span class="deposit__fee-value">{{ formatUSD(feeAmount) }}</span>
        </div>
        <div class="deposit__fee-row deposit__fee-row--net">
          <span class="deposit__fee-label">Net amount</span>
          <span class="deposit__fee-value deposit__fee-value--net">{{ formatUSD(netAmount) }}</span>
        </div>
      </div>

      <!-- Submit -->
      <BaseButton
        type="submit"
        variant="primary"
        :loading="walletStore.isLoading"
      >
        Initiate Deposit
      </BaseButton>
    </form>
  </div>
</template>

<style lang="scss" scoped>
.deposit {
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

  // Segmented toggle
  &__toggle {
    display: inline-flex;
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 2px;
    gap: 2px;
  }

  &__toggle-btn {
    flex: 1;
    padding: var(--space-2) var(--space-4);
    font-size: var(--text-sm);
    font-weight: 500;
    font-family: var(--font-sans);
    border: none;
    border-radius: calc(var(--radius-md) - 2px);
    background: transparent;
    color: var(--color-text-muted);
    cursor: pointer;
    transition: background var(--transition-fast), color var(--transition-fast);

    &--active {
      background: var(--color-surface);
      color: var(--color-text);
      box-shadow: var(--shadow-sm);
    }

    &:hover:not(&--active) {
      color: var(--color-text);
    }
  }

  // Select
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

  // Address box
  &__address-box {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-2) var(--space-3);
  }

  &__address-text {
    flex: 1;
    font-family: var(--font-mono);
    font-size: var(--text-sm);
    color: var(--color-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__copy-btn {
    flex-shrink: 0;
    padding: var(--space-1) var(--space-3);
    font-size: var(--text-xs);
    font-weight: 600;
    font-family: var(--font-sans);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    color: var(--color-primary);
    cursor: pointer;
    transition: background var(--transition-fast);

    &:hover {
      background: var(--color-border);
    }
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

  // Success state
  &__success {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-8);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    align-items: flex-start;
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
