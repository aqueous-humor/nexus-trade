<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAccountStore } from '@/stores/account'
import { useInvestmentStore } from '@/stores/investment'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTable from '@/components/ui/BaseTable.vue'

const route = useRoute()
const accountStore = useAccountStore()
const investmentStore = useInvestmentStore()

const id = Number(route.params.id)

// ── Leverage modal ────────────────────────────────────────────────────────────

const LEVERAGE_OPTIONS = [1, 50, 100, 200, 500, 1000]

const showLeverageModal = ref(false)
const newLeverage = ref(1)
const leverageError = ref('')
const isUpdatingLeverage = ref(false)

function openLeverageModal() {
  newLeverage.value = accountStore.selectedAccount?.leverage ?? 1
  leverageError.value = ''
  showLeverageModal.value = true
}

async function confirmLeverageChange() {
  leverageError.value = ''
  isUpdatingLeverage.value = true
  try {
    await accountStore.updateLeverage(id, newLeverage.value)
    showLeverageModal.value = false
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    if (apiErr?.response?.data?.errors?.leverage) {
      leverageError.value = apiErr.response.data.errors.leverage[0] ?? ''
    } else if (apiErr?.response?.data?.message) {
      leverageError.value = apiErr.response.data.message
    } else {
      leverageError.value = 'Failed to update leverage. Please try again.'
    }
  } finally {
    isUpdatingLeverage.value = false
  }
}

// ── Investment history table ───────────────────────────────────────────────────

const investmentColumns = [
  { key: 'plan', label: 'Plan' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
  { key: 'result', label: 'Result' },
  { key: 'profit', label: 'Profit' },
  { key: 'date', label: 'Date' },
]

// Stub: show all investments for now (filter by account_id when API supports it)
const investmentRows = computed(() =>
  investmentStore.investments.map((inv) => ({
    id: inv.id,
    plan: inv.plan.name,
    amount: formatUSD(inv.amount_cents),
    status: inv.status,
    result: inv.result ?? '—',
    profit: inv.profit_cents != null ? formatUSD(inv.profit_cents) : '—',
    date: '—',
  })),
)

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr: string | undefined): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
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

function investmentStatusVariant(status: string): 'warning' | 'info' | 'success' | 'neutral' | 'danger' {
  const map: Record<string, 'warning' | 'info' | 'success' | 'neutral' | 'danger'> = {
    pending: 'warning',
    active: 'info',
    completed: 'success',
    cancelled: 'neutral',
    rejected: 'danger',
  }
  return map[status] ?? 'neutral'
}

function resultBadgeVariant(result: string): 'success' | 'danger' | 'neutral' {
  if (result === 'WIN') return 'success'
  if (result === 'LOSS') return 'danger'
  return 'neutral'
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  accountStore.fetchAccount(id)
  investmentStore.fetchInvestments()
})
</script>

