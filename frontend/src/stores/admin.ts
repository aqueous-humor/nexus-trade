import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface AdminUser {
  id: number
  first_name: string
  last_name: string
  email: string
  role: 'user' | 'admin'
  created_at: string
  [key: string]: unknown
}

export interface AdminAccount {
  id: number
  type: 'demo' | 'live'
  balance_cents: number
  leverage: number
  status: string
  user: { id: number; email: string }
  [key: string]: unknown
}

export interface AdminInvestment {
  id: number
  amount_cents: number
  profit_cents: number
  status: string
  user: { id: number; email: string }
  plan: { id: number; name: string }
  [key: string]: unknown
}

export interface AuditLog {
  id: number
  operation_type: string
  actor_type: string
  actor_id: number
  target_type: string
  target_id: number
  ip_address: string
  outcome: string
  created_at: string
  [key: string]: unknown
}

export const useAdminStore = defineStore('admin', () => {
  const users = ref<AdminUser[]>([])
  const accounts = ref<AdminAccount[]>([])
  const investments = ref<AdminInvestment[]>([])
  const auditLogs = ref<AuditLog[]>([])
  const isLoading = ref(false)

  async function fetchUsers(filters?: Record<string, unknown>): Promise<void> {
    // TODO: implement in admin phase — call GET /api/v1/admin/users
    isLoading.value = true
    try {
      // Will update users from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchAccounts(filters?: Record<string, unknown>): Promise<void> {
    // TODO: implement in admin phase — call GET /api/v1/admin/accounts
    isLoading.value = true
    try {
      // Will update accounts from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchInvestments(filters?: Record<string, unknown>): Promise<void> {
    // TODO: implement in admin phase — call GET /api/v1/admin/investments
    isLoading.value = true
    try {
      // Will update investments from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchAuditLogs(filters?: Record<string, unknown>): Promise<void> {
    // TODO: implement in admin phase — call GET /api/v1/admin/audit-logs
    isLoading.value = true
    try {
      // Will update auditLogs from response
    } finally {
      isLoading.value = false
    }
  }

  return {
    users,
    accounts,
    investments,
    auditLogs,
    isLoading,
    fetchUsers,
    fetchAccounts,
    fetchInvestments,
    fetchAuditLogs,
  }
})
