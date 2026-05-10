import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// Mock stores before importing composable
vi.mock('@/stores/wallet', () => ({
  useWalletStore: vi.fn(() => ({
    fetchBalance: vi.fn().mockResolvedValue(undefined),
  })),
}))

vi.mock('@/stores/investment', () => ({
  useInvestmentStore: vi.fn(() => ({
    fetchInvestments: vi.fn().mockResolvedValue(undefined),
  })),
}))

describe('usePolling', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.resetModules()
  })

  it('start() sets isPolling to true', async () => {
    const { usePolling } = await import('@/composables/usePolling')
    const { isPolling, start, stop } = usePolling(1000)

    expect(isPolling.value).toBe(false)
    start()
    expect(isPolling.value).toBe(true)
    stop()
  })

  it('stop() sets isPolling to false', async () => {
    const { usePolling } = await import('@/composables/usePolling')
    const { isPolling, start, stop } = usePolling(1000)

    start()
    expect(isPolling.value).toBe(true)
    stop()
    expect(isPolling.value).toBe(false)
  })

  it('start() is idempotent — calling twice does not create two intervals', async () => {
    const { usePolling } = await import('@/composables/usePolling')
    const { isPolling, start, stop } = usePolling(1000)

    start()
    start() // second call should be a no-op
    expect(isPolling.value).toBe(true)

    stop()
    expect(isPolling.value).toBe(false)
  })

  it('stop() clears the interval', async () => {
    const clearIntervalSpy = vi.spyOn(globalThis, 'clearInterval')
    const { usePolling } = await import('@/composables/usePolling')
    const { start, stop } = usePolling(1000)

    start()
    stop()
    expect(clearIntervalSpy).toHaveBeenCalled()
  })
})
