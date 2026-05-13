import { ref } from 'vue'
import { defineStore } from 'pinia'
import { investmentsApi } from '@/api/investments'
import type { CreateInvestmentPayload, Duration } from '@/api/investments'

export interface Investment {
  id: number
  amount_cents: number
  profit_cents: number | null
  adjusted_profit_cents?: number | null
  status: 'pending' | 'active' | 'completed' | 'cancelled' | 'rejected'
  result?: 'WIN' | 'LOSS' | 'DRAW' | null
  maturity_at?: string
  activated_at?: string
  completed_at?: string
  terms_version?: string
  account?: { id: number; type: string }
  duration?: Duration
  plan: {
    id: number
    name: string
    roi_percentage: number
  }
}

export interface InvestmentPlan {
  id: number
  name: string
  description: string
  min_amount_cents: number
  max_amount_cents: number
  roi_percentage: number
  profit_min_pct?: number
  profit_max_pct?: number
  is_trending?: boolean
  status: string
  durations?: Duration[]
}

export interface InvestmentMeta {
  current_page: number
  last_page: number
  total: number
}

export const useInvestmentStore = defineStore('investment', () => {
  const investments = ref<Investment[]>([])
  const activeInvestments = ref<Investment[]>([])
  const plans = ref<InvestmentPlan[]>([])
  const meta = ref<InvestmentMeta>({ current_page: 1, last_page: 1, total: 0 })
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchInvestments(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await investmentsApi.index(filters)
      investments.value = res.data.data
      meta.value = res.data.meta
      if (filters?.status === 'active') {
        activeInvestments.value = res.data.data
      }
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load investments'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPlans(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await investmentsApi.plans()
      plans.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load plans'
    } finally {
      isLoading.value = false
    }
  }

  async function createInvestment(data: CreateInvestmentPayload): Promise<Investment> {
    isLoading.value = true
    error.value = null
    try {
      const res = await investmentsApi.store(data)
      investments.value.unshift(res.data.data)
      return res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Investment failed'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function cancelInvestment(id: number): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      await investmentsApi.cancel(id)
      const idx = investments.value.findIndex((i) => i.id === id)
      const target = idx !== -1 ? investments.value[idx] : undefined
      if (target) target.status = 'cancelled'
      activeInvestments.value = activeInvestments.value.filter((i) => i.id !== id)
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Cancel failed'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  return {
    investments,
    activeInvestments,
    plans,
    meta,
    isLoading,
    error,
    fetchInvestments,
    fetchPlans,
    createInvestment,
    cancelInvestment,
  }
})
