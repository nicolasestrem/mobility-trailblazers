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

Start the Docker stack:
```bash
docker-compose up -d
```

Access the platform:

- WordPress: http://localhost:9080
- phpMyAdmin: http://localhost:9081

Run initial setup:
```bash
# Install WordPress
docker exec mobility_wpcli wp core install \
  --url='http://localhost:9080' \
  --title='Mobility Trailblazers' \
  --admin_user='admin' \
  --admin_password='secure_password' \
  --admin_email='admin@mobilitytrailblazers.de'

# Activate the plugin
docker exec mobility_wpcli wp plugin activate mobility-trailblazers

# Import jury members
docker exec mobility_wpcli wp eval-file wp-content/jury-import.php
```

## 📋 Features

### Award Management System

- Three-phase selection process: 2000 → 200 → 50 → 25 candidates
- Category-based evaluation:
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

### Public Engagement

- Candidate showcase grid
- Public voting system (optional)
- Results visualization
- Responsive design for all devices

## 👥 Jury System

The platform features a distinguished 20-member jury led by:

- President: Prof. Dr. Andreas Herrmann (University of St. Gallen)
- Vice President: Prof. em. Dr. Dr. h.c. Torsten Tomczak
- Patron: Winfried Hermann (Transport Minister Baden-Württemberg)

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

### Shortcodes

- `[mt_candidate_grid]` - Display candidate grid
- `[mt_voting_form]` - Voting interface
- `[mt_jury_members]` - Jury member showcase
- `[mt_voting_results]` - Results display

## 📊 Communication Strategy

### 2025 Vision

- Focus: Establish Mobility Trailblazers as a prestigious new award
- Channels: Website + LinkedIn (organizational communication)
- Media Partner: Handelsblatt
- Event: Award ceremony at Smart Mobility Summit Berlin (Oct 30, 2025)

### 2026 Vision

- Expansion: Broader public engagement
- Additional Channels: Instagram, TikTok, Podcast
- Content: Video-first approach, mini-documentaries

## 🔐 Security & Permissions

- WordPress user roles integration
- Jury member authentication via email matching
- Secure voting with duplicate prevention
- GDPR-compliant data handling

## 📦 Development Setup

### Using WP-CLI

```bash
# List all candidates
docker exec mobility_wpcli wp post list --post_type=mt_candidate

# Check jury members
docker exec mobility_wpcli wp post list --post_type=mt_jury

# Export data
docker exec mobility_wpcli wp eval-file export-script.php
```

### Database Access

- Host: localhost:9306
- Database: mobility_trailblazers
- User: mt_db_user_2025
- Password: See docker-compose.yml

## 🚧 Roadmap

- Core award management system
- Jury evaluation platform
- Assignment management interface
- Advanced analytics dashboard
- Mobile app for jury members
- AI-powered candidate matching
- Multi-language support (DE/EN)

## 📄 License

Proprietary - Institut für Mobilität, Universität St. Gallen

## 🤝 Contributing

This is a private project. For access or contributions, please contact:

- Prof. Dr. Andreas Herrmann - andreas.herrmann@unisg.ch
- Technical Lead: Nicolas Estrem

## 🔗 Links

- Institut für Mobilität
- Smart Mobility Summit
- Media Partner: Handelsblatt
