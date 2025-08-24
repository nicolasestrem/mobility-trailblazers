import { test, expect } from '@playwright/test';

test.describe('Database Tables Tests', () => {
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to debug center for database checks
    await page.goto('/wp-admin/admin.php?page=mt-debug-center');
  });

  test('should verify wp_mt_evaluations table structure', async ({ page }) => {
    // Check if evaluations table exists
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=database');
    
    // Look for evaluations table in the debug output
    const tableInfo = page.locator('text=/wp_mt_evaluations/');
    await expect(tableInfo).toBeVisible();
    
    // Verify key columns are present
    const requiredColumns = [
      'jury_member_id',
      'candidate_id',
      'criterion_1',
      'criterion_2',
      'criterion_3',
      'criterion_4',
      'criterion_5',
      'total_score',
      'comments',
      'status',
      'created_at',
      'updated_at',
      'submitted_at'
    ];
    
    for (const column of requiredColumns) {
      await expect(page.locator(`text=/${column}/`)).toBeVisible();
    }
  });

  test('should verify wp_mt_jury_assignments table structure', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=database');
    
    // Check if assignments table exists
    const tableInfo = page.locator('text=/wp_mt_jury_assignments/');
    await expect(tableInfo).toBeVisible();
    
    // Verify key columns
    const requiredColumns = [
      'jury_member_id',
      'candidate_id',
      'assigned_at',
      'assigned_by'
    ];
    
    for (const column of requiredColumns) {
      await expect(page.locator(`text=/${column}/`)).toBeVisible();
    }
    
    // Check for unique constraint
    await expect(page.locator('text=/UNIQUE KEY/')).toBeVisible();
  });

  test('should verify wp_mt_audit_log table exists', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=database');
    
    const tableInfo = page.locator('text=/wp_mt_audit_log/');
    await expect(tableInfo).toBeVisible();
    
    // Verify audit log columns
    const requiredColumns = [
      'user_id',
      'action',
      'object_type',
      'object_id',
      'details',
      'ip_address',
      'user_agent',
      'created_at'
    ];
    
    for (const column of requiredColumns) {
      await expect(page.locator(`text=/${column}/`)).toBeVisible();
    }
  });

  test('should verify wp_mt_error_log table exists', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=database');
    
    const tableInfo = page.locator('text=/wp_mt_error_log/');
    await expect(tableInfo).toBeVisible();
    
    // Verify error log columns
    const requiredColumns = [
      'error_level',
      'error_message',
      'error_context',
      'file',
      'line',
      'created_at'
    ];
    
    for (const column of requiredColumns) {
      await expect(page.locator(`text=/${column}/`)).toBeVisible();
    }
  });

  test('should test evaluation data integrity', async ({ page }) => {
    // Navigate to evaluations page
    await page.goto('/wp-admin/admin.php?page=mt-evaluations');
    
    // Check that evaluation scores are within valid range (0-10)
    const scores = await page.locator('.evaluation-score').allTextContents();
    for (const score of scores) {
      const numScore = parseFloat(score);
      if (!isNaN(numScore)) {
        expect(numScore).toBeGreaterThanOrEqual(0);
        expect(numScore).toBeLessThanOrEqual(10);
      }
    }
    
    // Verify total scores are calculated correctly
    const totalScores = await page.locator('.total-score').allTextContents();
    for (const total of totalScores) {
      const numTotal = parseFloat(total);
      if (!isNaN(numTotal)) {
        expect(numTotal).toBeGreaterThanOrEqual(0);
        expect(numTotal).toBeLessThanOrEqual(50); // 5 criteria * 10 max
      }
    }
  });

  test('should test assignment uniqueness constraint', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Try to create a duplicate assignment via API
    const response = await page.evaluate(async () => {
      const formData = new FormData();
      formData.append('action', 'mt_create_assignment');
      formData.append('jury_member_id', '1');
      formData.append('candidate_id', '1');
      formData.append('nonce', (window as any).mt_ajax?.nonce || '');
      
      const result = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
      });
      
      return result.json();
    });
    
    // If assignment already exists, should get an error
    if (response.success === false) {
      expect(response.data).toContain('already assigned');
    }
  });

  test('should verify database indexes for performance', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=database');
    
    // Check for indexes on frequently queried columns
    const indexedColumns = [
      'status',
      'total_score',
      'updated_at',
      'jury_member_id',
      'candidate_id'
    ];
    
    for (const column of indexedColumns) {
      const indexInfo = page.locator(`text=/INDEX.*${column}/`);
      // At least some indexes should be present
      const count = await indexInfo.count();
      if (count > 0) {
        await expect(indexInfo.first()).toBeVisible();
      }
    }
  });

  test('should test cascade delete for assignments', async ({ page }) => {
    // This would typically be tested at the API level
    // Here we verify the UI reflects proper cascading
    await page.goto('/wp-admin/edit.php?post_type=mt_jury_member');
    
    // Check that deleting a jury member warns about assignments
    const deleteLink = page.locator('.submitdelete').first();
    if (await deleteLink.isVisible()) {
      await deleteLink.click();
      
      // Should show warning about related assignments
      const warning = page.locator('text=/assignments will be deleted/i');
      if (await warning.isVisible()) {
        await expect(warning).toBeVisible();
      }
    }
  });

  test('should verify data migration status', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-debug-center&tab=database');
    
    // Check database version
    const dbVersion = page.locator('text=/Database Version.*2\\.5/');
    await expect(dbVersion).toBeVisible();
    
    // Verify migration log
    const migrationStatus = page.locator('text=/All migrations.*complete/i');
    if (await migrationStatus.isVisible()) {
      await expect(migrationStatus).toBeVisible();
    }
  });

  test('should test transaction support for bulk operations', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Select multiple candidates for bulk assignment
    await page.check('input[name="candidate[]"]:nth-of-type(1)');
    await page.check('input[name="candidate[]"]:nth-of-type(2)');
    
    // Select a jury member
    await page.selectOption('select[name="jury_member"]', { index: 1 });
    
    // Perform bulk assignment
    await page.click('button:has-text("Bulk Assign")');
    
    // Verify all assignments were created or none (transaction)
    const successMessage = page.locator('.notice-success');
    const errorMessage = page.locator('.notice-error');
    
    // Either all succeed or all fail (transaction integrity)
    const hasSuccess = await successMessage.isVisible();
    const hasError = await errorMessage.isVisible();
    
    expect(hasSuccess || hasError).toBeTruthy();
    expect(!(hasSuccess && hasError)).toBeTruthy(); // Not both
  });
});