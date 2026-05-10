import { describe, it, expect, beforeEach, vi } from 'vitest'
import axios from 'axios'

// Mock router
vi.mock('@/router', () => ({
  default: {
    push: vi.fn(),
  },
}))

// Mock notification store
const mockShowToast = vi.fn()
vi.mock('@/stores/notification', () => ({
  useNotificationStore: vi.fn(() => ({
    showToast: mockShowToast,
  })),
}))

describe('axios client interceptors', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.resetModules()
  })

  it('401 response triggers router.push to /login', async () => {
    const { default: router } = await import('@/router')
    const { default: client } = await import('@/api/client')

    // Simulate a 401 error response
    const error = {
      response: { status: 401, headers: {} },
      config: {},
      isAxiosError: true,
    }

    // Access the response interceptor error handler
    const interceptors = (client.interceptors.response as unknown as {
      handlers: Array<{ fulfilled: unknown; rejected: (e: unknown) => unknown }>
    }).handlers

    const handler = interceptors[interceptors.length - 1]
    if (handler) {
      try {
        await handler.rejected(error)
      } catch {
        // expected rejection
      }
    }

    expect(router.push).toHaveBeenCalledWith('/login')
  })

  it('429 response calls notificationStore.showToast with warning variant', async () => {
    const { default: client } = await import('@/api/client')

    const error = {
      response: {
        status: 429,
        headers: { 'retry-after': '60' },
      },
      config: {},
      isAxiosError: true,
    }

    const interceptors = (client.interceptors.response as unknown as {
      handlers: Array<{ fulfilled: unknown; rejected: (e: unknown) => unknown }>
    }).handlers

    const handler = interceptors[interceptors.length - 1]
    if (handler) {
      try {
        await handler.rejected(error)
      } catch {
        // expected rejection
      }
    }

    expect(mockShowToast).toHaveBeenCalledWith(
      expect.stringContaining('Too many requests'),
      'warning',
    )
  })
})
