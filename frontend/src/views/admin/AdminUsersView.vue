<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AdminUser, type AdminAccount } from '@/stores/admin'
import { useNotificationStore } from '@/stores/notification'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(cents / 100)
}

const adminStore = useAdminStore()
const notificationStore = useNotificationStore()

// Search & filter state
const searchQuery = ref('')
const roleFilter = ref('all')
const statusFilter = ref('all')
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

function onSearchInput(value: string) {
  searchQuery.value = value
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    // debounced search applied via computed
  }, 300)
}

// Drawer state
const drawerOpen = ref(false)
const selectedUser = ref<AdminUser | null>(null)

function openDrawer(user: AdminUser) {
  selectedUser.value = user
  drawerOpen.value = true
}

function closeDrawer() {
  drawerOpen.value = false
  selectedUser.value = null
}

// Filtered users
const filteredUsers = computed(() => {
  return adminStore.users.filter((u) => {
    const q = searchQuery.value.toLowerCase()
    const matchesSearch =
      !q ||
      u.first_name.toLowerCase().includes(q) ||
      u.last_name.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q) ||
      String(u.id).includes(q)

    const matchesRole = roleFilter.value === 'all' || u.role === roleFilter.value
    const matchesStatus =
      statusFilter.value === 'all' ||
      (u as Record<string, unknown>).status === statusFilter.value

    return matchesSearch && matchesRole && matchesStatus
  })
})

// User accounts
const userAccounts = computed<AdminAccount[]>(() => {
  if (!selectedUser.value) return []
  return adminStore.accounts.filter(
    (a) => a.user && a.user.id === selectedUser.value!.id,
  )
})

const accountColumns = [
  { key: 'id', label: 'ID' },
  { key: 'type', label: 'Type' },
  { key: 'balance', label: 'Balance' },
  { key: 'status', label: 'Status' },
]

const accountRows = computed(() =>
  userAccounts.value.map((a) => ({
    id: a.id,
    type: a.type,
    balance: formatUSD(a.balance_cents),
    status: a.status,
    _raw: a,
  })),
)

// Table columns
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Role' },
  { key: 'status', label: 'Status' },
  { key: 'created', label: 'Created' },
  { key: 'actions', label: 'Actions', width: '100px' },
]

const tableRows = computed(() =>
  filteredUsers.value.map((u) => ({
    id: u.id,
    name: `${u.first_name} ${u.last_name}`,
    email: u.email,
    role: u.role,
    status: (u as Record<string, unknown>).status ?? 'active',
    created: new Date(u.created_at).toLocaleDateString(),
    actions: null,
    _raw: u,
  })),
)

const actionLoading = ref(false)

