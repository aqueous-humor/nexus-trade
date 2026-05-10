import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/auth', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    forgotPassword: vi.fn(),
    resetPassword: vi.fn(),
    resendVerification: vi.fn(),
  },
}))

vi.mock('@/router', () => ({
  default: {
    push: vi.fn(),
    beforeEach: vi.fn(),
  },
}))

vi.mock('@/api/client', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() },
    },
  },
}))

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('isAuthenticated', () => {
    it('returns false when user is null', () => {
      const store = useAuthStore()
      store.user = null
      expect(store.isAuthenticated).toBe(false)
    })

    it('returns true when user is set', () => {
      const store = useAuthStore()
      store.user = {
        id: 1,
        first_name: 'Test',
        last_name: 'User',
        email: 'test@example.com',
        role: 'user',
      }
      expect(store.isAuthenticated).toBe(true)
    })
  })

  describe('isAdmin', () => {
    it('returns false for user role', () => {
      const store = useAuthStore()
      store.user = {
        id: 1,
        first_name: 'Test',
        last_name: 'User',
        email: 'test@example.com',
        role: 'user',
      }
      expect(store.isAdmin).toBe(false)
    })

    it('returns true for admin role', () => {
      const store = useAuthStore()
      store.user = {
        id: 1,
        first_name: 'Admin',
        last_name: 'User',
        email: 'admin@example.com',
        role: 'admin',
      }
      expect(store.isAdmin).toBe(true)
    })

    it('returns false when user is null', () => {
      const store = useAuthStore()
      store.user = null
      expect(store.isAdmin).toBe(false)
    })
  })
})
