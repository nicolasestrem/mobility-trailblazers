-- ========================================
-- Vote Reset Functionality Extension
-- For Mobility Trailblazers Platform
-- Version: 1.0
-- Location: /mnt/dietpi_userdata/docker-files/STAGING/mysql-init/02-vote-reset-tables.sql
-- ========================================

USE wordpress_db;

-- ========================================
-- VOTE RESET LOGGING TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS wp_vote_reset_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reset_type ENUM('individual', 'bulk_user', 'bulk_candidate', 'phase_transition', 'full_reset') NOT NULL,
    initiated_by BIGINT(20) UNSIGNED NOT NULL,
    initiated_by_role ENUM('jury_member', 'admin', 'system') NOT NULL,
    affected_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
    affected_candidate_id BIGINT(20) UNSIGNED DEFAULT NULL,
    voting_phase VARCHAR(50) DEFAULT NULL,
    votes_affected INT NOT NULL DEFAULT 0,
    reset_reason TEXT,
    reset_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (initiated_by) REFERENCES wp_users(ID),
    FOREIGN KEY (affected_user_id) REFERENCES wp_users(ID),
    INDEX idx_reset_timestamp (reset_timestamp),
    INDEX idx_initiated_by (initiated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- UPDATE EXISTING TABLES
-- ========================================

-- Add soft delete capability to existing votes tables
ALTER TABLE wp_mt_votes 
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS reset_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS reset_by BIGINT(20) UNSIGNED DEFAULT NULL,
ADD INDEX IF NOT EXISTS idx_active_votes (is_active, candidate_id, jury_member_id);

ALTER TABLE wp_mt_candidate_scores
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS reset_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS reset_by BIGINT(20) UNSIGNED DEFAULT NULL,
ADD INDEX IF NOT EXISTS idx_active_scores (is_active, candidate_id, jury_member_id);

-- ========================================
-- VOTE HISTORY/BACKUP TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS wp_mt_votes_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    original_vote_id MEDIUMINT(9) NOT NULL,
    candidate_id BIGINT(20) NOT NULL,
    jury_member_id BIGINT(20) NOT NULL,
    vote_round TINYINT(1) NOT NULL,
    rating TINYINT(2) NOT NULL,
    comments TEXT,
    vote_date DATETIME,
    voting_phase VARCHAR(50),
    backed_up_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    backup_reason VARCHAR(100),
    INDEX idx_original_vote (original_vote_id),
    INDEX idx_backup_time (backed_up_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup table for detailed scores
CREATE TABLE IF NOT EXISTS wp_mt_candidate_scores_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    original_score_id MEDIUMINT(9) NOT NULL,
    candidate_id BIGINT(20) NOT NULL,
    jury_member_id BIGINT(20) NOT NULL,
    courage_score TINYINT(2) NOT NULL DEFAULT 0,
    innovation_score TINYINT(2) NOT NULL DEFAULT 0,
    implementation_score TINYINT(2) NOT NULL DEFAULT 0,
    relevance_score TINYINT(2) NOT NULL DEFAULT 0,
    visibility_score TINYINT(2) NOT NULL DEFAULT 0,
    total_score DECIMAL(4,2) DEFAULT 0,
    evaluation_round TINYINT(1) NOT NULL DEFAULT 1,
    evaluation_date DATETIME,
    comments TEXT,
    voting_phase VARCHAR(50),
    backed_up_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    backup_reason VARCHAR(100),
    INDEX idx_original_score (original_score_id),
    INDEX idx_backup_time (backed_up_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log successful schema update
SELECT 'Vote Reset schema extensions completed successfully!' as status,
       NOW() as completed_at;