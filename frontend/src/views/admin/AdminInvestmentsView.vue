<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AdminInvestment } from '@/stores/admin'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(cents / 100)
}

const adminStore = useAdminStore()

// Filters
const filterStatus = ref('all')
const filterResult = ref('all')
const filterUserId = ref('')

// Create investment modal
const createModalOpen = ref(false)
const createForm = ref({
  user_id: '',
  account_id: '',
  plan_id: '',
  duration_id: '',
  amount: '',
})

function openCreateModal() {
  createForm.value = { user_id: '', account_id: '', plan_id: '', duration_id: '', amount: '' }
  createModalOpen.value = true
}

async function confirmCreate() {
  // TODO: POST /api/v1/admin/investments
  console.log('Create investment', createForm.value)
  createModalOpen.value = false
}

// Update status modal
const statusModalOpen = ref(false)
const statusTarget = ref<AdminInvestment | null>(null)
const newStatus = ref('pending')

function openStatusModal(inv: AdminInvestment) {
  statusTarget.value = inv
  newStatus.value = inv.status
  statusModalOpen.value = true
}

async function confirmStatus() {
  if (!statusTarget.value) return
  // TODO: PATCH /api/v1/admin/investments/:id/status
  console.log('Update status', statusTarget.value.id, newStatus.value)
  statusModalOpen.value = false
}

// Record result modal
const resultModalOpen = ref(false)
const resultTarget = ref<AdminInvestment | null>(null)
const newResult = ref('WIN')
const resultProfitCents = ref('')

function openResultModal(inv: AdminInvestment) {
  resultTarget.value = inv
  newResult.value = 'WIN'
  resultProfitCents.value = ''
  resultModalOpen.value = true
}

async function confirmResult() {
  if (!resultTarget.value) return
  // TODO: PATCH /api/v1/admin/investments/:id/result
  console.log('Record result', resultTarget.value.id, newResult.value, resultProfitCents.value)
  resultModalOpen.value = false
}

// Profit adjustment modal
const profitModalOpen = ref(false)
const profitTarget = ref<AdminInvestment | null>(null)
const adjustedProfitCents = ref('')
const adjustmentReason = ref('')

function openProfitModal(inv: AdminInvestment) {
  profitTarget.value = inv
  adjustedProfitCents.value = ''
  adjustmentReason.value = ''
  profitModalOpen.value = true
}

async function confirmProfitAdjust() {
  if (!profitTarget.value) return
  // TODO: PATCH /api/v1/admin/investments/:id/profit
  console.log('Adjust profit', profitTarget.value.id, adjustedProfitCents.value, adjustmentReason.value)
  profitModalOpen.value = false
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'user', label: 'User' },
  { key: 'plan', label: 'Plan' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
  { key: 'result', label: 'Result' },
  { key: 'profit', label: 'Profit' },
  { key: 'actions', label: 'Actions', width: '240px' },
]

const filteredInvestments = computed(() =>
  adminStore.investments.filter((inv) => {
    const matchesStatus = filterStatus.value === 'all' || inv.status === filterStatus.value
    const matchesResult =
      filterResult.value === 'all' ||
      (inv as Record<string, unknown>).result === filterResult.value
    const matchesUser =
      !filterUserId.value || String(inv.user?.id) === filterUserId.value
    return matchesStatus && matchesResult && matchesUser
  }),
)

const tableRows = computed(() =>
  filteredInvestments.value.map((inv) => ({
    id: inv.id,
    user: inv.user?.email ?? `User #${inv.user?.id}`,
    plan: inv.plan?.name ?? `Plan #${inv.plan?.id}`,
    amount: formatUSD(inv.amount_cents),
    status: inv.status,
    result: (inv as Record<string, unknown>).result ?? '—',
    profit: formatUSD(inv.profit_cents),
    actions: null,
    _raw: inv,
  })),
)

function statusVariant(status: string) {
  const map: Record<string, 'warning' | 'info' | 'success' | 'neutral' | 'danger'> = {
    pending: 'warning',
    active: 'info',
    completed: 'success',
    cancelled: 'neutral',
    rejected: 'danger',
  }
  return map[status] ?? 'neutral'
}

function resultVariant(result: string) {
  const map: Record<string, 'success' | 'danger' | 'neutral'> = {
    WIN: 'success',
    LOSS: 'danger',
    DRAW: 'neutral',
  }
  return map[result] ?? 'neutral'
}

onMounted(() => {
  adminStore.fetchInvestments()
})
</script>

