import { FullConfig } from '@playwright/test';

/**
 * Global teardown for Playwright tests
 * Runs once after all tests complete
 */
async function globalTeardown(config: FullConfig) {
  console.log('🧹 Starting test cleanup...');
  
  try {
    // Cleanup test artifacts
    console.log('📁 Cleaning up test artifacts...');
    
    // Remove temporary auth files if needed
    const fs = await import('fs/promises');
    const path = await import('path');
    
    const authDir = path.join(process.cwd(), 'tests', '.auth');
    
    try {
      const authFiles = await fs.readdir(authDir);
      for (const file of authFiles) {
        if (file.endsWith('.json')) {
          const filePath = path.join(authDir, file);
          // Check if file is older than 1 hour (cleanup old auth states)
          const stats = await fs.stat(filePath);
          const now = new Date();
          const fileAge = now.getTime() - stats.mtime.getTime();
          const oneHour = 60 * 60 * 1000;
          
          if (fileAge > oneHour) {
            await fs.unlink(filePath);
            console.log(`🗑️  Removed old auth file: ${file}`);
          }
        }
      }
    } catch (error) {
      // Auth directory doesn't exist or is empty - that's fine
      console.log('📝 No auth files to cleanup');
    }
    
    // Log test summary
    console.log('📊 Test run completed');
    console.log(`📍 Base URL: ${config.projects[0]?.use?.baseURL || 'localhost'}`);
    console.log(`🖥️  Projects tested: ${config.projects.length}`);
    
    console.log('✅ Cleanup completed successfully');
    
  } catch (error) {
    console.error('❌ Cleanup failed:', error);
    // Don't throw - teardown failures shouldn't fail the test run
  }
}

export default globalTeardown;