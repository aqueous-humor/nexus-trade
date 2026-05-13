<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminStore, type FraudAssessment } from '@/stores/admin'
import { useNotificationStore } from '@/stores/notification'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'

const adminStore = useAdminStore()
const notificationStore = useNotificationStore()
const actionLoading = ref(false)
const assessments = computed(() => adminStore.fraudAssessments)

// Approve/Reject modal
const actionModalOpen = ref(false)
const actionType = ref<'approve' | 'reject'>('approve')
const actionTarget = ref<FraudAssessment | null>(null)

const actionReason = ref('')

function openActionModal(assessment: FraudAssessment, type: 'approve' | 'reject') {
  actionTarget.value = assessment
  actionType.value = type
  actionReason.value = ''
  actionModalOpen.value = true
}

async function confirmAction() {
  if (!actionTarget.value) return
  actionLoading.value = true
  try {
    if (actionType.value === 'approve') {
      await adminStore.approveFraudAssessment(actionTarget.value.id)
      notificationStore.showToast('Assessment approved.', 'success')
    } else {
      await adminStore.rejectFraudAssessment(actionTarget.value.id, actionReason.value)
      notificationStore.showToast('Assessment rejected.', 'success')
    }
    actionModalOpen.value = false
  } catch {
    notificationStore.showToast('Failed to process assessment.', 'danger')
  } finally {
    actionLoading.value = false
  }
}

function riskScoreVariant(score: number): 'success' | 'warning' | 'danger' {
  if (score >= 80) return 'danger'
  if (score >= 50) return 'warning'
  return 'success'
}

// Table
const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'entity', label: 'Transaction/Investment' },
  { key: 'risk_score', label: 'Risk Score' },
  { key: 'triggered_rules', label: 'Triggered Rules' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', width: '160px' },
]

const tableRows = computed(() =>
  (assessments.value as FraudAssessment[]).map((a) => ({
    id: a.id,
    entity: `${a.entity_type} #${a.entity_id}`,
    risk_score: a.risk_score,
    triggered_rules: a.triggered_rules.join(', ') || '—',
    status: a.status,
    actions: null,
    _raw: a,
  })),
)

onMounted(() => {
  adminStore.fetchFraud()
})
</script>

<template>
  <div class="admin-fraud">
    <div class="admin-fraud__header">
      <h1 class="admin-fraud__title">Fraud Review</h1>
    </div>

    <BaseTable :columns="columns" :rows="tableRows" :loading="adminStore.isLoading">
      <template #empty>No fraud assessments found</template>

      <template #risk_score="{ row }">
        <span :class="`admin-fraud__score admin-fraud__score--${riskScoreVariant(row.risk_score as number)}`">
          {{ row.risk_score }}
        </span>
      </template>

      <template #status="{ row }">
        <BaseBadge
          :variant="row.status === 'approved' ? 'success' : row.status === 'rejected' ? 'danger' : 'warning'"
        >
          {{ row.status }}
        </BaseBadge>
      </template>

      <template #actions="{ row }">
        <div class="admin-fraud__actions">
          <BaseButton
            size="sm"
            variant="secondary"
            :disabled="row.status !== 'pending'"
            @click="openActionModal((row as Record<string, unknown>)._raw as FraudAssessment, 'approve')"
          >
            Approve
          </BaseButton>
          <BaseButton
            size="sm"
            variant="danger"
            :disabled="row.status !== 'pending'"
            @click="openActionModal((row as Record<string, unknown>)._raw as FraudAssessment, 'reject')"
          >
            Reject
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Approve/Reject modal -->
    <BaseModal
      v-model="actionModalOpen"
      :title="actionType === 'approve' ? 'Approve Assessment' : 'Reject Assessment'"
      size="sm"
    >
      <div class="admin-fraud__modal-body">
        <div v-if="actionTarget" class="admin-fraud__modal-details">
          <div class="admin-fraud__detail-row">
            <span class="admin-fraud__detail-label">ID</span>
            <span class="admin-fraud__detail-value">#{{ actionTarget.id }}</span>
          </div>
          <div class="admin-fraud__detail-row">
            <span class="admin-fraud__detail-label">Entity</span>
            <span class="admin-fraud__detail-value">{{ actionTarget.entity_type }} #{{ actionTarget.entity_id }}</span>
          </div>
          <div class="admin-fraud__detail-row">
            <span class="admin-fraud__detail-label">Risk Score</span>
            <span :class="`admin-fraud__score admin-fraud__score--${riskScoreVariant(actionTarget.risk_score)}`">
              {{ actionTarget.risk_score }}
            </span>
          </div>
          <div class="admin-fraud__detail-row">
            <span class="admin-fraud__detail-label">Rules</span>
            <span class="admin-fraud__detail-value">{{ actionTarget.triggered_rules.join(', ') || '—' }}</span>
          </div>
        </div>

        <div v-if="actionType === 'reject'" class="admin-fraud__field">
          <label class="admin-fraud__label" for="reject-reason">
            Reason <span class="admin-fraud__required" aria-hidden="true">*</span>
          </label>
          <textarea
            id="reject-reason"
            v-model="actionReason"
            class="admin-fraud__textarea"
            placeholder="Reason for rejection…"
            rows="3"
            required
          />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="actionModalOpen = false">Cancel</BaseButton>
        <BaseButton
          :variant="actionType === 'reject' ? 'danger' : 'primary'"
          :disabled="actionType === 'reject' && !actionReason"
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
.admin-fraud {
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

  &__score {
    font-weight: 700;
    font-size: var(--text-sm);

    &--success { color: var(--color-success); }
    &--warning { color: var(--color-warning); }
    &--danger  { color: var(--color-danger); }
  }

  &__modal-body {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__modal-details {
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
