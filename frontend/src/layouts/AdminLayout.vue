<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const navItems = [
  { to: '/app/admin',            label: 'Overview',    icon: '⊞' },
  { to: '/app/admin/users',      label: 'Users',       icon: '👥' },
  { to: '/app/admin/accounts',   label: 'Accounts',    icon: '💼' },
  { to: '/app/admin/investments',label: 'Investments', icon: '📈' },
  { to: '/app/admin/plans',      label: 'Plans',       icon: '🗂' },
  { to: '/app/admin/brokers',    label: 'Brokers',     icon: '🔗' },
  { to: '/app/admin/signals',     label: 'Signals',     icon: '⚡' },
  { to: '/app/admin/deposits',    label: 'Deposits',    icon: '💰' },
  { to: '/app/admin/withdrawals', label: 'Withdrawals', icon: '💸' },
  { to: '/app/admin/fraud',       label: 'Fraud',       icon: '🛡' },
  { to: '/app/admin/audit-logs',  label: 'Audit Logs',  icon: '📋' },
]

function isActive(to: string): boolean {
  if (to === '/app/admin') return route.path === '/app/admin'
  return route.path.startsWith(to)
}
</script>

<template>
  <div class="admin-layout">
    <!-- Admin sub-navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">
      <div class="admin-nav__inner">
        <span class="admin-nav__badge">Admin</span>
        <div class="admin-nav__links">
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="admin-nav__link"
            :class="{ 'admin-nav__link--active': isActive(item.to) }"
          >
            <span class="admin-nav__icon" aria-hidden="true">{{ item.icon }}</span>
            {{ item.label }}
          </router-link>
        </div>
      </div>
    </nav>

    <!-- Page content -->
    <div class="admin-layout__content">
      <RouterView />
    </div>
  </div>
</template>

<style lang="scss" scoped>
.admin-layout {
  display: flex;
  flex-direction: column;
  min-height: 100%;
}

.admin-nav {
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  position: sticky;
  top: 0;
  z-index: 100;

  &__inner {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: 0 var(--space-6);
    overflow-x: auto;
    scrollbar-width: none;
    &::-webkit-scrollbar { display: none; }
  }

  &__badge {
    flex-shrink: 0;
    font-size: var(--text-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 12%, transparent);
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    border: 1px solid color-mix(in srgb, var(--color-primary) 25%, transparent);
  }

  &__links {
    display: flex;
    align-items: center;
    gap: var(--space-1);
  }

  &__link {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-3) var(--space-3);
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text-muted);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
    transition: color var(--transition-fast), border-color var(--transition-fast);

    &:hover {
      color: var(--color-text);
    }

    &--active {
      color: var(--color-primary);
      border-bottom-color: var(--color-primary);
    }
  }

  &__icon {
    font-size: var(--text-sm);
  }
}

.admin-layout__content {
  flex: 1;
}
</style>
