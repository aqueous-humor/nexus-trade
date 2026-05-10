import { test, expect } from '@playwright/test'

test('registration → login flow', async ({ page }) => {
  await page.goto('/register')
  await page.fill('[id="register-first-name"]', 'Test')
  await page.fill('[id="register-last-name"]', 'User')
  await page.fill('[id="register-email"]', `test${Date.now()}@example.com`)
  await page.fill('[id="register-password"]', 'SecurePass123!')
  await page.fill('[id="register-password-confirmation"]', 'SecurePass123!')
  await page.click('button[type="submit"]')
  await expect(page.locator('.alert--success')).toBeVisible({ timeout: 5000 })
})
