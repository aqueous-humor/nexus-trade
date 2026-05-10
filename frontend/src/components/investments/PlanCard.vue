<script setup lang="ts">
import { useRouter } from 'vue-router'
import TrendingBadge from './TrendingBadge.vue'

interface Duration {
  id: number
  label: string
}

interface Plan {
  id: number
  name: string
  description: string
  min_amount_cents: number
  max_amount_cents: number
  roi_percentage: number
  is_trending?: boolean
  trending_image_url?: string
  trending_title?: string
  durations?: Duration[]
}

interface Props {
  plan: Plan
}

const props = defineProps<Props>()
const router = useRouter()

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(cents / 100)
}

function invest() {
  router.push('/plans/' + props.plan.id)
}
</script>

<template>
  <div class="plan-card">
    <!-- Trending badge overlay -->
    <TrendingBadge v-if="plan.is_trending" />

    <!-- Plan name -->
    <h3 class="plan-card__name">{{ plan.name }}</h3>

    <!-- Description (truncated to 2 lines) -->
    <p class="plan-card__description">{{ plan.description }}</p>

    <!-- ROI -->
    <div class="plan-card__roi">
      {{ plan.roi_percentage }}% <span class="plan-card__roi-label">ROI</span>
    </div>

    <!-- Min / Max amount -->
    <div class="plan-card__range">
      {{ formatUSD(plan.min_amount_cents) }} – {{ formatUSD(plan.max_amount_cents) }}
    </div>

    <!-- Duration chips -->
    <div v-if="plan.durations && plan.durations.length > 0" class="plan-card__durations">
      <span
        v-for="duration in plan.durations"
        :key="duration.id"
        class="plan-card__duration-chip"
      >
        {{ duration.label }}
      </span>
    </div>

    <!-- CTA -->
    <button class="plan-card__cta" type="button" @click="invest">
      Invest Now
    </button>
  </div>
</template>

<style lang="scss" scoped>
.plan-card {
  position: relative; // required for TrendingBadge absolute positioning
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);

  &:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }

  &__name {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    padding-right: var(--space-12); // leave room for trending badge
  }

  &__description {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
    // Truncate to 2 lines
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
  }

  &__roi {
    font-size: var(--text-3xl);
    font-weight: 800;
    color: var(--color-primary);
    line-height: 1;
  }

  &__roi-label {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--color-text-muted);
  }

  &__range {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 500;
  }

  &__durations {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-top: var(--space-1);
  }

  &__duration-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem var(--space-2);
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 500;
    color: var(--color-text-muted);
    white-space: nowrap;
  }

  &__cta {
    margin-top: auto;
    padding: var(--space-2) var(--space-4);
    background: var(--color-primary);
    color: #0B0F1A;
    border: none;
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition-fast);
    text-align: center;

    &:hover {
      background: var(--color-primary-dark);
    }

    &:focus-visible {
      outline: 2px solid var(--color-primary);
      outline-offset: 2px;
    }
  }
}
</style>
