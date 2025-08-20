# phpMyAdmin Fix Guide - Missing Evaluation Criteria

## ⚠️ IMPORTANT: BACKUP FIRST!

Before doing anything:
1. In phpMyAdmin, select database `wp_mobil_db1`
2. Click "Export" tab
3. Choose "Quick" method, Format: SQL
4. Click "Go" to download backup

## Step-by-Step Instructions

### Step 1: Open phpMyAdmin
1. Login to phpMyAdmin
2. Select database: `wp_mobil_db1`
3. Click on "SQL" tab

### Step 2: Check Current Status
Copy and run this query first to see what needs fixing:

```sql
SELECT 
    'Current Status' as Report,
    COUNT(*) as total_candidates,
    SUM(CASE WHEN EXISTS (SELECT 1 FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_mt_criterion_relevance' AND meta_value != '') THEN 1 ELSE 0 END) as with_relevance,
    SUM(CASE WHEN EXISTS (SELECT 1 FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_mt_criterion_visibility' AND meta_value != '') THEN 1 ELSE 0 END) as with_visibility
FROM wp_posts p
WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish';
```

**Expected result:** 
- total_candidates: 48
- with_relevance: Should be less than 48 (probably 37)
- with_visibility: Should be less than 48 (probably 37)

### Step 3: Get Affected Candidate IDs
Run this to get the IDs of candidates that need fixing:

```sql
SELECT ID, post_title
FROM wp_posts 
WHERE post_type = 'mt_candidate' 
AND post_status = 'publish'
AND post_title IN (
    'Xanthi Doubara',
    'Michael Klasa',
    'Nic Knapp',
    'Oliver Blume',
    'Oliver May-Beckmann',
    'Roy Uhlmann',
    'Wen Han',
    'Dr. Corsin Sulser',
    'Friedrich Dräxlmaier',
    'Karel Dijkman',
    'Léa Miggiano'
);
```

**Write down these IDs!** You'll need them for the next steps.

### Step 4: Fix Misnamed Meta Keys
Run these UPDATE queries one by one:

```sql
-- Fix courage
UPDATE wp_postmeta 
SET meta_key = '_mt_criterion_courage' 
WHERE meta_key = '_mt_evaluation_courage' 
AND post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'mt_candidate');

-- Fix innovation
UPDATE wp_postmeta 
SET meta_key = '_mt_criterion_innovation' 
WHERE meta_key = '_mt_evaluation_innovation' 
AND post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'mt_candidate');

-- Fix implementation
UPDATE wp_postmeta 
SET meta_key = '_mt_criterion_implementation' 
WHERE meta_key = '_mt_evaluation_implementation' 
AND post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'mt_candidate');

-- Fix relevance
UPDATE wp_postmeta 
SET meta_key = '_mt_criterion_relevance' 
WHERE meta_key = '_mt_evaluation_relevance' 
AND post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'mt_candidate');

-- Fix visibility
UPDATE wp_postmeta 
SET meta_key = '_mt_criterion_visibility' 
WHERE meta_key = '_mt_evaluation_visibility' 
AND post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'mt_candidate');
```

### Step 5: Add Missing Criteria Data

**Option A: Use the automated INSERT queries** (Recommended)

Run the entire SQL from `production-fix-phpmyadmin.sql` Step 5 section. These queries use SELECT to find the right ID automatically.

**Option B: Manual INSERT with specific IDs**

If Option A doesn't work, use the IDs from Step 3 and run queries like this:

Example for Xanthi Doubara (replace `XXXX` with actual ID):
```sql
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(XXXX, '_mt_criterion_relevance', 'Doubara beweist, wie On-Demand-Mobilität den ÖPNV ergänzen und Mobilitätslücken schließen kann.'),
(XXXX, '_mt_criterion_visibility', 'Als Pionierin im Bereich autonomer und geteilter Mobilität ist sie sichtbare Vorreiterin der Verkehrswende.');
```

### Step 6: Verify the Fix
Run this final check:

```sql
SELECT 
    'Final Status' as Report,
    COUNT(*) as total_candidates,
    SUM(CASE WHEN EXISTS (SELECT 1 FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_mt_criterion_relevance' AND meta_value != '') THEN 1 ELSE 0 END) as with_relevance,
    SUM(CASE WHEN EXISTS (SELECT 1 FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_mt_criterion_visibility' AND meta_value != '') THEN 1 ELSE 0 END) as with_visibility
FROM wp_posts p
WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish';
```

**Expected result:** All counts should be 48!

### Step 7: Clear WordPress Cache

After running the SQL fixes:

