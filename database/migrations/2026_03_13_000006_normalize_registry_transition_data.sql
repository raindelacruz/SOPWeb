-- SOPWeb Phase D post-cutover data normalization
-- Align lingering approval-era data values with the registry model.

UPDATE procedure_versions pv
JOIN procedures p ON p.id = pv.procedure_id
SET pv.status = CASE
    WHEN p.current_version_id = pv.id AND p.status NOT IN ('SUPERSEDED', 'RESCINDED', 'ARCHIVED') THEN 'EFFECTIVE'
    WHEN p.status = 'RESCINDED' THEN 'RESCINDED'
    WHEN p.status = 'ARCHIVED' THEN 'ARCHIVED'
    ELSE 'SUPERSEDED'
END
WHERE pv.status = 'APPROVED';

UPDATE procedures
SET current_version_id = NULL
WHERE status IN ('SUPERSEDED', 'RESCINDED', 'ARCHIVED');

UPDATE workflow_actions
SET from_status = 'REGISTERED'
WHERE from_status = 'APPROVED';

UPDATE workflow_actions
SET to_status = 'REGISTERED'
WHERE to_status = 'APPROVED';
