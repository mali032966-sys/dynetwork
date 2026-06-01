-- ====================================================================
--  Migration 002 – Task reward is now driven by the user's active
--  package (`task_packages.earning_per_task`), so the per-task amount
--  no longer needs to be stored on individual tasks.
--
--  Safe to re-run: drops the column only if it still exists.
--  Historical earnings on `task_completions.reward` are preserved.
-- ====================================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tasks'
      AND COLUMN_NAME  = 'reward'
);
SET @sql := IF(@col > 0, 'ALTER TABLE tasks DROP COLUMN reward', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
