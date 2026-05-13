<script setup lang="ts">
import { useTheme } from '@/composables/useTheme'
import AppIcon from '@/components/AppIcon.vue'

const { theme, toggle } = useTheme()
</script>

<template>
  <button
    class="theme-toggle"
    :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    @click="toggle"
  >
    <Transition name="icon-swap" mode="out-in">
      <AppIcon v-if="theme === 'dark'" key="sun" name="sun" :size="16" />
      <AppIcon v-else key="moon" name="moon" :size="16" />
    </Transition>
  </button>
</template>

<style lang="scss" scoped>
.theme-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
  background: transparent;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-text-muted);
  transition: color var(--transition-fast), background var(--transition-fast),
    border-color var(--transition-fast);

  &:hover {
    color: var(--color-text);
    background: var(--color-surface-2);
    border-color: var(--color-primary);
  }

  &:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
  }
}

.icon-swap-enter-active,
.icon-swap-leave-active {
  transition: opacity 120ms ease, transform 120ms ease;
}

.icon-swap-enter-from { opacity: 0; transform: rotate(-30deg) scale(0.7); }
.icon-swap-leave-to   { opacity: 0; transform: rotate(30deg)  scale(0.7); }
</style>
