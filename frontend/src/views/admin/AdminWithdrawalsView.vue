<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AdminWithdrawal } from '@/stores/admin'
import { useNotificationStore } from '@/stores/notification'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(cents / 100)
}

const adminStore        = useAdminStore()
const notificationStore = useNotificationStore()
const actionLoading     = ref(false)

// Filter
const statusFilter = ref<'all' | 'pending' | 'approved' | 'rejected'>('pending')

const withdrawals = computed(() =>
  (adminStore.withdrawals as AdminWithdrawal[]).filter(
    (w) => statusFilter.value === 'all' || w.status === statusFilter.value,
  ),
)

// Approve / Reject modal
const modalOpen    = ref(false)
const actionType   = ref<'approve' | 'reject'>('approve')
const actionTarget = ref<AdminWithdrawal | null>(null)
const rejectReason = ref('')

function openModal(wdl: AdminWithdrawal, type: 'approve' | 'reject') {
  actionTarget.value = wdl
  actionType.value   = type
  rejectReason.value = ''
  modalOpen.value    = true
}

async function confirmAction() {
  if (!actionTarget.value) return
  actionLoading.value = true
  try {
    if (actionType.value === 'approve') {
      await adminStore.approveWithdrawal(actionTarget.value.id)
      notificationStore.showToast('Withdrawal approved.', 'success')
    } else {
      await adminStore.rejectWithdrawal(actionTarget.value.id, rejectReason.value)
      notificationStore.showToast('Withdrawal rejected.', 'success')
    }
    modalOpen.value = false
  } catch {
    notificationStore.showToast('Failed to process withdrawal.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

function statusVariant(status: string): 'warning' | 'success' | 'danger' {
  if (status === 'approved') return 'success'
  if (status === 'rejected') return 'danger'
  return 'warning'
}

const columns = [
  { key: 'id',      label: 'ID',      width: '60px' },
  { key: 'user',    label: 'User' },
  { key: 'amount',  label: 'Amount' },
  { key: 'wallet',  label: 'Wallet Address' },
  { key: 'status',  label: 'Status' },
  { key: 'created', label: 'Date' },
  { key: 'actions', label: 'Actions', width: '160px' },
]

const tableRows = computed(() =>
  withdrawals.value.map((w) => ({
    id:      w.id,
    user:    w.user?.email ?? `User #${w.user?.id}`,
    amount:  formatUSD(w.amount_cents),
    wallet:  w.wallet_address
      ? `${w.wallet_address.slice(0, 8)}…${w.wallet_address.slice(-6)}`
      : '—',
    status:  w.status,
    created: new Date(w.created_at).toLocaleDateString(),
    actions: null,
    _raw:    w,
  })),
)

onMounted(() => {
  adminStore.fetchWithdrawals()
})
</script>

<template>
  <div class="admin-withdrawals">
    <div class="admin-withdrawals__header">
      <h1 class="admin-withdrawals__title">Withdrawal Requests</h1>
    </div>

    <!-- Status filter -->
    <div class="admin-withdrawals__filters">
      <div class="admin-withdrawals__filter-group">
        <label class="admin-withdrawals__filter-label" for="wdl-status">Status</label>
        <select id="wdl-status" v-model="statusFilter" class="admin-withdrawals__select">
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No withdrawal requests found</template>

      <template #status="{ row }">
        <BaseBadge :variant="statusVariant(row.status as string)">{{ row.status }}</BaseBadge>
      </template>

      <template #wallet="{ row }">
        <span class="admin-withdrawals__wallet" :title="(row as Record<string, unknown>)._raw ? ((row as Record<string, unknown>)._raw as AdminWithdrawal).wallet_address : ''">
          {{ row.wallet }}
        </span>
      </template>

      <template #actions="{ row }">
        <div class="admin-withdrawals__actions">
          <BaseButton
            size="sm"
            variant="secondary"
            :disabled="row.status !== 'pending'"
            @click="openModal((row as Record<string, unknown>)._raw as AdminWithdrawal, 'approve')"
          >
            Approve
          </BaseButton>
          <BaseButton
            size="sm"
            variant="danger"
            :disabled="row.status !== 'pending'"
            @click="openModal((row as Record<string, unknown>)._raw as AdminWithdrawal, 'reject')"
          >
            Reject
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Confirm modal -->
    <BaseModal
      v-model="modalOpen"
      :title="actionType === 'approve' ? 'Approve Withdrawal' : 'Reject Withdrawal'"
      size="sm"
    >
      <div class="admin-withdrawals__modal-body">
        <div v-if="actionTarget" class="admin-withdrawals__details">
          <div class="admin-withdrawals__detail-row">
            <span class="admin-withdrawals__detail-label">User</span>
            <span class="admin-withdrawals__detail-value">{{ actionTarget.user?.email }}</span>
          </div>
          <div class="admin-withdrawals__detail-row">
            <span class="admin-withdrawals__detail-label">Amount</span>
            <span class="admin-withdrawals__detail-value">{{ formatUSD(actionTarget.amount_cents) }}</span>
          </div>
          <div class="admin-withdrawals__detail-row">
            <span class="admin-withdrawals__detail-label">Wallet</span>
            <span class="admin-withdrawals__detail-value admin-withdrawals__detail-value--mono">
              {{ actionTarget.wallet_address }}
            </span>
          </div>
        </div>

        <div v-if="actionType === 'reject'" class="admin-withdrawals__field">
          <label class="admin-withdrawals__label" for="wdl-reject-reason">
            Reason <span class="admin-withdrawals__required" aria-hidden="true">*</span>
          </label>
          <textarea
            id="wdl-reject-reason"
            v-model="rejectReason"
            class="admin-withdrawals__textarea"
            placeholder="Reason for rejection…"
            rows="3"
            required
          />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton
          :variant="actionType === 'reject' ? 'danger' : 'primary'"
          :disabled="actionType === 'reject' && !rejectReason"
          :loading="actionLoading"
          @click="confirmAction"
        >
          {{ actionType === 'approve' ? 'Approve' : 'Reject' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.admin-withdrawals {
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
    gap: var(--space-4);
    flex-wrap: wrap;
  }

  &__filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__filter-label {
    font-size: var(--text-xs);
    font-weight: 500;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
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

  &__actions {
    display: flex;
    gap: var(--space-2);
  }

  &__wallet {
    font-family: var(--font-mono);
    font-size: var(--text-xs);
    color: var(--color-text-muted);
  }

  &__modal-body {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__details {
    background: var(--color-surface-2);
    border-radius: var(--radius-md);
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__detail-row {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
  }

  &__detail-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 500;
    min-width: 80px;
    flex-shrink: 0;
  }

  &__detail-value {
    font-size: var(--text-sm);
    color: var(--color-text);
    word-break: break-all;

    &--mono {
      font-family: var(--font-mono);
      font-size: var(--text-xs);
    }
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

  &__required {
    color: var(--color-danger);
    margin-left: var(--space-1);
  }

  &__textarea {
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    color: var(--color-text);
    resize: vertical;
    outline: none;

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }
}
</style>
