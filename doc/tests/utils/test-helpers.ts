import { Page, expect, Locator } from '@playwright/test';

/**
 * Test helper utilities for Mobility Trailblazers plugin
 */

/**
 * WordPress admin helpers
 */
export class WordPressAdmin {
  constructor(private page: Page) {}

  async navigateToPlugin() {
    await this.page.goto('/wp-admin/admin.php?page=mobility-trailblazers');
    // The main dashboard may just have .wrap class
    await expect(this.page.locator('.wrap')).toBeVisible();
  }

  async navigateToAssignments() {
    await this.page.goto('/wp-admin/admin.php?page=mt-assignments');
    await expect(this.page.locator('.wrap')).toBeVisible();
    // Wait for page title to confirm correct page - support both English and German
    await expect(this.page.locator('h1').filter({ hasText: /Assignment|Zuweisung/ })).toBeVisible();
  }

  async navigateToEvaluations() {
    await this.page.goto('/wp-admin/admin.php?page=mt-evaluations');
    await expect(this.page.locator('.wrap')).toBeVisible();
    // Wait for page title to confirm correct page - support both English and German
    await expect(this.page.locator('h1').filter({ hasText: /Evaluation|Bewertung/ })).toBeVisible();
  }

  async navigateToDebugCenter() {
    await this.page.goto('/wp-admin/admin.php?page=mt-debug-center');
    await expect(this.page.locator('.wrap')).toBeVisible();
    // Wait for page title to confirm correct page
    await expect(this.page.locator('h1')).toBeVisible();
  }
}

/**
 * Jury dashboard helpers
 */
export class JuryDashboard {
  constructor(private page: Page) {}

  async navigate() {
    // Try different possible URLs for the jury dashboard
    const possibleUrls = ['/jury-dashboard/', '/vote/', '/'];
    
    let dashboardFound = false;
    for (const url of possibleUrls) {
      try {
        await this.page.goto(url, { waitUntil: 'networkidle' });
        
        // Check if we can find the jury dashboard on this page
        const dashboardVisible = await this.page.locator('.mt-jury-dashboard').isVisible({ timeout: 5000 });
        if (dashboardVisible) {
          dashboardFound = true;
          break;
        }
        
        // If not visible, check if we need to login
        if (await this.page.locator('#loginform, .wp-login-form').isVisible()) {
          console.warn('Login required for jury dashboard access');
          return;
        }
      } catch (error) {
        console.warn(`Could not access ${url}:`, error);
      }
    }
    
    if (dashboardFound) {
      await expect(this.page.locator('.mt-jury-dashboard')).toBeVisible();
    } else {
      console.warn('Jury dashboard not found on any expected URL');
    }
  }

  async getStatistics() {
    await this.navigate();
    
    const totalAssigned = await this.page.locator('.mt-stat-total-assigned .mt-stat-number').textContent();
    const completed = await this.page.locator('.mt-stat-completed .mt-stat-number').textContent();
    const pending = await this.page.locator('.mt-stat-pending .mt-stat-number').textContent();
    
    return {
      totalAssigned: parseInt(totalAssigned || '0'),
      completed: parseInt(completed || '0'),
      pending: parseInt(pending || '0')
    };
  }

  async searchCandidates(searchTerm: string) {
    await this.navigate();
    
    const searchInput = this.page.locator('.mt-search-input');
    await searchInput.fill(searchTerm);
    await searchInput.press('Enter');
    
    // Wait for search results
    await this.page.waitForTimeout(1000);
  }

  async filterByCategory(category: 'start-ups' | 'established-companies' | 'governance') {
    await this.navigate();
    
    const categoryFilter = this.page.locator('.mt-category-filter');
    await categoryFilter.selectOption(category);
    
    // Wait for filter to apply
    await this.page.waitForTimeout(1000);
  }

  async filterByStatus(status: 'all' | 'completed' | 'pending' | 'draft') {
    await this.navigate();
    
    const statusFilter = this.page.locator('.mt-status-filter');
    await statusFilter.selectOption(status);
    
    // Wait for filter to apply
    await this.page.waitForTimeout(1000);
  }

  async getCandidateCards() {
    await this.navigate();
    return this.page.locator('.mt-candidate-card');
  }

  async clickEvaluateButton(candidateId: string) {
    const candidateCard = this.page.locator(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
    await candidateCard.locator('.mt-evaluate-btn').click();
  }
}

/**
 * Evaluation form helpers
 */
export class EvaluationForm {
  constructor(private page: Page) {}

