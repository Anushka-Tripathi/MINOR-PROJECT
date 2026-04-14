-- ============================================================
--  MediCare Pro — Password Fix Patch
--  Run this if you already imported schema.sql and login fails.
--  This resets all demo account passwords to plain "admin123"
--  which login.php will auto-upgrade to bcrypt on first login.
-- ============================================================

USE medicare_pro;

UPDATE admins  SET password_hash = 'admin123' WHERE email = 'admin@medicare.com';
UPDATE doctors SET password_hash = 'admin123' WHERE email IN ('doctor@medicare.com','priya@medicare.com','suresh@medicare.com','kavitha@medicare.com');
UPDATE patients SET password_hash = 'admin123' WHERE email IN ('patient@medicare.com','rahul@medicare.com');

SELECT 'Passwords reset. Login with admin123 — system will auto-upgrade to bcrypt.' AS status;
