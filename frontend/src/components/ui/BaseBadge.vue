<script setup lang="ts">
interface Props {
  variant?: 'success' | 'danger' | 'warning' | 'info' | 'neutral'
  size?: 'sm' | 'md'
}

withDefaults(defineProps<Props>(), {
  variant: 'neutral',
  size: 'md',
})
</script>

<template>
  <span
    class="badge"
    :class="[`badge--${variant}`, `badge--${size}`]"
  >
    <slot />
  </span>
</template>

<style lang="scss" scoped>
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  border-radius: var(--radius-full);
  white-space: nowrap;
  letter-spacing: 0.02em;

  // Sizes
  &--sm {
    padding: 0.125rem var(--space-2);
    font-size: var(--text-xs);
  }

  &--md {
    padding: 0.25rem var(--space-3);
    font-size: var(--text-xs);
  }

  // Variants
  &--success {
    background: color-mix(in srgb, var(--color-success) 15%, transparent);
    color: var(--color-success);
  }

  &--danger {
    background: color-mix(in srgb, var(--color-danger) 15%, transparent);
    color: var(--color-danger);
  }

  &--warning {
    background: color-mix(in srgb, var(--color-warning) 15%, transparent);
    color: var(--color-warning);
  }

  &--info {
    background: color-mix(in srgb, var(--color-secondary) 15%, transparent);
    color: var(--color-secondary);
  }

  &--neutral {
    background: var(--color-surface-2);
    color: var(--color-text-muted);
  }
}
</style>
