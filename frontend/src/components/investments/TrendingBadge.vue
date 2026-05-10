<script setup lang="ts">
// TrendingBadge — positioned absolute in top-right of parent card
</script>

<template>
  <span class="trending-badge" aria-label="Trending plan">
    🔥 Trending
  </span>
</template>

<style lang="scss" scoped>
.trending-badge {
  position: absolute;
  top: var(--space-3);
  right: var(--space-3);
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: 0.25rem var(--space-3);
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  white-space: nowrap;
  pointer-events: none;
  box-shadow: var(--shadow-sm);
  letter-spacing: 0.02em;
}
</style>
