import client from './client'
import type { AdminUser, AdminAccount, AdminInvestment, AuditLog } from '@/stores/admin'

interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number }
}

export const adminApi = {
  // Users
  users: (params?: Record<string, unknown>) =>
    client.get<Paginated<AdminUser>>('/api/v1/admin/users', { params }),
  user: (id: number) =>
    client.get<{ data: AdminUser }>(`/api/v1/admin/users/${id}`),
  updateUser: (id: number, data: Record<string, unknown>) =>
    client.patch<{ data: AdminUser }>(`/api/v1/admin/users/${id}`, data),

  // Accounts
  accounts: (params?: Record<string, unknown>) =>
    client.get<Paginated<AdminAccount>>('/api/v1/admin/accounts', { params }),
  storeAccount: (data: Record<string, unknown>) =>
    client.post<{ data: AdminAccount }>('/api/v1/admin/accounts', data),
  updateAccount: (id: number, data: Record<string, unknown>) =>
    client.patch<{ data: AdminAccount }>(`/api/v1/admin/accounts/${id}`, data),
  updateAccountStatus: (id: number, status: string) =>
    client.patch(`/api/v1/admin/accounts/${id}/status`, { status }),
  reassignAccount: (id: number, userId: number) =>
    client.patch(`/api/v1/admin/accounts/${id}/reassign`, { user_id: userId }),
  deleteAccount: (id: number) =>
    client.delete(`/api/v1/admin/accounts/${id}`),

  // Investments
  investments: (params?: Record<string, unknown>) =>
    client.get<Paginated<AdminInvestment>>('/api/v1/admin/investments', { params }),
  storeInvestment: (data: Record<string, unknown>) =>
    client.post<{ data: AdminInvestment }>('/api/v1/admin/investments', data),
  updateInvestmentStatus: (id: number, status: string) =>
    client.patch(`/api/v1/admin/investments/${id}/status`, { status }),
  recordResult: (id: number, data: Record<string, unknown>) =>
    client.patch(`/api/v1/admin/investments/${id}/result`, data),
  adjustProfit: (id: number, data: Record<string, unknown>) =>
    client.patch(`/api/v1/admin/investments/${id}/profit`, data),
  recoverInvestment: (id: number) =>
    client.post(`/api/v1/admin/investments/${id}/recover`),

  // Plans
  plans: () =>
    client.get('/api/v1/admin/plans'),
  storePlan: (data: Record<string, unknown>) =>
    client.post('/api/v1/admin/plans', data),
  updatePlan: (id: number, data: Record<string, unknown>) =>
    client.patch(`/api/v1/admin/plans/${id}`, data),
  deletePlan: (id: number) =>
    client.delete(`/api/v1/admin/plans/${id}`),
  storeDuration: (data: Record<string, unknown>) =>
    client.post('/api/v1/admin/durations', data),
  attachDuration: (planId: number, durationId: number) =>
    client.post(`/api/v1/admin/plans/${planId}/durations`, { duration_id: durationId }),
  detachDuration: (planId: number, durationId: number) =>
    client.delete(`/api/v1/admin/plans/${planId}/durations/${durationId}`),

  // Brokers
  brokers: () =>
    client.get('/api/v1/admin/brokers'),
  storeBroker: (data: Record<string, unknown>) =>
    client.post('/api/v1/admin/brokers', data),
  updateBroker: (id: number, data: Record<string, unknown>) =>
    client.patch(`/api/v1/admin/brokers/${id}`, data),
  deleteBroker: (id: number) =>
    client.delete(`/api/v1/admin/brokers/${id}`),

  // Signals
  signals: () =>
    client.get('/api/v1/admin/signals'),
  storeSignal: (data: Record<string, unknown>) =>
    client.post('/api/v1/admin/signals', data),
  updateSignal: (id: number, data: Record<string, unknown>) =>
    client.patch(`/api/v1/admin/signals/${id}`, data),
  activateSignal: (id: number) =>
    client.post(`/api/v1/admin/signals/${id}/activate`),
  deactivateSignal: (id: number) =>
    client.post(`/api/v1/admin/signals/${id}/deactivate`),
  deleteSignal: (id: number) =>
    client.delete(`/api/v1/admin/signals/${id}`),

  // Fraud
  fraud: (params?: Record<string, unknown>) =>
    client.get('/api/v1/admin/fraud', { params }),
  approveFraud: (id: number) =>
    client.post(`/api/v1/admin/fraud/${id}/approve`),
  rejectFraud: (id: number, reason: string) =>
    client.post(`/api/v1/admin/fraud/${id}/reject`, { reason }),

  // Audit logs
  auditLogs: (params?: Record<string, unknown>) =>
    client.get<Paginated<AuditLog>>('/api/v1/admin/audit-logs', { params }),

  // Notification preferences
  notificationPreferences: () =>
    client.get('/api/v1/notifications/preferences'),
  updateNotificationPreferences: (data: Record<string, unknown>) =>
    client.patch('/api/v1/notifications/preferences', data),

  // Terms
  terms: () =>
    client.get('/api/v1/admin/terms'),
  storeTerm: (data: Record<string, unknown>) =>
    client.post('/api/v1/admin/terms', data),
  updateTerm: (id: number, data: Record<string, unknown>) =>
    client.patch(`/api/v1/admin/terms/${id}`, data),

  // Metrics
  metrics: () =>
    client.get<{ data: AdminMetrics }>('/api/v1/admin/metrics'),

  // Deposits / Withdrawals (admin review)
  deposits: (params?: Record<string, unknown>) =>
    client.get('/api/v1/admin/deposits', { params }),
  approveDeposit: (id: number) =>
    client.post(`/api/v1/admin/deposits/${id}/approve`),
  rejectDeposit: (id: number, reason: string) =>
    client.post(`/api/v1/admin/deposits/${id}/reject`, { reason }),
  withdrawals: (params?: Record<string, unknown>) =>
    client.get('/api/v1/admin/withdrawals', { params }),
  approveWithdrawal: (id: number) =>
    client.post(`/api/v1/admin/withdrawals/${id}/approve`),
  rejectWithdrawal: (id: number, reason: string) =>
    client.post(`/api/v1/admin/withdrawals/${id}/reject`, { reason }),
}

export interface AdminMetrics {
  total_investments: number
  total_value_cents: number
  active_users: number
  total_profit_paid_cents: number
  active_users_last_24h: number
  active_users_last_7d: number
  active_users_last_30d: number
  investment_growth: { date: string; value: number }[]
  top_plans: { name: string; total_invested: string; count: number }[]
}
