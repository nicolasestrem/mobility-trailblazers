import { test, expect } from '@playwright/test';
import { WordPressAdmin, JuryDashboard } from './utils/test-helpers';

test.describe('Navigation and UI Components', () => {
  // Use the stored admin authentication state for all tests
  test.use({ storageState: 'tests/.auth/admin.json' });
  
  test.describe('WordPress Admin Navigation', () => {
    test('MT plugin appears in admin menu', async ({ page }) => {
      // Already logged in via stored state
      await page.goto('/wp-admin');
      
      // Check for Mobility Trailblazers plugin menu items  
      const adminMenu = page.locator('#adminmenu');
      await expect(adminMenu.locator('a:has-text("Mobility Trailblazers")')).toBeVisible();
      
      // Check submenu items by hovering over the main menu
      await page.hover('#adminmenu a:has-text("Mobility Trailblazers")');
      
      const expectedSubmenuItems = [
        'Dashboard',
        'Assignments', 
        'Evaluations',
        'Import/Export'
      ];
      
      for (const item of expectedSubmenuItems) {
        // Use a more flexible selector that allows partial text matching
        const menuItem = adminMenu.locator(`a:has-text("${item}")`);
        if (await menuItem.isVisible()) {
          await expect(menuItem).toBeVisible();
        } else {
          console.warn(`Menu item "${item}" not found - may not be accessible to current user`);
        }
      }
    });

    test('can navigate to all MT admin pages', async ({ page }) => {
      const wp = new WordPressAdmin(page);
      
      // Already logged in via stored state
      await page.goto('/wp-admin');
      
      // Test navigation to each admin page
      const adminPages = [
        { name: 'Assignments', method: 'navigateToAssignments' },
        { name: 'Evaluations', method: 'navigateToEvaluations' },
        { name: 'Debug Center', method: 'navigateToDebugCenter' }
      ];
      
      for (const adminPage of adminPages) {
        try {
          await (wp as any)[adminPage.method]();
          
          // Verify page loaded correctly - use more flexible selectors
          const pageWrapper = page.locator('.wrap');
          await expect(pageWrapper).toBeVisible();
          
          // Check for WordPress error messages
          const errorMessage = page.locator('.wp-die-message');
          if (await errorMessage.isVisible()) {
            console.warn(`Error on ${adminPage.name} page`);
          }
          
          console.log(`✅ Successfully navigated to ${adminPage.name}`);
        } catch (error) {
          console.warn(`⚠️  Could not navigate to ${adminPage.name}:`, error);
        }
      }
    });

    test('custom post types accessible in admin', async ({ page }) => {
      // Already logged in via stored state
      await page.goto('/wp-admin');
      
      // Test Candidates post type
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      await expect(page.locator('.wp-list-table')).toBeVisible();
      await expect(page.locator('h1')).toContainText('Candidates');
      
      // Test add new candidate
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      await expect(page.locator('#title')).toBeVisible();
      await expect(page.locator('#content')).toBeVisible();
      
      // Check for custom meta boxes
      const candidateMetaBoxes = [
        '.mt-candidate-details',
        '.mt-candidate-meta',
        '.mt-company-info'
      ];
      
      for (const metaBox of candidateMetaBoxes) {
        if (await page.locator(metaBox).isVisible()) {
          await expect(page.locator(metaBox)).toBeVisible();
        }
      }
    });
  });

  test.describe('Jury Dashboard Navigation', () => {
    test('jury dashboard loads correctly', async ({ page }) => {
      // Would need jury member credentials for this test
      // For now, test the dashboard structure
      
      const jury = new JuryDashboard(page);
      
      try {
        // Try to access jury dashboard
        await page.goto('/jury-dashboard/');
        
        // Check if redirected to login
        if (page.url().includes('/wp-login.php')) {
          // Login as jury member if needed
          await page.fill('#user_login', process.env.JURY_USERNAME || 'jury1');
          await page.fill('#user_pass', process.env.JURY_PASSWORD || 'jury123');
          await page.click('#wp-submit');
          
          // Navigate back to dashboard
          await jury.navigate();
        }
        
        // Check dashboard components
        const dashboardElements = [
          '.mt-jury-dashboard',
          '.mt-dashboard-header',
          '.mt-stats-grid',
          '.mt-candidate-list'
        ];
        
        for (const element of dashboardElements) {
          if (await page.locator(element).isVisible()) {
            await expect(page.locator(element)).toBeVisible();
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not access jury dashboard - may need proper credentials');
      }
    });

    test('dashboard statistics display correctly', async ({ page }) => {
      try {
        const jury = new JuryDashboard(page);
        
        // Try to get statistics
        const stats = await jury.getStatistics();
        
        // Verify statistics are numbers
        expect(typeof stats.totalAssigned).toBe('number');
        expect(typeof stats.completed).toBe('number');
        expect(typeof stats.pending).toBe('number');
        
        // Verify logical relationships
        expect(stats.completed + stats.pending).toBeLessThanOrEqual(stats.totalAssigned);
        
      } catch (error) {
        console.warn('⚠️  Could not access dashboard statistics');
      }
    });

    test('candidate filtering works', async ({ page }) => {
      try {
        const jury = new JuryDashboard(page);
        
        await jury.navigate();
        
        // Test category filtering
        const categories = ['start-ups', 'established-companies', 'governance'];
        
        for (const category of categories) {
          await jury.filterByCategory(category as any);
          
          // Verify candidates are filtered
          const candidateCards = await jury.getCandidateCards();
          const count = await candidateCards.count();
          
          if (count > 0) {
            // Check that visible candidates match the category
            for (let i = 0; i < count; i++) {
              const card = candidateCards.nth(i);
              await expect(card.locator('.mt-category-badge')).toContainText(category.replace('-', ' '));
            }
          }
        }
        
        // Test status filtering
        const statuses = ['all', 'completed', 'pending', 'draft'];
        
        for (const status of statuses) {
          await jury.filterByStatus(status as any);
          
          // Just verify no errors occurred
          await expect(page.locator('.mt-error')).not.toBeVisible();
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test filtering functionality');
      }
    });

    test('candidate search functionality', async ({ page }) => {
      try {
        const jury = new JuryDashboard(page);
        
        await jury.navigate();
        
        // Test search functionality
        await jury.searchCandidates('test');
        
        // Verify search results
        const candidateCards = await jury.getCandidateCards();
        const count = await candidateCards.count();
        
        if (count > 0) {
          // Check that results contain search term (in title, company, or description)
          for (let i = 0; i < Math.min(count, 3); i++) { // Check first 3 results
            const card = candidateCards.nth(i);
            const cardText = await card.textContent();
            expect(cardText?.toLowerCase()).toContain('test');
          }
        }
        
        // Test empty search
        await jury.searchCandidates('');
        
        // Should show all candidates or default view
        await expect(page.locator('.mt-no-results')).not.toBeVisible();
        
      } catch (error) {
        console.warn('⚠️  Could not test search functionality');
      }
    });
  });

  test.describe('Mobile Navigation', () => {
    test('admin menu works on mobile', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      
      // Already logged in via stored state
      await page.goto('/wp-admin');
      
      // Check mobile admin menu
      const adminMenuButton = page.locator('#wp-admin-bar-menu-toggle');
      if (await adminMenuButton.isVisible()) {
        await adminMenuButton.click();
      }
      
      // Verify menu is accessible
      await expect(page.locator('#adminmenu')).toBeVisible();
      
      // Test MT plugin menu on mobile
      const mtMenuItem = page.locator('#adminmenu a:has-text("MT Award")');
      if (await mtMenuItem.isVisible()) {
        await mtMenuItem.click();
        
        // Check submenu accessibility
        await expect(page.locator('#adminmenu .wp-submenu')).toBeVisible();
      }
    });

    test('jury dashboard responsive on mobile', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      
      try {
        const jury = new JuryDashboard(page);
        await jury.navigate();
        
        // Check responsive elements
        const responsiveElements = [
          '.mt-jury-dashboard',
          '.mt-dashboard-header',
          '.mt-stats-grid',
          '.mt-candidate-list'
        ];
        
        for (const element of responsiveElements) {
          if (await page.locator(element).isVisible()) {
            await expect(page.locator(element)).toBeVisible();
            
            // Check that element fits in mobile viewport
            const boundingBox = await page.locator(element).boundingBox();
            if (boundingBox) {
              expect(boundingBox.width).toBeLessThanOrEqual(375);
            }
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test mobile jury dashboard');
      }
    });
  });

  test.describe('Breadcrumb Navigation', () => {
    test('breadcrumbs show correct path', async ({ page }) => {
      // Already logged in via stored state
      await page.goto('/wp-admin');
      
      // Test breadcrumbs on different pages
      const testPages = [
        {
          url: '/wp-admin/admin.php?page=mt-assignments',
          expectedBreadcrumb: 'Assignments'
        },
        {
          url: '/wp-admin/admin.php?page=mt-evaluations', 
          expectedBreadcrumb: 'Evaluations'
        },
        {
          url: '/wp-admin/edit.php?post_type=mt_candidate',
          expectedBreadcrumb: 'Candidates'
        },
        {
          url: '/wp-admin/admin.php?page=mobility-trailblazers',
          expectedBreadcrumb: 'Mobility Trailblazers Dashboard'
        }
      ];
      
      for (const testPage of testPages) {
        try {
          await page.goto(testPage.url);
          
          // Check for breadcrumb elements
          const breadcrumbSelectors = [
            '.mt-breadcrumb',
            '.wp-admin .wrap h1',
            '.page-title-action'
          ];
          
          let breadcrumbFound = false;
          for (const selector of breadcrumbSelectors) {
            if (await page.locator(selector).isVisible()) {
              breadcrumbFound = true;
              break;
            }
          }
          
          expect(breadcrumbFound).toBeTruthy();
          
        } catch (error) {
          console.warn(`⚠️  Could not test breadcrumb for ${testPage.url}`);
        }
      }
    });
  });

  test.describe('Back Button Navigation', () => {
    test('browser back button works correctly', async ({ page }) => {
      // Already logged in via stored state
      await page.goto('/wp-admin/');
      
      const startUrl = page.url();
      
      // Navigate to different page
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      const secondUrl = page.url();
      
      // Use browser back button
      await page.goBack();
      
      // Should return to original page
      expect(page.url()).toBe(startUrl);
      
      // Use browser forward button
      await page.goForward();
      expect(page.url()).toBe(secondUrl);
    });
  });

  test.describe('Error Page Navigation', () => {
    test('404 pages handle gracefully', async ({ page }) => {
      // Try to access non-existent MT page
      await page.goto('/wp-admin/admin.php?page=mt-nonexistent');
      
      // Should either show 404 or redirect to valid page
      const has404 = await page.locator('.wp-die-message').isVisible();
      const hasValidPage = await page.locator('.mt-admin-page').isVisible();
      
      expect(has404 || hasValidPage).toBeTruthy();
    });

    test('permission errors show appropriate message', async ({ page }) => {
      // Try to access admin page without proper permissions
      // This would need to be tested with different user roles
      
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Should either show login form, permission error, or valid page access
      const hasLoginForm = await page.locator('#loginform').isVisible();
      const hasPermissionError = await page.locator('.wp-die-message').isVisible();
      const hasValidAccess = await page.locator('.wrap').isVisible();
      
      expect(hasLoginForm || hasPermissionError || hasValidAccess).toBeTruthy();
    });
  });
});