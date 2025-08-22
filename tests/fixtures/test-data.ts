import { Page } from '@playwright/test';

/**
 * Test data creation and management utilities
 */

export interface TestCandidate {
  id?: number;
  title: string;
  company: string;
  category: 'start-ups' | 'established-companies' | 'governance';
  innovation: string;
  description?: string;
}

export interface TestJuryMember {
  id?: number;
  username: string;
  email: string;
  firstName: string;
  lastName: string;
  role: 'mt_jury_member' | 'mt_jury_admin';
}

export interface TestEvaluation {
  candidateId: number;
  juryMemberId: number;
  criterion1: number;
  criterion2: number;
  criterion3: number;
  criterion4: number;
  criterion5: number;
  comments?: string;
  status: 'draft' | 'submitted' | 'approved';
}

/**
 * Sample test candidates
 */
export const testCandidates: TestCandidate[] = [
  {
    title: 'Test Startup Alpha',
    company: 'Alpha Mobility Solutions',
    category: 'start-ups',
    innovation: 'AI-powered traffic optimization system',
    description: 'Revolutionary approach to urban traffic management using machine learning algorithms.'
  },
  {
    title: 'Test Established Beta',
    company: 'Beta Transport Corp',
    category: 'established-companies', 
    innovation: 'Electric fleet management platform',
    description: 'Comprehensive solution for managing large electric vehicle fleets in urban environments.'
  },
  {
    title: 'Test Governance Gamma',
    company: 'City of Gamma',
    category: 'governance',
    innovation: 'Smart city mobility governance framework',
    description: 'Integrated policy framework for sustainable urban mobility planning.'
  },
  {
    title: 'Test Innovation Delta',
    company: 'Delta Dynamics',
    category: 'start-ups',
    innovation: 'Autonomous delivery drone network',
    description: 'Last-mile delivery solution using coordinated autonomous drone systems.'
  },
  {
    title: 'Test Enterprise Epsilon',
    company: 'Epsilon Enterprises',
    category: 'established-companies',
    innovation: 'Multimodal transport integration platform',
    description: 'Seamless integration of multiple transportation modes through unified digital platform.'
  }
];

/**
 * Sample test jury members
 */
export const testJuryMembers: TestJuryMember[] = [
  {
    username: 'jurytester1',
    email: 'jury1@test.example.com',
    firstName: 'Maria',
    lastName: 'Schmidt',
    role: 'mt_jury_member'
  },
  {
    username: 'jurytester2', 
    email: 'jury2@test.example.com',
    firstName: 'Hans',
    lastName: 'Mueller',
    role: 'mt_jury_member'
  },
  {
    username: 'juryadmintester',
    email: 'juryadmin@test.example.com',
    firstName: 'Anna',
    lastName: 'Weber',
    role: 'mt_jury_admin'
  }
];

/**
 * Create test data in WordPress
 */
export async function createTestData(page: Page, baseURL: string) {
  try {
    // Try to access WP admin first
    await page.goto(`${baseURL}/wp-admin`);
    
    // Check if we're already logged in or need to login
    const isLoggedIn = await page.locator('#wpadminbar').isVisible();
    
    if (!isLoggedIn) {
      console.log('Not logged in, attempting to login with admin credentials...');
      
      await page.fill('#user_login', process.env.ADMIN_USERNAME || 'admin');
      await page.fill('#user_pass', process.env.ADMIN_PASSWORD || 'admin');
      await page.click('#wp-submit');
      
      // Wait for login success
      await page.waitForURL('**/wp-admin/**');
    }
    
    console.log('✅ Successfully accessed WordPress admin');
    
    // Create test candidates if they don't exist
    await createTestCandidates(page, baseURL);
    
    // Create test jury members if they don't exist
    await createTestJuryMembers(page, baseURL);
    
    console.log('✅ Test data setup completed');
    
  } catch (error) {
    console.warn('⚠️  Could not create test data automatically:', error);
    console.log('📝 Please ensure test data exists manually or check credentials');
  }
}

/**
 * Create test candidates
 */
