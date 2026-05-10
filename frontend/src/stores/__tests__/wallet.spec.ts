import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useWalletStore } from '@/stores/wallet'

describe('useWalletStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('initial state', () => {
    it('has balance of 0', () => {
      const store = useWalletStore()
      expect(store.balance).toBe(0)
    })

    it('has empty transactions array', () => {
      const store = useWalletStore()
      expect(store.transactions).toEqual([])
    })

    it('has USD as default currency', () => {
      const store = useWalletStore()
      expect(store.currency).toBe('USD')
    })

    it('has isLoading false', () => {
      const store = useWalletStore()
      expect(store.isLoading).toBe(false)
    })
  })

  describe('balance ref', () => {
    it('can be set directly (for WebSocket updates)', () => {
      const store = useWalletStore()
      store.balance = 50000
      expect(store.balance).toBe(50000)
    })

    it('can be updated multiple times', () => {
      const store = useWalletStore()
      store.balance = 10000
      store.balance = 20000
      expect(store.balance).toBe(20000)
    })
  })
})
