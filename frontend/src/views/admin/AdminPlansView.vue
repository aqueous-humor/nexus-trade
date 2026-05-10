<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

function formatUSD(cents: number): string {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(cents / 100)
}

interface Plan {
  id: number
  name: string
  description: string
  roi_percentage: number
  min_amount_cents: number
  max_amount_cents: number
  status: 'active' | 'inactive'
  trending: boolean
  image_url?: string
  trending_title?: string
  trending_description?: string
  duration_labels: string
}

const isLoading = ref(false)
const plans = ref<Plan[]>([])

// Modal state
const modalOpen = ref(false)
const editingPlan = ref<Plan | null>(null)
const form = ref<Omit<Plan, 'id'>>({
  name: '',
  description: '',
  roi_percentage: 0,
  min_amount_cents: 0,
  max_amount_cents: 0,
  status: 'active',
  trending: false,
  image_url: '',
  trending_title: '',
  trending_description: '',
  duration_labels: '',
})

function openCreateModal() {
  editingPlan.value = null
  form.value = {
    name: '',
    description: '',
    roi_percentage: 0,
    min_amount_cents: 0,
    max_amount_cents: 0,
    status: 'active',
    trending: false,
    image_url: '',
    trending_title: '',
    trending_description: '',
    duration_labels: '',
  }
  modalOpen.value = true
}

function openEditModal(plan: Plan) {
  editingPlan.value = plan
  form.value = { ...plan }
  modalOpen.value = true
}

async function confirmSave() {
  if (editingPlan.value) {
    // TODO: PATCH /api/v1/admin/plans/:id
    console.log('Update plan', editingPlan.value.id, form.value)
  } else {
    // TODO: POST /api/v1/admin/plans
    console.log('Create plan', form.value)
  }
  modalOpen.value = false
}

async function deletePlan(plan: Plan) {
  if (!confirm(`Delete plan "${plan.name}"?`)) return
  // TODO: DELETE /api/v1/admin/plans/:id
  console.log('Delete plan', plan.id)
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'name', label: 'Name' },
  { key: 'roi', label: 'ROI%' },
  { key: 'min', label: 'Min' },
  { key: 'max', label: 'Max' },
  { key: 'status', label: 'Status' },
  { key: 'trending', label: 'Trending' },
  { key: 'actions', label: 'Actions', width: '120px' },
]

const tableRows = computed(() =>
  plans.value.map((p) => ({
    id: p.id,
    name: p.name,
    roi: `${p.roi_percentage}%`,
    min: formatUSD(p.min_amount_cents),
    max: formatUSD(p.max_amount_cents),
    status: p.status,
    trending: p.trending,
    actions: null,
    _raw: p,
  })),
)

onMounted(async () => {
  isLoading.value = true
  try {
    // TODO: GET /api/v1/admin/plans
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="admin-plans">
    <div class="admin-plans__header">
      <h1 class="admin-plans__title">Plans</h1>
      <BaseButton @click="openCreateModal">Create Plan</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="isLoading">
      <template #empty>No plans found</template>

      <template #status="{ row }">
        <BaseBadge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</BaseBadge>
      </template>

      <template #trending="{ row }">
        <span :class="row.trending ? 'admin-plans__check' : 'admin-plans__dash'">
          {{ row.trending ? '✓' : '—' }}
        </span>
      </template>

      <template #actions="{ row }">
        <div class="admin-plans__actions">
          <BaseButton size="sm" variant="secondary" @click="openEditModal((row as Record<string, unknown>)._raw as Plan)">
            Edit
          </BaseButton>
          <BaseButton size="sm" variant="danger" @click="deletePlan((row as Record<string, unknown>)._raw as Plan)">
            Delete
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Create/Edit plan modal -->
    <BaseModal v-model="modalOpen" :title="editingPlan ? 'Edit Plan' : 'Create Plan'" size="lg">
      <div class="admin-plans__modal-body">
        <BaseInput v-model="form.name" label="Name" placeholder="Plan name" required />
        <div class="admin-plans__field">
          <label class="admin-plans__label" for="plan-desc">Description</label>
          <textarea
            id="plan-desc"
            v-model="form.description"
            class="admin-plans__textarea"
            placeholder="Plan description…"
            rows="3"
          />
        </div>
        <div class="admin-plans__row">
          <BaseInput v-model="(form.min_amount_cents as unknown as string)" label="Min Amount (USD)" placeholder="e.g. 100" type="number" />
          <BaseInput v-model="(form.max_amount_cents as unknown as string)" label="Max Amount (USD)" placeholder="e.g. 10000" type="number" />
        </div>
        <BaseInput v-model="(form.roi_percentage as unknown as string)" label="ROI %" placeholder="e.g. 5.5" type="number" />
        <div class="admin-plans__field">
          <label class="admin-plans__label" for="plan-status">Status</label>
          <select id="plan-status" v-model="form.status" class="admin-plans__select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <BaseInput v-model="form.duration_labels" label="Duration Labels (comma-separated)" placeholder="e.g. 7 days, 14 days, 30 days" />
        <div class="admin-plans__field">
          <label class="admin-plans__checkbox-label">
            <input v-model="form.trending" type="checkbox" class="admin-plans__checkbox" />
            Trending
          </label>
        </div>
        <template v-if="form.trending">
          <BaseInput v-model="form.image_url" label="Image URL" placeholder="https://…" />
          <BaseInput v-model="form.trending_title" label="Trending Title" placeholder="Trending title" />
          <div class="admin-plans__field">
            <label class="admin-plans__label" for="trending-desc">Trending Description</label>
            <textarea
              id="trending-desc"
              v-model="form.trending_description"
              class="admin-plans__textarea"
              placeholder="Trending description…"
              rows="2"
            />
          </div>
        </template>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton :disabled="!form.name" @click="confirmSave">
          {{ editingPlan ? 'Save Changes' : 'Create' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.admin-plans {
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

  &__check {
    color: var(--color-success);
    font-weight: 700;
  }

  &__dash {
    color: var(--color-text-muted);
  }

  &__modal-body {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
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

  &__checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text);
    cursor: pointer;
  }

  &__checkbox {
    width: 1rem;
    height: 1rem;
    accent-color: var(--color-primary);
    cursor: pointer;
  }
}
</style>
