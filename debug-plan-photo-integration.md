# Photo Integration & UI Enhancement Debug Plan

## Overview
Systematic testing plan for v2.4.0 photo management and UI enhancements using Docker, MySQL, WordPress, and browser testing.

## Prerequisites
- WordPress site running in Docker
- Access to MySQL database
- Chrome browser for visual testing
- MCP tools: docker, mysql, wordpress, vision

---

## Phase 1: Environment Verification (5 mins)

### 1.1 Check Docker Status
```bash
# Check all containers
mcp__docker__mobility_status

# Verify WordPress container
mcp__docker__docker_ps

# Check recent logs
mcp__docker__wp_logs --lines=50
```

**Expected Results:**
- ✅ WordPress container running
- ✅ MariaDB container running
- ✅ No critical errors in logs

### 1.2 Verify Plugin Status
```bash
# Check plugin activation
mcp__wordpress__wp_plugin_list

# Verify plugin version
mcp__wordpress__wp_cli "plugin get mobility-trailblazers --field=version"
```

**Expected Results:**
- ✅ mobility-trailblazers active
- ✅ Version 2.4.0 or higher

---

## Phase 2: Database Verification (10 mins)

### 2.1 Check Candidate Data
```sql
# Count total candidates
mcp__mysql__mysql_query "SELECT COUNT(*) as total FROM wp_posts WHERE post_type='mt_candidate' AND post_status='publish'"

# Check specific candidates
mcp__mysql__mysql_query "SELECT ID, post_title FROM wp_posts WHERE post_type='mt_candidate' AND post_title LIKE '%Günther%'"

# Verify meta data
mcp__mysql__mysql_query "SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE meta_key LIKE '_mt_%' LIMIT 10"
```

**Expected Results:**
- ✅ 51-52 candidates found
- ✅ Günther Schuh exists (ID: 4444)
- ✅ Meta fields populated

### 2.2 Check Photo Attachments
```sql
# Check existing featured images
mcp__mysql__mysql_query "
SELECT p.ID, p.post_title, pm.meta_value as thumbnail_id 
FROM wp_posts p 
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
WHERE p.post_type = 'mt_candidate' 
AND p.post_status = 'publish'
LIMIT 10"
```

**Expected Results:**
- ✅ Some candidates may already have thumbnails
- ✅ Note which ones are missing

---

## Phase 3: Photo Attachment Execution (15 mins)

### 3.1 Run Verification Script
```bash
# First verify current status
mcp__wordpress__wp_cli "eval-file verify-photo-matching.php"
```

**Expected Results:**
- ✅ Report shows candidates without photos
- ✅ Photo files detected in directory
- ✅ Mappings verified

### 3.2 Execute Photo Attachment
```bash
# Run the complete attachment script
mcp__wordpress__wp_cli "eval-file direct-photo-attach-complete.php"
```

**Expected Results:**
- ✅ Processing messages for each candidate
- ✅ Success count > 0
- ✅ Summary shows attachments created

### 3.3 Verify Attachments in Database
```sql
# Check attachment count
mcp__mysql__mysql_query "
SELECT COUNT(DISTINCT pm.meta_value) as attached_photos
FROM wp_postmeta pm
WHERE pm.meta_key = '_thumbnail_id'
AND pm.post_id IN (
    SELECT ID FROM wp_posts 
    WHERE post_type = 'mt_candidate' 
    AND post_status = 'publish'
)"

# Check media library
mcp__mysql__mysql_query "
SELECT COUNT(*) as webp_images 
FROM wp_posts 
WHERE post_type = 'attachment' 
AND post_mime_type = 'image/webp'"
```

**Expected Results:**
- ✅ 50+ attached photos
- ✅ WebP images in media library

---

## Phase 4: Frontend Testing - Candidate Profiles (15 mins)

### 4.1 Test Individual Candidate Page
```
Navigate to: /mt_candidate/alexander-moller/
```

