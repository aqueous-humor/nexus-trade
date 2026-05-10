import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface Transaction {
  id: number
  type: string
  status: string
  amount_cents: number
  fee_cents: number
  net_amount_cents: number
  currency: string
  created_at: string
}

export const useWalletStore = defineStore('wallet', () => {
  const balance = ref<number>(0)
  const currency = ref<string>('USD')
  const transactions = ref<Transaction[]>([])
  const isLoading = ref(false)

  async function fetchBalance(): Promise<void> {
    // TODO: implement in wallet phase — call GET /api/v1/wallet/balance
    isLoading.value = true
    try {
      // Will update balance and currency from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTransactions(filters?: Record<string, unknown>): Promise<void> {
    // TODO: implement in wallet phase — call GET /api/v1/wallet/transactions
    isLoading.value = true
    try {
      // Will update transactions from response
    } finally {
      isLoading.value = false
    }
  }

  async function deposit(data: {
    amount: number
    currency: string
    provider: string
    [key: string]: unknown
  }): Promise<void> {
    // TODO: implement in wallet phase — call POST /api/v1/wallet/deposit
    isLoading.value = true
    try {
      // Will trigger balance refresh after success
    } finally {
      isLoading.value = false
    }
  }

  async function withdraw(data: {
    amount: number
    currency: string
    destination_address: string
    [key: string]: unknown
  }): Promise<void> {
    // TODO: implement in wallet phase — call POST /api/v1/wallet/withdraw
    isLoading.value = true
    try {
      // Will trigger balance refresh after success
    } finally {
      isLoading.value = false
    }
  }

  return { balance, currency, transactions, isLoading, fetchBalance, fetchTransactions, deposit, withdraw }
})
