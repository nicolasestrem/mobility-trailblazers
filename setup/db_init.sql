-- ========================================
-- Mobility Trailblazers Database Initialization Script
-- For MySQL 8.0+ in Docker Environment
-- Location: /mnt/dietpi_userdata/docker-files/STAGING/mysql-init/01-mobility-trailblazers.sql
-- ========================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS mobility_trailblazers 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Create user if it doesn't exist
CREATE USER IF NOT EXISTS 'mt_db_user_2025'@'%' IDENTIFIED BY 'mT8kL9xP2qR7vN6wE3zY4uC1sA5fG';

-- Grant privileges
GRANT ALL PRIVILEGES ON mobility_trailblazers.* TO 'mt_db_user_2025'@'%';
FLUSH PRIVILEGES;

-- Now use the database
USE mobility_trailblazers;

-- ========================================
-- 1. CREATE CUSTOM ROLES AND PERMISSIONS
-- ========================================

-- Create custom role for jury members
CREATE ROLE IF NOT EXISTS 'mt_jury_role';
CREATE ROLE IF NOT EXISTS 'mt_admin_role';
CREATE ROLE IF NOT EXISTS 'mt_public_role';

-- Grant basic read permissions to public role
GRANT SELECT ON mobility_trailblazers.mt_posts TO 'mt_public_role';
GRANT SELECT ON mobility_trailblazers.mt_postmeta TO 'mt_public_role';
GRANT SELECT ON mobility_trailblazers.mt_terms TO 'mt_public_role';
GRANT SELECT ON mobility_trailblazers.mt_term_taxonomy TO 'mt_public_role';
GRANT SELECT ON mobility_trailblazers.mt_term_relationships TO 'mt_public_role';

-- Grant voting permissions to public role
GRANT INSERT, SELECT ON mobility_trailblazers.mt_mt_public_votes TO 'mt_public_role';

-- Grant jury evaluation permissions
GRANT INSERT, UPDATE, SELECT, DELETE ON mobility_trailblazers.mt_mt_candidate_scores TO 'mt_jury_role';
GRANT INSERT, UPDATE, SELECT, DELETE ON mobility_trailblazers.mt_mt_votes TO 'mt_jury_role';
GRANT SELECT ON mobility_trailblazers.mt_posts TO 'mt_jury_role';
GRANT SELECT ON mobility_trailblazers.mt_postmeta TO 'mt_jury_role';
GRANT SELECT ON mobility_trailblazers.mt_users TO 'mt_jury_role';

-- Grant full admin permissions
GRANT ALL PRIVILEGES ON mobility_trailblazers.* TO 'mt_admin_role';

-- ========================================
-- 2. CREATE PLUGIN-SPECIFIC TABLES
-- ========================================

