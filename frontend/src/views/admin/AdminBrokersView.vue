<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

interface Broker {
  id: number
  name: string
  platform_type: 'MT4' | 'MT5'
  default_leverage: number
  status: 'active' | 'inactive'
  credentials_json: string
}

const isLoading = ref(false)
const brokers = ref<Broker[]>([])

// Modal state
const modalOpen = ref(false)
const editingBroker = ref<Broker | null>(null)
const form = ref<Omit<Broker, 'id'>>({
  name: '',
  platform_type: 'MT4',
  default_leverage: 1,
  status: 'active',
  credentials_json: '{"server": "", "port": 443}',
})

const leverageOptions = [1, 50, 100, 200, 500, 1000]

function openCreateModal() {
  editingBroker.value = null
  form.value = {
    name: '',
    platform_type: 'MT4',
    default_leverage: 1,
    status: 'active',
    credentials_json: '{"server": "", "port": 443}',
  }
  modalOpen.value = true
}

function openEditModal(broker: Broker) {
  editingBroker.value = broker
  form.value = { ...broker }
  modalOpen.value = true
}

async function confirmSave() {
  if (editingBroker.value) {
    // TODO: PATCH /api/v1/admin/brokers/:id
    console.log('Update broker', editingBroker.value.id, form.value)
  } else {
    // TODO: POST /api/v1/admin/brokers
    console.log('Create broker', form.value)
  }
  modalOpen.value = false
}

async function deleteBroker(broker: Broker) {
  if (!confirm(`Delete broker "${broker.name}"?`)) return
  // TODO: DELETE /api/v1/admin/brokers/:id
  console.log('Delete broker', broker.id)
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'name', label: 'Name' },
  { key: 'platform', label: 'Platform' },
  { key: 'leverage', label: 'Default Leverage' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', width: '120px' },
]

const tableRows = computed(() =>
  brokers.value.map((b) => ({
    id: b.id,
    name: b.name,
    platform: b.platform_type,
    leverage: `1:${b.default_leverage}`,
    status: b.status,
    actions: null,
    _raw: b,
  })),
)

onMounted(async () => {
  isLoading.value = true
  try {
    // TODO: GET /api/v1/admin/brokers
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="admin-brokers">
    <div class="admin-brokers__header">
      <h1 class="admin-brokers__title">Brokers</h1>
      <BaseButton @click="openCreateModal">Create Broker</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="isLoading">
      <template #empty>No brokers found</template>

      <template #platform="{ row }">
        <BaseBadge :variant="row.platform === 'MT5' ? 'success' : 'info'">{{ row.platform }}</BaseBadge>
      </template>

      <template #status="{ row }">
        <BaseBadge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</BaseBadge>
      </template>

      <template #actions="{ row }">
        <div class="admin-brokers__actions">
          <BaseButton size="sm" variant="secondary" @click="openEditModal((row as Record<string, unknown>)._raw as Broker)">
            Edit
          </BaseButton>
          <BaseButton size="sm" variant="danger" @click="deleteBroker((row as Record<string, unknown>)._raw as Broker)">
            Delete
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Create/Edit broker modal -->
    <BaseModal v-model="modalOpen" :title="editingBroker ? 'Edit Broker' : 'Create Broker'">
      <div class="admin-brokers__modal-body">
        <BaseInput v-model="form.name" label="Name" placeholder="Broker name" required />

        <div class="admin-brokers__field">
          <span class="admin-brokers__label">Platform Type</span>
          <div class="admin-brokers__radio-group">
            <label class="admin-brokers__radio-label">
              <input v-model="form.platform_type" type="radio" value="MT4" class="admin-brokers__radio" />
              MT4
            </label>
            <label class="admin-brokers__radio-label">
              <input v-model="form.platform_type" type="radio" value="MT5" class="admin-brokers__radio" />
              MT5
            </label>
          </div>
        </div>

        <div class="admin-brokers__field">
          <label class="admin-brokers__label" for="broker-leverage">Default Leverage</label>
          <select id="broker-leverage" v-model="form.default_leverage" class="admin-brokers__select">
            <option v-for="lev in leverageOptions" :key="lev" :value="lev">1:{{ lev }}</option>
          </select>
        </div>

        <div class="admin-brokers__field">
          <label class="admin-brokers__label" for="broker-status">Status</label>
          <select id="broker-status" v-model="form.status" class="admin-brokers__select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="admin-brokers__field">
          <label class="admin-brokers__label" for="broker-creds">Credentials (JSON)</label>
          <textarea
            id="broker-creds"
            v-model="form.credentials_json"
            class="admin-brokers__textarea"
            placeholder='{"server": "...", "port": 443}'
            rows="4"
          />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="modalOpen = false">Cancel</BaseButton>
        <BaseButton :disabled="!form.name" @click="confirmSave">
          {{ editingBroker ? 'Save Changes' : 'Create' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style lang="scss" scoped>
.admin-brokers {
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
    gap: var(--space-2);
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
    font-family: var(--font-mono);
    font-size: var(--text-sm);
    color: var(--color-text);
    resize: vertical;
    outline: none;

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }
  }

  &__radio-group {
    display: flex;
    gap: var(--space-6);
  }

  &__radio-label {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--color-text);
    cursor: pointer;
  }

  &__radio {
    accent-color: var(--color-primary);
    cursor: pointer;
  }
}
</style>
