import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface Account {
  id: number
  type: 'demo' | 'live'
  balance_cents: number
  leverage: number
  status: string
  broker?: {
    id: number
    name: string
  }
}

export const useAccountStore = defineStore('account', () => {
  const accounts = ref<Account[]>([])
  const selectedAccount = ref<Account | null>(null)
  const isLoading = ref(false)

  async function fetchAccounts(): Promise<void> {
    // TODO: implement in accounts phase — call GET /api/v1/accounts
    isLoading.value = true
    try {
      // Will update accounts from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchAccount(id: number): Promise<void> {
    // TODO: implement in accounts phase — call GET /api/v1/accounts/:id
    isLoading.value = true
    try {
      // Will update selectedAccount from response
    } finally {
      isLoading.value = false
    }
  }

  async function createAccount(data: {
    type: 'demo' | 'live'
    leverage: number
    broker_id?: number
  }): Promise<void> {
    // TODO: implement in accounts phase — call POST /api/v1/accounts
    isLoading.value = true
    try {
      // Will add new account to accounts array
    } finally {
      isLoading.value = false
    }
  }

  async function updateLeverage(id: number, leverage: number): Promise<void> {
    // TODO: implement in accounts phase — call PATCH /api/v1/accounts/:id/leverage
    isLoading.value = true
    try {
      // Will update leverage on matching account in accounts array
    } finally {
      isLoading.value = false
    }
  }

  async function deleteAccount(id: number): Promise<void> {
    // TODO: implement in accounts phase — call DELETE /api/v1/accounts/:id
    isLoading.value = true
    try {
      // Will remove account from accounts array
    } finally {
      isLoading.value = false
    }
  }

  return {
    accounts,
    selectedAccount,
    isLoading,
    fetchAccounts,
    fetchAccount,
    createAccount,
    updateLeverage,
    deleteAccount,
  }
})
