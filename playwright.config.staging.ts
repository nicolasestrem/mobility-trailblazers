import { defineConfig } from '@playwright/test';
import baseConfig from './playwright.config';

/**
 * Staging environment configuration
 * For testing against Docker staging environment
 */
export default defineConfig(baseConfig, {
  use: {
    ...baseConfig.use,
    baseURL: 'http://localhost:8080',
    
    // Staging-specific settings
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  /* Staging test settings */
  workers: 2,
  retries: 1,
  
  /* Reporter for staging */
  reporter: [
    ['list'],
    ['html', { outputFolder: 'test-results/staging-report' }],
    ['junit', { outputFile: 'test-results/staging-junit.xml' }]
  ],

  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },
    {
      name: 'staging-tests',
      use: { 
        channel: 'chrome',
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
    }
  ],

  /* Environment-specific test patterns */
  testIgnore: [
    '**/production.*',
    '**/local.*'
  ],

  /* Docker-specific web server settings */
  webServer: {
    command: 'docker-compose ps wordpress | grep -q "Up" || echo "Docker containers should be running"',
    url: 'http://localhost:8080',
    reuseExistingServer: true,
    timeout: 180 * 1000, // Longer timeout for Docker startup
  },
});