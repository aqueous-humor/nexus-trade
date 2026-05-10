import { ref, watchEffect } from 'vue'

const STORAGE_KEY = 'nexustrade-theme'
type Theme = 'light' | 'dark'

const theme = ref<Theme>(
  (localStorage.getItem(STORAGE_KEY) as Theme) ?? 'dark'
)

watchEffect(() => {
  document.documentElement.setAttribute('data-theme', theme.value)
  localStorage.setItem(STORAGE_KEY, theme.value)
})

export function useTheme() {
  function toggle() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
  }
  return { theme, toggle }
}
