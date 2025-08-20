# Documentation Hub - Mobility Trailblazers Plugin

Welcome to the documentation hub for the Mobility Trailblazers WordPress Plugin (v2.5.37). This directory contains all technical documentation for developers, administrators, and contributors.

## Quick Navigation

### 📚 Core Documentation

| Document | Description | Last Updated |
|----------|-------------|--------------|
| [Developer Guide](developer-guide.md) | Comprehensive technical reference for plugin development | August 20, 2025 |
| [Import/Export Guide](IMPORT-EXPORT-GUIDE.md) | Complete guide for data import, export, and migration | August 20, 2025 |
| [Candidate Import System](candidate-import-system.md) | Technical architecture of the candidate import system | August 2025 |
| [Changelog](CHANGELOG.md) | Version history and release notes | August 20, 2025 |

### 🗂️ Archives

| Directory | Contents |
|-----------|----------|
| [Fixes Archive](archive/fixes-archive-index.md) | Historical documentation of implemented fixes organized by category |
| [Reports](report/) | Production reports and deployment documentation |

## Documentation Structure

```
doc/
├── README.md                       # This file - navigation hub
├── developer-guide.md              # Technical development guide
├── IMPORT-EXPORT-GUIDE.md         # Import/export procedures
├── candidate-import-system.md     # Import system architecture
├── CHANGELOG.md                   # Version history
├── archive/                       # Historical fixes documentation
│   ├── fixes-archive-index.md    # Archive navigation
│   ├── css-fixes/                # CSS-related fixes
│   ├── performance-fixes/        # Performance optimizations
│   ├── ui-fixes/                 # UI improvements
│   └── system-fixes/             # System configurations
└── report/                        # Production reports
```

## Quick Start for Developers

1. **New to the project?** Start with the [Developer Guide](developer-guide.md)
2. **Working with imports?** See the [Import/Export Guide](IMPORT-EXPORT-GUIDE.md)
3. **Troubleshooting issues?** Check the [Developer Guide Troubleshooting](developer-guide.md#troubleshooting) section
4. **Looking for historical fixes?** Browse the [Fixes Archive](archive/fixes-archive-index.md)

## Key Resources

### Environment URLs
- **Production:** https://mobilitytrailblazers.de/vote/
- **Staging:** http://localhost:8080/
- **Local Dev:** http://localhost/

### Important Dates
- **Platform Launch:** August 18, 2025
- **Award Ceremony:** October 30, 2025

### Version Information
- **Current Version:** 2.5.37
- **PHP Requirement:** 7.4+
- **WordPress:** 5.8+
- **Database:** MySQL 5.7+ / MariaDB 10.3+

## Documentation Guidelines

### When Adding New Documentation

1. **Core Guides** - Update existing guides rather than creating new files
2. **Fixes** - Document in the appropriate archive subdirectory
3. **Reports** - Place in the `/report/` directory
4. **Naming Convention** - Use lowercase with hyphens (e.g., `feature-name.md`)

### Documentation Standards

- Use Markdown format (.md)
- Include date in filename for time-sensitive docs
- Add table of contents for documents > 500 lines
- Cross-reference related documentation
- Update this README when adding major documentation

## Maintenance

### Recent Consolidation (August 20, 2025)

This documentation structure was reorganized to improve maintainability:
- **Reduced from 21 to 5 main files** (75% reduction)
- **Archived 11 historical fix documents** for reference
- **Consolidated duplicate content** into core guides
- **Created clear navigation** structure

### Regular Updates

- **Weekly:** Update CHANGELOG.md with new versions
- **Per Feature:** Update relevant guide sections
- **Monthly:** Review and archive outdated fixes
- **Quarterly:** Full documentation review

## Getting Help

### For Developers
1. Check the [Developer Guide](developer-guide.md)
2. Review [Troubleshooting](developer-guide.md#troubleshooting)
3. Browse [Fixes Archive](archive/fixes-archive-index.md) for similar issues

### For Import/Export Issues
1. See [Import/Export Guide](IMPORT-EXPORT-GUIDE.md)
2. Check [Migration Procedures](IMPORT-EXPORT-GUIDE.md#migration-procedures)
3. Review [Troubleshooting](IMPORT-EXPORT-GUIDE.md#troubleshooting)

### Debug Resources
- **Debug Center:** Admin → MT Award System → Debug Center
- **Error Logs:** `/wp-content/debug.log`
- **Database Health:** `wp eval "MobilityTrailblazers\Utilities\MT_Database_Health::check_health();"`

## Contributing

When contributing documentation:
1. Follow existing format and structure
2. Update relevant sections rather than creating new files
3. Include code examples where appropriate
4. Test all commands and code snippets
5. Update this README if adding new sections

---

*Last Updated: August 20, 2025*  
*Plugin Version: 2.5.37*