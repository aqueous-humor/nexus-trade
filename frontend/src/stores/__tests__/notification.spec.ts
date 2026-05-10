import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useNotificationStore } from '@/stores/notification'

describe('useNotificationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  describe('showToast', () => {
    it('adds a toast with correct message and variant', () => {
      const store = useNotificationStore()
      store.showToast('Hello world', 'success')

      expect(store.toasts).toHaveLength(1)
      expect(store.toasts[0]!.message).toBe('Hello world')
      expect(store.toasts[0]!.variant).toBe('success')
    })

    it('adds a toast with a unique id', () => {
      const store = useNotificationStore()
      store.showToast('First', 'info')
      store.showToast('Second', 'warning')

      expect(store.toasts).toHaveLength(2)
      expect(store.toasts[0]!.id).not.toBe(store.toasts[1]!.id)
    })

    it('defaults variant to info when not specified', () => {
      const store = useNotificationStore()
      store.showToast('Default variant')

      expect(store.toasts[0]!.variant).toBe('info')
    })

    it('auto-dismisses after 5 seconds', () => {
      const store = useNotificationStore()
      store.showToast('Auto dismiss', 'info')
      expect(store.toasts).toHaveLength(1)

      vi.advanceTimersByTime(5000)
      expect(store.toasts).toHaveLength(0)
    })
  })

  describe('dismissToast', () => {
    it('removes the correct toast by id', () => {
      const store = useNotificationStore()
      store.showToast('First', 'info')
      store.showToast('Second', 'success')

      const idToRemove = store.toasts[0]!.id
      store.dismissToast(idToRemove)

      expect(store.toasts).toHaveLength(1)
      expect(store.toasts[0]!.message).toBe('Second')
    })

    it('does nothing when id is unknown', () => {
      const store = useNotificationStore()
      store.showToast('Keep me', 'info')

      store.dismissToast('non-existent-id')

      expect(store.toasts).toHaveLength(1)
    })
  })
})
