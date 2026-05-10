<script setup lang="ts">
import { ref } from 'vue'
import { useAccountStore } from '@/stores/account'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

// ── Types ─────────────────────────────────────────────────────────────────────

interface Signal {
  id: number
  name: string
  description: string
  provider_metadata: Record<string, unknown>
  status: 'active' | 'inactive'
}

// ── Stores ────────────────────────────────────────────────────────────────────

const accountStore = useAccountStore()

// ── Stub data ─────────────────────────────────────────────────────────────────

// Stub: signals will be fetched from GET /api/v1/signals in a future phase
const signals = ref<Signal[]>([])
const isLoading = ref(false)

// ── Subscription state ────────────────────────────────────────────────────────

// Map<accountId, signalId> — tracks which account is subscribed to which signal
const subscriptions = ref<Map<number, number>>(new Map())

function isSubscribed(accountId: number, signalId: number): boolean {
  return subscriptions.value.get(accountId) === signalId
}

function subscribe(accountId: number, signalId: number): void {
  // TODO: POST /api/v1/accounts/:id/signal
  subscriptions.value = new Map(subscriptions.value).set(accountId, signalId)
}

function unsubscribe(accountId: number): void {
  // TODO: DELETE /api/v1/accounts/:id/signal
  const updated = new Map(subscriptions.value)
  updated.delete(accountId)
  subscriptions.value = updated
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function statusBadgeVariant(status: Signal['status']): 'success' | 'neutral' {
  return status === 'active' ? 'success' : 'neutral'
}

function accountLabel(account: { type: string; id: number }): string {
  return `${account.type.charAt(0).toUpperCase() + account.type.slice(1)} #${account.id}`
}
</script>

<template>
  <div class="signals">
    <h1 class="signals__title">Trading Signals</h1>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="signals__skeleton-list" aria-busy="true" aria-label="Loading signals">
      <div v-for="i in 3" :key="i" class="signals__skeleton-card">
        <div class="signals__skeleton signals__skeleton--title" />
        <div class="signals__skeleton signals__skeleton--text" />
        <div class="signals__skeleton signals__skeleton--text signals__skeleton--short" />
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="signals.length === 0"
      class="signals__empty"
    >
      <p>No active signals available.</p>
    </div>

    <!-- Signal cards -->
    <ul v-else class="signals__list" role="list">
      <li
        v-for="signal in signals"
        :key="signal.id"
        class="signals__card"
      >
        <!-- Card header -->
        <div class="signals__card-header">
          <h2 class="signals__card-name">{{ signal.name }}</h2>
          <BaseBadge :variant="statusBadgeVariant(signal.status)">
            {{ signal.status.charAt(0).toUpperCase() + signal.status.slice(1) }}
          </BaseBadge>
        </div>

        <!-- Description -->
        <p class="signals__card-description">{{ signal.description }}</p>

        <!-- Provider metadata -->
        <dl
          v-if="Object.keys(signal.provider_metadata).length > 0"
          class="signals__metadata"
        >
          <div
            v-for="(value, key) in signal.provider_metadata"
            :key="String(key)"
            class="signals__metadata-row"
          >
            <dt class="signals__metadata-key">
              {{ String(key).charAt(0).toUpperCase() + String(key).slice(1).replace(/_/g, ' ') }}
            </dt>
            <dd class="signals__metadata-value">{{ value }}</dd>
          </div>
        </dl>

        <!-- Per-account subscription controls -->
        <div
          v-if="accountStore.accounts.length > 0"
          class="signals__accounts"
        >
          <h3 class="signals__accounts-title">Subscribe with account</h3>
          <div class="signals__account-list">
            <div
              v-for="account in accountStore.accounts"
              :key="account.id"
              class="signals__account-row"
            >
              <span class="signals__account-label">{{ accountLabel(account) }}</span>
              <BaseButton
                v-if="!isSubscribed(account.id, signal.id)"
                variant="primary"
                size="sm"
                @click="subscribe(account.id, signal.id)"
              >
                Subscribe
              </BaseButton>
              <BaseButton
                v-else
                variant="secondary"
                size="sm"
                @click="unsubscribe(account.id)"
              >
                Unsubscribe
              </BaseButton>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<style lang="scss" scoped>
.signals {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);

  &__title {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  // Signal list
  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  // Signal card
  &__card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    box-shadow: var(--shadow-sm);
    transition: box-shadow var(--transition-fast);

    &:hover {
      box-shadow: var(--shadow-md);
    }
  }

  &__card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
  }

  &__card-name {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__card-description {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
    line-height: 1.6;
  }

  // Provider metadata
  &__metadata {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2) var(--space-6);
    margin: 0;
    padding: var(--space-3) var(--space-4);
    background: var(--color-surface-2);
    border-radius: var(--radius-md);
  }

  &__metadata-row {
    display: flex;
    align-items: center;
    gap: var(--space-2);
  }

  &__metadata-key {
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  &__metadata-value {
    font-size: var(--text-sm);
    color: var(--color-text);
    font-weight: 500;
    margin: 0;
  }

  // Account subscription controls
  &__accounts {
    border-top: 1px solid var(--color-border);
    padding-top: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__accounts-title {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text-muted);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  &__account-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__account-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface-2);
    border-radius: var(--radius-md);
  }

  &__account-label {
    font-size: var(--text-sm);
    color: var(--color-text);
    font-weight: 500;
  }

  // Empty state
  &__empty {
    padding: var(--space-12) var(--space-4);
    text-align: center;
    color: var(--color-text-muted);
    font-size: var(--text-sm);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);

    p {
      margin: 0;
    }
  }

  // Loading skeleton
  &__skeleton-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__skeleton-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__skeleton {
    background: var(--color-surface-2);
    border-radius: var(--radius-sm);
    animation: shimmer 1.5s ease-in-out infinite;

    &--title {
      height: 1.5rem;
      width: 40%;
    }

    &--text {
      height: 1rem;
      width: 80%;
    }

    &--short {
      width: 55%;
    }
  }
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>
