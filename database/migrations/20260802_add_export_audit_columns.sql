ALTER TABLE activity_logs
    ADD COLUMN user_name VARCHAR(120) NULL AFTER ip_address,
    ADD COLUMN user_role VARCHAR(30) NULL AFTER user_name,
    ADD COLUMN module_key VARCHAR(30) NULL AFTER user_role,
    ADD COLUMN export_format VARCHAR(10) NULL AFTER module_key,
    ADD COLUMN file_name VARCHAR(255) NULL AFTER export_format,
    ADD COLUMN record_count INT UNSIGNED NULL AFTER file_name,
    ADD COLUMN filters_json TEXT NULL AFTER record_count,
    ADD COLUMN user_agent VARCHAR(1000) NULL AFTER filters_json;
