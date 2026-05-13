import client from './client'
import type { UserMetrics, TimeSeriesPoint } from '@/stores/analytics'

export interface UserMetricsResponse {
  data: UserMetrics
}

export interface TimeSeriesResponse {
  data: TimeSeriesPoint[]
}

export interface PlatformMetrics {
  total_investments_count: number
  total_investments_value: number
  active_users_count: number
  total_profit_paid: number
  plan_distribution: Record<string, number>
  top_plans: { name: string; total: number }[]
  active_users_last_24h: number
  active_users_last_7d: number
  active_users_last_30d: number
}

export interface PlatformMetricsResponse {
  data: PlatformMetrics
}

export const analyticsApi = {
  userMetrics: () =>
    client.get<UserMetricsResponse>('/api/v1/analytics/me'),

  userTimeSeries: (params: { granularity: string; from: string; to: string }) =>
    client.get<TimeSeriesResponse>('/api/v1/analytics/me/timeseries', { params }),

  platformMetrics: () =>
    client.get<PlatformMetricsResponse>('/api/v1/admin/analytics'),

  platformTimeSeries: (params: { granularity: string; from: string; to: string }) =>
    client.get<TimeSeriesResponse>('/api/v1/admin/analytics/timeseries', { params }),
}
