# Documentation Hub - Mobility Trailblazers Plugin

Welcome to the documentation hub for the Mobility Trailblazers WordPress Plugin (v4.1.0+). This directory contains all technical documentation for developers, administrators, and contributors.

## Quick Navigation

### 📚 Core Documentation

| Document | Description | Last Updated |
|----------|-------------|--------------|
| [Architecture Guide](architecture.md) | Complete system architecture and design patterns | August 22, 2025 |
| [Developer Guide](developer-guide.md) | Development environment setup and coding patterns | August 23, 2025 |
| [CSS Guide](css-guide.md) | Frontend styling system and component architecture | August 22, 2025 |
| [CSS v4 Framework Guide](CSS-V4-GUIDE.md) | Modern token-based CSS architecture with mobile-first design | August 23, 2025 |
| [Import/Export Guide](import-export.md) | Data management, import/export, and migration procedures | August 22, 2025 |

### 📋 Reference Documentation

| Document | Description | Last Updated |
|----------|-------------|--------------|
| [API Reference](API-REFERENCE.md) | Complete API documentation | August 20, 2025 |
| [Dependency Injection Guide](DEPENDENCY-INJECTION-GUIDE.md) | DI container usage and patterns | August 20, 2025 |
| [Testing Strategies](TESTING-STRATEGIES.md) | Testing patterns and best practices | August 20, 2025 |
| [Migration Guide](MIGRATION-GUIDE.md) | Version migration procedures | August 20, 2025 |

### 📖 Specialized Guides

| Document | Description | Last Updated |
|----------|-------------|--------------|
| [Email Guide](EMAIL-GUIDE.md) | Email system documentation | August 20, 2025 |
| [Jury Handbook 2025](jury-handbook-2025.md) | Jury member documentation | August 20, 2025 |
| [Localization Fix Summary](localization-fix-summary.md) | Translation fixes | August 20, 2025 |

### 🗂️ Archives

| Directory | Contents |
|-----------|----------|
| [Fixes Archive](archive/fixes-archive-index.md) | Historical documentation of implemented fixes organized by category |
| [Implementation Archive](archive/implementations/) | Past implementation documentation |
| [Reports](report/) | Production reports and deployment documentation |

### 📋 Specifications (Technical Reference)

The `specs/` directory contains detailed technical specifications (30+ documents) covering every aspect of the system. See [specs/README.md](specs/README.md) for the complete index.

## Optimized Documentation Structure

```
doc/
├── README.md                       # This file - navigation hub
├── architecture.md                 # System architecture (consolidated)
├── developer-guide.md              # Development guide (streamlined)
├── css-guide.md                    # CSS system guide (consolidated)
├── import-export.md                # Import/export guide (consolidated)
├── API-REFERENCE.md               # API documentation
├── DEPENDENCY-INJECTION-GUIDE.md # DI container guide
├── TESTING-STRATEGIES.md         # Testing documentation
├── MIGRATION-GUIDE.md             # Migration procedures
├── EMAIL-GUIDE.md                 # Email system
├── jury-handbook-2025.md          # Jury documentation
├── localization-fix-summary.md    # Translation fixes
├── specs/                         # Technical specifications (30+ files)
│   └── README.md                  # Specs navigation
├── archive/                       # Historical documentation
│   ├── fixes-archive-index.md    # Archive navigation
│   ├── implementations/          # Past implementations
│   ├── css-fixes/                # CSS-related fixes
│   ├── performance-fixes/        # Performance optimizations
│   ├── ui-fixes/                 # UI improvements
│   └── system-fixes/             # System configurations
└── report/                        # Production reports
```

## Quick Start for Developers

1. **New to the project?** Start with the [Architecture Guide](architecture.md)
2. **Setting up development?** See the [Developer Guide](developer-guide.md#development-environment-setup)
3. **Working with styles?** Check the [CSS Guide](css-guide.md)
4. **Working with data?** See the [Import/Export Guide](import-export.md)
5. **Troubleshooting issues?** Check the [Developer Guide Troubleshooting](developer-guide.md#troubleshooting)
6. **Looking for historical fixes?** Browse the [Fixes Archive](archive/fixes-archive-index.md)

## Key Resources

### Environment URLs
- **Production:** https://mobilitytrailblazers.de/vote/
- **Staging:** http://localhost:8080/
- **Local Dev:** http://localhost/

### Important Dates
- **Platform Launch:** August 18, 2025
- **Award Ceremony:** October 30, 2025

### Version Information
- **Current Version:** 4.1.0
- **PHP Requirement:** 7.4+
- **WordPress:** 5.8+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **CSS Framework:** v4.1.0 (Mobile-First)

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

*Last Updated: August 23, 2025*  
*Plugin Version: 4.1.0*