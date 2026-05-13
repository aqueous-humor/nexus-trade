import client from './client'
import type { Account } from '@/stores/account'

export interface AccountListResponse {
  data: Account[]
}

export interface AccountResponse {
  data: Account
}

export interface CreateAccountPayload {
  type: 'demo' | 'live'
  leverage: number
  broker_id?: number
  broker_account_id?: string
}

export interface UpdateLeveragePayload {
  leverage: number
}

export const accountsApi = {
  index: () =>
    client.get<AccountListResponse>('/api/v1/accounts'),

  show: (id: number) =>
    client.get<AccountResponse>(`/api/v1/accounts/${id}`),

  store: (payload: CreateAccountPayload) =>
    client.post<AccountResponse>('/api/v1/accounts', payload),

  destroy: (id: number) =>
    client.delete(`/api/v1/accounts/${id}`),

  updateLeverage: (id: number, payload: UpdateLeveragePayload) =>
    client.patch<AccountResponse>(`/api/v1/accounts/${id}/leverage`, payload),
}
