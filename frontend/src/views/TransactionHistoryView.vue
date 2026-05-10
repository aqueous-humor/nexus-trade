<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const walletStore = useWalletStore()

// ── Filter tabs ───────────────────────────────────────────────────────────────

type FilterTab = 'all' | 'deposit' | 'withdrawal' | 'profit' | 'fee'

const activeTab = ref<FilterTab>('all')

const tabs: { key: FilterTab; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'deposit', label: 'Deposits' },
  { key: 'withdrawal', label: 'Withdrawals' },
  { key: 'profit', label: 'Profits' },
  { key: 'fee', label: 'Fees' },
]

// ── Table columns ─────────────────────────────────────────────────────────────

const columns = [
  { key: 'type', label: 'Type' },
  { key: 'amount', label: 'Amount' },
  { key: 'fee', label: 'Fee' },
  { key: 'net_amount', label: 'Net Amount' },
  { key: 'status', label: 'Status' },
  { key: 'date', label: 'Date' },
]

// ── Filtered rows ─────────────────────────────────────────────────────────────

const filteredTransactions = computed(() => {
  if (activeTab.value === 'all') return walletStore.transactions
  return walletStore.transactions.filter((tx) => tx.type === activeTab.value)
})

const rows = computed(() =>
  filteredTransactions.value.map((tx) => ({
    id: tx.id,
    type: tx.type,
    amount: formatUSD(tx.amount_cents),
    fee: formatUSD(tx.fee_cents),
    net_amount: formatUSD(tx.net_amount_cents),
    status: tx.status,
    date: formatDate(tx.created_at),
    _raw_type: tx.type,
    _raw_status: tx.status,
  })),
)

// ── Pagination stub ───────────────────────────────────────────────────────────

// TODO: implement pagination — currently shows all transactions
const currentPage = ref(1)
const totalPages = ref(1) // stub

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
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

function labelForType(type: string): string {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

function labelForStatus(status: string): string {
  return status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  walletStore.fetchTransactions()
})
</script>

<template>
  <div class="tx-history">
    <h1 class="tx-history__title">Transaction History</h1>

    <!-- Filter tabs -->
    <div class="tx-history__tabs" role="tablist" aria-label="Filter transactions">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        role="tab"
        class="tx-history__tab"
        :class="{ 'tx-history__tab--active': activeTab === tab.key }"
        :aria-selected="activeTab === tab.key"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Table -->
    <BaseTable
      :columns="columns"
      :rows="rows"
      :loading="walletStore.isLoading"
    >
      <!-- Type column -->
      <template #type="{ row }">
        <BaseBadge :variant="typeBadgeVariant(String(row._raw_type))" size="sm">
          {{ labelForType(String(row._raw_type)) }}
        </BaseBadge>
      </template>

      <!-- Status column -->
      <template #status="{ row }">
        <BaseBadge :variant="statusBadgeVariant(String(row._raw_status))" size="sm">
          {{ labelForStatus(String(row._raw_status)) }}
        </BaseBadge>
      </template>

      <!-- Empty state -->
      <template #empty>
        <div class="tx-history__empty">
          <p>No transactions found.</p>
        </div>
      </template>
    </BaseTable>

    <!-- Pagination -->
    <!-- TODO: implement pagination — currently shows all transactions -->
    <div class="tx-history__pagination" aria-label="Pagination">
      <BaseButton
        variant="secondary"
        size="sm"
        :disabled="currentPage <= 1"
        @click="prevPage"
      >
        ← Prev
      </BaseButton>
      <span class="tx-history__page-counter">
        Page {{ currentPage }} of {{ totalPages }}
      </span>
      <BaseButton
        variant="secondary"
        size="sm"
        :disabled="currentPage >= totalPages"
        @click="nextPage"
      >
        Next →
      </BaseButton>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.tx-history {
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

  // Tabs
  &__tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--color-border);
  }

  &__tab {
    padding: var(--space-2) var(--space-4);
    font-size: var(--text-sm);
    font-weight: 500;
    font-family: var(--font-sans);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    color: var(--color-text-muted);
    cursor: pointer;
    transition: color var(--transition-fast), border-color var(--transition-fast);

    &:hover {
      color: var(--color-text);
    }

    &--active {
      color: var(--color-primary);
      border-bottom-color: var(--color-primary);
    }
  }

  // Empty state
  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-text-muted);
    font-size: var(--text-sm);

    p {
      margin: 0;
    }
  }

  // Pagination
  &__pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-4);
  }

  &__page-counter {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    min-width: 6rem;
    text-align: center;
  }
}
</style>
