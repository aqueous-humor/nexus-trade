<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AdminAccount } from '@/stores/admin'
import { useNotificationStore } from '@/stores/notification'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(cents / 100)
}

const adminStore = useAdminStore()
const notificationStore = useNotificationStore()
const actionLoading = ref(false)

// Reassign modal
const reassignModalOpen = ref(false)
const reassignTargetAccount = ref<AdminAccount | null>(null)
const reassignNewUserId = ref('')

function openReassignModal(account: AdminAccount) {
  reassignTargetAccount.value = account
  reassignNewUserId.value = ''
  reassignModalOpen.value = true
}

async function confirmReassign() {
  if (!reassignTargetAccount.value || !reassignNewUserId.value) return
  actionLoading.value = true
  try {
    await adminStore.reassignAccount(reassignTargetAccount.value.id, Number(reassignNewUserId.value))
    notificationStore.showToast('Account reassigned successfully.', 'success')
    reassignModalOpen.value = false
  } catch {
    notificationStore.showToast('Failed to reassign account.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

// Create account modal
const createModalOpen = ref(false)
const createForm = ref({
  user_id: '',
  type: 'demo' as 'demo' | 'live',
  leverage: '1',
})

function openCreateModal() {
  createForm.value = { user_id: '', type: 'demo', leverage: '1' }
  createModalOpen.value = true
}

async function confirmCreate() {
  actionLoading.value = true
  try {
    await adminStore.createAccount({
      user_id: Number(createForm.value.user_id),
      type: createForm.value.type,
      leverage: Number(createForm.value.leverage),
    })
    notificationStore.showToast('Account created successfully.', 'success')
    createModalOpen.value = false
  } catch {
    notificationStore.showToast('Failed to create account.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

// Suspend / Reactivate
async function toggleSuspend(account: AdminAccount) {
  const newStatus = account.status === 'suspended' ? 'active' : 'suspended'
  try {
    await adminStore.updateAccountStatus(account.id, newStatus)
    notificationStore.showToast(`Account ${newStatus}.`, 'success')
  } catch {
    notificationStore.showToast('Failed to update account status.', 'danger')
  }
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'user', label: 'User' },
  { key: 'type', label: 'Type' },
  { key: 'balance', label: 'Balance' },
  { key: 'status', label: 'Status' },
  { key: 'leverage', label: 'Leverage' },
  { key: 'actions', label: 'Actions', width: '180px' },
]

const tableRows = computed(() =>
  adminStore.accounts.map((a) => ({
    id: a.id,
    user: a.user?.email ?? `User #${a.user?.id}`,
    type: a.type,
    balance: formatUSD(a.balance_cents),
    status: a.status,
    leverage: `1:${a.leverage}`,
    actions: null,
    _raw: a,
  })),
)

const leverageOptions = ['1', '50', '100', '200', '500', '1000']

onMounted(() => {
  adminStore.fetchAccounts()
})
</script>

<template>
  <div class="admin-accounts">
    <div class="admin-accounts__header">
      <h1 class="admin-accounts__title">Accounts</h1>
      <BaseButton @click="openCreateModal">Create Account</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No accounts found</template>

      <template #type="{ row }">
        <BaseBadge :variant="row.type === 'live' ? 'success' : 'info'">{{ row.type }}</BaseBadge>
      </template>

      <template #status="{ row }">
        <BaseBadge
          :variant="row.status === 'active' ? 'success' : row.status === 'suspended' ? 'warning' : 'danger'"
        >
          {{ row.status }}
        </BaseBadge>
      </template>

      <template #actions="{ row }">
        <div class="admin-accounts__actions">
          <BaseButton
            size="sm"
            :variant="row.status === 'suspended' ? 'secondary' : 'danger'"
            @click="toggleSuspend((row as Record<string, unknown>)._raw as AdminAccount)"
          >
            {{ row.status === 'suspended' ? 'Reactivate' : 'Suspend' }}
          </BaseButton>
          <BaseButton
            size="sm"
            variant="secondary"
            @click="openReassignModal((row as Record<string, unknown>)._raw as AdminAccount)"
          >
            Reassign
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Reassign modal -->
    <BaseModal v-model="reassignModalOpen" title="Reassign Account" size="sm">
      <div class="admin-accounts__modal-body">
        <p class="admin-accounts__modal-desc">
          Reassign account <strong>#{{ reassignTargetAccount?.id }}</strong> to a new user.
        </p>
        <BaseInput
          v-model="reassignNewUserId"
          label="New User ID"
          placeholder="Enter user ID"
          required
        />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="reassignModalOpen = false">Cancel</BaseButton>
        <BaseButton :disabled="!reassignNewUserId" @click="confirmReassign">Confirm</BaseButton>
      </template>
    </BaseModal>

    <!-- Create account modal -->
    <BaseModal v-model="createModalOpen" title="Create Account">
      <div class="admin-accounts__modal-body">
        <BaseInput
          v-model="createForm.user_id"
          label="User ID"
          placeholder="Enter user ID"
          required
        />
        <div class="admin-accounts__field">
          <label class="admin-accounts__label" for="account-type">Type</label>
          <select id="account-type" v-model="createForm.type" class="admin-accounts__select">
            <option value="demo">Demo</option>
            <option value="live">Live</option>
          </select>
        </div>
        <div class="admin-accounts__field">
          <label class="admin-accounts__label" for="account-leverage">Leverage</label>
          <select id="account-leverage" v-model="createForm.leverage" class="admin-accounts__select">
            <option v-for="lev in leverageOptions" :key="lev" :value="lev">1:{{ lev }}</option>
          </select>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="createModalOpen = false">Cancel</BaseButton>
        <BaseButton :disabled="!createForm.user_id" @click="confirmCreate">Create</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.admin-accounts {
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

  &__actions {
    display: flex;
    gap: var(--space-2);
  }

  &__modal-body {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__modal-desc {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
  }

  &__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__label {
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
    outline: none;

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }
}
</style>
