<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useInvestmentStore } from '@/stores/investment'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const investmentStore = useInvestmentStore()

// ── Table columns ─────────────────────────────────────────────────────────────

const columns = [
  { key: 'plan', label: 'Plan' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
  { key: 'result', label: 'Result' },
  { key: 'profit', label: 'Profit' },
  { key: 'maturity_date', label: 'Maturity Date' },
  { key: 'actions', label: 'Actions' },
]

// ── Rows ──────────────────────────────────────────────────────────────────────

const rows = computed(() =>
  investmentStore.investments.map((inv) => ({
    id: inv.id,
    plan: inv.plan.name,
    amount: formatUSD(inv.amount_cents),
    status: inv.status,
    result: inv.result ?? null,
    profit: inv.profit_cents != null ? inv.profit_cents : null,
    maturity_date: null as string | null, // TODO: add maturity_at to Investment type
    _raw_status: inv.status,
    _raw_result: inv.result ?? null,
    _raw_profit: inv.profit_cents ?? null,
  })),
)

// ── Cancel confirmation ───────────────────────────────────────────────────────

const cancellingId = ref<number | null>(null)

async function cancelInvestment(id: number) {
  if (!confirm('Are you sure you want to cancel this investment?')) return
  cancellingId.value = id
  try {
    await investmentStore.cancelInvestment(id)
  } finally {
    cancellingId.value = null
  }
}

// ── Pagination stub ───────────────────────────────────────────────────────────

// TODO: implement pagination — currently shows all investments
const currentPage = ref(1)
const totalPages = ref(1) // stub

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}

function statusBadgeVariant(status: string): 'warning' | 'info' | 'success' | 'neutral' | 'danger' {
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
  investmentStore.fetchInvestments()
})
</script>

<template>
  <div class="investment-history">
    <h1 class="investment-history__title">Investment History</h1>

    <BaseTable
      :columns="columns"
      :rows="rows"
      :loading="investmentStore.isLoading"
    >
      <!-- Status column -->
      <template #status="{ row }">
        <BaseBadge :variant="statusBadgeVariant(String(row._raw_status))">
          {{ String(row._raw_status).charAt(0).toUpperCase() + String(row._raw_status).slice(1) }}
        </BaseBadge>
      </template>

      <!-- Result column -->
      <template #result="{ row }">
        <BaseBadge
          v-if="row._raw_result"
          :variant="resultBadgeVariant(String(row._raw_result))"
        >
          {{ row._raw_result }}
        </BaseBadge>
        <span v-else class="investment-history__muted">—</span>
      </template>

      <!-- Profit column -->
      <template #profit="{ row }">
        <span
          v-if="row._raw_result === 'WIN' && row._raw_profit != null"
          class="investment-history__profit investment-history__profit--win"
        >
          +{{ formatUSD(Number(row._raw_profit)) }}
        </span>
        <span v-else-if="row._raw_result === 'LOSS' || row._raw_result === 'DRAW'" class="investment-history__muted">
          $0.00
        </span>
        <span v-else class="investment-history__muted">—</span>
      </template>

      <!-- Maturity date column -->
      <template #maturity_date="{ row }">
        {{ formatDate(row.maturity_date as string | null) }}
      </template>

      <!-- Actions column -->
      <template #actions="{ row }">
        <BaseButton
          v-if="row._raw_status === 'pending'"
          variant="danger"
          size="sm"
          :loading="cancellingId === row.id"
          @click="cancelInvestment(Number(row.id))"
        >
          Cancel
        </BaseButton>
        <span v-else class="investment-history__muted">—</span>
      </template>

      <!-- Empty state -->
      <template #empty>
        <div class="investment-history__empty">
          <p>No investments found.</p>
          <router-link to="/plans" class="investment-history__link">Browse plans →</router-link>
        </div>
      </template>
    </BaseTable>

    <!-- Pagination -->
    <!-- TODO: implement pagination — currently shows all investments -->
    <div class="investment-history__pagination" aria-label="Pagination">
      <BaseButton
        variant="secondary"
        size="sm"
        :disabled="currentPage <= 1"
        @click="prevPage"
      >
        ← Prev
      </BaseButton>
      <span class="investment-history__page-counter">
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
.investment-history {
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

  &__muted {
    color: var(--color-text-muted);
    font-size: var(--text-sm);
  }

  &__profit {
    font-size: var(--text-sm);
    font-weight: 600;

    &--win {
      color: var(--color-success);
    }
  }

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

  &__link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }

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
