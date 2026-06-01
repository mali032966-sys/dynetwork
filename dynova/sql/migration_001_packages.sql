-- =====================================================================
-- DYNOVA NETWORK – Migration 001: Task Packages
-- Adds the catalog (task_packages) and user activations (user_packages).
-- Safe to re-run on existing installations.
-- =====================================================================

CREATE TABLE IF NOT EXISTS task_packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  tier VARCHAR(40) NOT NULL DEFAULT 'standard',
  emoji VARCHAR(10) NOT NULL DEFAULT '',
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  daily_tasks INT UNSIGNED NOT NULL DEFAULT 5,
  daily_earning DECIMAL(10,2) NOT NULL DEFAULT 0,
  validity_days INT UNSIGNED NOT NULL DEFAULT 30,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  package_id INT UNSIGNED NOT NULL,
  daily_tasks INT UNSIGNED NOT NULL,
  daily_earning DECIMAL(10,2) NOT NULL,
  price_paid DECIMAL(12,2) NOT NULL,
  activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  INDEX idx_user (user_id),
  INDEX idx_active (user_id, status, expires_at),
  CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_up_pkg  FOREIGN KEY (package_id) REFERENCES task_packages(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default packages (only if catalog is empty)
INSERT INTO task_packages
  (name, tier, emoji, price, daily_tasks, daily_earning, validity_days, is_featured, sort_order)
SELECT * FROM (
  SELECT 'Starter'  AS name,'starter'  AS tier,'🚀' AS emoji,500    AS price,5  AS daily_tasks,35   AS daily_earning,30 AS validity_days,0 AS is_featured,1 AS sort_order UNION ALL
  SELECT 'Silver'   ,'silver'   ,'🥈' ,2000   ,10 ,70   ,30 ,0 ,2 UNION ALL
  SELECT 'Gold'     ,'gold'     ,'🥇' ,5000   ,21 ,147  ,30 ,1 ,3 UNION ALL
  SELECT 'Platinum' ,'platinum' ,'💠' ,10000  ,35 ,280  ,30 ,0 ,4 UNION ALL
  SELECT 'Diamond'  ,'diamond'  ,'💎' ,25000  ,60 ,600  ,30 ,0 ,5
) AS d
WHERE NOT EXISTS (SELECT 1 FROM task_packages LIMIT 1);
