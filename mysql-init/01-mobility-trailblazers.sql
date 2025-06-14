-- ========================================
-- Mobility Trailblazers Database Initialization Script
-- For MySQL 8.0+ in Docker Environment
-- Version: 2.0 - WITHOUT PUBLIC VOTING
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

-- Create custom role for jury members and admin
CREATE ROLE IF NOT EXISTS 'mt_jury_role';
CREATE ROLE IF NOT EXISTS 'mt_admin_role';

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

-- Voting records table for jury members (simplified voting)
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

-- Detailed evaluation scores table (main evaluation system)
CREATE TABLE IF NOT EXISTS mt_mt_candidate_scores (
    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) NOT NULL,
    jury_member_id BIGINT(20) NOT NULL,
    -- 5 evaluation criteria
    courage_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Mut & Pioniergeist (1-10)',
    innovation_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Innovationsgrad (1-10)',
    implementation_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Umsetzungskraft & Wirkung (1-10)',
    relevance_score TINYINT(2) NOT NULL DEFAULT 0 COMMENT 'Relevanz für Mobilitätswende (1-10)',
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
    CONSTRAINT chk_relevance_score CHECK (relevance_score >= 0 AND relevance_score <= 10),
    CONSTRAINT chk_visibility_score CHECK (visibility_score >= 0 AND visibility_score <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment management table
CREATE TABLE IF NOT EXISTS mt_mt_assignments (
    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) NOT NULL,
    jury_member_id BIGINT(20) NOT NULL,
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    completed_date DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_assignment (candidate_id, jury_member_id),
    KEY candidate_idx (candidate_id),
    KEY jury_idx (jury_member_id),
    KEY status_idx (status)
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
    SET NEW.total_score = NEW.courage_score + NEW.innovation_score + 
                         NEW.implementation_score + NEW.relevance_score + 
                         NEW.visibility_score;
END$$

-- Trigger to automatically calculate total score on update
CREATE TRIGGER tr_calculate_total_score_update
    BEFORE UPDATE ON mt_mt_candidate_scores
    FOR EACH ROW
BEGIN
    SET NEW.total_score = NEW.courage_score + NEW.innovation_score + 
                         NEW.implementation_score + NEW.relevance_score + 
                         NEW.visibility_score;
END$$

DELIMITER ;

-- ========================================
-- 4. CREATE STORED PROCEDURES FOR COMMON OPERATIONS
-- ========================================

DELIMITER $$

-- Procedure to get voting statistics (jury only)
CREATE PROCEDURE GetVotingStatistics()
BEGIN
    SELECT 
        'jury_evaluations' as stat_type,
        COUNT(*) as count,
        AVG(total_score) as avg_score,
        MAX(total_score) as max_score,
        MIN(total_score) as min_score
    FROM mt_mt_candidate_scores
    WHERE total_score > 0;
END$$

-- Procedure to get candidate rankings
CREATE PROCEDURE GetCandidateRankings(IN round_number INT)
BEGIN
    SELECT 
        c.candidate_id,
        AVG(c.total_score) as average_score,
        COUNT(DISTINCT c.jury_member_id) as evaluation_count,
        GROUP_CONCAT(DISTINCT c.jury_member_id) as jury_members
    FROM mt_mt_candidate_scores c
    WHERE c.evaluation_round = round_number
    GROUP BY c.candidate_id
    HAVING evaluation_count > 0
    ORDER BY average_score DESC;
END$$

-- Procedure to clean up test data (for development)
CREATE PROCEDURE CleanupTestData()
BEGIN
    DELETE FROM mt_mt_votes WHERE candidate_id = 999999;
    DELETE FROM mt_mt_candidate_scores WHERE candidate_id = 999999;
    DELETE FROM mt_mt_assignments WHERE candidate_id = 999999;
END$$

-- Procedure to get jury member statistics
CREATE PROCEDURE GetJuryMemberStats(IN jury_id BIGINT)
BEGIN
    SELECT 
        COUNT(DISTINCT candidate_id) as candidates_evaluated,
        AVG(total_score) as average_score_given,
        MAX(evaluation_date) as last_evaluation_date,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_assignments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_assignments
    FROM mt_mt_candidate_scores cs
    LEFT JOIN mt_mt_assignments a ON cs.candidate_id = a.candidate_id 
        AND cs.jury_member_id = a.jury_member_id
    WHERE cs.jury_member_id = jury_id;
END$$

DELIMITER ;

-- ========================================
-- 5. CREATE USERS FOR DIFFERENT ACCESS LEVELS
-- ========================================

-- Create jury access user
CREATE USER IF NOT EXISTS 'mt_jury_user'@'%' IDENTIFIED BY 'JuRy2025!MobTr';
GRANT 'mt_jury_role' TO 'mt_jury_user'@'%';
SET DEFAULT ROLE 'mt_jury_role' TO 'mt_jury_user'@'%';

-- Grant admin role to main database user
GRANT 'mt_admin_role' TO 'mt_db_user_2025'@'%';
SET DEFAULT ROLE 'mt_admin_role' TO 'mt_db_user_2025'@'%';

-- ========================================
-- 6. CREATE VIEWS FOR COMMON QUERIES (AFTER WORDPRESS INSTALLATION)
-- ========================================

-- Note: These views will be created by the plugin activation hook after WordPress installation
-- They reference WordPress tables (mt_posts, mt_postmeta, etc.)

-- ========================================
-- 7. FINAL GRANTS AND CLEANUP
-- ========================================

-- Flush privileges to ensure all grants take effect
FLUSH PRIVILEGES;

-- Analyze plugin tables for better query planning
ANALYZE TABLE mt_mt_votes, mt_mt_candidate_scores, mt_mt_assignments;

-- Log successful initialization
SELECT 'Mobility Trailblazers database initialization completed successfully!' as status,
       'Public voting has been removed - Jury evaluation only' as configuration,
       'Next step: Install WordPress, then activate the plugin' as next_step;