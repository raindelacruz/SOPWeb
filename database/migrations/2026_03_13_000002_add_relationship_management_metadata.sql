-- SOPWeb PDMS relationship management metadata
-- Adds explicit tracking for sync-managed relationship rows so cleanup and
-- re-sync behavior do not depend on parsing remarks text.

ALTER TABLE document_relationships
    ADD COLUMN management_source VARCHAR(50) NULL AFTER remarks,
    ADD KEY idx_document_relationships_management_source (management_source);

UPDATE document_relationships
SET management_source = 'LEGACY_SYNC',
    remarks = NULLIF(TRIM(REPLACE(COALESCE(remarks, ''), '[LEGACY_SYNC]', '')), '')
WHERE remarks LIKE '[LEGACY_SYNC]%';
