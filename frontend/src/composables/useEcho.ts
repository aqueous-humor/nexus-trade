import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { ref } from 'vue'

// Make Pusher available globally (required by laravel-echo)
window.Pusher = Pusher

let echoInstance: Echo | null = null

function createEcho(): Echo {
  return new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
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
      },
    },
  })
}

export function useEcho() {
  const isConnected = ref(false)

  if (!echoInstance) {
    echoInstance = createEcho()
  }

  echoInstance.connector.pusher.connection.bind('connected', () => {
    isConnected.value = true
  })
  echoInstance.connector.pusher.connection.bind('disconnected', () => {
    isConnected.value = false
  })

  function subscribeToUserChannel(
    userId: number,
    callbacks: {
      onWalletUpdated?: (data: unknown) => void
      onInvestmentStatusChanged?: (data: unknown) => void
      onNotificationReceived?: (data: unknown) => void
    },
  ) {
    const channel = echoInstance!.private(`user.${userId}`)

    if (callbacks.onWalletUpdated) {
      channel.listen('.wallet.updated', callbacks.onWalletUpdated)
    }
    if (callbacks.onInvestmentStatusChanged) {
      channel.listen('.investment.status.changed', callbacks.onInvestmentStatusChanged)
    }
    if (callbacks.onNotificationReceived) {
      channel.listen('.notification.received', callbacks.onNotificationReceived)
    }

    return () => echoInstance!.leave(`user.${userId}`)
  }

  function subscribeToAdminChannel(callbacks: { onFraudAlertRaised?: (data: unknown) => void }) {
    const channel = echoInstance!.private('admin')

    if (callbacks.onFraudAlertRaised) {
      channel.listen('.fraud.alert.raised', callbacks.onFraudAlertRaised)
    }

    return () => echoInstance!.leave('admin')
  }

  function disconnect() {
    echoInstance?.disconnect()
    echoInstance = null
    isConnected.value = false
  }

  return {
    echo: echoInstance,
    isConnected,
    subscribeToUserChannel,
    subscribeToAdminChannel,
    disconnect,
  }
}