**Visual Checks (Use Vision MCP):**
- ✅ Hero section with gradient background
- ✅ Photo displayed in frame (280x280px)
- ✅ Name, position, organization visible
- ✅ Category badges displayed
- ✅ Social links (LinkedIn, Website)

### 4.2 Check Evaluation Criteria Display
**Visual Elements to Verify:**
- ✅ 5 criteria cards displayed
- ✅ Icons for each criterion
- ✅ Colored borders (orange, blue, green, purple, pink)
- ✅ Readable text in each card
- ✅ Sidebar with Quick Facts

### 4.3 Test Navigation
- ✅ Previous/Next candidate links work
- ✅ Links maintain category context

---

## Phase 5: Frontend Testing - Candidates Grid (10 mins)

### 5.1 Navigate to Candidates Grid
```
Navigate to: /kandidaten/ or page with [mt_candidates_grid] shortcode
```

**Visual Checks:**
- ✅ Card-based layout visible
- ✅ Photos displayed in cards
- ✅ Search box at top
- ✅ Category filter buttons

### 5.2 Test Interactive Features
**Search Functionality:**
1. Type "Oliver" in search box
2. Verify cards filter in real-time
3. Clear search, verify all return

**Category Filtering:**
1. Click category buttons
2. Verify smooth animations
3. Check "All" button resets

**Hover Effects:**
1. Hover over cards
2. Verify image scales up
3. Check shadow increases

---

## Phase 6: Data Processing (10 mins)

### 6.1 Parse Evaluation Criteria
```bash
# Parse criteria for structured display
mcp__wordpress__wp_cli "eval-file tools/parse-criteria.php process"

# Verify parsing results
mcp__wordpress__wp_cli "eval-file tools/parse-criteria.php verify 4377"
```

**Expected Results:**
- ✅ Criteria parsed into 5 fields
- ✅ Meta fields created for each criterion
- ✅ Verification shows structured data

### 6.2 Check Parsed Data in Database
```sql
mcp__mysql__mysql_query "
SELECT meta_key, LEFT(meta_value, 100) as preview
FROM wp_postmeta 
WHERE post_id = 4377 
AND meta_key LIKE '_mt_criterion_%'"
```

---

## Phase 7: Responsive & Performance Testing (10 mins)

### 7.1 Mobile Responsiveness
**Browser Developer Tools (F12):**
1. Toggle device toolbar
2. Select iPhone 12 Pro
3. Check candidate profile page
4. Check candidates grid

**Verify:**
- ✅ Single column layout on mobile
- ✅ Text remains readable
- ✅ Images scale properly
- ✅ Navigation remains accessible

### 7.2 JavaScript Console Check
**Open Console (F12 > Console):**
- ✅ No red errors
- ✅ No 404s for resources
- ✅ AJAX calls succeed (if any)

### 7.3 Performance Metrics
**Network Tab (F12 > Network):**
- ✅ Images load as WebP
- ✅ Total page size < 3MB
- ✅ Load time < 3 seconds

---

## Phase 8: Jury Features Testing (5 mins)

### 8.1 Check Jury Dashboard
```bash
# Get jury member IDs
mcp__mysql__mysql_query "SELECT ID, post_title FROM wp_posts WHERE post_type='mt_jury_member' LIMIT 5"
```

### 8.2 Test Jury Profile Page
```
Navigate to: /mt_jury_member/[jury-member-slug]/
```

**Visual Checks:**
- ✅ Circular profile photo
- ✅ Statistics cards (4 metrics)
- ✅ Bio section if available
- ✅ Expertise tags

---

## Phase 9: Error Checking & Logs (5 mins)

### 9.1 Check WordPress Debug Log
```bash
mcp__wordpress__wp_debug_log --lines=50
```

### 9.2 Check Plugin Errors
```bash
mcp__mysql__mt_debug_check
```

### 9.3 Container Health
```bash
mcp__docker__docker_exec --container=mobility-trailblazers-wordpress-1 --command="df -h"
mcp__docker__docker_exec --container=mobility-trailblazers-wordpress-1 --command="free -m"
```

