import axios from 'axios'
import type { AxiosInstance } from 'axios'
import router from '@/router'

const client: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  withCredentials: true, // Send cookies for Sanctum
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

// CSRF cookie handling (Sanctum requirement)
let csrfTokenFetched = false

client.interceptors.request.use(async (config) => {
  // Inject Bearer token when available (falls back to cookie auth)
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }

  // Fetch CSRF cookie before first mutating request
  if (
    !csrfTokenFetched &&
    ['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase() ?? '')
  ) {
    await axios.get(`${config.baseURL}/sanctum/csrf-cookie`, { withCredentials: true })
    csrfTokenFetched = true
  }
  return config
})

// ── Helper: extract a human-readable message from an Axios error ─────────────
function extractMessage(error: unknown): string {
  const e = error as { response?: { data?: { message?: string; error?: string } } }
  return (
    e?.response?.data?.message ??
    e?.response?.data?.error ??
    'An unexpected error occurred. Please try again.'
  )
}

// Response interceptors
client.interceptors.response.use(
  (response) => response,
  async (error) => {
    // Imported lazily inside the interceptor to avoid circular dependency:
    // client → stores/auth → api/auth → client
    const { useNotificationStore } = await import('@/stores/notification')
    const notificationStore = useNotificationStore()
    const status: number | undefined = error.response?.status

    // 401 Unauthorized — clear credentials and redirect to login
    if (status === 401) {
      localStorage.removeItem('auth_token')
      try {
        const { useAuthStore } = await import('@/stores/auth')
        const authStore = useAuthStore()
        authStore.user = null
      } catch {
        // Pinia may not be active during early boot or in test environments
      }
      const currentPath = router.currentRoute?.value?.path
      if (currentPath !== '/login' && currentPath !== '/') {
        router.push('/login')
      }
    }

    // 422 Unprocessable — validation errors (handled inline by forms; skip global toast)
    // 429 Too Many Requests → show toast
    else if (status === 429) {
      const retryAfter = error.response.headers['retry-after'] ?? '60'
      notificationStore.showToast(
        `Too many requests. Please wait ${retryAfter}s before trying again.`,
        'warning',
      )
    }

    // 403 Forbidden
    else if (status === 403) {
      notificationStore.showToast('You do not have permission to perform this action.', 'danger')
    }

    // 500+ Server errors — show server message when available
    else if (status !== undefined && status >= 500) {
      notificationStore.showToast(extractMessage(error), 'danger')
    }

    // Network / no response (CORS, offline, etc.)
    else if (!error.response) {
      notificationStore.showToast(
        'Unable to reach the server. Check your connection and try again.',
        'warning',
      )
    }

    return Promise.reject(error)
  },
)

export default client
