# Mobility Trailblazers Award Platform

> 🏆 **25 Mobility Trailblazers in 25** - Recognizing courage and innovation in mobility transformation across the DACH region

## Overview

The Mobility Trailblazers platform is a comprehensive WordPress-based award system designed to identify, evaluate, and celebrate the top 25 individuals driving mobility transformation in Germany, Austria, and Switzerland (DACH region). This platform serves as both a public communication hub and a secure jury evaluation system.

**Key Mission**: "Weil mobiler Wandel Mut braucht" (Because mobility transformation requires courage)

## 🎯 Project Goals

1. **Individual Recognition** - Honor 25 makers and shapers transforming mobility
2. **Innovation Showcase** - Highlight courageous innovations strengthening DACH's mobility competitiveness  
3. **Transformation Visibility** - Demonstrate the scope of mobility transformation achievements

## 🛠 Technical Stack

- **WordPress 6.4** with PHP 8.2
- **MySQL 8.0** database
- **Redis** for caching
- **Cloudflare Tunnel** for secure access
- **Docker** containerization with Komodo management
- **Custom WordPress Plugin** for award management

## 🚀 Quick Start

### Prerequisites

- Docker and Docker Compose
- 4GB+ RAM recommended
- Port availability: 9080, 9306, 9379, 9081

### Installation

1. Clone the repository:
```bash
git clone https://github.com/your-org/mobility-trailblazers.git
cd mobility-trailblazers
```

2. Start the Docker stack:
```bash
docker-compose up -d
```

3. Access the platform:
- WordPress: http://localhost:9080
- phpMyAdmin: http://localhost:9081

4. Run initial setup:
```bash
# Install WordPress
docker exec mobility_wpcli wp core install \
  --url='http://localhost:9080' \
  --title='Mobility Trailblazers' \
  --admin_user='admin' \
  --admin_password='secure_password' \
  --admin_email='admin@mobility-trailblazers.de'

# Activate the plugin
docker exec mobility_wpcli wp plugin activate mobility-trailblazers
```

5. Import jury members:
```bash
docker exec mobility_wpcli wp eval-file wp-content/jury-import.php
```

## 📋 Features

### Award Management System
- **Three-phase selection process**: 2000 → 200 → 50 → 25 candidates
- **Category-based evaluation**:
  - Established Companies
  - Start-ups & New Makers
  - Infrastructure/Politics/Public Companies

### Jury Evaluation Platform
- Secure jury member portal with role-based access
- Five evaluation criteria scoring (1-10 scale):
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)

### Assignment Management
- Advanced assignment interface for jury-candidate allocation
- Auto-assignment algorithms with multiple distribution strategies
- Real-time progress tracking and analytics
- Export functionality for assignments and evaluations

## 📝 Shortcodes Documentation

### [mt_candidate_grid]
Displays a responsive grid of candidates with filtering options.

**Parameters:**
- `category` (string) - Filter by specific category slug
- `status` (string) - Filter by status: 'longlist', 'shortlist', 'finalist', 'winner'
- `year` (string) - Filter by award year (default: current year)
- `columns` (int) - Number of columns: 2, 3, or 4 (default: 3)
- `show_filters` (bool) - Display category filter buttons (default: true)
- `show_status` (bool) - Show candidate status badges (default: true)
- `orderby` (string) - Order by: 'name', 'score', 'random' (default: 'name')
- `limit` (int) - Maximum number of candidates to display

**Examples:**
```shortcode
// Display all finalists in a 4-column grid
[mt_candidate_grid status="finalist" columns="4"]

// Show only startup candidates with filters
[mt_candidate_grid category="startups" show_filters="true"]

// Random selection of 12 candidates
[mt_candidate_grid orderby="random" limit="12"]
```

### [mt_voting_form]
Renders the voting interface for jury members or public voting.

