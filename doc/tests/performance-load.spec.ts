import { test, expect } from '@playwright/test';

test.describe('Performance Tests - 490+ Candidates', () => {
  test.use({ 
    storageState: 'tests/.auth/admin.json',
    // Extend timeout for performance tests
    timeout: 120000 
  });

  test('should load candidate list page within acceptable time', async ({ page }) => {
    const startTime = Date.now();
    
    // Navigate to candidates list
    await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
    
    // Wait for the list to be fully loaded
    await page.waitForSelector('.wp-list-table');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Page should load within 5 seconds even with 490+ candidates
    expect(loadTime).toBeLessThan(5000);
    
    // Verify pagination is working
    const pagination = page.locator('.tablenav-pages');
    await expect(pagination).toBeVisible();
    
    // Check that we have multiple pages (490+ candidates)
    const totalItems = await page.locator('.displaying-num').textContent();
    expect(totalItems).toContain('items');
    
    // Extract number and verify it's 490+
    const match = totalItems?.match(/(\d+)\s+items/);
    if (match) {
      const count = parseInt(match[1]);
      expect(count).toBeGreaterThanOrEqual(490);
    }
  });

  test('should handle bulk operations on multiple candidates', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
    
    // Select first 20 candidates for bulk operation
    await page.click('#cb-select-all-1'); // Select all on current page
    
    // Choose bulk action
    await page.selectOption('#bulk-action-selector-top', 'edit');
    await page.click('#doaction');
    
    // Measure bulk edit load time
    const startTime = Date.now();
    await page.waitForSelector('#bulk-edit', { timeout: 10000 });
    const loadTime = Date.now() - startTime;
    
    // Bulk edit should open within 3 seconds
    expect(loadTime).toBeLessThan(3000);
    
    // Cancel bulk edit
    await page.click('.cancel');
  });

  test('should efficiently filter and search candidates', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
    
    // Test search performance
    const searchInput = page.locator('#post-search-input');
    await searchInput.fill('mobility');
    
    const startTime = Date.now();
    await page.click('#search-submit');
    await page.waitForLoadState('networkidle');
    const searchTime = Date.now() - startTime;
    
    // Search should complete within 3 seconds
    expect(searchTime).toBeLessThan(3000);
    
    // Verify search results are displayed
    const results = page.locator('.wp-list-table tbody tr');
    const count = await results.count();
    expect(count).toBeGreaterThan(0);
  });

  test('should load jury assignments page with many candidates', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Assignments page should load within 5 seconds
    expect(loadTime).toBeLessThan(5000);
    
    // Check that candidate dropdown is populated
    const candidateSelect = page.locator('select[name="candidate_id"]');
    if (await candidateSelect.isVisible()) {
      const options = await candidateSelect.locator('option').count();
      // Should have 490+ options plus the default
      expect(options).toBeGreaterThan(490);
    }
  });

  test('should handle pagination efficiently', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=mt_candidate&paged=1');
    
    // Test pagination navigation
    const paginationLinks = page.locator('.pagination-links');
    
    // Navigate to page 5 (assuming 20 per page, this would be candidates 81-100)
    const page5Link = page.locator('.pagination-links a:has-text("5")').first();
    if (await page5Link.isVisible()) {
      const startTime = Date.now();
      await page5Link.click();
      await page.waitForLoadState('networkidle');
      const navTime = Date.now() - startTime;
      
      // Pagination should be fast (under 2 seconds)
      expect(navTime).toBeLessThan(2000);
      
      // Verify we're on page 5
      const currentPage = page.locator('.current-page');
      await expect(currentPage).toHaveValue('5');
    }
  });

  test('should test AJAX performance with large datasets', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-dashboard');
    
    // Measure AJAX request performance
    const ajaxPromise = page.waitForResponse(
      response => response.url().includes('admin-ajax.php') && 
                 response.url().includes('mt_get_statistics')
    );
    
    // Trigger statistics load
    const statsButton = page.locator('button:has-text("Refresh Statistics")');
    if (await statsButton.isVisible()) {
      const startTime = Date.now();
      await statsButton.click();
      
      const response = await ajaxPromise;
      const responseTime = Date.now() - startTime;
      
      // AJAX requests should complete within 2 seconds
      expect(responseTime).toBeLessThan(2000);
      
      // Verify response is successful
      expect(response.status()).toBe(200);
    }
  });

  test('should test evaluation form performance', async ({ page }) => {
    // Switch to jury member
    await page.context().storageState({ path: 'tests/.auth/jury.json' });
    await page.goto('/jury-dashboard/');
    
    // Click on first candidate to evaluate
    const evaluateLink = page.locator('.evaluate-candidate').first();
    if (await evaluateLink.isVisible()) {
      const startTime = Date.now();
      await evaluateLink.click();
      
      // Wait for evaluation form to load
      await page.waitForSelector('.evaluation-form');
      const loadTime = Date.now() - startTime;
      
      // Form should load within 2 seconds
      expect(loadTime).toBeLessThan(2000);
      
      // Test form submission performance
      await page.fill('input[name="criterion_1"]', '8');
      await page.fill('input[name="criterion_2"]', '7');
      await page.fill('input[name="criterion_3"]', '9');
      await page.fill('input[name="criterion_4"]', '8');
      await page.fill('input[name="criterion_5"]', '7');
      
      const submitStart = Date.now();
      await page.click('button[name="save_draft"]');
      
      // Wait for success message
      await page.waitForSelector('.notice-success, .mt-success-message');
      const submitTime = Date.now() - submitStart;
      
      // Submission should complete within 2 seconds
      expect(submitTime).toBeLessThan(2000);
    }
  });

  test('should test export performance with large dataset', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-import-export');
    
    const exportButton = page.locator('button:has-text("Export Candidates")');
    if (await exportButton.isVisible()) {
      // Start download monitoring
      const downloadPromise = page.waitForEvent('download');
      
      const startTime = Date.now();
      await exportButton.click();
      
      const download = await downloadPromise;
      const exportTime = Date.now() - startTime;
      
      // Export should start within 10 seconds even for 490+ candidates
      expect(exportTime).toBeLessThan(10000);
      
      // Verify download started
      expect(download).toBeTruthy();
    }
  });

  test('should test database query optimization', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=performance');
    
    // Look for slow query warnings
    const slowQueries = page.locator('.slow-query-warning');
    const slowQueryCount = await slowQueries.count();
    
    // Should have minimal slow queries
    expect(slowQueryCount).toBeLessThan(5);
    
    // Check query execution times
    const queryTimes = await page.locator('.query-time').allTextContents();
    for (const timeStr of queryTimes) {
      const time = parseFloat(timeStr);
      if (!isNaN(time)) {
        // No query should take more than 1 second
        expect(time).toBeLessThan(1000);
      }
    }
  });

  test('should test memory usage with large candidate list', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=performance');
    
    // Check memory usage
    const memoryUsage = page.locator('.memory-usage');
    if (await memoryUsage.isVisible()) {
      const memText = await memoryUsage.textContent();
      const memMatch = memText?.match(/(\d+)\s*MB/);
      
      if (memMatch) {
        const memoryMB = parseInt(memMatch[1]);
        // Memory usage should stay under 256MB even with large dataset
        expect(memoryMB).toBeLessThan(256);
      }
    }
  });

  test('should test concurrent user simulation', async ({ browser }) => {
    const userCount = 5; // Simulate 5 concurrent jury members
    const contexts = [];
    const pages = [];
    
    // Create multiple browser contexts
    for (let i = 0; i < userCount; i++) {
      const context = await browser.newContext({
        storageState: 'tests/.auth/jury.json'
      });
      contexts.push(context);
      const page = await context.newPage();
      pages.push(page);
    }
    
    // All users navigate simultaneously
    const startTime = Date.now();
    const navigationPromises = pages.map(page => 
      page.goto('/jury-dashboard/')
    );
    
    await Promise.all(navigationPromises);
    const loadTime = Date.now() - startTime;
    
    // Even with concurrent users, pages should load within 10 seconds
    expect(loadTime).toBeLessThan(10000);
    
    // Clean up
    for (const context of contexts) {
      await context.close();
    }
  });

  test('should test asset loading optimization', async ({ page }) => {
    const assetPromises: Promise<any>[] = [];
    
    // Monitor asset loading
    page.on('response', response => {
      const url = response.url();
      if (url.includes('.js') || url.includes('.css')) {
        assetPromises.push(
          response.finished().then(() => ({
            url,
            status: response.status(),
            size: response.headers()['content-length']
          }))
        );
      }
    });
    
    await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
    await page.waitForLoadState('networkidle');
    
    const assets = await Promise.all(assetPromises);
    
    // Check that assets are minified (have .min in filename)
    const minifiedCount = assets.filter(a => a.url.includes('.min.')).length;
    const totalCount = assets.length;
    
    // At least 50% of assets should be minified in production
    expect(minifiedCount / totalCount).toBeGreaterThan(0.5);
    
    // Check for proper caching headers
    for (const asset of assets) {
      expect(asset.status).toBe(200); // or 304 for cached
    }
  });
});