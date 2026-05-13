<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NexusLogo from '@/components/NexusLogo.vue'
import AppIcon from '@/components/AppIcon.vue'

const authStore = useAuthStore()
const router   = useRouter()
const isOpen   = ref(false)

function toggleSidebar() { isOpen.value = !isOpen.value }
function closeSidebar()  { isOpen.value = false }

async function handleLogout() {
  closeSidebar()
  await authStore.logout()
  router.push('/login')
}

function getInitials(first: string, last: string) {
  return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase()
}

interface NavItem {
  label: string
  to: string
  icon: string
  exact?: boolean
  adminOnly?: boolean
}

const mainNav: NavItem[] = [
  { to: '/app',             label: 'Dashboard',   icon: 'home',        exact: true  },
  { to: '/app/accounts',    label: 'Accounts',    icon: 'accounts',    exact: false },
  { to: '/app/plans',       label: 'Plans',       icon: 'plans',       exact: false },
  { to: '/app/invest',      label: 'Invest',      icon: 'investments', exact: false },
  { to: '/app/investments', label: 'History',     icon: 'bar-chart',   exact: false },
  { to: '/app/wallet',      label: 'Wallet',      icon: 'wallet',      exact: false },
  { to: '/app/signals',     label: 'Signals',     icon: 'zap',         exact: false },
]

const adminNav: NavItem[] = [
  { label: 'Admin Panel', to: '/app/admin', icon: 'admin', adminOnly: true },
]

const isAdmin = computed(() => authStore.user?.role === 'admin')
</script>

<template>
  <!-- Mobile toggle -->
  <button
    class="sidebar-toggle"
    :aria-expanded="isOpen"
    aria-controls="sidebar"
    aria-label="Toggle navigation"
    @click="toggleSidebar"
  >
    <AppIcon :name="isOpen ? 'x' : 'menu'" :size="18" />
  </button>

  <!-- Mobile backdrop -->
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
    <!-- Brand -->
    <div class="sidebar__brand">
      <NexusLogo :width="140" />
    </div>

    <!-- Main navigation -->
    <div class="sidebar__scroll">
      <ul class="sidebar__nav" role="list">
        <li
          v-for="item in mainNav"
          :key="item.to"
          class="sidebar__nav-item"
        >
          <RouterLink
            :to="item.to"
            class="sidebar__link"
            :active-class="item.exact ? '' : 'is-active'"
            :exact-active-class="item.exact ? 'is-active' : ''"
            @click="closeSidebar"
          >
            <span class="sidebar__link-icon">
              <AppIcon :name="item.icon" :size="18" />
            </span>
            <span class="sidebar__link-label">{{ item.label }}</span>
          </RouterLink>
        </li>
      </ul>

      <!-- Admin section -->
      <template v-if="isAdmin">
        <div class="sidebar__divider">
          <span class="sidebar__divider-label">Administration</span>
        </div>
        <ul class="sidebar__nav" role="list">
          <li
            v-for="item in adminNav"
            :key="item.to"
            class="sidebar__nav-item"
          >
            <RouterLink
              :to="item.to"
              class="sidebar__link"
              active-class="is-active"
              @click="closeSidebar"
            >
              <span class="sidebar__link-icon">
                <AppIcon :name="item.icon" :size="18" />
              </span>
              <span class="sidebar__link-label">{{ item.label }}</span>
            </RouterLink>
          </li>
        </ul>
      </template>
    </div>

    <!-- User footer -->
    <div v-if="authStore.user" class="sidebar__footer">
      <div class="sidebar__user">
        <div class="sidebar__user-avatar">
          {{ getInitials(authStore.user.first_name, authStore.user.last_name) }}
        </div>
        <div class="sidebar__user-info">
          <span class="sidebar__user-name">
            {{ authStore.user.first_name }} {{ authStore.user.last_name }}
          </span>
          <span class="sidebar__user-role">{{ authStore.user.role }}</span>
        </div>
        <button
          class="sidebar__logout-btn"
          title="Sign out"
          aria-label="Sign out"
          @click="handleLogout"
        >
          <AppIcon name="logout" :size="16" />
        </button>
      </div>
    </div>
  </nav>
