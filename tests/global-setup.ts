import { chromium, FullConfig } from '@playwright/test';
import { createTestData } from './fixtures/test-data';

/**
 * Global setup for Playwright tests
 * Runs once before all tests
 */
async function globalSetup(config: FullConfig) {
  console.log('🚀 Starting Mobility Trailblazers test setup...');
  
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    const baseURL = config.projects[0]?.use?.baseURL || 'http://localhost';
    
    // Check if WordPress is accessible
    console.log(`📡 Checking WordPress availability at ${baseURL}...`);
    
    const response = await page.goto(baseURL, { 
      waitUntil: 'networkidle',
      timeout: 30000 
    });
    
    if (!response?.ok()) {
      throw new Error(`WordPress not accessible at ${baseURL}. Status: ${response?.status()}`);
    }
    
    // Check if plugin is active
    console.log('🔌 Checking Mobility Trailblazers plugin status...');
    
    await page.goto(`${baseURL}/wp-admin/plugins.php`);
    
    // Look for plugin in the page
    const pluginActive = await page.locator('tr[data-slug="mobility-trailblazers"]').count() > 0;
    
    if (!pluginActive) {
      console.warn('⚠️  Mobility Trailblazers plugin may not be active');
    } else {
      console.log('✅ Plugin appears to be active');
    }
    
    // Setup test data if needed
    console.log('📊 Setting up test data...');
    await createTestData(page, baseURL);
    
    console.log('✅ Global setup completed successfully');
    
  } catch (error) {
    console.error('❌ Global setup failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

export default globalSetup;