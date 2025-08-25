# CSS Remediation Implementation Checklist

## 🚀 Quick Start Commands

```bash
# Backup current CSS
./scripts/backup-css.sh

# Run CSS audit
npx stylelint "assets/css/**/*.css" --custom-formatter json > css-audit.json

# Test visual regression
npx playwright test --config=doc/playwright.config.ts --grep="visual"

# Deploy with feature flag
wp mt css-deploy --feature-flag=css_v4_migration --percentage=10
```

---

## Pre-Implementation Checklist

### ✅ Environment Setup
- [ ] Production backup created: `wp db export backup-$(date +%Y%m%d).sql`
- [ ] Staging environment synced with production
- [ ] Feature flags configured in `wp_options` table
- [ ] A/B testing framework activated
- [ ] Visual regression baseline captured
- [ ] Rollback scripts tested: `./scripts/css-rollback.sh`

### ✅ Team Alignment
- [ ] Stakeholders notified of timeline
- [ ] QA team briefed on test scenarios
- [ ] DevOps prepared for emergency response
- [ ] Documentation shared with team
- [ ] Daily standup scheduled (9 AM)
- [ ] Emergency contact list updated

---

## Day-by-Day Implementation Tasks

### 📅 **Day 1: Emergency Stabilization**

#### Morning Tasks (9 AM - 1 PM)
```css
/* BEFORE: mt-evaluation-forms.css */
.mt-evaluation-criteria {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* AFTER: mt-evaluation-forms.css */
@layer components {
    .mt-evaluation-criteria {
        display: block;
        visibility: visible;
        opacity: 1;
    }
}
```

- [ ] Create CSS layer structure in `mt-core.css`:
  ```css
  @layer reset, base, tokens, components, utilities;
  ```
- [ ] Move evaluation form styles to components layer
- [ ] Test with jury member account: `test-jury-01`
- [ ] Verify all 5 evaluation criteria visible
- [ ] Check mobile responsive (375px, 768px, 1024px)

#### Afternoon Tasks (2 PM - 6 PM)
- [ ] Fix `emergency-fixes.css` (25 !important):
  ```bash
  # Replace with proper specificity
  sed -i 's/!important//g' assets/css/emergency-fixes.css
  # Add proper selectors
  ```
- [ ] Remove `evaluation-fix.css` entirely
- [ ] Update `class-mt-public-assets.php`:
  ```php
  // Remove: wp_enqueue_style('mt-evaluation-fix', ...);
  ```
- [ ] Run evaluation form tests:
  ```bash
  npx playwright test evaluation-form.spec.ts
  ```
- [ ] Deploy to staging with monitoring

### 📅 **Day 2: Profile Override Refactor**

#### Critical File: `candidate-profile-override.css` (252 !important)

- [ ] **Step 1**: Analyze override patterns
  ```bash
  grep -n "!important" candidate-profile-override.css | head -20
  ```

- [ ] **Step 2**: Create scoped styles
  ```css
  /* BEFORE */
  .mt-hero-section {
      background: linear-gradient(...) !important;
  }
  
  /* AFTER */
  body.single-mt_candidate .entry-content .mt-hero-section {
      background: linear-gradient(...);
  }
  ```

- [ ] **Step 3**: Test profile pages:
  - [ ] `/vote/kandidaten/startup-01/`
  - [ ] `/vote/kandidaten/corporation-01/`
  - [ ] `/vote/kandidaten/individual-01/`

- [ ] **Step 4**: Visual regression check
  ```bash
  npx playwright test candidate-profiles.spec.ts --update-snapshots
  ```

### 📅 **Day 3: Grid System Consolidation**

#### Files to Merge (1,100+ !important):
- `mt-candidate-grid.css`
- `frontend-critical-fixes.css` 
- `mt-grid-responsive.css`
- `mt-grid-v3.css`

