# Mobility Trailblazers Plugin

The **Mobility Trailblazers** plugin powers the award system for identifying and celebrating the top 25 individuals driving mobility transformation across the DACH region. It enables structured jury voting, candidate assignment, and transparent evaluation workflows — all within WordPress.

## 🔍 Overview

This plugin was developed for the official Mobility Trailblazers Award platform and is tailored for private jury evaluation. The public does not participate in the voting.

Key features include:
- Structured multi-round jury voting
- Candidate profile grid for public display
- Role-based dashboards for jury members
- Assignment management between candidates and jury
- REST API endpoints for assignment/voting actions

---

## 🔧 Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate it in the WordPress admin under **Plugins**
3. Import initial candidate and jury data (CSV or via admin panel)
4. Set up the required shortcodes in pages (see below)
5. Verify user roles: `jury_member` role must be assigned manually or programmatically

---

## 🧩 Shortcodes

Use these shortcodes to embed the front-end features:

- `[mt_candidate_grid]`  
  → Displays the public candidate grid

- `[mt_voting_interface]`  
  → Jury voting interface (jury members only)

- `[mt_jury_dashboard]`  
  → Dashboard for jury members to track assignments and progress

- `[mt_voting_progress]`  
  → Admin/jury stats on voting progress

---

## 👥 User Roles

- **Administrator**: Full access, including assignment tools and export
- **Jury Member**: Can view assigned candidates and submit evaluations

---

## 🗳 Voting System

Each jury member:
- Receives 10+ candidates for evaluation
- Rates each candidate on a scale of 1 to 10 across 4 criteria:
  - Innovation
  - Impact
  - Courage
  - Role model quality

Votes are saved in a custom table and can be exported for analysis.

Voting progresses in phases:
1. 200 candidates → Top 50 (first jury ranking)
2. Top 50 → Final 25 (second jury round)

---

## 🔁 Assignment System

Jury assignments are:
- Created automatically (auto-assignment based on availability)
- Can be edited manually in the admin interface
- Stored in the custom table `wp_mt_jury_assignments`

Jury members only see their assigned candidates.

Admin panel features:
- Bulk assignment
- Manual override
- Export of assignments

---

## 🌐 REST API Endpoints

The plugin registers custom REST endpoints (prefixed with `/mt/v1/`) to:
- Trigger bulk or auto-assignments
- Export assignment data
- Validate voting status

---

## 🧹 Cleanup Tools

Administrators can:
- Clean up invalid assignments
- Reset votes
- Export all data to CSV

---

## 📦 Data Model

Custom tables:
- `wp_mt_candidates`
- `wp_mt_jury`
- `wp_mt_jury_assignments`
- `wp_mt_votes`

All relationships are managed via candidate IDs and jury IDs. Standard WordPress users are extended with meta fields where needed.

---

## 📘 Documentation & Support

This plugin is custom-built for the Mobility Trailblazers platform and not intended for public reuse. For internal support or feature extensions, contact the lead developer (Nicolas Estrem) directly.

For a detailed technical reference, refer to the companion document:  
**Mobility Trailblazers Platform – Technical Documentation v3.0**