<template>
  <div class="admin-investments">
    <div class="admin-investments__header">
      <h1 class="admin-investments__title">Investments</h1>
      <BaseButton @click="openCreateModal">Create Investment</BaseButton>
    </div>

    <!-- Filter bar -->
    <div class="admin-investments__filters">
      <div class="admin-investments__filter-group">
        <label class="admin-investments__filter-label" for="inv-status">Status</label>
        <select id="inv-status" v-model="filterStatus" class="admin-investments__select">
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="admin-investments__filter-group">
        <label class="admin-investments__filter-label" for="inv-result">Result</label>
        <select id="inv-result" v-model="filterResult" class="admin-investments__select">
          <option value="all">All</option>
          <option value="WIN">WIN</option>
          <option value="LOSS">LOSS</option>
          <option value="DRAW">DRAW</option>
        </select>
      </div>
      <div class="admin-investments__filter-group">
        <label class="admin-investments__filter-label" for="inv-user">User ID</label>
        <input
          id="inv-user"
          v-model="filterUserId"
          class="admin-investments__select"
          placeholder="Filter by user ID"
        />
      </div>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No investments found</template>

      <template #status="{ row }">
        <BaseBadge :variant="statusVariant(row.status as string)">{{ row.status }}</BaseBadge>
      </template>

      <template #result="{ row }">
        <BaseBadge v-if="row.result !== '—'" :variant="resultVariant(row.result as string)">{{ row.result }}</BaseBadge>
        <span v-else class="admin-investments__dash">—</span>
      </template>

      <template #actions="{ row }">
        <div class="admin-investments__actions">
          <BaseButton size="sm" variant="secondary" @click="openStatusModal((row as Record<string, unknown>)._raw as AdminInvestment)">
            Status
          </BaseButton>
          <BaseButton size="sm" variant="secondary" @click="openResultModal((row as Record<string, unknown>)._raw as AdminInvestment)">
            Result
          </BaseButton>
          <BaseButton size="sm" variant="secondary" @click="openProfitModal((row as Record<string, unknown>)._raw as AdminInvestment)">
            Profit
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Create investment modal -->
    <BaseModal v-model="createModalOpen" title="Create Investment">
      <div class="admin-investments__modal-body">
        <BaseInput v-model="createForm.user_id" label="User ID" placeholder="Enter user ID" required />
        <BaseInput v-model="createForm.account_id" label="Account ID" placeholder="Enter account ID" required />
        <BaseInput v-model="createForm.plan_id" label="Plan ID" placeholder="Enter plan ID" required />
        <BaseInput v-model="createForm.duration_id" label="Duration ID" placeholder="Enter duration ID" required />
        <BaseInput v-model="createForm.amount" label="Amount (USD)" placeholder="e.g. 1000.00" required />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="createModalOpen = false">Cancel</BaseButton>
        <BaseButton @click="confirmCreate">Create</BaseButton>
      </template>
    </BaseModal>

    <!-- Update status modal -->
    <BaseModal v-model="statusModalOpen" title="Update Status" size="sm">
      <div class="admin-investments__modal-body">
        <p class="admin-investments__modal-desc">
          Update status for investment <strong>#{{ statusTarget?.id }}</strong>
        </p>
        <div class="admin-investments__field">
          <label class="admin-investments__filter-label" for="new-status">New Status</label>
          <select id="new-status" v-model="newStatus" class="admin-investments__select">
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="statusModalOpen = false">Cancel</BaseButton>
        <BaseButton @click="confirmStatus">Update</BaseButton>
      </template>
    </BaseModal>

    <!-- Record result modal -->
    <BaseModal v-model="resultModalOpen" title="Record Result" size="sm">
      <div class="admin-investments__modal-body">
        <p class="admin-investments__modal-desc">
          Record result for investment <strong>#{{ resultTarget?.id }}</strong>
        </p>
        <div class="admin-investments__field">
          <label class="admin-investments__filter-label" for="new-result">Result</label>
          <select id="new-result" v-model="newResult" class="admin-investments__select">
            <option value="WIN">WIN</option>
            <option value="LOSS">LOSS</option>
            <option value="DRAW">DRAW</option>
          </select>
        </div>
        <BaseInput v-model="resultProfitCents" label="Profit (cents)" placeholder="e.g. 5000" />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="resultModalOpen = false">Cancel</BaseButton>
        <BaseButton @click="confirmResult">Record</BaseButton>
      </template>
    </BaseModal>

    <!-- Profit adjustment modal -->
    <BaseModal v-model="profitModalOpen" title="Adjust Profit" size="sm">
      <div class="admin-investments__modal-body">
        <p class="admin-investments__modal-desc">
          Adjust profit for investment <strong>#{{ profitTarget?.id }}</strong>
        </p>
        <BaseInput v-model="adjustedProfitCents" label="Adjusted Profit (cents)" placeholder="e.g. 5000" required />
        <div class="admin-investments__field">
          <label class="admin-investments__filter-label" for="adjust-reason">Reason</label>
          <textarea
            id="adjust-reason"
            v-model="adjustmentReason"
            class="admin-investments__textarea"
            placeholder="Reason for adjustment…"
            rows="3"
          />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="profitModalOpen = false">Cancel</BaseButton>
        <BaseButton :disabled="!adjustedProfitCents" @click="confirmProfitAdjust">Adjust</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.admin-investments {
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
    outline: none;
    min-width: 120px;

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }

  &__actions {
    display: flex;
    gap: var(--space-2);
  }

  &__dash {
    color: var(--color-text-muted);
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