**Parameters:**
- `type` (string) - 'jury' or 'public' (default: 'public')
- `candidate_id` (int) - Specific candidate ID for single voting
- `category` (string) - Limit voting to specific category
- `show_criteria` (bool) - Display evaluation criteria (jury only, default: true)
- `redirect_after` (string) - URL to redirect after voting

**Examples:**
```shortcode
// Public voting form for all candidates
[mt_voting_form type="public"]

// Jury voting for specific candidate
[mt_voting_form type="jury" candidate_id="123"]

// Category-specific voting with redirect
[mt_voting_form category="established" redirect_after="/thank-you"]
```

### [mt_jury_members]
Displays jury members in a professional grid layout.

**Parameters:**
- `role` (string) - Filter by role: 'president', 'vice-president', 'member'
- `columns` (int) - Number of columns: 2, 3, or 4 (default: 3)
- `show_bio` (bool) - Display member biographies (default: true)
- `show_expertise` (bool) - Show expertise areas (default: true)

**Examples:**
```shortcode
// Display all jury members
[mt_jury_members]

// Show only leadership in 2 columns
[mt_jury_members role="president,vice-president" columns="2"]
```

### [mt_voting_results]
Shows voting results and rankings.

**Parameters:**
- `type` (string) - 'summary', 'detailed', or 'chart' (default: 'summary')
- `category` (string) - Filter results by category
- `show_scores` (bool) - Display numerical scores (default: false)
- `top` (int) - Show only top N candidates
- `year` (string) - Specific award year

**Examples:**
```shortcode
// Top 25 summary results
[mt_voting_results type="summary" top="25"]

// Detailed results with scores for startups
[mt_voting_results type="detailed" category="startups" show_scores="true"]

// Chart visualization of all results
[mt_voting_results type="chart"]
```

### [mt_timeline]
Displays the award timeline and important dates.

**Parameters:**
- `phase` (string) - Highlight specific phase: 'submission', 'evaluation', 'announcement'
- `style` (string) - 'horizontal' or 'vertical' (default: 'horizontal')

**Example:**
```shortcode
[mt_timeline phase="evaluation" style="vertical"]
```

### [mt_stats]
Shows award statistics and metrics.

**Parameters:**
- `type` (string) - 'candidates', 'votes', 'participation', or 'all'
- `animated` (bool) - Animate numbers on scroll (default: true)

**Example:**
```shortcode
[mt_stats type="all" animated="true"]
```

## 🔌 REST API Documentation

The plugin provides a comprehensive REST API for external integrations.

### Authentication
All API endpoints require authentication using WordPress application passwords or JWT tokens.

```bash
# Basic authentication example
curl -X GET https://your-site.com/wp-json/mobility-trailblazers/v1/candidates \
  -H "Authorization: Basic $(echo -n 'username:application-password' | base64)"
```

### Endpoints

#### Candidates

**GET /wp-json/mobility-trailblazers/v1/candidates**
Retrieve all candidates with filtering options.

Query Parameters:
- `category` - Filter by category slug
- `status` - Filter by status
- `year` - Award year
- `page` - Page number
- `per_page` - Items per page (max: 100)
- `orderby` - Sort field: 'name', 'score', 'date'
- `order` - Sort direction: 'asc', 'desc'

Response:
```json
{
  "candidates": [
    {
      "id": 123,
      "name": "Dr. Jane Doe",
      "title": "CEO CleanMobility GmbH",
      "category": "startups",
      "status": "finalist",
      "description": "Pioneering sustainable urban mobility...",
      "photo_url": "https://...",
      "scores": {
        "courage": 9.2,
        "innovation": 8.8,
        "implementation": 9.0,
        "relevance": 9.5,
        "visibility": 8.5,
        "average": 9.0
      },
      "meta": {
        "company": "CleanMobility GmbH",
        "linkedin": "https://linkedin.com/in/janedoe",
        "website": "https://cleanmobility.de"
      }
    }
  ],
  "total": 150,
  "pages": 6,
  "current_page": 1
}
```

