<script setup lang="ts">
import { useNotificationStore } from '@/stores/notification'
import SupportChat from '@/components/SupportChat.vue'

const notificationStore = useNotificationStore()
</script>

<template>
  <RouterView />
  <SupportChat />

  <!-- Global toast container -->
  <Teleport to="body">
    <div class="toast-container" aria-live="polite" aria-atomic="false">
      <TransitionGroup name="toast">
        <div
          v-for="toast in notificationStore.toasts"
          :key="toast.id"
          class="toast"
          :class="`toast--${toast.variant}`"
          role="alert"
        >
          <span class="toast__icon">
            <svg v-if="toast.variant === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
            <svg v-else-if="toast.variant === 'danger'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <svg v-else-if="toast.variant === 'warning'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </span>
          <p class="toast__message">{{ toast.message }}</p>
          <button
            class="toast__close"
            aria-label="Dismiss"
            @click="notificationStore.dismissToast(toast.id)"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style>
.toast-container {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  pointer-events: none;
  max-width: min(420px, calc(100vw - 2rem));
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  padding: 0.75rem 0.875rem;
  border-radius: 0.625rem;
  backdrop-filter: blur(16px);
  background: rgba(15, 22, 36, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  pointer-events: auto;
  min-width: 280px;

  &--success { border-color: rgba(0, 212, 170, 0.35); .toast__icon { color: #00D4AA; } }
  &--danger  { border-color: rgba(255, 77,  109, 0.35); .toast__icon { color: #FF4D6D; } }
  &--warning { border-color: rgba(255, 184,  0, 0.35);  .toast__icon { color: #FFB800; } }
  &--info    { border-color: rgba(108, 99,  255, 0.35); .toast__icon { color: #6C63FF; } }

  &__icon { flex-shrink: 0; margin-top: 1px; }

  &__message {
    flex: 1;
    font-size: 0.8125rem;
    line-height: 1.5;
    color: #E2E8F0;
    margin: 0;
  }

  &__close {
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.4);
    padding: 0;
    display: flex;
    align-items: center;
    transition: color 0.15s;

    &:hover { color: rgba(255, 255, 255, 0.8); }
  }
}

/* Transitions */
.toast-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from  { opacity: 0; transform: translateX(100%); }
.toast-leave-to   { opacity: 0; transform: translateX(100%); }
.toast-move       { transition: transform 0.3s ease; }
</style>
