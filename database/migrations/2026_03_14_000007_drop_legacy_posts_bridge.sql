-- SOPWeb legacy bridge retirement
-- Drops the legacy posts archive surface and the remaining schema bridge
-- columns now that runtime authoring and read flows are PDMS-only.

DROP TABLE IF EXISTS posts;

ALTER TABLE procedure_versions
    DROP COLUMN legacy_post_id;

ALTER TABLE procedures
    DROP COLUMN legacy_post_id;
