<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

function goHome() {
  router.push(authStore.isAuthenticated ? '/app' : '/')
}
</script>

<template>
  <div class="nf">
    <div class="nf__inner">
      <p class="nf__code">404</p>
      <h1 class="nf__title">Page not found</h1>
      <p class="nf__desc">The page you're looking for doesn't exist or has been moved.</p>
      <button class="nf__btn" @click="goHome">
        {{ authStore.isAuthenticated ? 'Back to Dashboard' : 'Back to Home' }}
      </button>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.nf {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg, #080C14);
  padding: var(--space-6, 1.5rem);

  &__inner {
    text-align: center;
    max-width: 420px;
  }

  &__code {
    font-size: clamp(5rem, 15vw, 9rem);
    font-weight: 900;
    background: linear-gradient(135deg, var(--color-primary, #00D4AA), var(--color-secondary, #6C63FF));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin: 0 0 0.5rem;
  }

  &__title {
    font-size: clamp(1.25rem, 4vw, 1.75rem);
    font-weight: 700;
    color: var(--color-text, #E2E8F0);
    margin: 0 0 0.75rem;
  }

  &__desc {
    font-size: 0.9375rem;
    color: var(--color-text-muted, #718096);
    margin: 0 0 2rem;
    line-height: 1.6;
  }

  &__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.75rem;
    border-radius: var(--radius-lg, 0.75rem);
    background: linear-gradient(135deg, var(--color-primary, #00D4AA), #00b894);
    border: none;
    color: #fff;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s, transform 0.15s;

    &:hover { opacity: 0.9; transform: translateY(-1px); }
    &:active { transform: translateY(0); }
  }
}
</style>
