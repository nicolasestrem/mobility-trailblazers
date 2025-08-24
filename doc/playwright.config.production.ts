import { defineConfig } from '@playwright/test';
import baseConfig from './playwright.config';

/**
 * Production environment configuration
 * For testing against live production site
 * WARNING: Use with extreme caution - read-only tests only!
 */
export default defineConfig(baseConfig, {
  use: {
    ...baseConfig.use,
    baseURL: 'https://mobilitytrailblazers.de',
    
    // Production-safe settings
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    
    // Longer timeouts for production environment
    actionTimeout: 45000,
    navigationTimeout: 45000,
  },

  /* Conservative settings for production */
  workers: 1, // Single worker to avoid overwhelming production
  retries: 0, // No retries to avoid repeated requests
  
  /* Production reporter */
  reporter: [
    ['list'],
    ['html', { outputFolder: 'test-results/production-report' }],
    ['junit', { outputFile: 'test-results/production-junit.xml' }]
  ],

  projects: [
    {
      name: 'production-readonly',
      use: { 
        channel: 'chrome',
        // No auth storage - only public pages
      },
      testMatch: [
        '**/production.readonly.spec.ts',
        '**/accessibility.spec.ts',
        '**/performance.spec.ts'
      ]
    }
  ],

  /* Only run safe, read-only tests */
  testMatch: [
    '**/production.readonly.spec.ts',
    '**/accessibility.spec.ts', 
    '**/performance.spec.ts'
  ],

  /* Ignore all interactive tests */
  testIgnore: [
    '**/auth.*',
    '**/admin.*',
    '**/jury.*',
    '**/forms.*',
    '**/crud.*',
    '**/local.*',
    '**/staging.*'
  ],

  /* No web server - testing live site */
  webServer: undefined,
});