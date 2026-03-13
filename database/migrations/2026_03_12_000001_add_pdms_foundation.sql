-- SOPWeb PDMS foundation schema
-- Phase 1 only: additive tables to support procedure masters, versions,
-- relationships, and workflow without removing legacy posts fields.

CREATE TABLE IF NOT EXISTS procedures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_code VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    owner_office VARCHAR(100) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
    current_version_id INT UNSIGNED NULL,
    legacy_post_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_procedures_procedure_code (procedure_code),
    KEY idx_procedures_current_version_id (current_version_id),
    KEY idx_procedures_legacy_post_id (legacy_post_id),
    KEY idx_procedures_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procedure_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_id INT UNSIGNED NOT NULL,
    legacy_post_id INT UNSIGNED NULL,
    version_number VARCHAR(50) NOT NULL,
    document_number VARCHAR(100) NULL,
    title VARCHAR(255) NOT NULL,
    summary_of_change TEXT NULL,
    change_type VARCHAR(50) NOT NULL DEFAULT 'NEW',
    effective_date DATE NULL,
    approval_date DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'DRAFT',
    file_path VARCHAR(255) NULL,
    based_on_version_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    approved_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_procedure_versions_procedure_id (procedure_id),
    KEY idx_procedure_versions_legacy_post_id (legacy_post_id),
    KEY idx_procedure_versions_based_on_version_id (based_on_version_id),
    KEY idx_procedure_versions_status (status),
    KEY idx_procedure_versions_effective_date (effective_date),
    CONSTRAINT fk_procedure_versions_procedure
        FOREIGN KEY (procedure_id) REFERENCES procedures(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_procedure_versions_based_on
        FOREIGN KEY (based_on_version_id) REFERENCES procedure_versions(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_relationships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_version_id INT UNSIGNED NOT NULL,
    target_version_id INT UNSIGNED NOT NULL,
    relationship_type VARCHAR(50) NOT NULL,
    affected_sections TEXT NULL,
    remarks TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_document_relationships_source_version_id (source_version_id),
    KEY idx_document_relationships_target_version_id (target_version_id),
    KEY idx_document_relationships_relationship_type (relationship_type),
    CONSTRAINT fk_document_relationships_source
        FOREIGN KEY (source_version_id) REFERENCES procedure_versions(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_document_relationships_target
        FOREIGN KEY (target_version_id) REFERENCES procedure_versions(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_actions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_version_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NULL,
    acted_by INT UNSIGNED NULL,
    remarks TEXT NULL,
    acted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_workflow_actions_procedure_version_id (procedure_version_id),
    KEY idx_workflow_actions_action_type (action_type),
    KEY idx_workflow_actions_acted_at (acted_at),
    CONSTRAINT fk_workflow_actions_procedure_version
        FOREIGN KEY (procedure_version_id) REFERENCES procedure_versions(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add the circular dependency after procedure_versions exists.
ALTER TABLE procedures
    ADD CONSTRAINT fk_procedures_current_version
        FOREIGN KEY (current_version_id) REFERENCES procedure_versions(id)
        ON DELETE SET NULL ON UPDATE CASCADE;
