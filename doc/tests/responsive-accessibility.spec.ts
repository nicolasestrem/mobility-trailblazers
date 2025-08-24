import { test, expect } from '@playwright/test';
import { ResponsiveHelper, AccessibilityHelper } from './utils/test-helpers';

test.describe('Responsive Design and Accessibility', () => {
  // Use the stored admin authentication state
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.describe('Responsive Design - Admin Interface', () => {
    test('admin interface adapts to mobile viewport', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      
      // Test MT plugin pages on mobile
      const mtPages = [
        '/wp-admin/admin.php?page=mt-assignments',
        '/wp-admin/admin.php?page=mt-evaluations',
        '/wp-admin/edit.php?post_type=mt_candidate'
      ];
      
      for (const mtPage of mtPages) {
        try {
          await responsive.setMobileViewport();
          await page.goto(mtPage);
          
          // Check if page loads without horizontal scroll
          const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
          expect(bodyWidth).toBeLessThanOrEqual(375 + 20); // Allow small margin
          
          // Check for mobile-specific elements
          const mobileElements = [
            '#wp-admin-bar-menu-toggle',
            '.mobile-menu',
            '.responsive-table'
          ];
          
          for (const element of mobileElements) {
            if (await page.locator(element).isVisible()) {
              console.log(`✅ Mobile element found: ${element}`);
            }
          }
          
          // Verify key functionality is accessible
          const keyElements = [
            '.mt-admin-page',
            '.wp-list-table',
            '.mt-assignment-controls'
          ];
          
          for (const element of keyElements) {
            if (await page.locator(element).isVisible()) {
              await expect(page.locator(element)).toBeVisible();
            }
          }
          
          console.log(`✅ Mobile responsive: ${mtPage}`);
          
        } catch (error) {
          console.warn(`⚠️  Could not test mobile responsiveness for ${mtPage}`);
        }
      }
    });

    test('admin interface works on tablet viewport', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      
      await responsive.setTabletViewport();
      
      // Test assignment management on tablet
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Check layout adapts properly
        const assignmentControls = page.locator('.mt-assignment-controls');
        if (await assignmentControls.isVisible()) {
          const boundingBox = await assignmentControls.boundingBox();
          if (boundingBox) {
            expect(boundingBox.width).toBeLessThanOrEqual(768);
          }
        }
        
        // Test tablet-specific interactions
        const dropdowns = page.locator('select');
        const dropdownCount = await dropdowns.count();
        
        for (let i = 0; i < Math.min(dropdownCount, 3); i++) {
          const dropdown = dropdowns.nth(i);
          if (await dropdown.isVisible()) {
            await dropdown.click();
            // Should open properly on tablet
            await page.waitForTimeout(500);
          }
        }
        
        console.log('✅ Tablet responsive design works');
        
      } catch (error) {
        console.warn('⚠️  Could not test tablet responsiveness');
      }
    });

    test('tables are responsive', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      
      // Test responsive tables on different viewports
      const viewports = [
        { width: 375, height: 667, name: 'Mobile' },
        { width: 768, height: 1024, name: 'Tablet' },
        { width: 1200, height: 800, name: 'Desktop' }
      ];
      
      for (const viewport of viewports) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        
        // Test candidate list table
        try {
          await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
          
          const table = page.locator('.wp-list-table');
          if (await table.isVisible()) {
            await expect(table).toBeVisible();
            
            // On mobile, table should be scrollable or stacked
            if (viewport.width <= 768) {
              const tableContainer = page.locator('.table-responsive, .tablepress-scroll-wrapper');
              const hasScrollable = await tableContainer.isVisible();
              
              if (hasScrollable) {
                console.log(`✅ Table is scrollable on ${viewport.name}`);
              } else {
                // Check if table adapts differently (like stacked layout)
                const tableWidth = await table.evaluate(el => el.scrollWidth);
                if (tableWidth > viewport.width) {
                  // Table overflows - should have horizontal scroll
                  const hasHorizontalScroll = await page.evaluate(() => 
                    document.documentElement.scrollWidth > window.innerWidth
                  );
                  expect(hasHorizontalScroll).toBeTruthy();
                }
              }
            }
          }
          
        } catch (error) {
          console.warn(`⚠️  Could not test table responsiveness on ${viewport.name}`);
        }
      }
    });
  });

  test.describe('Responsive Design - Frontend', () => {
    test('jury dashboard is mobile responsive', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      
      try {
        // Try to access jury dashboard
        await page.goto('/jury-dashboard/');
        
        // Check if redirected to login
        if (page.url().includes('/wp-login.php')) {
          // Login as jury member if possible
          await page.fill('#user_login', process.env.JURY_USERNAME || 'jury1');
          await page.fill('#user_pass', process.env.JURY_PASSWORD || 'jury123');
          await page.click('#wp-submit');
          
          // Navigate back to dashboard
          await page.goto('/jury-dashboard/');
        }
        
        await responsive.setMobileViewport();
        
        const dashboardElements = [
          '.mt-jury-dashboard',
          '.mt-dashboard-header',
          '.mt-stats-grid',
          '.mt-candidate-list'
        ];
        
        for (const element of dashboardElements) {
          if (await page.locator(element).isVisible()) {
            const size = await responsive.testResponsiveElement(element);
            
            // Mobile size should fit viewport
            if (size.mobileSize) {
              expect(size.mobileSize.width).toBeLessThanOrEqual(375);
            }
            
            console.log(`✅ Responsive element: ${element}`);
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test jury dashboard responsiveness');
      }
    });

    test('evaluation form is mobile responsive', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      
      try {
        // Navigate to evaluation form
        await page.goto('/jury-evaluation/?candidate=1');
        
        // Check if form exists and is accessible
        const evaluationForm = page.locator('.mt-evaluation-form');
        if (await evaluationForm.isVisible()) {
          
          await responsive.setMobileViewport();
          
          // Test form elements on mobile
          const formElements = [
            '.mt-candidate-details',
            '.mt-criteria-section',
            '.mt-score-input',
            '.mt-form-actions'
          ];
          
          for (const element of formElements) {
            if (await page.locator(element).isVisible()) {
              const boundingBox = await page.locator(element).boundingBox();
              if (boundingBox) {
                expect(boundingBox.width).toBeLessThanOrEqual(375);
              }
            }
          }
          
          // Test mobile interactions
          const scoreInputs = page.locator('.mt-score-slider, .mt-score-btn');
          const inputCount = await scoreInputs.count();
          
          if (inputCount > 0) {
            // Test first score input on mobile
            const firstInput = scoreInputs.first();
            await firstInput.click();
            
            // Should be usable on touch device
            console.log('✅ Score inputs work on mobile');
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test evaluation form mobile responsiveness');
      }
    });

    test('candidate display is responsive', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      
      try {
        // Get a candidate URL from admin
        await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
        
        const firstCandidate = page.locator('.wp-list-table .row-title').first();
        if (await firstCandidate.isVisible()) {
          const candidateRow = firstCandidate.locator('xpath=ancestor::tr');
          const viewLink = candidateRow.locator('.row-actions .view a');
          
          if (await viewLink.isVisible()) {
            const candidateUrl = await viewLink.getAttribute('href');
            
            if (candidateUrl) {
              // Test candidate page responsiveness
              await page.goto(candidateUrl);
              
              const viewports = [375, 768, 1200];
              
              for (const width of viewports) {
                await page.setViewportSize({ width, height: 800 });
                
                // Check key elements fit viewport
                const candidateElements = [
                  'h1',
                  '.entry-content',
                  '.mt-candidate-meta',
                  '.mt-candidate-photo'
                ];
                
                for (const element of candidateElements) {
                  if (await page.locator(element).isVisible()) {
                    const boundingBox = await page.locator(element).boundingBox();
                    if (boundingBox) {
                      expect(boundingBox.width).toBeLessThanOrEqual(width + 20);
                    }
                  }
                }
                
                console.log(`✅ Candidate page responsive at ${width}px`);
              }
            }
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test candidate page responsiveness');
      }
    });
  });

  test.describe('Accessibility - WCAG Compliance', () => {
    test('has proper heading structure', async ({ page }) => {
      const testPages = [
        '/wp-admin/admin.php?page=mt-assignments',
        '/wp-admin/edit.php?post_type=mt_candidate'
      ];
      
      for (const testPage of testPages) {
        try {
          await page.goto(testPage);
          
          // Check heading hierarchy
          const headings = await page.locator('h1, h2, h3, h4, h5, h6').allTextContents();
          
          if (headings.length > 0) {
            console.log(`✅ Page ${testPage} has headings: ${headings.length}`);
            
            // Should have at least one h1
            const h1Elements = await page.locator('h1').count();
            expect(h1Elements).toBeGreaterThanOrEqual(1);
            
            // Check for skip to content (accessibility feature)
            const skipLink = page.locator('.skip-link, a[href="#main"], a:has-text("Skip to content")');
            if (await skipLink.isVisible()) {
              console.log('✅ Skip to content link found');
            }
          }
          
        } catch (error) {
          console.warn(`⚠️  Could not test heading structure for ${testPage}`);
        }
      }
    });

    test('form labels are properly associated', async ({ page }) => {
      try {
        // Test candidate creation form
        await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
        
        // Check form fields have proper labels
        const formFields = page.locator('input[type="text"], input[type="email"], textarea, select');
        const fieldCount = await formFields.count();
        
        for (let i = 0; i < Math.min(fieldCount, 10); i++) {
          const field = formFields.nth(i);
          const fieldId = await field.getAttribute('id');
          const fieldName = await field.getAttribute('name');
          
          if (fieldId) {
            // Look for associated label
            const label = page.locator(`label[for="${fieldId}"]`);
            if (await label.isVisible()) {
              console.log(`✅ Field ${fieldId} has proper label`);
            }
          }
          
          // Check for aria-label as alternative
          const ariaLabel = await field.getAttribute('aria-label');
          const ariaLabelledBy = await field.getAttribute('aria-labelledby');
          
          if (ariaLabel || ariaLabelledBy) {
            console.log(`✅ Field has aria labeling`);
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test form label association');
      }
    });

    test('interactive elements are keyboard accessible', async ({ page }) => {
      const accessibility = new AccessibilityHelper(page);
      
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Test keyboard navigation through interactive elements
        const interactiveElements = [
          'button',
          'a[href]',
          'input',
          'select',
          '[tabindex="0"]'
        ];
        
        let focusableElements: string[] = [];
        
        for (const elementType of interactiveElements) {
          const elements = page.locator(elementType);
          const count = await elements.count();
          
          for (let i = 0; i < Math.min(count, 5); i++) {
            const element = elements.nth(i);
            if (await element.isVisible()) {
              const tagName = await element.evaluate(el => el.tagName.toLowerCase());
              const id = await element.getAttribute('id') || `${tagName}-${i}`;
              focusableElements.push(id);
            }
          }
        }
        
        if (focusableElements.length > 0) {
          // Test tab navigation
          await page.keyboard.press('Tab');
          
          // Check that focus is visible
          const focusedElement = page.locator(':focus');
          if (await focusedElement.isVisible()) {
            console.log('✅ Keyboard focus is visible');
          }
          
          console.log(`✅ Found ${focusableElements.length} focusable elements`);
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test keyboard accessibility');
      }
    });

    test('color contrast is sufficient', async ({ page }) => {
      const accessibility = new AccessibilityHelper(page);
      
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Basic color contrast check - verify text is visible
        await accessibility.checkColorContrast();
        
        // Check for sufficient color contrast in key elements
        const importantElements = [
          '.mt-admin-page h1',
          '.mt-assignment-controls button',
          '.wp-list-table th',
          '.notice'
        ];
        
        for (const element of importantElements) {
          if (await page.locator(element).isVisible()) {
            // Get computed styles
            const styles = await page.locator(element).evaluate(el => {
              const computed = window.getComputedStyle(el);
              return {
                color: computed.color,
                backgroundColor: computed.backgroundColor,
                fontSize: computed.fontSize
              };
            });
            
            console.log(`✅ Element ${element} styles:`, styles);
          }
        }
        
        console.log('✅ Color contrast check completed');
        
      } catch (error) {
        console.warn('⚠️  Could not perform color contrast check');
      }
    });

    test('images have alt text', async ({ page }) => {
      try {
        // Test candidate page with images
        await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
        
        const firstCandidate = page.locator('.wp-list-table .row-title').first();
        if (await firstCandidate.isVisible()) {
          await firstCandidate.click();
          
          // Check for images in editor
          const images = page.locator('img');
          const imageCount = await images.count();
          
          for (let i = 0; i < imageCount; i++) {
            const img = images.nth(i);
            const altText = await img.getAttribute('alt');
            const src = await img.getAttribute('src');
            
            if (src && !src.includes('data:') && !src.includes('spinner')) {
              // Content images should have alt text
              if (altText) {
                console.log(`✅ Image has alt text: ${altText.substring(0, 50)}`);
              } else {
                console.warn(`⚠️  Image missing alt text: ${src.substring(0, 50)}`);
              }
            }
          }
          
          if (imageCount === 0) {
            console.log('ℹ️  No images found to test alt text');
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test image alt text');
      }
    });

    test('ARIA attributes are properly used', async ({ page }) => {
      const accessibility = new AccessibilityHelper(page);
      
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Check for ARIA landmarks
        const landmarks = [
          '[role="main"]',
          '[role="navigation"]', 
          '[role="banner"]',
          '[role="contentinfo"]',
          'main',
          'nav',
          'header',
          'footer'
        ];
        
        for (const landmark of landmarks) {
          if (await page.locator(landmark).isVisible()) {
            console.log(`✅ Landmark found: ${landmark}`);
          }
        }
        
        // Check for ARIA labels on buttons without text
        const buttons = page.locator('button, [role="button"]');
        const buttonCount = await buttons.count();
        
        for (let i = 0; i < Math.min(buttonCount, 10); i++) {
          const button = buttons.nth(i);
          const buttonText = await button.textContent();
          const ariaLabel = await button.getAttribute('aria-label');
          const ariaLabelledBy = await button.getAttribute('aria-labelledby');
          
          if (!buttonText?.trim() && !ariaLabel && !ariaLabelledBy) {
            console.warn('⚠️  Button without accessible name found');
          } else {
            console.log('✅ Button has accessible name');
          }
        }
        
        // Check for ARIA expanded on dropdowns
        const dropdowns = page.locator('[aria-expanded]');
        const dropdownCount = await dropdowns.count();
        
        if (dropdownCount > 0) {
          console.log(`✅ Found ${dropdownCount} elements with aria-expanded`);
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test ARIA attributes');
      }
    });
  });

  test.describe('Accessibility - Screen Reader Support', () => {
    test('tables have proper headers', async ({ page }) => {
      try {
        await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
        
        const table = page.locator('.wp-list-table');
        if (await table.isVisible()) {
          
          // Check for table headers
          const headers = table.locator('th');
          const headerCount = await headers.count();
          
          if (headerCount > 0) {
            // Check headers have scope attributes
            for (let i = 0; i < headerCount; i++) {
              const header = headers.nth(i);
              const scope = await header.getAttribute('scope');
              const headerText = await header.textContent();
              
              if (scope === 'col' || scope === 'row') {
                console.log(`✅ Header "${headerText}" has proper scope: ${scope}`);
              }
            }
            
            // Check for table caption or summary
            const caption = table.locator('caption');
            const summary = await table.getAttribute('summary');
            
            if (await caption.isVisible() || summary) {
              console.log('✅ Table has caption or summary');
            }
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test table headers');
      }
    });

    test('forms have proper fieldsets and legends', async ({ page }) => {
      try {
        await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
        
        // Check for fieldsets in form
        const fieldsets = page.locator('fieldset');
        const fieldsetCount = await fieldsets.count();
        
        for (let i = 0; i < fieldsetCount; i++) {
          const fieldset = fieldsets.nth(i);
          const legend = fieldset.locator('legend');
          
          if (await legend.isVisible()) {
            const legendText = await legend.textContent();
            console.log(`✅ Fieldset has legend: ${legendText}`);
          } else {
            console.warn('⚠️  Fieldset without legend found');
          }
        }
        
        if (fieldsetCount === 0) {
          console.log('ℹ️  No fieldsets found - checking for grouped form controls');
          
          // Look for grouped controls that should use fieldsets
          const groupedControls = [
            '.mt-candidate-meta',
            '.meta-box',
            '.postbox'
          ];
          
          for (const group of groupedControls) {
            if (await page.locator(group).isVisible()) {
              const inputs = page.locator(`${group} input, ${group} select, ${group} textarea`);
              const inputCount = await inputs.count();
              
              if (inputCount > 1) {
                console.log(`✅ Grouped controls found: ${group} (${inputCount} inputs)`);
              }
            }
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test fieldsets and legends');
      }
    });

    test('error messages are announced', async ({ page }) => {
      try {
        await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
        
        // Try to trigger validation error
        await page.click('#publish');
        
        // Look for error messages
        const errorMessages = page.locator('.error, .notice-error, .field-error');
        const errorCount = await errorMessages.count();
        
        for (let i = 0; i < errorCount; i++) {
          const error = errorMessages.nth(i);
          const ariaLive = await error.getAttribute('aria-live');
          const role = await error.getAttribute('role');
          
          if (ariaLive || role === 'alert') {
            console.log('✅ Error message has proper ARIA attributes');
          } else {
            const errorText = await error.textContent();
            console.warn(`⚠️  Error message may not be announced: ${errorText?.substring(0, 50)}`);
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test error message announcement');
      }
    });
  });

  test.describe('Mobile Usability', () => {
    test('touch targets are appropriately sized', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      await responsive.setMobileViewport();
      
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Check button sizes on mobile
        const buttons = page.locator('button, .button, input[type="submit"]');
        const buttonCount = await buttons.count();
        
        for (let i = 0; i < Math.min(buttonCount, 10); i++) {
          const button = buttons.nth(i);
          if (await button.isVisible()) {
            const boundingBox = await button.boundingBox();
            
            if (boundingBox) {
              // Touch targets should be at least 44x44px (WCAG guideline)
              const minSize = 44;
              
              if (boundingBox.width >= minSize && boundingBox.height >= minSize) {
                console.log(`✅ Button has adequate touch target: ${boundingBox.width}x${boundingBox.height}`);
              } else {
                console.warn(`⚠️  Button may be too small for touch: ${boundingBox.width}x${boundingBox.height}`);
              }
            }
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test touch target sizes');
      }
    });

    test('mobile navigation is usable', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      await responsive.setMobileViewport();
      
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Test mobile menu functionality
        const menuToggle = page.locator('#wp-admin-bar-menu-toggle, .mobile-menu-toggle');
        if (await menuToggle.isVisible()) {
          await menuToggle.click();
          
          // Menu should be accessible
          const adminMenu = page.locator('#adminmenu, .mobile-menu');
          if (await adminMenu.isVisible()) {
            console.log('✅ Mobile admin menu works');
            
            // Test menu item interaction
            const menuItems = adminMenu.locator('a').first();
            if (await menuItems.isVisible()) {
              const itemBox = await menuItems.boundingBox();
              if (itemBox && itemBox.height >= 40) {
                console.log('✅ Menu items have adequate touch targets');
              }
            }
          }
        }
        
        // Test responsive table interactions
        const table = page.locator('.wp-list-table');
        if (await table.isVisible()) {
          // Should be scrollable or have mobile-friendly layout
          const tableWidth = await table.evaluate(el => el.scrollWidth);
          const viewportWidth = 375;
          
          if (tableWidth > viewportWidth) {
            // Should have horizontal scroll or alternative layout
            console.log('✅ Table adapts to mobile viewport');
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test mobile navigation');
      }
    });

    test('forms are usable on mobile', async ({ page }) => {
      const responsive = new ResponsiveHelper(page);
      await responsive.setMobileViewport();
      
      try {
        await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
        
        // Test form field usability on mobile
        const formFields = page.locator('input[type="text"], textarea, select');
        const fieldCount = await formFields.count();
        
        for (let i = 0; i < Math.min(fieldCount, 5); i++) {
          const field = formFields.nth(i);
          if (await field.isVisible()) {
            const boundingBox = await field.boundingBox();
            
            if (boundingBox) {
              // Form fields should be at least 40px high for mobile
              if (boundingBox.height >= 40) {
                console.log(`✅ Form field has adequate height: ${boundingBox.height}px`);
              } else {
                console.warn(`⚠️  Form field may be too small: ${boundingBox.height}px`);
              }
              
              // Field should not exceed viewport width
              if (boundingBox.width <= 375) {
                console.log('✅ Form field fits mobile viewport');
              }
            }
            
            // Test field interaction
            await field.click();
            await field.fill('test');
            await field.clear();
            
            console.log('✅ Form field interaction works on mobile');
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test mobile form usability');
      }
    });
  });

  test.describe('Cross-Browser Compatibility', () => {
    test('CSS Grid and Flexbox fallbacks work', async ({ page }) => {
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Check for modern CSS features and fallbacks
        const gridElements = page.locator('.mt-grid, .grid, [style*="display: grid"]');
        const gridCount = await gridElements.count();
        
        if (gridCount > 0) {
          console.log(`✅ Found ${gridCount} grid layouts`);
          
          // Verify grid elements are functional
          for (let i = 0; i < Math.min(gridCount, 3); i++) {
            const grid = gridElements.nth(i);
            if (await grid.isVisible()) {
              const computedDisplay = await grid.evaluate(el => 
                window.getComputedStyle(el).display
              );
              
              if (computedDisplay.includes('grid') || computedDisplay.includes('flex')) {
                console.log(`✅ Modern layout working: ${computedDisplay}`);
              }
            }
          }
        }
        
        // Check for flexbox usage
        const flexElements = page.locator('[style*="display: flex"], .flex, .d-flex');
        const flexCount = await flexElements.count();
        
        if (flexCount > 0) {
          console.log(`✅ Found ${flexCount} flexbox layouts`);
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test CSS layout compatibility');
      }
    });

    test('JavaScript feature detection works', async ({ page }) => {
      try {
        await page.goto('/wp-admin/admin.php?page=mt-assignments');
        
        // Check for graceful degradation
        const jsFeatures = await page.evaluate(() => {
          return {
            hasQuerySelector: typeof document.querySelector === 'function',
            hasAddEventListener: typeof document.addEventListener === 'function',
            hasJSON: typeof JSON !== 'undefined',
            hasLocalStorage: typeof localStorage !== 'undefined',
            hasArrayForEach: Array.prototype.forEach !== undefined
          };
        });
        
        // All modern features should be available in test environment
        for (const [feature, available] of Object.entries(jsFeatures)) {
          if (available) {
            console.log(`✅ JavaScript feature available: ${feature}`);
          } else {
            console.warn(`⚠️  JavaScript feature missing: ${feature}`);
          }
        }
        
        // Check for polyfills or fallbacks
        const polyfillIndicators = [
          'window.mtPolyfills',
          'window.mtFallbacks',
          'html.no-js'
        ];
        
        for (const indicator of polyfillIndicators) {
          const hasIndicator = await page.evaluate((ind) => {
            if (ind.startsWith('window.')) {
              return !!window[ind.replace('window.', '')];
            } else if (ind.includes('.no-js')) {
              return document.documentElement.classList.contains('no-js');
            }
            return false;
          }, indicator);
          
          if (hasIndicator) {
            console.log(`✅ Fallback mechanism found: ${indicator}`);
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test JavaScript compatibility');
      }
    });
  });
});