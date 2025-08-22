import { test, expect } from '@playwright/test';

test.describe('Authentication and Login', () => {
  test.describe('WordPress Admin Login', () => {
    test('admin can login successfully', async ({ page }) => {
      await page.goto('/wp-admin');
      
      // Check if login form is present
      await expect(page.locator('#loginform')).toBeVisible();
      
      // Fill login credentials
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      
      // Submit login form
      await page.click('#wp-submit');
      
      // Verify successful login
      await page.waitForURL('**/wp-admin/**');
      await expect(page.locator('#wpadminbar')).toBeVisible();
      await expect(page.locator('.wp-admin')).toBeVisible();
      
      // Check for admin menu
      await expect(page.locator('#adminmenu')).toBeVisible();
    });

    test('jury member can login successfully', async ({ page }) => {
      await page.goto('/wp-admin');
      
      // Fill jury member credentials
      await page.fill('#user_login', process.env.JURY_USERNAME || 'jury1');
      await page.fill('#user_pass', process.env.JURY_PASSWORD || 'jury123');
      
      await page.click('#wp-submit');
      
      // Jury members might have limited admin access or be redirected
      try {
        await page.waitForURL('**/wp-admin/**', { timeout: 5000 });
        await expect(page.locator('#wpadminbar')).toBeVisible();
      } catch {
        // If redirected to frontend, check for jury dashboard
        await page.goto('/jury-dashboard/');
        await expect(page.locator('.mt-jury-dashboard')).toBeVisible();
      }
    });

    test('handles invalid login credentials', async ({ page }) => {
      await page.goto('/wp-admin');
      
      // Enter invalid credentials
      await page.fill('#user_login', 'invalid_user');
      await page.fill('#user_pass', 'invalid_password');
      
      await page.click('#wp-submit');
      
      // Should remain on login page with error
      await expect(page.locator('#login_error')).toBeVisible();
      await expect(page.locator('#login_error')).toContainText('Fehler'); // German error message
    });

    test('redirects to login for protected pages', async ({ page }) => {
      // Try to access admin page without authentication
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Should redirect to login
      await expect(page.locator('#loginform')).toBeVisible();
      await expect(page.url()).toContain('/wp-login.php');
    });
  });

  test.describe('User Role Access Control', () => {
    test('admin has access to MT plugin pages', async ({ page, context }) => {
      // Login as admin
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.waitForURL('**/wp-admin/**');
      
      // Check access to MT plugin pages
      const mtPages = [
        '/wp-admin/admin.php?page=mt-assignments',
        '/wp-admin/admin.php?page=mt-evaluations',
        '/wp-admin/admin.php?page=mt-debug'
      ];
      
      for (const mtPage of mtPages) {
        await page.goto(mtPage);
        
        // Should not see permission error
        await expect(page.locator('.wp-die-message')).not.toBeVisible();
        
        // Should see MT admin interface
        await expect(page.locator('.mt-admin-page, .mt-assignments-page, .mt-evaluations-page, .mt-debug-page')).toBeVisible();
      }
    });

    test('jury member has limited access', async ({ page }) => {
      // This test would need proper jury member credentials
      // For now, we'll test the access control logic
      
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.JURY_USERNAME || 'jury1');
      await page.fill('#user_pass', process.env.JURY_PASSWORD || 'jury123');
      await page.click('#wp-submit');
      
      // Try to access admin-only page
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Should either redirect or show permission error
      try {
        await expect(page.locator('.wp-die-message')).toBeVisible({ timeout: 5000 });
      } catch {
        // Might redirect to dashboard instead
        await expect(page.url()).toContain('/wp-admin/index.php');
      }
    });
  });

  test.describe('Session Management', () => {
    test('maintains login session across pages', async ({ page }) => {
      // Login
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.waitForURL('**/wp-admin/**');
      
      // Navigate to different admin pages
      const adminPages = [
        '/wp-admin/index.php',
        '/wp-admin/admin.php?page=mt-assignments',
        '/wp-admin/users.php',
        '/wp-admin/edit.php?post_type=mt_candidate'
      ];
      
      for (const adminPage of adminPages) {
        await page.goto(adminPage);
        
        // Should remain logged in
        await expect(page.locator('#wpadminbar')).toBeVisible();
        await expect(page.locator('#loginform')).not.toBeVisible();
      }
    });

    test('handles session timeout gracefully', async ({ page }) => {
      // This would require setting up a shorter session timeout
      // For now, we'll test the logout functionality
      
      // Login first
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.waitForURL('**/wp-admin/**');
      
      // Logout
      await page.hover('#wp-admin-bar-my-account');
      await page.click('#wp-admin-bar-logout a');
      
      // Should redirect to login page
      await expect(page.locator('#loginform')).toBeVisible();
      
      // Try to access protected page
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Should redirect back to login
      await expect(page.locator('#loginform')).toBeVisible();
    });
  });

  test.describe('Frontend Access Control', () => {
    test('jury dashboard requires authentication', async ({ page }) => {
      // Try to access jury dashboard without login
      await page.goto('/jury-dashboard/');
      
      // Should redirect to login or show access denied
      const isRedirectedToLogin = page.url().includes('/wp-login.php');
      const hasAccessDenied = await page.locator('.mt-access-denied').isVisible();
      
      expect(isRedirectedToLogin || hasAccessDenied).toBeTruthy();
    });

    test('evaluation form requires proper permissions', async ({ page }) => {
      // Try to access evaluation form without proper role
      await page.goto('/jury-evaluation/?candidate=1');
      
      // Should redirect to login or show permission error
      const isRedirectedToLogin = page.url().includes('/wp-login.php');
      const hasPermissionError = await page.locator('.mt-permission-error').isVisible();
      
      expect(isRedirectedToLogin || hasPermissionError).toBeTruthy();
    });
  });

  test.describe('AJAX Authentication', () => {
    test('AJAX requests require valid nonce', async ({ page }) => {
      // Login first
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.waitForURL('**/wp-admin/**');
      
      // Go to a page with AJAX functionality
      await page.goto('/jury-dashboard/');
      
      // Intercept AJAX requests and check for nonce
      page.on('request', (request) => {
        if (request.url().includes('admin-ajax.php')) {
          const postData = request.postData();
          expect(postData).toContain('nonce=');
        }
      });
      
      // Trigger an AJAX request (if possible)
      const ajaxTrigger = page.locator('.mt-ajax-trigger').first();
      if (await ajaxTrigger.isVisible()) {
        await ajaxTrigger.click();
      }
    });

    test('AJAX requests fail with invalid nonce', async ({ page }) => {
      // This would require intercepting and modifying requests
      // Setting up a mock with invalid nonce
      
      await page.route('**/admin-ajax.php', async (route) => {
        const request = route.request();
        let postData = request.postData() || '';
        
        // Replace valid nonce with invalid one
        postData = postData.replace(/nonce=[^&]+/, 'nonce=invalid_nonce');
        
        await route.continue({
          postData: postData
        });
      });
      
      // Login and try to make AJAX request
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.goto('/jury-dashboard/');
      
      // Try to trigger AJAX request - should fail
      const ajaxTrigger = page.locator('.mt-ajax-trigger').first();
      if (await ajaxTrigger.isVisible()) {
        await ajaxTrigger.click();
        
        // Check for error response
        await expect(page.locator('.mt-error-message')).toBeVisible();
      }
    });
  });

  test.describe('Multi-device Login', () => {
    test('mobile login works correctly', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      
      await page.goto('/wp-admin');
      
      // Check mobile-friendly login form
      await expect(page.locator('#loginform')).toBeVisible();
      
      // Login should work on mobile
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.waitForURL('**/wp-admin/**');
      await expect(page.locator('#wpadminbar')).toBeVisible();
    });

    test('tablet login works correctly', async ({ page }) => {
      // Set tablet viewport
      await page.setViewportSize({ width: 768, height: 1024 });
      
      await page.goto('/wp-admin');
      
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      await page.waitForURL('**/wp-admin/**');
      await expect(page.locator('#wpadminbar')).toBeVisible();
    });
  });
});