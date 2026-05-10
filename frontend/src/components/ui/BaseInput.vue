<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  modelValue?: string | number
  label?: string
  type?: string
  placeholder?: string
  error?: string
  disabled?: boolean
  required?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  required: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

// Generate a stable id for label/input association if not provided
const inputId = computed(() => props.id ?? `input-${Math.random().toString(36).slice(2, 9)}`)

function onInput(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
  <div class="field" :class="{ 'field--error': error, 'field--disabled': disabled }">
    <label
      v-if="label"
      :for="inputId"
      class="field__label"
    >
      {{ label }}
      <span v-if="required" class="field__required" aria-hidden="true">*</span>
    </label>

    <input
      :id="inputId"
      class="field__input"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :aria-invalid="!!error"
      :aria-describedby="error ? `${inputId}-error` : undefined"
      @input="onInput"
    />

    <p
      v-if="error"
      :id="`${inputId}-error`"
      class="field__error"
      role="alert"
      aria-live="polite"
    >
      {{ error }}
    </p>
  </div>
</template>

<style lang="scss" scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  width: 100%;

  &__label {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text);
  }

  &__required {
    color: var(--color-danger);
    margin-left: var(--space-1);
  }

  &__input {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    color: var(--color-text);
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    outline: none;

    &::placeholder {
      color: var(--color-text-muted);
    }

    &:hover:not(:disabled) {
      border-color: var(--color-text-muted);
    }

    &:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }

    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      background: var(--color-surface-2);
    }
  }

  &--error &__input {
    border-color: var(--color-danger);

    &:focus {
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-danger) 20%, transparent);
    }
  }

  &__error {
    font-size: var(--text-xs);
    color: var(--color-danger);
    margin: 0;
  }
}
</style>
