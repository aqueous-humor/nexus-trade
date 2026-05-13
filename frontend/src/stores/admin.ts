import { ref } from 'vue'
import { defineStore } from 'pinia'
import { adminApi } from '@/api/admin'
import type { AdminMetrics } from '@/api/admin'

// ── Exported types ────────────────────────────────────────────────────────────

export type { AdminMetrics }

export interface AdminUser {
  id: number
  first_name: string
  last_name: string
  email: string
  role: 'user' | 'admin'
  status?: 'active' | 'locked'
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
  result?: string
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

export interface AdminPlan {
  id: number
  name: string
  description: string
  roi_percentage: number
  min_amount_cents: number
  max_amount_cents: number
  status: 'active' | 'inactive'
  trending: boolean
  image_url?: string
  trending_title?: string
  trending_description?: string
  duration_labels?: string
  [key: string]: unknown
}

export interface AdminBroker {
  id: number
  name: string
  platform_type: 'MT4' | 'MT5'
  default_leverage: number
  status: 'active' | 'inactive'
  credentials_json: string
  [key: string]: unknown
}

export interface AdminSignal {
  id: number
  name: string
  description: string
  provider_metadata: string
  status: 'active' | 'inactive'
  created_at: string
  [key: string]: unknown
}

export interface AdminDeposit {
  id: number
  user: { id: number; email: string }
  amount_cents: number
  currency: string
  status: 'pending' | 'confirmed' | 'rejected'
  payment_method: string
  reference: string
  created_at: string
  [key: string]: unknown
}

export interface AdminWithdrawal {
  id: number
  user: { id: number; email: string }
  amount_cents: number
  currency: string
  status: 'pending' | 'approved' | 'rejected'
  wallet_address: string
  created_at: string
  [key: string]: unknown
}

export interface FraudAssessment {
  id: number
  entity_type: string
  entity_id: number
  risk_score: number
  triggered_rules: string[]
  status: 'pending' | 'approved' | 'rejected'
  [key: string]: unknown
}

// ── Store ─────────────────────────────────────────────────────────────────────

export const useAdminStore = defineStore('admin', () => {
  // ── State ──────────────────────────────────────────────────────────────────
  const users             = ref<AdminUser[]>([])
  const accounts          = ref<AdminAccount[]>([])
  const investments       = ref<AdminInvestment[]>([])
  const auditLogs         = ref<AuditLog[]>([])
  const plans             = ref<AdminPlan[]>([])
  const brokers           = ref<AdminBroker[]>([])
  const signals           = ref<AdminSignal[]>([])
  const fraudAssessments  = ref<FraudAssessment[]>([])
  const deposits          = ref<AdminDeposit[]>([])
  const withdrawals       = ref<AdminWithdrawal[]>([])
  const metrics           = ref<AdminMetrics | null>(null)

  const isLoading = ref(false)
  const error     = ref<string | null>(null)
  const meta      = ref({ current_page: 1, last_page: 1, total: 0 })

  function setError(e: unknown) {
    error.value = (e as Error)?.message ?? 'An error occurred'
  }

  // ── Users ──────────────────────────────────────────────────────────────────
  async function fetchUsers(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res = await adminApi.users(filters)
      users.value = res.data.data
      meta.value  = res.data.meta
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function updateUser(id: number, data: Record<string, unknown>): Promise<AdminUser> {
    const res     = await adminApi.updateUser(id, data)
    const updated = res.data.data
    const idx     = users.value.findIndex((u) => u.id === id)
    if (idx !== -1) users.value[idx] = { ...users.value[idx], ...updated }
    return updated
  }

  // ── Accounts ───────────────────────────────────────────────────────────────
  async function fetchAccounts(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res = await adminApi.accounts(filters)
      accounts.value = res.data.data
      meta.value     = res.data.meta
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function createAccount(data: Record<string, unknown>): Promise<AdminAccount> {
    const res     = await adminApi.storeAccount(data)
    const created = res.data.data
    accounts.value.unshift(created)
    return created
  }

  async function updateAccountStatus(id: number, status: string): Promise<void> {
    await adminApi.updateAccountStatus(id, status)
    const idx = accounts.value.findIndex((a) => a.id === id)
    if (idx !== -1) accounts.value[idx] = { ...accounts.value[idx], status } as AdminAccount
  }

  async function reassignAccount(id: number, userId: number): Promise<void> {
    await adminApi.reassignAccount(id, userId)
    await fetchAccounts()
  }

  // ── Investments ────────────────────────────────────────────────────────────
  async function fetchInvestments(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res = await adminApi.investments(filters)
      investments.value = res.data.data
      meta.value        = res.data.meta
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function createInvestment(data: Record<string, unknown>): Promise<AdminInvestment> {
    const res     = await adminApi.storeInvestment(data)
    const created = res.data.data
    investments.value.unshift(created)
    return created
  }

  async function updateInvestmentStatus(id: number, status: string): Promise<void> {
    await adminApi.updateInvestmentStatus(id, status)
    const idx = investments.value.findIndex((inv) => inv.id === id)
    if (idx !== -1) investments.value[idx] = { ...investments.value[idx], status } as AdminInvestment
  }

  async function recordInvestmentResult(id: number, data: Record<string, unknown>): Promise<void> {
    await adminApi.recordResult(id, data)
    const idx = investments.value.findIndex((inv) => inv.id === id)
    if (idx !== -1) investments.value[idx] = { ...investments.value[idx], ...data } as AdminInvestment
  }

  async function adjustInvestmentProfit(id: number, data: Record<string, unknown>): Promise<void> {
    await adminApi.adjustProfit(id, data)
    await fetchInvestments()
  }

  async function recoverInvestment(id: number): Promise<void> {
    await adminApi.recoverInvestment(id)
    const idx = investments.value.findIndex((inv) => inv.id === id)
    if (idx !== -1) investments.value[idx] = { ...investments.value[idx], status: 'active' } as AdminInvestment
  }

  // ── Plans ──────────────────────────────────────────────────────────────────
  async function fetchPlans(): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res    = await adminApi.plans()
      plans.value  = (res.data as { data: AdminPlan[] }).data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function createPlan(data: Record<string, unknown>): Promise<AdminPlan> {
    const res     = await adminApi.storePlan(data)
    const created = (res.data as { data: AdminPlan }).data
    plans.value.unshift(created)
    return created
  }

  async function updatePlan(id: number, data: Record<string, unknown>): Promise<AdminPlan> {
    const res     = await adminApi.updatePlan(id, data)
    const updated = (res.data as { data: AdminPlan }).data
    const idx     = plans.value.findIndex((p) => p.id === id)
    if (idx !== -1) plans.value[idx] = updated
    return updated
  }

  async function deletePlan(id: number): Promise<void> {
    await adminApi.deletePlan(id)
    plans.value = plans.value.filter((p) => p.id !== id)
  }

  // ── Brokers ────────────────────────────────────────────────────────────────
  async function fetchBrokers(): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res      = await adminApi.brokers()
      brokers.value  = (res.data as { data: AdminBroker[] }).data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function createBroker(data: Record<string, unknown>): Promise<AdminBroker> {
    const res     = await adminApi.storeBroker(data)
    const created = (res.data as { data: AdminBroker }).data
    brokers.value.unshift(created)
    return created
  }

  async function updateBroker(id: number, data: Record<string, unknown>): Promise<AdminBroker> {
    const res     = await adminApi.updateBroker(id, data)
    const updated = (res.data as { data: AdminBroker }).data
    const idx     = brokers.value.findIndex((b) => b.id === id)
    if (idx !== -1) brokers.value[idx] = updated
    return updated
  }

  async function deleteBroker(id: number): Promise<void> {
    await adminApi.deleteBroker(id)
    brokers.value = brokers.value.filter((b) => b.id !== id)
  }

  // ── Signals ────────────────────────────────────────────────────────────────
  async function fetchSignals(): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res      = await adminApi.signals()
      signals.value  = (res.data as { data: AdminSignal[] }).data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function createSignal(data: Record<string, unknown>): Promise<AdminSignal> {
    const res     = await adminApi.storeSignal(data)
    const created = (res.data as { data: AdminSignal }).data
    signals.value.unshift(created)
    return created
  }

  async function updateSignal(id: number, data: Record<string, unknown>): Promise<AdminSignal> {
    const res     = await adminApi.updateSignal(id, data)
    const updated = (res.data as { data: AdminSignal }).data
    const idx     = signals.value.findIndex((s) => s.id === id)
    if (idx !== -1) signals.value[idx] = updated
    return updated
  }

  async function toggleSignalStatus(id: number, active: boolean): Promise<void> {
    if (active) await adminApi.activateSignal(id)
    else        await adminApi.deactivateSignal(id)
    const idx = signals.value.findIndex((s) => s.id === id)
    if (idx !== -1) signals.value[idx] = { ...signals.value[idx], status: active ? 'active' : 'inactive' } as AdminSignal
  }

  async function deleteSignal(id: number): Promise<void> {
    await adminApi.deleteSignal(id)
    signals.value = signals.value.filter((s) => s.id !== id)
  }

  // ── Deposits ───────────────────────────────────────────────────────────────
  async function fetchDeposits(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res     = await adminApi.deposits(filters)
      deposits.value = (res.data as { data: AdminDeposit[] }).data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function approveDeposit(id: number): Promise<void> {
    await adminApi.approveDeposit(id)
    const idx = deposits.value.findIndex((d) => d.id === id)
    if (idx !== -1) deposits.value[idx] = { ...deposits.value[idx], status: 'confirmed' } as AdminDeposit
  }

  async function rejectDeposit(id: number, reason: string): Promise<void> {
    await adminApi.rejectDeposit(id, reason)
    const idx = deposits.value.findIndex((d) => d.id === id)
    if (idx !== -1) deposits.value[idx] = { ...deposits.value[idx], status: 'rejected' } as AdminDeposit
  }

  // ── Withdrawals ────────────────────────────────────────────────────────────
  async function fetchWithdrawals(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res       = await adminApi.withdrawals(filters)
      withdrawals.value = (res.data as { data: AdminWithdrawal[] }).data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function approveWithdrawal(id: number): Promise<void> {
    await adminApi.approveWithdrawal(id)
    const idx = withdrawals.value.findIndex((w) => w.id === id)
    if (idx !== -1) withdrawals.value[idx] = { ...withdrawals.value[idx], status: 'approved' } as AdminWithdrawal
  }

  async function rejectWithdrawal(id: number, reason: string): Promise<void> {
    await adminApi.rejectWithdrawal(id, reason)
    const idx = withdrawals.value.findIndex((w) => w.id === id)
    if (idx !== -1) withdrawals.value[idx] = { ...withdrawals.value[idx], status: 'rejected' } as AdminWithdrawal
  }

  // ── Fraud ──────────────────────────────────────────────────────────────────
  async function fetchFraud(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res              = await adminApi.fraud(filters)
      fraudAssessments.value = (res.data as { data: FraudAssessment[] }).data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  async function approveFraudAssessment(id: number): Promise<void> {
    await adminApi.approveFraud(id)
    const idx = fraudAssessments.value.findIndex((a) => a.id === id)
    if (idx !== -1) fraudAssessments.value[idx] = { ...fraudAssessments.value[idx], status: 'approved' } as FraudAssessment
  }

  async function rejectFraudAssessment(id: number, reason: string): Promise<void> {
    await adminApi.rejectFraud(id, reason)
    const idx = fraudAssessments.value.findIndex((a) => a.id === id)
    if (idx !== -1) fraudAssessments.value[idx] = { ...fraudAssessments.value[idx], status: 'rejected' } as FraudAssessment
  }

  // ── Audit Logs ─────────────────────────────────────────────────────────────
  async function fetchAuditLogs(filters?: Record<string, unknown>): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res      = await adminApi.auditLogs(filters)
      auditLogs.value = res.data.data
      meta.value      = res.data.meta
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  // ── Metrics ────────────────────────────────────────────────────────────────
  async function fetchMetrics(): Promise<void> {
    isLoading.value = true; error.value = null
    try {
      const res     = await adminApi.metrics()
      metrics.value = res.data.data
    } catch (e) { setError(e) } finally { isLoading.value = false }
  }

  return {
    // State
    users, accounts, investments, auditLogs,
    plans, brokers, signals, fraudAssessments, deposits, withdrawals, metrics,
    isLoading, error, meta,
    // Users
    fetchUsers, updateUser,
    // Accounts
    fetchAccounts, createAccount, updateAccountStatus, reassignAccount,
    // Investments
    fetchInvestments, createInvestment, updateInvestmentStatus,
    recordInvestmentResult, adjustInvestmentProfit, recoverInvestment,
    // Plans
    fetchPlans, createPlan, updatePlan, deletePlan,
    // Brokers
    fetchBrokers, createBroker, updateBroker, deleteBroker,
    // Signals
    fetchSignals, createSignal, updateSignal, toggleSignalStatus, deleteSignal,
    // Deposits
    fetchDeposits, approveDeposit, rejectDeposit,
    // Withdrawals
    fetchWithdrawals, approveWithdrawal, rejectWithdrawal,
    // Fraud
    fetchFraud, approveFraudAssessment, rejectFraudAssessment,
    // Audit
    fetchAuditLogs,
    // Metrics
    fetchMetrics,
  }
})
