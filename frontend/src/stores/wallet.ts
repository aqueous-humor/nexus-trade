import { ref } from 'vue'
import { defineStore } from 'pinia'
import { walletApi } from '@/api/wallet'
import type { DepositPayload, WithdrawPayload } from '@/api/wallet'

export interface Transaction {
  id: number
  type: string
  status: string
  amount_cents: number
  fee_cents: number
  net_amount_cents: number
  currency: string
  created_at: string
  reference?: string
  provider?: string
  destination_address?: string
}

export interface TransactionMeta {
  current_page: number
  last_page: number
  total: number
}

export const useWalletStore = defineStore('wallet', () => {
  const balance = ref<number>(0)
  const currency = ref<string>('USD')
  const transactions = ref<Transaction[]>([])
  const transactionMeta = ref<TransactionMeta>({ current_page: 1, last_page: 1, total: 0 })
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchBalance(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await walletApi.show()
      balance.value = res.data.data.balance_cents
      currency.value = res.data.data.currency
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load wallet'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTransactions(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await walletApi.transactions(filters)
      transactions.value = res.data.data
      transactionMeta.value = res.data.meta
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load transactions'
    } finally {
      isLoading.value = false
    }
  }

  async function deposit(data: DepositPayload): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      await walletApi.deposit(data)
      await fetchBalance()
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Deposit failed'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function withdraw(data: WithdrawPayload): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      await walletApi.withdraw(data)
      await fetchBalance()
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Withdrawal failed'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  function setBalance(cents: number): void {
    balance.value = cents
  }

  return {
    balance,
    currency,
    transactions,
    transactionMeta,
    isLoading,
    error,
    fetchBalance,
    fetchTransactions,
    deposit,
    withdraw,
    setBalance,
  }
})
