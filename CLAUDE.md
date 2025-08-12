# Claude Instructions for Mobility Trailblazers Plugin

## Project Overview
This is a WordPress plugin called "Mobility Trailblazers" that helps users track and manage mobility-related activities and achievements.

## Code Standards
- Follow WordPress coding standards
- Use proper PHP namespacing
- Follow PSR-4 autoloading standards
- Use proper WordPress hooks and filters
- Include proper security measures (nonces, sanitization, validation)
- Comment your code clearly
- Use semantic versioning for releases

## File Structure
- Main plugin file: `mobility-trailblazers.php`
- Classes in `includes/` directory
- Templates in `templates/` directory
- Assets in `assets/` directory
- Language files in `languages/` directory

## WordPress Standards
- Use WordPress-specific functions when available
- Follow WordPress security best practices
- Use proper escaping and sanitization
- Include proper capability checks
- Use WordPress database abstraction layer
- Follow WordPress HTML/CSS guidelines

## Testing
- Always test changes thoroughly
- Consider backward compatibility
- Test with different WordPress versions when possible
- Include unit tests when adding new features

## When Making Changes
- Update version number in main plugin file when making significant changes
- Update changelog in README.md
- Follow semantic versioning (MAJOR.MINOR.PATCH)
- Consider database migration needs
- Update documentation as needed

## Security
- Always validate and sanitize user inputs
- Use nonces for form submissions
- Check user capabilities before performing actions
- Escape output properly
- Follow WordPress security guidelines
