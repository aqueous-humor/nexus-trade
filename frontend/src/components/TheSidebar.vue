<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isOpen = ref(false)

function toggleSidebar() {
  isOpen.value = !isOpen.value
}

function closeSidebar() {
  isOpen.value = false
}

interface NavItem {
  label: string
  to: string
  icon: string
  adminOnly?: boolean
}

const navItems: NavItem[] = [
  { label: 'Dashboard', to: '/', icon: '⊞' },
  { label: 'Accounts', to: '/accounts', icon: '🏦' },
  { label: 'Plans', to: '/plans', icon: '📋' },
  { label: 'Investments', to: '/investments', icon: '📈' },
  { label: 'Wallet', to: '/wallet', icon: '💳' },
  { label: 'Signals', to: '/signals', icon: '📡' },
  { label: 'Admin', to: '/admin', icon: '⚙️', adminOnly: true },
]
</script>

<template>
  <!-- Mobile hamburger button -->
  <button
    class="sidebar-toggle"
    :aria-expanded="isOpen"
    aria-controls="sidebar"
    aria-label="Toggle navigation"
    @click="toggleSidebar"
  >
    <span class="sidebar-toggle__bar" aria-hidden="true" />
    <span class="sidebar-toggle__bar" aria-hidden="true" />
    <span class="sidebar-toggle__bar" aria-hidden="true" />
  </button>

  <!-- Backdrop for mobile -->
  <div
    v-if="isOpen"
    class="sidebar-backdrop"
    aria-hidden="true"
    @click="closeSidebar"
  />

  <nav
    id="sidebar"
    class="sidebar"
    :class="{ 'sidebar--open': isOpen }"
    aria-label="Main navigation"
  >
    <div class="sidebar__brand">
      <span class="sidebar__logo">NexusTrade</span>
    </div>

    <ul class="sidebar__nav" role="list">
      <li
        v-for="item in navItems"
        :key="item.to"
        v-show="!item.adminOnly || authStore.user?.role === 'admin'"
        class="sidebar__nav-item"
      >
        <RouterLink
          :to="item.to"
          class="sidebar__link"
          active-class="is-active"
          :exact="item.to === '/'"
          @click="closeSidebar"
        >
          <span class="sidebar__link-icon" aria-hidden="true">{{ item.icon }}</span>
          <span class="sidebar__link-label">{{ item.label }}</span>
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>

<style lang="scss" scoped>
.sidebar-toggle {
  display: none;
  position: fixed;
  top: calc((var(--navbar-height) - 2rem) / 2);
  left: var(--space-4);
  z-index: 300;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 4px;
  width: 2rem;
  height: 2rem;
  padding: var(--space-1);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  cursor: pointer;

  &:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
  }

  &__bar {
    display: block;
    width: 1rem;
    height: 2px;
    background: var(--color-text);
    border-radius: 1px;
    transition: transform var(--transition-fast), opacity var(--transition-fast);
  }

  @media (max-width: 767px) {
    display: flex;
  }
}

.sidebar-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 200;

  @media (max-width: 767px) {
    display: block;
  }
}

.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: var(--sidebar-width);
  height: 100vh;
  background: var(--color-surface);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  z-index: 250;
  overflow-y: auto;

  &__brand {
    display: flex;
    align-items: center;
    height: var(--navbar-height);
    padding: 0 var(--space-6);
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
  }

  &__logo {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--color-primary);
    letter-spacing: -0.02em;
  }

  &__nav {
    list-style: none;
    margin: 0;
    padding: var(--space-4) 0;
    flex: 1;
  }

  &__nav-item {
    margin: 0;
  }

  &__link {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-6);
    color: var(--color-text-muted);
    text-decoration: none;
    font-size: var(--text-sm);
    font-weight: 500;
    border-left: 3px solid transparent;
    transition:
      color var(--transition-fast),
      background var(--transition-fast),
      border-color var(--transition-fast);

    &:hover {
      color: var(--color-text);
      background: var(--color-surface-2);
    }

    &:focus-visible {
      outline: 2px solid var(--color-primary);
      outline-offset: -2px;
    }

    &.is-active {
      color: var(--color-primary);
      background: color-mix(in srgb, var(--color-primary) 8%, transparent);
      border-left-color: var(--color-primary);
    }
  }

  &__link-icon {
    font-size: var(--text-base);
    flex-shrink: 0;
    width: 1.25rem;
    text-align: center;
  }

  &__link-label {
    flex: 1;
  }

  // Mobile: hidden by default, slide in when open
  @media (max-width: 767px) {
    transform: translateX(-100%);
    transition: transform var(--transition-base);

    &--open {
      transform: translateX(0);
    }
  }
}
</style>
