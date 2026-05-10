import { ref, onUnmounted } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import { useInvestmentStore } from '@/stores/investment'

export function usePolling(intervalMs = 30_000) {
  const isPolling = ref(false)
  let timerId: ReturnType<typeof setInterval> | null = null

  const walletStore = useWalletStore()
  const investmentStore = useInvestmentStore()

  async function poll() {
    try {
      await Promise.all([
        walletStore.fetchBalance(),
        investmentStore.fetchInvestments(),
      ])
    } catch {
      // Silently ignore polling errors to avoid disrupting the UI
    }
  }

  function start() {
    if (isPolling.value) return
    isPolling.value = true
    timerId = setInterval(poll, intervalMs)
  }

  function stop() {
    if (timerId !== null) {
      clearInterval(timerId)
      timerId = null
    }
    isPolling.value = false
  }

  onUnmounted(stop)

  return { isPolling, start, stop, poll }
}
