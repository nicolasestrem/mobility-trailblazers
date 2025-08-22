# Color Token Consolidation Plan

## Current State
As of 2025-08-21, the Mobility Trailblazers plugin has inconsistent color token definitions across multiple CSS files.

## Color Value Conflicts

### Primary Color
- **mt-variables.css**: `#003C3D` (Dark Petrol)
- **candidate-enhanced-v2.css**: `#004C5F` (Dark Indigo)
- **frontend.css**: `#003C3D` (Dark Petrol)

### Secondary Color  
- **mt-variables.css**: `#004C5F` (Dark Indigo)
- **candidate-enhanced-v2.css**: `#00ACC1` (Turquoise)

### Accent Color
- Consistent across files: `#C1693C` (Copper)

## Files Using Color Tokens

### Core Variable Files
1. `assets/css/mt-variables.css` - Main variable definitions
2. `assets/css/v3/mt-tokens.css` - V3 token system

### Component CSS Files
1. `assets/css/candidate-enhanced-v2.css` - Override with different values
2. `assets/css/frontend.css` - Uses correct values
3. `assets/css/admin.css` - References variables
4. `assets/css/enhanced-candidate-profile.css` - Uses var() references

## Recommended Approach

### Phase 1: Audit (Current)
- [x] Identify all color token definitions
- [x] Document conflicts
- [ ] Determine correct brand colors from design system

### Phase 2: Consolidation
1. **Update mt-variables.css** with final approved colors
2. **Remove duplicate :root definitions** from:
   - candidate-enhanced-v2.css
   - candidate-enhanced-v2-backup.css
   - Any other files with :root overrides

3. **Ensure all files import mt-variables.css** first

### Phase 3: Testing
1. Test each major template:
   - Candidate profiles
   - Jury dashboard
   - Admin interface
   - Evaluation forms

2. Visual regression testing with Kapture MCP

## Current Visual State
- Hero sections use teal/turquoise gradient (#004C5F to #00ACC1)
- CTAs use copper accent (#C1693C)
- Text uses dark petrol (#003C3D)

## Action Items
1. Get design team approval on final color values
2. Update mt-variables.css with approved values
3. Remove all duplicate :root definitions
4. Test thoroughly with visual regression tools
5. Update documentation

## Notes
- The candidate-enhanced-v2.css file is overriding the main variables
- This causes inconsistent colors across different templates
- Need to ensure all CSS files use centralized variables from mt-variables.css