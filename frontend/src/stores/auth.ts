import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '@/api/auth'
import type { RegisterPayload } from '@/api/auth'

export interface User {
  id: number
  first_name: string
  last_name: string
  email: string
  role: 'user' | 'admin'
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function login(email: string, password: string): Promise<void> {
    isLoading.value = true
    try {
      const response = await authApi.login({ email, password })
      user.value = response.data.data.user
    } finally {
      isLoading.value = false
    }
  }

  async function register(data: RegisterPayload): Promise<void> {
    isLoading.value = true
    try {
      await authApi.register(data)
    } finally {
      isLoading.value = false
    }
  }

  async function logout(): Promise<void> {
    isLoading.value = true
    try {
      await authApi.logout()
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMe(): Promise<void> {
    isLoading.value = true
    try {
      const response = await authApi.me()
      user.value = response.data.data
    } catch (error: unknown) {
      const axiosError = error as { response?: { status?: number } }
      if (axiosError?.response?.status === 401) {
        user.value = null
      } else {
        throw error
      }
    } finally {
      isLoading.value = false
    }
  }

  return { user, isLoading, isAuthenticated, isAdmin, login, register, logout, fetchMe }
})
