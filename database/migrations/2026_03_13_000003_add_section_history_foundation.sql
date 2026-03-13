-- SOPWeb PDMS section history foundation
-- Adds additive tables for structured section lineage while preserving the
-- existing affected_sections text bridge during the migration period.

CREATE TABLE IF NOT EXISTS procedure_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_id INT UNSIGNED NOT NULL,
    section_key VARCHAR(150) NOT NULL,
    section_title VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_procedure_sections_procedure_section_key (procedure_id, section_key),
    KEY idx_procedure_sections_procedure_id (procedure_id),
    CONSTRAINT fk_procedure_sections_procedure
        FOREIGN KEY (procedure_id) REFERENCES procedures(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS section_change_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_section_id INT UNSIGNED NOT NULL,
    procedure_version_id INT UNSIGNED NOT NULL,
    document_relationship_id INT UNSIGNED NULL,
    change_type VARCHAR(50) NOT NULL,
    entry_kind VARCHAR(50) NOT NULL DEFAULT 'AFFECTED_SECTION',
    section_label VARCHAR(255) NOT NULL,
    change_summary TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_section_change_log_procedure_section_id (procedure_section_id),
    KEY idx_section_change_log_procedure_version_id (procedure_version_id),
    KEY idx_section_change_log_document_relationship_id (document_relationship_id),
    KEY idx_section_change_log_change_type (change_type),
    CONSTRAINT fk_section_change_log_section
        FOREIGN KEY (procedure_section_id) REFERENCES procedure_sections(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_section_change_log_version
        FOREIGN KEY (procedure_version_id) REFERENCES procedure_versions(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_section_change_log_relationship
        FOREIGN KEY (document_relationship_id) REFERENCES document_relationships(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
