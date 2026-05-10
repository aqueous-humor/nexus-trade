<script setup lang="ts">
interface Column {
  key: string
  label: string
  width?: string
}

interface Props {
  columns: Column[]
  rows: Record<string, unknown>[]
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false,
})
</script>

<template>
  <div class="table-wrapper">
    <div v-if="loading" class="table-skeleton" aria-busy="true" aria-label="Loading data">
      <div v-for="i in 5" :key="i" class="table-skeleton__row">
        <div
          v-for="col in columns"
          :key="col.key"
          class="table-skeleton__cell"
        />
      </div>
    </div>

    <table v-else class="table" role="table">
      <thead class="table__head">
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            class="table__th"
            :style="col.width ? { width: col.width } : {}"
            scope="col"
          >
            {{ col.label }}
          </th>
        </tr>
      </thead>

      <tbody class="table__body">
        <tr v-if="rows.length === 0">
          <td :colspan="columns.length" class="table__empty">
            <slot name="empty">No data</slot>
          </td>
        </tr>

        <tr
          v-for="(row, rowIndex) in rows"
          :key="rowIndex"
          class="table__row"
          :class="{ 'table__row--alt': rowIndex % 2 === 1 }"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            class="table__td"
          >
            <slot :name="col.key" :row="row" :value="row[col.key]">
              {{ row[col.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style lang="scss" scoped>
.table-wrapper {
  width: 100%;
  overflow-x: auto;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--text-sm);

  &__head {
    background: var(--color-surface-2);
  }

  &__th {
    padding: var(--space-3) var(--space-4);
    text-align: left;
    font-weight: 600;
    color: var(--color-text-muted);
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--color-border);
    white-space: nowrap;
  }

  &__body {
    background: var(--color-surface);
  }

  &__row {
    border-bottom: 1px solid var(--color-border);
    transition: background var(--transition-fast);

    &:last-child {
      border-bottom: none;
    }

    &:hover {
      background: var(--color-surface-2);
    }

    &--alt {
      background: color-mix(in srgb, var(--color-surface-2) 50%, var(--color-surface));

      &:hover {
        background: var(--color-surface-2);
      }
    }
  }

  &__td {
    padding: var(--space-3) var(--space-4);
    color: var(--color-text);
    vertical-align: middle;
  }

  &__empty {
    padding: var(--space-12) var(--space-4);
    text-align: center;
    color: var(--color-text-muted);
    font-size: var(--text-sm);
  }
}

// Skeleton loader
.table-skeleton {
  padding: var(--space-3) var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);

  &__row {
    display: flex;
    gap: var(--space-4);
  }

  &__cell {
    flex: 1;
    height: 1.25rem;
    background: var(--color-surface-2);
    border-radius: var(--radius-sm);
    animation: shimmer 1.5s ease-in-out infinite;
  }
}

@keyframes shimmer {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>
