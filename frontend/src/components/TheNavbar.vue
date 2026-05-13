<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import AppIcon from '@/components/AppIcon.vue'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()

const isUserMenuOpen = ref(false)

const pageTitle = computed(() => {
  const name = route.name as string | undefined
  const titles: Record<string, string> = {
    dashboard:           'Dashboard',
    accounts:            'Trading Accounts',
    'account-detail':    'Account Detail',
    plans:               'Investment Plans',
    invest:              'New Investment',
    investments:         'Investment History',
    wallet:              'Wallet',
    'wallet-deposit':    'Deposit Funds',
    'wallet-withdraw':   'Withdraw Funds',
    'wallet-transactions': 'Transactions',
    signals:             'Trading Signals',
    'admin-dashboard':   'Admin — Dashboard',
    'admin-users':       'Admin — Users',
    'admin-accounts':    'Admin — Accounts',
    'admin-investments': 'Admin — Investments',
    'admin-plans':       'Admin — Plans',
    'admin-brokers':     'Admin — Brokers',
    'admin-signals':     'Admin — Signals',
    'admin-fraud':       'Admin — Fraud Review',
    'admin-audit-logs':  'Admin — Audit Logs',
  }
  return name ? (titles[name] ?? '') : ''
})

function getInitials(first: string, last: string) {
  return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase()
}

async function handleLogout() {
  isUserMenuOpen.value = false
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header class="navbar" role="banner">
    <!-- Left: page title -->
    <div class="navbar__left">
      <h1 class="navbar__title">{{ pageTitle }}</h1>
    </div>

    <!-- Right: actions -->
    <div class="navbar__right">
      <!-- Notification bell (placeholder) -->
      <button class="navbar__action-btn" aria-label="Notifications" title="Notifications">
        <AppIcon name="bell" :size="18" />
      </button>

      <ThemeToggle />

      <!-- User menu -->
      <div v-if="authStore.user" class="user-menu">
        <button
          class="user-menu__trigger"
          :aria-expanded="isUserMenuOpen"
          aria-haspopup="true"
          aria-label="User menu"
          @click="isUserMenuOpen = !isUserMenuOpen"
        >
          <span class="user-menu__avatar" aria-hidden="true">
            {{ getInitials(authStore.user.first_name, authStore.user.last_name) }}
          </span>
          <span class="user-menu__name">{{ authStore.user.first_name }}</span>
          <AppIcon
            name="chevron-down"
            :size="14"
            class="user-menu__chevron"
            :class="{ 'user-menu__chevron--open': isUserMenuOpen }"
          />
        </button>

        <Transition name="dropdown">
          <div
            v-if="isUserMenuOpen"
            class="user-menu__dropdown"
            role="menu"
            @keydown.escape="isUserMenuOpen = false"
          >
            <div class="user-menu__header">
              <span class="user-menu__full-name">
                {{ authStore.user.first_name }} {{ authStore.user.last_name }}
              </span>
              <span class="user-menu__email">{{ authStore.user.email }}</span>
            </div>
            <div class="user-menu__separator" />
            <button
              class="user-menu__item user-menu__item--danger"
              role="menuitem"
              @click="handleLogout"
            >
              <AppIcon name="logout" :size="15" />
              Sign out
            </button>
          </div>
        </Transition>
      </div>
    </div>

    <div
      v-if="isUserMenuOpen"
      class="user-menu__backdrop"
      aria-hidden="true"
      @click="isUserMenuOpen = false"
    />
  </header>
</template>

<style lang="scss" scoped>
.navbar {
  position: fixed;
  top: 0;
  left: var(--sidebar-width);
  right: 0;
  height: var(--navbar-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--space-6);
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  z-index: 100;

  &__left {
    display: flex;
    align-items: center;
    min-width: 0;
  }

  &__title {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--color-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
  }

  &__right {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-shrink: 0;
  }

  &__action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    color: var(--color-text-muted);
    cursor: pointer;
    transition: color var(--transition-fast), background var(--transition-fast),
      border-color var(--transition-fast);

    &:hover {
      color: var(--color-text);
      background: var(--color-surface-2);
      border-color: var(--color-primary);
    }

    &:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
  }

  @media (max-width: 767px) {
    left: 0;
    padding-left: calc(var(--space-4) + 2.25rem + var(--space-3));
  }
}

// ── User menu ──────────────────────────────────────────────────────────────────
.user-menu {
  position: relative;

  &__trigger {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 0.3rem var(--space-3) 0.3rem 0.3rem;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-full);
    cursor: pointer;
    color: var(--color-text);
    font-size: var(--text-sm);
    transition: background var(--transition-fast), border-color var(--transition-fast);

    &:hover {
      background: var(--color-surface-2);
      border-color: var(--color-primary);
    }

    &:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
  }

  &__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.875rem;
    height: 1.875rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: #0B0F1A;
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 800;
    flex-shrink: 0;
    letter-spacing: 0.04em;
  }

  &__name {
    font-weight: 500;
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__chevron {
    flex-shrink: 0;
    color: var(--color-text-muted);
    transition: transform var(--transition-fast);

    &--open { transform: rotate(180deg); }
  }

  &__dropdown {
    position: absolute;
    top: calc(100% + var(--space-2));
    right: 0;
    min-width: 220px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    z-index: 200;
    overflow: hidden;
  }

  &__header {
    padding: var(--space-3) var(--space-4);
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  &__full-name {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
  }

  &__email {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__separator {
    height: 1px;
    background: var(--color-border);
  }

  &__item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    width: 100%;
    padding: var(--space-3) var(--space-4);
    background: transparent;
    border: none;
    text-align: left;
    font-size: var(--text-sm);
    font-family: var(--font-sans);
    color: var(--color-text);
    cursor: pointer;
    text-decoration: none;
    transition: background var(--transition-fast);

    &:hover { background: var(--color-surface-2); }
    &:focus-visible { outline: 2px solid var(--color-primary); outline-offset: -2px; }

    &--danger {
      color: var(--color-danger);
      &:hover { background: color-mix(in srgb, var(--color-danger) 8%, transparent); }
    }
  }

  &__backdrop {
    position: fixed;
    inset: 0;
    z-index: 150;
  }
}

// ── Dropdown transition ────────────────────────────────────────────────────────
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity var(--transition-fast), transform var(--transition-fast);
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.97);
}
</style>