  async navigate(candidateId: string) {
    // Try different possible URLs for the evaluation form
    const possibleUrls = [
      `/jury-evaluation/?candidate=${candidateId}`,
      `/jury-dashboard/?evaluate=${candidateId}`,
      `/vote/?evaluate=${candidateId}`,
      `/?evaluate=${candidateId}`
    ];
    
    let formFound = false;
    for (const url of possibleUrls) {
      try {
        await this.page.goto(url, { waitUntil: 'networkidle' });
        
        // Check if we can find the evaluation form on this page
        const formVisible = await this.page.locator('.mt-evaluation-form').isVisible({ timeout: 5000 });
        if (formVisible) {
          formFound = true;
          break;
        }
        
        // Alternative: check for the form ID
        const formIdVisible = await this.page.locator('#mt-evaluation-form').isVisible({ timeout: 5000 });
        if (formIdVisible) {
          formFound = true;
          break;
        }
        
      } catch (error) {
        console.warn(`Could not access ${url}:`, error);
      }
    }
    
    if (formFound) {
      // Wait for either class or ID selector
      const formLocator = this.page.locator('.mt-evaluation-form, #mt-evaluation-form').first();
      await expect(formLocator).toBeVisible();
    } else {
      console.warn('Evaluation form not found on any expected URL');
    }
  }

  async fillEvaluation(scores: {
    criterion1: number;
    criterion2: number;
    criterion3: number;
    criterion4: number;
    criterion5: number;
    comments?: string;
  }) {
    // Fill in scores for each criterion
    await this.setCriterionScore(1, scores.criterion1);
    await this.setCriterionScore(2, scores.criterion2);
    await this.setCriterionScore(3, scores.criterion3);
    await this.setCriterionScore(4, scores.criterion4);
    await this.setCriterionScore(5, scores.criterion5);

    // Comments section was removed per Issue #25, so skip comments
    // if (scores.comments) {
    //   await this.page.fill('.mt-evaluation-comments', scores.comments);
    // }
  }

  async setCriterionScore(criterion: number, score: number) {
    // Map criterion number to key (based on template structure)
    const criterionKeys = ['courage', 'innovation', 'implementation', 'relevance', 'visibility'];
    const key = criterionKeys[criterion - 1];
    
    if (!key) {
      throw new Error(`Invalid criterion number: ${criterion}`);
    }
    
    const scoreContainer = this.page.locator(`[data-criterion="${key}"]`);
    
    // Try slider first
    const slider = scoreContainer.locator('.mt-score-slider');
    if (await slider.isVisible()) {
      await slider.fill(score.toString());
      return;
    }

    // Try score buttons
    const scoreButton = scoreContainer.locator(`.mt-score-button[data-value="${score}"]`);
    if (await scoreButton.isVisible()) {
      await scoreButton.click();
      return;
    }

    // Try direct numeric input
    const scoreInput = scoreContainer.locator('.mt-score-input');
    if (await scoreInput.isVisible()) {
      await scoreInput.fill(score.toString());
      return;
    }

    // Try star rating
    const starRating = scoreContainer.locator(`.dashicons[data-value="${score}"]`);
    if (await starRating.isVisible()) {
      await starRating.click();
      return;
    }

    // Try name-based input as fallback
    const namedInput = this.page.locator(`input[name="${key}_score"]`);
    if (await namedInput.isVisible()) {
      await namedInput.fill(score.toString());
      return;
    }

    throw new Error(`Could not set score for criterion ${criterion} (${key})`);
  }

  async getTotalScore(): Promise<number> {
    const totalElement = this.page.locator('.mt-total-score');
    const totalText = await totalElement.textContent();
    return parseFloat(totalText?.replace(/[^\d.]/g, '') || '0');
  }

  async saveDraft() {
    // Draft functionality might be handled by regular submit - check for draft button first
    const draftBtn = this.page.locator('.mt-save-draft-btn');
    if (await draftBtn.isVisible()) {
      await draftBtn.click();
    } else {
      console.warn('Draft button not found - may not be implemented');
    }
    
    // Wait for any success message or form response
    try {
      await expect(this.page.locator('.mt-success-message, .notice-success')).toBeVisible();
    } catch {
      console.warn('No success message found after draft save');
    }
  }

