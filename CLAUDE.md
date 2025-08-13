# Mobility Trailblazers WordPress Plugin

Award platform for "25 Mobility Trailblazers in 25" - DACH region mobility innovators.

## 🚨 CRITICAL PROJECT RULES

**PROJECT LOCATION**: `E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers`

### Non-Negotiable Rules
- **NEVER remove features without asking first**
- **NEVER use --no-verify when committing**
- **ALWAYS verify nonces in AJAX handlers**
- **ALWAYS check existing code before implementing**
- **ALWAYS start by reviewing ALL code files to understand context**

## 🏗️ ARCHITECTURE & PATTERNS

### File Structure
```
includes/
├── core/           # MT_Plugin, MT_Activator
├── admin/          # MT_Admin, dashboards
├── ajax/           # AJAX handlers (MUST verify nonces)
├── repositories/   # Data access layer
├── services/       # Business logic
└── widgets/        # Elementor widgets
```

### Naming Conventions
- **Classes**: `MT_Assignment_Service`
- **Methods**: `process_auto_assignment()`
- **Files**: `class-mt-assignment-service.php`
- **Tables**: `wp_mt_assignments`
- **CSS**: `.mt-assignment__header`
- **Text Domain**: `'mobility-trailblazers'`

### Required Code Patterns

#### AJAX Handler Template (MANDATORY)
```php
if (!wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')) {
    wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
    return;
}
if (!current_user_can('edit_posts')) {
    wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
    return;
}
$data = array_map('sanitize_text_field', $_POST['data']);
```

## 📊 DATABASE & CORE FEATURES

### Database Tables
- **wp_mt_evaluations**: criterion_1-5, comments, status, jury_member_id
- **wp_mt_assignments**: jury_member_id, candidate_id, status
- **wp_posts**: post_type='mt_candidate'

### Core Features
- **Evaluation System**: 5 criteria (0-100), inline AJAX save, draft/submitted states
- **Assignments**: Auto-assignment, conflict detection, bulk operations
- **Candidates**: Custom post type `mt_candidate`, CSV import/export
- **Jury Dashboard**: Progress tracking, personalized evaluation interface

## 🛠️ DEVELOPMENT WORKFLOW

### 1. Before Starting Any Task
```bash
# Check existing implementation
grep -r "MT_" includes/
```

### 2. Development Process
1. Read relevant docs in `/doc/`
2. Follow Repository-Service-Controller pattern
3. Test with different user roles
4. Update documentation when done

### 3. Common Commands
```bash
# Security & Testing
composer security-scan
composer check-nonce
composer check-escaping
wp db check

# Development & Debug
tail -f wp-content/debug.log
wp transient delete --all
wp db query "SHOW TABLES LIKE '%mt_%'"
```

## ✅ QUALITY ASSURANCE

### Security Checklist (MANDATORY)
- [ ] All user inputs sanitized: `sanitize_text_field()`, `wp_kses_post()`
- [ ] All outputs escaped: `esc_html()`, `esc_url()`, `esc_attr()`
- [ ] Nonces verified for forms and AJAX
- [ ] Capability checks: `current_user_can()`
- [ ] SQL injection prevention: `$wpdb->prepare()`

### Testing Checklist (BEFORE COMPLETION)
- [ ] Test with sample data
- [ ] Test edge cases (empty fields, special characters)
- [ ] Verify backward compatibility
- [ ] Check for PHP errors with WP_DEBUG enabled
- [ ] Test on different user roles
- [ ] Verify database queries are optimized

### Code Standards Checklist
- [ ] No hardcoded values (use constants or options)
- [ ] All strings translatable: `__('text', 'mobility-trailblazers')`
- [ ] SQL queries use `$wpdb->prepare()`
- [ ] AJAX calls include nonce verification
- [ ] JavaScript uses proper jQuery no-conflict mode
- [ ] CSS classes prefixed with `mt-`

## 📚 DOCUMENTATION REQUIREMENTS

### Files to Update After Changes
Whenever relevant update these files in `/doc/`:
- **changelog.md**: Add entry with version, date, and changes
- **general_index.md**: Update if files added/modified
- **mt-developer-guide.md**: Update implementation details
- Create specific guides for new features
- Suggest a commit title and description do not commit yourself

### Important Documentation Files
- **Main Plugin**: `mobility-trailblazers.php`
- **Architecture**: `/doc/mt-architecture-docs.md`
- **Changelog**: `/doc/mt-changelog-updated.md`
- **Index**: `/doc/general_index.md`

## 🎨 BRAND & STYLING

### Brand Colors
- **Primary**: #26a69a
- **Success**: #4caf50
- **Warning**: #ff9800
- **Error**: #f44336

## 🔄 EDIT PREFERENCES

### Code Editing Guidelines
- Use existing files and file structure when possible
- Indicate code edits for cursor tracking
- Prefer file modifications over complete rewrites
- Maintain existing feature set unless explicitly requested otherwise