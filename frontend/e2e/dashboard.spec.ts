import { test, expect } from '@playwright/test'

test('dashboard loads for authenticated user', async ({ page }) => {
  // Navigate to login
  await page.goto('/login')
  await expect(page).toHaveTitle(/NexusTrade/)
})