<template>
  <div class="account-detail">
    <!-- Loading state -->
    <div v-if="accountStore.isLoading && !accountStore.selectedAccount" class="account-detail__loading">
      <div class="skeleton skeleton--title" />
      <div class="skeleton skeleton--block" />
    </div>

    <!-- Not found -->
    <div v-else-if="!accountStore.selectedAccount" class="account-detail__not-found">
      <p>Account not found.</p>
      <router-link to="/accounts" class="account-detail__back-link">← Back to Accounts</router-link>
    </div>

    <template v-else>
      <!-- Back link -->
      <router-link to="/accounts" class="account-detail__back-link">← Back to Accounts</router-link>

      <!-- Account info section -->
      <section class="account-detail__section">
        <h1 class="account-detail__title">Account #{{ accountStore.selectedAccount.id }}</h1>

        <div class="account-info">
          <div class="account-info__badges">
            <BaseBadge :variant="typeBadgeVariant(accountStore.selectedAccount.type)">
              {{ accountStore.selectedAccount.type === 'demo' ? 'Demo' : 'Live' }}
            </BaseBadge>
            <BaseBadge :variant="statusBadgeVariant(accountStore.selectedAccount.status)">
              {{ accountStore.selectedAccount.status.charAt(0).toUpperCase() + accountStore.selectedAccount.status.slice(1) }}
            </BaseBadge>
          </div>

          <dl class="account-info__grid">
            <div class="account-info__item">
              <dt class="account-info__label">Balance</dt>
              <dd class="account-info__value account-info__value--large">
                {{ formatUSD(accountStore.selectedAccount.balance_cents) }}
              </dd>
            </div>
            <div v-if="accountStore.selectedAccount.broker" class="account-info__item">
              <dt class="account-info__label">Broker</dt>
              <dd class="account-info__value">{{ accountStore.selectedAccount.broker.name }}</dd>
            </div>
            <div class="account-info__item">
              <dt class="account-info__label">Leverage</dt>
              <dd class="account-info__value">1:{{ accountStore.selectedAccount.leverage }}</dd>
            </div>
          </dl>
        </div>
      </section>

      <!-- Leverage control section -->
      <section class="account-detail__section">
        <h2 class="account-detail__section-title">Leverage</h2>
        <div class="leverage-control">
          <div class="leverage-control__current">
            <span class="leverage-control__label">Current leverage:</span>
            <span class="leverage-control__value">1:{{ accountStore.selectedAccount.leverage }}</span>
          </div>
          <BaseButton variant="secondary" size="sm" @click="openLeverageModal">
            Change Leverage
          </BaseButton>
        </div>
      </section>

      <!-- Signal subscription section -->
      <section class="account-detail__section">
        <h2 class="account-detail__section-title">Signal Subscription</h2>
        <p class="account-detail__muted">No active signal</p>
        <!-- TODO: implement signal subscription in phase 25 -->
      </section>

      <!-- Investment history section -->
      <section class="account-detail__section">
        <h2 class="account-detail__section-title">Investment History</h2>
        <BaseTable
          :columns="investmentColumns"
          :rows="investmentRows"
          :loading="investmentStore.isLoading"
        >
          <template #status="{ value }">
            <BaseBadge :variant="investmentStatusVariant(String(value))">
              {{ String(value).charAt(0).toUpperCase() + String(value).slice(1) }}
            </BaseBadge>
          </template>

          <template #result="{ value }">
            <BaseBadge v-if="value !== '—'" :variant="resultBadgeVariant(String(value))">
              {{ value }}
            </BaseBadge>
            <span v-else class="account-detail__muted">—</span>
          </template>

          <template #empty>
            <span>No investments found for this account.</span>
          </template>
        </BaseTable>
      </section>
    </template>

    <!-- Leverage change modal -->
    <BaseModal v-model="showLeverageModal" title="Change Leverage" size="sm">
      <div class="leverage-modal">
        <p class="leverage-modal__current">
          Current leverage: <strong>1:{{ accountStore.selectedAccount?.leverage }}</strong>
        </p>

        <div class="leverage-modal__field">
          <label class="leverage-modal__label" for="new-leverage">New Leverage</label>
          <select
            id="new-leverage"
            v-model="newLeverage"
            class="leverage-modal__select"
          >
            <option v-for="lev in LEVERAGE_OPTIONS" :key="lev" :value="lev">
              1:{{ lev }}
            </option>
          </select>
        </div>

        <p class="leverage-modal__warning">
          ⚠️ Leverage cannot be changed while an active investment exists.
        </p>

        <p v-if="leverageError" class="leverage-modal__error" role="alert">
          {{ leverageError }}
        </p>
      </div>

      <template #footer>
        <BaseButton variant="secondary" :disabled="isUpdatingLeverage" @click="showLeverageModal = false">
          Cancel
        </BaseButton>
        <BaseButton variant="primary" :loading="isUpdatingLeverage" @click="confirmLeverageChange">
          Confirm
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.account-detail {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);

  &__back-link {
    font-size: var(--text-sm);
    color: var(--color-primary);
    text-decoration: none;
    align-self: flex-start;

    &:hover {
      text-decoration: underline;
    }
  }

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 var(--space-4);
  }

  &__section {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__section-title {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
  }

  &__muted {
    color: var(--color-text-muted);
    font-size: var(--text-sm);
    margin: 0;
  }

  &__loading {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__not-found {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-16);
    text-align: center;
    color: var(--color-text-muted);
  }
}

// Account info
.account-info {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);

  &__badges {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: var(--space-4);
    margin: 0;
  }

  &__item {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__label {
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  &__value {
    font-size: var(--text-base);
    font-weight: 500;
    color: var(--color-text);

    &--large {
      font-size: var(--text-2xl);
      font-weight: 700;
    }
  }
}

// Leverage control
.leverage-control {
  display: flex;
  align-items: center;
  gap: var(--space-4);

  &__label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
  }

  &__value {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--color-text);
    margin-left: var(--space-1);
  }
}

// Leverage modal
.leverage-modal {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);

  &__current {
    font-size: var(--text-sm);
    color: var(--color-text);
    margin: 0;
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
    outline: none;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }

  &__warning {
    font-size: var(--text-xs);
    color: var(--color-warning);
    background: color-mix(in srgb, var(--color-warning) 10%, transparent);
    border-radius: var(--radius-md);
    padding: var(--space-2) var(--space-3);
    margin: 0;
  }

  &__error {
    font-size: var(--text-sm);
    color: var(--color-danger);
    margin: 0;
  }
}

// Skeleton
.skeleton {
  background: var(--color-surface-2);
  border-radius: var(--radius-sm);
  animation: shimmer 1.5s ease-in-out infinite;

  &--title {
    height: 2.5rem;
    width: 40%;
  }

  &--block {
    height: 12rem;
    width: 100%;
    border-radius: var(--radius-lg);
  }
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>