- [ ] **Step 1**: Create unified grid component
  ```css
  /* mt-grid-v4.css */
  @layer components {
      .mt-grid {
          --grid-columns: 1;
          --grid-gap: var(--mt-space-md);
          
          display: grid;
          grid-template-columns: repeat(var(--grid-columns), 1fr);
          gap: var(--grid-gap);
      }
      
      @media (min-width: 768px) {
          .mt-grid { --grid-columns: 2; }
      }
      
      @media (min-width: 1024px) {
          .mt-grid { --grid-columns: 3; }
      }
  }
  ```

- [ ] **Step 2**: Remove old grid files
  ```bash
  # Archive old files first
  mkdir -p assets/css/archive/grid-backup
  mv assets/css/mt-grid-*.css assets/css/archive/grid-backup/
  ```

- [ ] **Step 3**: Update grid instances in templates
  ```bash
  # Find all grid usages
  grep -r "mt-candidate-grid" templates/
  # Replace with mt-grid
  ```

### 📅 **Day 4: Mobile Dashboard Fix**

#### File: `v4/mt-mobile-jury-dashboard.css` (187 !important)

- [ ] Test on real devices:
  - [ ] iPhone 12/13 (Safari)
  - [ ] Samsung Galaxy (Chrome)
  - [ ] iPad (Safari)

- [ ] Fix touch targets (minimum 44x44px):
  ```css
  @layer components {
      @media (max-width: 768px) {
          .mt-jury-action-btn {
              min-height: 44px;
              min-width: 44px;
              padding: var(--mt-space-sm);
          }
      }
  }
  ```

- [ ] Remove viewport-specific !important:
  ```css
  /* REMOVE these patterns */
  @media (max-width: 768px) {
      .mt-element { 
          property: value !important; 
      }
  }
  ```

### 📅 **Day 5: Dashboard Consolidation**

- [ ] Merge dashboard files:
  1. `mt-jury-dashboard.css` (base)
  2. `mt-jury-dashboard-fix.css` (77 !important)
  3. `mt-jury-dashboard-enhanced.css`

- [ ] Create single dashboard component:
  ```bash
  cat mt-jury-dashboard*.css | sort -u > mt-dashboard-v4.css
  # Manual cleanup required after merge
  ```

- [ ] Test dashboard functionality:
  - [ ] Assignment creation
  - [ ] Evaluation submission
  - [ ] Filtering/sorting
  - [ ] Export features

### 📅 **Day 6: Brand Fixes Integration**

#### File: `mt-brand-fixes.css` (161 !important)

- [ ] Apply design tokens consistently:
  ```css
  /* Map brand colors to tokens */
  :root {
      --mt-brand-primary: #003C3D;
      --mt-brand-secondary: #FF7F32;
      --mt-brand-accent: #00A8A0;
  }
  ```

- [ ] Remove color overrides:
  ```bash
  # Find all color !important
  grep -E "color:.*!important|background.*!important" mt-brand-fixes.css
  ```

- [ ] Verify brand consistency:
  - [ ] Logo placement
  - [ ] Color palette
  - [ ] Typography scale
  - [ ] Spacing rhythm

### 📅 **Day 7-8: Framework Migration**

- [ ] **Day 7 Tasks**:
  - [ ] Remove `/v3` directory completely
  - [ ] Update autoloader paths
  - [ ] Convert remaining components to v4 tokens
  - [ ] Test cross-browser compatibility

- [ ] **Day 8 Tasks**:
  - [ ] Consolidate 26 duplicate `.mt-candidate-card` definitions
  - [ ] Create single `mt-components-v4.css`
  - [ ] Document component API
  - [ ] Update style guide

### 📅 **Day 9-10: Final Cleanup**

- [ ] **Day 9: Remove remaining !important**
  ```bash
  # Final audit
  find assets/css -name "*.css" -exec grep -l "!important" {} \;
  
  # Should return: No files found
  ```

- [ ] **Day 10: Performance optimization**
  - [ ] Minify all CSS files
  - [ ] Enable gzip compression
  - [ ] Configure browser caching
  - [ ] Test load times

---

