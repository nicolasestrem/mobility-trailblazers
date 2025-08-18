# Mobility Trailblazers Plugin

**Location**: `E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers`  
**Purpose**: DACH region mobility innovators award platform
**PRODUCTION PLUGIN**: /public_html/vote/wp-content/plugins/mobility-trailblazers
**PRODUCTION URL**: https://mobilitytrailblazers.de/vote/
**STAGING**: http://localhost:8080/

## 🚨 CRITICAL RULES
- NEVER remove features without asking
- ALWAYS verify nonces in AJAX: `wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')`
- ALWAYS check existing code first: `grep -r "MT_" includes/`
- NEVER use --no-verify commits

## 📁 STRUCTURE
```
includes/
├── core/         # MT_Plugin, MT_Activator
├── admin/        # Dashboards
├── ajax/         # AJAX (verify nonces!)
├── repositories/ # Data layer
├── services/     # Business logic
└── widgets/      # Elementor
```

## 🔧 CONVENTIONS
- **Classes**: `MT_Assignment_Service`
- **Files**: `class-mt-assignment-service.php`
- **Tables**: `wp_mt_assignments`
- **CSS**: `.mt-assignment__header`
- **Text Domain**: `'mobility-trailblazers'`

## 📊 DATABASE
- `wp_mt_evaluations`: criterion_1-5, comments, status, jury_member_id
- `wp_mt_assignments`: jury_member_id, candidate_id, status
- `wp_posts`: post_type='mt_candidate'

## ✅ SECURITY CHECKLIST
- Sanitize inputs: `sanitize_text_field()`, `wp_kses_post()`
- Escape outputs: `esc_html()`, `esc_url()`, `esc_attr()`
- Verify nonces & capabilities: `current_user_can()`
- SQL: `$wpdb->prepare()`
- Translatable strings: `__('text', 'mobility-trailblazers')`
- CSS prefix: `mt-`

## 📚 DOCS TO UPDATE
- `/doc/changelog.md` - Version & changes
- `/doc/general_index.md` - File updates
- `/doc/mt-developer-guide.md` - Implementation details
- Suggest commit message (don't commit)

## 🎨 COLORS
Primary: #26a69a | Success: #4caf50 | Warning: #ff9800 | Error: #f44336

## 💡 WORKFLOW
1. Check existing code
2. Follow Repository-Service-Controller pattern
3. Test with WP_DEBUG & different MCP servers
4. Update production with FTP MCP
5. Update docs
6. Commit and PR

**Key Commands**: `wp transient delete --all` | `wp db check` | `tail -f wp-content/debug.log`