async function changeRole(user: AdminUser) {
  actionLoading.value = true
  try {
    const newRole = user.role === 'admin' ? 'user' : 'admin'
    await adminStore.updateUser(user.id, { role: newRole })
    if (selectedUser.value?.id === user.id) selectedUser.value = { ...selectedUser.value, role: newRole }
    notificationStore.showToast(`Role updated to ${newRole}.`, 'success')
  } catch {
    notificationStore.showToast('Failed to update role.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

async function toggleLock(user: AdminUser) {
  actionLoading.value = true
  try {
    const currentStatus = user.status ?? 'active'
    const newStatus = currentStatus === 'locked' ? 'active' : 'locked'
    await adminStore.updateUser(user.id, { status: newStatus })
    if (selectedUser.value?.id === user.id) selectedUser.value = { ...selectedUser.value, status: newStatus as 'active' | 'locked' }
    notificationStore.showToast(`Account ${newStatus === 'locked' ? 'locked' : 'unlocked'}.`, 'success')
  } catch {
    notificationStore.showToast('Failed to update account status.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

onMounted(() => {
  adminStore.fetchUsers()
})
</script>

<template>
  <div class="admin-users">
    <div class="admin-users__header">
      <h1 class="admin-users__title">Users</h1>
    </div>

    <!-- Filter bar -->
    <div class="admin-users__filters">
      <BaseInput
        :model-value="searchQuery"
        placeholder="Search by name, email, or ID…"
        @update:model-value="onSearchInput"
      />
      <div class="admin-users__filter-group">
        <label class="admin-users__filter-label" for="role-filter">Role</label>
        <select id="role-filter" v-model="roleFilter" class="admin-users__select">
          <option value="all">All</option>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="admin-users__filter-group">
        <label class="admin-users__filter-label" for="status-filter">Status</label>
        <select id="status-filter" v-model="statusFilter" class="admin-users__select">
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="locked">Locked</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No users found</template>

      <template #role="{ row }">
        <BaseBadge :variant="row.role === 'admin' ? 'warning' : 'neutral'">
          {{ row.role }}
        </BaseBadge>
      </template>

      <template #status="{ row }">
        <BaseBadge :variant="row.status === 'active' ? 'success' : 'danger'">
          {{ row.status }}
        </BaseBadge>
      </template>

      <template #actions="{ row }">
        <BaseButton size="sm" variant="secondary" @click="openDrawer((row as Record<string, unknown>)._raw as AdminUser)">
          View
        </BaseButton>
      </template>
    </BaseTable>

    <!-- User detail drawer -->
    <Transition name="drawer">
      <div v-if="drawerOpen" class="admin-users__drawer-backdrop" @click.self="closeDrawer">
        <div class="admin-users__drawer" role="dialog" aria-modal="true" aria-label="User Details">
          <div class="admin-users__drawer-header">
            <h2 class="admin-users__drawer-title">User Details</h2>
            <button class="admin-users__drawer-close" aria-label="Close drawer" @click="closeDrawer">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              </svg>
            </button>
          </div>

          <div v-if="selectedUser" class="admin-users__drawer-body">
            <div class="admin-users__drawer-section">
              <div class="admin-users__info-grid">
                <div class="admin-users__info-item">
                  <span class="admin-users__info-label">Name</span>
                  <span class="admin-users__info-value">{{ selectedUser.first_name }} {{ selectedUser.last_name }}</span>
                </div>
                <div class="admin-users__info-item">
                  <span class="admin-users__info-label">Email</span>
                  <span class="admin-users__info-value">{{ selectedUser.email }}</span>
                </div>
                <div class="admin-users__info-item">
                  <span class="admin-users__info-label">Role</span>
                  <BaseBadge :variant="selectedUser.role === 'admin' ? 'warning' : 'neutral'">
                    {{ selectedUser.role }}
                  </BaseBadge>
                </div>
                <div class="admin-users__info-item">
                  <span class="admin-users__info-label">Status</span>
                  <BaseBadge :variant="(selectedUser as Record<string, unknown>).status === 'locked' ? 'danger' : 'success'">
                    {{ (selectedUser as Record<string, unknown>).status ?? 'active' }}
                  </BaseBadge>
                </div>
                <div class="admin-users__info-item">
                  <span class="admin-users__info-label">Created</span>
                  <span class="admin-users__info-value">{{ new Date(selectedUser.created_at).toLocaleString() }}</span>
                </div>
              </div>
            </div>

            <div class="admin-users__drawer-section">
              <h3 class="admin-users__drawer-subtitle">Accounts</h3>
              <BaseTable :columns="accountColumns" :rows="accountRows">
                <template #type="{ row }">
                  <BaseBadge :variant="row.type === 'live' ? 'success' : 'info'">{{ row.type }}</BaseBadge>
                </template>
                <template #status="{ row }">
                  <BaseBadge :variant="row.status === 'active' ? 'success' : row.status === 'suspended' ? 'warning' : 'danger'">
                    {{ row.status }}
                  </BaseBadge>
                </template>
                <template #empty>No accounts</template>
              </BaseTable>
            </div>

            <div class="admin-users__drawer-actions">
              <BaseButton variant="secondary" :loading="actionLoading" @click="changeRole(selectedUser)">
                Change Role (→ {{ selectedUser.role === 'admin' ? 'User' : 'Admin' }})
              </BaseButton>
              <BaseButton
                :variant="selectedUser.status === 'locked' ? 'secondary' : 'danger'"
                :loading="actionLoading"
                @click="toggleLock(selectedUser)"
              >
                {{ selectedUser.status === 'locked' ? 'Unlock' : 'Lock' }} Account
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style lang="scss" scoped>
.admin-users {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__filters {
    display: flex;
    align-items: flex-end;
    gap: var(--space-4);
    flex-wrap: wrap;
  }

  &__filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__filter-label {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text);
  }

  &__select {
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    color: var(--color-text);
    cursor: pointer;
    outline: none;
    min-width: 120px;

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }

  // Drawer
  &__drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 900;
    display: flex;
    justify-content: flex-end;
  }

  &__drawer {
    width: 480px;
    max-width: 100vw;
    height: 100%;
    background: var(--color-surface);
    box-shadow: var(--shadow-lg);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  &__drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4) var(--space-6);
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
  }

  &__drawer-title {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
  }

  &__drawer-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    color: var(--color-text-muted);
    transition: background var(--transition-fast), color var(--transition-fast);

    &:hover {
      background: var(--color-surface-2);
      color: var(--color-text);
    }
  }

  &__drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
  }

  &__drawer-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__drawer-subtitle {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
  }

  &__info-grid {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__info-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
  }

  &__info-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 500;
    min-width: 80px;
  }

  &__info-value {
    font-size: var(--text-sm);
    color: var(--color-text);
  }

  &__drawer-actions {
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;
  }
}

// Drawer slide-in transition
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity var(--transition-base);

  .admin-users__drawer {
    transition: transform var(--transition-base);
  }
}

.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;

  .admin-users__drawer {
    transform: translateX(100%);
  }
}
</style>
