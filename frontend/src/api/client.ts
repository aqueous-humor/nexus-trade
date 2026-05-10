import axios from 'axios'
import type { AxiosInstance } from 'axios'
import router from '@/router'
import { useNotificationStore } from '@/stores/notification'

const client: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
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

// Response interceptors
client.interceptors.response.use(
  (response) => response,
  (error) => {
    const notificationStore = useNotificationStore()

    // 401 Unauthorized → redirect to login
    if (error.response?.status === 401) {
      router.push('/login')
    }

    // 429 Too Many Requests → show toast
    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after']
      notificationStore.showToast(
        `Too many requests. Please try again in ${retryAfter} seconds.`,
        'warning',
      )
    }

    return Promise.reject(error)
  },
)

export default client
