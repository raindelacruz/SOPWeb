-- SOPWeb Phase D cutover preflight
-- Run these checks before applying 2026_03_13_000005_drop_approval_compatibility_columns.sql.

SELECT
    TABLE_NAME,
    COLUMN_NAME,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('procedure_versions', 'workflow_actions')
  AND COLUMN_NAME IN (
      'approval_date',
      'approved_by',
      'registration_date',
      'registered_by',
      'action_type',
      'lifecycle_action_type',
      'status'
  )
ORDER BY TABLE_NAME, COLUMN_NAME;

SELECT
    COUNT(*) AS missing_registration_date
FROM procedure_versions
WHERE registration_date IS NULL;

SELECT
    COUNT(*) AS missing_registered_by
FROM procedure_versions
WHERE registered_by IS NULL;

SELECT
    COUNT(*) AS missing_lifecycle_action_type
FROM workflow_actions
WHERE lifecycle_action_type IS NULL
   OR lifecycle_action_type = '';

SELECT
    status,
    COUNT(*) AS version_count
FROM procedure_versions
GROUP BY status
ORDER BY status;

SELECT
    lifecycle_action_type,
    COUNT(*) AS action_count
FROM workflow_actions
GROUP BY lifecycle_action_type
ORDER BY lifecycle_action_type;
