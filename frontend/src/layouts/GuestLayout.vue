<script setup lang="ts">
// GuestLayout: full-screen centred card layout for unauthenticated pages
</script>

<template>
  <div class="guest-layout">
    <div class="guest-layout__card">
      <div class="guest-layout__brand">
        <span class="guest-layout__logo">NexusTrade</span>
      </div>
      <slot />
    </div>
  </div>
</template>

<style lang="scss" scoped>
.guest-layout {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: var(--color-bg);
  padding: var(--space-4);

  &__card {
    width: 100%;
    max-width: 420px;
    background: var(--color-surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: var(--space-8);
  }

  &__brand {
    text-align: center;
    margin-bottom: var(--space-6);
  }

  &__logo {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--color-primary);
    letter-spacing: -0.02em;
  }
}
</style>
