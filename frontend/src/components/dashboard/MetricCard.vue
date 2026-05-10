<script setup lang="ts">
interface Props {
  label: string
  value: string
  trend?: number
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false,
})
</script>

<template>
  <div class="metric-card" :class="{ 'metric-card--loading': loading }">
    <!-- Loading skeleton -->
    <template v-if="loading">
      <div class="skeleton skeleton--label" />
      <div class="skeleton skeleton--value" />
      <div class="skeleton skeleton--trend" />
    </template>

    <!-- Content -->
    <template v-else>
      <span class="metric-card__label">{{ label }}</span>
      <span class="metric-card__value">{{ value }}</span>
      <span
        v-if="trend !== undefined"
        class="metric-card__trend"
        :class="trend >= 0 ? 'metric-card__trend--up' : 'metric-card__trend--down'"
      >
        <span class="metric-card__trend-arrow">{{ trend >= 0 ? '▲' : '▼' }}</span>
        {{ trend >= 0 ? '+' : '' }}{{ trend.toFixed(2) }}%
      </span>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.metric-card {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);

  &__label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 500;
  }

  &__value {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--color-text);
    line-height: 1.2;
  }

  &__trend {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-sm);
    font-weight: 600;

    &--up {
      color: var(--color-success);
    }

    &--down {
      color: var(--color-danger);
    }
  }

  &__trend-arrow {
    font-size: var(--text-xs);
  }
}

// Shimmer animation
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.skeleton {
  border-radius: var(--radius-sm);
  background: linear-gradient(
    90deg,
    var(--color-surface-2) 25%,
    var(--color-border) 50%,
    var(--color-surface-2) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;

  &--label {
    height: 14px;
    width: 60%;
  }

  &--value {
    height: 28px;
    width: 80%;
    margin-top: var(--space-1);
  }

  &--trend {
    height: 14px;
    width: 40%;
  }
}
</style>
