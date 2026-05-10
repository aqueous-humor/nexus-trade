<script setup lang="ts">
import { computed } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import { useInvestmentStore } from '@/stores/investment'
import BaseBadge from '@/components/ui/BaseBadge.vue'

const walletStore = useWalletStore()
const investmentStore = useInvestmentStore()

const recentTransactions = computed(() => walletStore.transactions.slice(0, 5))
const recentInvestments = computed(() => investmentStore.investments.slice(0, 5))

const isLoading = computed(() => walletStore.isLoading || investmentStore.isLoading)

type BadgeVariant = 'success' | 'danger' | 'warning' | 'info' | 'neutral'

function transactionBadgeVariant(type: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    deposit: 'success',
    withdrawal: 'warning',
    profit: 'success',
    fee: 'neutral',
    refund: 'info',
  }
  return map[type] ?? 'neutral'
}

function investmentStatusVariant(status: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    pending: 'warning',
    active: 'info',
    completed: 'success',
    cancelled: 'neutral',
    rejected: 'danger',
  }
  return map[status] ?? 'neutral'
}

function investmentResultVariant(result: string): BadgeVariant {
  const map: Record<string, BadgeVariant> = {
    WIN: 'success',
    LOSS: 'danger',
    DRAW: 'neutral',
  }
  return map[result] ?? 'neutral'
}

function formatAmount(cents: number, currency = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(dateStr))
}
</script>

<template>
  <div class="recent-activity">
    <!-- Transactions section -->
    <div class="recent-activity__section">
      <h3 class="recent-activity__title">Recent Transactions</h3>

      <!-- Loading skeleton -->
      <template v-if="isLoading">
        <div v-for="i in 3" :key="i" class="skeleton-row">
          <div class="skeleton skeleton--badge" />
          <div class="skeleton skeleton--text" />
          <div class="skeleton skeleton--text skeleton--text-sm" />
        </div>
      </template>

      <!-- Empty state -->
      <div
        v-else-if="recentTransactions.length === 0"
        class="recent-activity__empty"
      >
        No recent activity
      </div>

      <!-- Transaction rows -->
      <template v-else>
        <div
          v-for="tx in recentTransactions"
          :key="tx.id"
          class="recent-activity__row"
        >
          <BaseBadge :variant="transactionBadgeVariant(tx.type)" size="sm">
            {{ tx.type }}
          </BaseBadge>
          <span class="recent-activity__amount">
            {{ formatAmount(tx.net_amount_cents, tx.currency) }}
          </span>
          <span class="recent-activity__date">
            {{ formatDate(tx.created_at) }}
          </span>
        </div>
      </template>
    </div>

    <!-- Investments section -->
    <div class="recent-activity__section">
      <h3 class="recent-activity__title">Recent Investments</h3>

      <!-- Loading skeleton -->
      <template v-if="isLoading">
        <div v-for="i in 3" :key="i" class="skeleton-row">
          <div class="skeleton skeleton--text" />
          <div class="skeleton skeleton--badge" />
          <div class="skeleton skeleton--text skeleton--text-sm" />
        </div>
      </template>

      <!-- Empty state -->
      <div
        v-else-if="recentInvestments.length === 0"
        class="recent-activity__empty"
      >
        No recent activity
      </div>

      <!-- Investment rows -->
      <template v-else>
        <div
          v-for="inv in recentInvestments"
          :key="inv.id"
          class="recent-activity__row"
        >
          <span class="recent-activity__plan-name">{{ inv.plan.name }}</span>
          <span class="recent-activity__amount">
            {{ formatAmount(inv.amount_cents) }}
          </span>
          <div class="recent-activity__badges">
            <BaseBadge :variant="investmentStatusVariant(inv.status)" size="sm">
              {{ inv.status }}
            </BaseBadge>
            <BaseBadge
              v-if="inv.result"
              :variant="investmentResultVariant(inv.result)"
              size="sm"
            >
              {{ inv.result }}
            </BaseBadge>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.recent-activity {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-6);

  @media (max-width: 640px) {
    grid-template-columns: 1fr;
  }

  &__section {
    background: var(--color-surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__title {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--color-text);
    margin: 0 0 var(--space-2);
  }

  &__row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    border-bottom: 1px solid var(--color-border);

    &:last-child {
      border-bottom: none;
    }
  }

  &__plan-name {
    flex: 1;
    font-size: var(--text-sm);
    color: var(--color-text);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__amount {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
    white-space: nowrap;
    margin-left: auto;
  }

  &__date {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    white-space: nowrap;
  }

  &__badges {
    display: flex;
    gap: var(--space-1);
    flex-wrap: wrap;
  }

  &__empty {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    text-align: center;
    padding: var(--space-6) 0;
  }
}

// Shimmer animation
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.skeleton-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) 0;
  border-bottom: 1px solid var(--color-border);

  &:last-child {
    border-bottom: none;
  }
}

.skeleton {
  border-radius: var(--radius-sm);
  background: linear-gradient(
    90deg,
    var(--color-surface-2) 25%,
    var(--color-border) 50%,
    var(--color-surface-2) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;

  &--badge {
    height: 20px;
    width: 64px;
    border-radius: var(--radius-full);
  }

  &--text {
    height: 14px;
    flex: 1;
  }

  &--text-sm {
    flex: 0 0 60px;
  }
}
</style>
