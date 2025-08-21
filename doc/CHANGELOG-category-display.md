## Category Display Update - 2025-08-21

### Changes Made:
1. Updated jury-evaluation-form.php template to display candidate categories using the new _mt_category_type meta field
2. Added CSS styling to ensure proper alignment and display of meta items (Organization, Position, Category)
3. Fixed text truncation issue to show full text for all meta fields
4. Ensured responsive design for mobile and tablet views

### Files Modified:
- templates/frontend/jury-evaluation-form.php - Updated to use _mt_category_type meta field instead of taxonomy
- assets/css/frontend.css - Added styling for equal-width meta items with proper text wrapping

### Technical Details:
- Categories now pulled from post meta field '_mt_category_type' (new 3-category system)
- Meta items displayed with min-width: 250px and max-width: 350px for optimal readability
- Text wrapping enabled to prevent truncation of longer category names

