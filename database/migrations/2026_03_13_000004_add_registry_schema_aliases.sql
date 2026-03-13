-- SOPWeb registry schema alignment
-- Adds additive registry-native aliases while preserving approval-era columns
-- and workflow table compatibility during the migration period.

ALTER TABLE procedure_versions
    ADD COLUMN registration_date DATE NULL AFTER effective_date,
    ADD COLUMN registered_by INT UNSIGNED NULL AFTER created_by;

UPDATE procedure_versions
SET registration_date = COALESCE(registration_date, approval_date, effective_date)
WHERE registration_date IS NULL;

UPDATE procedure_versions
SET registered_by = COALESCE(registered_by, approved_by, created_by)
WHERE registered_by IS NULL;

ALTER TABLE workflow_actions
    ADD COLUMN lifecycle_action_type VARCHAR(100) NULL AFTER action_type;

UPDATE workflow_actions
SET lifecycle_action_type = CASE action_type
    WHEN 'PDMS_CREATE' THEN 'PDMS_REGISTER_PROCEDURE'
    WHEN 'PDMS_ISSUE' THEN 'PDMS_REGISTER_REVISION'
    WHEN 'PDMS_PROMOTE_EFFECTIVE' THEN 'PDMS_MARK_EFFECTIVE'
    WHEN 'PDMS_TRANSITION' THEN 'PDMS_MARK_EFFECTIVE'
    WHEN 'PDMS_REPLACED' THEN 'PDMS_REGISTERED_REPLACEMENT'
    WHEN 'PDMS_SUPERSEDED' THEN 'PDMS_REGISTERED_SUPERSESSION'
    ELSE action_type
END
WHERE lifecycle_action_type IS NULL;
