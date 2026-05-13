<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AdminDeposit } from '@/stores/admin'
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
const statusFilter = ref<'all' | 'pending' | 'confirmed' | 'rejected'>('pending')

const deposits = computed(() =>
  (adminStore.deposits as AdminDeposit[]).filter(
    (d) => statusFilter.value === 'all' || d.status === statusFilter.value,
  ),
)

// Approve / Reject modal
const modalOpen    = ref(false)
const actionType   = ref<'approve' | 'reject'>('approve')
const actionTarget = ref<AdminDeposit | null>(null)
const rejectReason = ref('')

function openModal(dep: AdminDeposit, type: 'approve' | 'reject') {
  actionTarget.value = dep
  actionType.value   = type
  rejectReason.value = ''
  modalOpen.value    = true
}

async function confirmAction() {
  if (!actionTarget.value) return
  actionLoading.value = true
  try {
    if (actionType.value === 'approve') {
      await adminStore.approveDeposit(actionTarget.value.id)
      notificationStore.showToast('Deposit approved.', 'success')
    } else {
      await adminStore.rejectDeposit(actionTarget.value.id, rejectReason.value)
      notificationStore.showToast('Deposit rejected.', 'success')
    }
    modalOpen.value = false
  } catch {
    notificationStore.showToast('Failed to process deposit.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

function statusVariant(status: string): 'warning' | 'success' | 'danger' | 'neutral' {
  if (status === 'confirmed') return 'success'
  if (status === 'rejected')  return 'danger'
  return 'warning'
}

const columns = [
  { key: 'id',      label: 'ID',      width: '60px' },
  { key: 'user',    label: 'User' },
  { key: 'amount',  label: 'Amount' },
  { key: 'method',  label: 'Method' },
  { key: 'ref',     label: 'Reference' },
  { key: 'status',  label: 'Status' },
  { key: 'created', label: 'Date' },
  { key: 'actions', label: 'Actions', width: '160px' },
]

const tableRows = computed(() =>
  deposits.value.map((d) => ({
    id:      d.id,
    user:    d.user?.email ?? `User #${d.user?.id}`,
    amount:  formatUSD(d.amount_cents),
    method:  d.payment_method,
    ref:     d.reference,
    status:  d.status,
    created: new Date(d.created_at).toLocaleDateString(),
    actions: null,
    _raw:    d,
  })),
)

onMounted(() => {
  adminStore.fetchDeposits()
})
</script>

<template>
  <div class="admin-deposits">
    <div class="admin-deposits__header">
      <h1 class="admin-deposits__title">Deposit Requests</h1>
    </div>

    <!-- Status filter -->
    <div class="admin-deposits__filters">
      <div class="admin-deposits__filter-group">
        <label class="admin-deposits__filter-label" for="dep-status">Status</label>
        <select id="dep-status" v-model="statusFilter" class="admin-deposits__select">
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No deposit requests found</template>

      <template #status="{ row }">
        <BaseBadge :variant="statusVariant(row.status as string)">{{ row.status }}</BaseBadge>
      </template>

      <template #actions="{ row }">
        <div class="admin-deposits__actions">
          <BaseButton
            size="sm"
            variant="secondary"
            :disabled="row.status !== 'pending'"
            @click="openModal((row as Record<string, unknown>)._raw as AdminDeposit, 'approve')"
          >
            Approve
          </BaseButton>
          <BaseButton
            size="sm"
            variant="danger"
            :disabled="row.status !== 'pending'"
            @click="openModal((row as Record<string, unknown>)._raw as AdminDeposit, 'reject')"
          >
            Reject
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Confirm modal -->
    <BaseModal
      v-model="modalOpen"
      :title="actionType === 'approve' ? 'Approve Deposit' : 'Reject Deposit'"
      size="sm"
    >
      <div class="admin-deposits__modal-body">
        <div v-if="actionTarget" class="admin-deposits__details">
          <div class="admin-deposits__detail-row">
            <span class="admin-deposits__detail-label">User</span>
            <span class="admin-deposits__detail-value">{{ actionTarget.user?.email }}</span>
          </div>
          <div class="admin-deposits__detail-row">
            <span class="admin-deposits__detail-label">Amount</span>
            <span class="admin-deposits__detail-value">{{ formatUSD(actionTarget.amount_cents) }}</span>
          </div>
          <div class="admin-deposits__detail-row">
            <span class="admin-deposits__detail-label">Method</span>
            <span class="admin-deposits__detail-value">{{ actionTarget.payment_method }}</span>
          </div>
          <div class="admin-deposits__detail-row">
            <span class="admin-deposits__detail-label">Reference</span>
            <span class="admin-deposits__detail-value">{{ actionTarget.reference }}</span>
          </div>
        </div>
        <div v-if="actionType === 'reject'" class="admin-deposits__field">
          <label class="admin-deposits__label" for="dep-reject-reason">
            Reason <span class="admin-deposits__required" aria-hidden="true">*</span>
          </label>
          <textarea
            id="dep-reject-reason"
            v-model="rejectReason"
            class="admin-deposits__textarea"
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
.admin-deposits {
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
  }

  &__detail-value {
    font-size: var(--text-sm);
    color: var(--color-text);
    word-break: break-all;
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
