<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investment'
import PlanCard from '@/components/investments/PlanCard.vue'

const router = useRouter()
const investmentStore = useInvestmentStore()

// Extend the base InvestmentPlan type with trending fields used in this view
interface PlanWithTrending {
  id: number
  name: string
  description: string
  min_amount_cents: number
  max_amount_cents: number
  roi_percentage: number
  status: string
  is_trending?: boolean
  trending_image_url?: string
  trending_title?: string
  durations?: { id: number; label: string }[]
}

const plans = computed(() => investmentStore.plans as PlanWithTrending[])

const trendingPlans = computed(() => plans.value.filter((p) => p.is_trending))
const allPlans = computed(() => plans.value.filter((p) => p.status === 'active'))

function investInPlan(planId: number) {
  router.push({ path: '/app/invest', query: { planId: String(planId) } })
}

onMounted(() => {
  investmentStore.fetchPlans()
})
</script>

<template>
  <div class="plans-view">
    <h1 class="plans-view__title">Investment Plans</h1>

    <!-- Loading skeleton -->
    <template v-if="investmentStore.isLoading">
      <!-- Trending skeleton -->
      <div class="plans-view__trending-skeleton skeleton-banner" aria-busy="true" />

      <!-- Grid skeleton -->
      <div class="plans-view__grid">
        <div v-for="i in 6" :key="i" class="plan-skeleton">
          <div class="skeleton skeleton--badge" />
          <div class="skeleton skeleton--title" />
          <div class="skeleton skeleton--roi" />
          <div class="skeleton skeleton--line" />
          <div class="skeleton skeleton--btn" />
        </div>
      </div>
    </template>

    <template v-else-if="allPlans.length === 0">
      <!-- Empty state -->
      <div class="plans-view__empty">
        <p class="plans-view__empty-text">No investment plans are available at the moment.</p>
      </div>
    </template>

    <template v-else>
      <!-- Trending plans banner (horizontal scroll) -->
      <section v-if="trendingPlans.length > 0" class="plans-view__trending-section">
        <h2 class="plans-view__section-title">🔥 Trending Plans</h2>
        <div class="trending-scroll">
          <div
            v-for="plan in trendingPlans"
            :key="plan.id"
            class="trending-card"
            :style="plan.trending_image_url ? { backgroundImage: `url(${plan.trending_image_url})` } : {}"
          >
            <div class="trending-card__overlay">
              <h3 class="trending-card__title">{{ plan.trending_title || plan.name }}</h3>
              <p class="trending-card__description">{{ plan.description }}</p>
              <button
                class="trending-card__cta"
                type="button"
                @click="investInPlan(plan.id)"
              >
                Invest Now
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- All plans grid -->
      <section class="plans-view__all-section">
        <h2 class="plans-view__section-title">All Plans</h2>
        <div class="plans-view__grid">
          <PlanCard
            v-for="plan in allPlans"
            :key="plan.id"
            :plan="plan"
          />
        </div>
      </section>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.plans-view {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-8);

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__section-title {
    font-size: var(--text-xl);
    font-weight: 600;
    color: var(--color-text);
    margin: 0 0 var(--space-4);
  }

  &__trending-section,
  &__all-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__trending-skeleton {
    height: 200px;
    border-radius: var(--radius-lg);
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-4);

    @media (max-width: 1024px) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (max-width: 600px) {
      grid-template-columns: 1fr;
    }
  }

  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--space-16) var(--space-4);
    text-align: center;
  }

  &__empty-text {
    color: var(--color-text-muted);
    font-size: var(--text-base);
    margin: 0;
  }
}

// Trending horizontal scroll
.trending-scroll {
  display: flex;
  gap: var(--space-4);
  overflow-x: auto;
  padding-bottom: var(--space-2);
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;

  &::-webkit-scrollbar {
    height: 4px;
  }

  &::-webkit-scrollbar-track {
    background: var(--color-surface-2);
    border-radius: var(--radius-full);
  }

  &::-webkit-scrollbar-thumb {
    background: var(--color-border);
    border-radius: var(--radius-full);
  }
}

.trending-card {
  flex: 0 0 320px;
  min-height: 200px;
  border-radius: var(--radius-lg);
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  background-size: cover;
  background-position: center;
  position: relative;
  overflow: hidden;
  scroll-snap-align: start;

  &__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.75) 0%, rgba(0, 0, 0, 0.2) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: var(--space-6);
    gap: var(--space-2);
  }

  &__title {
    font-size: var(--text-xl);
    font-weight: 700;
    color: #fff;
    margin: 0;
  }

  &__description {
    font-size: var(--text-sm);
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__cta {
    align-self: flex-start;
    margin-top: var(--space-2);
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

    &:hover {
      background: var(--color-primary-dark);
    }

    &:focus-visible {
      outline: 2px solid #fff;
      outline-offset: 2px;
    }
  }
}

// Plan skeleton
.plan-skeleton {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.skeleton-banner {
  background: var(--color-surface-2);
  animation: shimmer 1.5s ease-in-out infinite;
}

.skeleton {
  background: var(--color-surface-2);
  border-radius: var(--radius-sm);
  animation: shimmer 1.5s ease-in-out infinite;

  &--badge {
    height: 1.25rem;
    width: 5rem;
    border-radius: var(--radius-full);
  }

  &--title {
    height: 1.5rem;
    width: 70%;
  }

  &--roi {
    height: 2.5rem;
    width: 50%;
  }

  &--line {
    height: 1rem;
    width: 100%;
  }

  &--btn {
    height: 2.5rem;
    width: 100%;
    border-radius: var(--radius-md);
    margin-top: auto;
  }
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>
