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
    await this.page.goto('/wp-admin/admin.php?page=mt-plugin');
    await expect(this.page.locator('.mt-admin-page')).toBeVisible();
  }

  async navigateToAssignments() {
    await this.page.goto('/wp-admin/admin.php?page=mt-assignments');
    await expect(this.page.locator('.mt-assignments-page')).toBeVisible();
  }

  async navigateToEvaluations() {
    await this.page.goto('/wp-admin/admin.php?page=mt-evaluations');
    await expect(this.page.locator('.mt-evaluations-page')).toBeVisible();
  }

  async navigateToDebugCenter() {
    await this.page.goto('/wp-admin/admin.php?page=mt-debug');
    await expect(this.page.locator('.mt-debug-page')).toBeVisible();
  }
}

/**
 * Jury dashboard helpers
 */
export class JuryDashboard {
  constructor(private page: Page) {}

  async navigate() {
    await this.page.goto('/jury-dashboard/');
    await expect(this.page.locator('.mt-jury-dashboard')).toBeVisible();
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
    await this.page.goto(`/jury-evaluation/?candidate=${candidateId}`);
    await expect(this.page.locator('.mt-evaluation-form')).toBeVisible();
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

    // Add comments if provided
    if (scores.comments) {
      await this.page.fill('.mt-evaluation-comments', scores.comments);
    }
  }

  async setCriterionScore(criterion: number, score: number) {
    // Try different score input methods (slider, buttons, input)
    const scoreContainer = this.page.locator(`.mt-criterion-${criterion}`);
    
    // Try slider first
    const slider = scoreContainer.locator('.mt-score-slider');
    if (await slider.isVisible()) {
      await slider.fill(score.toString());
      return;
    }

    // Try score buttons
    const scoreButton = scoreContainer.locator(`.mt-score-btn[data-score="${score}"]`);
    if (await scoreButton.isVisible()) {
      await scoreButton.click();
      return;
    }

    // Try direct input
    const scoreInput = scoreContainer.locator('.mt-score-input');
    if (await scoreInput.isVisible()) {
      await scoreInput.fill(score.toString());
      return;
    }

    // Try star rating
    const starRating = scoreContainer.locator(`.mt-star[data-score="${score}"]`);
    if (await starRating.isVisible()) {
      await starRating.click();
      return;
    }

    throw new Error(`Could not set score for criterion ${criterion}`);
  }

  async getTotalScore(): Promise<number> {
    const totalElement = this.page.locator('.mt-total-score');
    const totalText = await totalElement.textContent();
    return parseFloat(totalText?.replace(/[^\d.]/g, '') || '0');
  }

  async saveDraft() {
    await this.page.click('.mt-save-draft-btn');
    await expect(this.page.locator('.mt-success-message')).toBeVisible();
  }

  async submitEvaluation() {
    await this.page.click('.mt-submit-evaluation-btn');
    await expect(this.page.locator('.mt-success-message')).toBeVisible();
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
    await expect(this.page.locator('.mt-assignments-page')).toBeVisible();
  }

  async performAutoAssignment(method: 'balanced' | 'random' = 'balanced') {
    await this.navigateToAssignments();
    
    // Set assignment method
    await this.page.selectOption('.mt-assignment-method', method);
    
    // Set candidates per jury member (default 20)
    await this.page.fill('.mt-candidates-per-jury', '20');
    
    // Click auto-assign button
    await this.page.click('.mt-auto-assign-btn');
    
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
    
    const totalAssignments = await this.page.locator('.mt-total-assignments').textContent();
    const juryMembers = await this.page.locator('.mt-active-jury-members').textContent();
    const avgPerJury = await this.page.locator('.mt-avg-per-jury').textContent();
    
    return {
      total: parseInt(totalAssignments || '0'),
      juryMembers: parseInt(juryMembers || '0'),
      averagePerJury: parseFloat(avgPerJury || '0')
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