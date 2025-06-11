-- =========================================================================
-- MOBILITY TRAILBLAZERS - COMPLETE DATABASE SETUP
-- This creates ALL missing tables and structure for the assignment interface
-- Run this IMMEDIATELY in phpMyAdmin to fix the non-functional interface
-- =========================================================================

-- 🏗️ STEP 1: CREATE CORE ASSIGNMENT TABLE
-- =========================================================================

CREATE TABLE IF NOT EXISTS `mt_jury_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `jury_member_id` int(11) NOT NULL,
  `stage` varchar(50) NOT NULL DEFAULT 'semifinal',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`candidate_id`,`jury_member_id`,`stage`),
  KEY `idx_candidate` (`candidate_id`),
  KEY `idx_jury_member` (`jury_member_id`),
  KEY `idx_stage` (`stage`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 🗳️ STEP 2: CREATE SCORING/EVALUATION TABLES
-- =========================================================================

CREATE TABLE IF NOT EXISTS `mt_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `jury_member_id` int(11) NOT NULL,
  `stage` varchar(50) NOT NULL,
  `criteria_1_score` decimal(3,1) DEFAULT NULL,
  `criteria_1_comment` text DEFAULT NULL,
  `criteria_2_score` decimal(3,1) DEFAULT NULL,
  `criteria_2_comment` text DEFAULT NULL,
  `criteria_3_score` decimal(3,1) DEFAULT NULL,
  `criteria_3_comment` text DEFAULT NULL,
  `criteria_4_score` decimal(3,1) DEFAULT NULL,
  `criteria_4_comment` text DEFAULT NULL,
  `overall_score` decimal(4,2) DEFAULT NULL,
  `overall_comment` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_evaluation` (`assignment_id`),
  KEY `idx_candidate_jury` (`candidate_id`,`jury_member_id`),
  KEY `idx_stage` (`stage`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_eval_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `mt_jury_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 📊 STEP 3: CREATE STAGES AND WORKFLOW TABLE
-- =========================================================================

CREATE TABLE IF NOT EXISTS `mt_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stage_name` varchar(50) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `max_candidates` int(11) DEFAULT NULL,
  `assignments_per_candidate` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stage_name` (`stage_name`),
  KEY `idx_stage_order` (`stage_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default stages
INSERT INTO `mt_stages` (`stage_name`, `stage_order`, `description`, `assignments_per_candidate`) VALUES
('database', 1, 'Initial database of all candidates', 1),
('shortlist', 2, 'Shortlisted candidates for evaluation', 2),
('semifinal', 3, 'Semifinal evaluation stage', 3),
('final', 4, 'Final evaluation stage', 3)
ON DUPLICATE KEY UPDATE 
stage_order = VALUES(stage_order),
description = VALUES(description);

-- 📝 STEP 4: CREATE ASSIGNMENT LOGS TABLE
-- =========================================================================

