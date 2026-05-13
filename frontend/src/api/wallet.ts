import client from './client'
import type { Transaction } from '@/stores/wallet'

export interface WalletResponse {
  data: {
    balance_cents: number
    currency: string
  }
}

export interface TransactionListResponse {
  data: Transaction[]
  meta: { current_page: number; last_page: number; total: number }
}

export interface DepositPayload {
  currency: string
  network?: string
  provider: string
  amount: number
  [key: string]: unknown
}

export interface WithdrawPayload {
  amount: number
  currency: string
  destination_address: string
  provider?: string
  [key: string]: unknown
}

export const walletApi = {
  show: () =>
    client.get<WalletResponse>('/api/v1/wallet'),

  transactions: (params?: Record<string, unknown>) =>
    client.get<TransactionListResponse>('/api/v1/wallet/transactions', { params }),

  deposit: (payload: DepositPayload) =>
    client.post('/api/v1/wallet/deposit', payload),

  confirmDeposit: (transactionId: number) =>
    client.post(`/api/v1/wallet/deposit/${transactionId}/confirm`),

  withdraw: (payload: WithdrawPayload) =>
    client.post('/api/v1/wallet/withdraw', payload),
}
