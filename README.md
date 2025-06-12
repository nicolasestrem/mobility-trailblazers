# Mobility Trailblazers Award System

A comprehensive WordPress plugin for managing the "25 Mobility Trailblazers in 25" award system, designed for the Institut für Mobilität at the University of St. Gallen.

## Overview

This plugin provides a complete award management system featuring:

- **Candidate Management**: Track mobility innovators across three dimensions (Established Companies, Start-ups & New Makers, Infrastructure/Politics/Public Companies)
- **Jury System**: Expert evaluation with structured scoring criteria
- **Public Voting**: Community engagement through public voting
- **Award Ceremonies**: Manage the selection process from 2000 → 200 → 50 → 25 finalists
- **Media Integration**: Partnership support with Handelsblatt and other media outlets
- **Multi-phase Process**: From preparation to post-award communication

## Features

### Core Functionality
- **Custom Post Types**: Candidates, Jury Members, Awards
- **Custom Taxonomies**: Categories, Award Years, Selection Status
- **Evaluation System**: 5-criteria scoring (Courage, Innovation, Implementation, Mobility Relevance, Visibility)
- **Voting Management**: Both jury and public voting systems
- **Results Dashboard**: Real-time voting statistics and rankings
- **Export Capabilities**: CSV export for candidate data and results

### User Interfaces
- **Admin Dashboard**: Comprehensive management interface
- **Jury Portal**: Dedicated evaluation interface for jury members
- **Public Interface**: Candidate showcases and voting forms
- **REST API**: For external integrations and mobile apps

### Content Management
- **Shortcodes**: Easy content embedding with `[mt_candidate_grid]`, `[mt_jury_members]`, `[mt_voting_form]`, `[mt_voting_results]`
- **Widgets**: Voting statistics widget
- **Responsive Design**: Mobile-optimized interfaces
- **Accessibility**: WCAG 2.1 AA compliant

## Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Recommended: 2GB RAM, SSD storage

### Docker Installation (Recommended)
Your existing Docker setup is perfect for this plugin:

1. **Upload Plugin Files**:
   ```bash
   # Copy plugin files to your WordPress installation
   cp -r mobility-trailblazers/ /mnt/dietpi_userdata/docker-files/mobility-trailblazers/wp-content/plugins/
   ```

2. **Set Permissions**:
   ```bash
   sudo chown -R 33:33 /mnt/dietpi_userdata/docker-files/mobility-trailblazers/wp-content/plugins/mobility-trailblazers/
   sudo chmod -R 755 /mnt/dietpi_userdata/docker-files/mobility-trailblazers/wp-content/plugins/mobility-trailblazers/
   ```

3. **Activate Plugin**:
   - Access your WordPress admin at `http://your-domain:9090/wp-admin`
   - Go to Plugins → Installed Plugins
   - Activate "Mobility Trailblazers Award System"

### Manual Installation
1. Download the plugin files
2. Upload to `/wp-content/plugins/mobility-trailblazers/`
3. Activate through the WordPress admin

## Configuration

### Initial Setup

1. **Access Plugin Settings**:
   - Go to `MT Award System → Settings` in WordPress admin
   - Configure basic settings:
     - Award Year: 2025
     - Current Phase: Preparation
     - Enable voting options as needed

2. **Create Default Content**:
   ```php
   // The plugin automatically creates default categories and statuses
   // Categories: Established Companies, Start-ups & New Makers, Infrastructure/Politics/Public
   // Statuses: Longlist (~200), Shortlist (50), Finalist (25), Winner (Top 3), Rejected
   ```

3. **Setup Jury Members**:
   - Go to `MT Award System → Jury Members → Add New`
   - Add jury member details including expertise areas
   - Mark President and Vice President roles as needed

### Database Schema

The plugin creates these custom tables:

```sql
-- Jury voting scores
wp_mt_candidate_scores (
    candidate_id, jury_member_id, 
    courage_score, innovation_score, implementation_score, 
    mobility_relevance_score, visibility_score, 
    total_score, evaluation_round
)

-- Public voting
wp_mt_public_votes (
    candidate_id, voter_email, voter_ip, vote_date
)

-- Detailed jury votes (if needed)
wp_mt_votes (
    candidate_id, jury_member_id, vote_round, 
    rating, comments, vote_date
)
```

