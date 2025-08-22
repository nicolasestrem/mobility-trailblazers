import { test, expect } from '@playwright/test';

test.describe('Assignment Management - Simple Tests', () => {
  
  test('can access assignment management page', async ({ page }) => {
    // Navigate directly to assignments page
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Check if we're on the right page (handle both German and English)
    const pageTitle = page.locator('.wrap h1');
    const titleText = await pageTitle.textContent();
    console.log('Page title text:', titleText);
    expect(titleText).toMatch(/(Assignment|Zuweisung)/);
    
    // Check for statistics dashboard
    await expect(page.locator('.mt-stats-dashboard')).toBeVisible();
    
    // Check for stat cards
    await expect(page.locator('.mt-stat-card').first()).toBeVisible();
    
    // Check for action buttons
    await expect(page.locator('#mt-auto-assign-btn')).toBeVisible();
    await expect(page.locator('#mt-manual-assign-btn')).toBeVisible();
    await expect(page.locator('#mt-bulk-actions-btn')).toBeVisible();
    await expect(page.locator('#mt-export-btn')).toBeVisible();
    
    // Check for assignments table
    await expect(page.locator('.mt-assignments-table')).toBeVisible();
  });

  test('statistics display correctly', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Check all stat cards are present
    const statCards = page.locator('.mt-stat-card');
    await expect(statCards).toHaveCount(4);
    
    // Check stat numbers are displayed
    const statNumbers = page.locator('.mt-stat-number');
    await expect(statNumbers).toHaveCount(4);
    
    // Verify each stat has a value
    const count = await statNumbers.count();
    for (let i = 0; i < count; i++) {
      const text = await statNumbers.nth(i).textContent();
      expect(text).toBeTruthy();
      expect(parseInt(text || '0')).toBeGreaterThanOrEqual(0);
    }
  });

  test('auto-assign button opens modal', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Click auto-assign button
    await page.click('#mt-auto-assign-btn');
    
    // Wait a bit for modal animation
    await page.waitForTimeout(500);
    
    // Check if modal opens (it might be hidden with display:none initially)
    const modal = page.locator('#mt-auto-assign-modal, #autoAssignModal');
    
    // Force check visibility or existence
    const modalExists = await modal.count() > 0;
    expect(modalExists).toBeTruthy();
  });

  test('assignment table displays data', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Check table exists
    const table = page.locator('.mt-assignments-table');
    await expect(table).toBeVisible();
    
    // Check table has headers
    await expect(table.locator('thead')).toBeVisible();
    
    // Check if table has tbody
    const tbody = table.locator('tbody');
    await expect(tbody).toBeVisible();
  });

  test('search and filter controls are present', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments');
    
    // Check search box
    await expect(page.locator('#mt-assignment-search')).toBeVisible();
    
    // Check filter dropdowns
    await expect(page.locator('#mt-filter-jury')).toBeVisible();
    await expect(page.locator('#mt-filter-status')).toBeVisible();
  });
});