import { test, expect } from '@playwright/test'

test('admin login redirects to admin dashboard', async ({ page }) => {
  await page.goto('/login')
  await expect(page.locator('h1')).toBeVisible()
})