## Usage Guide

### Phase 1: Candidate Collection (Mid-May - July 2025)

1. **Add Candidates**:
   ```php
   // Via admin interface
   MT Award System → Candidates → Add New
   
   // Or programmatically
   $candidate_id = wp_insert_post([
       'post_type' => 'mt_candidate',
       'post_title' => 'Candidate Name',
       'post_content' => 'Candidate description...',
       'post_status' => 'publish'
   ]);
   
   // Add metadata
   update_post_meta($candidate_id, '_mt_company', 'Company Name');
   update_post_meta($candidate_id, '_mt_position', 'CEO');
   update_post_meta($candidate_id, '_mt_innovation_description', 'Innovation details...');
   ```

2. **Assign Categories**:
   ```php
   wp_set_post_terms($candidate_id, ['established-companies'], 'mt_category');
   wp_set_post_terms($candidate_id, ['longlist'], 'mt_status');
   wp_set_post_terms($candidate_id, ['2025'], 'mt_award_year');
   ```

### Phase 2: Jury Evaluation (July - August 2025)

1. **Setup Jury Access**:
   - Create WordPress users for jury members
   - Link jury member posts to user accounts via email
   - Set current phase to "Jury Evaluation" in settings

2. **Jury Evaluation Process**:
   - Jury members access `MT Award System → Jury Evaluation`
   - Each candidate scored on 5 criteria (1-10 scale)
   - Automatic calculation of total scores
   - Progress tracking per jury member

### Phase 3: Public Voting (September - October 2025)

1. **Enable Public Voting**:
   ```php
   update_option('mt_public_voting_enabled', true);
   update_option('mt_current_phase', 'public_voting');
   ```

2. **Display Candidates**:
   ```html
   <!-- Show all finalists -->
   [mt_candidate_grid status="finalist" show_voting="true"]
   
   <!-- Show by category -->
   [mt_candidate_grid category="startups-new-makers" status="finalist"]
   
   <!-- Show jury members -->
   [mt_jury_members show_bio="true"]
   ```

3. **Voting Forms**:
   ```html
   <!-- Individual candidate voting -->
   [mt_voting_form candidate_id="123" type="public"]
   
   <!-- Voting results -->
   [mt_voting_results type="public" limit="25"]
   [mt_voting_results type="jury" limit="25"]
   ```

### Phase 4: Award Ceremony (October 30, 2025)

1. **Final Selection**:
   - Review voting results in `MT Award System → Voting Results`
   - Update candidate statuses to "winner" for top 3
   - Generate final reports

2. **Event Management**:
   - Export candidate data for booklet production
   - Prepare award materials
   - Coordinate with Smart Mobility Summit

## Shortcodes Reference

### Candidate Grid
```html
[mt_candidate_grid 
    category="established-companies" 
    status="finalist" 
    limit="25" 
    show_voting="true"]
```

**Parameters**:
- `category`: Filter by category slug
- `status`: Filter by selection status
- `limit`: Number of candidates to show
- `show_voting`: Enable voting forms

### Jury Members
```html
[mt_jury_members limit="20" show_bio="true"]
```

**Parameters**:
- `limit`: Number of jury members (-1 for all)
- `show_bio`: Show biography excerpts

### Voting Form
```html
[mt_voting_form candidate_id="123" type="public"]
```

**Parameters**:
- `candidate_id`: Specific candidate ID
- `type`: "public" or "jury"

### Voting Results
```html
[mt_voting_results type="public" limit="10"]
```

**Parameters**:
- `type`: "public" or "jury"
- `limit`: Number of results to show

## REST API Endpoints

### Get Candidates
```http
GET /wp-json/mobility-trailblazers/v1/candidates
```

**Parameters**:
- `category`: Filter by category
- `status`: Filter by status
- `per_page`: Number of results

### Get Voting Results
```http
GET /wp-json/mobility-trailblazers/v1/results?type=public&limit=10
```

### Submit Vote
```http
POST /wp-json/mobility-trailblazers/v1/vote
Content-Type: application/json

{
    "candidate_id": 123,
    "voter_email": "voter@example.com"
}
```

## Customization

### Theme Integration

Add this to your theme's `functions.php`:

