import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface Toast {
  id: string
  message: string
  variant: 'success' | 'danger' | 'warning' | 'info'
}

export interface NotificationPreferences {
  email_investment_created: boolean
  email_investment_completed: boolean
  email_deposit_confirmed: boolean
  email_withdrawal_approved: boolean
  email_withdrawal_rejected: boolean
  email_account_locked: boolean
  email_account_suspended: boolean
  email_signal_deactivated: boolean
  [key: string]: boolean
}

export const useNotificationStore = defineStore('notification', () => {
  const toasts = ref<Toast[]>([])
  const preferences = ref<Partial<NotificationPreferences>>({})
  const isLoading = ref(false)

  function showToast(message: string, variant: Toast['variant'] = 'info'): void {
    const id = `toast-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
    toasts.value.push({ id, message, variant })

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      dismissToast(id)
    }, 5000)
  }

  function dismissToast(id: string): void {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  async function fetchPreferences(): Promise<void> {
    isLoading.value = true
    try {
      const { default: client } = await import('@/api/client')
      const res = await client.get<{ data: NotificationPreferences }>('/api/v1/notifications/preferences')
      preferences.value = res.data.data
    } catch {
      // Silently ignore — non-critical
    } finally {
      isLoading.value = false
    }
  }

  async function updatePreferences(data: Partial<NotificationPreferences>): Promise<void> {
    isLoading.value = true
    try {
      const { default: client } = await import('@/api/client')
      const res = await client.patch<{ data: NotificationPreferences }>('/api/v1/notifications/preferences', data)
      preferences.value = res.data.data
    } catch (e: unknown) {
      showToast('Failed to update notification preferences', 'danger')
      throw e
    } finally {
      isLoading.value = false
    }
  }

  return { toasts, preferences, isLoading, showToast, dismissToast, fetchPreferences, updatePreferences }
})
