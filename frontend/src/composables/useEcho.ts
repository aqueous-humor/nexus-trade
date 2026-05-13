import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { ref } from 'vue'

// Extend Window type so TypeScript accepts window.Pusher
declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

// laravel-echo v2 types can be noisy — use a permissive local alias
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyEcho = Echo<any>

// Singleton Echo instance — created lazily so missing env vars don't crash on import
let echoInstance: AnyEcho | null = null

// Shared reactive connection flag — one ref so all composable calls share the same value
const isConnected = ref(false)
let listenersAttached = false

function createEcho(): AnyEcho {
  window.Pusher = Pusher
  return new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY ?? '',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? 'localhost',
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 6001),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 6001),
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Authorization: `Bearer ${localStorage.getItem('auth_token') ?? ''}`,
      },
    },
  })
}

function getEcho(): AnyEcho | null {
  // No-op when Pusher key is not configured (dev without WebSocket)
  if (!import.meta.env.VITE_PUSHER_APP_KEY) return null

  if (!echoInstance) {
    echoInstance = createEcho()
  }

  // Attach connection state listeners exactly once per singleton
  if (!listenersAttached) {
    listenersAttached = true
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const conn = (echoInstance.connector as any)?.pusher?.connection
    if (conn) {
      conn.bind('connected', () => { isConnected.value = true })
      conn.bind('disconnected', () => { isConnected.value = false })
      conn.bind('failed', () => { isConnected.value = false })
    }
  }

  return echoInstance
}

export function useEcho() {
  function subscribeToUserChannel(
    userId: number,
    callbacks: {
      onWalletUpdated?: (data: unknown) => void
      onInvestmentStatusChanged?: (data: unknown) => void
      onNotificationReceived?: (data: unknown) => void
    },
  ): () => void {
    const echo = getEcho()
    if (!echo) return () => {}

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const channel: any = echo.private(`user.${userId}`)
    if (callbacks.onWalletUpdated) channel.listen('.wallet.updated', callbacks.onWalletUpdated)
    if (callbacks.onInvestmentStatusChanged) channel.listen('.investment.status.changed', callbacks.onInvestmentStatusChanged)
    if (callbacks.onNotificationReceived) channel.listen('.notification.received', callbacks.onNotificationReceived)

    return () => echo.leave(`user.${userId}`)
  }

  function subscribeToAdminChannel(callbacks: {
    onFraudAlertRaised?: (data: unknown) => void
  }): () => void {
    const echo = getEcho()
    if (!echo) return () => {}

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const channel: any = echo.private('admin')
    if (callbacks.onFraudAlertRaised) channel.listen('.fraud.alert.raised', callbacks.onFraudAlertRaised)

    return () => echo.leave('admin')
  }

  function disconnect(): void {
    echoInstance?.disconnect()
    echoInstance = null
    listenersAttached = false
    isConnected.value = false
  }

  return {
    isConnected,
    subscribeToUserChannel,
    subscribeToAdminChannel,
    disconnect,
  }
}
