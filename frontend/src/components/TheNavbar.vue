<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const router = useRouter()
const authStore = useAuthStore()

const isUserMenuOpen = ref(false)

function toggleUserMenu() {
  isUserMenuOpen.value = !isUserMenuOpen.value
}

function closeUserMenu() {
  isUserMenuOpen.value = false
}

async function handleLogout() {
  closeUserMenu()
  await authStore.logout()
  router.push('/login')
}

function getInitials(firstName: string, lastName: string): string {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}
</script>

<template>
  <header class="navbar" role="banner">
    <div class="navbar__left">
      <span class="navbar__logo" aria-label="NexusTrade">NexusTrade</span>
    </div>

    <div class="navbar__right">
      <ThemeToggle />

      <div class="user-menu" v-if="authStore.user">
        <button
          class="user-menu__trigger"
          :aria-expanded="isUserMenuOpen"
          aria-haspopup="true"
          aria-label="User menu"
          @click="toggleUserMenu"
        >
          <span class="user-menu__avatar" aria-hidden="true">
            {{ getInitials(authStore.user.first_name, authStore.user.last_name) }}
          </span>
          <span class="user-menu__name">{{ authStore.user.first_name }}</span>
          <svg
            class="user-menu__chevron"
            :class="{ 'user-menu__chevron--open': isUserMenuOpen }"
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>

        <div
          v-if="isUserMenuOpen"
          class="user-menu__dropdown"
          role="menu"
          @keydown.escape="closeUserMenu"
        >
          <RouterLink
            to="/profile"
            class="user-menu__item"
            role="menuitem"
            @click="closeUserMenu"
          >
            Profile
          </RouterLink>
          <button
            class="user-menu__item user-menu__item--danger"
            role="menuitem"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </div>
    </div>

    <!-- Backdrop to close menu on outside click -->
    <div
      v-if="isUserMenuOpen"
      class="user-menu__backdrop"
      aria-hidden="true"
      @click="closeUserMenu"
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
    gap: var(--space-3);
  }

  &__logo {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--color-primary);
    letter-spacing: -0.02em;
  }

  &__right {
    display: flex;
    align-items: center;
    gap: var(--space-3);
  }
}

.user-menu {
  position: relative;

  &__trigger {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-2);
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    color: var(--color-text);
    font-size: var(--text-sm);
    transition: background var(--transition-fast), border-color var(--transition-fast);

    &:hover {
      background: var(--color-surface-2);
      border-color: var(--color-primary);
    }

    &:focus-visible {
      outline: 2px solid var(--color-primary);
      outline-offset: 2px;
    }
  }

  &__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    background: var(--color-primary);
    color: #0B0F1A;
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 700;
    flex-shrink: 0;
  }

  &__name {
    font-weight: 500;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__chevron {
    flex-shrink: 0;
    transition: transform var(--transition-fast);
    color: var(--color-text-muted);

    &--open {
      transform: rotate(180deg);
    }
  }

  &__dropdown {
    position: absolute;
    top: calc(100% + var(--space-2));
    right: 0;
    min-width: 160px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    z-index: 200;
    overflow: hidden;
  }

  &__item {
    display: block;
    width: 100%;
    padding: var(--space-3) var(--space-4);
    background: transparent;
    border: none;
    text-align: left;
    font-size: var(--text-sm);
    color: var(--color-text);
    cursor: pointer;
    text-decoration: none;
    transition: background var(--transition-fast);

    &:hover {
      background: var(--color-surface-2);
    }

    &:focus-visible {
      outline: 2px solid var(--color-primary);
      outline-offset: -2px;
    }

    &--danger {
      color: var(--color-danger);

      &:hover {
        background: color-mix(in srgb, var(--color-danger) 10%, transparent);
      }
    }
  }

  &__backdrop {
    position: fixed;
    inset: 0;
    z-index: 150;
  }
}
</style>
