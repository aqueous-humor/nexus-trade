import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseBadge from '@/components/ui/BaseBadge.vue'

describe('BaseBadge', () => {
  it('renders slot content', () => {
    const wrapper = mount(BaseBadge, {
      slots: { default: 'Active' },
    })
    expect(wrapper.text()).toContain('Active')
  })

  it('applies correct variant class', () => {
    const wrapper = mount(BaseBadge, {
      props: { variant: 'success' },
    })
    expect(wrapper.find('.badge').classes()).toContain('badge--success')
  })

  it('applies neutral variant class by default', () => {
    const wrapper = mount(BaseBadge)
    expect(wrapper.find('.badge').classes()).toContain('badge--neutral')
  })

  it('applies danger variant class', () => {
    const wrapper = mount(BaseBadge, {
      props: { variant: 'danger' },
    })
    expect(wrapper.find('.badge').classes()).toContain('badge--danger')
  })

  it('applies size class', () => {
    const wrapper = mount(BaseBadge, {
      props: { size: 'sm' },
    })
    expect(wrapper.find('.badge').classes()).toContain('badge--sm')
  })
})
