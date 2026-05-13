import { ref } from 'vue'
import { defineStore } from 'pinia'
import { analyticsApi } from '@/api/analytics'
import type { PlatformMetrics } from '@/api/analytics'

export interface UserMetrics {
  total_invested_cents: number
  total_profit_cents: number
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
  const platformMetrics = ref<PlatformMetrics | null>(null)
  const platformTimeSeries = ref<TimeSeriesPoint[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchUserMetrics(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await analyticsApi.userMetrics()
      userMetrics.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load metrics'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUserTimeSeries(
    granularity: 'day' | 'week' | 'month',
    from: string,
    to: string,
  ): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await analyticsApi.userTimeSeries({ granularity, from, to })
      timeSeries.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load time series'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPlatformMetrics(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await analyticsApi.platformMetrics()
      platformMetrics.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load platform metrics'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPlatformTimeSeries(
    granularity: 'day' | 'week' | 'month',
    from: string,
    to: string,
  ): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await analyticsApi.platformTimeSeries({ granularity, from, to })
      platformTimeSeries.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load platform time series'
    } finally {
      isLoading.value = false
    }
  }

  return {
    userMetrics,
    timeSeries,
    platformMetrics,
    platformTimeSeries,
    isLoading,
    error,
    fetchUserMetrics,
    fetchUserTimeSeries,
    fetchPlatformMetrics,
    fetchPlatformTimeSeries,
  }
})
