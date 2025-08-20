-- Fix for Jury Member Usernames with Leading Dots
-- Date: 2025-08-20
-- Issue: Several jury member usernames have dots at the beginning
-- Solution: Remove leading dots from usernames

-- BACKUP: First, let's see the current state
SELECT ID, user_login, display_name 
FROM wp_users 
WHERE user_login LIKE '.%'
ORDER BY ID;

-- Create a backup table (optional - for safety)
-- CREATE TABLE wp_users_backup_20250820 AS SELECT * FROM wp_users;

-- FIX: Remove leading dots from usernames
-- These are the specific updates needed:

UPDATE wp_users 
SET user_login = 'torsten.tomczak' 
WHERE ID = 25 AND user_login = '.....torsten.tomczak';

UPDATE wp_users 
SET user_login = 'andreas.herrmann' 
WHERE ID = 24 AND user_login = '..andreas.herrmann';

UPDATE wp_users 
SET user_login = 'oliver.gassmann' 
WHERE ID = 29 AND user_login = '..oliver.gassmann';

UPDATE wp_users 
SET user_login = 'astrid.fontaine' 
WHERE ID = 27 AND user_login = '.astrid.fontaine';

UPDATE wp_users 
SET user_login = 'kjell.gruner' 
WHERE ID = 31 AND user_login = '.kjell.gruner';

UPDATE wp_users 
SET user_login = 'zheng.han' 
WHERE ID = 32 AND user_login = '..zheng.han';

UPDATE wp_users 
SET user_login = 'wolfgang.jenewein' 
WHERE ID = 33 AND user_login = '..wolfgang.jenewein';

UPDATE wp_users 
SET user_login = 'nikolaus.lang' 
WHERE ID = 35 AND user_login = '..nikolaus.lang';

UPDATE wp_users 
SET user_login = 'philipp.rosler' 
WHERE ID = 38 AND user_login = '.philipp.rosler';

-- VERIFY: Check the results
SELECT ID, user_login, display_name 
FROM wp_users 
WHERE ID IN (24, 25, 27, 29, 31, 32, 33, 35, 38)
ORDER BY ID;

-- ROLLBACK SCRIPT (if needed):
-- UPDATE wp_users SET user_login = '.....torsten.tomczak' WHERE ID = 25;
-- UPDATE wp_users SET user_login = '..andreas.herrmann' WHERE ID = 24;
-- UPDATE wp_users SET user_login = '..oliver.gassmann' WHERE ID = 29;
-- UPDATE wp_users SET user_login = '.astrid.fontaine' WHERE ID = 27;
-- UPDATE wp_users SET user_login = '.kjell.gruner' WHERE ID = 31;
-- UPDATE wp_users SET user_login = '..zheng.han' WHERE ID = 32;
-- UPDATE wp_users SET user_login = '..wolfgang.jenewein' WHERE ID = 33;
-- UPDATE wp_users SET user_login = '..nikolaus.lang' WHERE ID = 35;
-- UPDATE wp_users SET user_login = '.philipp.rosler' WHERE ID = 38;
