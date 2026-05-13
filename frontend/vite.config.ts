/// <reference types="vitest" />
import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => ({
  plugins: [
    vue(),
    // DevTools only loaded in development — Vite excludes it from production dist
    ...(mode === 'development' ? [vueDevTools()] : []),
  ],

  envPrefix: 'VITE_',

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  server: {
    port: 5173,
    proxy: {
      '/api':          { target: 'http://localhost:8000', changeOrigin: true },
      '/sanctum':      { target: 'http://localhost:8000', changeOrigin: true },
      '/broadcasting': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },

  build: {
    target: 'es2020',
    sourcemap: mode !== 'production',
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-vue':    ['vue', 'vue-router', 'pinia'],
          'vendor-charts': ['apexcharts', 'vue3-apexcharts'],
          'vendor-echo':   ['laravel-echo', 'pusher-js'],
          'vendor-axios':  ['axios'],
        },
      },
    },
    chunkSizeWarningLimit: 600,
  },

  // Vitest configuration
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test-setup.ts'],
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html'],
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.spec.ts', 'src/test-setup.ts'],
    },
  },
}))
