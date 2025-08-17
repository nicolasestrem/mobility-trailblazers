# German Localization Guide
**Version:** 2.5.8  
**Last Updated:** August 17, 2025

## Overview
The Mobility Trailblazers plugin includes complete German localization support for the DACH region market. This guide documents the localization implementation, usage, and maintenance procedures.

## Language Files

### Translation Files Location
```
/languages/
├── mobility-trailblazers-de_DE.po    # German translation source
└── mobility-trailblazers-de_DE.mo    # Compiled German translation
```

### File Details
- **Text Domain:** `mobility-trailblazers`
- **Language Code:** `de_DE`
- **Total Strings:** 1000+ translated strings
- **Coverage:** 100% of user-facing text

## Translation Coverage

### Core Features
✅ **Evaluation System**
- All evaluation criteria labels
- Score descriptions and guidelines
- Submission messages and confirmations
- Draft/submitted status indicators

✅ **Jury Dashboard**
- Welcome messages and instructions
- Progress indicators and statistics
- Navigation elements
- Action buttons and tooltips

✅ **Assignment Management**
- Assignment status labels
- Bulk action descriptions
- Auto-assignment interface
- Manual assignment dialogs

✅ **Admin Interface**
- Menu items and page titles
- Settings descriptions
- Form labels and placeholders
- Help text and tooltips

✅ **Email Templates**
- Notification subjects
- Email body content
- Footer text and signatures

✅ **Error Messages**
- Validation errors
- Security warnings
- System notifications
- Success confirmations

## Implementation Details

### i18n Class
The plugin uses a custom internationalization class (`MT_I18n`) that provides:
- Automatic language detection
- User preference storage
- Language switcher widget
- Environment-aware locale handling

### Language Detection Priority
1. URL parameter (`?mt_lang=de_DE`)
2. User meta preference
3. Browser cookie
4. Site language setting
5. WordPress locale
6. Default to German (`de_DE`)

## Activation Instructions

### Setting WordPress to German
1. **Install German Language Pack:**
```bash
docker exec mobility_wordpress_dev bash -c "
  cd /var/www/html/wp-content/languages && 
  wget https://downloads.wordpress.org/translation/core/6.7.1/de_DE.zip && 
  unzip -o de_DE.zip && 
  rm de_DE.zip && 
  chown -R www-data:www-data .
"
```

2. **Set Site Language:**
```bash
wp option update WPLANG de_DE
```

3. **Clear Cache:**
```bash
wp cache flush
wp transient delete --all
```

### User Language Preference
Users can set their preferred language:
1. Go to User Profile
2. Find "Mobility Trailblazers Language Settings"
3. Select "Deutsch"
4. Save profile

## Translation Guidelines

### String Format
All translatable strings use WordPress i18n functions:
```php
// Simple string
__('Text to translate', 'mobility-trailblazers')

// With context
_x('Post', 'noun', 'mobility-trailblazers')

// Plural forms
_n('%d item', '%d items', $count, 'mobility-trailblazers')

// With variables
sprintf(__('Welcome, %s', 'mobility-trailblazers'), $name)
```

### German Translation Standards
- **Formal Address:** Use "Sie" form throughout
- **Technical Terms:** Keep English terms when commonly used (e.g., "Dashboard", "Widget")
- **Date Format:** DD.MM.YYYY
- **Number Format:** 1.234,56 (dot for thousands, comma for decimals)

## Common Translations

| English | German |
|---------|--------|
| Evaluation | Bewertung |
| Assignment | Zuweisung |
| Jury Member | Jurymitglied |
| Candidate | Kandidat |
| Submit | Absenden |
| Draft | Entwurf |
| Pending | Ausstehend |
| Completed | Abgeschlossen |
| Dashboard | Dashboard |
| Settings | Einstellungen |
| Save | Speichern |
| Cancel | Abbrechen |
| Delete | Löschen |
| Export | Exportieren |
| Import | Importieren |

## Testing Localization

### Manual Testing
1. Switch WordPress to German
2. Navigate through all plugin pages
3. Check for untranslated strings
4. Verify proper formatting
5. Test special characters (ä, ö, ü, ß)

### Automated Testing
```bash
# Check for missing translations
wp i18n make-pot . languages/mobility-trailblazers.pot
diff languages/mobility-trailblazers.pot languages/mobility-trailblazers-de_DE.po

# Validate .po file
msgfmt -c languages/mobility-trailblazers-de_DE.po
```

## Maintenance

### Adding New Translations
1. **Extract strings from code:**
```bash
wp i18n make-pot . languages/mobility-trailblazers.pot
```

2. **Update German .po file:**
```bash
msgmerge -U languages/mobility-trailblazers-de_DE.po languages/mobility-trailblazers.pot
```

3. **Translate new strings** in .po file

4. **Compile to .mo:**
```bash
msgfmt languages/mobility-trailblazers-de_DE.po -o languages/mobility-trailblazers-de_DE.mo
```

### Translation Tools
- **Poedit:** Desktop application for .po file editing
- **Loco Translate:** WordPress plugin for in-admin translation
- **WP-CLI:** Command-line translation management

## Troubleshooting

### Strings Not Translating
1. **Check text domain:** Ensure using `'mobility-trailblazers'`
2. **Clear cache:** Both WordPress and browser cache
3. **Verify .mo file:** Must be compiled from latest .po
4. **Check locale:** Confirm WordPress is set to `de_DE`

### Character Encoding Issues
- Ensure files saved as UTF-8
- Check database charset is `utf8mb4`
- Verify HTTP headers send correct charset

### Missing Translations in Admin
Some WordPress admin strings require core language pack:
```bash
wp language core install de_DE
wp language core activate de_DE
```

## API Reference

### Getting Current Language
```php
$i18n = new MT_I18n();
$current_lang = $i18n->get_current_language(); // Returns 'de_DE'
$lang_code = $i18n->get_current_language_code(); // Returns 'de'
```

### Switching Language Programmatically
```php
// Temporary switch
add_filter('locale', function() {
    return 'de_DE';
});

// User preference
update_user_meta($user_id, 'mt_language_preference', 'de_DE');
```

## Quality Assurance

### Translation Checklist
- [ ] All menu items translated
- [ ] Form labels and placeholders complete
- [ ] Error messages localized
- [ ] Email templates translated
- [ ] JavaScript strings included
- [ ] Date/time formats correct
- [ ] Number formats appropriate
- [ ] Special characters display correctly

### Review Process
1. Native German speaker review
2. Technical terminology verification
3. Consistency check across plugin
4. User testing with target audience

## Support

### Reporting Translation Issues
1. Identify the untranslated string
2. Note the page/context where it appears
3. Check if string exists in .po file
4. Report via GitHub issue with details

### Contributing Translations
1. Fork the repository
2. Update .po file with translations
3. Test thoroughly
4. Submit pull request with changes

---

*This guide ensures proper German localization for the Mobility Trailblazers plugin, providing a native experience for DACH region users.*