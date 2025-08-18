# Candidate Content Editor Guide

## Overview
The Candidate Content Editor feature (v2.5.21) provides administrators with a comprehensive interface to edit candidate profile content sections directly from the WordPress admin area.

## Features

### 1. Meta Box Editing
When editing a candidate post, you'll find three new meta boxes:

#### Innovation Summary
- Contains the main overview/summary of the candidate's innovation
- Supports rich text formatting
- Displayed at the top of the candidate profile

#### Evaluation Criteria
- Contains structured evaluation content with 5 standard criteria:
  - **Mut & Pioniergeist:** (Courage & Pioneer Spirit)
  - **Innovationsgrad:** (Innovation Degree)
  - **Umsetzungskraft & Wirkung:** (Implementation & Impact)
  - **Relevanz für die Mobilitätswende:** (Relevance for Mobility Transformation)
  - **Vorbildfunktion & Sichtbarkeit:** (Role Model & Visibility)
- Helper text provided for consistency
- Use **bold** markdown for headers

#### Biography
- Personal background and story of the candidate
- Supports rich text and media
- Displayed in the Biography section

### 2. Inline Editing Modal
From the candidates list page (`/wp-admin/edit.php?post_type=mt_candidate`):
- Each candidate row has an "Edit Content" button
- Click to open a modal with tabbed interface
- Switch between Innovation Summary, Evaluation Criteria, and Biography
- Save changes via AJAX without page reload

### 3. Visual Editor Support
All content areas support:
- Bold, italic, underline formatting
- Lists (bulleted and numbered)
- Links
- Blockquotes
- Text alignment
- Media insertion (where applicable)

## How to Use

### Editing from Post Editor
1. Navigate to **Candidates** in the admin menu
2. Click **Edit** on any candidate
3. Scroll down to find the three content meta boxes
4. Edit content using the visual editor
5. Click **Update** to save changes

### Quick Editing from List View
1. Navigate to **Candidates** list
2. Click **Edit Content** button next to candidate name
3. Modal opens with three tabs
4. Select the tab for the content you want to edit
5. Make changes in the textarea
6. Click **Save Changes**
7. Modal shows "Saved!" confirmation

## Technical Information

### Database Storage
Content is stored in WordPress post meta:
- `_mt_overview` - Innovation Summary content
- `_mt_evaluation_criteria` - Evaluation Criteria content
- `_mt_personality` - Biography content

### Security
- All updates require `edit_posts` capability
- Nonce verification on all AJAX requests
- Content sanitized with `wp_kses_post()`

### File Structure
```
includes/admin/
└── class-mt-candidate-editor.php   # Main editor class

assets/js/
└── candidate-editor.js              # JavaScript for inline editing
```

### Hooks and Filters
The feature integrates with WordPress using:
- `add_meta_boxes` - Registers meta boxes
- `save_post_mt_candidate` - Saves meta data
- `wp_ajax_mt_update_candidate_content` - AJAX update handler
- `wp_ajax_mt_get_candidate_content` - AJAX content retrieval

## Content Guidelines

### Evaluation Criteria Format
For consistency, use this format:

```
**Mut & Pioniergeist:**
[Description of courage and pioneering aspects]

**Innovationsgrad:**
[Description of innovation level]

**Umsetzungskraft & Wirkung:**
[Description of implementation and impact]

**Relevanz für die Mobilitätswende:**
[Description of relevance to mobility transformation]

**Vorbildfunktion & Sichtbarkeit:**
[Description of role model function and visibility]
```

### Best Practices
1. Keep content concise and focused
2. Use consistent formatting across all candidates
3. Include specific examples and achievements
4. Maintain professional, objective tone
5. Update content regularly as candidates progress

## Troubleshooting

### Content Not Saving
- Check user permissions (need `edit_posts` capability)
- Verify JavaScript console for errors
- Ensure proper nonce is being sent

### Modal Not Opening
- Clear browser cache
- Check for JavaScript conflicts
- Verify candidate-editor.js is loaded

### Formatting Lost
- Use the visual editor for rich formatting
- Avoid switching between Text/Visual tabs unnecessarily
- Check that content is properly escaped in templates

## Future Enhancements
Potential improvements for future versions:
- Bulk editing capabilities
- Content templates
- Revision history
- Import/export functionality
- Multi-language support

## Support
For issues or questions about the Candidate Content Editor:
1. Check the JavaScript console for errors
2. Verify file permissions on the server
3. Review the changelog for recent updates
4. Contact the development team with specific error messages