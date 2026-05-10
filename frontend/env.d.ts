/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_PUSHER_APP_KEY: string
  readonly VITE_PUSHER_HOST: string
  readonly VITE_PUSHER_PORT: string
  readonly VITE_PUSHER_SCHEME: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

// Required by laravel-echo to locate the Pusher constructor at runtime
interface Window {
  Pusher: typeof import('pusher-js')
}
