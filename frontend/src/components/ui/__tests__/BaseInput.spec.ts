import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseInput from '@/components/ui/BaseInput.vue'

describe('BaseInput', () => {
  it('renders label when label prop is provided', () => {
    const wrapper = mount(BaseInput, {
      props: { label: 'Email address', id: 'email' },
    })
    const label = wrapper.find('label')
    expect(label.exists()).toBe(true)
    expect(label.text()).toContain('Email address')
  })

  it('does not render label when label prop is not provided', () => {
    const wrapper = mount(BaseInput)
    expect(wrapper.find('label').exists()).toBe(false)
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '' },
    })
    const input = wrapper.find('input')
    await input.setValue('hello')
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')![0]).toEqual(['hello'])
  })

  it('shows error message when error prop is provided', () => {
    const wrapper = mount(BaseInput, {
      props: { error: 'This field is required' },
    })
    expect(wrapper.text()).toContain('This field is required')
    expect(wrapper.find('.field__error').exists()).toBe(true)
  })

  it('disabled prop disables the input', () => {
    const wrapper = mount(BaseInput, {
      props: { disabled: true },
    })
    const input = wrapper.find('input')
    expect(input.attributes('disabled')).toBeDefined()
  })

  it('applies error class when error prop is provided', () => {
    const wrapper = mount(BaseInput, {
      props: { error: 'Invalid value' },
    })
    expect(wrapper.find('.field').classes()).toContain('field--error')
  })
})
