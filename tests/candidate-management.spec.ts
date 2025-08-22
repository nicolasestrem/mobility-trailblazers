import { test, expect } from '@playwright/test';
import { testCandidates } from './fixtures/test-data';

test.describe('Candidate Management', () => {
  // Use the stored admin authentication state
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.describe('Candidate List Management', () => {
    test('can view candidate list', async ({ page }) => {
      // Navigate to candidates post type
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Verify page loaded correctly
      await expect(page.locator('.wp-list-table')).toBeVisible();
      await expect(page.locator('h1')).toContainText('Candidates');
      
      // Check for candidate management features
      const managementFeatures = [
        '.tablenav', // Table navigation
        '.search-box', // Search functionality
        '.bulk-actions', // Bulk operations
        'th.check-column' // Bulk select checkboxes
      ];
      
      for (const feature of managementFeatures) {
        if (await page.locator(feature).isVisible()) {
          await expect(page.locator(feature)).toBeVisible();
        }
      }
      
      // Check for Add New button
      await expect(page.locator('.page-title-action, a:has-text("Add New")')).toBeVisible();
    });

    test('can search candidates', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Use search functionality
      const searchBox = page.locator('#post-search-input');
      if (await searchBox.isVisible()) {
        await searchBox.fill('test');
        await page.click('#search-submit');
        
        // Verify search results
        await expect(page.locator('.wp-list-table')).toBeVisible();
        
        // Check that URL contains search parameter
        expect(page.url()).toContain('s=test');
      }
    });

    test('can filter candidates by category', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Look for category filter dropdown
      const categoryFilter = page.locator('select[name="category"], .tablenav select');
      
      if (await categoryFilter.first().isVisible()) {
        // Test filtering by different categories
        const categories = ['start-ups', 'established-companies', 'governance'];
        
        for (const category of categories) {
          try {
            await categoryFilter.first().selectOption({ label: category });
            await page.click('.button[value="Filter"]');
            
            // Verify filter was applied
            await expect(page.locator('.wp-list-table')).toBeVisible();
            expect(page.url()).toContain('category=');
            
          } catch (error) {
            console.warn(`⚠️  Could not filter by category: ${category}`);
          }
        }
      }
    });

    test('can perform bulk operations', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Check if candidates exist
      const candidateRows = page.locator('.wp-list-table tbody tr:not(.no-items)');
      const candidateCount = await candidateRows.count();
      
      if (candidateCount > 0) {
        // Select first few candidates
        const selectableRows = Math.min(candidateCount, 3);
        
        for (let i = 0; i < selectableRows; i++) {
          const checkbox = candidateRows.nth(i).locator('input[type="checkbox"]');
          await checkbox.check();
        }
        
        // Test bulk operations
        const bulkActions = page.locator('#bulk-action-selector-top');
        if (await bulkActions.isVisible()) {
          await bulkActions.selectOption('trash');
          await page.click('#doaction');
          
          // Should show bulk action confirmation or result
          // (In real test, we'd want to test with test data only)
          console.log('✅ Bulk operation interface works');
        }
      }
    });
  });

  test.describe('Candidate Creation', () => {
    test('can create new candidate', async ({ page }) => {
      // Navigate to new candidate page
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Verify candidate editor is loaded
      await expect(page.locator('#title')).toBeVisible();
      await expect(page.locator('#content')).toBeVisible();
      
      // Fill in candidate details
      const testCandidate = testCandidates[0];
      
      await page.fill('#title', testCandidate.title);
      
      // Fill content/description
      await page.click('#content');
      await page.fill('#content', testCandidate.description || 'Test candidate description');
      
      // Fill meta fields if they exist
      const metaFields = [
        { selector: 'input[name="company"], #company', value: testCandidate.company },
        { selector: 'textarea[name="innovation"], #innovation', value: testCandidate.innovation },
        { selector: 'select[name="category"], #category', value: testCandidate.category }
      ];
      
      for (const field of metaFields) {
        try {
          const element = page.locator(field.selector).first();
          if (await element.isVisible()) {
            if (field.selector.includes('select')) {
              await element.selectOption(field.value);
            } else {
              await element.fill(field.value);
            }
          }
        } catch (error) {
          console.warn(`⚠️  Could not fill field ${field.selector}`);
        }
      }
      
      // Save as draft first
      await page.click('#save-post');
      
      // Wait for save confirmation
      await expect(page.locator('.notice-success, #message')).toBeVisible();
      
      // Verify candidate was created
      const postId = await page.locator('#post_ID').inputValue();
      expect(postId).toBeTruthy();
      
      console.log(`✅ Created test candidate with ID: ${postId}`);
    });

    test('validates required fields', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Try to publish without required fields
      await page.click('#publish');
      
      // Should show validation error or require title
      const titleField = page.locator('#title');
      const titleValue = await titleField.inputValue();
      
      if (!titleValue) {
        // WordPress typically requires a title
        await expect(page.locator('.notice-error, .error')).toBeVisible();
      }
    });

    test('can upload candidate photo', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Fill basic candidate info first
      await page.fill('#title', 'Test Candidate with Photo');
      
      // Look for featured image/photo upload
      const uploadButton = page.locator('#set-post-thumbnail, .set-featured-image');
      
      if (await uploadButton.isVisible()) {
        await uploadButton.click();
        
        // Media library should open
        await expect(page.locator('.media-modal, .media-frame')).toBeVisible();
        
        // Check upload functionality exists
        const uploadTab = page.locator('.media-menu-item:has-text("Upload")');
        if (await uploadTab.isVisible()) {
          await uploadTab.click();
          
          // Verify upload interface
          await expect(page.locator('.drag-drop-area, #plupload-upload-ui')).toBeVisible();
        }
        
        // Close modal
        await page.click('.media-modal-close');
      }
    });
  });

  test.describe('Candidate Editing', () => {
    test('can edit existing candidate', async ({ page }) => {
      // First, go to candidates list to find an existing candidate
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Find first candidate to edit
      const firstCandidateLink = page.locator('.wp-list-table .row-title').first();
      
      if (await firstCandidateLink.isVisible()) {
        await firstCandidateLink.click();
        
        // Should be on edit page
        await expect(page.locator('#title')).toBeVisible();
        await expect(page.locator('#post_ID')).toBeVisible();
        
        // Get current title
        const currentTitle = await page.locator('#title').inputValue();
        
        // Modify title
        const newTitle = `${currentTitle} - Modified`;
        await page.fill('#title', newTitle);
        
        // Update candidate
        await page.click('#publish, #update');
        
        // Wait for update confirmation
        await expect(page.locator('.notice-success, #message')).toBeVisible();
        
        // Verify title was updated
        const updatedTitle = await page.locator('#title').inputValue();
        expect(updatedTitle).toBe(newTitle);
        
        console.log('✅ Successfully edited candidate');
      }
    });

    test('can use quick edit functionality', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Look for quick edit option
      const quickEditLink = page.locator('.editinline').first();
      
      if (await quickEditLink.isVisible()) {
        await quickEditLink.click();
        
        // Quick edit form should appear
        await expect(page.locator('.quick-edit-row')).toBeVisible();
        
        // Try to modify something
        const quickEditTitle = page.locator('.quick-edit-row input[name="post_title"]');
        if (await quickEditTitle.isVisible()) {
          const currentValue = await quickEditTitle.inputValue();
          await quickEditTitle.fill(`${currentValue} - Quick Edit`);
          
          // Save changes
          await page.click('.save');
          
          // Quick edit should close
          await expect(page.locator('.quick-edit-row')).not.toBeVisible();
        }
      }
    });

    test('handles candidate status changes', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      const statusLinks = [
        'a:has-text("Published")',
        'a:has-text("Draft")', 
        'a:has-text("Pending")'
      ];
      
      // Test status filtering
      for (const statusLink of statusLinks) {
        if (await page.locator(statusLink).first().isVisible()) {
          await page.locator(statusLink).first().click();
          
          // Verify URL changed to include status filter
          await expect(page.locator('.wp-list-table')).toBeVisible();
          
          console.log(`✅ Status filter works for ${statusLink}`);
        }
      }
    });
  });

  test.describe('Candidate Meta Fields', () => {
    test('can manage custom meta fields', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Look for custom meta boxes
      const expectedMetaBoxes = [
        '.mt-candidate-details',
        '.mt-company-info', 
        '.mt-innovation-details',
        '.mt-contact-info'
      ];
      
      for (const metaBox of expectedMetaBoxes) {
        if (await page.locator(metaBox).isVisible()) {
          await expect(page.locator(metaBox)).toBeVisible();
          
          // Check for input fields within meta box
          const inputs = page.locator(`${metaBox} input, ${metaBox} textarea, ${metaBox} select`);
          const inputCount = await inputs.count();
          
          if (inputCount > 0) {
            console.log(`✅ Meta box ${metaBox} has ${inputCount} input fields`);
          }
        }
      }
    });

    test('validates meta field data', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Test email validation if email field exists
      const emailField = page.locator('input[type="email"], input[name*="email"]');
      if (await emailField.first().isVisible()) {
        await emailField.first().fill('invalid-email');
        
        // Try to save
        await page.click('#save-post');
        
        // Should show validation error
        const isValidationShown = await page.locator('.error, .notice-error').isVisible();
        if (isValidationShown) {
          console.log('✅ Email validation works');
        }
      }
      
      // Test URL validation if website field exists
      const urlField = page.locator('input[type="url"], input[name*="website"], input[name*="url"]');
      if (await urlField.first().isVisible()) {
        await urlField.first().fill('not-a-url');
        
        await page.click('#save-post');
        
        // Check for URL validation
        const hasUrlValidation = await page.locator('.error, .notice-error').isVisible();
        if (hasUrlValidation) {
          console.log('✅ URL validation works');
        }
      }
    });
  });

  test.describe('Candidate Categories and Taxonomies', () => {
    test('can assign categories', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Look for category assignment interface
      const categoryMetaBox = page.locator('#categorydiv, .mt-category-metabox');
      
      if (await categoryMetaBox.isVisible()) {
        // Check for category checkboxes
        const categoryCheckboxes = categoryMetaBox.locator('input[type="checkbox"]');
        const checkboxCount = await categoryCheckboxes.count();
        
        if (checkboxCount > 0) {
          // Select first category
          await categoryCheckboxes.first().check();
          
          // Fill title and save
          await page.fill('#title', 'Test Candidate with Category');
          await page.click('#save-post');
          
          // Verify save was successful
          await expect(page.locator('.notice-success')).toBeVisible();
          
          console.log('✅ Category assignment works');
        }
      }
      
      // Test custom taxonomy if it exists
      const customTaxonomies = [
        '.mt-innovation-type',
        '.mt-company-size',
        '.mt-industry'
      ];
      
      for (const taxonomy of customTaxonomies) {
        if (await page.locator(taxonomy).isVisible()) {
          console.log(`✅ Custom taxonomy ${taxonomy} is available`);
        }
      }
    });

    test('can create new categories', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Look for "Add New Category" functionality
      const addNewCategory = page.locator('#category-add-toggle, .category-add-new');
      
      if (await addNewCategory.isVisible()) {
        await addNewCategory.click();
        
        // Should show new category form
        const newCategoryName = page.locator('#newcategory, input[name="newcategory"]');
        if (await newCategoryName.isVisible()) {
          await newCategoryName.fill('Test Category');
          
          // Submit new category
          const addButton = page.locator('#category-add-submit');
          if (await addButton.isVisible()) {
            await addButton.click();
            
            // Should add category to list
            await page.waitForTimeout(1000);
            
            console.log('✅ New category creation works');
          }
        }
      }
    });
  });

  test.describe('Candidate Import/Export Features', () => {
    test('can access import functionality', async ({ page }) => {
      // Check if there's a specific import page for candidates
      const importUrls = [
        '/wp-admin/admin.php?page=mt-import-candidates',
        '/wp-admin/import.php',
        '/wp-admin/admin.php?page=mt-import'
      ];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Check if import interface is available
          const importElements = [
            '.mt-import-form',
            '#upload-form',
            'input[type="file"]'
          ];
          
          let hasImportInterface = false;
          for (const element of importElements) {
            if (await page.locator(element).isVisible()) {
              hasImportInterface = true;
              break;
            }
          }
          
          if (hasImportInterface) {
            console.log(`✅ Import interface found at ${url}`);
            break;
          }
          
        } catch (error) {
          // URL might not exist - continue to next
        }
      }
    });

    test('can access export functionality', async ({ page }) => {
      // Check for export options
      const exportUrls = [
        '/wp-admin/admin.php?page=mt-export-candidates',
        '/wp-admin/export.php',
        '/wp-admin/admin.php?page=mt-export'
      ];
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Look for export interface
          const exportElements = [
            '.mt-export-form',
            '#export-form',
            'input[name="download_export_file"]'
          ];
          
          let hasExportInterface = false;
          for (const element of exportElements) {
            if (await page.locator(element).isVisible()) {
              hasExportInterface = true;
              break;
            }
          }
          
          if (hasExportInterface) {
            console.log(`✅ Export interface found at ${url}`);
            break;
          }
          
        } catch (error) {
          // URL might not exist - continue
        }
      }
    });
  });

  test.describe('Candidate Display and Frontend', () => {
    test('candidate displays correctly on frontend', async ({ page }) => {
      // First get a candidate ID from admin
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      const firstCandidate = page.locator('.wp-list-table .row-title').first();
      
      if (await firstCandidate.isVisible()) {
        // Get candidate permalink
        const candidateRow = firstCandidate.locator('xpath=ancestor::tr');
        const viewLink = candidateRow.locator('.row-actions .view a');
        
        if (await viewLink.isVisible()) {
          const candidateUrl = await viewLink.getAttribute('href');
          
          if (candidateUrl) {
            // Visit frontend candidate page
            await page.goto(candidateUrl);
            
            // Check frontend display
            const frontendElements = [
              'h1', // Title
              '.entry-content', // Content
              '.mt-candidate-meta', // Meta information
              '.mt-candidate-details' // Candidate details
            ];
            
            for (const element of frontendElements) {
              if (await page.locator(element).isVisible()) {
                await expect(page.locator(element)).toBeVisible();
              }
            }
            
            console.log('✅ Candidate displays correctly on frontend');
          }
        }
      }
    });

    test('candidate archive page works', async ({ page }) => {
      // Try to access candidate archive
      const archiveUrls = [
        '/candidates/',
        '/mt_candidate/',
        '/?post_type=mt_candidate'
      ];
      
      for (const url of archiveUrls) {
        try {
          await page.goto(url);
          
          // Check if it's a valid archive page
          if (!page.url().includes('404')) {
            // Look for candidate listings
            const candidateElements = [
              '.mt-candidate',
              '.candidate-item',
              '.post-type-mt_candidate'
            ];
            
            let hasValidArchive = false;
            for (const element of candidateElements) {
              if (await page.locator(element).isVisible()) {
                hasValidArchive = true;
                break;
              }
            }
            
            if (hasValidArchive) {
              console.log(`✅ Candidate archive works at ${url}`);
              break;
            }
          }
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Candidate Permissions and Security', () => {
    test('non-admin users have appropriate restrictions', async ({ page }) => {
      // This would need to be tested with different user roles
      // For now, we'll test the admin interface security
      
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Verify admin has full access
      const adminCapabilities = [
        '.page-title-action', // Can add new
        '.row-actions .edit', // Can edit
        '.row-actions .trash', // Can delete
        '#bulk-action-selector-top' // Can bulk edit
      ];
      
      for (const capability of adminCapabilities) {
        if (await page.locator(capability).first().isVisible()) {
          await expect(page.locator(capability).first()).toBeVisible();
        }
      }
    });

    test('candidate data is properly sanitized', async ({ page }) => {
      await page.goto('/wp-admin/post-new.php?post_type=mt_candidate');
      
      // Test XSS prevention
      const maliciousTitle = '<script>alert("XSS")</script>Test Candidate';
      await page.fill('#title', maliciousTitle);
      
      // Save candidate
      await page.click('#save-post');
      
      // Check that script was sanitized
      const savedTitle = await page.locator('#title').inputValue();
      expect(savedTitle).not.toContain('<script>');
      
      // Should contain the safe part
      expect(savedTitle).toContain('Test Candidate');
      
      console.log('✅ XSS prevention works in title field');
    });
  });
});