-- ========================================
-- Mobility Trailblazers - MINIMAL Database Initialization
-- Only creates database, users, and plugin tables
-- NO references to WordPress tables
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

-- Use the database
USE mobility_trailblazers;

-- ========================================
-- CREATE PLUGIN-SPECIFIC TABLES ONLY
-- ========================================

-- Jury voting records table
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
    -- 5 evaluation criteria from documentation
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
-- CREATE TRIGGERS FOR AUTOMATIC SCORE CALCULATION
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
-- SET TIMEZONE AND FINAL SETUP
-- ========================================

-- Set timezone for DACH region
SET time_zone = '+01:00';

-- Analyze tables for performance
ANALYZE TABLE mt_mt_votes, mt_mt_public_votes, mt_mt_candidate_scores;

-- Final status
SELECT 'Mobility Trailblazers database initialization completed!' as status,
       'Plugin tables created successfully' as result,
       'Next: Install WordPress, then activate plugin' as next_step;