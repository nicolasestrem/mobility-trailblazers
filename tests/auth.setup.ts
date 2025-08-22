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
  await page.locator('#user_pass').type(process.env.ADMIN_PASSWORD || 'testadmin123', { delay: 50 });
  await page.waitForTimeout(500);
  await page.click('#wp-submit');
  
  // Wait for successful login - handle both German and English interfaces
  // The login might redirect to different pages or show an error
  await page.waitForLoadState('networkidle');
  
  // Check if we have an error message (German: "Fehler" or English: "Error")
  const errorElement = page.locator('#login_error');
  if (await errorElement.isVisible({ timeout: 1000 }).catch(() => false)) {
    const errorText = await errorElement.textContent();
    throw new Error(`Login failed: ${errorText}`);
  }
  
  // Wait for either redirect to admin or the admin bar to appear
  await Promise.race([
    page.waitForURL('**/wp-admin/**', { timeout: 10000 }),
    page.locator('#wpadminbar').waitFor({ state: 'visible', timeout: 10000 })
  ]).catch(async () => {
    // If neither worked, check if we're still on login page
    const currentUrl = page.url();
    if (currentUrl.includes('wp-login.php')) {
      // Try to submit again as sometimes the form needs a second submission
      await page.click('#wp-submit');
      await page.waitForLoadState('networkidle');
    }
  });
  
  // Final check - ensure we're logged in
  const isLoggedIn = await page.locator('#wpadminbar').isVisible({ timeout: 5000 }).catch(() => false) ||
                     page.url().includes('/wp-admin/');
  
  if (!isLoggedIn) {
    throw new Error('Failed to authenticate as admin');
  }
  
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
  
  // Wait for successful login - handle German locale
  await page.waitForLoadState('networkidle');
  
  // Check for login error
  const errorElement = page.locator('#login_error');
  if (await errorElement.isVisible({ timeout: 1000 }).catch(() => false)) {
    const errorText = await errorElement.textContent();
    throw new Error(`Login failed: ${errorText}`);
  }
  
  // Wait for redirect or admin bar
  await Promise.race([
    page.waitForURL('**/wp-admin/**', { timeout: 10000 }),
    page.locator('#wpadminbar').waitFor({ state: 'visible', timeout: 10000 })
  ]);
  
  // Verify we're logged in
  const isLoggedIn = await page.locator('#wpadminbar').isVisible({ timeout: 5000 }).catch(() => false);
  if (!isLoggedIn && !page.url().includes('/wp-admin/')) {
    throw new Error('Failed to authenticate as jury admin');
  }
  
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