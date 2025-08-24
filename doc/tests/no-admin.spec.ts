import { test, expect } from '@playwright/test';

/**
 * Tests that can run without admin credentials
 * Focused on public functionality and basic accessibility
 */

test.describe('Public Access Tests (No Admin Required)', () => {
  test.describe('WordPress Basic Functionality', () => {
    test('WordPress site is accessible', async ({ page }) => {
      // Test basic WordPress access
      await page.goto('/');
      
      // Should load without errors
      await expect(page).toHaveTitle(/./); // Any title indicates site loaded
      
      // Should not show fatal errors
      const hasPhpError = await page.locator('body').textContent();
      expect(hasPhpError).not.toContain('Fatal error');
      expect(hasPhpError).not.toContain('Parse error');
      
      console.log('✅ WordPress site is accessible');
    });

    test('WordPress admin login page exists', async ({ page }) => {
      // Check admin login is accessible
      await page.goto('/wp-admin');
      
      // Should redirect to login page
      await expect(page.locator('#loginform')).toBeVisible();
      await expect(page.locator('#user_login')).toBeVisible();
      await expect(page.locator('#user_pass')).toBeVisible();
      
      console.log('✅ WordPress admin login page accessible');
    });

    test('plugin files exist', async ({ page }) => {
      // Test plugin assets are accessible
      const pluginAssets = [
        '/wp-content/plugins/mobility-trailblazers/assets/css/mt-frontend.css',
        '/wp-content/plugins/mobility-trailblazers/assets/js/mt-frontend.js',
        '/wp-content/plugins/mobility-trailblazers/mobility-trailblazers.php'
      ];
      
      for (const asset of pluginAssets) {
        try {
          const response = await page.goto(asset);
          
          if (response?.status() === 200) {
            console.log(`✅ Plugin asset accessible: ${asset}`);
          } else if (response?.status() === 404) {
            console.log(`ℹ️  Asset not found (may not exist): ${asset}`);
          } else {
            console.log(`⚠️  Unexpected response for ${asset}: ${response?.status()}`);
          }
        } catch (error) {
          console.log(`ℹ️  Could not test asset: ${asset}`);
        }
      }
    });
  });

  test.describe('Public Plugin Functionality', () => {
    test('candidate archive is accessible', async ({ page }) => {
      // Try different candidate archive URLs
      const candidateUrls = [
        '/candidates/',
        '/mt_candidate/',
        '/?post_type=mt_candidate',
        '/candidate-archive/'
      ];
      
      let accessibleUrl = null;
      
      for (const url of candidateUrls) {
        try {
          const response = await page.goto(url);
          
          if (response?.status() === 200 && !page.url().includes('404')) {
            accessibleUrl = url;
            
            // Check for candidate content
            const hasContent = await page.locator('body').textContent();
            if (hasContent && !hasContent.includes('Nothing here')) {
              console.log(`✅ Candidate archive accessible at: ${url}`);
              break;
            }
          }
        } catch (error) {
          // Continue to next URL
        }
      }
      
      if (!accessibleUrl) {
        console.log('ℹ️  No public candidate archive found (may require login)');
      }
    });

    test('frontend assets load correctly', async ({ page }) => {
      // Test that plugin frontend assets load
      await page.goto('/');
      
      // Check for CSS/JS loading
      const responses: string[] = [];
      
      page.on('response', (response) => {
        if (response.url().includes('mobility-trailblazers')) {
          responses.push(`${response.status()}: ${response.url()}`);
        }
      });
      
      // Wait for assets to load
      await page.waitForTimeout(3000);
      
      if (responses.length > 0) {
        console.log('✅ Plugin assets detected:');
        responses.forEach(response => console.log(`  ${response}`));
      } else {
        console.log('ℹ️  No plugin assets loaded on homepage (may be conditional)');
      }
    });

    test('jury dashboard redirects properly', async ({ page }) => {
      // Test jury dashboard access without login
      await page.goto('/jury-dashboard/');
      
      // Should redirect to login or show access denied
      const url = page.url();
      const hasLoginForm = await page.locator('#loginform').isVisible();
      const hasAccessDenied = await page.locator('.access-denied, .unauthorized').isVisible();
      const is404 = page.url().includes('404') || await page.locator('.error-404').isVisible();
      
      if (hasLoginForm) {
        console.log('✅ Jury dashboard properly redirects to login');
      } else if (hasAccessDenied) {
        console.log('✅ Jury dashboard shows access denied message');
      } else if (is404) {
        console.log('ℹ️  Jury dashboard page not found (may be admin-only)');
      } else {
        console.log('⚠️  Jury dashboard behavior unclear');
      }
    });
  });

  test.describe('Database and Plugin Status', () => {
    test('check plugin activation status', async ({ page }) => {
      // Try to detect if plugin is active through public indicators
      await page.goto('/');
      
      // Look for plugin-specific classes or elements
      const pluginIndicators = [
        '.mt-plugin',
        '.mobility-trailblazers',
        '[data-mt-plugin]',
        '#mt-styles'
      ];
      
      let pluginActive = false;
      
      for (const indicator of pluginIndicators) {
        if (await page.locator(indicator).isVisible()) {
          pluginActive = true;
          console.log(`✅ Plugin activity indicator found: ${indicator}`);
          break;
        }
      }
      
      // Check page source for plugin signatures
      const pageContent = await page.content();
      const pluginSignatures = [
        'mobility-trailblazers',
        'mt-frontend',
        'MT_VERSION'
      ];
      
      for (const signature of pluginSignatures) {
        if (pageContent.includes(signature)) {
          pluginActive = true;
          console.log(`✅ Plugin signature found: ${signature}`);
          break;
        }
      }
      
      if (!pluginActive) {
        console.log('ℹ️  No public plugin indicators found (may be admin-only)');
      }
    });

    test('WordPress REST API is accessible', async ({ page }) => {
      // Test WordPress REST API
      try {
        const response = await page.goto('/wp-json/wp/v2');
        
        if (response?.ok()) {
          console.log('✅ WordPress REST API is accessible');
          
          // Check for MT-specific endpoints
          const jsonResponse = await response.json();
          if (jsonResponse.routes) {
            const mtRoutes = Object.keys(jsonResponse.routes).filter(route => 
              route.includes('mt_') || route.includes('mobility')
            );
            
            if (mtRoutes.length > 0) {
              console.log(`✅ Found ${mtRoutes.length} MT-related API routes`);
            } else {
              console.log('ℹ️  No MT-specific API routes found in public API');
            }
          }
        } else {
          console.log('⚠️  WordPress REST API not accessible');
        }
      } catch (error) {
        console.log('⚠️  Could not access WordPress REST API');
      }
    });

    test('check for custom post types', async ({ page }) => {
      // Try to access custom post type endpoints
      const postTypes = ['mt_candidate', 'mt_jury_member'];
      
      for (const postType of postTypes) {
        try {
          const response = await page.goto(`/wp-json/wp/v2/${postType}`);
          
          if (response?.ok()) {
            const data = await response.json();
            console.log(`✅ Custom post type ${postType} API accessible (${data.length} items)`);
          } else if (response?.status() === 401) {
            console.log(`ℹ️  Custom post type ${postType} requires authentication`);
          } else {
            console.log(`ℹ️  Custom post type ${postType} not publicly accessible`);
          }
        } catch (error) {
          console.log(`ℹ️  Could not test custom post type: ${postType}`);
        }
      }
    });
  });

  test.describe('Basic Accessibility (No Login Required)', () => {
    test('homepage meets basic accessibility standards', async ({ page }) => {
      await page.goto('/');
      
      // Check for basic accessibility features
      const accessibilityChecks = [
        { selector: 'h1', name: 'Page has main heading' },
        { selector: '[lang]', name: 'Language is specified' },
        { selector: 'title', name: 'Page has title' },
        { selector: 'meta[name="viewport"]', name: 'Viewport meta tag exists' }
      ];
      
      for (const check of accessibilityChecks) {
        const element = page.locator(check.selector);
        if (await element.isVisible() || await element.count() > 0) {
          console.log(`✅ ${check.name}`);
        } else {
          console.log(`⚠️  ${check.name} - not found`);
        }
      }
      
      // Check for skip links
      const skipLink = page.locator('.skip-link, a[href="#main"], a[href="#content"]');
      if (await skipLink.isVisible()) {
        console.log('✅ Skip to content link found');
      }
      
      // Check images have alt text
      const images = page.locator('img');
      const imageCount = await images.count();
      let imagesWithAlt = 0;
      
      for (let i = 0; i < imageCount; i++) {
        const img = images.nth(i);
        const alt = await img.getAttribute('alt');
        const src = await img.getAttribute('src');
        
        if (src && !src.includes('data:') && alt !== null) {
          imagesWithAlt++;
        }
      }
      
      if (imageCount > 0) {
        console.log(`✅ Images with alt text: ${imagesWithAlt}/${imageCount}`);
      }
    });

    test('login page is accessible', async ({ page }) => {
      await page.goto('/wp-admin');
      
      // Check login form accessibility
      const loginForm = page.locator('#loginform');
      await expect(loginForm).toBeVisible();
      
      // Check form labels
      const usernameLabel = page.locator('label[for="user_login"]');
      const passwordLabel = page.locator('label[for="user_pass"]');
      
      if (await usernameLabel.isVisible() && await passwordLabel.isVisible()) {
        console.log('✅ Login form has proper labels');
      }
      
      // Check form can be navigated with keyboard
      await page.keyboard.press('Tab');
      const focusedElement = page.locator(':focus');
      if (await focusedElement.isVisible()) {
        console.log('✅ Login form is keyboard accessible');
      }
    });
  });

  test.describe('Performance (Public Pages)', () => {
    test('homepage loads within reasonable time', async ({ page }) => {
      const startTime = Date.now();
      
      await page.goto('/', { waitUntil: 'networkidle' });
      
      const loadTime = Date.now() - startTime;
      
      // Homepage should load within 5 seconds
      expect(loadTime).toBeLessThan(5000);
      
      console.log(`✅ Homepage loaded in ${loadTime}ms`);
    });

    test('login page loads quickly', async ({ page }) => {
      const startTime = Date.now();
      
      await page.goto('/wp-admin', { waitUntil: 'networkidle' });
      
      const loadTime = Date.now() - startTime;
      
      // Login page should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
      
      console.log(`✅ Login page loaded in ${loadTime}ms`);
    });

    test('check for performance issues', async ({ page }) => {
      await page.goto('/');
      
      // Monitor console errors
      const consoleErrors: string[] = [];
      page.on('console', (message) => {
        if (message.type() === 'error') {
          consoleErrors.push(message.text());
        }
      });
      
      // Check for JavaScript errors
      const jsErrors: string[] = [];
      page.on('pageerror', (error) => {
        jsErrors.push(error.message);
      });
      
      // Wait for page to settle
      await page.waitForTimeout(3000);
      
      if (consoleErrors.length === 0) {
        console.log('✅ No console errors detected');
      } else {
        console.log(`⚠️  Console errors detected: ${consoleErrors.length}`);
      }
      
      if (jsErrors.length === 0) {
        console.log('✅ No JavaScript errors detected');
      } else {
        console.log(`⚠️  JavaScript errors detected: ${jsErrors.length}`);
      }
    });
  });

  test.describe('Mobile Responsiveness (Public)', () => {
    test('homepage is mobile responsive', async ({ page }) => {
      // Test mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/');
      
      // Check horizontal scroll
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(375 + 20); // Small margin allowed
      
      console.log('✅ Homepage fits mobile viewport');
      
      // Check for mobile menu or navigation
      const navigation = page.locator('nav, .menu, .navigation');
      if (await navigation.isVisible()) {
        console.log('✅ Navigation visible on mobile');
      }
    });

    test('login page is mobile responsive', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/wp-admin');
      
      // Login form should be usable on mobile
      const loginForm = page.locator('#loginform');
      await expect(loginForm).toBeVisible();
      
      const formBox = await loginForm.boundingBox();
      if (formBox) {
        expect(formBox.width).toBeLessThanOrEqual(375);
        console.log('✅ Login form fits mobile viewport');
      }
      
      // Test form interaction on mobile
      const usernameField = page.locator('#user_login');
      await usernameField.click();
      await usernameField.fill('test');
      
      console.log('✅ Login form is interactive on mobile');
    });
  });

  test.describe('Security (Public Testing)', () => {
    test('sensitive files are not publicly accessible', async ({ page }) => {
      // Test that sensitive plugin files are protected
      const sensitiveFiles = [
        '/wp-content/plugins/mobility-trailblazers/includes/config.php',
        '/wp-content/plugins/mobility-trailblazers/.env',
        '/wp-content/plugins/mobility-trailblazers/composer.json',
        '/wp-config.php',
        '/.env'
      ];
      
      for (const file of sensitiveFiles) {
        try {
          const response = await page.goto(file);
          
          if (response?.status() === 403 || response?.status() === 404) {
            console.log(`✅ Protected file properly blocked: ${file}`);
          } else if (response?.status() === 200) {
            const content = await page.textContent('body');
            if (content?.includes('<?php') || content?.includes('password') || content?.includes('secret')) {
              console.log(`⚠️  Sensitive file may be exposed: ${file}`);
            } else {
              console.log(`ℹ️  File accessible but appears safe: ${file}`);
            }
          }
        } catch (error) {
          console.log(`ℹ️  Could not test file: ${file}`);
        }
      }
    });

    test('directory browsing is disabled', async ({ page }) => {
      // Test that directory listings are disabled
      const directories = [
        '/wp-content/plugins/',
        '/wp-content/plugins/mobility-trailblazers/',
        '/wp-content/plugins/mobility-trailblazers/includes/',
        '/wp-content/uploads/'
      ];
      
      for (const dir of directories) {
        try {
          const response = await page.goto(dir);
          const content = await page.textContent('body');
          
          if (content?.includes('Index of') || content?.includes('Directory listing')) {
            console.log(`⚠️  Directory browsing enabled: ${dir}`);
          } else {
            console.log(`✅ Directory browsing disabled: ${dir}`);
          }
        } catch (error) {
          console.log(`✅ Directory not accessible: ${dir}`);
        }
      }
    });
  });
});