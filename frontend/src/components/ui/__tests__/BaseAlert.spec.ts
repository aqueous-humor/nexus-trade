import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseAlert from '@/components/ui/BaseAlert.vue'

describe('BaseAlert', () => {
  it('renders slot content', () => {
    const wrapper = mount(BaseAlert, {
      slots: { default: 'Something went wrong' },
    })
    expect(wrapper.text()).toContain('Something went wrong')
  })

  it('dismissible prop shows dismiss button', () => {
    const wrapper = mount(BaseAlert, {
      props: { dismissible: true },
    })
    expect(wrapper.find('.alert__dismiss').exists()).toBe(true)
  })

  it('does not show dismiss button when not dismissible', () => {
    const wrapper = mount(BaseAlert, {
      props: { dismissible: false },
    })
    expect(wrapper.find('.alert__dismiss').exists()).toBe(false)
  })

  it('clicking dismiss button hides the alert', async () => {
    const wrapper = mount(BaseAlert, {
      props: { dismissible: true },
    })
    expect(wrapper.find('.alert').exists()).toBe(true)

    await wrapper.find('.alert__dismiss').trigger('click')
    expect(wrapper.find('.alert').exists()).toBe(false)
  })

  it('clicking dismiss button emits dismiss event', async () => {
    const wrapper = mount(BaseAlert, {
      props: { dismissible: true },
    })
    await wrapper.find('.alert__dismiss').trigger('click')
    expect(wrapper.emitted('dismiss')).toBeTruthy()
  })

  it('applies correct variant class', () => {
    const wrapper = mount(BaseAlert, {
      props: { variant: 'success' },
    })
    expect(wrapper.find('.alert').classes()).toContain('alert--success')
  })
})