-- Voting records table for jury members
CREATE TABLE IF NOT EXISTS mt_mt_votes (
    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) NOT NULL,
    jury_member_id BIGINT(20) NOT NULL,
    vote_round TINYINT(1) NOT NULL DEFAULT 1,
    rating TINYINT(2) NOT NULL,
    comments TEXT,
    vote_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_vote (candidate_id, jury_member_id, vote_round),
    KEY candidate_idx (candidate_id),
    KEY jury_idx (jury_member_id),
    KEY vote_round_idx (vote_round),
    KEY vote_date_idx (vote_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Public voting table
CREATE TABLE IF NOT EXISTS mt_mt_public_votes (
    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) NOT NULL,
    voter_email VARCHAR(255) NOT NULL,
    voter_ip VARCHAR(45) NOT NULL,
    vote_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_public_vote (candidate_id, voter_email),
    KEY candidate_idx (candidate_id),
    KEY voter_email_idx (voter_email),
    KEY vote_date_idx (vote_date),
    KEY voter_ip_idx (voter_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detailed evaluation scores table
CREATE TABLE IF NOT EXISTS mt_mt_candidate_scores (
    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) NOT NULL,
    jury_member_id BIGINT(20) NOT NULL,
    -- 5 evaluation criteria from documentation page 5
    courage_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Mut & Pioniergeist (1-10)',
    innovation_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Innovationsgrad (1-10)',
    implementation_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Umsetzungskraft & Wirkung (1-10)',
    mobility_relevance_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Relevanz für Mobilitätswende (1-10)',
    visibility_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Vorbildfunktion & Sichtbarkeit (1-10)',
    total_score DECIMAL(4,2) DEFAULT 0 COMMENT 'Calculated total score (max 50)',
    evaluation_round TINYINT(1) NOT NULL DEFAULT 1,
    evaluation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round),
    KEY candidate_idx (candidate_id),
    KEY jury_idx (jury_member_id),
    KEY total_score_idx (total_score),
    KEY evaluation_round_idx (evaluation_round),
    CONSTRAINT chk_courage_score CHECK (courage_score >= 0 AND courage_score <= 10),
    CONSTRAINT chk_innovation_score CHECK (innovation_score >= 0 AND innovation_score <= 10),
    CONSTRAINT chk_implementation_score CHECK (implementation_score >= 0 AND implementation_score <= 10),
    CONSTRAINT chk_mobility_relevance_score CHECK (mobility_relevance_score >= 0 AND mobility_relevance_score <= 10),
    CONSTRAINT chk_visibility_score CHECK (visibility_score >= 0 AND visibility_score <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. CREATE TRIGGERS FOR SCORE CALCULATION
-- ========================================

DELIMITER $$

-- Trigger to automatically calculate total score on insert
CREATE TRIGGER tr_calculate_total_score_insert
    BEFORE INSERT ON mt_mt_candidate_scores
    FOR EACH ROW
BEGIN
    SET NEW.total_score = NEW.courage_score + NEW.innovation_score + NEW.implementation_score + NEW.mobility_relevance_score + NEW.visibility_score;
END$$

-- Trigger to automatically calculate total score on update
CREATE TRIGGER tr_calculate_total_score_update
    BEFORE UPDATE ON mt_mt_candidate_scores
    FOR EACH ROW
BEGIN
    SET NEW.total_score = NEW.courage_score + NEW.innovation_score + NEW.implementation_score + NEW.mobility_relevance_score + NEW.visibility_score;
END$$

DELIMITER ;

-- ========================================
-- 4. CREATE INDEXES FOR PERFORMANCE (WILL BE APPLIED AFTER WORDPRESS INSTALLATION)
-- ========================================

-- Note: These indexes reference WordPress tables that don't exist yet
-- They will be created by the plugin activation hook after WordPress installation

-- ========================================
-- 5. INSERT DEFAULT DATA (PLUGIN ACTIVATION WILL HANDLE WORDPRESS OPTIONS)
-- ========================================

-- Note: WordPress options will be inserted by the plugin activation hook
-- when WordPress is installed and the plugin is activated

-- ========================================
-- 6. CREATE STORED PROCEDURES FOR COMMON OPERATIONS (SIMPLIFIED)
-- ========================================

DELIMITER $

-- Procedure to get voting statistics (plugin tables only)
CREATE PROCEDURE GetVotingStatistics()
BEGIN
    SELECT 
        'jury_evaluations' as stat_type,
        COUNT(*) as count
    FROM mt_mt_candidate_scores
    UNION ALL
    SELECT 
        'public_votes' as stat_type,
        COUNT(*) as count
    FROM mt_mt_public_votes;
END$

-- Procedure to clean up test data (for development)
CREATE PROCEDURE CleanupTestData()
BEGIN
    DELETE FROM mt_mt_votes WHERE candidate_id = 999999;
    DELETE FROM mt_mt_public_votes WHERE candidate_id = 999999;
    DELETE FROM mt_mt_candidate_scores WHERE candidate_id = 999999;
END$

DELIMITER ;

-- ========================================
-- 7. CREATE USERS FOR DIFFERENT ACCESS LEVELS
-- ========================================

-- Create jury access user
CREATE USER IF NOT EXISTS 'mt_jury_user'@'%' IDENTIFIED BY 'JuRy2025!MobTr';
GRANT 'mt_jury_role' TO 'mt_jury_user'@'%';
SET DEFAULT ROLE 'mt_jury_role' TO 'mt_jury_user'@'%';

-- Create public voting user (for API access)
CREATE USER IF NOT EXISTS 'mt_public_user'@'%' IDENTIFIED BY 'PuBl1c2025!Vote';
GRANT 'mt_public_role' TO 'mt_public_user'@'%';
SET DEFAULT ROLE 'mt_public_role' TO 'mt_public_user'@'%';

-- Grant admin role to main database user
GRANT 'mt_admin_role' TO 'mt_db_user_2025'@'%';
SET DEFAULT ROLE 'mt_admin_role' TO 'mt_db_user_2025'@'%';

-- ========================================
-- 8. CREATE VIEWS FOR COMMON QUERIES (AFTER WORDPRESS INSTALLATION)
-- ========================================

-- Note: These views reference WordPress tables (mt_posts, mt_postmeta, etc.)
-- They will be created by the plugin activation hook after WordPress installation

-- ========================================
-- 12. FINAL GRANTS AND CLEANUP
-- ========================================

-- Flush privileges to ensure all grants take effect
FLUSH PRIVILEGES;

-- Analyze plugin tables for better query planning
ANALYZE TABLE mt_mt_votes, mt_mt_public_votes, mt_mt_candidate_scores;

-- Log successful initialization
-- Note: Plugin options will be added when WordPress is installed and plugin is activated

SELECT 'Mobility Trailblazers database initialization completed successfully!' as status,
       'Next step: Install WordPress, then activate the plugin' as next_step;