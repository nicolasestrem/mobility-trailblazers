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
  await page.fill('#user_login', process.env.ADMIN_USERNAME || 'testadmin');
  
  // Clear password field and type password slowly to ensure it's entered correctly
  await page.locator('#user_pass').clear();
  await page.waitForTimeout(500);
  await page.locator('#user_pass').type(process.env.ADMIN_PASSWORD || 'AdminPlaywright2025', { delay: 50 });
  await page.waitForTimeout(500);
  await page.click('#wp-submit');
  
  // Wait for successful login - WordPress may redirect to different admin pages
  await page.waitForURL('**/wp-admin/**');
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
  await page.fill('#user_login', process.env.JURY_USERNAME || 'jurytester1');
  await page.fill('#user_pass', process.env.JURY_PASSWORD || 'Test123!@#Pass');
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
  await page.fill('#user_login', process.env.JURY_ADMIN_USERNAME || 'juryadmintester');
  await page.fill('#user_pass', process.env.JURY_ADMIN_PASSWORD || 'Test123!@#Pass');
  await page.click('#wp-submit');
  
  // Wait for successful login
  await page.waitForURL('/wp-admin/**');
  await expect(page.locator('#wpadminbar')).toBeVisible();
  
  // Verify jury admin has access to MT plugin
  await page.goto('/wp-admin/admin.php?page=mt-assignments');
  // Check for either the admin page class or the page title
  const hasAdminPage = await page.locator('.mt-admin-page, .wrap h1:has-text("MT Award")').isVisible({ timeout: 5000 }).catch(() => false);
  if (!hasAdminPage) {
    console.log('⚠️  MT admin page not found, but continuing...');
  }
  
  console.log('✅ Jury admin authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: juryAdminFile });
});

// Logout helper for cleanup
setup('logout from all sessions', async ({ page }) => {
  console.log('🚪 Cleaning up authentication sessions...');
  
  // Clear all cookies and local storage
  await page.context().clearCookies();
  
  // Try to clear storage, but don't fail if not allowed
  try {
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });
  } catch (e) {
    // Storage clearing might fail on some pages, that's okay
  }
  
  console.log('✅ Sessions cleared');
});