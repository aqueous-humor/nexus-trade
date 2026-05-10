import { describe, it, expect, beforeEach, vi } from 'vitest'
import { nextTick } from 'vue'
import { useTheme } from '@/composables/useTheme'

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: vi.fn((key: string) => store[key] ?? null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key]
    }),
    clear: vi.fn(() => {
      store = {}
    }),
  }
})()

Object.defineProperty(globalThis, 'localStorage', {
  value: localStorageMock,
  writable: true,
})

describe('useTheme', () => {
  beforeEach(() => {
    localStorageMock.setItem.mockClear()
    localStorageMock.getItem.mockClear()
  })

  it('sets data-theme attribute on document.documentElement', async () => {
    useTheme()
    await nextTick()
    expect(document.documentElement.getAttribute('data-theme')).toBeTruthy()
  })

  it('toggle switches between dark and light', () => {
    const { theme, toggle } = useTheme()
    const before = theme.value
    toggle()
    const after = theme.value
    expect(after).not.toBe(before)
    expect(['dark', 'light']).toContain(after)
  })

  it('toggle is reversible', () => {
    const { theme, toggle } = useTheme()
    const original = theme.value
    toggle()
    toggle()
    expect(theme.value).toBe(original)
  })

  it('persists theme to localStorage on toggle', async () => {
    const { theme, toggle } = useTheme()
    const currentTheme = theme.value
    localStorageMock.setItem.mockClear()

    toggle()
    await nextTick()

    const expectedTheme = currentTheme === 'dark' ? 'light' : 'dark'
    expect(localStorageMock.setItem).toHaveBeenCalledWith('nexustrade-theme', expectedTheme)
  })
})