**GET /wp-json/mobility-trailblazers/v1/candidates/{id}**
Get a specific candidate's details.

**POST /wp-json/mobility-trailblazers/v1/candidates**
Create a new candidate (admin only).

Request Body:
```json
{
  "name": "Dr. Jane Doe",
  "title": "CEO CleanMobility GmbH",
  "category": "startups",
  "description": "Pioneering sustainable urban mobility...",
  "company": "CleanMobility GmbH",
  "linkedin": "https://linkedin.com/in/janedoe"
}
```

**PUT /wp-json/mobility-trailblazers/v1/candidates/{id}**
Update candidate information.

**DELETE /wp-json/mobility-trailblazers/v1/candidates/{id}**
Remove a candidate (admin only).

#### Voting

**POST /wp-json/mobility-trailblazers/v1/votes**
Submit a vote (jury members only).

Request Body:
```json
{
  "candidate_id": 123,
  "scores": {
    "courage": 9,
    "innovation": 8,
    "implementation": 9,
    "relevance": 10,
    "visibility": 8
  },
  "comment": "Exceptional work in sustainable mobility..."
}
```

**GET /wp-json/mobility-trailblazers/v1/votes/my-votes**
Get current user's votes.

**GET /wp-json/mobility-trailblazers/v1/votes/statistics**
Get voting statistics (admin only).

Response:
```json
{
  "total_votes": 450,
  "jury_participation": 0.85,
  "average_scores": {
    "courage": 8.2,
    "innovation": 7.9,
    "implementation": 8.1,
    "relevance": 8.5,
    "visibility": 7.8
  },
  "votes_by_category": {
    "established": 150,
    "startups": 180,
    "public": 120
  }
}
```

#### Jury Management

**GET /wp-json/mobility-trailblazers/v1/jury**
List all jury members.

**GET /wp-json/mobility-trailblazers/v1/jury/{id}/assignments**
Get jury member's assigned candidates.

**POST /wp-json/mobility-trailblazers/v1/jury/assignments**
Create or update jury assignments (admin only).

Request Body:
```json
{
  "jury_id": 456,
  "candidate_ids": [123, 124, 125],
  "assignment_type": "manual"
}
```

#### Public Voting

**POST /wp-json/mobility-trailblazers/v1/public-vote**
Submit a public vote.

Request Body:
```json
{
  "candidate_id": 123,
  "email": "voter@example.com",
  "gdpr_consent": true
}
```

**GET /wp-json/mobility-trailblazers/v1/public-vote/results**
Get public voting results.

#### Export

**GET /wp-json/mobility-trailblazers/v1/export/candidates**
Export candidates as CSV (admin only).

**GET /wp-json/mobility-trailblazers/v1/export/votes**
Export voting data as CSV (admin only).

**GET /wp-json/mobility-trailblazers/v1/export/results**
Export final results as PDF (admin only).

### Webhooks

The plugin supports webhooks for key events:

- `candidate.created` - New candidate added
- `candidate.status_changed` - Candidate status updated
- `vote.submitted` - New vote recorded
- `phase.changed` - Award phase transition

Configure webhooks in WordPress admin under Mobility Trailblazers > Settings > Webhooks.

### Rate Limiting

- Public endpoints: 100 requests per hour
- Authenticated endpoints: 1000 requests per hour
- Voting endpoints: 50 requests per hour per user

### Error Responses

All errors follow consistent format:
```json
{
  "code": "invalid_candidate",
  "message": "Candidate not found",
  "data": {
    "status": 404,
    "candidate_id": 999
  }
}
```

Common error codes:
- `unauthorized` - Authentication required
- `forbidden` - Insufficient permissions
- `invalid_request` - Malformed request data
- `not_found` - Resource not found
- `rate_limited` - Too many requests
- `validation_failed` - Input validation errors

## 🔧 Plugin Architecture

