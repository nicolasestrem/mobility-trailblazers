# Mobility Trailblazers Award Platform

A comprehensive WordPress plugin for managing the Mobility Trailblazers award system, handling candidates, jury members, voting processes, and public engagement.

## Overview

The Mobility Trailblazers Award System is a complete solution for managing an award program that recognizes 25 mobility trailblazers. The system facilitates the entire process from candidate management to jury evaluation and public voting.

## Features

- **Candidate Management**
  - Custom post type for candidates
  - Detailed candidate profiles
  - Category-based organization
  - Award year tracking
  - Selection status management

- **Jury System**
  - Jury member management
  - Automated candidate assignment
  - Evaluation interface
  - Voting management
  - Jury notifications

- **Voting System**
  - Public voting interface
  - Jury voting system
  - Real-time vote counting
  - Results display
  - Vote validation and security

- **Admin Features**
  - Dashboard with key metrics
  - Assignment management
  - Export functionality
  - Settings configuration
  - User role management

## Installation

1. Upload the plugin files to `/wp-content/plugins/mobility-trailblazers`
2. Activate the plugin through the WordPress admin interface
3. Configure the plugin settings under the "Mobility Trailblazers" menu
4. Set up initial categories and award years

## Database Setup

The plugin creates several custom database tables during activation:
- Candidate assignments
- Voting records
- Jury evaluations
- System settings

### Setup Scripts

The `setup/` directory contains several important scripts for system initialization:

- `db_init.sql` - Complete database schema initialization
- `minimal_db_init.sql` - Minimal database setup for testing
- `init_script.sh` - Shell script for automated setup
- `jury-import.php` - Jury member import utility
- `fake-candidates-generator.php` - Development tool for generating test candidates

To initialize the database:

1. Run the initialization script:
   ```bash
   cd setup
   ./init_script.sh
   ```

2. Import jury members (optional):
   ```bash
   php jury-import.php
   ```

3. Generate test candidates (development only):
   ```bash
   php fake-candidates-generator.php
   ```

## User Roles and Permissions

The system defines three main user roles:

1. **Public Role**
   - Can view candidates and jury members
   - Can submit public votes
   - Limited to one vote per email address
   - Access to public voting interface

2. **Jury Role**
   - Can evaluate assigned candidates
   - Can submit detailed scores
   - Access to jury evaluation interface
   - Can view candidate details and statistics

3. **Admin Role**
   - Full system access
   - Manage candidates and jury members
   - Configure system settings
   - View and export results
   - Manage assignments

## Database Structure

The plugin uses several custom tables:

1. **mt_mt_votes**
   - Stores jury member votes
   - Tracks vote rounds
   - Includes comments and ratings
   - Timestamps for audit trail

2. **mt_mt_public_votes**
   - Records public voting
   - Tracks voter email and IP
   - Prevents duplicate votes
   - Stores user agent information

3. **mt_mt_candidate_scores**
   - Detailed evaluation scores
   - Five evaluation criteria:
     - Courage & Pioneer Spirit (1-10)
     - Innovation Level (1-10)
     - Implementation & Impact (1-10)
     - Mobility Relevance (1-10)
     - Role Model & Visibility (1-10)
   - Automatic total score calculation
   - Evaluation round tracking

## Shortcodes

The plugin provides several shortcodes for frontend display:

1. **Voting Form**
   ```php
   [mt_voting_form candidate_id="123" type="public"]
   ```
   - Displays voting interface for specific candidate
   - Supports both public and jury voting
   - Includes email validation
   - Real-time feedback

2. **Candidate Grid**
   ```php
   [mt_candidate_grid category="innovation" status="finalist" limit="25" show_voting="true"]
   ```
   - Displays candidates in grid layout
   - Filterable by category and status
   - Configurable limit
   - Optional voting integration

3. **Jury Members**
   ```php
   [mt_jury_members limit="-1" show_bio="true"]
   ```
   - Lists jury members
   - Shows roles (President/Vice President)
   - Displays expertise and company
   - Optional biography display

4. **Voting Results**
   ```php
   [mt_voting_results type="public" limit="10"]
   ```
   - Shows current voting statistics
   - Supports both public and jury results
   - Configurable result limit
   - Real-time updates

## Development

### Directory Structure

```
mobility-trailblazers/
├── assets/           # CSS, JS, and media files
│   ├── css/         # Stylesheets
│   ├── js/          # JavaScript files
│   └── images/      # Media assets
├── setup/           # Installation and setup scripts
├── templates/       # Frontend templates
│   └── assignment-template.php  # Jury assignment interface
└── mobility-trailblazers.php  # Main plugin file
```

### Templates

The plugin uses custom templates for various frontend displays:

- `assignment-template.php`: Handles the jury assignment interface
  - Displays candidate assignments
  - Manages jury member evaluations
  - Shows voting statistics

### Assets

The plugin includes several frontend assets:

- **CSS**
  - Main stylesheet for plugin components
  - Responsive design for mobile devices
  - Custom styling for voting interface

- **JavaScript**
  - AJAX handlers for voting
  - Dynamic content loading
  - Form validation
  - Real-time updates

### Key Components

- **Main Plugin Class**: `MobilityTrailblazersPlugin`
- **Custom Post Types**: Candidates, Jury Members, Awards
- **Custom Taxonomies**: Categories, Award Years, Selection Status
- **Admin Interface**: Dashboard, Settings, Assignment Management
- **Frontend Components**: Voting Forms, Candidate Display, Results

## API Endpoints

The plugin provides REST API endpoints:

1. **Candidates**
   - `GET /wp-json/mt/v1/candidates`
   - Returns list of candidates
   - Supports filtering and pagination
   - Includes detailed candidate information

2. **Voting**
   - `POST /wp-json/mt/v1/vote`
   - Submits votes (public or jury)
   - Validates voter eligibility
   - Returns voting confirmation

3. **Results**
   - `GET /wp-json/mt/v1/results`
   - Returns current voting results
   - Supports different result types
   - Includes detailed statistics

## User Flows

### Public Voting Flow
1. User visits candidate page
2. Views candidate details and achievements
3. Submits vote with email verification
4. Receives confirmation
5. Can view current results

### Jury Evaluation Flow
1. Jury member logs in
2. Views assigned candidates
3. Evaluates each candidate on five criteria
4. Submits detailed scores
5. Can view evaluation statistics

### Admin Management Flow
1. Admin logs in to dashboard
2. Manages candidates and jury members
3. Configures system settings
4. Monitors voting progress
5. Exports results and reports

### Candidate Management Flow
1. Admin adds new candidate
2. Assigns categories and status
3. Manages candidate details
4. Tracks evaluation progress
5. Updates candidate status

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please contact the Mobility Trailblazers team at [support@mobilitytrailblazers.de](mailto:support@mobilitytrailblazers.de)