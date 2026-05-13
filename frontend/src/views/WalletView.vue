<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWalletStore } from '@/stores/wallet'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const walletStore = useWalletStore()

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr: string): string {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}

// ── Badge variant maps ────────────────────────────────────────────────────────

type BadgeVariant = 'success' | 'warning' | 'info' | 'neutral' | 'danger'

function typeBadgeVariant(type: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    deposit: 'success',
    withdrawal: 'warning',
    profit: 'success',
    fee: 'neutral',
    refund: 'info',
    cancellation: 'neutral',
  }
  return map[type] ?? 'neutral'
}

function statusBadgeVariant(status: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    completed: 'success',
    pending: 'warning',
    failed: 'danger',
    pending_review: 'warning',
  }
  return map[status] ?? 'neutral'
}

// ── Recent transactions (last 5) ──────────────────────────────────────────────

const recentTransactions = computed(() =>
  walletStore.transactions.slice(0, 5),
)

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  walletStore.fetchBalance()
  walletStore.fetchTransactions()
})
</script>

<template>
  <div class="wallet">
    <h1 class="wallet__title">Wallet</h1>

    <!-- Balance card -->
    <div class="wallet__balance-card">
      <div v-if="walletStore.isLoading" class="wallet__skeleton wallet__skeleton--balance" aria-busy="true" />
      <template v-else>
        <p class="wallet__balance-label">Available Balance</p>
        <p class="wallet__balance-amount">{{ formatUSD(walletStore.balance) }}</p>
        <div class="wallet__actions">
          <BaseButton variant="primary" @click="router.push('/app/wallet/deposit')">
            Deposit
          </BaseButton>
          <BaseButton variant="secondary" @click="router.push('/app/wallet/withdraw')">
            Withdraw
          </BaseButton>
        </div>
      </template>
    </div>

    <!-- Recent transactions -->
    <section class="wallet__section">
      <div class="wallet__section-header">
        <h2 class="wallet__section-title">Recent Transactions</h2>
        <router-link to="/app/wallet/transactions" class="wallet__link">
          View all transactions →
        </router-link>
      </div>

      <!-- Loading skeleton -->
      <div v-if="walletStore.isLoading" class="wallet__tx-skeleton" aria-busy="true">
        <div v-for="i in 5" :key="i" class="wallet__tx-skeleton-row">
          <div class="wallet__skeleton wallet__skeleton--badge" />
          <div class="wallet__skeleton wallet__skeleton--text" />
          <div class="wallet__skeleton wallet__skeleton--badge" />
          <div class="wallet__skeleton wallet__skeleton--text" />
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="recentTransactions.length === 0"
        class="wallet__empty"
      >
        <p>No transactions yet.</p>
      </div>

      <!-- Transaction rows -->
      <ul v-else class="wallet__tx-list" role="list">
        <li
          v-for="tx in recentTransactions"
          :key="tx.id"
          class="wallet__tx-row"
        >
          <BaseBadge :variant="typeBadgeVariant(tx.type)" size="sm">
            {{ tx.type.charAt(0).toUpperCase() + tx.type.slice(1) }}
          </BaseBadge>

          <span class="wallet__tx-amount">{{ formatUSD(tx.net_amount_cents) }}</span>

          <BaseBadge :variant="statusBadgeVariant(tx.status)" size="sm">
            {{ tx.status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
          </BaseBadge>

          <span class="wallet__tx-date">{{ formatDate(tx.created_at) }}</span>
        </li>
      </ul>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.wallet {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
  max-width: 720px;

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  // Balance card
  &__balance-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-8);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    box-shadow: var(--shadow-md);
  }

  &__balance-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
  }

  &__balance-amount {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    font-variant-numeric: tabular-nums;
  }

  &__actions {
    display: flex;
    gap: var(--space-3);
  }

  // Section
  &__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__section-title {
    font-size: var(--text-xl);
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
  }

  &__link {
    font-size: var(--text-sm);
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }

  // Transaction list
  &__tx-list {
    list-style: none;
    margin: 0;
    padding: 0;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
  }

  &__tx-row {
    display: grid;
    grid-template-columns: 120px 1fr 140px 120px;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--color-border);
    transition: background var(--transition-fast);

    &:last-child {
      border-bottom: none;
    }

    &:hover {
      background: var(--color-surface-2);
    }
  }

  &__tx-amount {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
    font-variant-numeric: tabular-nums;
  }

  &__tx-date {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    text-align: right;
  }

  // Empty state
  &__empty {
    padding: var(--space-12) var(--space-4);
    text-align: center;
    color: var(--color-text-muted);
    font-size: var(--text-sm);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);

    p {
      margin: 0;
    }
  }

  // Skeletons
  &__tx-skeleton {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-3) var(--space-4);
  }

  &__tx-skeleton-row {
    display: grid;
    grid-template-columns: 120px 1fr 140px 120px;
    gap: var(--space-4);
    align-items: center;
  }

  &__skeleton {
    background: var(--color-surface-2);
    border-radius: var(--radius-sm);
    animation: shimmer 1.5s ease-in-out infinite;

    &--balance {
      height: 5rem;
      border-radius: var(--radius-md);
    }

    &--badge {
      height: 1.25rem;
      width: 80px;
    }

    &--text {
      height: 1rem;
    }
  }
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>