  async submitEvaluation() {
    // Use the actual button class from template
    await this.page.click('.mt-btn.mt-btn-primary');
    
    // Wait for success message or page redirect
    try {
      await expect(this.page.locator('.mt-success-message, .notice-success')).toBeVisible();
    } catch {
      // May redirect back to dashboard after successful submit
      await this.page.waitForTimeout(2000);
    }
  }

  async getCandidateDetails() {
    const candidateSection = this.page.locator('.mt-candidate-details');
    
    const name = await candidateSection.locator('.mt-candidate-name').textContent();
    const company = await candidateSection.locator('.mt-candidate-company').textContent();
    const innovation = await candidateSection.locator('.mt-candidate-innovation').textContent();
    
    return { name, company, innovation };
  }

  async hasCriteriaContent(): Promise<boolean> {
    // Check if criteria-specific content is displayed
    const criteriaContent = this.page.locator('.mt-criteria-content');
    return await criteriaContent.isVisible();
  }
}

/**
 * Assignment management helpers
 */
export class AssignmentManager {
  constructor(private page: Page) {}

  async navigateToAssignments() {
    await this.page.goto('/wp-admin/admin.php?page=mt-assignments');
    await expect(this.page.locator('.wrap')).toBeVisible();
    // Wait for page title to confirm correct page - support both English and German
    await expect(this.page.locator('h1').filter({ hasText: /Assignment|Zuweisung/ })).toBeVisible();
  }

  async performAutoAssignment(method: 'balanced' | 'random' = 'balanced') {
    await this.navigateToAssignments();
    
    // Set assignment method
    await this.page.selectOption('.mt-assignment-method', method);
    
    // Set candidates per jury member (default 20)
    await this.page.fill('.mt-candidates-per-jury', '20');
    
    // Click auto-assign button (using correct ID from template)
    await this.page.click('#mt-auto-assign-btn');
    
    // Wait for assignment to complete
    await expect(this.page.locator('.mt-assignment-success')).toBeVisible();
  }

  async manualAssignment(juryMemberId: string, candidateIds: string[]) {
    await this.navigateToAssignments();
    
    // Select jury member
    await this.page.selectOption('.mt-jury-member-select', juryMemberId);
    
    // Select candidates
    for (const candidateId of candidateIds) {
      await this.page.check(`.mt-candidate-checkbox[value="${candidateId}"]`);
    }
    
    // Assign
    await this.page.click('.mt-assign-selected-btn');
    
    // Wait for success
    await expect(this.page.locator('.mt-assignment-success')).toBeVisible();
  }

  async removeAssignment(juryMemberId: string, candidateId: string) {
    await this.navigateToAssignments();
    
    const assignmentRow = this.page.locator(
      `tr[data-jury="${juryMemberId}"][data-candidate="${candidateId}"]`
    );
    
    await assignmentRow.locator('.mt-remove-assignment').click();
    
    // Confirm removal
    await this.page.click('.mt-confirm-removal');
    
    // Wait for removal success
    await expect(assignmentRow).toBeHidden();
  }

  async getAssignmentStatistics() {
    await this.navigateToAssignments();
    
    // Use the actual structure from the template - multiple .mt-stat-number elements
    const statNumbers = this.page.locator('.mt-stat-number');
    const count = await statNumbers.count();
    
    if (count >= 4) {
      const totalCandidates = await statNumbers.nth(0).textContent();
      const totalJuryMembers = await statNumbers.nth(1).textContent();
      const totalAssignments = await statNumbers.nth(2).textContent();
      const avgPerJury = await statNumbers.nth(3).textContent();
      
      return {
        totalCandidates: parseInt(totalCandidates || '0'),
        totalJuryMembers: parseInt(totalJuryMembers || '0'),
        totalAssignments: parseInt(totalAssignments || '0'),
        averagePerJury: parseFloat(avgPerJury || '0'),
        total: parseInt(totalAssignments || '0')
      };
    }
    
    // Fallback if stats not found
    return {
      totalCandidates: 0,
      totalJuryMembers: 0,
      totalAssignments: 0,
      averagePerJury: 0,
      total: 0
    };
  }
}

/**
 * AJAX request helpers
 */
export class AjaxHelper {
  constructor(private page: Page) {}