## Testing Checklist

### 🧪 Automated Tests
```bash
# Run all CSS-related tests
npm run test:css

# Specific test suites
npx playwright test evaluation-form.spec.ts
npx playwright test candidate-profiles.spec.ts  
npx playwright test jury-dashboard.spec.ts
npx playwright test responsive.spec.ts
```

### 👁️ Visual Regression Tests
- [ ] Homepage layout
- [ ] Candidate grid (desktop/tablet/mobile)
- [ ] Individual candidate profiles
- [ ] Jury dashboard
- [ ] Evaluation forms
- [ ] Modal overlays
- [ ] Navigation menu
- [ ] Footer layout

### 📱 Device Testing Matrix
| Device | Browser | Viewport | Status |
|--------|---------|----------|--------|
| iPhone 12 | Safari | 375x812 | [ ] |
| iPhone 14 Pro | Safari | 393x852 | [ ] |
| Samsung S21 | Chrome | 384x854 | [ ] |
| iPad Pro | Safari | 1024x1366 | [ ] |
| Desktop | Chrome | 1920x1080 | [ ] |
| Desktop | Firefox | 1920x1080 | [ ] |
| Desktop | Edge | 1920x1080 | [ ] |

---

## Rollback Procedures

### 🔄 Quick Rollback (< 1 minute)
```bash
# Revert to backup CSS
./scripts/css-rollback.sh --quick

# Or manual:
cp -r /backup/css/2025-01-25/* /wp-content/plugins/mobility-trailblazers/assets/css/
wp cache flush
```

### 🔄 Full Rollback (< 5 minutes)
```bash
# Restore database and files
wp db import backup-20250125.sql
cd /wp-content/plugins/mobility-trailblazers
git checkout main
git pull origin main
wp cache flush
```

---

## Monitoring & Alerts

### 📊 Key Metrics to Track
```javascript
// Add to monitoring dashboard
const metrics = {
    cssLoadTime: performance.getEntriesByType('resource')
        .filter(r => r.name.includes('.css'))
        .reduce((sum, r) => sum + r.duration, 0),
    
    importantCount: Array.from(document.styleSheets)
        .flatMap(sheet => Array.from(sheet.cssRules))
        .filter(rule => rule.cssText?.includes('!important')).length,
    
    totalCSSSize: performance.getEntriesByType('resource')
        .filter(r => r.name.includes('.css'))
        .reduce((sum, r) => sum + r.transferSize, 0)
};
```

### 🚨 Alert Thresholds
- CSS Load Time > 2s → Warning
- !important count > 0 → Critical
- Visual regression > 5% → Warning
- 404 on CSS file → Critical
- Bundle size > 300KB → Warning

---

## Post-Implementation Verification

### ✅ Final Acceptance Criteria
- [ ] **Zero !important declarations** in production CSS
- [ ] **CSS files reduced** from 65 to <15
- [ ] **Bundle size** < 250KB (gzipped)
- [ ] **Load time** < 1.5s on 3G connection
- [ ] **No visual regressions** reported
- [ ] **All E2E tests passing** (100% success rate)
- [ ] **Lighthouse score** > 90 for performance
- [ ] **Mobile usability** score > 95
- [ ] **Zero console errors** related to CSS
- [ ] **Documentation updated** with new architecture

---

## Sign-off Requirements

### Technical Sign-off
- [ ] Lead Developer: _______________ Date: ___________
- [ ] QA Lead: _______________ Date: ___________
- [ ] DevOps: _______________ Date: ___________

### Business Sign-off
- [ ] Product Owner: _______________ Date: ___________
- [ ] Project Manager: _______________ Date: ___________

---

## Celebration Checklist 🎉
- [ ] Team retrospective scheduled
- [ ] Success metrics documented
- [ ] Lessons learned captured
- [ ] Team celebration planned
- [ ] Case study written
- [ ] Technical blog post drafted

---

*Use this checklist to ensure systematic implementation and track progress throughout the CSS remediation project.*