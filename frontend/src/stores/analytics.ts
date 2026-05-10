import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface UserMetrics {
  total_invested: number
  total_profit: number
  roi_percentage: number
  active_investments: number
}

export interface TimeSeriesPoint {
  date: string
  value: number
}

export const useAnalyticsStore = defineStore('analytics', () => {
  const userMetrics = ref<UserMetrics | null>(null)
  const timeSeries = ref<TimeSeriesPoint[]>([])
  const isLoading = ref(false)

  async function fetchUserMetrics(): Promise<void> {
    // TODO: implement in analytics phase — call GET /api/v1/analytics/metrics
    isLoading.value = true
    try {
      // Will update userMetrics from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUserTimeSeries(
    granularity: 'day' | 'week' | 'month',
    from: string,
    to: string,
  ): Promise<void> {
    // TODO: implement in analytics phase — call GET /api/v1/analytics/time-series
    isLoading.value = true
    try {
      // Will update timeSeries from response
    } finally {
      isLoading.value = false
    }
  }

  return { userMetrics, timeSeries, isLoading, fetchUserMetrics, fetchUserTimeSeries }
})
