import client from './client'
import type { Investment, InvestmentPlan } from '@/stores/investment'

export interface PlanListResponse {
  data: (InvestmentPlan & { durations: Duration[] })[]
}

export interface Duration {
  id: number
  unit: 'hour' | 'day' | 'week' | 'month'
  value: number
  label: string
}

export interface InvestmentListResponse {
  data: Investment[]
  meta: { current_page: number; last_page: number; total: number }
}

export interface InvestmentResponse {
  data: Investment
}

export interface TermsResponse {
  data: { version: string; content: string; effective_at: string }
}

export interface CreateInvestmentPayload {
  account_id: number
  plan_id: number
  duration_id: number
  amount_cents: number
  terms_version: string
}

export const investmentsApi = {
  plans: () =>
    client.get<PlanListResponse>('/api/v1/plans'),

  plan: (id: number) =>
    client.get<{ data: InvestmentPlan & { durations: Duration[] } }>(`/api/v1/plans/${id}`),

  index: (params?: Record<string, unknown>) =>
    client.get<InvestmentListResponse>('/api/v1/investments', { params }),

  show: (id: number) =>
    client.get<InvestmentResponse>(`/api/v1/investments/${id}`),

  store: (payload: CreateInvestmentPayload) =>
    client.post<InvestmentResponse>('/api/v1/investments', payload),

  cancel: (id: number) =>
    client.post(`/api/v1/investments/${id}/cancel`),

  currentTerms: () =>
    client.get<TermsResponse>('/api/v1/terms/current'),

  acceptTerms: (version: string) =>
    client.post('/api/v1/terms/accept', { version }),
}
