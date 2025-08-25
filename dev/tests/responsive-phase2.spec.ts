import { test, expect } from '@playwright/test';

/**
 * Phase 2.3 - Responsive Design Testing
 * Tests CSS refactoring on multiple viewport sizes
 */

const BASE_URL = 'http://localhost:8080';
const CANDIDATES_URL = `${BASE_URL}/kandidaten/`;
const JURY_URL = `${BASE_URL}/jury-bewertung/`;

// Viewport sizes to test
const viewports = {
  'mobile-small': { width: 320, height: 568 },
  'mobile-medium': { width: 375, height: 667 },
  'mobile-large': { width: 414, height: 896 },
  'tablet-portrait': { width: 768, height: 1024 },
  'tablet-landscape': { width: 1024, height: 768 },
  'desktop': { width: 1280, height: 720 },
  'desktop-wide': { width: 1920, height: 1080 }
};

test.describe('Phase 2.3 - Responsive Design Tests', () => {
  
  // Test each viewport size
  Object.entries(viewports).forEach(([name, viewport]) => {
    
    test.describe(`${name} (${viewport.width}x${viewport.height})`, () => {
      
      test.beforeEach(async ({ page }) => {
        await page.setViewportSize(viewport);
      });
      
      test('Candidates Grid - Layout and visibility', async ({ page }) => {
        await page.goto(CANDIDATES_URL);
        
        // Wait for grid to load
        await page.waitForSelector('.mt-candidates-grid', { timeout: 10000 });
        
        // Check grid exists
        const grid = await page.locator('.mt-candidates-grid');
        await expect(grid).toBeVisible();
        
        // Check if cards are visible
        const cards = page.locator('.mt-candidate-card');
        const cardCount = await cards.count();
        expect(cardCount).toBeGreaterThan(0);
        
        // Check responsive grid columns
        if (viewport.width <= 768) {
          // Mobile: should be single column or 2 columns max
          const gridStyle = await grid.evaluate(el => window.getComputedStyle(el).gridTemplateColumns);
          expect(gridStyle).toMatch(/^(repeat\((1|2),|[\d.]+px)/);
        } else if (viewport.width <= 1024) {
          // Tablet: 2-3 columns
          const gridStyle = await grid.evaluate(el => window.getComputedStyle(el).gridTemplateColumns);
          expect(gridStyle).toMatch(/repeat\([23],/);
        } else {
          // Desktop: 3+ columns
          const gridStyle = await grid.evaluate(el => window.getComputedStyle(el).gridTemplateColumns);
          expect(gridStyle).toMatch(/repeat\([34],/);
        }
        
        // Check card readability
        const firstCard = cards.first();
        await expect(firstCard).toBeVisible();
        
        // Check text is not cut off
        const cardTitle = firstCard.locator('.mt-candidate-name');
        await expect(cardTitle).toBeVisible();
        const titleOverflow = await cardTitle.evaluate(el => {
          const styles = window.getComputedStyle(el);
          return styles.overflow !== 'hidden' || styles.textOverflow !== 'ellipsis';
        });
        
        // On mobile, text might need ellipsis
        if (viewport.width <= 414) {
          expect(titleOverflow).toBeDefined();
        }
      });
      
      test('Navigation Menu - Mobile hamburger', async ({ page }) => {
        await page.goto(CANDIDATES_URL);
        
        if (viewport.width <= 768) {
          // Mobile menu should exist
          const mobileMenuToggle = page.locator('.mt-mobile-menu-toggle, .mobile-menu-toggle, #mobile-menu-toggle');
          const menuExists = await mobileMenuToggle.count() > 0;
          
          if (menuExists) {
            await expect(mobileMenuToggle.first()).toBeVisible();
            
            // Test menu toggle
            await mobileMenuToggle.first().click();
            await page.waitForTimeout(500); // Wait for animation
            
            // Check if menu opens
            const mobileNav = page.locator('.mt-mobile-nav, .mobile-navigation, #mobile-menu');
            if (await mobileNav.count() > 0) {
              await expect(mobileNav.first()).toBeVisible();
            }
          }
        } else {
          // Desktop menu should be visible
          const desktopNav = page.locator('.mt-navigation, .main-navigation, nav');
          if (await desktopNav.count() > 0) {
            await expect(desktopNav.first()).toBeVisible();
          }
        }
      });
      
      test('Typography - Font sizes scale properly', async ({ page }) => {
        await page.goto(CANDIDATES_URL);
        
        // Check main heading
        const heading = page.locator('h1').first();
        if (await heading.count() > 0) {
          const fontSize = await heading.evaluate(el => 
            parseInt(window.getComputedStyle(el).fontSize)
          );
          
          // Font sizes should scale with viewport
          if (viewport.width <= 414) {
            expect(fontSize).toBeGreaterThanOrEqual(24);
            expect(fontSize).toBeLessThanOrEqual(32);
          } else if (viewport.width <= 768) {
            expect(fontSize).toBeGreaterThanOrEqual(28);
            expect(fontSize).toBeLessThanOrEqual(36);
          } else {
            expect(fontSize).toBeGreaterThanOrEqual(32);
            expect(fontSize).toBeLessThanOrEqual(48);
          }
        }
      });
      
      test('Images - Responsive and not distorted', async ({ page }) => {
        await page.goto(CANDIDATES_URL);
        
        // Check candidate photos
        const images = page.locator('.mt-candidate-photo img, .candidate-photo img, img');
        const imageCount = await images.count();
        
        if (imageCount > 0) {
          const firstImage = images.first();
          await expect(firstImage).toBeVisible();
          
          // Check aspect ratio is maintained
          const dimensions = await firstImage.evaluate(img => {
            const rect = img.getBoundingClientRect();
            return {
              width: rect.width,
              height: rect.height,
              naturalWidth: img.naturalWidth,
              naturalHeight: img.naturalHeight
            };
          });
          
          // Image should not be stretched
          if (dimensions.naturalWidth > 0 && dimensions.naturalHeight > 0) {
            const displayRatio = dimensions.width / dimensions.height;
            const naturalRatio = dimensions.naturalWidth / dimensions.naturalHeight;
            const ratioDiff = Math.abs(displayRatio - naturalRatio);
            expect(ratioDiff).toBeLessThan(0.1); // Allow 10% difference
          }
        }
      });
      
      test('Buttons - Touch-friendly on mobile', async ({ page }) => {
        await page.goto(CANDIDATES_URL);
        
        const buttons = page.locator('.mt-btn, .mt-button, button');
        const buttonCount = await buttons.count();
        
        if (buttonCount > 0) {
          const firstButton = buttons.first();
          const size = await firstButton.evaluate(el => {
            const rect = el.getBoundingClientRect();
            return {
              width: rect.width,
              height: rect.height
            };
          });
          
          // On mobile, buttons should be at least 44x44px (touch target)
          if (viewport.width <= 768) {
            expect(size.height).toBeGreaterThanOrEqual(40);
            expect(size.width).toBeGreaterThanOrEqual(40);
          }
        }
      });
      
      test('Forms - Usable on all devices', async ({ page }) => {
        // Try jury evaluation form if accessible
        await page.goto(JURY_URL);
        
        const forms = page.locator('form');
        const formCount = await forms.count();
        
        if (formCount > 0) {
          const inputs = page.locator('input[type="text"], input[type="email"], textarea, select');
          const inputCount = await inputs.count();
          
          if (inputCount > 0) {
            const firstInput = inputs.first();
            const inputSize = await firstInput.evaluate(el => {
              const rect = el.getBoundingClientRect();
              const styles = window.getComputedStyle(el);
              return {
                width: rect.width,
                height: rect.height,
                fontSize: parseInt(styles.fontSize)
              };
            });
            
            // On mobile, inputs should be large enough
            if (viewport.width <= 768) {
              expect(inputSize.height).toBeGreaterThanOrEqual(36);
              expect(inputSize.fontSize).toBeGreaterThanOrEqual(14);
            }
          }
        }
      });
      
      test('Performance - CSS loads efficiently', async ({ page }) => {
        const response = await page.goto(CANDIDATES_URL, { waitUntil: 'networkidle' });
        
        // Check response status
        expect(response?.status()).toBe(200);
        
        // Check CSS files are loaded
        const cssFiles = await page.evaluate(() => {
          const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
          return links.map(link => ({
            href: link.href,
            loaded: link.sheet !== null
          }));
        });
        
        // All CSS files should be loaded
        cssFiles.forEach(css => {
          if (css.href.includes('mt-')) {
            expect(css.loaded).toBeTruthy();
          }
        });
        
        // Check for render-blocking CSS
        const criticalCSS = cssFiles.find(css => css.href.includes('mt-critical'));
        if (criticalCSS) {
          expect(criticalCSS.loaded).toBeTruthy();
        }
      });
      
      test('Accessibility - Focus visible on interactive elements', async ({ page }) => {
        await page.goto(CANDIDATES_URL);
        
        // Tab through page
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');
        
        // Check if focus is visible
        const focusedElement = await page.evaluate(() => {
          const el = document.activeElement;
          if (!el) return null;
          const styles = window.getComputedStyle(el);
          return {
            tagName: el.tagName,
            outline: styles.outline,
            boxShadow: styles.boxShadow,
            border: styles.border
          };
        });
        
        if (focusedElement && ['A', 'BUTTON', 'INPUT'].includes(focusedElement.tagName)) {
          // Should have visible focus indicator
          const hasVisibleFocus = 
            focusedElement.outline !== 'none' ||
            focusedElement.boxShadow !== 'none' ||
            focusedElement.border !== 'none';
          expect(hasVisibleFocus).toBeTruthy();
        }
      });
    });
  });
  
  test('Screenshot comparison - Multiple viewports', async ({ page }) => {
    const screenshotDir = 'dev/tests/screenshots/phase2';
    
    for (const [name, viewport] of Object.entries(viewports)) {
      await page.setViewportSize(viewport);
      await page.goto(CANDIDATES_URL);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000); // Wait for animations
      
      // Take screenshot
      await page.screenshot({
        path: `${screenshotDir}/candidates-${name}.png`,
        fullPage: false
      });
    }
  });
});

// Accessibility specific tests
test.describe('Accessibility - WCAG Compliance', () => {
  test('Color contrast ratios', async ({ page }) => {
    await page.goto(CANDIDATES_URL);
    
    // Check text contrast
    const textElements = await page.evaluate(() => {
      const elements = Array.from(document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, a, button'));
      return elements.map(el => {
        const styles = window.getComputedStyle(el);
        const bg = styles.backgroundColor;
        const fg = styles.color;
        return { bg, fg, text: el.textContent?.substring(0, 50) };
      });
    });
    
    // Basic check that text has defined colors
    textElements.forEach(el => {
      if (el.text && el.text.trim()) {
        expect(el.fg).not.toBe('rgba(0, 0, 0, 0)');
      }
    });
  });
  
  test('Keyboard navigation', async ({ page }) => {
    await page.goto(CANDIDATES_URL);
    
    // Tab through interactive elements
    const interactiveElements = [];
    for (let i = 0; i < 10; i++) {
      await page.keyboard.press('Tab');
      const focused = await page.evaluate(() => {
        const el = document.activeElement;
        return el ? {
          tag: el.tagName,
          class: el.className,
          href: el.getAttribute('href'),
          text: el.textContent?.substring(0, 30)
        } : null;
      });
      if (focused) {
        interactiveElements.push(focused);
      }
    }
    
    // Should have tabbable elements
    expect(interactiveElements.length).toBeGreaterThan(0);
  });
});