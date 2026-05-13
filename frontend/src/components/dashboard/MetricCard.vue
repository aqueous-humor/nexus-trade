<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'

interface Props {
  label: string
  value: string
  icon?: string
  trend?: number
  loading?: boolean
  accent?: 'teal' | 'purple' | 'green' | 'red' | 'amber'
}

withDefaults(defineProps<Props>(), {
  loading: false,
  accent: 'teal',
})
</script>

<template>
  <div class="metric-card" :class="[`metric-card--${accent}`, { 'metric-card--loading': loading }]">
    <!-- Loading skeleton -->
    <template v-if="loading">
      <div class="metric-card__top skeleton-row">
        <div class="skeleton skeleton--icon" />
      </div>
      <div class="skeleton skeleton--label" />
      <div class="skeleton skeleton--value" />
      <div class="skeleton skeleton--trend" />
    </template>

    <!-- Content -->
    <template v-else>
      <div class="metric-card__top">
        <div v-if="icon" class="metric-card__icon-wrap">
          <AppIcon :name="icon" :size="18" />
        </div>
        <span
          v-if="trend !== undefined"
          class="metric-card__trend-badge"
          :class="trend >= 0 ? 'metric-card__trend-badge--up' : 'metric-card__trend-badge--down'"
        >
          <AppIcon :name="trend >= 0 ? 'trending-up' : 'trending-down'" :size="12" />
          {{ trend >= 0 ? '+' : '' }}{{ trend.toFixed(1) }}%
        </span>
      </div>

      <div class="metric-card__body">
        <span class="metric-card__value">{{ value }}</span>
        <span class="metric-card__label">{{ label }}</span>
      </div>
    </template>
  </div>
</template>

<style lang="scss" scoped>
// Accent colour maps
$accents: (
  'teal':   var(--color-primary),
  'purple': var(--color-secondary),
  'green':  var(--color-success),
  'red':    var(--color-danger),
  'amber':  var(--color-warning),
);

.metric-card {
  position: relative;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-sm);
  padding: var(--space-5) var(--space-5) var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  overflow: hidden;
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);

  &:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
  }

  // Top accent strip
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    background: var(--color-primary);
  }

  @each $name, $color in $accents {
    &--#{$name}::before { background: #{$color}; }
    &--#{$name} .metric-card__icon-wrap { background: color-mix(in srgb, #{$color} 12%, transparent); color: #{$color}; }
  }

  // ── Top row ───────────────────────────────────────────────────────────────
  &__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 2rem;
  }

  &__icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: var(--radius-md);
    flex-shrink: 0;
  }

  &__trend-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 700;
    letter-spacing: -0.01em;

    &--up {
      background: color-mix(in srgb, var(--color-success) 12%, transparent);
      color: var(--color-success);
    }

    &--down {
      background: color-mix(in srgb, var(--color-danger) 12%, transparent);
      color: var(--color-danger);
    }
  }

  // ── Body ──────────────────────────────────────────────────────────────────
  &__body {
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  &__value {
    font-size: var(--text-2xl);
    font-weight: 800;
    color: var(--color-text);
    line-height: 1.15;
    letter-spacing: -0.03em;
  }

  &__label {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
}

// ── Shimmer skeleton ──────────────────────────────────────────────────────────
@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position:  200% 0; }
}

.skeleton-row {
  display: flex;
  gap: var(--space-2);
}

.skeleton {
  border-radius: var(--radius-sm);
  background: linear-gradient(
    90deg,
    var(--color-surface-2) 25%,
    var(--color-border)    50%,
    var(--color-surface-2) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.6s infinite;

  &--icon   { width: 2.25rem; height: 2.25rem; border-radius: var(--radius-md); }
  &--label  { height: 11px; width: 55%; border-radius: var(--radius-sm); }
  &--value  { height: 30px; width: 75%; margin-top: 4px; border-radius: var(--radius-sm); }
  &--trend  { height: 20px; width: 40%; border-radius: var(--radius-full); }
}
</style>