```php
// Custom candidate display
function custom_candidate_display($candidate_id) {
    $company = get_post_meta($candidate_id, '_mt_company', true);
    $innovation = get_post_meta($candidate_id, '_mt_innovation_description', true);
    
    return "<div class='custom-candidate'>
        <h3>" . get_the_title($candidate_id) . "</h3>
        <p><strong>$company</strong></p>
        <p>$innovation</p>
    </div>";
}

// Add custom evaluation criteria
add_filter('mt_evaluation_criteria', function($criteria) {
    $criteria['sustainability_score'] = __('Sustainability Impact');
    return $criteria;
});
```

### Custom Styling

Override default styles in your theme:

```css
/* Custom candidate cards */
.mt-candidate-card {
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

/* Brand colors */
.mt-candidate-card h3 {
    color: #your-brand-color;
}
```

### Email Notifications

Setup custom notifications:

```php
// Custom jury notification
add_action('mt_candidate_added', function($candidate_id) {
    $jury_emails = get_jury_member_emails();
    foreach($jury_emails as $email) {
        mt_send_jury_notification(
            $email,
            'New Candidate Added',
            'A new candidate has been added for evaluation.'
        );
    }
});
```

## Performance Optimization

### Caching
The plugin supports:
- **Object Caching**: Redis integration (already configured in your Docker setup)
- **Database Queries**: Optimized with proper indexing
- **Image Loading**: Lazy loading for candidate photos

### Optimization Tips
1. **Enable Redis**: Already configured in your setup
2. **Image Optimization**: Use WebP format for candidate photos
3. **CDN**: Consider CloudFlare for static assets
4. **Database**: Regular optimization of voting tables

## Security Features

### Data Protection
- **Email Validation**: All voting emails validated
- **Rate Limiting**: Prevents spam voting
- **Nonce Verification**: All AJAX requests secured
- **SQL Injection Protection**: Prepared statements used throughout

### GDPR Compliance
- **Cookie Consent**: Built-in consent management
- **Data Export**: User data export capabilities
- **Right to Deletion**: Automated data removal options

## Monitoring & Analytics

### Dashboard Metrics
- Total candidates by category
- Voting participation rates
- Jury evaluation progress
- Geographic distribution of votes

### Export Capabilities
```php
// Export all candidates
wp_admin_url('admin-post.php?action=mt_export_candidates');

// Export voting results
wp_admin_url('admin-post.php?action=mt_export_voting_results');
```

## Troubleshooting

### Common Issues

1. **Plugin Activation Fails**:
   ```bash
   # Check PHP memory limit
   php -i | grep memory_limit
   
   # Increase if needed in wp-config.php
   ini_set('memory_limit', '512M');
   ```

2. **Database Tables Not Created**:
   ```php
   // Manually trigger table creation
   deactivate_plugins('mobility-trailblazers/mobility-trailblazers.php');
   activate_plugin('mobility-trailblazers/mobility-trailblazers.php');
   ```

3. **Voting Not Working**:
   - Check nonce verification
   - Verify AJAX URL configuration
   - Ensure voting is enabled in settings

### Debug Mode
Enable debugging in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('MOBILITY_TRAILBLAZERS_DEBUG', true);
```

## Support & Documentation

### Resources
- **Plugin Documentation**: `/wp-admin/admin.php?page=mt-documentation`
- **API Documentation**: Built-in endpoint documentation
- **Video Tutorials**: Available in admin dashboard

### Getting Help
1. Check the built-in help tabs in admin pages
2. Review error logs in `wp-content/debug.log`
3. Use the plugin's diagnostic tools in settings

## Roadmap

### 2025 Goals
- [x] Core award system implementation
- [x] Jury evaluation interface
- [x] Public voting system
- [ ] Mobile app integration
- [ ] Advanced analytics dashboard
- [ ] Multi-language support (DE/EN)

### Future Enhancements
- AI-powered candidate matching
- Blockchain-based voting verification
- Social media integration
- Live streaming integration for ceremonies

## License

This plugin is released under the GPL v2 or later license, compatible with WordPress licensing requirements.

---

**Mobility Trailblazers Award System v1.0.0**  
*Institut für Mobilität, Universität St. Gallen*  
*June 2025*