---

## Phase 10: Final Verification Checklist

### Visual Summary (Take Screenshots)
1. ✅ Candidate profile with photo
2. ✅ Candidates grid with filters
3. ✅ Mobile responsive view
4. ✅ Jury member profile

### Functional Summary
1. ✅ All 52 candidates have photos
2. ✅ Search and filter work
3. ✅ Criteria display properly
4. ✅ No JavaScript errors
5. ✅ Responsive on mobile

### Performance Summary
1. ✅ Page load < 3 seconds
2. ✅ Images optimized (WebP)
3. ✅ Smooth animations
4. ✅ No memory issues

---

## Troubleshooting Guide

### Issue: Photos Not Displaying
```bash
# Check file permissions
mcp__docker__docker_exec --container=mobility-trailblazers-wordpress-1 --command="ls -la wp-content/uploads/"

# Regenerate thumbnails
mcp__wordpress__wp_cli "media regenerate --yes"
```

### Issue: JavaScript Not Working
```bash
# Clear cache
mcp__wordpress__wp_cache_flush

# Check script registration
mcp__wordpress__wp_cli "eval 'global \$wp_scripts; print_r(\$wp_scripts->registered[\"mt-candidate-interactions\"]);'"
```

### Issue: Slow Performance
```bash
# Optimize database
mcp__mysql__mysql_query "OPTIMIZE TABLE wp_posts, wp_postmeta"

# Check query performance
mcp__mysql__mysql_query "SHOW PROCESSLIST"
```

### Issue: Criteria Not Parsing
```bash
# Clear existing parsed data
mcp__wordpress__wp_cli "eval-file tools/parse-criteria.php clear"

# Re-run parsing
mcp__wordpress__wp_cli "eval-file tools/parse-criteria.php process"
```

---

## Success Criteria

The implementation is considered successful when:

1. **Photo Integration (100%)**
   - All 52 candidates have featured images
   - Photos display correctly on profiles and grid
   - No broken image links

2. **UI Enhancements (100%)**
   - Hero sections with gradients visible
   - Criteria cards properly formatted
   - Grid layout responsive and filterable

3. **Performance (Acceptable)**
   - Page load under 3 seconds
   - Smooth animations (60fps)
   - No memory leaks

4. **Functionality (100%)**
   - Search works instantly
   - Filters apply correctly
   - Navigation between profiles works

5. **Mobile Experience (100%)**
   - Responsive on all screen sizes
   - Touch interactions work
   - Text remains readable

---

## Reporting Template

```markdown
## Photo Integration Test Report
Date: [DATE]
Version: 2.4.0
Tester: Claude Desktop

### Environment
- WordPress: [VERSION]
- PHP: [VERSION]
- MySQL: [VERSION]
- Browser: Chrome [VERSION]

### Results Summary
- Total Candidates: 52
- Photos Attached: [NUMBER]
- Success Rate: [PERCENTAGE]%

### UI Testing
- [ ] Candidate Profiles: PASS/FAIL
- [ ] Candidates Grid: PASS/FAIL
- [ ] Jury Profiles: PASS/FAIL
- [ ] Mobile Responsive: PASS/FAIL

### Performance
- Average Load Time: [TIME]s
- Largest Image: [SIZE]KB
- Total Page Size: [SIZE]MB

### Issues Found
1. [ISSUE DESCRIPTION]
   - Severity: Low/Medium/High
   - Resolution: [STEPS TAKEN]

### Recommendations
1. [RECOMMENDATION]
2. [RECOMMENDATION]

### Screenshots
- [Attach key screenshots]
```

---

## Time Estimate
- **Total Time:** 60-75 minutes
- **Critical Path:** Phases 1-4 (45 mins)
- **Optional:** Phases 5-10 (30 mins)

## Next Steps After Testing
1. Document any issues found
2. Create GitHub issues for bugs
3. Plan optimization tasks if needed
4. Schedule production deployment