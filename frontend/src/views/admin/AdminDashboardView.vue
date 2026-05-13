<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import MetricCard from '@/components/dashboard/MetricCard.vue'
import LineChart from '@/components/charts/LineChart.vue'
import BaseTable from '@/components/ui/BaseTable.vue'

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(cents / 100)
}

const adminStore = useAdminStore()

const totalInvestments = computed(() => String(adminStore.metrics?.total_investments ?? 0))
const totalValue       = computed(() => formatUSD(adminStore.metrics?.total_value_cents ?? 0))
const activeUsers      = computed(() => String(adminStore.metrics?.active_users ?? 0))
const totalProfitPaid  = computed(() => formatUSD(adminStore.metrics?.total_profit_paid_cents ?? 0))

const chartSeries = computed<{ name: string; data: number[] }[]>(() => [{
  name: 'Investment Growth',
  data: adminStore.metrics?.investment_growth.map((d) => d.value) ?? [],
}])

const chartCategories = computed<string[]>(() =>
  adminStore.metrics?.investment_growth.map((d) => d.date) ?? [],
)

const topPlansColumns = [
  { key: 'name',           label: 'Plan Name' },
  { key: 'total_invested', label: 'Total Invested' },
  { key: 'count',          label: 'Count' },
]

const topPlansRows = computed(() => adminStore.metrics?.top_plans ?? [])

const activeUsersLast24h  = computed(() => adminStore.metrics?.active_users_last_24h ?? 0)
const activeUsersLast7d   = computed(() => adminStore.metrics?.active_users_last_7d ?? 0)
const activeUsersLast30d  = computed(() => adminStore.metrics?.active_users_last_30d ?? 0)

onMounted(() => {
  adminStore.fetchMetrics()
})
</script>

<template>
  <div class="admin-dashboard">
    <h1 class="admin-dashboard__title">Admin Dashboard</h1>

    <!-- Metric cards -->
    <div class="admin-dashboard__metrics">
      <MetricCard label="Total Investments" :value="totalInvestments" :loading="adminStore.isLoading" />
      <MetricCard label="Total Value" :value="totalValue" :loading="adminStore.isLoading" />
      <MetricCard label="Active Users" :value="activeUsers" :loading="adminStore.isLoading" />
      <MetricCard label="Total Profit Paid" :value="totalProfitPaid" :loading="adminStore.isLoading" />
    </div>

    <!-- Investment growth chart -->
    <div class="admin-dashboard__card">
      <h2 class="admin-dashboard__section-title">Investment Growth</h2>
      <LineChart :series="chartSeries" :categories="chartCategories" :loading="adminStore.isLoading" />
    </div>

    <!-- Bottom row -->
    <div class="admin-dashboard__bottom">
      <!-- Top 5 plans -->
      <div class="admin-dashboard__card">
        <h2 class="admin-dashboard__section-title">Top 5 Plans</h2>
        <BaseTable :columns="topPlansColumns" :rows="topPlansRows" :loading="adminStore.isLoading">
          <template #empty>No plan data available</template>
        </BaseTable>
      </div>

      <!-- Active users by period -->
      <div class="admin-dashboard__card">
        <h2 class="admin-dashboard__section-title">Active Users by Period</h2>
        <div class="admin-dashboard__period-stats">
          <div class="admin-dashboard__period-stat">
            <span class="admin-dashboard__period-label">Last 24h</span>
            <span class="admin-dashboard__period-value">{{ activeUsersLast24h }}</span>
          </div>
          <div class="admin-dashboard__period-stat">
            <span class="admin-dashboard__period-label">Last 7 days</span>
            <span class="admin-dashboard__period-value">{{ activeUsersLast7d }}</span>
          </div>
          <div class="admin-dashboard__period-stat">
            <span class="admin-dashboard__period-label">Last 30 days</span>
            <span class="admin-dashboard__period-value">{{ activeUsersLast30d }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.admin-dashboard {
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

  &__metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-4);

    @media (max-width: 1024px) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (max-width: 480px) {
      grid-template-columns: 1fr;
    }
  }

  &__card {
    background: var(--color-surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--space-6);
  }

  &__section-title {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--color-text);
    margin: 0 0 var(--space-4);
  }

  &__bottom {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--space-6);

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  &__period-stats {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__period-stat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4);
    background: var(--color-surface-2);
    border-radius: var(--radius-md);
  }

  &__period-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 500;
  }

  &__period-value {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--color-text);
  }
}
</style>
