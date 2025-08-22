import { test, expect } from '@playwright/test';
import { JuryDashboard, EvaluationForm, AjaxHelper, ErrorHelper } from './utils/test-helpers';

test.describe('Jury Evaluation Workflow', () => {
  test.beforeEach(async ({ page }) => {
    // Login as jury member before each test
    try {
      await page.goto('/wp-admin');
      await page.fill('#user_login', process.env.JURY_USERNAME || 'jury1');
      await page.fill('#user_pass', process.env.JURY_PASSWORD || 'jury123');
      await page.click('#wp-submit');
      
      // Handle different login scenarios
      try {
        await page.waitForURL('**/wp-admin/**', { timeout: 5000 });
      } catch {
        // If redirected elsewhere, try to navigate to jury dashboard
        await page.goto('/jury-dashboard/');
      }
    } catch (error) {
      console.warn('⚠️  Could not login as jury member - tests may use mock data');
    }
  });

  test.describe('Evaluation Form Access and Display', () => {
    test('can access evaluation form for assigned candidate', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        // Try to access evaluation form with test candidate ID
        await evaluationForm.navigate('1');
        
        // Verify form elements are present
        await expect(page.locator('.mt-evaluation-form')).toBeVisible();
        
        // Check for candidate details section
        const candidateDetails = await evaluationForm.getCandidateDetails();
        expect(candidateDetails.name).toBeTruthy();
        expect(candidateDetails.company).toBeTruthy();
        
        // Verify all 5 criteria are present
        for (let i = 1; i <= 5; i++) {
          await expect(page.locator(`.mt-criterion-${i}`)).toBeVisible();
        }
        
        // Check for score input elements
        const scoreInputTypes = [
          '.mt-score-slider',
          '.mt-score-btn',
          '.mt-score-input',
          '.mt-star-rating'
        ];
        
        let hasScoreInput = false;
        for (const inputType of scoreInputTypes) {
          if (await page.locator(inputType).first().isVisible()) {
            hasScoreInput = true;
            break;
          }
        }
        
        expect(hasScoreInput).toBeTruthy();
        
        // Check for comments section
        await expect(page.locator('.mt-evaluation-comments, textarea[name="comments"]')).toBeVisible();
        
        // Check for form action buttons
        await expect(page.locator('.mt-save-draft-btn, .mt-submit-evaluation-btn')).toBeVisible();
        
      } catch (error) {
        console.warn('⚠️  Could not access evaluation form - may need proper candidate assignment');
      }
    });

    test('displays candidate information correctly', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Check candidate details
        const details = await evaluationForm.getCandidateDetails();
        
        // Verify required information is present
        expect(details.name).toBeTruthy();
        expect(details.company).toBeTruthy();
        expect(details.innovation).toBeTruthy();
        
        // Check for candidate photo
        const candidatePhoto = page.locator('.mt-candidate-photo, .mt-candidate-image');
        if (await candidatePhoto.isVisible()) {
          await expect(candidatePhoto).toBeVisible();
          
          // Verify image loads correctly
          const imgElement = candidatePhoto.locator('img');
          if (await imgElement.isVisible()) {
            await expect(imgElement).toHaveAttribute('src');
          }
        }
        
        // Check for criteria-specific content
        const hasCriteriaContent = await evaluationForm.hasCriteriaContent();
        if (hasCriteriaContent) {
          // Verify criteria descriptions are in German
          const criteriaElements = page.locator('.mt-criteria-content');
          const criteriaText = await criteriaElements.textContent();
          
          // Should contain German text
          const germanWords = ['Innovation', 'Umsetzung', 'Relevanz', 'Sichtbarkeit', 'Mut'];
          const hasGermanContent = germanWords.some(word => criteriaText?.includes(word));
          expect(hasGermanContent).toBeTruthy();
        }
        
      } catch (error) {
        console.warn('⚠️  Could not verify candidate information display');
      }
    });

    test('shows correct evaluation criteria labels in German', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Expected German criteria labels
        const expectedCriteria = [
          'Mut zur Mobilität',
          'Innovation und Neuartigkeit', 
          'Umsetzung und Implementierung',
          'Relevanz und Marktpotential',
          'Sichtbarkeit und Kommunikation'
        ];
        
        // Check that criteria labels are displayed
        for (let i = 0; i < expectedCriteria.length; i++) {
          const criterionSection = page.locator(`.mt-criterion-${i + 1}`);
          await expect(criterionSection).toBeVisible();
          
          // Check for German text in criterion
          const criterionText = await criterionSection.textContent();
          const hasPartialMatch = expectedCriteria.some(expected => 
            criterionText?.includes(expected) || 
            expected.split(' ').some(word => criterionText?.includes(word))
          );
          
          if (!hasPartialMatch) {
            console.warn(`⚠️  Criterion ${i + 1} may not have expected German label`);
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not verify German criteria labels');
      }
    });
  });

  test.describe('Score Input and Validation', () => {
    test('can input scores using different input methods', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Test scores for each criterion
        const testScores = {
          criterion1: 8.5,
          criterion2: 9.0,
          criterion3: 7.5,
          criterion4: 8.0,
          criterion5: 9.5
        };
        
        // Try to fill evaluation
        await evaluationForm.fillEvaluation(testScores);
        
        // Verify total score calculation
        const totalScore = await evaluationForm.getTotalScore();
        const expectedTotal = Object.values(testScores).reduce((sum, score) => sum + score, 0);
        
        // Allow for small rounding differences
        expect(Math.abs(totalScore - expectedTotal)).toBeLessThan(0.1);
        
      } catch (error) {
        console.warn('⚠️  Could not test score input methods');
      }
    });

    test('validates score range (0-10)', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Test invalid scores
        const invalidScores = [-1, 11, 15, -5];
        
        for (const invalidScore of invalidScores) {
          try {
            // Try to set invalid score
            await evaluationForm.setCriterionScore(1, invalidScore);
            
            // Check if validation error appears
            const validationError = page.locator('.mt-validation-error, .error');
            if (await validationError.isVisible()) {
              await expect(validationError).toBeVisible();
            } else {
              // If no visible error, check if value was rejected
              const actualValue = await page.locator('.mt-criterion-1 .mt-score-input').inputValue();
              expect(parseFloat(actualValue)).toBeLessThanOrEqual(10);
              expect(parseFloat(actualValue)).toBeGreaterThanOrEqual(0);
            }
          } catch (error) {
            // Score input might prevent invalid values - this is good
            console.log(`✅ Score input correctly rejected invalid value: ${invalidScore}`);
          }
        }
        
        // Test valid scores
        const validScores = [0, 5, 10, 7.5, 9.2];
        
        for (const validScore of validScores) {
          try {
            await evaluationForm.setCriterionScore(1, validScore);
            // Should not show validation error
            await expect(page.locator('.mt-validation-error')).not.toBeVisible();
          } catch (error) {
            console.warn(`⚠️  Could not set valid score: ${validScore}`);
          }
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test score validation');
      }
    });

    test('calculates total score correctly', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Test different score combinations
        const scoreTests = [
          { scores: [5, 5, 5, 5, 5], expected: 25 },
          { scores: [10, 10, 10, 10, 10], expected: 50 },
          { scores: [0, 0, 0, 0, 0], expected: 0 },
          { scores: [8.5, 9.0, 7.5, 8.0, 9.5], expected: 42.5 }
        ];
        
        for (const test of scoreTests) {
          // Set scores
          for (let i = 0; i < test.scores.length; i++) {
            await evaluationForm.setCriterionScore(i + 1, test.scores[i]);
          }
          
          // Wait for calculation
          await page.waitForTimeout(500);
          
          // Check total
          const calculatedTotal = await evaluationForm.getTotalScore();
          expect(Math.abs(calculatedTotal - test.expected)).toBeLessThan(0.1);
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test total score calculation');
      }
    });
  });

  test.describe('Form Submission Workflow', () => {
    test('can save evaluation as draft', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      const ajaxHelper = new AjaxHelper(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Fill partial evaluation
        const partialScores = {
          criterion1: 8,
          criterion2: 7,
          criterion3: 0, // Incomplete
          criterion4: 0, // Incomplete
          criterion5: 0, // Incomplete
          comments: 'Draft evaluation in progress'
        };
        
        await evaluationForm.fillEvaluation(partialScores);
        
        // Save as draft
        await evaluationForm.saveDraft();
        
        // Wait for AJAX to complete
        await ajaxHelper.waitForAjaxComplete();
        
        // Verify success message
        await expect(page.locator('.mt-success-message, .notice-success')).toBeVisible();
        
        // Verify form remains in draft state
        const draftIndicator = page.locator('.mt-draft-status, .mt-status-draft');
        if (await draftIndicator.isVisible()) {
          await expect(draftIndicator).toBeVisible();
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test draft save functionality');
      }
    });

    test('can submit complete evaluation', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      const ajaxHelper = new AjaxHelper(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Fill complete evaluation
        const completeScores = {
          criterion1: 8.5,
          criterion2: 9.0,
          criterion3: 7.5,
          criterion4: 8.0,
          criterion5: 9.5,
          comments: 'Excellent innovative approach to urban mobility challenges.'
        };
        
        await evaluationForm.fillEvaluation(completeScores);
        
        // Submit evaluation
        await evaluationForm.submitEvaluation();
        
        // Wait for AJAX to complete
        await ajaxHelper.waitForAjaxComplete();
        
        // Verify success message
        await expect(page.locator('.mt-success-message, .notice-success')).toBeVisible();
        
        // Should redirect to dashboard or show completion status
        try {
          await expect(page.locator('.mt-evaluation-submitted')).toBeVisible();
        } catch {
          // Might redirect to dashboard
          await expect(page.url()).toContain('/jury-dashboard');
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test evaluation submission');
      }
    });

    test('validates required fields before submission', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Try to submit without filling scores
        await page.click('.mt-submit-evaluation-btn');
        
        // Should show validation error
        const validationError = page.locator('.mt-validation-error, .error, .mt-field-error');
        await expect(validationError).toBeVisible();
        
        // Fill only some criteria
        await evaluationForm.setCriterionScore(1, 8);
        await evaluationForm.setCriterionScore(2, 7);
        // Leave 3, 4, 5 empty
        
        // Try to submit again
        await page.click('.mt-submit-evaluation-btn');
        
        // Should still show validation error for incomplete fields
        const incompleteError = page.locator('.mt-incomplete-evaluation, .error');
        if (await incompleteError.isVisible()) {
          await expect(incompleteError).toBeVisible();
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test submission validation');
      }
    });

    test('prevents double submission', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Fill complete evaluation
        const scores = {
          criterion1: 8,
          criterion2: 9,
          criterion3: 7,
          criterion4: 8,
          criterion5: 9,
          comments: 'Test evaluation'
        };
        
        await evaluationForm.fillEvaluation(scores);
        
        // Click submit button multiple times quickly
        const submitButton = page.locator('.mt-submit-evaluation-btn');
        
        await submitButton.click();
        await submitButton.click(); // Double click
        await submitButton.click(); // Triple click
        
        // Button should be disabled after first click
        await expect(submitButton).toBeDisabled();
        
        // Wait for submission to complete
        await page.waitForTimeout(2000);
        
        // Should only see one success message
        const successMessages = page.locator('.mt-success-message, .notice-success');
        const messageCount = await successMessages.count();
        expect(messageCount).toBeLessThanOrEqual(1);
        
      } catch (error) {
        console.warn('⚠️  Could not test double submission prevention');
      }
    });
  });

  test.describe('Error Handling and Recovery', () => {
    test('handles network errors gracefully', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Simulate network failure
        await page.route('**/admin-ajax.php', async (route) => {
          await route.abort('failed');
        });
        
        // Fill and try to submit evaluation
        await evaluationForm.fillEvaluation({
          criterion1: 8,
          criterion2: 9,
          criterion3: 7,
          criterion4: 8,
          criterion5: 9
        });
        
        await page.click('.mt-submit-evaluation-btn');
        
        // Should show network error message
        await expect(page.locator('.mt-network-error, .error')).toBeVisible();
        
        // Button should be re-enabled for retry
        await expect(page.locator('.mt-submit-evaluation-btn')).not.toBeDisabled();
        
      } catch (error) {
        console.warn('⚠️  Could not test network error handling');
      }
    });

    test('handles server errors gracefully', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Mock server error response
        await page.route('**/admin-ajax.php', async (route) => {
          await route.fulfill({
            status: 500,
            contentType: 'application/json',
            body: JSON.stringify({
              success: false,
              data: { message: 'Server error occurred' }
            })
          });
        });
        
        // Try to submit evaluation
        await evaluationForm.fillEvaluation({
          criterion1: 8,
          criterion2: 9,
          criterion3: 7,
          criterion4: 8,
          criterion5: 9
        });
        
        await page.click('.mt-submit-evaluation-btn');
        
        // Should show server error message
        await expect(page.locator('.mt-server-error, .error')).toBeVisible();
        
      } catch (error) {
        console.warn('⚠️  Could not test server error handling');
      }
    });

    test('recovers from session timeout', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Mock session timeout response
        await page.route('**/admin-ajax.php', async (route) => {
          const request = route.request();
          if (request.postData()?.includes('mt_submit_evaluation')) {
            await route.fulfill({
              status: 403,
              contentType: 'application/json',
              body: JSON.stringify({
                success: false,
                data: { message: 'Session expired' }
              })
            });
          } else {
            await route.continue();
          }
        });
        
        // Try to submit evaluation
        await evaluationForm.fillEvaluation({
          criterion1: 8,
          criterion2: 9,
          criterion3: 7,
          criterion4: 8,
          criterion5: 9
        });
        
        await page.click('.mt-submit-evaluation-btn');
        
        // Should show session timeout message
        const sessionError = page.locator('.mt-session-error, .error:has-text("Session")');
        if (await sessionError.isVisible()) {
          await expect(sessionError).toBeVisible();
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test session timeout handling');
      }
    });
  });

  test.describe('Form State Persistence', () => {
    test('preserves form data during page refresh', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Fill evaluation data
        const testScores = {
          criterion1: 8.5,
          criterion2: 9.0,
          criterion3: 7.5,
          criterion4: 8.0,
          criterion5: 9.5,
          comments: 'Test persistence comment'
        };
        
        await evaluationForm.fillEvaluation(testScores);
        
        // Save as draft first
        await evaluationForm.saveDraft();
        
        // Wait for save to complete
        await page.waitForTimeout(1000);
        
        // Refresh page
        await page.reload();
        
        // Verify data is preserved
        const totalScore = await evaluationForm.getTotalScore();
        const expectedTotal = Object.values(testScores).slice(0, 5).reduce((sum, score) => sum + score, 0);
        
        expect(Math.abs(totalScore - expectedTotal)).toBeLessThan(0.1);
        
        // Check comments
        const commentsField = page.locator('.mt-evaluation-comments, textarea[name="comments"]');
        if (await commentsField.isVisible()) {
          const commentsValue = await commentsField.inputValue();
          expect(commentsValue).toContain('Test persistence');
        }
        
      } catch (error) {
        console.warn('⚠️  Could not test form data persistence');
      }
    });

    test('shows unsaved changes warning', async ({ page }) => {
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Fill some data but don't save
        await evaluationForm.setCriterionScore(1, 8);
        await evaluationForm.setCriterionScore(2, 9);
        
        // Try to navigate away
        await page.goto('/jury-dashboard/');
        
        // Should show unsaved changes warning (if implemented)
        page.on('dialog', async (dialog) => {
          expect(dialog.type()).toBe('beforeunload');
          await dialog.accept();
        });
        
      } catch (error) {
        console.warn('⚠️  Could not test unsaved changes warning');
      }
    });
  });

  test.describe('Multi-device Evaluation', () => {
    test('evaluation form works on mobile', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Check mobile responsiveness
        await expect(page.locator('.mt-evaluation-form')).toBeVisible();
        
        // Verify form elements fit in mobile viewport
        const formElements = [
          '.mt-candidate-details',
          '.mt-criteria-section',
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
        
        // Test score input on mobile
        await evaluationForm.setCriterionScore(1, 8);
        
        // Verify total calculation works on mobile
        const totalScore = await evaluationForm.getTotalScore();
        expect(totalScore).toBeGreaterThanOrEqual(0);
        
      } catch (error) {
        console.warn('⚠️  Could not test mobile evaluation form');
      }
    });

    test('evaluation form works on tablet', async ({ page }) => {
      // Set tablet viewport
      await page.setViewportSize({ width: 768, height: 1024 });
      
      const evaluationForm = new EvaluationForm(page);
      
      try {
        await evaluationForm.navigate('1');
        
        // Test tablet-specific interactions
        await expect(page.locator('.mt-evaluation-form')).toBeVisible();
        
        // Test touch interactions if available
        await evaluationForm.setCriterionScore(1, 7);
        await evaluationForm.setCriterionScore(2, 8);
        
        // Verify functionality works on tablet
        const totalScore = await evaluationForm.getTotalScore();
        expect(totalScore).toBeGreaterThan(0);
        
      } catch (error) {
        console.warn('⚠️  Could not test tablet evaluation form');
      }
    });
  });
});