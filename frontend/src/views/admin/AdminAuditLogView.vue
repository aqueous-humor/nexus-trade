<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AuditLog } from '@/stores/admin'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

const adminStore = useAdminStore()

// Filters
const filterActorId = ref('')
const filterOperationType = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterOutcome = ref('all')

// Pagination stub
const currentPage = ref(1)
const perPage = 20

function applyFilters() {
  const filters: Record<string, unknown> = {}
  if (filterActorId.value) filters.actor_id = filterActorId.value
  if (filterOperationType.value) filters.operation_type = filterOperationType.value
  if (filterDateFrom.value) filters.date_from = filterDateFrom.value
  if (filterDateTo.value) filters.date_to = filterDateTo.value
  if (filterOutcome.value !== 'all') filters.outcome = filterOutcome.value
  adminStore.fetchAuditLogs(filters)
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'operation_type', label: 'Operation Type' },
  { key: 'actor', label: 'Actor' },
  { key: 'target', label: 'Target' },
  { key: 'ip_address', label: 'IP Address' },
  { key: 'outcome', label: 'Outcome' },
  { key: 'date', label: 'Date' },
]

const tableRows = computed(() =>
  adminStore.auditLogs.map((log) => ({
    id: log.id,
    operation_type: log.operation_type,
    actor: `${log.actor_type} #${log.actor_id}`,
    target: `${log.target_type} #${log.target_id}`,
    ip_address: log.ip_address,
    outcome: log.outcome,
    date: new Date(log.created_at).toLocaleString(),
    _raw: log,
  })),
)

// Pagination stub
const totalPages = computed(() => Math.max(1, Math.ceil(adminStore.auditLogs.length / perPage)))

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
}

onMounted(() => {
  adminStore.fetchAuditLogs()
})
</script>

<template>
  <div class="admin-audit">
    <div class="admin-audit__header">
      <h1 class="admin-audit__title">Audit Logs</h1>
    </div>

    <!-- Filter bar -->
    <div class="admin-audit__filters">
      <BaseInput
        v-model="filterActorId"
        label="Actor ID"
        placeholder="Filter by actor ID"
      />
      <BaseInput
        v-model="filterOperationType"
        label="Operation Type"
        placeholder="e.g. user.login"
      />
      <BaseInput
        v-model="filterDateFrom"
        label="Date From"
        type="date"
      />
      <BaseInput
        v-model="filterDateTo"
        label="Date To"
        type="date"
      />
      <div class="admin-audit__filter-group">
        <label class="admin-audit__filter-label" for="audit-outcome">Outcome</label>
        <select id="audit-outcome" v-model="filterOutcome" class="admin-audit__select">
          <option value="all">All</option>
          <option value="success">Success</option>
          <option value="error">Error</option>
        </select>
      </div>
      <div class="admin-audit__filter-action">
        <BaseButton @click="applyFilters">Apply Filters</BaseButton>
      </div>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No audit logs found</template>

      <template #outcome="{ row }">
        <BaseBadge :variant="row.outcome === 'success' ? 'success' : 'danger'">
          {{ row.outcome }}
        </BaseBadge>
      </template>
    </BaseTable>

    <!-- Pagination stub -->
    <div class="admin-audit__pagination">
      <span class="admin-audit__pagination-info">
        Page {{ currentPage }} of {{ totalPages }}
      </span>
      <div class="admin-audit__pagination-controls">
        <BaseButton size="sm" variant="secondary" :disabled="currentPage <= 1" @click="prevPage">
          Previous
        </BaseButton>
        <BaseButton size="sm" variant="secondary" :disabled="currentPage >= totalPages" @click="nextPage">
          Next
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.admin-audit {
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

  &__filter-action {
    padding-bottom: 0;
    display: flex;
    align-items: flex-end;
  }

  &__pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4) 0;
  }

  &__pagination-info {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
  }

  &__pagination-controls {
    display: flex;
    gap: var(--space-2);
  }
}
</style>