### Custom Post Types
- `mt_candidate` - Award candidates
- `mt_jury` - Jury members
- `mt_award` - Award records

### Custom Taxonomies
- `mt_category` - Candidate categories
- `mt_status` - Selection status (longlist, shortlist, finalist, winner)
- `mt_award_year` - Award year tracking

### Database Tables
- `wp_mt_votes` - Jury voting records
- `wp_mt_public_votes` - Public voting data
- `wp_mt_candidate_scores` - Detailed evaluation scores
- `wp_mt_assignments` - Jury-candidate assignments
- `wp_mt_voting_sessions` - Voting session tracking

## 👥 Jury System

The platform features a distinguished 20-member jury led by:
- **President**: Prof. Dr. Andreas Herrmann (University of St. Gallen)
- **Vice President**: Prof. em. Dr. Dr. h.c. Torsten Tomczak
- **Patron**: Winfried Hermann (Transport Minister Baden-Württemberg)

## 📊 Communication Strategy

### 2025 Vision
- **Focus**: Establish Mobility Trailblazers as a prestigious new award
- **Channels**: Website + LinkedIn (organizational communication)
- **Media Partner**: Handelsblatt
- **Event**: Award ceremony at Smart Mobility Summit Berlin (Oct 30, 2025)

### 2026 Vision
- **Expansion**: Broader public engagement
- **Additional Channels**: Instagram, TikTok, Podcast
- **Content**: Video-first approach, mini-documentaries

## 🔐 Security & Permissions

- WordPress user roles integration
- Jury member authentication via email matching
- Secure voting with duplicate prevention
- GDPR-compliant data handling
- Rate limiting on all API endpoints
- CORS configuration for approved domains

## 📦 Development Setup

### Using WP-CLI
```bash
# List all candidates
docker exec mobility_wpcli wp post list --post_type=mt_candidate

# Check jury members
docker exec mobility_wpcli wp post list --post_type=mt_jury

# Export data
docker exec mobility_wpcli wp eval-file export-script.php

# Run tests
docker exec mobility_wpcli wp eval-file tests/run-tests.php
```

### Database Access
- Host: `localhost:9306`
- Database: `mobility_trailblazers`
- User: `mt_db_user_2025`
- Password: See docker-compose.yml

## 🧪 Testing

### API Testing with cURL

```bash
# Test candidate listing
curl -X GET http://localhost:9080/wp-json/mobility-trailblazers/v1/candidates

# Test voting submission (requires auth)
curl -X POST http://localhost:9080/wp-json/mobility-trailblazers/v1/votes \
  -H "Content-Type: application/json" \
  -H "Authorization: Basic base64_encoded_credentials" \
  -d '{"candidate_id": 123, "scores": {"courage": 9, "innovation": 8}}'
```

### Postman Collection

Import the included `mobility-trailblazers.postman_collection.json` for comprehensive API testing.

## 🚧 Roadmap

- [x] Core award management system
- [x] Jury evaluation platform
- [x] Assignment management interface
- [x] REST API implementation
- [ ] GraphQL API support
- [ ] Advanced analytics dashboard
- [ ] Mobile app for jury members
- [ ] AI-powered candidate matching
- [ ] Multi-language support (DE/EN)
- [ ] Blockchain-based voting verification

## 📄 License

Proprietary - Institut für Mobilität, Universität St. Gallen

## 🤝 Contributing

This is a private project. For access or contributions, please contact:
- Prof. Dr. Andreas Herrmann - andreas.herrmann@unisg.ch
- Technical Lead: Nicolas Estrem

## 🔗 Links

- [Institut für Mobilität](https://mobility.unisg.ch)
- [Smart Mobility Summit](https://smart-mobility-summit.de)
- Media Partner: [Handelsblatt](https://handelsblatt.com)

---

**Mobility Trailblazers** - Transforming mobility with courage and innovation in the DACH region 🚀