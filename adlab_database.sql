-- ============================================
--  AD LAB — CVD Detection System
--  MySQL Database Setup
--  Patient Health Assessments
-- ============================================

-- 1. CREATE DATABASE
CREATE DATABASE IF NOT EXISTS adlab_cvd;
USE adlab_cvd;

-- 2. PATIENT ASSESSMENTS TABLE
CREATE TABLE IF NOT EXISTS patient_assessments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_name    VARCHAR(100),
    age             INT NOT NULL,
    gender          ENUM('Male', 'Female') NOT NULL,
    height_cm       FLOAT NOT NULL,
    weight_kg       FLOAT NOT NULL,
    systolic_bp     INT NOT NULL,
    diastolic_bp    INT NOT NULL,
    cholesterol     ENUM('Normal', 'Above Normal', 'Well Above Normal') NOT NULL,
    glucose         ENUM('Normal', 'Above Normal', 'Well Above Normal') NOT NULL,
    smoke           TINYINT(1) DEFAULT 0,
    alcohol         TINYINT(1) DEFAULT 0,
    active          TINYINT(1) DEFAULT 1,

    -- Engineered Features (auto-calculated)
    bmi             FLOAT GENERATED ALWAYS AS (weight_kg / ((height_cm / 100) * (height_cm / 100))) STORED,
    pulse_pressure  INT GENERATED ALWAYS AS (systolic_bp - diastolic_bp) STORED,
    hypertension    TINYINT(1) GENERATED ALWAYS AS (IF(systolic_bp >= 140 OR diastolic_bp >= 90, 1, 0)) STORED,

    -- Prediction Result
    risk_score      INT,
    risk_level      ENUM('Low', 'Moderate', 'High'),
    cvd_prediction  TINYINT(1),   -- 1 = CVD detected, 0 = No CVD

    -- Metadata
    assessed_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    assessed_by     VARCHAR(100) DEFAULT 'AD LAB System'
);

-- 3. SAMPLE DATA
INSERT INTO patient_assessments (
    patient_name, age, gender, height_cm, weight_kg,
    systolic_bp, diastolic_bp, cholesterol, glucose,
    smoke, alcohol, active, risk_score, risk_level, cvd_prediction
) VALUES
('John Doe',      55, 'Male',   170, 85, 145, 95, 'Above Normal',      'Normal',       1, 0, 0, 12, 'High',     1),
('Jane Smith',    42, 'Female', 162, 68, 118, 78, 'Normal',             'Normal',       0, 0, 1,  3, 'Low',      0),
('Ravi Kumar',    60, 'Male',   168, 90, 155, 100,'Well Above Normal',  'Above Normal', 1, 1, 0, 16, 'High',     1),
('Priya Sharma',  35, 'Female', 158, 60, 110, 72, 'Normal',             'Normal',       0, 0, 1,  1, 'Low',      0),
('Amit',      50, 'Male',   172, 78, 135, 88, 'Above Normal',       'Normal',       0, 1, 1,  7, 'Moderate', 0);

-- 4. VIEW: Full Assessment Summary
CREATE OR REPLACE VIEW assessment_summary AS
SELECT
    id,
    COALESCE(patient_name, 'Anonymous') AS patient_name,
    age, gender,
    ROUND(bmi, 1)        AS bmi,
    systolic_bp, diastolic_bp,
    pulse_pressure,
    hypertension,
    cholesterol, glucose,
    smoke, alcohol, active,
    risk_score, risk_level,
    cvd_prediction,
    assessed_at
FROM patient_assessments
ORDER BY assessed_at DESC;

-- 5. USEFUL QUERIES

-- Get all high risk patients
-- SELECT * FROM patient_assessments WHERE risk_level = 'High';

-- Count by risk level
-- SELECT risk_level, COUNT(*) AS total FROM patient_assessments GROUP BY risk_level;

-- Average BMI of CVD positive patients
-- SELECT AVG(bmi) AS avg_bmi FROM patient_assessments WHERE cvd_prediction = 1;

-- Hypertensive patients with CVD
-- SELECT * FROM patient_assessments WHERE hypertension = 1 AND cvd_prediction = 1;

SELECT 'AD LAB Database Setup Complete ✅' AS status;
