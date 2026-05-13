import { onMounted, onUnmounted } from 'vue'

/**
 * Watches for elements with [data-reveal] and adds `is-revealed` when they
 * enter the viewport.  Supports `data-delay="150"` (ms) for staggered groups.
 */
export function useScrollReveal(selector = '[data-reveal]') {
  let observer: IntersectionObserver | null = null

  onMounted(() => {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const el = entry.target as HTMLElement
            const delay = el.dataset.delay ? parseInt(el.dataset.delay) : 0
            setTimeout(() => el.classList.add('is-revealed'), delay)
            observer?.unobserve(el)
          }
        })
      },
      { threshold: 0.08, rootMargin: '0px 0px -40px 0px' },
    )

    document.querySelectorAll<HTMLElement>(selector).forEach((el) => {
      observer?.observe(el)
    })
  })

  onUnmounted(() => observer?.disconnect())
}

/**
 * Animates a numeric counter from 0 → target when the element enters view.
 * Usage: <span data-counter="48000" data-suffix="K+">0</span>
 */
export function useCounterAnimation(selector = '[data-counter]') {
  let observer: IntersectionObserver | null = null

  function animateCounter(el: HTMLElement) {
    const raw      = el.dataset.counter
    const target   = raw ? parseFloat(raw) : 0
    if (isNaN(target)) return
    const suffix   = el.dataset.suffix  ?? ''
    const prefix   = el.dataset.prefix  ?? ''
    const decimals = el.dataset.decimals ? parseInt(el.dataset.decimals) : 0
    const duration = 1800
    const start    = performance.now()

    function step(now: number) {
      const progress = Math.min((now - start) / duration, 1)
      const eased    = 1 - Math.pow(1 - progress, 3)
      const value    = (eased * target).toFixed(decimals)
      el.textContent = prefix + value + suffix
      if (progress < 1) requestAnimationFrame(step)
    }

    requestAnimationFrame(step)
  }

  onMounted(() => {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            animateCounter(entry.target as HTMLElement)
            observer?.unobserve(entry.target)
          }
        })
      },
      { threshold: 0.3 },
    )

    document.querySelectorAll<HTMLElement>(selector).forEach((el) => {
      observer?.observe(el)
    })
  })

  onUnmounted(() => observer?.disconnect())
}
