-- Seed invoice theme defaults for existing deployments.
INSERT INTO app_settings (setting_key, setting_value)
SELECT 'invoice_theme', 'classic'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM app_settings WHERE setting_key = 'invoice_theme'
);

INSERT INTO app_settings (setting_key, setting_value)
SELECT 'invoice_accent_color', '#1d6f5f'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM app_settings WHERE setting_key = 'invoice_accent_color'
);
