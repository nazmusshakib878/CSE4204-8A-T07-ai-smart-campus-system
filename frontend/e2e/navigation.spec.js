import { test,expect } from '@playwright/test';
test('public home loads and protected dashboard redirects to login',async({page})=>{await page.goto('/');await expect(page).toHaveTitle(/AI Smart Campus/i);await page.goto('/admin');await expect(page).toHaveURL(/\/login/);await expect(page.getByRole('button',{name:/sign in|login/i}).first()).toBeVisible();});
