<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  variant?: 'success' | 'danger' | 'warning' | 'info'
  dismissible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'info',
  dismissible: false,
})

const emit = defineEmits<{
  dismiss: []
}>()

const isDismissed = ref(false)

function dismiss() {
  isDismissed.value = true
  emit('dismiss')
}

const icons: Record<string, string> = {
  success: '✓',
  danger: '✕',
  warning: '⚠',
  info: 'ℹ',
}
</script>

<template>
  <div
    v-if="!isDismissed"
    class="alert"
    :class="`alert--${variant}`"
    role="alert"
    aria-live="polite"
  >
    <span class="alert__icon" aria-hidden="true">{{ icons[variant] }}</span>

    <div class="alert__content">
      <slot />
    </div>

    <button
      v-if="dismissible"
      class="alert__dismiss"
      aria-label="Dismiss alert"
      @click="dismiss"
    >
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>
    </button>
  </div>
</template>

<style lang="scss" scoped>
.alert {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  font-size: var(--text-sm);

  &__icon {
    flex-shrink: 0;
    font-size: var(--text-base);
    font-weight: 700;
    line-height: 1.4;
  }

  &__content {
    flex: 1;
    line-height: 1.5;
  }

  &__dismiss {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    opacity: 0.7;
    transition: opacity var(--transition-fast), background var(--transition-fast);
    color: inherit;

    &:hover {
      opacity: 1;
      background: rgba(0, 0, 0, 0.1);
    }

    &:focus-visible {
      outline: 2px solid currentColor;
      outline-offset: 2px;
    }
  }

  // Variants
  &--success {
    background: color-mix(in srgb, var(--color-success) 12%, transparent);
    border-color: color-mix(in srgb, var(--color-success) 30%, transparent);
    color: var(--color-success);
  }

  &--danger {
    background: color-mix(in srgb, var(--color-danger) 12%, transparent);
    border-color: color-mix(in srgb, var(--color-danger) 30%, transparent);
    color: var(--color-danger);
  }

  &--warning {
    background: color-mix(in srgb, var(--color-warning) 12%, transparent);
    border-color: color-mix(in srgb, var(--color-warning) 30%, transparent);
    color: var(--color-warning);
  }

  &--info {
    background: color-mix(in srgb, var(--color-secondary) 12%, transparent);
    border-color: color-mix(in srgb, var(--color-secondary) 30%, transparent);
    color: var(--color-secondary);
  }
}
</style>
