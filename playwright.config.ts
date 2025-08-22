import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';

// Load test environment variables
dotenv.config({ path: path.resolve(__dirname, 'tests', '.env.test') });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }],
    ['list']
  ],
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: 'http://localhost:8080',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    
    /* Take screenshot on failure */
    screenshot: 'only-on-failure',
    
    /* Record video on failure */
    video: 'retain-on-failure',
    
    /* Global timeout for each action */
    actionTimeout: 30000,
    
    /* Navigation timeout */
    navigationTimeout: 30000,
    
    /* Test timeout */
    timeout: 60000,
  },

  /* Global setup and teardown */
  globalSetup: './tests/global-setup.ts',
  globalTeardown: './tests/global-teardown.ts',

  /* Configure projects for major browsers */
  projects: [
    // Setup project for authentication
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },

    // Desktop browsers
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
    },

    {
      name: 'firefox',
      use: { 
        ...devices['Desktop Firefox'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
    },

    {
      name: 'webkit',
      use: { 
        ...devices['Desktop Safari'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
    },

    // Mobile browsers
    {
      name: 'Mobile Chrome',
      use: { 
        ...devices['Pixel 5'],
        storageState: 'tests/.auth/jury.json'
      },
      dependencies: ['setup'],
    },
    {
      name: 'Mobile Safari',
      use: { 
        ...devices['iPhone 12'],
        storageState: 'tests/.auth/jury.json'
      },
      dependencies: ['setup'],
    },

    // Tablet
    {
      name: 'Tablet',
      use: { 
        ...devices['iPad Pro'],
        storageState: 'tests/.auth/jury.json'
      },
      dependencies: ['setup'],
    },

    // Jury member specific tests
    {
      name: 'jury-member',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/jury.json'
      },
      dependencies: ['setup'],
      testMatch: /.*jury.*\.spec\.ts/,
    },

    // Admin specific tests
    {
      name: 'admin',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
      testMatch: /.*admin.*\.spec\.ts/,
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'echo "WordPress server should be running on localhost:8080"',
    url: 'http://localhost:8080',
    reuseExistingServer: true,
    timeout: 120 * 1000,
  },

  /* Test output directory */
  outputDir: 'test-results',
  
  /* Expect timeout */
  expect: {
    timeout: 10000,
  },
});