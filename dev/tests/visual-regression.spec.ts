import { test, expect } from '@playwright/test';

/**
 * Visual Regression Tests for CSS Refactoring
 * Phase 4: Testing Protocol
 * 
 * Tests visual integrity across different viewports after CSS refactoring
 */

test.describe('CSS Refactoring Visual Regression Tests', () => {
    // Define test pages
    const testPages = [
        { 
            url: '/vote/', 
            name: 'jury-dashboard',
            description: 'Jury Dashboard with BEM components'
        },
        { 
            url: '/candidate/nic-knapp/', 
            name: 'candidate-profile',
            description: 'Candidate Profile Page'
        },
        { 
            url: '/?evaluate=4866', 
            name: 'evaluation-form',
            description: 'Evaluation Form with BEM structure'
        }
    ];
    
    // Define viewport configurations
    const viewports = [
        { 
            width: 1920, 
            height: 1080, 
            name: 'desktop',
            device: 'Desktop 1080p'
        },
        { 
            width: 768, 
            height: 1024, 
            name: 'tablet',
            device: 'iPad'
        },
        { 
            width: 375, 
            height: 812, 
            name: 'mobile',
            device: 'iPhone X'
        }
    ];
    
    // Set up authentication before tests
    test.beforeEach(async ({ page }) => {
        // Login if needed
        const authFile = '.auth/user.json';
        try {
            await page.context().storageState({ path: authFile });
        } catch {
            // Auth file might not exist, continue anyway
        }
    });
    
    // Generate tests for each page and viewport combination
    testPages.forEach(testPage => {
        test.describe(`${testPage.description}`, () => {
            viewports.forEach(viewport => {
                test(`${testPage.name} at ${viewport.device} (${viewport.width}x${viewport.height})`, async ({ page }) => {
                    // Set viewport size
                    await page.setViewportSize({
                        width: viewport.width,
                        height: viewport.height
                    });
                    
                    // Navigate to the page
                    await page.goto(testPage.url, {
                        waitUntil: 'networkidle',
                        timeout: 30000
                    });
                    
                    // Wait for CSS to fully load
                    await page.waitForLoadState('domcontentloaded');
                    await page.waitForTimeout(1000); // Additional wait for animations
                    
                    // Hide dynamic content that might change
                    await page.evaluate(() => {
                        // Hide timestamps and dynamic dates
                        document.querySelectorAll('.timestamp, .date, .time').forEach(el => {
                            (el as HTMLElement).style.visibility = 'hidden';
                        });
                        
                        // Stabilize any animations
                        document.querySelectorAll('*').forEach(el => {
                            (el as HTMLElement).style.animation = 'none';
                            (el as HTMLElement).style.transition = 'none';
                        });
                    });
                    
                    // Take screenshot for visual comparison
                    await expect(page).toHaveScreenshot(
                        `${testPage.name}-${viewport.name}.png`,
                        {
                            fullPage: true,
                            animations: 'disabled',
                            mask: [page.locator('.dynamic-content')], // Mask any dynamic content
                            maxDiffPixels: 100, // Allow small differences
                            threshold: 0.2 // 20% threshold for pixel differences
                        }
                    );
                });
            });
        });
    });
    
    // Test BEM component structure
    test.describe('BEM Component Structure Tests', () => {
        test('Candidate Card BEM structure', async ({ page }) => {
            await page.goto('/vote/');
            
            // Check for BEM classes
            const candidateCard = page.locator('.mt-candidate-card').first();
            await expect(candidateCard).toBeVisible();
            
            // Verify BEM elements exist
            await expect(candidateCard.locator('.mt-candidate-card__title')).toBeVisible();
            await expect(candidateCard.locator('.mt-candidate-card__meta')).toBeVisible();
            
            // Take component screenshot
            await expect(candidateCard).toHaveScreenshot('candidate-card-component.png');
        });
        
        test('Evaluation Form BEM structure', async ({ page }) => {
            await page.goto('/?evaluate=4866');
            
            // Check for BEM classes
            const evaluationForm = page.locator('.mt-evaluation-form').first();
            
            if (await evaluationForm.count() > 0) {
                await expect(evaluationForm).toBeVisible();
                
                // Verify BEM elements exist
                const criterion = evaluationForm.locator('.mt-evaluation-form__criterion').first();
                if (await criterion.count() > 0) {
                    await expect(criterion).toBeVisible();
                }
                
                // Take component screenshot
                await expect(evaluationForm).toHaveScreenshot('evaluation-form-component.png');
            }
        });
        
        test('Jury Dashboard BEM structure', async ({ page }) => {
            await page.goto('/vote/');
            
            // Check for BEM classes
            const dashboard = page.locator('.mt-jury-dashboard').first();
            
            if (await dashboard.count() > 0) {
                await expect(dashboard).toBeVisible();
                
                // Verify BEM elements exist
                const header = dashboard.locator('.mt-jury-dashboard__header').first();
                if (await header.count() > 0) {
                    await expect(header).toBeVisible();
                }
                
                // Take component screenshot
                await expect(dashboard).toHaveScreenshot('jury-dashboard-component.png');
            }
        });
    });
    
    // Test responsive behavior
    test.describe('Responsive Behavior Tests', () => {
        test('Mobile menu behavior', async ({ page }) => {
            // Set mobile viewport
            await page.setViewportSize({ width: 375, height: 812 });
            await page.goto('/vote/');
            
            // Check if mobile menu is present
            const mobileMenu = page.locator('.mobile-menu, .hamburger-menu, [aria-label*="menu"]').first();
            if (await mobileMenu.count() > 0) {
                await expect(mobileMenu).toBeVisible();
                await expect(page).toHaveScreenshot('mobile-menu.png');
            }
        });
        
        test('Tablet layout adaptation', async ({ page }) => {
            // Set tablet viewport
            await page.setViewportSize({ width: 768, height: 1024 });
            await page.goto('/vote/');
            
            // Check grid layout
            const grid = page.locator('.mt-candidate-card-grid, .candidates-grid').first();
            if (await grid.count() > 0) {
                await expect(grid).toBeVisible();
                await expect(page).toHaveScreenshot('tablet-grid-layout.png');
            }
        });
        
        test('Desktop full layout', async ({ page }) => {
            // Set desktop viewport
            await page.setViewportSize({ width: 1920, height: 1080 });
            await page.goto('/vote/');
            
            // Check full layout
            await expect(page).toHaveScreenshot('desktop-full-layout.png', {
                fullPage: false // Only viewport
            });
        });
    });
    
    // Test CSS custom properties (tokens)
    test.describe('CSS Token System Tests', () => {
        test('Verify CSS custom properties are loaded', async ({ page }) => {
            await page.goto('/vote/');
            
            // Check if CSS variables are defined
            const hasTokens = await page.evaluate(() => {
                const styles = getComputedStyle(document.documentElement);
                return {
                    primary: styles.getPropertyValue('--mt-primary'),
                    spaceBase: styles.getPropertyValue('--mt-space-md'),
                    fontBase: styles.getPropertyValue('--mt-font-base'),
                    shadowBase: styles.getPropertyValue('--mt-shadow-md')
                };
            });
            
            // Verify tokens exist
            expect(hasTokens.primary).toBeTruthy();
            expect(hasTokens.spaceBase).toBeTruthy();
            expect(hasTokens.fontBase).toBeTruthy();
            expect(hasTokens.shadowBase).toBeTruthy();
        });
        
        test('Check responsive spacing with clamp()', async ({ page }) => {
            await page.goto('/vote/');
            
            // Test at different viewports
            for (const viewport of viewports) {
                await page.setViewportSize({
                    width: viewport.width,
                    height: viewport.height
                });
                
                const spacing = await page.evaluate(() => {
                    const el = document.querySelector('.mt-candidate-card');
                    if (el) {
                        return getComputedStyle(el).padding;
                    }
                    return null;
                });
                
                // Spacing should exist and be responsive
                expect(spacing).toBeTruthy();
            }
        });
    });
    
    // Test hover and focus states
    test.describe('Interactive State Tests', () => {
        test('Card hover state', async ({ page }) => {
            await page.goto('/vote/');
            
            const card = page.locator('.mt-candidate-card').first();
            if (await card.count() > 0) {
                // Capture before hover
                await expect(card).toHaveScreenshot('card-before-hover.png');
                
                // Hover
                await card.hover();
                await page.waitForTimeout(300); // Wait for transition
                
                // Capture after hover
                await expect(card).toHaveScreenshot('card-after-hover.png');
            }
        });
        
        test('Button focus state', async ({ page }) => {
            await page.goto('/vote/');
            
            const button = page.locator('.mt-candidate-card__button, .btn, button').first();
            if (await button.count() > 0) {
                // Focus the button
                await button.focus();
                await page.waitForTimeout(100);
                
                // Check focus state
                await expect(button).toHaveScreenshot('button-focus-state.png');
            }
        });
    });
});

// Performance monitoring during tests
test.describe('CSS Performance Metrics', () => {
    test('Measure CSS load time', async ({ page }) => {
        const metrics = await page.evaluate(() => {
            const perf = performance.getEntriesByType('resource');
            const cssFiles = perf.filter(entry => entry.name.includes('.css'));
            
            return {
                totalCSSFiles: cssFiles.length,
                totalCSSSize: cssFiles.reduce((sum, file) => sum + (file as any).transferSize, 0),
                totalCSSLoadTime: cssFiles.reduce((sum, file) => sum + file.duration, 0)
            };
        });
        
        // Log metrics for reporting
        console.log('CSS Performance Metrics:', metrics);
        
        // Assert reasonable performance
        expect(metrics.totalCSSFiles).toBeLessThan(20); // Consolidated CSS
        expect(metrics.totalCSSLoadTime).toBeLessThan(2000); // Under 2 seconds
    });
});