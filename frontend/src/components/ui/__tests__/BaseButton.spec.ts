import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from '@/components/ui/BaseButton.vue'

describe('BaseButton', () => {
  it('renders slot content', () => {
    const wrapper = mount(BaseButton, {
      slots: { default: 'Click me' },
    })
    expect(wrapper.text()).toContain('Click me')
  })

  it('disabled prop disables the button', () => {
    const wrapper = mount(BaseButton, {
      props: { disabled: true },
    })
    const button = wrapper.find('button')
    expect(button.attributes('disabled')).toBeDefined()
  })

  it('loading prop shows spinner and disables button', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
    })
    const button = wrapper.find('button')
    expect(button.attributes('disabled')).toBeDefined()
    expect(wrapper.find('.btn__spinner').exists()).toBe(true)
  })

  it('variant prop applies correct CSS class', () => {
    const wrapper = mount(BaseButton, {
      props: { variant: 'danger' },
    })
    expect(wrapper.find('button').classes()).toContain('btn--danger')
  })

  it('applies primary variant class by default', () => {
    const wrapper = mount(BaseButton)
    expect(wrapper.find('button').classes()).toContain('btn--primary')
  })

  it('applies loading class when loading', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
    })
    expect(wrapper.find('button').classes()).toContain('btn--loading')
  })
})
