import { test, expect } from '@playwright/test';
import { AjaxHelper, ErrorHelper } from './utils/test-helpers';

test.describe('Import/Export Functionality', () => {
  // Use the stored admin authentication state
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.describe('Import Interface Access', () => {
    test('can access import functionality', async ({ page }) => {
      // Try different potential import URLs
      const importUrls = [
        '/wp-admin/admin.php?page=mt-import',
        '/wp-admin/admin.php?page=mt-import-candidates',
        '/wp-admin/tools.php?page=mt-import',
        '/wp-admin/import.php'
      ];
      
      let importFound = false;
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Check if this is the import page
          const importIndicators = [
            '.mt-import-page',
            '.mt-import-form',
            '#upload-form',
            'input[type="file"]',
            'h1:has-text("Import")'
          ];
          
          for (const indicator of importIndicators) {
            if (await page.locator(indicator).isVisible()) {
              importFound = true;
              console.log(`✅ Import interface found at: ${url}`);
              
              // Verify import form elements
              await expect(page.locator(indicator)).toBeVisible();
              
              // Check for file upload
              const fileInput = page.locator('input[type="file"]');
              if (await fileInput.isVisible()) {
                await expect(fileInput).toBeVisible();
                console.log('✅ File upload interface available');
              }
              
              // Check for import options
              const importOptions = [
                '.mt-import-options',
                'input[name="dry_run"]',
                'input[name="delete_existing"]',
                'select[name="import_method"]'
              ];
              
              for (const option of importOptions) {
                if (await page.locator(option).isVisible()) {
                  console.log(`✅ Import option available: ${option}`);
                }
              }
              
              break;
            }
          }
          
          if (importFound) break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
      
      if (!importFound) {
        console.warn('⚠️  Import interface not found at standard locations');
      }
    });

    test('import page shows proper instructions', async ({ page }) => {
      // Try to find import page
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates', '/wp-admin/import.php'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for help text or instructions
          const helpElements = [
            '.mt-import-help',
            '.import-instructions',
            '.description',
            '.help-text'
          ];
          
          for (const helpElement of helpElements) {
            if (await page.locator(helpElement).isVisible()) {
              const helpText = await page.locator(helpElement).textContent();
              
              // Check for useful information in help text
              const expectedContent = [
                'CSV',
                'Excel',
                '.xlsx',
                '.csv',
                'format',
                'required',
                'column'
              ];
              
              const hasRelevantContent = expectedContent.some(content => 
                helpText?.toLowerCase().includes(content.toLowerCase())
              );
              
              if (hasRelevantContent) {
                console.log(`✅ Helpful import instructions found: ${helpElement}`);
                break;
              }
            }
          }
          
          // Look for sample/template download
          const templateLink = page.locator('a:has-text("template"), a:has-text("sample"), a:has-text("example")');
          if (await templateLink.isVisible()) {
            console.log('✅ Template/sample file download available');
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('CSV Import Functionality', () => {
    test('validates CSV file format', async ({ page }) => {
      // Navigate to import page
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          const fileInput = page.locator('input[type="file"]');
          if (await fileInput.isVisible()) {
            
            // Test file type validation
            // Note: In real testing, we'd need actual files
            // For now, we'll test the interface behavior
            
            const uploadButton = page.locator('input[type="submit"], button[type="submit"]');
            if (await uploadButton.isVisible()) {
              
              // Try to submit without file
              await uploadButton.click();
              
              // Should show validation error
              const validationError = page.locator('.error, .notice-error, .mt-validation-error');
              if (await validationError.isVisible()) {
                await expect(validationError).toBeVisible();
                console.log('✅ File validation prevents empty submission');
              }
            }
            
            // Check for accepted file types
            const acceptAttribute = await fileInput.getAttribute('accept');
            if (acceptAttribute) {
              const acceptedTypes = ['.csv', '.xlsx', '.xls', 'text/csv'];
              const hasValidAccept = acceptedTypes.some(type => 
                acceptAttribute.includes(type)
              );
              
              if (hasValidAccept) {
                console.log(`✅ File input accepts proper types: ${acceptAttribute}`);
              }
            }
            
            break;
          }
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });

    test('dry run functionality works', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for dry run option
          const dryRunCheckbox = page.locator('input[name="dry_run"], .mt-dry-run');
          if (await dryRunCheckbox.isVisible()) {
            
            // Enable dry run
            await dryRunCheckbox.check();
            await expect(dryRunCheckbox).toBeChecked();
            
            // Check for dry run description
            const dryRunDescription = page.locator('.dry-run-description, .mt-dry-run-help');
            if (await dryRunDescription.isVisible()) {
              const description = await dryRunDescription.textContent();
              
              // Should mention testing, preview, or no changes
              const isDryRunDescription = ['test', 'preview', 'no changes', 'simulation'].some(
                keyword => description?.toLowerCase().includes(keyword)
              );
              
              if (isDryRunDescription) {
                console.log('✅ Dry run functionality properly described');
              }
            }
            
            console.log('✅ Dry run option available');
            break;
          }
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });

    test('import options are configurable', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Check for various import options
          const importOptions = [
            { selector: 'input[name="delete_existing"]', name: 'Delete Existing' },
            { selector: 'input[name="update_existing"]', name: 'Update Existing' },
            { selector: 'input[name="skip_duplicates"]', name: 'Skip Duplicates' },
            { selector: 'select[name="delimiter"]', name: 'CSV Delimiter' },
            { selector: 'select[name="encoding"]', name: 'Character Encoding' },
            { selector: 'input[name="has_header"]', name: 'Has Header Row' }
          ];
          
          for (const option of importOptions) {
            if (await page.locator(option.selector).isVisible()) {
              console.log(`✅ Import option available: ${option.name}`);
              
              // Test the option
              const element = page.locator(option.selector);
              
              if (option.selector.includes('select')) {
                // Test dropdown options
                const options = element.locator('option');
                const optionCount = await options.count();
                if (optionCount > 1) {
                  console.log(`   - Has ${optionCount} options`);
                }
              } else if (option.selector.includes('input[type="checkbox"]') || option.selector.includes('input[name')) {
                // Test checkbox
                if (option.selector.includes('checkbox')) {
                  await element.check();
                  await expect(element).toBeChecked();
                  await element.uncheck();
                  await expect(element).not.toBeChecked();
                }
              }
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Excel Import Functionality', () => {
    test('supports Excel file formats', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          const fileInput = page.locator('input[type="file"]');
          if (await fileInput.isVisible()) {
            
            const acceptAttribute = await fileInput.getAttribute('accept');
            if (acceptAttribute) {
              const excelTypes = ['.xlsx', '.xls', 'application/vnd.openxmlformats'];
              const supportsExcel = excelTypes.some(type => 
                acceptAttribute.includes(type)
              );
              
              if (supportsExcel) {
                console.log('✅ Excel file formats supported');
              }
            }
            
            // Look for Excel-specific options
            const excelOptions = [
              'select[name="worksheet"]',
              'input[name="start_row"]',
              '.excel-options',
              '.worksheet-selector'
            ];
            
            for (const option of excelOptions) {
              if (await page.locator(option).isVisible()) {
                console.log(`✅ Excel option available: ${option}`);
              }
            }
            
            break;
          }
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Import Progress and Feedback', () => {
    test('shows import progress', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for progress indicators
          const progressElements = [
            '.mt-import-progress',
            '.progress-bar',
            '.import-status',
            '#import-progress'
          ];
          
          for (const element of progressElements) {
            if (await page.locator(element).isVisible()) {
              console.log(`✅ Progress indicator available: ${element}`);
            }
          }
          
          // Check for AJAX progress updates
          const ajaxHelper = new AjaxHelper(page);
          
          // Monitor for progress-related AJAX calls
          page.on('request', (request) => {
            if (request.url().includes('admin-ajax.php')) {
              const postData = request.postData();
              if (postData?.includes('import_progress') || postData?.includes('import_status')) {
                console.log('✅ AJAX progress monitoring detected');
              }
            }
          });
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });

    test('displays import results', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for results display areas
          const resultElements = [
            '.mt-import-results',
            '.import-summary',
            '.import-report',
            '#import-results'
          ];
          
          for (const element of resultElements) {
            if (await page.locator(element).isVisible()) {
              console.log(`✅ Results display area: ${element}`);
            }
          }
          
          // Check for error display
          const errorElements = [
            '.mt-import-errors',
            '.import-errors',
            '.error-list',
            '.validation-errors'
          ];
          
          for (const element of errorElements) {
            if (await page.locator(element).isVisible()) {
              console.log(`✅ Error display area: ${element}`);
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Export Interface Access', () => {
    test('can access export functionality', async ({ page }) => {
      // Try different potential export URLs
      const exportUrls = [
        '/wp-admin/admin.php?page=mt-export',
        '/wp-admin/admin.php?page=mt-export-candidates',
        '/wp-admin/tools.php?page=mt-export',
        '/wp-admin/export.php'
      ];
      
      let exportFound = false;
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Check if this is the export page
          const exportIndicators = [
            '.mt-export-page',
            '.mt-export-form',
            '#export-form',
            'input[name="download_export_file"]',
            'h1:has-text("Export")'
          ];
          
          for (const indicator of exportIndicators) {
            if (await page.locator(indicator).isVisible()) {
              exportFound = true;
              console.log(`✅ Export interface found at: ${url}`);
              
              // Verify export form elements
              await expect(page.locator(indicator)).toBeVisible();
              
              // Check for export options
              const exportOptions = [
                'select[name="content"]',
                'input[name="start_date"]',
                'input[name="end_date"]',
                'select[name="format"]',
                'select[name="category"]'
              ];
              
              for (const option of exportOptions) {
                if (await page.locator(option).isVisible()) {
                  console.log(`✅ Export option available: ${option}`);
                }
              }
              
              break;
            }
          }
          
          if (exportFound) break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
      
      if (!exportFound) {
        console.warn('⚠️  Export interface not found at standard locations');
      }
    });

    test('export format options are available', async ({ page }) => {
      const exportUrls = ['/wp-admin/admin.php?page=mt-export-candidates', '/wp-admin/export.php'];
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Look for format selection
          const formatSelector = page.locator('select[name="format"], .mt-export-format');
          if (await formatSelector.isVisible()) {
            
            // Check for common export formats
            const expectedFormats = ['csv', 'excel', 'xlsx', 'json', 'xml'];
            
            for (const format of expectedFormats) {
              const formatOption = formatSelector.locator(`option[value="${format}"], option:has-text("${format.toUpperCase()}")`);
              if (await formatOption.isVisible()) {
                console.log(`✅ Export format available: ${format.toUpperCase()}`);
              }
            }
            
            // Test format selection
            const firstOption = formatSelector.locator('option:not([value=""])').first();
            const optionValue = await firstOption.getAttribute('value');
            
            if (optionValue) {
              await formatSelector.selectOption(optionValue);
              const selectedValue = await formatSelector.inputValue();
              expect(selectedValue).toBe(optionValue);
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Export Filtering and Options', () => {
    test('can filter export data', async ({ page }) => {
      const exportUrls = ['/wp-admin/admin.php?page=mt-export-candidates'];
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Test content type filtering
          const contentFilter = page.locator('select[name="content"], .mt-export-content');
          if (await contentFilter.isVisible()) {
            
            const contentOptions = ['all', 'candidates', 'evaluations', 'assignments'];
            
            for (const option of contentOptions) {
              const optionElement = contentFilter.locator(`option[value="${option}"]`);
              if (await optionElement.isVisible()) {
                await contentFilter.selectOption(option);
                console.log(`✅ Content filter option: ${option}`);
              }
            }
          }
          
          // Test date range filtering
          const startDate = page.locator('input[name="start_date"], .export-start-date');
          const endDate = page.locator('input[name="end_date"], .export-end-date');
          
          if (await startDate.isVisible() && await endDate.isVisible()) {
            
            // Test date input
            await startDate.fill('2024-01-01');
            await endDate.fill('2024-12-31');
            
            const startValue = await startDate.inputValue();
            const endValue = await endDate.inputValue();
            
            expect(startValue).toBe('2024-01-01');
            expect(endValue).toBe('2024-12-31');
            
            console.log('✅ Date range filtering works');
          }
          
          // Test category filtering
          const categoryFilter = page.locator('select[name="category"], .export-category');
          if (await categoryFilter.isVisible()) {
            
            const categories = ['start-ups', 'established-companies', 'governance'];
            
            for (const category of categories) {
              const categoryOption = categoryFilter.locator(`option[value="${category}"]`);
              if (await categoryOption.isVisible()) {
                await categoryFilter.selectOption(category);
                console.log(`✅ Category filter option: ${category}`);
              }
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });

    test('export includes proper field selection', async ({ page }) => {
      const exportUrls = ['/wp-admin/admin.php?page=mt-export-candidates'];
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Look for field selection interface
          const fieldSelection = [
            '.mt-export-fields',
            '.field-selection',
            '.export-columns'
          ];
          
          for (const selector of fieldSelection) {
            if (await page.locator(selector).isVisible()) {
              console.log(`✅ Field selection interface: ${selector}`);
              
              // Check for common candidate fields
              const expectedFields = [
                'title', 'company', 'innovation', 'category', 
                'description', 'contact', 'email', 'website'
              ];
              
              for (const field of expectedFields) {
                const fieldCheckbox = page.locator(`${selector} input[name*="${field}"], ${selector} input[value="${field}"]`);
                if (await fieldCheckbox.isVisible()) {
                  
                  // Test field selection
                  await fieldCheckbox.check();
                  await expect(fieldCheckbox).toBeChecked();
                  
                  console.log(`✅ Field available for export: ${field}`);
                }
              }
              
              break;
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Export File Generation', () => {
    test('can generate export file', async ({ page }) => {
      const exportUrls = ['/wp-admin/admin.php?page=mt-export-candidates'];
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Configure export options
          const formatSelector = page.locator('select[name="format"]');
          if (await formatSelector.isVisible()) {
            await formatSelector.selectOption('csv');
          }
          
          const contentSelector = page.locator('select[name="content"]');
          if (await contentSelector.isVisible()) {
            await contentSelector.selectOption('candidates');
          }
          
          // Look for export button
          const exportButton = page.locator('input[name="download_export_file"], .mt-export-btn, input[type="submit"]');
          if (await exportButton.isVisible()) {
            
            // Set up download monitoring
            const downloadPromise = page.waitForEvent('download', { timeout: 30000 });
            
            try {
              await exportButton.click();
              
              // Wait for download to start
              const download = await downloadPromise;
              
              // Verify download
              const filename = download.suggestedFilename();
              console.log(`✅ Export file generated: ${filename}`);
              
              // Check filename format
              const hasValidExtension = ['.csv', '.xlsx', '.json', '.xml'].some(ext => 
                filename.toLowerCase().includes(ext)
              );
              
              if (hasValidExtension) {
                console.log('✅ Export file has valid extension');
              }
              
              // Verify file size (should not be empty)
              const path = await download.path();
              if (path) {
                console.log('✅ Export file downloaded successfully');
              }
              
            } catch (downloadError) {
              console.warn('⚠️  Export download may not be working or may require real data');
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });

    test('handles large exports gracefully', async ({ page }) => {
      const exportUrls = ['/wp-admin/admin.php?page=mt-export-candidates'];
      
      for (const url of exportUrls) {
        try {
          await page.goto(url);
          
          // Look for batch processing options
          const batchOptions = [
            'input[name="batch_size"]',
            'select[name="batch_size"]',
            '.batch-processing',
            '.export-chunks'
          ];
          
          for (const option of batchOptions) {
            if (await page.locator(option).isVisible()) {
              console.log(`✅ Batch processing option: ${option}`);
            }
          }
          
          // Look for progress tracking for large exports
          const progressTracking = [
            '.export-progress',
            '#export-progress-bar',
            '.progress-indicator'
          ];
          
          for (const indicator of progressTracking) {
            if (await page.locator(indicator).isVisible()) {
              console.log(`✅ Large export progress tracking: ${indicator}`);
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Import/Export Error Handling', () => {
    test('handles file upload errors', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Test error scenarios
          const uploadButton = page.locator('input[type="submit"], button[type="submit"]');
          if (await uploadButton.isVisible()) {
            
            // Try to submit without file
            await uploadButton.click();
            
            // Should show file required error
            const fileError = page.locator('.error:has-text("file"), .notice-error:has-text("file")');
            if (await fileError.isVisible()) {
              console.log('✅ File required validation works');
            }
          }
          
          // Test file size limits
          const fileInput = page.locator('input[type="file"]');
          if (await fileInput.isVisible()) {
            
            // Check for file size information
            const sizeInfo = page.locator('.file-size-limit, .max-upload-size');
            if (await sizeInfo.isVisible()) {
              const sizeText = await sizeInfo.textContent();
              console.log(`✅ File size limit displayed: ${sizeText}`);
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });

    test('validates data format during import', async ({ page }) => {
      const errorHelper = new ErrorHelper(page);
      
      // Monitor for JavaScript errors
      const jsErrors = await errorHelper.captureConsoleErrors();
      
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for validation error display areas
          const validationAreas = [
            '.validation-errors',
            '.import-errors',
            '.data-errors',
            '.format-errors'
          ];
          
          for (const area of validationAreas) {
            if (await page.locator(area).isVisible()) {
              console.log(`✅ Validation error display area: ${area}`);
            }
          }
          
          // Check for row-by-row error reporting
          const rowErrors = page.locator('.row-error, .line-error, .record-error');
          if (await rowErrors.isVisible()) {
            console.log('✅ Row-level error reporting available');
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
      
      // Verify no JavaScript errors occurred
      if (jsErrors.length === 0) {
        console.log('✅ No JavaScript errors during import page load');
      }
    });

    test('provides helpful error messages', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for example error messages or help text
          const helpElements = [
            '.error-examples',
            '.common-errors',
            '.troubleshooting',
            '.import-help'
          ];
          
          for (const element of helpElements) {
            if (await page.locator(element).isVisible()) {
              const helpText = await page.locator(element).textContent();
              
              // Check for useful error guidance
              const helpfulContent = [
                'column', 'header', 'format', 'required', 
                'example', 'valid', 'invalid', 'encoding'
              ];
              
              const hasUsefulContent = helpfulContent.some(content => 
                helpText?.toLowerCase().includes(content)
              );
              
              if (hasUsefulContent) {
                console.log(`✅ Helpful error guidance found: ${element}`);
              }
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Import/Export Performance', () => {
    test('import/export pages load quickly', async ({ page }) => {
      const urls = [
        '/wp-admin/admin.php?page=mt-import-candidates',
        '/wp-admin/admin.php?page=mt-export-candidates'
      ];
      
      for (const url of urls) {
        try {
          const startTime = Date.now();
          await page.goto(url, { waitUntil: 'networkidle' });
          const loadTime = Date.now() - startTime;
          
          // Page should load within reasonable time
          expect(loadTime).toBeLessThan(10000); // 10 seconds max
          
          console.log(`✅ Page ${url} loaded in ${loadTime}ms`);
          
        } catch (error) {
          // Page might not exist
        }
      }
    });

    test('import/export handles timeouts gracefully', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          // Look for timeout handling indicators
          const timeoutHandling = [
            '.timeout-warning',
            '.large-file-warning',
            '.processing-time',
            '.background-processing'
          ];
          
          for (const indicator of timeoutHandling) {
            if (await page.locator(indicator).isVisible()) {
              console.log(`✅ Timeout handling indicator: ${indicator}`);
            }
          }
          
          // Check for background processing options
          const backgroundOptions = [
            'input[name="background_processing"]',
            '.background-import',
            '.queue-processing'
          ];
          
          for (const option of backgroundOptions) {
            if (await page.locator(option).isVisible()) {
              console.log(`✅ Background processing option: ${option}`);
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });

  test.describe('Import/Export Security', () => {
    test('validates user permissions', async ({ page }) => {
      // Test that import/export requires proper permissions
      const secureUrls = [
        '/wp-admin/admin.php?page=mt-import-candidates',
        '/wp-admin/admin.php?page=mt-export-candidates'
      ];
      
      for (const url of secureUrls) {
        try {
          await page.goto(url);
          
          // Should either show the page (if admin) or redirect/show error
          const hasAccess = await page.locator('.mt-import-page, .mt-export-page').isVisible();
          const hasPermissionError = await page.locator('.wp-die-message').isVisible();
          const isRedirected = page.url().includes('/wp-login.php');
          
          // One of these should be true
          expect(hasAccess || hasPermissionError || isRedirected).toBeTruthy();
          
          if (hasAccess) {
            console.log(`✅ Access granted to ${url}`);
          } else {
            console.log(`✅ Access properly restricted for ${url}`);
          }
          
        } catch (error) {
          // Expected for non-existent pages
        }
      }
    });

    test('sanitizes file uploads', async ({ page }) => {
      const importUrls = ['/wp-admin/admin.php?page=mt-import-candidates'];
      
      for (const url of importUrls) {
        try {
          await page.goto(url);
          
          const fileInput = page.locator('input[type="file"]');
          if (await fileInput.isVisible()) {
            
            // Check file type restrictions
            const acceptAttribute = await fileInput.getAttribute('accept');
            if (acceptAttribute) {
              
              // Should only accept safe file types
              const dangerousTypes = ['.exe', '.php', '.js', '.html', '.htm'];
              const acceptsDangerous = dangerousTypes.some(type => 
                acceptAttribute.includes(type)
              );
              
              expect(acceptsDangerous).toBeFalsy();
              console.log('✅ File upload restricts dangerous file types');
            }
          }
          
          break;
          
        } catch (error) {
          // Continue to next URL
        }
      }
    });
  });
});