1. **Via WordPress Admin:**
   - Login to WordPress admin
   - Go to Settings → Clear Cache (if you have a cache plugin)
   - Or use Admin Bar → Clear Cache

2. **Via FTP/File Manager:**
   - Delete everything in `/wp-content/cache/` directory

3. **Via Hosting Panel:**
   - Look for "Clear Cache" or "Purge Cache" option
   - Clear Varnish/Redis cache if available

### Step 8: Test on Production Site

1. Go to: https://mobilitytrailblazers.de/vote/
2. Login as jury member (or admin)
3. Go to jury dashboard
4. Click evaluation for these candidates:
   - Xanthi Doubara
   - Michael Klasa
   - Any other from the list

**Each should now show 5 evaluation criteria:**
- ✅ Mut & Pioniergeist
- ✅ Innovationsgrad
- ✅ Umsetzungskraft & Wirkung
- ✅ Relevanz für die Mobilitätswende
- ✅ Vorbildfunktion & Sichtbarkeit

## Troubleshooting

### If queries don't work:
1. Check table prefix (might not be `wp_`)
2. Check that candidates exist with exact names
3. Try running queries one at a time

### To find actual table prefix:
```sql
SHOW TABLES LIKE '%posts';
```
If it shows `xyz_posts`, then your prefix is `xyz_` not `wp_`

### To check what's in postmeta for a specific candidate:
```sql
SELECT * FROM wp_postmeta 
WHERE post_id = XXXX 
AND meta_key LIKE '%criterion%';
```

## Quick Reference - All 11 Candidates Data

| Candidate | Relevance | Visibility |
|-----------|-----------|------------|
| **Xanthi Doubara** | Doubara beweist, wie On-Demand-Mobilität den ÖPNV ergänzen und Mobilitätslücken schließen kann. | Als Pionierin im Bereich autonomer und geteilter Mobilität ist sie sichtbare Vorreiterin der Verkehrswende. |
| **Michael Klasa** | Klasa zeigt, wie traditionelle Verkehrsunternehmen sich transformieren und neue Mobilitätsdienste integrieren können. | Als Vorstand eines regionalen Verkehrsunternehmens ist er Vorbild für die Branchentransformation. |
| **Nic Knapp** | Knapp demonstriert, wie digitale Lösungen die Effizienz und Nachhaltigkeit im Verkehr steigern können. | Als Innovator im Mobilitätssektor ist er sichtbarer Treiber der digitalen Transformation. |
| **Oliver Blume** | Blume treibt die Elektrifizierung eines der größten Automobilkonzerne voran und beeinflusst damit die gesamte Branche. | Als CEO von Porsche und VW-Konzern ist er eine der sichtbarsten Führungspersönlichkeiten der Mobilitätswende. |
| **Oliver May-Beckmann** | May-Beckmann zeigt, wie klassische Logistik nachhaltiger und effizienter gestaltet werden kann. | Als Führungskraft in der Logistikbranche ist er Vorbild für nachhaltige Transformation. |
| **Roy Uhlmann** | Uhlmann demonstriert, wie neue Technologien die Mobilität revolutionieren und nachhaltiger machen können. | Als Innovator und Unternehmer ist er sichtbarer Pionier neuer Mobilitätslösungen. |
| **Wen Han** | Han zeigt, wie internationale Kooperation die Mobilitätswende beschleunigen kann. | Als Brückenbauer zwischen Märkten ist er sichtbares Vorbild für globale Mobilitätsinnovation. |
| **Dr. Corsin Sulser** | Sulser zeigt, wie Bahninfrastruktur modernisiert und digitalisiert werden kann, um mehr Kapazität und Pünktlichkeit zu erreichen. | Als Leiter eines der wichtigsten Bahnprojekte Europas ist er international sichtbar und Vorbild für moderne Mobilitätsinfrastruktur. |
| **Friedrich Dräxlmaier** | Seine Vision einer vernetzten, elektrischen Mobilität trägt direkt zur Transformation des Verkehrssektors bei. | Als Unternehmer und Innovator ist er ein sichtbares Vorbild für nachhaltige Mobilität in der Automobilindustrie. |
| **Karel Dijkman** | Dijkman treibt die Digitalisierung und Automatisierung des Güterverkehrs voran, was essentiell für nachhaltige Logistik ist. | Als Führungskraft in einem großen Logistikunternehmen hat er erhebliche Reichweite und Einfluss auf die Branche. |
| **Léa Miggiano** | Miggiano fördert nachhaltige urbane Mobilität und zeigt, wie Städte lebenswerter werden können. | Als Marketingchefin eines großen Mobilitätsanbieters prägt sie das öffentliche Bild nachhaltiger Mobilität. |