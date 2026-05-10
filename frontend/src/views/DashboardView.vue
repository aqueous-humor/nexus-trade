<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics'
import { useWalletStore } from '@/stores/wallet'
import { useInvestmentStore } from '@/stores/investment'
import { useAuthStore } from '@/stores/auth'
import { useEcho } from '@/composables/useEcho'
import { usePolling } from '@/composables/usePolling'
import MetricCard from '@/components/dashboard/MetricCard.vue'
import RecentActivity from '@/components/dashboard/RecentActivity.vue'
import LineChart from '@/components/charts/LineChart.vue'
import DonutChart from '@/components/charts/DonutChart.vue'

const analyticsStore = useAnalyticsStore()
const walletStore = useWalletStore()
const investmentStore = useInvestmentStore()
const authStore = useAuthStore()
const echo = useEcho()
const polling = usePolling()

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(cents / 100)
}

// ── Metric card values ────────────────────────────────────────────────────────

const totalInvested = computed(() =>
  analyticsStore.userMetrics ? formatUSD(analyticsStore.userMetrics.total_invested) : '$0.00',
)

const totalProfit = computed(() =>
  analyticsStore.userMetrics ? formatUSD(analyticsStore.userMetrics.total_profit) : '$0.00',
)

const roiPercentage = computed(() =>
  analyticsStore.userMetrics
    ? `${analyticsStore.userMetrics.roi_percentage.toFixed(2)}%`
    : '0.00%',
)

const activeInvestments = computed(() =>
  analyticsStore.userMetrics
    ? String(analyticsStore.userMetrics.active_investments)
    : '0',
)

// ── Chart data ────────────────────────────────────────────────────────────────

const lineChartSeries = computed(() => [
  {
    name: 'Investment Value',
    data: analyticsStore.timeSeries.map((p) => p.value / 100),
  },
])

const lineChartCategories = computed(() =>
  analyticsStore.timeSeries.map((p) =>
    new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' }).format(
      new Date(p.date),
    ),
  ),
)

// Donut: distribution of active investments by plan name
const donutSeries = computed(() => {
  const planMap = new Map<string, number>()
  for (const inv of investmentStore.investments) {
    const name = inv.plan.name
    planMap.set(name, (planMap.get(name) ?? 0) + inv.amount_cents)
  }
  return Array.from(planMap.values()).map((v) => v / 100)
})

const donutLabels = computed(() => {
  const planMap = new Map<string, number>()
  for (const inv of investmentStore.investments) {
    const name = inv.plan.name
    planMap.set(name, (planMap.get(name) ?? 0) + inv.amount_cents)
  }
  return Array.from(planMap.keys())
})

// ── Loading states ────────────────────────────────────────────────────────────

const metricsLoading = computed(() => analyticsStore.isLoading)
const chartsLoading = computed(() => analyticsStore.isLoading || investmentStore.isLoading)

// ── Lifecycle ─────────────────────────────────────────────────────────────────

let unsubscribe: (() => void) | null = null

onMounted(async () => {
  const today = new Date().toISOString().split('T')[0]
  const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
    .toISOString()
    .split('T')[0]

  await Promise.all([
    analyticsStore.fetchUserMetrics(),
    analyticsStore.fetchUserTimeSeries('day', thirtyDaysAgo, today),
    walletStore.fetchTransactions(),
    investmentStore.fetchInvestments({ status: 'active' }),
  ])

  // Subscribe to real-time updates if user is authenticated
  if (authStore.user) {
    unsubscribe = echo.subscribeToUserChannel(authStore.user.id, {
      onWalletUpdated(data) {
        const payload = data as { balance_cents?: number }
        if (payload.balance_cents !== undefined) {
          walletStore.balance = payload.balance_cents
        }
      },
      onInvestmentStatusChanged() {
        investmentStore.fetchInvestments({ status: 'active' })
      },
    })
  }

  // Fall back to polling when WebSocket disconnects
  watch(
    () => echo.isConnected.value,
    (connected) => {
      if (connected) {
        polling.stop()
      } else {
        polling.start()
      }
    },
    { immediate: true },
  )
})

onUnmounted(() => {
  unsubscribe?.()
  polling.stop()
})
</script>

<template>
  <div class="dashboard">
    <h1 class="dashboard__title">Dashboard</h1>

    <!-- Metric cards -->
    <div class="dashboard__metrics">
      <MetricCard
        label="Total Invested"
        :value="totalInvested"
        :loading="metricsLoading"
      />
      <MetricCard
        label="Total Profit"
        :value="totalProfit"
        :loading="metricsLoading"
      />
      <MetricCard
        label="ROI %"
        :value="roiPercentage"
        :loading="metricsLoading"
      />
      <MetricCard
        label="Active Investments"
        :value="activeInvestments"
        :loading="metricsLoading"
      />
    </div>

    <!-- Charts -->
    <div class="dashboard__charts">
      <div class="dashboard__chart-card">
        <h2 class="dashboard__section-title">Investment Performance</h2>
        <LineChart
          :series="lineChartSeries"
          :categories="lineChartCategories"
          :loading="chartsLoading"
        />
      </div>

      <div class="dashboard__chart-card">
        <h2 class="dashboard__section-title">Plan Distribution</h2>
        <DonutChart
          :series="donutSeries"
          :labels="donutLabels"
          :loading="chartsLoading"
        />
      </div>
    </div>

    <!-- Recent activity -->
    <RecentActivity />
  </div>
</template>

<style lang="scss" scoped>
.dashboard {
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

  &__charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-6);

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  &__chart-card {
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
}
</style>
