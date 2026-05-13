import { ref } from 'vue'
import { defineStore } from 'pinia'
import { accountsApi } from '@/api/accounts'
import type { CreateAccountPayload } from '@/api/accounts'

export interface Account {
  id: number
  type: 'demo' | 'live'
  balance_cents: number
  leverage: number
  status: string
  broker_account_id?: string
  signal_subscription?: { signal_id: number; signal: { id: number; name: string } } | null
  broker?: {
    id: number
    name: string
    platform_type: 'MT4' | 'MT5'
  }
}

export const useAccountStore = defineStore('account', () => {
  const accounts = ref<Account[]>([])
  const selectedAccount = ref<Account | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchAccounts(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await accountsApi.index()
      accounts.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load accounts'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchAccount(id: number): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await accountsApi.show(id)
      selectedAccount.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to load account'
    } finally {
      isLoading.value = false
    }
  }

  async function createAccount(data: CreateAccountPayload): Promise<Account> {
    isLoading.value = true
    error.value = null
    try {
      const res = await accountsApi.store(data)
      accounts.value.push(res.data.data)
      return res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to create account'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function updateLeverage(id: number, leverage: number): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const res = await accountsApi.updateLeverage(id, { leverage })
      const idx = accounts.value.findIndex((a) => a.id === id)
      if (idx !== -1) accounts.value[idx] = res.data.data
      if (selectedAccount.value?.id === id) selectedAccount.value = res.data.data
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to update leverage'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function deleteAccount(id: number): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      await accountsApi.destroy(id)
      accounts.value = accounts.value.filter((a) => a.id !== id)
      if (selectedAccount.value?.id === id) selectedAccount.value = null
    } catch (e: unknown) {
      error.value = (e as Error)?.message ?? 'Failed to delete account'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  return {
    accounts,
    selectedAccount,
    isLoading,
    error,
    fetchAccounts,
    fetchAccount,
    createAccount,
    updateLeverage,
    deleteAccount,
  }
})
