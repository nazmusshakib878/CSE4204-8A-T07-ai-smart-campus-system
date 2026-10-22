import { expect, test } from '@playwright/test';

const users = {
  student: { id: 101, name: 'Test Student', email: 'student@example.test', role: 'student' },
  faculty: { id: 102, name: 'Test Faculty', email: 'faculty@example.test', role: 'faculty' },
  admin: { id: 103, name: 'Test Admin', email: 'admin@example.test', role: 'admin' },
};

async function signInAs(page, role) {
  const user = users[role];

  await page.addInitScript((sessionUser) => {
    localStorage.setItem('auth_token', 'playwright-test-token');
    localStorage.setItem('user', JSON.stringify(sessionUser));
  }, user);

  await page.route('**/api/profile', async (route) => {
    await route.fulfill({ json: { user } });
  });
}

test('student cannot open an admin-only route', async ({ page }) => {
  await signInAs(page, 'student');

  await page.goto('/admin');

  await expect(page).toHaveURL(/\/dashboard$/);
});

test('faculty cannot open a student-only route', async ({ page }) => {
  await signInAs(page, 'faculty');

  await page.goto('/course-recommendations');

  await expect(page).toHaveURL(/\/faculty-dashboard$/);
});

test('admin is routed to the admin dashboard', async ({ page }) => {
  await signInAs(page, 'admin');

  await page.goto('/dashboard');

  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByText('Campus overview')).toBeVisible();
});