</template>

<style lang="scss" scoped>
// ── Mobile toggle ─────────────────────────────────────────────────────────────
.sidebar-toggle {
  display: none;
  position: fixed;
  top: calc((var(--navbar-height) - 2.25rem) / 2);
  left: var(--space-4);
  z-index: 300;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  cursor: pointer;
  transition: background var(--transition-fast), border-color var(--transition-fast);

  &:hover { background: var(--color-surface-2); border-color: var(--color-primary); }
  &:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }

  @media (max-width: 767px) { display: flex; }
}

.sidebar-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(2px);
  z-index: 200;

  @media (max-width: 767px) { display: block; }
}

// ── Sidebar shell ─────────────────────────────────────────────────────────────
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

  // ── Brand ──────────────────────────────────────────────────────────────────
  &__brand {
    display: flex;
    align-items: center;
    height: var(--navbar-height);
    padding: 0 var(--space-5);
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
  }

  // ── Scrollable nav area ────────────────────────────────────────────────────
  &__scroll {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-3) 0 var(--space-4);
    scrollbar-width: thin;
    scrollbar-color: var(--color-border) transparent;

    &::-webkit-scrollbar { width: 3px; }
    &::-webkit-scrollbar-track { background: transparent; }
    &::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 2px; }
  }

  // ── Nav list ───────────────────────────────────────────────────────────────
  &__nav {
    list-style: none;
    padding: 0 var(--space-3);
    margin: 0;
  }

  &__nav-item { margin-bottom: 2px; }

  &__link {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: 0.6rem var(--space-3);
    border-radius: var(--radius-md);
    color: var(--color-text-muted);
    text-decoration: none;
    font-size: var(--text-sm);
    font-weight: 500;
    transition: color var(--transition-fast), background var(--transition-fast);

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
      background: color-mix(in srgb, var(--color-primary) 10%, transparent);
      font-weight: 600;

      .sidebar__link-icon { color: var(--color-primary); }
    }
  }

  &__link-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: inherit;
  }

  &__link-label { flex: 1; }

  // ── Section divider ────────────────────────────────────────────────────────
  &__divider {
    display: flex;
    align-items: center;
    padding: var(--space-4) var(--space-4) var(--space-2);
  }

  &__divider-label {
    font-size: var(--text-xs);
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--color-text-subtle);
  }

  // ── User footer ────────────────────────────────────────────────────────────
  &__footer {
    border-top: 1px solid var(--color-border);
    padding: var(--space-3) var(--space-3);
    flex-shrink: 0;
  }

  &__user {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-2);
    border-radius: var(--radius-md);
  }

  &__user-avatar {
    flex-shrink: 0;
    width: 2rem;
    height: 2rem;
    border-radius: var(--radius-full);
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: #0B0F1A;
    font-size: var(--text-xs);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__user-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  &__user-name {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__user-role {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    text-transform: capitalize;
  }

  &__logout-btn {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--radius-sm);
    color: var(--color-text-muted);
    cursor: pointer;
    transition: color var(--transition-fast), background var(--transition-fast),
      border-color var(--transition-fast);

    &:hover {
      color: var(--color-danger);
      background: color-mix(in srgb, var(--color-danger) 8%, transparent);
      border-color: color-mix(in srgb, var(--color-danger) 25%, transparent);
    }

    &:focus-visible { outline: 2px solid var(--color-danger); outline-offset: 2px; }
  }

  // ── Mobile ─────────────────────────────────────────────────────────────────
  @media (max-width: 767px) {
    transform: translateX(-100%);
    transition: transform var(--transition-base);
    box-shadow: var(--shadow-xl);

    &--open { transform: translateX(0); }
  }
}
</style>
