# CLAUDE.md - Mobility Trailblazers WordPress Plugin

**AI Development Guide for Claude Code & Claude Desktop**  
**Version:** 2.2.14  
**Last Updated:** August 2025  
**Local Path:** `C:\Users\nicol\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers` or `E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers` depending on the computer Claude is running on.

## 🎯 Project Overview

You're working on the **Mobility Trailblazers Award Platform** - a WordPress plugin for managing awards recognizing mobility innovation pioneers in the DACH region (Germany, Austria, Switzerland). The platform handles jury evaluations, candidate management, public voting, and award administration.

### Current Status
- **Phase:** Platform Development In Progress
- **Infrastructure:** ✅ Complete (Docker, Database, Security)  
- **Core Features:** ✅ Complete (Evaluation System, Dashboard, Assignments, Voting)
- **Elementor Integration:** ✅ Complete (8 Custom Widgets)
- **Next Focus:** Content population, candidate profiles, event integration

### Business Context
- **Partnership:** Handelsblatt Media Group
- **Event:** Award ceremony October 30, 2025
- **Stakeholders:** 25 jury members, 50+ candidates, media partners
- **Languages:** German (primary), English (secondary)
- **Mission:** "Weil mobiler Wandel Mut braucht" (Because mobility transformation takes courage)

## 🏗️ Technical Architecture

### Stack
- **WordPress:** 5.8+ with modern PHP 7.4+
- **Frontend:** Vanilla JS, AJAX, Responsive CSS Grid
- **Database:** MySQL 5.7+ with custom tables (mt_ prefix)
- **Infrastructure:** Docker containers managed via Komodo
- **Design:** Corporate colors (Teal #00736C, Copper #C27A5E, Beige #F6E8DE)

### Plugin Structure
```
mobility-trailblazers/
├── assets/               # CSS, JS, images
│   ├── css/             # Admin and frontend styles
│   ├── js/              # Modular JavaScript
│   └── images/          # Logos, icons
├── includes/            # PHP classes (PSR-4 autoloading)
│   ├── admin/          # Admin functionality
│   ├── ajax/           # AJAX handlers
│   ├── core/           # Core plugin classes
│   ├── repositories/   # Data access layer
│   ├── services/       # Business logic
│   └── shortcodes/     # Frontend shortcodes
├── templates/           # PHP/HTML templates
│   ├── admin/          # Admin interface templates
│   └── frontend/       # Public-facing templates
├── languages/          # i18n files (de_DE, en_US)
├── doc/               # Technical documentation
└── mobility-trailblazers.php  # Main plugin file
```

## 📋 DEVELOPMENT WORKFLOW

### 1. EXPLORE
Before making any changes:
- Review this CLAUDE.md file completely
- Search for existing implementations using pattern matching
- Review relevant documentation in `/doc/` directory
- Check `/doc/general_index.md` for file overview
- Understand the Repository-Service-Controller architecture
- Check for existing similar features to maintain consistency

```bash
# Search for similar implementations
grep -r "MT_" includes/
grep -r "mt_" templates/
# Check documentation
cat doc/mt-developer-guide.md
cat doc/general_index.md
```

### 2. PLAN
Create a detailed implementation plan that includes:
- Database schema changes (if needed) with `mt_` prefix
- WordPress hooks and filters to use
- Security measures (nonces, capability checks, sanitization)
- Internationalization requirements (`mobility-trailbl