CREATE TABLE IF NOT EXISTS `mt_assignment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assignment` (`assignment_id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_performed_at` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 🎯 STEP 5: CREATE CANDIDATE STAGES TRACKING
-- =========================================================================

CREATE TABLE IF NOT EXISTS `mt_candidate_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `current_stage` varchar(50) NOT NULL DEFAULT 'database',
  `previous_stage` varchar(50) DEFAULT NULL,
  `stage_changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stage_changed_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `elimination_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_candidate_stage` (`candidate_id`),
  KEY `idx_current_stage` (`current_stage`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 🛠️ STEP 6: CREATE PLUGIN SETTINGS TABLE
-- =========================================================================

CREATE TABLE IF NOT EXISTS `mt_plugin_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` varchar(20) NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default plugin settings
INSERT INTO `mt_plugin_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('assignments_per_candidate', '3', 'integer', 'Default number of jury members to assign per candidate'),
('auto_assignment_enabled', '1', 'boolean', 'Enable automatic assignment functionality'),
('evaluation_criteria_weights', '{"criteria_1": 0.25, "criteria_2": 0.25, "criteria_3": 0.25, "criteria_4": 0.25}', 'json', 'Scoring weights for evaluation criteria'),
('current_active_stage', 'semifinal', 'string', 'Currently active evaluation stage'),
('interface_debug_mode', '1', 'boolean', 'Enable debug mode for assignment interface')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = CURRENT_TIMESTAMP;

-- 📋 STEP 7: VERIFY TABLE CREATION AND SETUP
-- =========================================================================

-- Check that all tables were created successfully
SELECT 
    'TABLE CREATION VERIFICATION' as check_type,
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name LIKE 'mt_%'
ORDER BY table_name;

-- Verify foreign key constraints
SELECT 
    'FOREIGN KEY VERIFICATION' as check_type,
    constraint_name,
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE()
AND constraint_name LIKE 'fk_%'
ORDER BY table_name;

-- 🎯 STEP 8: INITIALIZE CANDIDATE STAGES (CRITICAL FOR INTERFACE)
-- =========================================================================

-- This populates the candidate stages table with all existing candidates
-- This is ESSENTIAL for the assignment interface to show candidates
INSERT INTO `mt_candidate_stages` (`candidate_id`, `current_stage`, `stage_changed_at`, `is_active`)
SELECT 
    p.ID as candidate_id,
    COALESCE(pm.meta_value, 'database') as current_stage,
    p.post_date as stage_changed_at,
    1 as is_active
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'stage'
WHERE p.post_type = 'candidate' 
AND p.post_status = 'publish'
AND NOT EXISTS (
    SELECT 1 FROM mt_candidate_stages cs WHERE cs.candidate_id = p.ID
)
ON DUPLICATE KEY UPDATE
current_stage = COALESCE(VALUES(current_stage), current_stage);

-- 📊 STEP 9: FINAL VERIFICATION AND SUMMARY
-- =========================================================================

-- Final health check - this should show your system is ready
SELECT 
    '🎯 ASSIGNMENT INTERFACE READINESS CHECK' as system_status,
    (SELECT COUNT(*) FROM wp_posts WHERE post_type = 'candidate' AND post_status = 'publish') as total_candidates,
    (SELECT COUNT(*) FROM wp_users u JOIN wp_usermeta um ON u.ID = um.user_id WHERE um.meta_key = 'wp_capabilities' AND (um.meta_value LIKE '%jury%' OR um.meta_value LIKE '%administrator%')) as total_jury_members,
    (SELECT COUNT(*) FROM mt_candidate_stages WHERE is_active = 1) as candidates_in_system,
    (SELECT COUNT(*) FROM mt_stages WHERE is_active = 1) as active_stages,
    (SELECT COUNT(*) FROM mt_jury_assignments) as current_assignments,
    CASE 
        WHEN (SELECT COUNT(*) FROM wp_posts WHERE post_type = 'candidate' AND post_status = 'publish') = 0 THEN '❌ NO CANDIDATES FOUND - Import candidate data first'
        WHEN (SELECT COUNT(*) FROM wp_users u JOIN wp_usermeta um ON u.ID = um.user_id WHERE um.meta_key = 'wp_capabilities' AND (um.meta_value LIKE '%jury%' OR um.meta_value LIKE '%administrator%')) = 0 THEN '❌ NO JURY MEMBERS FOUND - Set up user roles'
        WHEN (SELECT COUNT(*) FROM mt_candidate_stages WHERE is_active = 1) = 0 THEN '❌ NO CANDIDATE STAGES - Run candidate initialization'
        ELSE '✅ DATABASE STRUCTURE READY - Assignment interface should now work'
    END as diagnosis,
    NOW() as setup_completed_at;

-- Show sample data for verification
SELECT '📋 SAMPLE CANDIDATES FOR TESTING' as info;
SELECT 
    p.ID,
    p.post_title as candidate_name,
    cs.current_stage,
    cs.is_active
FROM wp_posts p
JOIN mt_candidate_stages cs ON p.ID = cs.candidate_id
WHERE p.post_type = 'candidate' AND p.post_status = 'publish'
ORDER BY p.post_title
LIMIT 5;

SELECT '👥 SAMPLE JURY MEMBERS FOR TESTING' as info;
SELECT 
    u.ID,
    u.display_name,
    u.user_email
FROM wp_users u
JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities' 
AND (um.meta_value LIKE '%jury%' OR um.meta_value LIKE '%administrator%')
ORDER BY u.display_name
LIMIT 5;