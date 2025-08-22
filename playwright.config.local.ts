import { defineConfig } from '@playwright/test';
import baseConfig from './playwright.config';

/**
 * Local development configuration
 * For testing against local WordPress installation
 */
export default defineConfig(baseConfig, {
  use: {
    ...baseConfig.use,
    baseURL: 'http://localhost',
    
    // More verbose logging for local development
    trace: 'on',
    screenshot: 'on',
    video: 'on',
    
    // Slower timeouts for debugging
    actionTimeout: 60000,
    navigationTimeout: 60000,
  },

  /* Run in headed mode for local development */
  workers: 1,
  
  /* Local test specific settings */
  testDir: './tests',
  
  /* Reporter optimized for local development */
  reporter: [
    ['list'],
    ['html', { outputFolder: 'test-results/local-report', open: 'on-failure' }]
  ],

  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },
    {
      name: 'local-chrome',
      use: { 
        channel: 'chrome',
        storageState: 'tests/.auth/admin.json',
        // Enable debugging features
        devtools: true,
        slowMo: 100, // Slow down actions for visibility
      },
      dependencies: ['setup'],
    }
  ],

  /* Environment-specific test patterns */
  testIgnore: [
    '**/production.*',
    '**/staging.*'
  ]
});