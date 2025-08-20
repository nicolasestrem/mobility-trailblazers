# Rich Text Editor Implementation

**Version**: 2.5.32  
**Implemented**: January 18, 2025  
**Author**: Claude Code

## Overview

A lightweight, bulletproof rich text editor has been implemented for the candidate content editing modal in the WordPress admin. This replaces the previous plain textarea implementation with a full-featured WYSIWYG editor that provides formatting capabilities while maintaining security and compatibility.

## Features

### Core Functionality

- **ContentEditable-based Editor**: Modern browser implementation with graceful fallback
- **Formatting Toolbar**: Comprehensive set of formatting tools
- **Keyboard Shortcuts**: Standard shortcuts for quick formatting
- **History Management**: Undo/redo with up to 50 states per editor
- **Auto-save**: Automatic content saving every 30 seconds
- **HTML Sanitization**: Double-layer security (client + server)

### Toolbar Components

1. **Text Formatting**
   - Bold (Ctrl+B)
   - Italic (Ctrl+I)
   - Underline (Ctrl+U)

2. **Headings**
   - Dropdown selector for H1, H2, H3, and Normal text

3. **Lists**
   - Bullet lists
   - Numbered lists

4. **Links**
   - Insert link (Ctrl+K)
   - Remove link

5. **Utilities**
   - Clear formatting
   - Undo (Ctrl+Z)
   - Redo (Ctrl+Y)

### Special Features

#### Evaluation Criteria Templates
- Quick-insert button for standard criteria headers
- Pre-formatted with proper styling
- Includes all 5 standard evaluation criteria:
  - Mut & Pioniergeist
  - Innovationsgrad
  - Umsetzungskraft & Wirkung
  - Relevanz für die Mobilitätswende
  - Vorbildfunktion & Sichtbarkeit

#### User Experience
- Character count display
- Visual feedback for active formatting
- Unsaved changes warning
- Responsive design for mobile/tablet
- Dark mode support (follows WordPress admin theme)

## Technical Implementation

### Architecture

```javascript
// Module structure
window.MTRichEditor = {
    config: { /* Configuration options */ },
    editors: { /* Active editor instances */ },
    history: { /* Undo/redo stacks */ },
    historyIndex: { /* Current position in history */ },
    
    // Core methods
    init: function(containerId, options) { },
    executeCommand: function(containerId, command) { },
    sanitizeHTML: function(html) { },
    // ... additional methods
}
```

### Files Structure

```
assets/
├── js/
│   ├── mt-rich-editor.js      # Core editor module
│   └── candidate-editor.js    # Integration layer (modified)
├── css/
│   └── mt-rich-editor.css     # Editor styling
includes/
└── admin/
    └── class-mt-candidate-editor.php  # Backend handler (modified)
```

### Security Implementation

#### Client-side Sanitization
```javascript
sanitizeHTML: function(html) {
    const temp = document.createElement('div');
    temp.innerHTML = html;
    
    // Remove dangerous elements
    temp.querySelectorAll('script, style, meta, link').forEach(el => el.remove());
    
    // Remove dangerous attributes
    temp.querySelectorAll('*').forEach(el => {
        // Keep only safe attributes
        const allowedAttrs = ['href', 'target', 'rel', 'title', 'alt'];
        // ... sanitization logic
    });
    
    return temp.innerHTML;
}
```

#### Server-side Sanitization
```php
// All content is sanitized before saving
$content = wp_kses_post($_POST['content']);
update_post_meta($post_id, $field_map[$field], $content);
```

### Browser Compatibility

- **Full Support**: Chrome 60+, Firefox 55+, Safari 11+, Edge 79+
- **Fallback Mode**: Older browsers receive textarea with markdown hints
- **Feature Detection**: Automatic detection of contentEditable support

## Usage

### For Administrators

1. Navigate to **Candidates** list in WordPress admin
2. Click **Edit Content** button next to any candidate
3. Use the formatting toolbar or keyboard shortcuts
4. Content auto-saves every 30 seconds
5. Click **Save Changes** to save immediately

### For Developers

#### Initializing an Editor
```javascript
MTRichEditor.init('editor-container', {
    content: 'Initial content',
    minHeight: 200,
    maxHeight: 400,
    label: 'Editor Label',
    autosave: true,
    onAutosave: function(content) {
        // Handle auto-save
    }
});
```

#### Getting/Setting Content
```javascript
// Get content
var content = MTRichEditor.getContent('editor-container');

// Set content
MTRichEditor.setContent('editor-container', '<p>New content</p>');

// Destroy editor
MTRichEditor.destroy('editor-container');
```

## Modal Integration

The rich text editor is integrated into the candidate editing modal with three tabs:

1. **Innovation Summary** - General overview content
2. **Evaluation Criteria** - Structured criteria with template support
3. **Biography** - Personal/professional background

Each tab maintains its own editor instance with independent history and auto-save.

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl+B / Cmd+B | Bold |
| Ctrl+I / Cmd+I | Italic |
| Ctrl+U / Cmd+U | Underline |
| Ctrl+K / Cmd+K | Insert Link |
| Ctrl+Z / Cmd+Z | Undo |
| Ctrl+Y / Cmd+Y | Redo |
| Escape | Close Modal |

## Performance Considerations

- **Lightweight**: No external libraries required
- **Lazy Loading**: Editors initialized only when needed
- **Memory Management**: Proper cleanup on modal close
- **History Limits**: Maximum 50 undo states to prevent memory issues

## Troubleshooting

### Editor Not Appearing
1. Check browser console for JavaScript errors
2. Verify files are properly enqueued
3. Ensure browser supports contentEditable

### Formatting Not Saving
1. Check WordPress user permissions
2. Verify nonce is valid
3. Check server-side error logs

### Fallback Mode Active
- Browser doesn't support contentEditable
- Use markdown syntax in textarea:
  - `**bold**` for bold text
  - `*italic*` for italic text
  - `[link text](url)` for links

## Future Enhancements

Potential improvements for future versions:

1. **Image Upload**: Direct image insertion capability
2. **Table Support**: Table creation and editing
3. **Code Blocks**: Syntax-highlighted code insertion
4. **Emoji Picker**: Emoji insertion panel
5. **Find & Replace**: Search and replace within content
6. **Export Options**: Export to PDF/Word formats
7. **Collaborative Editing**: Real-time multi-user editing
8. **AI Assistance**: Content suggestions and improvements

## Related Documentation

- [Developer Guide](developer-guide.md) - Overall plugin architecture
- [CHANGELOG](CHANGELOG.md) - Version history and updates

## Support

For issues or questions regarding the Rich Text Editor:
1. Check this documentation
2. Review browser console for errors
3. Contact development team with specific error messages