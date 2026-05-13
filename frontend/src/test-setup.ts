// Global test setup for Vitest + jsdom
import { beforeEach } from 'vitest'

// ── ResizeObserver stub (required by ApexCharts in jsdom) ─────────────────────
globalThis.ResizeObserver = class ResizeObserver {
  observe() {}
  unobserve() {}
  disconnect() {}
}

// ── IntersectionObserver stub (required by useScrollReveal) ──────────────────
globalThis.IntersectionObserver = class IntersectionObserver {
  readonly root = null
  readonly rootMargin = ''
  readonly thresholds: ReadonlyArray<number> = []
  observe() {}
  unobserve() {}
  disconnect() {}
  takeRecords(): IntersectionObserverEntry[] { return [] }
} as unknown as typeof IntersectionObserver

// ── localStorage stub (jsdom provides one but we ensure it's clear each test) ─
beforeEach(() => {
  localStorage.clear()
})