async function createTestCandidates(page: Page, baseURL: string) {
  for (const candidate of testCandidates.slice(0, 3)) { // Create first 3 for testing
    try {
      // Go to new post page for mt_candidate post type
      await page.goto(`${baseURL}/wp-admin/post-new.php?post_type=mt_candidate`);
      
      // Check if the page loaded correctly
      if (await page.locator('#title').isVisible()) {
        // Fill in candidate details
        await page.fill('#title', candidate.title);
        
        // Fill meta fields if they exist
        if (await page.locator('input[name="company"]').isVisible()) {
          await page.fill('input[name="company"]', candidate.company);
        }
        
        if (await page.locator('textarea[name="innovation"]').isVisible()) {
          await page.fill('textarea[name="innovation"]', candidate.innovation);
        }
        
        // Set category if dropdown exists
        if (await page.locator('select[name="category"]').isVisible()) {
          await page.selectOption('select[name="category"]', candidate.category);
        }
        
        // Publish the candidate
        await page.click('#publish');
        
        // Wait for success message
        await page.waitForSelector('.notice-success', { timeout: 10000 });
        
        console.log(`✅ Created test candidate: ${candidate.title}`);
      }
    } catch (error) {
      console.warn(`⚠️  Could not create candidate ${candidate.title}:`, error);
    }
  }
}

/**
 * Create test jury members
 */
async function createTestJuryMembers(page: Page, baseURL: string) {
  for (const juryMember of testJuryMembers) {
    try {
      // Go to new user page
      await page.goto(`${baseURL}/wp-admin/user-new.php`);
      
      if (await page.locator('#user_login').isVisible()) {
        // Fill in user details
        await page.fill('#user_login', juryMember.username);
        await page.fill('#email', juryMember.email);
        await page.fill('#first_name', juryMember.firstName);
        await page.fill('#last_name', juryMember.lastName);
        
        // Set password
        await page.fill('#pass1', 'test123!@#');
        await page.fill('#pass2', 'test123!@#');
        
        // Set role
        await page.selectOption('#role', juryMember.role);
        
        // Submit form
        await page.click('#createusersub');
        
        // Wait for success or error
        await page.waitForTimeout(2000);
        
        console.log(`✅ Created test jury member: ${juryMember.username}`);
      }
    } catch (error) {
      console.warn(`⚠️  Could not create jury member ${juryMember.username}:`, error);
    }
  }
}

/**
 * Clean up test data
 */
export async function cleanupTestData(page: Page, baseURL: string) {
  try {
    console.log('🧹 Cleaning up test data...');
    
    // Delete test candidates
    await page.goto(`${baseURL}/wp-admin/edit.php?post_type=mt_candidate`);
    
    for (const candidate of testCandidates) {
      const candidateRow = page.locator(`tr:has-text("${candidate.title}")`);
      if (await candidateRow.isVisible()) {
        await candidateRow.locator('.row-actions .trash a').click();
        console.log(`🗑️  Deleted test candidate: ${candidate.title}`);
      }
    }
    
    // Delete test users
    await page.goto(`${baseURL}/wp-admin/users.php`);
    
    for (const juryMember of testJuryMembers) {
      const userRow = page.locator(`tr:has-text("${juryMember.username}")`);
      if (await userRow.isVisible()) {
        await userRow.locator('.row-actions .delete a').click();
        await page.click('#submit'); // Confirm deletion
        console.log(`🗑️  Deleted test jury member: ${juryMember.username}`);
      }
    }
    
    console.log('✅ Test data cleanup completed');
    
  } catch (error) {
    console.warn('⚠️  Could not clean up test data:', error);
  }
}

/**
 * Generate random test evaluation data
 */
export function generateRandomEvaluation(candidateId: number, juryMemberId: number): TestEvaluation {
  return {
    candidateId,
    juryMemberId,
    criterion1: Math.floor(Math.random() * 10) + 1,
    criterion2: Math.floor(Math.random() * 10) + 1,
    criterion3: Math.floor(Math.random() * 10) + 1,
    criterion4: Math.floor(Math.random() * 10) + 1,
    criterion5: Math.floor(Math.random() * 10) + 1,
    comments: `Test evaluation comments for candidate ${candidateId}`,
    status: Math.random() > 0.5 ? 'submitted' : 'draft'
  };
}