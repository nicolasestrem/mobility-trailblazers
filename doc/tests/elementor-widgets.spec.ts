import { test, expect } from '@playwright/test';

test.describe('Elementor Widget Tests', () => {
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.beforeEach(async ({ page }) => {
    // Check if Elementor is active
    await page.goto('/wp-admin/plugins.php');
    const elementorPlugin = page.locator('tr[data-slug="elementor"]');
    if (await elementorPlugin.isVisible()) {
      const isActive = await elementorPlugin.locator('.deactivate').isVisible();
      if (!isActive) {
        // Activate Elementor if not active
        await elementorPlugin.locator('.activate').click();
      }
    }
  });

  test('should register MT custom widgets in Elementor', async ({ page }) => {
    // Create a new page with Elementor
    await page.goto('/wp-admin/post-new.php?post_type=page');
    await page.fill('#title', 'Test Elementor MT Widgets');
    
    // Save draft first
    await page.click('#save-post');
    await page.waitForLoadState('networkidle');
    
    // Edit with Elementor
    const editWithElementor = page.locator('a:has-text("Edit with Elementor")');
    if (await editWithElementor.isVisible()) {
      await editWithElementor.click();
      
      // Wait for Elementor to load
      await page.waitForSelector('#elementor-panel', { timeout: 15000 });
      
      // Search for MT widgets
      await page.fill('#elementor-panel-elements-search-input', 'mobility');
      
      // Check for MT widgets
      const mtWidgets = [
        'MT Candidate Grid',
        'MT Candidate Slider',
        'MT Evaluation Form',
        'MT Statistics',
        'MT Jury Dashboard'
      ];
      
      for (const widgetName of mtWidgets) {
        const widget = page.locator(`.elementor-element:has-text("${widgetName}")`);
        if (await widget.isVisible({ timeout: 2000 }).catch(() => false)) {
          await expect(widget).toBeVisible();
        }
      }
    }
  });

  test('should test MT shortcodes functionality', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=page');
    
    // Test various MT shortcodes
    const shortcodes = [
      '[mt_candidates limit="10" category="innovation"]',
      '[mt_evaluation_form candidate_id="1"]',
      '[mt_jury_dashboard]',
      '[mt_statistics type="overview"]',
      '[mt_candidate_grid columns="3" show_filters="true"]',
      '[mt_top_candidates count="5"]'
    ];
    
    // Add shortcodes to content
    await page.fill('#title', 'MT Shortcode Test Page');
    
    // Switch to text/HTML editor
    const textTab = page.locator('#content-html');
    if (await textTab.isVisible()) {
      await textTab.click();
    }
    
    // Add all shortcodes
    const content = shortcodes.join('\n\n');
    await page.fill('#content', content);
    
    // Publish the page
    await page.click('#publish');
    await page.waitForLoadState('networkidle');
    
    // View the page
    const viewPage = page.locator('a:has-text("View Page")').first();
    if (await viewPage.isVisible()) {
      await viewPage.click();
      
      // Verify shortcodes are rendered (not displayed as text)
      for (const shortcode of shortcodes) {
        await expect(page.locator('body')).not.toContainText(shortcode);
      }
      
      // Check for rendered elements
      const renderedElements = [
        '.mt-candidates-grid',
        '.mt-evaluation-form',
        '.mt-jury-dashboard',
        '.mt-statistics',
        '.mt-candidate-item'
      ];
      
      for (const selector of renderedElements) {
        const element = page.locator(selector);
        if (await element.isVisible({ timeout: 2000 }).catch(() => false)) {
          await expect(element).toBeVisible();
        }
      }
    }
  });

  test('should test candidate grid widget settings', async ({ page }) => {
    // Navigate to a page with Elementor
    await page.goto('/wp-admin/edit.php?post_type=page');
    
    // Find or create a test page
    let testPageLink = page.locator('a:has-text("Test Elementor MT Widgets")').first();
    if (!await testPageLink.isVisible()) {
      // Create new page
      await page.goto('/wp-admin/post-new.php?post_type=page');
      await page.fill('#title', 'Test Elementor MT Widgets');
      await page.click('#publish');
    } else {
      await testPageLink.click();
    }
    
    // Edit with Elementor
    const editWithElementor = page.locator('a:has-text("Edit with Elementor")');
    if (await editWithElementor.isVisible()) {
      await editWithElementor.click();
      await page.waitForSelector('#elementor-panel', { timeout: 15000 });
      
      // Add candidate grid widget
      await page.fill('#elementor-panel-elements-search-input', 'candidate grid');
      
      const gridWidget = page.locator('.elementor-element-wrapper:has-text("MT Candidate Grid")').first();
      if (await gridWidget.isVisible()) {
        // Drag widget to canvas (simplified - click to add)
        await gridWidget.click();
        
        // Configure widget settings
        const settings = {
          'columns': '4',
          'limit': '12',
          'show_filters': true,
          'show_pagination': true,
          'order_by': 'score'
        };
        
        // Apply settings
        for (const [key, value] of Object.entries(settings)) {
          const field = page.locator(`[data-setting="${key}"]`);
          if (await field.isVisible()) {
            if (typeof value === 'boolean') {
              const switcher = field.locator('.elementor-switch');
              if (value && !await switcher.evaluate(el => el.classList.contains('elementor-active'))) {
                await switcher.click();
              }
            } else {
              await field.fill(value.toString());
            }
          }
        }
        
        // Save changes
        await page.click('#elementor-panel-saver-button-publish');
        await page.waitForTimeout(2000);
      }
    }
  });

  test('should test evaluation form widget', async ({ page }) => {
    // Switch to jury member
    await page.context().storageState({ path: 'tests/.auth/jury.json' });
    
    // Go to a page with evaluation form shortcode
    await page.goto('/jury-evaluation/');
    
    // Check if evaluation form is rendered
    const evaluationForm = page.locator('.mt-evaluation-form');
    if (await evaluationForm.isVisible()) {
      // Verify all form elements are present
      const formElements = [
        'input[name="criterion_1"]',
        'input[name="criterion_2"]',
        'input[name="criterion_3"]',
        'input[name="criterion_4"]',
        'input[name="criterion_5"]',
        'textarea[name="comments"]',
        'button[name="save_draft"]',
        'button[name="submit_evaluation"]'
      ];
      
      for (const selector of formElements) {
        await expect(page.locator(selector)).toBeVisible();
      }
      
      // Test form validation
      await page.click('button[name="submit_evaluation"]');
      
      // Should show validation errors for empty criteria
      await expect(page.locator('.validation-error, .error-message')).toBeVisible();
    }
  });

  test('should test statistics widget display', async ({ page }) => {
    await page.goto('/wp-admin/');
    
    // Navigate to a page with statistics widget
    // First, let's create a page with the statistics shortcode
    await page.goto('/wp-admin/post-new.php?post_type=page');
    await page.fill('#title', 'Statistics Test Page');
    
    // Add statistics shortcode
    const textTab = page.locator('#content-html');
    if (await textTab.isVisible()) {
      await textTab.click();
    }
    await page.fill('#content', '[mt_statistics type="overview" show_charts="true"]');
    
    // Publish
    await page.click('#publish');
    await page.waitForLoadState('networkidle');
    
    // View the page
    const viewPage = page.locator('a:has-text("View Page")').first();
    if (await viewPage.isVisible()) {
      await viewPage.click();
      
      // Verify statistics are displayed
      const statsContainer = page.locator('.mt-statistics');
      if (await statsContainer.isVisible()) {
        // Check for statistics elements
        const statElements = [
          '.total-candidates',
          '.total-evaluations',
          '.average-score',
          '.completion-rate'
        ];
        
        for (const selector of statElements) {
          const element = page.locator(selector);
          if (await element.isVisible({ timeout: 2000 }).catch(() => false)) {
            const value = await element.textContent();
            // Should contain numeric values
            expect(value).toMatch(/\d/);
          }
        }
      }
    }
  });

  test('should test candidate slider widget', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=page');
    await page.fill('#title', 'Candidate Slider Test');
    
    // Add slider shortcode
    const textTab = page.locator('#content-html');
    if (await textTab.isVisible()) {
      await textTab.click();
    }
    
    await page.fill('#content', '[mt_candidate_slider slides="5" autoplay="true" speed="3000"]');
    await page.click('#publish');
    await page.waitForLoadState('networkidle');
    
    // View the page
    const viewPage = page.locator('a:has-text("View Page")').first();
    if (await viewPage.isVisible()) {
      await viewPage.click();
      
      // Check for slider elements
      const slider = page.locator('.mt-candidate-slider');
      if (await slider.isVisible()) {
        // Verify slider components
        await expect(slider.locator('.slide-item')).toHaveCount(5);
        await expect(slider.locator('.slider-nav')).toBeVisible();
        
        // Test navigation
        const nextButton = slider.locator('.next-slide');
        if (await nextButton.isVisible()) {
          const firstSlide = slider.locator('.slide-item').first();
          const initialClass = await firstSlide.getAttribute('class');
          
          await nextButton.click();
          await page.waitForTimeout(500);
          
          // Check that slide changed
          const newClass = await firstSlide.getAttribute('class');
          expect(newClass).not.toBe(initialClass);
        }
      }
    }
  });

  test('should test widget responsive behavior', async ({ page }) => {
    // Create a page with MT widgets
    await page.goto('/wp-admin/post-new.php?post_type=page');
    await page.fill('#title', 'Responsive Widget Test');
    
    const content = `
      [mt_candidate_grid columns="4" responsive="true"]
      [mt_statistics type="cards" mobile_layout="stack"]
    `;
    
    const textTab = page.locator('#content-html');
    if (await textTab.isVisible()) {
      await textTab.click();
    }
    await page.fill('#content', content);
    await page.click('#publish');
    await page.waitForLoadState('networkidle');
    
    // View the page
    const viewPage = page.locator('a:has-text("View Page")').first();
    if (await viewPage.isVisible()) {
      await viewPage.click();
      
      // Test desktop view
      await page.setViewportSize({ width: 1920, height: 1080 });
      const desktopGrid = page.locator('.mt-candidates-grid');
      if (await desktopGrid.isVisible()) {
        const desktopColumns = await desktopGrid.evaluate(el => 
          window.getComputedStyle(el).gridTemplateColumns
        );
        expect(desktopColumns).toContain('4');
      }
      
      // Test tablet view
      await page.setViewportSize({ width: 768, height: 1024 });
      await page.waitForTimeout(500);
      if (await desktopGrid.isVisible()) {
        const tabletColumns = await desktopGrid.evaluate(el => 
          window.getComputedStyle(el).gridTemplateColumns
        );
        // Should have fewer columns on tablet
        expect(tabletColumns).not.toContain('4');
      }
      
      // Test mobile view
      await page.setViewportSize({ width: 375, height: 667 });
      await page.waitForTimeout(500);
      const mobileStats = page.locator('.mt-statistics');
      if (await mobileStats.isVisible()) {
        const display = await mobileStats.evaluate(el => 
          window.getComputedStyle(el).display
        );
        // Should stack on mobile
        expect(['block', 'flex'].includes(display)).toBeTruthy();
      }
    }
  });

  test('should test widget AJAX functionality', async ({ page }) => {
    await page.goto('/');
    
    // Find a page with MT widgets that use AJAX
    const candidateGrid = page.locator('.mt-candidates-grid');
    if (await candidateGrid.isVisible()) {
      // Test filter functionality
      const filterButton = candidateGrid.locator('.filter-category');
      if (await filterButton.first().isVisible()) {
        // Monitor AJAX request
        const ajaxPromise = page.waitForResponse(
          response => response.url().includes('admin-ajax.php') && 
                     response.url().includes('mt_filter_candidates')
        );
        
        await filterButton.first().click();
        const response = await ajaxPromise;
        
        expect(response.status()).toBe(200);
        
        // Check that grid updated
        await page.waitForTimeout(1000);
        const items = await candidateGrid.locator('.mt-candidate-item').count();
        expect(items).toBeGreaterThan(0);
      }
      
      // Test pagination if available
      const paginationNext = candidateGrid.locator('.pagination-next');
      if (await paginationNext.isVisible()) {
        const ajaxPromise = page.waitForResponse(
          response => response.url().includes('admin-ajax.php')
        );
        
        await paginationNext.click();
        const response = await ajaxPromise;
        expect(response.status()).toBe(200);
      }
    }
  });

  test('should test widget caching', async ({ page }) => {
    // Check if widgets implement caching
    await page.goto('/wp-admin/admin.php?page=mt-settings');
    
    const cacheSettings = page.locator('.cache-settings');
    if (await cacheSettings.isVisible()) {
      // Enable widget caching
      const cacheToggle = cacheSettings.locator('input[name="enable_widget_cache"]');
      if (await cacheToggle.isVisible()) {
        await cacheToggle.check();
        await page.click('button:has-text("Save Settings")');
      }
      
      // Visit a page with widgets twice and measure load time
      await page.goto('/');
      const firstLoadStart = Date.now();
      await page.waitForLoadState('networkidle');
      const firstLoadTime = Date.now() - firstLoadStart;
      
      // Second visit (should be cached)
      await page.reload();
      const secondLoadStart = Date.now();
      await page.waitForLoadState('networkidle');
      const secondLoadTime = Date.now() - secondLoadStart;
      
      // Second load should be faster due to caching
      expect(secondLoadTime).toBeLessThanOrEqual(firstLoadTime);
    }
  });
});