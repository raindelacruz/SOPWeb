-- SOPWeb Phase D hard cutover
-- Removes approval-era compatibility columns after registry-native aliases
-- and registry-only behavior are fully in place.

ALTER TABLE procedure_versions
    MODIFY COLUMN registration_date DATE NOT NULL,
    MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'REGISTERED',
    DROP COLUMN approval_date,
    DROP COLUMN approved_by;

ALTER TABLE workflow_actions
    MODIFY COLUMN lifecycle_action_type VARCHAR(100) NOT NULL,
    DROP INDEX idx_workflow_actions_action_type,
    ADD KEY idx_workflow_actions_lifecycle_action_type (lifecycle_action_type),
    DROP COLUMN action_type;
