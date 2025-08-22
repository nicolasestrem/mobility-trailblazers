import { test as setup, expect } from '@playwright/test';

/**
 * Authentication setup for different user roles
 * Creates auth states that can be reused across tests
 */

const adminFile = 'tests/.auth/admin.json';
const juryFile = 'tests/.auth/jury.json';
const juryAdminFile = 'tests/.auth/jury-admin.json';

// Admin user authentication
setup('authenticate as admin', async ({ page }) => {
  console.log('🔑 Setting up admin authentication...');
  
  await page.goto('/wp-admin');
  
  // Fill login form
  await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
  await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
  await page.click('#wp-submit');
  
  // Wait for successful login
  await page.waitForURL('/wp-admin/index.php');
  await expect(page.locator('#wpadminbar')).toBeVisible();
  
  console.log('✅ Admin authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: adminFile });
});

// Jury member authentication
setup('authenticate as jury member', async ({ page }) => {
  console.log('🔑 Setting up jury member authentication...');
  
  await page.goto('/wp-admin');
  
  // Fill login form with jury member credentials
  await page.fill('#user_login', process.env.JURY_USERNAME || 'jury1');
  await page.fill('#user_pass', process.env.JURY_PASSWORD || 'jury123');
  await page.click('#wp-submit');
  
  // For jury members, they might be redirected to a different page
  // or have limited admin access
  try {
    await page.waitForURL('/wp-admin/**', { timeout: 10000 });
    await expect(page.locator('#wpadminbar')).toBeVisible();
  } catch {
    // If no admin access, check for frontend redirect
    await page.goto('/jury-dashboard/');
    await expect(page.locator('.mt-jury-dashboard')).toBeVisible();
  }
  
  console.log('✅ Jury member authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: juryFile });
});

// Jury admin authentication
setup('authenticate as jury admin', async ({ page }) => {
  console.log('🔑 Setting up jury admin authentication...');
  
  await page.goto('/wp-admin');
  
  // Fill login form with jury admin credentials
  await page.fill('#user_login', process.env.JURY_ADMIN_USERNAME || 'juryadmin');
  await page.fill('#user_pass', process.env.JURY_ADMIN_PASSWORD || 'juryadmin123');
  await page.click('#wp-submit');
  
  // Wait for successful login
  await page.waitForURL('/wp-admin/**');
  await expect(page.locator('#wpadminbar')).toBeVisible();
  
  // Verify jury admin has access to MT plugin
  await page.goto('/wp-admin/admin.php?page=mt-assignments');
  await expect(page.locator('.mt-admin-page')).toBeVisible();
  
  console.log('✅ Jury admin authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: juryAdminFile });
});

// Logout helper for cleanup
setup('logout from all sessions', async ({ page }) => {
  console.log('🚪 Cleaning up authentication sessions...');
  
  // Clear all cookies and local storage
  await page.context().clearCookies();
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });
  
  console.log('✅ Sessions cleared');
});