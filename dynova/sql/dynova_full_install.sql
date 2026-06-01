-- =====================================================================
--  DYNOVA NETWORK – Full install schema (current as of Jan 2026)
--  Single-file installer for shared hosting (Hostinger / cPanel).
--  Engine: InnoDB · Charset: utf8mb4 · Currency: PKR
--
--  HOW TO USE (Hostinger / phpMyAdmin):
--    1.  cPanel → MySQL Databases → create DB + user → grant ALL privileges.
--    2.  cPanel → phpMyAdmin → select that DB → Import → upload this file.
--    3.  Edit  app/config.php  with the DB host/name/user/pass.
--    4.  Visit your domain – login at /?r=auth/login.
--    5.  Default admin: admin@dynova.com / password   (change immediately).
-- =====================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS salaries;
DROP TABLE IF EXISTS salary_ranks;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS withdrawals;
DROP TABLE IF EXISTS deposits;
DROP TABLE IF EXISTS user_packages;
DROP TABLE IF EXISTS joining_bonuses;
DROP TABLE IF EXISTS task_completions;
DROP TABLE IF EXISTS task_packages;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS admin_settings;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------ users
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL DEFAULT '',
  whatsapp VARCHAR(32) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  referral_code VARCHAR(32) NOT NULL UNIQUE,
  referred_by INT UNSIGNED NULL,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  task_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  referral_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  salary_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  deposit_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  rank_name VARCHAR(40) NOT NULL DEFAULT '',
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  remember_token VARCHAR(120) NULL,
  joining_bonus_received TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_referred_by (referred_by),
  CONSTRAINT fk_users_ref FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ admins
CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ admin_settings (key/value)
CREATE TABLE admin_settings (
  setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ payment_methods
CREATE TABLE payment_methods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  account_title VARCHAR(120) NOT NULL,
  account_number VARCHAR(60) NOT NULL,
  instructions TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ tasks
CREATE TABLE tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  video_url VARCHAR(500) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ task_completions
CREATE TABLE task_completions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  task_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  reward DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_task (task_id),
  INDEX idx_user_day (user_id, created_at),
  CONSTRAINT fk_tc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tc_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ task_packages
CREATE TABLE task_packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  tier VARCHAR(40) NOT NULL DEFAULT 'standard',
  emoji VARCHAR(10) NOT NULL DEFAULT '',
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  daily_tasks INT UNSIGNED NOT NULL DEFAULT 5,
  earning_per_task DECIMAL(10,2) NOT NULL DEFAULT 0,
  daily_earning DECIMAL(10,2) NOT NULL DEFAULT 0,
  validity_days INT UNSIGNED NOT NULL DEFAULT 36500,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  min_withdrawal_ladder VARCHAR(255) NOT NULL DEFAULT '1500,7000,15000,35000,100000,200000',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ user_packages
CREATE TABLE user_packages (
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

-- ------------------------------------------------------ joining_bonuses (per-package)
CREATE TABLE joining_bonuses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED NOT NULL,
  referrer_bonus DECIMAL(10,2) NOT NULL DEFAULT 0,
  invitee_bonus  DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_package (package_id),
  CONSTRAINT fk_jb_pkg FOREIGN KEY (package_id) REFERENCES task_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ deposits
CREATE TABLE deposits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(40) NOT NULL,
  transaction_id VARCHAR(120) NOT NULL,
  sender_account VARCHAR(60) NULL,
  screenshot VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  CONSTRAINT fk_dep_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ withdrawals
CREATE TABLE withdrawals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(40) NOT NULL,
  account_number VARCHAR(60) NOT NULL,
  account_title VARCHAR(120) NOT NULL,
  status ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  CONSTRAINT fk_wd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ referrals (ledger of paid commissions)
CREATE TABLE referrals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  source_user_id INT UNSIGNED NOT NULL,
  level TINYINT NOT NULL,
  source_completion_id INT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  percent DECIMAL(6,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_source (source_user_id),
  CONSTRAINT fk_ref_user   FOREIGN KEY (user_id)        REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ref_source FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ salary_ranks (per-level requirements)
CREATE TABLE salary_ranks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  emoji VARCHAR(10) NOT NULL DEFAULT '',
  min_referrals  INT UNSIGNED NOT NULL DEFAULT 0,
  min_l1_members INT UNSIGNED NOT NULL DEFAULT 0,
  min_l2_members INT UNSIGNED NOT NULL DEFAULT 0,
  min_l3_members INT UNSIGNED NOT NULL DEFAULT 0,
  min_business    DECIMAL(14,2) NOT NULL DEFAULT 0,
  min_l1_business DECIMAL(14,2) NOT NULL DEFAULT 0,
  min_l2_business DECIMAL(14,2) NOT NULL DEFAULT 0,
  min_l3_business DECIMAL(14,2) NOT NULL DEFAULT 0,
  monthly_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ salaries (paid ledger)
CREATE TABLE salaries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  rank_name VARCHAR(40) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  week_ending DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  UNIQUE KEY uniq_user_week (user_id, week_ending),
  CONSTRAINT fk_sal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ transactions (full ledger)
CREATE TABLE transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('deposit','task','referral','salary','withdrawal','admin_adjust') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  meta VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_type (type),
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Admin (email: admin@dynova.com, password: password) – CHANGE AFTER FIRST LOGIN
INSERT INTO admins (email, password_hash, name) VALUES
  ('admin@dynova.com', '$2y$10$0Pxa5xTE/cdZSJ./WvsGjeyrg2t1.UmRsEzwlh70KtHR8gO9ERgmC', 'Administrator');

-- Default settings
INSERT INTO admin_settings (setting_key, setting_value) VALUES
  ('referral_l1', '10'),
  ('referral_l2', '5'),
  ('referral_l3', '2.5'),
  ('min_withdrawal', '1500'),
  ('site_name', 'DYNOVA NETWORK'),
  ('site_tagline', 'Rate. Earn. Refer.');

-- Default payment methods (edit account numbers in admin → Settings)
INSERT INTO payment_methods (name, account_title, account_number, instructions, is_active) VALUES
  ('JazzCash',      'Dynova Network', '03001234567',                 'Send the exact amount and copy the Transaction ID (TID) from the SMS.', 1),
  ('EasyPesa',      'Dynova Network', '03451234567',                 'Send the exact amount and paste the EasyPesa TID below.', 1),
  ('Bank Transfer', 'Dynova Network', 'PK00MEZN0000000000000000',    'Send the exact amount via IBFT (Raast / online banking) and paste the bank reference number below. Use the IBAN exactly as shown.', 1);

-- Sample tasks (per-task reward now comes from each user's active package)
INSERT INTO tasks (title, video_url, description, is_active) VALUES
  ('iPhone 15 Pro – Product Review',  'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Watch the review and rate the production quality.', 1),
  ('Samsung Galaxy S24 – First Look', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate honestly from 1 to 5 stars.', 1),
  ('Best Budget Phones 2026',         'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate content usefulness.', 1),
  ('Top 10 Tech Gadgets',             'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate the presentation.', 1),
  ('Smart Home Setup Guide',          'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate the tutorial quality.', 1);

-- Default task packages (admin enters daily_tasks + earning_per_task;
-- daily_earning is derived = daily_tasks × earning_per_task and stored for fast reads)
INSERT INTO task_packages
  (name, tier, emoji, price, daily_tasks, earning_per_task, daily_earning,
   validity_days, is_featured, sort_order, min_withdrawal_ladder)
VALUES
  ('Starter',  'starter',  '', 500,    5,  7,  35,  36500, 0, 1, '1500,7000,15000,35000,100000,200000'),
  ('Silver',   'silver',   '', 2000,   10, 7,  70,  36500, 0, 2, '1500,7000,15000,35000,100000,200000'),
  ('Gold',     'gold',     '', 5000,   21, 7,  147, 36500, 1, 3, '1500,7000,15000,35000,100000,200000'),
  ('Platinum', 'platinum', '', 10000,  35, 8,  280, 36500, 0, 4, '1500,7000,15000,35000,100000,200000'),
  ('Diamond',  'diamond',  '', 25000,  60, 10, 600, 36500, 0, 5, '1500,7000,15000,35000,100000,200000');

-- Joining bonuses tied to each package (referrer / invitee, both one-time)
INSERT INTO joining_bonuses (package_id, referrer_bonus, invitee_bonus, is_active) VALUES
  (1, 0,   50,   1),
  (2, 50,  100,  1),
  (3, 100, 300,  1),
  (4, 200, 600,  1),
  (5, 500, 1500, 1);

-- Salary ranks (monthly payouts, per-level team requirements)
INSERT INTO salary_ranks
  (name, emoji, min_referrals,
   min_l1_members, min_l2_members, min_l3_members,
   min_business, min_l1_business, min_l2_business, min_l3_business,
   monthly_salary, sort_order)
VALUES
  ('Bronze',  '🥉', 6,   2,  2,  2,  10000,  4000,   3000,   3000,   2000,  1),
  ('Silver',  '🥈', 20,  8,  6,  6,  50000,  20000,  15000,  15000,  8000,  2),
  ('Gold',    '🥇', 50,  20, 15, 15, 200000, 80000,  60000,  60000,  20000, 3),
  ('Diamond', '💎', 100, 40, 30, 30, 500000, 200000, 150000, 150000, 48000, 4);