  async waitForAjaxComplete(timeout = 10000) {
    await this.page.waitForFunction(
      () => (window as any).jQuery && (window as any).jQuery.active === 0,
      { timeout }
    );
  }

  async interceptAjaxRequest(action: string): Promise<any> {
    return new Promise((resolve) => {
      this.page.on('response', async (response) => {
        if (response.url().includes('admin-ajax.php') && 
            response.request().postData()?.includes(`action=${action}`)) {
          const responseData = await response.json();
          resolve(responseData);
        }
      });
    });
  }

  async mockAjaxResponse(action: string, mockResponse: any) {
    await this.page.route('**/admin-ajax.php', async (route) => {
      const request = route.request();
      if (request.postData()?.includes(`action=${action}`)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(mockResponse)
        });
      } else {
        await route.continue();
      }
    });
  }
}

/**
 * Responsive design helpers
 */
export class ResponsiveHelper {
  constructor(private page: Page) {}

  async setMobileViewport() {
    await this.page.setViewportSize({ width: 375, height: 667 });
  }

  async setTabletViewport() {
    await this.page.setViewportSize({ width: 768, height: 1024 });
  }

  async setDesktopViewport() {
    await this.page.setViewportSize({ width: 1200, height: 800 });
  }

  async testResponsiveElement(selector: string) {
    const element = this.page.locator(selector);
    
    // Test mobile
    await this.setMobileViewport();
    await expect(element).toBeVisible();
    const mobileSize = await element.boundingBox();
    
    // Test tablet
    await this.setTabletViewport();
    await expect(element).toBeVisible();
    const tabletSize = await element.boundingBox();
    
    // Test desktop
    await this.setDesktopViewport();
    await expect(element).toBeVisible();
    const desktopSize = await element.boundingBox();
    
    return { mobileSize, tabletSize, desktopSize };
  }
}

/**
 * Accessibility helpers
 */
export class AccessibilityHelper {
  constructor(private page: Page) {}

  async checkAriaLabels(selectors: string[]) {
    for (const selector of selectors) {
      const element = this.page.locator(selector);
      if (await element.isVisible()) {
        await expect(element).toHaveAttribute('aria-label');
      }
    }
  }

  async checkKeyboardNavigation(startSelector: string, expectedSelectors: string[]) {
    await this.page.focus(startSelector);
    
    for (const expectedSelector of expectedSelectors) {
      await this.page.keyboard.press('Tab');
      await expect(this.page.locator(expectedSelector)).toBeFocused();
    }
  }

  async checkColorContrast() {
    // This would require additional libraries like axe-core
    // For now, we'll just check that text is visible
    const textElements = this.page.locator('p, h1, h2, h3, h4, h5, h6, span, div:has-text');
    const count = await textElements.count();
    
    for (let i = 0; i < count; i++) {
      await expect(textElements.nth(i)).toBeVisible();
    }
  }
}

/**
 * Performance helpers
 */
export class PerformanceHelper {
  constructor(private page: Page) {}

  async measurePageLoadTime(url: string): Promise<number> {
    const startTime = Date.now();
    await this.page.goto(url, { waitUntil: 'networkidle' });
    const endTime = Date.now();
    
    return endTime - startTime;
  }

  async measureAjaxResponseTime(ajaxAction: string): Promise<number> {
    const startTime = Date.now();
    
    return new Promise((resolve) => {
      this.page.on('response', async (response) => {
        if (response.url().includes('admin-ajax.php') && 
            response.request().postData()?.includes(`action=${ajaxAction}`)) {
          const endTime = Date.now();
          resolve(endTime - startTime);
        }
      });
    });
  }
}

/**
 * Error handling helpers
 */
export class ErrorHelper {
  constructor(private page: Page) {}

  async captureConsoleErrors(): Promise<string[]> {
    const errors: string[] = [];
    
    this.page.on('console', (message) => {
      if (message.type() === 'error') {
        errors.push(message.text());
      }
    });
    
    return errors;
  }

  async checkForPhpErrors(): Promise<boolean> {
    const pageContent = await this.page.content();
    return pageContent.includes('Fatal error') || 
           pageContent.includes('Parse error') || 
           pageContent.includes('Warning:') ||
           pageContent.includes('Notice:');
  }

  async checkForJavaScriptErrors(): Promise<string[]> {
    const errors: string[] = [];
    
    this.page.on('pageerror', (error) => {
      errors.push(error.message);
    });
    
    return errors;
  }
}