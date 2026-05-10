<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  type?: 'button' | 'submit' | 'reset'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
})
</script>

<template>
  <button
    class="btn"
    :class="[`btn--${variant}`, `btn--${size}`, { 'btn--loading': loading }]"
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading"
    :aria-disabled="disabled || loading"
  >
    <span v-if="loading" class="btn__spinner" aria-hidden="true">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <circle
          cx="8"
          cy="8"
          r="6"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-dasharray="28"
          stroke-dashoffset="10"
        />
      </svg>
    </span>
    <span class="btn__content" :class="{ 'btn__content--hidden': loading }">
      <slot />
    </span>
    <span v-if="loading" class="sr-only">Loading…</span>
  </button>
</template>

<style lang="scss" scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  font-family: var(--font-sans);
  font-weight: 500;
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
  transition:
    background var(--transition-fast),
    border-color var(--transition-fast),
    color var(--transition-fast),
    box-shadow var(--transition-fast),
    opacity var(--transition-fast);
  position: relative;

  &:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
  }

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }

  // Sizes
  &--sm {
    padding: var(--space-1) var(--space-3);
    font-size: var(--text-sm);
    height: 2rem;
  }

  &--md {
    padding: var(--space-2) var(--space-4);
    font-size: var(--text-sm);
    height: 2.5rem;
  }

  &--lg {
    padding: var(--space-3) var(--space-6);
    font-size: var(--text-base);
    height: 3rem;
  }

  // Variants
  &--primary {
    background: var(--color-primary);
    color: #0B0F1A;
    border-color: var(--color-primary);

    &:hover:not(:disabled) {
      background: var(--color-primary-dark);
      border-color: var(--color-primary-dark);
    }

    &:active:not(:disabled) {
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 30%, transparent);
    }
  }

  &--secondary {
    background: var(--color-surface-2);
    color: var(--color-text);
    border-color: var(--color-border);

    &:hover:not(:disabled) {
      background: var(--color-border);
    }

    &:active:not(:disabled) {
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-text) 10%, transparent);
    }
  }

  &--danger {
    background: var(--color-danger);
    color: #fff;
    border-color: var(--color-danger);

    &:hover:not(:disabled) {
      background: color-mix(in srgb, var(--color-danger) 85%, #000);
      border-color: color-mix(in srgb, var(--color-danger) 85%, #000);
    }

    &:active:not(:disabled) {
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-danger) 30%, transparent);
    }
  }

  &--ghost {
    background: transparent;
    color: var(--color-text);
    border-color: transparent;

    &:hover:not(:disabled) {
      background: var(--color-surface-2);
    }

    &:active:not(:disabled) {
      background: var(--color-border);
    }
  }

  // Loading state
  &--loading {
    cursor: wait;
  }

  &__spinner {
    display: inline-flex;
    animation: spin 0.75s linear infinite;
    flex-shrink: 0;
  }

  &__content {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);

    &--hidden {
      visibility: hidden;
      position: absolute;
    }
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>
