<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type AdminSignal } from '@/stores/admin'
import { useNotificationStore } from '@/stores/notification'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

type Signal = AdminSignal

const adminStore = useAdminStore()
const notificationStore = useNotificationStore()
const actionLoading = ref(false)
const signals = computed(() => adminStore.signals)

// Modal state
const modalOpen = ref(false)
const editingSignal = ref<AdminSignal | null>(null)
const form = ref<{ name: string; description: string; provider_metadata: string; status: 'active' | 'inactive' }>({
  name: '',
  description: '',
  provider_metadata: '{}',
  status: 'active',
})

function openCreateModal() {
  editingSignal.value = null
  form.value = { name: '', description: '', provider_metadata: '{}', status: 'active' }
  modalOpen.value = true
}

function openEditModal(signal: Signal) {
  editingSignal.value = signal
  form.value = {
    name: signal.name,
    description: signal.description,
    provider_metadata: signal.provider_metadata,
    status: signal.status,
  }
  modalOpen.value = true
}

async function confirmSave() {
  actionLoading.value = true
  try {
    if (editingSignal.value) {
      await adminStore.updateSignal(editingSignal.value.id, { ...form.value })
      notificationStore.showToast('Signal updated.', 'success')
    } else {
      await adminStore.createSignal({ ...form.value })
      notificationStore.showToast('Signal created.', 'success')
    }
    modalOpen.value = false
  } catch {
    notificationStore.showToast('Failed to save signal.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

async function toggleActivate(signal: AdminSignal) {
  try {
    await adminStore.toggleSignalStatus(signal.id, signal.status !== 'active')
    notificationStore.showToast(`Signal ${signal.status === 'active' ? 'deactivated' : 'activated'}.`, 'success')
  } catch {
    notificationStore.showToast('Failed to update signal status.', 'danger')
  }
}

async function deleteSignal(signal: AdminSignal) {
  if (!confirm(`Delete signal "${signal.name}"?`)) return
  try {
    await adminStore.deleteSignal(signal.id)
    notificationStore.showToast('Signal deleted.', 'success')
  } catch {
    notificationStore.showToast('Failed to delete signal.', 'danger')
  }
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'name', label: 'Name' },
  { key: 'status', label: 'Status' },
  { key: 'created', label: 'Created' },
  { key: 'actions', label: 'Actions', width: '200px' },
]

const tableRows = computed(() =>
  (signals.value as AdminSignal[]).map((s) => ({
    id: s.id,
    name: s.name,
    status: s.status,
    created: new Date(s.created_at).toLocaleDateString(),
    actions: null,
    _raw: s,
  })),
)

onMounted(() => {
  adminStore.fetchSignals()
})
</script>

<template>
  <div class="admin-signals">
    <div class="admin-signals__header">
      <h1 class="admin-signals__title">Signals</h1>
      <BaseButton @click="openCreateModal">Create Signal</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No signals found</template>

      <template #status="{ row }">
        <BaseBadge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</BaseBadge>
      </template>

      <template #actions="{ row }">
        <div class="admin-signals__actions">
          <BaseButton
            size="sm"
            :variant="row.status === 'active' ? 'danger' : 'secondary'"
            @click="toggleActivate((row as Record<string, unknown>)._raw as AdminSignal)"
          >
            {{ row.status === 'active' ? 'Deactivate' : 'Activate' }}
          </BaseButton>
          <BaseButton size="sm" variant="secondary" @click="openEditModal((row as Record<string, unknown>)._raw as AdminSignal)">
            Edit
          </BaseButton>
          <BaseButton size="sm" variant="danger" @click="deleteSignal((row as Record<string, unknown>)._raw as AdminSignal)">
            Delete
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Create/Edit signal modal -->
    <BaseModal v-model="modalOpen" :title="editingSignal ? 'Edit Signal' : 'Create Signal'">
      <div class="admin-signals__modal-body">
        <BaseInput v-model="form.name" label="Name" placeholder="Signal name" required />
        <div class="admin-signals__field">
          <label class="admin-signals__label" for="signal-desc">Description</label>
          <textarea
            id="signal-desc"
            v-model="form.description"
            class="admin-signals__textarea"
            placeholder="Signal description…"
            rows="3"
          />
        </div>
        <div class="admin-signals__field">
          <label class="admin-signals__label" for="signal-meta">Provider Metadata (JSON)</label>
          <textarea
            id="signal-meta"
            v-model="form.provider_metadata"
            class="admin-signals__textarea admin-signals__textarea--mono"
            placeholder="{}"
            rows="4"
          />
        </div>
        <div class="admin-signals__field">
          <label class="admin-signals__label" for="signal-status">Status</label>
          <select id="signal-status" v-model="form.status" class="admin-signals__select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton :disabled="!form.name" :loading="actionLoading" @click="confirmSave">
          {{ editingSignal ? 'Save Changes' : 'Create' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.admin-signals {
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

    &--mono {
      font-family: var(--font-mono);
    }

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }
}
</style>
