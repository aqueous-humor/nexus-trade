import client from './client'

export interface Signal {
  id: number
  name: string
  description: string
  status: 'active' | 'inactive'
  provider_metadata: Record<string, unknown>
  created_at: string
}

export interface SignalListResponse {
  data: Signal[]
}

export const signalsApi = {
  index: () =>
    client.get<SignalListResponse>('/api/v1/signals'),

  subscribe: (accountId: number) =>
    client.post(`/api/v1/accounts/${accountId}/signal`),

  unsubscribe: (accountId: number) =>
    client.delete(`/api/v1/accounts/${accountId}/signal`),
}
