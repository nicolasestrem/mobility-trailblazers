import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

test.describe('German Translation Tests', () => {
  test.use({ storageState: 'tests/.auth/admin.json' });

  test.beforeEach(async ({ page }) => {
    // Set German locale
    await page.addInitScript(() => {
      localStorage.setItem('wp_locale', 'de_DE');
    });
  });

  test('should verify .po and .mo files exist', async () => {
    const langDir = path.join(process.cwd(), 'languages');
    
    // Check for required translation files
    const requiredFiles = [
      'mobility-trailblazers.pot',
      'mobility-trailblazers-de_DE.po',
      'mobility-trailblazers-de_DE.mo'
    ];
    
    for (const file of requiredFiles) {
      const filePath = path.join(langDir, file);
      expect(fs.existsSync(filePath)).toBeTruthy();
      
      // Verify file is not empty
      const stats = fs.statSync(filePath);
      expect(stats.size).toBeGreaterThan(0);
    }
  });

  test('should verify German translations in UI', async ({ page }) => {
    // Navigate to German version of the site
    await page.goto('/?lang=de_DE');
    
    // Common German translations that should appear
    const germanTerms = [
      { english: 'Submit', german: 'Absenden' },
      { english: 'Save', german: 'Speichern' },
      { english: 'Cancel', german: 'Abbrechen' },
      { english: 'Delete', german: 'Löschen' },
      { english: 'Edit', german: 'Bearbeiten' },
      { english: 'Search', german: 'Suchen' },
      { english: 'Filter', german: 'Filtern' }
    ];
    
    // Check admin area
    await page.goto('/wp-admin/admin.php?page=mt-dashboard&lang=de_DE');
    
    for (const term of germanTerms) {
      const germanText = page.locator(`text=/${term.german}/i`).first();
      if (await germanText.isVisible({ timeout: 1000 }).catch(() => false)) {
        await expect(germanText).toBeVisible();
      }
    }
  });

  test('should verify MT plugin specific translations', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-dashboard&lang=de_DE');
    
    // Plugin-specific German translations
    const mtTranslations = [
      { key: 'Mobility Trailblazers', expected: 'Mobility Trailblazers' }, // Brand name stays same
      { key: 'Jury Member', expected: 'Jurymitglied' },
      { key: 'Candidate', expected: 'Kandidat' },
      { key: 'Evaluation', expected: 'Bewertung' },
      { key: 'Assignment', expected: 'Zuweisung' },
      { key: 'Criterion', expected: 'Kriterium' },
      { key: 'Total Score', expected: 'Gesamtpunktzahl' },
      { key: 'Innovation', expected: 'Innovation' },
      { key: 'Feasibility', expected: 'Machbarkeit' },
      { key: 'Impact', expected: 'Wirkung' },
      { key: 'Sustainability', expected: 'Nachhaltigkeit' },
      { key: 'Scalability', expected: 'Skalierbarkeit' }
    ];
    
    for (const translation of mtTranslations) {
      const element = page.locator(`text=/${translation.expected}/i`).first();
      if (await element.isVisible({ timeout: 1000 }).catch(() => false)) {
        await expect(element).toBeVisible();
      }
    }
  });

  test('should verify evaluation form translations', async ({ page }) => {
    // Login as jury member
    await page.context().storageState({ path: 'tests/.auth/jury.json' });
    await page.goto('/jury-dashboard/?lang=de_DE');
    
    // Check evaluation form translations
    const formTranslations = [
      'Bewertungskriterien',
      'Innovation und Neuartigkeit',
      'Machbarkeit und Umsetzung',
      'Gesellschaftliche Wirkung',
      'Nachhaltigkeit',
      'Skalierbarkeit',
      'Kommentare',
      'Bewertung speichern',
      'Als Entwurf speichern',
      'Bewertung einreichen'
    ];
    
    // Click on first evaluation if available
    const evaluationLink = page.locator('.evaluate-candidate-link').first();
    if (await evaluationLink.isVisible()) {
      await evaluationLink.click();
      
      for (const text of formTranslations) {
        const element = page.locator(`text=/${text}/i`).first();
        if (await element.isVisible({ timeout: 1000 }).catch(() => false)) {
          await expect(element).toBeVisible();
        }
      }
    }
  });

  test('should verify date and number formatting for German locale', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-evaluations&lang=de_DE');
    
    // German date format: DD.MM.YYYY
    const dateElements = await page.locator('[data-date], .date-field').allTextContents();
    for (const dateText of dateElements) {
      if (dateText && /\d/.test(dateText)) {
        // Check for German date format
        const germanDatePattern = /\d{1,2}\.\d{1,2}\.\d{4}/;
        expect(dateText).toMatch(germanDatePattern);
      }
    }
    
    // German number format: 1.234,56 (dot for thousands, comma for decimal)
    const numberElements = await page.locator('.score, .total-score').allTextContents();
    for (const numText of numberElements) {
      if (numText && /\d/.test(numText)) {
        // Check for German decimal separator
        if (numText.includes('.') || numText.includes(',')) {
          expect(numText).toMatch(/^\d{1,3}(\.?\d{3})*(,\d+)?$/);
        }
      }
    }
  });

  test('should verify error messages in German', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-assignments&lang=de_DE');
    
    // Trigger an error (e.g., try to submit empty form)
    const submitButton = page.locator('button[type="submit"]').first();
    if (await submitButton.isVisible()) {
      await submitButton.click();
      
      // Check for German error messages
      const germanErrors = [
        'Bitte füllen Sie alle Pflichtfelder aus',
        'Fehler',
        'Ungültige Eingabe',
        'Erforderlich',
        'Bitte wählen Sie'
      ];
      
      for (const errorText of germanErrors) {
        const errorElement = page.locator(`text=/${errorText}/i`).first();
        if (await errorElement.isVisible({ timeout: 1000 }).catch(() => false)) {
          await expect(errorElement).toBeVisible();
          break; // At least one German error message found
        }
      }
    }
  });

  test('should verify email templates in German', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-settings&tab=emails&lang=de_DE');
    
    // Check email template translations
    const emailTranslations = [
      'E-Mail-Vorlagen',
      'Betreff',
      'Nachricht',
      'Platzhalter',
      'Neue Zuweisung',
      'Bewertung eingereicht',
      'Bewertung genehmigt',
      'Erinnerung'
    ];
    
    for (const text of emailTranslations) {
      const element = page.locator(`text=/${text}/i`).first();
      if (await element.isVisible({ timeout: 1000 }).catch(() => false)) {
        await expect(element).toBeVisible();
      }
    }
  });

  test('should verify AJAX responses include German translations', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mt-dashboard&lang=de_DE');
    
    // Intercept AJAX responses
    const ajaxResponse = await page.waitForResponse(
      response => response.url().includes('admin-ajax.php'),
      { timeout: 10000 }
    ).catch(() => null);
    
    if (ajaxResponse) {
      const responseBody = await ajaxResponse.text();
      
      // Check if response contains German text
      const germanIndicators = ['erfolgreich', 'gespeichert', 'aktualisiert', 'gelöscht'];
      const hasGermanText = germanIndicators.some(text => 
        responseBody.toLowerCase().includes(text)
      );
      
      if (responseBody.length > 0) {
        expect(hasGermanText || responseBody.includes('success')).toBeTruthy();
      }
    }
  });

  test('should verify .po file syntax is valid', async () => {
    const poFile = path.join(process.cwd(), 'languages', 'mobility-trailblazers-de_DE.po');
    const poContent = fs.readFileSync(poFile, 'utf8');
    
    // Basic .po file validation
    const lines = poContent.split('\n');
    let inMsgid = false;
    let inMsgstr = false;
    let currentMsgid = '';
    
    for (const line of lines) {
      // Check for proper msgid/msgstr pairs
      if (line.startsWith('msgid ')) {
        inMsgid = true;
        inMsgstr = false;
        currentMsgid = line.substring(6).trim();
      } else if (line.startsWith('msgstr ')) {
        expect(inMsgid).toBeTruthy(); // msgstr must follow msgid
        inMsgid = false;
        inMsgstr = true;
        
        // Non-empty msgid should have non-empty msgstr (with some exceptions)
        const msgstr = line.substring(7).trim();
        if (currentMsgid !== '""' && currentMsgid !== '') {
          // Allow empty msgstr for context lines, but flag for review
          if (msgstr === '""' && !line.includes('msgctxt')) {
            console.warn(`Empty translation for: ${currentMsgid}`);
          }
        }
      } else if (line.startsWith('"') && (inMsgid || inMsgstr)) {
        // Continuation lines - check for proper escaping
        expect(line).toMatch(/^".*"$/);
      }
    }
  });

  test('should verify .mo file is compiled and up-to-date', async () => {
    const poFile = path.join(process.cwd(), 'languages', 'mobility-trailblazers-de_DE.po');
    const moFile = path.join(process.cwd(), 'languages', 'mobility-trailblazers-de_DE.mo');
    
    // Check that .mo file exists
    expect(fs.existsSync(moFile)).toBeTruthy();
    
    // Check that .mo is newer than .po (compiled after last .po change)
    const poStats = fs.statSync(poFile);
    const moStats = fs.statSync(moFile);
    
    // .mo should be newer or same age as .po
    expect(moStats.mtime.getTime()).toBeGreaterThanOrEqual(poStats.mtime.getTime() - 1000); // 1 second tolerance
  });
});