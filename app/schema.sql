-- FeeBook — schema
-- Cameroon training-centre fee collection and student records.
-- Money is stored as whole FCFA (XAF has no minor unit). Never use FLOAT for money.
--
-- Load with:  mysql -u root -p < app/schema.sql

CREATE DATABASE IF NOT EXISTS feebook
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE feebook;

-- An institution is one training centre / driving school.
-- Present from day one so the single-tenant demo becomes multi-tenant
-- without a migration.
CREATE TABLE institutions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)  NOT NULL,
    phone           VARCHAR(30)   NOT NULL DEFAULT '',
    address         VARCHAR(255)  NOT NULL DEFAULT '',
    receipt_prefix  VARCHAR(8)    NOT NULL DEFAULT 'FB',
    next_receipt_no INT UNSIGNED  NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id  INT UNSIGNED  NOT NULL,
    full_name       VARCHAR(120)  NOT NULL,
    email           VARCHAR(190)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    role            ENUM('owner','staff') NOT NULL DEFAULT 'staff',
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_institution (institution_id),
    CONSTRAINT fk_users_institution FOREIGN KEY (institution_id)
        REFERENCES institutions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A programme is a course the centre sells, with a standard fee.
CREATE TABLE programmes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id  INT UNSIGNED  NOT NULL,
    name            VARCHAR(150)  NOT NULL,
    fee_fcfa        INT UNSIGNED  NOT NULL DEFAULT 0,
    duration_label  VARCHAR(60)   NOT NULL DEFAULT '',
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_programmes_institution (institution_id, is_active),
    CONSTRAINT fk_programmes_institution FOREIGN KEY (institution_id)
        REFERENCES institutions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id  INT UNSIGNED  NOT NULL,
    student_code    VARCHAR(20)   NOT NULL,
    first_name      VARCHAR(80)   NOT NULL,
    last_name       VARCHAR(80)   NOT NULL,
    phone           VARCHAR(30)   NOT NULL DEFAULT '',
    email           VARCHAR(190)  NOT NULL DEFAULT '',
    gender          ENUM('F','M','')  NOT NULL DEFAULT '',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_students_code (institution_id, student_code),
    KEY idx_students_name (institution_id, last_name, first_name),
    KEY idx_students_phone (institution_id, phone),
    CONSTRAINT fk_students_institution FOREIGN KEY (institution_id)
        REFERENCES institutions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- One student enrolled on one programme for one intake.
-- agreed_fee_fcfa is copied from the programme at enrolment time so that
-- later price changes never rewrite what an existing student owes.
CREATE TABLE enrolments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id  INT UNSIGNED  NOT NULL,
    student_id      INT UNSIGNED  NOT NULL,
    programme_id    INT UNSIGNED  NOT NULL,
    intake_label    VARCHAR(60)   NOT NULL DEFAULT '',
    agreed_fee_fcfa INT UNSIGNED  NOT NULL,
    status          ENUM('active','completed','dropped') NOT NULL DEFAULT 'active',
    started_on      DATE          NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_enrolments_student (student_id),
    KEY idx_enrolments_lookup (institution_id, status),
    CONSTRAINT fk_enrolments_institution FOREIGN KEY (institution_id)
        REFERENCES institutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrolments_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrolments_programme FOREIGN KEY (programme_id)
        REFERENCES programmes(id)
) ENGINE=InnoDB;

-- Payments are APPEND-ONLY. A mistake is corrected by inserting a
-- 'reversal' row pointing at the original, never by UPDATE or DELETE.
-- This is what makes the record trustworthy when a student disputes it.
CREATE TABLE payments (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id     INT UNSIGNED NOT NULL,
    enrolment_id       INT UNSIGNED NOT NULL,
    entry_type         ENUM('payment','reversal') NOT NULL DEFAULT 'payment',
    reverses_payment_id INT UNSIGNED NULL,
    amount_fcfa        INT UNSIGNED NOT NULL,
    method             ENUM('cash','momo','orange','bank','cheque') NOT NULL DEFAULT 'cash',
    reference          VARCHAR(80)  NOT NULL DEFAULT '',
    paid_on            DATE         NOT NULL,
    note               VARCHAR(255) NOT NULL DEFAULT '',
    receipt_no         VARCHAR(30)  NOT NULL,
    receipt_code       CHAR(8)      NOT NULL,
    recorded_by        INT UNSIGNED NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payments_receipt_no (institution_id, receipt_no),
    UNIQUE KEY uq_payments_receipt_code (receipt_code),
    KEY idx_payments_enrolment (enrolment_id),
    KEY idx_payments_date (institution_id, paid_on),
    CONSTRAINT fk_payments_institution FOREIGN KEY (institution_id)
        REFERENCES institutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_enrolment FOREIGN KEY (enrolment_id)
        REFERENCES enrolments(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_reverses FOREIGN KEY (reverses_payment_id)
        REFERENCES payments(id),
    CONSTRAINT fk_payments_user FOREIGN KEY (recorded_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
