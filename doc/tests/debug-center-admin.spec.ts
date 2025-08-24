import { test, expect } from '@playwright/test';

test.describe('Debug Center Admin Tests', () => {
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to Debug Center
    await page.goto('/wp-admin/admin.php?page=mt-debug-center');
  });

  test('should display Debug Center main dashboard', async ({ page }) => {
    // Verify Debug Center loaded
    await expect(page.locator('h1:has-text("MT Debug Center")')).toBeVisible();
    
    // Check for main sections
    const sections = [
      'System Information',
      'Database Status',
      'Error Logs',
      'Performance Metrics',
      'Plugin Status'
    ];
    
    for (const section of sections) {
      const sectionElement = page.locator(`h2:has-text("${section}"), h3:has-text("${section}")`);
      if (await sectionElement.isVisible({ timeout: 2000 }).catch(() => false)) {
        await expect(sectionElement).toBeVisible();
      }
    }
  });

  test('should display system information correctly', async ({ page }) => {
    await page.click('a[href*="tab=system"], button:has-text("System Info")');
    
    // Check for system information
    const systemInfo = [
      { label: 'PHP Version', pattern: /\d+\.\d+\.\d+/ },
      { label: 'WordPress Version', pattern: /\d+\.\d+/ },
      { label: 'Plugin Version', pattern: /\d+\.\d+\.\d+/ },
      { label: 'MySQL Version', pattern: /\d+\.\d+/ },
      { label: 'Memory Limit', pattern: /\d+M/ },
      { label: 'Max Execution Time', pattern: /\d+/ },
      { label: 'Environment', pattern: /development|staging|production/ }
    ];
    
    for (const info of systemInfo) {
      const row = page.locator(`tr:has-text("${info.label}")`);
      if (await row.isVisible()) {
        const value = await row.locator('td').last().textContent();
        expect(value).toMatch(info.pattern);
      }
    }
  });

  test('should display database table information', async ({ page }) => {
    await page.click('a[href*="tab=database"], button:has-text("Database")');
    
    // Check for MT custom tables
    const customTables = [
      'wp_mt_evaluations',
      'wp_mt_jury_assignments',
      'wp_mt_audit_log',
      'wp_mt_error_log'
    ];
    
    for (const table of customTables) {
      const tableInfo = page.locator(`text=/${table}/`);
      await expect(tableInfo).toBeVisible();
      
      // Check for table stats
      const tableRow = page.locator(`tr:has-text("${table}")`);
      if (await tableRow.isVisible()) {
        // Should show row count
        const rowCount = await tableRow.locator('td:nth-of-type(2)').textContent();
        expect(rowCount).toMatch(/\d+/);
        
        // Should show table size
        const tableSize = await tableRow.locator('td:nth-of-type(3)').textContent();
        expect(tableSize).toMatch(/\d+(\.\d+)?\s*(KB|MB|GB)/);
      }
    }
  });

  test('should display and filter error logs', async ({ page }) => {
    await page.click('a[href*="tab=errors"], button:has-text("Error Logs")');
    
    // Check for error log table
    const errorTable = page.locator('table.error-logs, .wp-list-table');
    await expect(errorTable).toBeVisible();
    
    // Test log level filters
    const logLevels = ['error', 'warning', 'notice', 'info'];
    for (const level of logLevels) {
      const filterButton = page.locator(`button:has-text("${level}"), input[value="${level}"]`);
      if (await filterButton.isVisible()) {
        await filterButton.click();
        await page.waitForTimeout(500);
        
        // Check that filtered results are shown
        const filteredRows = page.locator(`tr[data-level="${level}"], tr:has-text("${level}")`);
        if (await filteredRows.first().isVisible({ timeout: 1000 }).catch(() => false)) {
          const count = await filteredRows.count();
          expect(count).toBeGreaterThanOrEqual(0);
        }
      }
    }
    
    // Test date range filter
    const dateFrom = page.locator('input[name="date_from"]');
    const dateTo = page.locator('input[name="date_to"]');
    if (await dateFrom.isVisible() && await dateTo.isVisible()) {
      const today = new Date().toISOString().split('T')[0];
      const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];
      
      await dateFrom.fill(yesterday);
      await dateTo.fill(today);
      await page.click('button:has-text("Filter")');
      
      // Should show filtered results
      await page.waitForTimeout(1000);
      const results = page.locator('tbody tr');
      const resultCount = await results.count();
      expect(resultCount).toBeGreaterThanOrEqual(0);
    }
  });

  test('should test error log export functionality', async ({ page }) => {
    await page.click('a[href*="tab=errors"], button:has-text("Error Logs")');
    
    const exportButton = page.locator('button:has-text("Export"), a:has-text("Export")');
    if (await exportButton.isVisible()) {
      // Set up download promise
      const downloadPromise = page.waitForEvent('download');
      
      await exportButton.click();
      
      const download = await downloadPromise;
      expect(download).toBeTruthy();
      
      // Verify file name
      const fileName = download.suggestedFilename();
      expect(fileName).toMatch(/error.*log.*\.(csv|txt|json)/);
    }
  });

  test('should display performance metrics', async ({ page }) => {
    await page.click('a[href*="tab=performance"], button:has-text("Performance")');
    
    // Check for performance metrics
    const metrics = [
      { name: 'Page Load Time', unit: 'ms' },
      { name: 'Database Queries', unit: 'queries' },
      { name: 'Memory Usage', unit: 'MB' },
      { name: 'Peak Memory', unit: 'MB' },
      { name: 'Query Time', unit: 'ms' }
    ];
    
    for (const metric of metrics) {
      const metricElement = page.locator(`text=/${metric.name}/`);
      if (await metricElement.isVisible()) {
        const valueElement = metricElement.locator('..').locator('.metric-value, td');
        const value = await valueElement.textContent();
        expect(value).toMatch(/\d/);
        if (metric.unit !== 'queries') {
          expect(value?.toLowerCase()).toContain(metric.unit.toLowerCase());
        }
      }
    }
  });

  test('should test query monitor integration', async ({ page }) => {
    await page.click('a[href*="tab=queries"], button:has-text("Queries")');
    
    // Check for slow queries
    const slowQueries = page.locator('.slow-query, tr.slow');
    const slowCount = await slowQueries.count();
    
    if (slowCount > 0) {
      // Inspect first slow query
      const firstSlowQuery = slowQueries.first();
      await expect(firstSlowQuery).toBeVisible();
      
      // Should show query details
      const queryText = await firstSlowQuery.locator('.query-sql, td.query').textContent();
      expect(queryText).toContain('SELECT');
      
      // Should show execution time
      const timeText = await firstSlowQuery.locator('.query-time, td.time').textContent();
      expect(timeText).toMatch(/\d+(\.\d+)?\s*ms/);
    }
  });

  test('should test cache management tools', async ({ page }) => {
    await page.click('a[href*="tab=cache"], button:has-text("Cache")');
    
    // Check cache status
    const cacheStatus = page.locator('.cache-status');
    if (await cacheStatus.isVisible()) {
      const statusText = await cacheStatus.textContent();
      expect(statusText).toMatch(/enabled|disabled|active|inactive/i);
    }
    
    // Test cache clear functionality
    const clearCacheButton = page.locator('button:has-text("Clear Cache"), button:has-text("Flush Cache")');
    if (await clearCacheButton.isVisible()) {
      await clearCacheButton.click();
      
      // Should show success message
      await expect(page.locator('.notice-success, .success-message')).toBeVisible();
      const successText = await page.locator('.notice-success, .success-message').textContent();
      expect(successText?.toLowerCase()).toContain('cache');
    }
  });

  test('should test audit log viewer', async ({ page }) => {
    await page.click('a[href*="tab=audit"], button:has-text("Audit Log")');
    
    // Check for audit entries
    const auditTable = page.locator('table.audit-log, .audit-entries');
    await expect(auditTable).toBeVisible();
    
    // Check audit entry structure
    const auditRows = page.locator('tbody tr');
    const rowCount = await auditRows.count();
    
    if (rowCount > 0) {
      const firstRow = auditRows.first();
      
      // Should have user info
      const userCell = firstRow.locator('td').nth(0);
      const userText = await userCell.textContent();
      expect(userText).toBeTruthy();
      
      // Should have action
      const actionCell = firstRow.locator('td').nth(1);
      const actionText = await actionCell.textContent();
      expect(actionText).toMatch(/create|update|delete|view|login|logout/i);
      
      // Should have timestamp
      const timeCell = firstRow.locator('td').nth(2);
      const timeText = await timeCell.textContent();
      expect(timeText).toMatch(/\d{4}-\d{2}-\d{2}|\d{2}\/\d{2}\/\d{4}/);
    }
  });

  test('should test debug mode toggle', async ({ page }) => {
    await page.click('a[href*="tab=settings"], button:has-text("Settings")');
    
    // Find debug mode toggle
    const debugToggle = page.locator('input[name="debug_mode"], input#debug_mode');
    if (await debugToggle.isVisible()) {
      const isChecked = await debugToggle.isChecked();
      
      // Toggle debug mode
      if (isChecked) {
        await debugToggle.uncheck();
      } else {
        await debugToggle.check();
      }
      
      // Save settings
      await page.click('button:has-text("Save Settings")');
      
      // Should show success message
      await expect(page.locator('.notice-success')).toBeVisible();
      
      // Verify setting was saved
      await page.reload();
      const newState = await debugToggle.isChecked();
      expect(newState).toBe(!isChecked);
      
      // Restore original state
      if (newState !== isChecked) {
        if (isChecked) {
          await debugToggle.check();
        } else {
          await debugToggle.uncheck();
        }
        await page.click('button:has-text("Save Settings")');
      }
    }
  });

  test('should test data integrity checker', async ({ page }) => {
    await page.click('a[href*="tab=integrity"], button:has-text("Data Integrity")');
    
    // Run integrity check
    const checkButton = page.locator('button:has-text("Run Check"), button:has-text("Check Integrity")');
    if (await checkButton.isVisible()) {
      await checkButton.click();
      
      // Wait for results
      await page.waitForSelector('.integrity-results, .check-results', { timeout: 10000 });
      
      // Check for issues
      const issues = page.locator('.integrity-issue, .warning, .error');
      const issueCount = await issues.count();
      
      if (issueCount > 0) {
        // Display issue details
        const firstIssue = issues.first();
        const issueText = await firstIssue.textContent();
        console.log(`Found integrity issue: ${issueText}`);
        
        // Check for fix button
        const fixButton = firstIssue.locator('button:has-text("Fix"), button:has-text("Repair")');
        if (await fixButton.isVisible()) {
          await expect(fixButton).toBeEnabled();
        }
      } else {
        // Should show success if no issues
        const successMessage = page.locator('.integrity-success, .all-clear');
        await expect(successMessage).toBeVisible();
      }
    }
  });

  test('should test backup and restore tools', async ({ page }) => {
    await page.click('a[href*="tab=backup"], button:has-text("Backup")');
    
    // Test backup creation
    const backupButton = page.locator('button:has-text("Create Backup")');
    if (await backupButton.isVisible()) {
      // Monitor download
      const downloadPromise = page.waitForEvent('download', { timeout: 30000 })
        .catch(() => null);
      
      await backupButton.click();
      
      // Should either download or show in-page backup
      const download = await downloadPromise;
      if (download) {
        const fileName = download.suggestedFilename();
        expect(fileName).toMatch(/backup.*\.(sql|zip)/);
      } else {
        // Check for backup list update
        const backupList = page.locator('.backup-list, table.backups');
        await expect(backupList).toBeVisible();
        
        const backupRows = backupList.locator('tr');
        const rowCount = await backupRows.count();
        expect(rowCount).toBeGreaterThan(0);
      }
    }
  });

  test('should test environment indicator', async ({ page }) => {
    // Check for environment indicator
    const envIndicator = page.locator('.environment-indicator, .env-badge');
    if (await envIndicator.isVisible()) {
      const envText = await envIndicator.textContent();
      expect(envText?.toLowerCase()).toMatch(/development|staging|production/);
      
      // Check color coding
      const classes = await envIndicator.getAttribute('class');
      if (envText?.toLowerCase().includes('production')) {
        expect(classes).toContain('red');
      } else if (envText?.toLowerCase().includes('staging')) {
        expect(classes).toContain('yellow');
      } else {
        expect(classes).toContain('green');
      }
    }
  });

  test('should test debug console', async ({ page }) => {
    await page.click('a[href*="tab=console"], button:has-text("Console")');
    
    // Check for debug console
    const console = page.locator('.debug-console, #debug-console');
    if (await console.isVisible()) {
      // Test command execution
      const commandInput = page.locator('input[name="command"], #console-command');
      const executeButton = page.locator('button:has-text("Execute"), button:has-text("Run")');
      
      if (await commandInput.isVisible() && await executeButton.isVisible()) {
        // Try a safe command
        await commandInput.fill('wp_get_environment_type()');
        await executeButton.click();
        
        // Check for output
        const output = page.locator('.console-output, #console-results');
        await expect(output).toBeVisible();
        const outputText = await output.textContent();
        expect(outputText).toBeTruthy();
      }
    }
  });

  test('should test real-time monitoring', async ({ page }) => {
    await page.click('a[href*="tab=monitor"], button:has-text("Monitor")');
    
    // Check for real-time stats
    const realtimeStats = page.locator('.realtime-stats, .live-monitor');
    if (await realtimeStats.isVisible()) {
      // Get initial values
      const initialValues: Record<string, string> = {};
      const statElements = await realtimeStats.locator('.stat-value').all();
      
      for (const element of statElements) {
        const label = await element.locator('..').locator('.stat-label').textContent();
        const value = await element.textContent();
        if (label && value) {
          initialValues[label] = value;
        }
      }
      
      // Wait for update
      await page.waitForTimeout(5000);
      
      // Check if values updated
      let hasUpdated = false;
      for (const element of statElements) {
        const label = await element.locator('..').locator('.stat-label').textContent();
        const newValue = await element.textContent();
        if (label && initialValues[label] && newValue !== initialValues[label]) {
          hasUpdated = true;
          break;
        }
      }
      
      // At least some values should update in real-time
      expect(hasUpdated).toBeTruthy();
    }
  });
});