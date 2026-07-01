-- =====================================================================
-- Base de données : DELTA
-- Compatibilité : MySQL 8+, InnoDB, utf8mb4
-- Objet : Plateforme d'audit (ISA/OHADA)
-- =====================================================================

DROP DATABASE IF EXISTS DELTA;
CREATE DATABASE DELTA CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE DELTA;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =====================================================================
-- Tables de sécurité / référentiels
-- =====================================================================

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','associe','manager','auditeur') NOT NULL DEFAULT 'auditeur',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role);

CREATE TABLE entities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sector VARCHAR(100),
  country VARCHAR(80),
  ohada_applicable TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_entities_country ON entities(country);

-- =====================================================================
-- Missions / pilotage
-- =====================================================================

CREATE TABLE engagements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_id INT NOT NULL,
  fiscal_year YEAR NOT NULL,
  start_date DATE,
  end_date DATE,
  partner_id INT NULL,
  manager_id INT NULL,
  materiality DECIMAL(12,2),
  performance_materiality DECIMAL(12,2),
  status ENUM('plan','fieldwork','review','closed') NOT NULL DEFAULT 'plan',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eng_entity  FOREIGN KEY (entity_id)  REFERENCES entities(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_eng_partner FOREIGN KEY (partner_id) REFERENCES users(id)    ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT fk_eng_manager FOREIGN KEY (manager_id) REFERENCES users(id)    ON DELETE SET NULL  ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_eng_entity_fy ON engagements(entity_id, fiscal_year);
CREATE INDEX idx_eng_status ON engagements(status);

-- =====================================================================
-- Feuilles de travail / observations / tests
-- =====================================================================

CREATE TABLE workpapers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  engagement_id INT NOT NULL,
  cycle ENUM('ventes','achats','paie','tresorerie','immobilisations','stocks','autres') NOT NULL,
  title VARCHAR(180) NOT NULL,
  assigned_to INT NULL,
  status ENUM('en_cours','a_reviser','validee') NOT NULL DEFAULT 'en_cours',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wp_eng  FOREIGN KEY (engagement_id) REFERENCES engagements(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_wp_user FOREIGN KEY (assigned_to)   REFERENCES users(id)       ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_wp_engagement ON workpapers(engagement_id);
CREATE INDEX idx_wp_cycle_status ON workpapers(cycle, status);

CREATE TABLE observations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workpaper_id INT NOT NULL,
  description TEXT NOT NULL,
  severity ENUM('mineur','significatif','majeur') NOT NULL,
  proposer_id INT NOT NULL,
  manager_review TEXT NULL,
  partner_review TEXT NULL,
  status ENUM('ouvert','en_revue','clos') NOT NULL DEFAULT 'ouvert',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_obs_wp   FOREIGN KEY (workpaper_id) REFERENCES workpapers(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_obs_user FOREIGN KEY (proposer_id)  REFERENCES users(id)      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_obs_wp_status ON observations(workpaper_id, status);
CREATE INDEX idx_obs_severity ON observations(severity);

CREATE TABLE tests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workpaper_id INT NOT NULL,
  test_type ENUM('benford','dates_atypiques','doublons','analytique','autre') NOT NULL,
  params JSON,
  executed_by INT NULL,
  executed_at DATETIME NULL,
  result_summary JSON,
  findings_level ENUM('ok','attention','anomalie') NOT NULL DEFAULT 'ok',
  CONSTRAINT fk_test_wp   FOREIGN KEY (workpaper_id) REFERENCES workpapers(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_test_user FOREIGN KEY (executed_by)  REFERENCES users(id)      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_tests_type ON tests(test_type);

-- =====================================================================
-- Import écritures (GL)
-- =====================================================================

CREATE TABLE gl_imports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  engagement_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  uploaded_by INT NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gli_eng  FOREIGN KEY (engagement_id) REFERENCES engagements(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gli_user FOREIGN KEY (uploaded_by)   REFERENCES users(id)       ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gl_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gl_import_id INT NOT NULL,
  entry_date DATE,
  journal VARCHAR(50),
  account VARCHAR(50),
  description VARCHAR(255),
  debit  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  ref VARCHAR(100),
  CONSTRAINT fk_gle_import FOREIGN KEY (gl_import_id) REFERENCES gl_imports(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_gle_import ON gl_entries(gl_import_id);
CREATE INDEX idx_gle_account ON gl_entries(account);
CREATE INDEX idx_gle_date ON gl_entries(entry_date);

-- Contrainte GL (MySQL 8+)
ALTER TABLE gl_entries
  ADD CONSTRAINT chk_debit_credit_nonbothzero
  CHECK (debit >= 0 AND credit >= 0 AND (debit > 0 OR credit > 0));

-- =====================================================================
-- Circularisations
-- =====================================================================

CREATE TABLE circularizations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  engagement_id INT NOT NULL,
  type ENUM('clients','banques','fournisseurs') NOT NULL,
  counterparty_name VARCHAR(150) NOT NULL,
  email VARCHAR(180),
  request_date DATE NOT NULL,
  status ENUM('envoye','relance','recu','en_attente') NOT NULL DEFAULT 'en_attente',
  response_file VARCHAR(255) NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  CONSTRAINT fk_circ_eng FOREIGN KEY (engagement_id) REFERENCES engagements(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_circ_status ON circularizations(status);

-- =====================================================================
-- Rapports
-- =====================================================================

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  engagement_id INT NOT NULL,
  report_type ENUM('sans_reserve','avec_reserve','refus') NOT NULL,
  body LONGTEXT,
  generated_pdf VARCHAR(255),
  generated_at DATETIME,
  signed_by_partner INT NULL,
  signature_datetime DATETIME NULL,
  CONSTRAINT fk_rep_eng     FOREIGN KEY (engagement_id)     REFERENCES engagements(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rep_partner FOREIGN KEY (signed_by_partner) REFERENCES users(id)       ON DELETE SET NULL  ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_reports_type ON reports(report_type);

-- =====================================================================
-- Journal d’audit et sauvegardes
-- =====================================================================

CREATE TABLE journals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(255) NOT NULL,
  entity_type VARCHAR(50),
  entity_id INT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_journal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_journal_user   ON journals(user_id);
CREATE INDEX idx_journal_entity ON journals(entity_type, entity_id);

CREATE TABLE backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_path VARCHAR(255) NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_backup_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- Vues (dashboard)
-- =====================================================================

CREATE OR REPLACE VIEW vw_engagement_progress AS
SELECT
  e.id AS engagement_id,
  COUNT(w.id) AS total_wp,
  SUM(w.status = 'validee') AS validated_wp,
  ROUND(100 * SUM(w.status = 'validee') / NULLIF(COUNT(w.id),0), 2) AS progress_pct
FROM engagements e
LEFT JOIN workpapers w ON w.engagement_id = e.id
GROUP BY e.id;

CREATE OR REPLACE VIEW vw_obs_severity_by_engagement AS
SELECT
  e.id AS engagement_id,
  SUM(o.severity = 'mineur') AS mineur,
  SUM(o.severity = 'significatif') AS significatif,
  SUM(o.severity = 'majeur') AS majeur
FROM engagements e
LEFT JOIN workpapers w ON w.engagement_id = e.id
LEFT JOIN observations o ON o.workpaper_id = w.id
GROUP BY e.id;

-- =====================================================================
-- Triggers (journalisation)
-- =====================================================================

DELIMITER $$

CREATE TRIGGER trg_users_after_insert
AFTER INSERT ON users FOR EACH ROW
BEGIN
  INSERT INTO journals(user_id, action, entity_type, entity_id, created_at)
  VALUES (NEW.id, 'user_created', 'user', NEW.id, NOW());
END$$

CREATE TRIGGER trg_entities_after_insert
AFTER INSERT ON entities FOR EACH ROW
BEGIN
  INSERT INTO journals(user_id, action, entity_type, entity_id, created_at)
  VALUES (NULL, 'entity_created', 'entity', NEW.id, NOW());
END$$

CREATE TRIGGER trg_workpapers_after_status_update
AFTER UPDATE ON workpapers FOR EACH ROW
BEGIN
  IF NEW.status <> OLD.status THEN
    INSERT INTO journals(user_id, action, entity_type, entity_id, created_at)
    VALUES (NEW.assigned_to, CONCAT('workpaper_status_', NEW.status), 'workpaper', NEW.id, NOW());
  END IF;
END$$

DELIMITER ;

-- =====================================================================
-- Contraintes additionnelles (MySQL 8+)
-- =====================================================================

ALTER TABLE observations
  ADD CONSTRAINT chk_obs_status CHECK (status IN ('ouvert','en_revue','clos'));

ALTER TABLE workpapers
  ADD CONSTRAINT chk_wp_status CHECK (status IN ('en_cours','a_reviser','validee'));

-- =====================================================================
-- Données de démonstration
-- Remplacez les password_hash par de vrais hash générés en PHP.
-- =====================================================================

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Admin Demo',    'admin@example.com',    '$2y$10$8OeD3n9X0nqk7iE1M8uWQO9m4l2x1Z7q3oXq9bZ8V2k7lqSPy0r2i', 'admin',   'active'),
('Associé Demo',  'associe@example.com',  '$2y$10$8OeD3n9X0nqk7iE1M8uWQO9m4l2x1Z7q3oXq9bZ8V2k7lqSPy0r2i', 'associe', 'active'),
('Manager Demo',  'manager@example.com',  '$2y$10$8OeD3n9X0nqk7iE1M8uWQO9m4l2x1Z7q3oXq9bZ8V2k7lqSPy0r2i', 'manager', 'active'),
('Auditeur 1',    'auditeur1@example.com','$2y$10$8OeD3n9X0nqk7iE1M8uWQO9m4l2x1Z7q3oXq9bZ8V2k7lqSPy0r2i', 'auditeur','active'),
('Auditeur 2',    'auditeur2@example.com','$2y$10$8OeD3n9X0nqk7iE1M8uWQO9m4l2x1Z7q3oXq9bZ8V2k7lqSPy0r2i', 'auditeur','active');

INSERT INTO entities (name, sector, country, ohada_applicable) VALUES
('Société Alpha SA', 'Distribution', 'SN', 1);

INSERT INTO engagements (entity_id, fiscal_year, start_date, end_date, partner_id, manager_id, materiality, performance_materiality, status)
VALUES (1, 2025, '2025-01-10', '2025-03-31', 2, 3, 100000.00, 70000.00, 'fieldwork');

INSERT INTO workpapers (engagement_id, cycle, title, assigned_to, status) VALUES
(1, 'ventes',          'WP Ventes - Tests de coupure',              4, 'en_cours'),
(1, 'achats',          'WP Achats - Confirmation fournisseurs',     5, 'a_reviser'),
(1, 'tresorerie',      'WP Trésorerie - Circularisation banques',   4, 'en_cours'),
(1, 'immobilisations', 'WP Immos - Amortissements',                 5, 'validee');

INSERT INTO observations (workpaper_id, description, severity, proposer_id, status) VALUES
(1, 'Factures de fin d''exercice enregistrées le 02/01 -> risque de coupure.', 'significatif', 4, 'en_revue'),
(2, 'Absence de bon de commande pour 3 échantillons.',                         'mineur',       5, 'ouvert'),
(4, 'Durées d’amortissement non alignées avec la politique groupe.',           'majeur',       5, 'ouvert');

INSERT INTO gl_imports (engagement_id, filename, uploaded_by) VALUES
(1, 'gl_2025.csv', 4),
(1, 'gl_2024.csv', 5);

INSERT INTO gl_entries (gl_import_id, entry_date, journal, account, description, debit, credit, ref) VALUES
(1, '2025-01-02', 'VE', '701100', 'Vente produit A',      0.00,   150000.00, 'FA2025-0001'),
(1, '2025-01-03', 'AC', '607000', 'Achat matière X',  50000.00,        0.00, 'FA2025-0002'),
(1, '2025-01-03', 'BQ', '512000', 'Encaissement client', 150000.00,     0.00, 'REC-0001'),
(2, '2024-12-30', 'VE', '701100', 'Vente produit B',      0.00,   120000.00, 'FA2024-0456'),
(2, '2024-12-29', 'AC', '607000', 'Achat matière X',  40000.00,        0.00, 'FA2024-0312');

INSERT INTO tests (workpaper_id, test_type, params, executed_by, executed_at, result_summary, findings_level)
VALUES
(1, 'benford', JSON_OBJECT('gl_import_id', 1), 4, NOW(),
 JSON_OBJECT('total', 3, 'observed', JSON_OBJECT('1',1,'5',1,'9',1), 'chi2', 4.2), 'ok');

-- Circularisations — CORRIGÉ : génération de tokens uniques (UUID sans tirets)
INSERT INTO circularizations (engagement_id, type, counterparty_name, email, request_date, status, token) VALUES
(1, 'clients',  'Client Delta',   'client.delta@example.com',  '2025-02-01', 'envoye',     REPLACE(UUID(),'-','')),
(1, 'banques',  'Banque Centrale','contact@banque.example.com','2025-02-01', 'en_attente', REPLACE(UUID(),'-',''));

INSERT INTO reports (engagement_id, report_type, body, generated_pdf, generated_at)
VALUES (1, 'sans_reserve', 'Brouillon de rapport sans réserve pour FY 2025.', NULL, NULL);

INSERT INTO journals (user_id, action, entity_type, entity_id, created_at)
VALUES (1, 'seed_data', 'system', 0, NOW());

-- =====================================================================
-- Procédure utilitaire
-- =====================================================================

DELIMITER $$

CREATE PROCEDURE deactivate_user(IN p_user_id INT)
BEGIN
  UPDATE users SET status='disabled' WHERE id = p_user_id;
  INSERT INTO journals(user_id, action, entity_type, entity_id, created_at)
  VALUES (p_user_id, 'user_deactivated', 'user', p_user_id, NOW());
END$$

DELIMITER ;

-- =====================================================================
-- Fin
-- =====================================================================
