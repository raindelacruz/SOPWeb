<?php
require_once __DIR__ . '/../helpers/pdms_authoring_options.php';

class ProcedureSyncService {
    private $db;
    private $legacySyncRemarkPrefix = '[LEGACY_SYNC]';
    private $legacySyncManagementSource = 'LEGACY_SYNC';

    public function __construct() {
        $this->db = new Database;
    }

    private function tableExists($tableName) {
        $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.tables
             WHERE table_schema = :table_schema
               AND table_name = :table_name'
        );
        $this->db->bind(':table_schema', DB_NAME);
        $this->db->bind(':table_name', $tableName);

        $row = $this->db->single();
        return $row && (int) $row->total > 0;
    }

    private function columnExists($tableName, $columnName) {
        $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = :table_schema
               AND table_name = :table_name
               AND column_name = :column_name'
        );
        $this->db->bind(':table_schema', DB_NAME);
        $this->db->bind(':table_name', $tableName);
        $this->db->bind(':column_name', $columnName);

        $row = $this->db->single();
        return $row && (int) $row->total > 0;
    }

    public function hasPdmsFoundation() {
        return $this->tableExists('procedures')
            && $this->tableExists('procedure_versions')
            && $this->tableExists('document_relationships');
    }

    private function hasSectionHistoryFoundation() {
        return $this->tableExists('procedure_sections')
            && $this->tableExists('section_change_log');
    }

    private function hasRelationshipManagementMetadata() {
        return $this->columnExists('document_relationships', 'management_source');
    }

    private function sanitizeLegacySyncRemarks($remarks) {
        $remarks = trim((string) $remarks);

        if (strpos($remarks, $this->legacySyncRemarkPrefix) === 0) {
            $remarks = trim(substr($remarks, strlen($this->legacySyncRemarkPrefix)));
        }

        return $remarks;
    }

    private function sqlInList(array $values) {
        return '"' . implode('", "', $values) . '"';
    }

    public function getBackfillCandidates($limit = null) {
        if (!$this->hasPdmsFoundation()) {
            return [];
        }

        $sql = 'SELECT p.id
                FROM posts p
                LEFT JOIN procedure_versions pv ON pv.legacy_post_id = p.id
                WHERE pv.id IS NULL
                ORDER BY p.id ASC';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
        }

        $this->db->query($sql);

        if ($limit !== null) {
            $this->db->bind(':limit', (int) $limit, PDO::PARAM_INT);
        }

        return $this->db->resultSet();
    }

    public function backfillLegacyPosts($limit = 25, $userId = null) {
        $result = [
            'attempted' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (!$this->hasPdmsFoundation()) {
            $result['errors'][] = 'PDMS foundation tables are not available.';
            return $result;
        }

        $candidates = $this->getBackfillCandidates($limit);

        foreach ($candidates as $candidate) {
            $result['attempted']++;

            try {
                $syncedVersion = $this->syncLegacyPost((int) $candidate->id, $userId);

                if ($syncedVersion) {
                    $result['synced']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = 'Post #' . (int) $candidate->id . ' could not be synchronized.';
                }
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][] = 'Post #' . (int) $candidate->id . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    public function normalizeLegacyManagedRelationships($limit = 100, $userId = null) {
        $result = [
            'attempted' => 0,
            'updated' => 0,
            'errors' => []
        ];

        if (!$this->hasPdmsFoundation()) {
            $result['errors'][] = 'PDMS foundation tables are not available.';
            return $result;
        }

        $candidates = $this->getUnmarkedLegacyManagedRelationships($limit);
        $result['attempted'] = count($candidates);

        foreach ($candidates as $relationship) {
            try {
                $cleanRemarks = $this->sanitizeLegacySyncRemarks($relationship->remarks ?? null);

                if ($this->hasRelationshipManagementMetadata()) {
                    $this->db->query(
                        'UPDATE document_relationships
                         SET remarks = :remarks,
                             management_source = :management_source
                         WHERE id = :id'
                    );
                    $this->db->bind(':remarks', $cleanRemarks !== '' ? $cleanRemarks : null);
                    $this->db->bind(':management_source', $this->legacySyncManagementSource);
                    $this->db->bind(':id', (int) $relationship->id, PDO::PARAM_INT);
                } else {
                    $normalizedRemarks = trim($this->legacySyncRemarkPrefix . ' ' . $cleanRemarks);

                    $this->db->query(
                        'UPDATE document_relationships
                         SET remarks = :remarks
                         WHERE id = :id'
                    );
                    $this->db->bind(':remarks', $normalizedRemarks);
                    $this->db->bind(':id', (int) $relationship->id, PDO::PARAM_INT);
                }

                if ($this->db->execute()) {
                    $result['updated']++;
                }
            } catch (Throwable $e) {
                $result['errors'][] = 'Relationship #' . (int) $relationship->id . ': ' . $e->getMessage();
            }
        }

        if ($result['updated'] > 0) {
            $this->logPdmsActivity(
                $userId,
                'PDMS Cleanup',
                'Normalized ' . $result['updated'] . ' legacy-managed relationship records with sync markers.'
            );
        }

        return $result;
    }

    public function syncLegacyPost($postId, $userId = null, $trail = [], $options = []) {
        $postId = (int) $postId;

        if ($postId <= 0 || in_array($postId, $trail, true) || !$this->hasPdmsFoundation()) {
            return null;
        }

        $post = $this->getPostById($postId);
        if (!$post) {
            return null;
        }

        $trail[] = $postId;

        $amendedVersion = null;
        if (!empty($post->amended_post_id)) {
            $amendedVersion = $this->syncLegacyPost((int) $post->amended_post_id, $userId, $trail);
        }

        $supersededVersion = null;
        if (!empty($post->superseded_post_id)) {
            $supersededVersion = $this->syncLegacyPost((int) $post->superseded_post_id, $userId, $trail);
        }

        $existingVersion = $this->getVersionByLegacyPostId($postId);
        $procedure = $existingVersion ? $this->getProcedureById((int) $existingVersion->procedure_id) : null;

        $changeType = $this->resolveChangeType($post, $options['change_type'] ?? null);
        $versionStatus = $this->resolveVersionStatus($options['workflow_status'] ?? null);
        $procedureId = null;
        $basedOnVersionId = null;

        if ($amendedVersion) {
            $procedureId = (int) $amendedVersion->procedure_id;
            $basedOnVersionId = (int) $amendedVersion->id;
            $procedure = $this->getProcedureById($procedureId);
        } elseif ($procedure) {
            $procedureId = (int) $procedure->id;
        } else {
            $legacyProcedure = $this->getProcedureByLegacyPostId($postId);
            if ($legacyProcedure) {
                $procedure = $legacyProcedure;
                $procedureId = (int) $legacyProcedure->id;
            }
        }

        $this->db->beginTransaction();

        try {
            $previousCurrentVersion = $procedureId ? $this->getCurrentVersionForProcedure($procedureId) : null;

            if (!$procedureId) {
                $procedureId = $this->createProcedure([
                    'procedure_code' => $post->reference_number,
                    'title' => $post->title,
                    'description' => $post->description,
                    'status' => 'ACTIVE',
                    'legacy_post_id' => PdmsAuthoringOptions::changeTypePreservesPreviousCurrentAsEffective($changeType) ? null : $postId,
                    'created_by' => $userId
                ]);
            } else {
                $this->updateProcedure($procedureId, [
                    'procedure_code' => $procedure && !empty($procedure->procedure_code) ? $procedure->procedure_code : $post->reference_number,
                    'title' => $post->title,
                    'description' => $post->description,
                    'status' => 'ACTIVE',
                    'legacy_post_id' => (PdmsAuthoringOptions::changeTypePreservesPreviousCurrentAsEffective($changeType) && $procedure && !empty($procedure->legacy_post_id))
                        ? $procedure->legacy_post_id
                        : ($procedure->legacy_post_id ?? $postId)
                ]);
            }

            if ($existingVersion) {
                $versionId = (int) $existingVersion->id;
                $versionNumber = $existingVersion->version_number;
                $basedOnVersionId = PdmsAuthoringOptions::changeTypePreservesPreviousCurrentAsEffective($changeType) ? $basedOnVersionId : ($existingVersion->based_on_version_id ?? null);
                $previousStatus = $existingVersion->status ?? null;

                $this->updateVersion($versionId, [
                    'procedure_id' => $procedureId,
                    'version_number' => $versionNumber,
                    'document_number' => $post->reference_number,
                    'title' => $post->title,
                    'summary_of_change' => $post->description,
                    'change_type' => $changeType,
                    'effective_date' => $post->date_of_effectivity,
                    'status' => $versionStatus,
                    'file_path' => $post->file,
                    'based_on_version_id' => $basedOnVersionId,
                    'registered_by' => $userId
                ]);

                $this->recordWorkflowTransition(
                    $versionId,
                    'SYNC_UPDATE',
                    $previousStatus,
                    $versionStatus,
                    $userId,
                    'Legacy SOP update synchronized into the PDMS version record.'
                );
            } else {
                $versionNumber = $this->generateNextVersionNumber($procedureId, $changeType);
                $versionId = $this->createVersion([
                    'procedure_id' => $procedureId,
                    'legacy_post_id' => $postId,
                    'version_number' => $versionNumber,
                    'document_number' => $post->reference_number,
                    'title' => $post->title,
                    'summary_of_change' => $post->description,
                    'change_type' => $changeType,
                    'effective_date' => $post->date_of_effectivity,
                    'registration_date' => $post->upload_date ?? date('Y-m-d'),
                    'status' => $versionStatus,
                    'file_path' => $post->file,
                    'based_on_version_id' => $basedOnVersionId,
                    'created_by' => $userId,
                    'registered_by' => $userId
                ]);

                $this->recordWorkflowTransition(
                    $versionId,
                    'SYNC_CREATE',
                    null,
                    $versionStatus,
                    $userId,
                    'Legacy SOP record created a PDMS version.'
                );
            }

            if ($this->canBecomeControllingVersion($versionStatus)) {
                $this->updatePreviousCurrentVersionStatus($previousCurrentVersion, $versionId, $changeType, $userId);
                $this->setCurrentVersion($procedureId, $versionId);
            } else {
                $this->reconcileCurrentVersionAfterNonControllingSync($procedureId, $versionId, $previousCurrentVersion, $userId);
            }

            $this->replaceRelationships($versionId, $changeType, $amendedVersion, $supersededVersion, $userId, $options);

            if ($changeType === 'SUPERSEDING_PROCEDURE') {
                $this->handleSupersededTarget($supersededVersion, $versionId, $userId);
            }

            $this->logPdmsActivity(
                $userId,
                'PDMS Sync',
                'Legacy SOP #' . $postId . ' synchronized as PDMS version ' . $versionNumber . ' (' . $changeType . ', ' . $versionStatus . ').'
            );

            $this->db->commit();
            return $this->getVersionByLegacyPostId($postId);
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function inferChangeType($post) {
        return PdmsAuthoringOptions::inferLegacyChangeTypeFromLinks($post->amended_post_id ?? null, $post->superseded_post_id ?? null);
    }

    private function resolveChangeType($post, $override) {
        $allowed = PdmsAuthoringOptions::createChangeTypes();

        $override = strtoupper((string) $override);

        if (in_array($override, $allowed, true)) {
            return $override;
        }

        return $this->inferChangeType($post);
    }

    private function resolveVersionStatus($override) {
        if (PdmsAuthoringOptions::isRecognizedWorkflowStatus($override)) {
            return PdmsAuthoringOptions::normalizeWorkflowStatus($override, 'EFFECTIVE');
        }

        return 'EFFECTIVE';
    }

    private function canBecomeControllingVersion($status) {
        return in_array($status, PdmsAuthoringOptions::currentEligibleWorkflowStatuses(), true);
    }

    private function generateNextVersionNumber($procedureId, $changeType) {
        $latest = $this->getLatestVersionForProcedure($procedureId);

        if (!$latest || empty($latest->version_number)) {
            return '1.0';
        }

        $parts = explode('.', (string) $latest->version_number);
        $major = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : 1;
        $minor = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : 0;

        if (PdmsAuthoringOptions::changeTypeUsesMinorVersionIncrement($changeType)) {
            $minor++;
        } else {
            $major++;
            $minor = 0;
        }

        return $major . '.' . $minor;
    }

    private function replaceRelationships($sourceVersionId, $changeType, $amendedVersion, $supersededVersion, $userId, $options = []) {
        $this->clearSectionHistoryForVersion($sourceVersionId);
        $this->deleteLegacyManagedRelationshipsBySourceVersionId($sourceVersionId);

        $changeType = strtoupper((string) $changeType);
        $allowsAmends = PdmsAuthoringOptions::changeTypeUsesAmendedLegacyTarget($changeType);
        $allowsSupersedes = PdmsAuthoringOptions::changeTypeUsesSupersededLegacyTarget($changeType);

        if ($amendedVersion && $allowsAmends) {
            $relationshipType = $this->resolveRelationshipType(
                $options['pdms_relationship_type'] ?? null,
                PdmsAuthoringOptions::defaultRelationshipTypeForChangeType($changeType)
            );
            $this->createRelationship([
                'source_version_id' => $sourceVersionId,
                'target_version_id' => (int) $amendedVersion->id,
                'relationship_type' => $relationshipType,
                'affected_sections' => $options['affected_sections'] ?? null,
                'remarks' => $options['relationship_remarks'] ?? null,
                'legacy_managed' => true,
                'created_by' => $userId
            ]);
        }

        if ($supersededVersion && $allowsSupersedes) {
            $relationshipType = $this->resolveRelationshipType(
                $options['pdms_relationship_type'] ?? null,
                PdmsAuthoringOptions::defaultRelationshipTypeForChangeType($changeType)
            );
            $this->createRelationship([
                'source_version_id' => $sourceVersionId,
                'target_version_id' => (int) $supersededVersion->id,
                'relationship_type' => $relationshipType,
                'affected_sections' => $options['affected_sections'] ?? null,
                'remarks' => $options['relationship_remarks'] ?? null,
                'legacy_managed' => true,
                'created_by' => $userId
            ]);
        }
    }

    private function resolveRelationshipType($override, $fallback) {
        $allowed = PdmsAuthoringOptions::normalizedRelationshipTypes();

        $override = strtoupper((string) $override);

        if (in_array($override, $allowed, true)) {
            return $override;
        }

        return $fallback;
    }

    private function updatePreviousCurrentVersionStatus($previousCurrentVersion, $newVersionId, $changeType, $userId) {
        if (!$previousCurrentVersion || (int) $previousCurrentVersion->id === (int) $newVersionId) {
            return;
        }

        $targetStatus = PdmsAuthoringOptions::replacementStatusForPreviousCurrent($changeType);
        $previousStatus = $previousCurrentVersion->status ?? null;

        if ($previousStatus === $targetStatus) {
            return;
        }

        $this->setVersionStatus((int) $previousCurrentVersion->id, $targetStatus);
        $this->recordWorkflowTransition(
            (int) $previousCurrentVersion->id,
            'SYNC_REPLACED',
            $previousStatus,
            $targetStatus,
            $userId,
            'A newer synchronized version became the controlling document for this procedure.'
        );
    }

    private function reconcileCurrentVersionAfterNonControllingSync($procedureId, $versionId, $previousCurrentVersion, $userId) {
        if (!$previousCurrentVersion || (int) $previousCurrentVersion->id !== (int) $versionId) {
            return;
        }

        $fallbackVersion = $this->getBestControllingVersionForProcedure($procedureId, $versionId);

        if ($fallbackVersion) {
            $this->setCurrentVersion($procedureId, (int) $fallbackVersion->id);
            $this->recordWorkflowTransition(
                (int) $fallbackVersion->id,
                'SYNC_RESTORED_CURRENT',
                $fallbackVersion->status ?? null,
                $fallbackVersion->status ?? null,
                $userId,
                'A non-controlling update removed the previous current version, so this eligible version was restored as current.'
            );
            return;
        }

        $this->clearCurrentVersion($procedureId);
    }

    private function handleSupersededTarget($supersededVersion, $newVersionId, $userId) {
        if (!$supersededVersion) {
            return;
        }

        $previousStatus = $supersededVersion->status ?? null;
        if ($previousStatus !== 'SUPERSEDED') {
            $this->setVersionStatus((int) $supersededVersion->id, 'SUPERSEDED');
            $this->recordWorkflowTransition(
                (int) $supersededVersion->id,
                'SYNC_SUPERSEDED',
                $previousStatus,
                'SUPERSEDED',
                $userId,
                'Superseded by synchronized PDMS version #' . (int) $newVersionId . '.'
            );
        }

        $supersededProcedure = $this->getProcedureById((int) $supersededVersion->procedure_id);
        if ($supersededProcedure && $supersededProcedure->status !== 'SUPERSEDED') {
            $this->clearCurrentVersion((int) $supersededProcedure->id);
            $this->setProcedureStatus((int) $supersededProcedure->id, 'SUPERSEDED');
            $this->logPdmsActivity(
                $userId,
                'PDMS Supersession',
                'Procedure "' . $supersededProcedure->title . '" was marked SUPERSEDED by legacy synchronization.'
            );
        }
    }

    private function getPostById($postId) {
        $this->db->query('SELECT * FROM posts WHERE id = :id');
        $this->db->bind(':id', $postId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getProcedureById($procedureId) {
        $this->db->query('SELECT * FROM procedures WHERE id = :id');
        $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getCurrentVersionForProcedure($procedureId) {
        $this->db->query(
            'SELECT pv.*
             FROM procedures p
             INNER JOIN procedure_versions pv ON pv.id = p.current_version_id
             WHERE p.id = :id'
        );
        $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getProcedureByLegacyPostId($legacyPostId) {
        $this->db->query('SELECT * FROM procedures WHERE legacy_post_id = :legacy_post_id');
        $this->db->bind(':legacy_post_id', $legacyPostId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getVersionByLegacyPostId($legacyPostId) {
        $this->db->query('SELECT * FROM procedure_versions WHERE legacy_post_id = :legacy_post_id');
        $this->db->bind(':legacy_post_id', $legacyPostId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getVersionById($versionId) {
        $this->db->query('SELECT * FROM procedure_versions WHERE id = :id');
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getLatestVersionForProcedure($procedureId) {
        $this->db->query(
            'SELECT *
             FROM procedure_versions
             WHERE procedure_id = :procedure_id
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getUnmarkedLegacyManagedRelationships($limit) {
        if ($this->hasRelationshipManagementMetadata()) {
            $this->db->query(
                'SELECT dr.*
                 FROM document_relationships dr
                 INNER JOIN procedure_versions pv_source ON pv_source.id = dr.source_version_id
                 WHERE pv_source.legacy_post_id IS NOT NULL
                   AND dr.relationship_type IN (' . $this->sqlInList(PdmsAuthoringOptions::normalizedRelationshipTypes()) . ')
                   AND dr.management_source IS NULL
                 ORDER BY dr.id ASC
                 LIMIT :limit'
            );
            $this->db->bind(':limit', (int) $limit, PDO::PARAM_INT);
            return $this->db->resultSet();
        }

        $this->db->query(
            'SELECT dr.*
             FROM document_relationships dr
             INNER JOIN procedure_versions pv_source ON pv_source.id = dr.source_version_id
             WHERE pv_source.legacy_post_id IS NOT NULL
               AND dr.relationship_type IN (' . $this->sqlInList(PdmsAuthoringOptions::normalizedRelationshipTypes()) . ')
               AND (dr.remarks IS NULL OR dr.remarks NOT LIKE :sync_marker)
             ORDER BY dr.id ASC
             LIMIT :limit'
        );
        $this->db->bind(':sync_marker', $this->legacySyncRemarkPrefix . '%');
        $this->db->bind(':limit', (int) $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    private function getBestControllingVersionForProcedure($procedureId, $excludeVersionId = null) {
        $sql = 'SELECT *
                FROM procedure_versions
                WHERE procedure_id = :procedure_id
                  AND status IN (' . $this->sqlInList(PdmsAuthoringOptions::controllingWorkflowStatuses()) . ')';

        if ($excludeVersionId !== null) {
            $sql .= ' AND id <> :exclude_version_id';
        }

        $sql .= ' ORDER BY
                    CASE WHEN status = "EFFECTIVE" THEN 0 ELSE 1 END,
                    effective_date DESC,
                    id DESC
                  LIMIT 1';

        $this->db->query($sql);
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);

        if ($excludeVersionId !== null) {
            $this->db->bind(':exclude_version_id', $excludeVersionId, PDO::PARAM_INT);
        }

        return $this->db->single();
    }

    private function createProcedure($data) {
        $this->db->query(
            'INSERT INTO procedures
                (procedure_code, title, description, status, legacy_post_id, created_by)
             VALUES
                (:procedure_code, :title, :description, :status, :legacy_post_id, :created_by)'
        );
        $this->db->bind(':procedure_code', $data['procedure_code']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'ACTIVE');
        $this->db->bind(':legacy_post_id', $data['legacy_post_id'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    private function updateProcedure($procedureId, $data) {
        $this->db->query(
            'UPDATE procedures
             SET procedure_code = :procedure_code,
                 title = :title,
                 description = :description,
                 status = :status,
                 legacy_post_id = :legacy_post_id
             WHERE id = :id'
        );
        $this->db->bind(':procedure_code', $data['procedure_code']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'ACTIVE');
        $this->db->bind(':legacy_post_id', $data['legacy_post_id'] ?? null);
        $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function setProcedureStatus($procedureId, $status) {
        $this->db->query(
            'UPDATE procedures
             SET status = :status
             WHERE id = :id'
        );
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function createVersion($data) {
        $this->db->query(
            'INSERT INTO procedure_versions
                (procedure_id, legacy_post_id, version_number, document_number, title, summary_of_change, change_type,
                 effective_date, registration_date, status, file_path, based_on_version_id, created_by, registered_by)
             VALUES
                (:procedure_id, :legacy_post_id, :version_number, :document_number, :title, :summary_of_change, :change_type,
                 :effective_date, :registration_date, :status, :file_path, :based_on_version_id, :created_by, :registered_by)'
        );
        $this->db->bind(':registration_date', $data['registration_date'] ?? date('Y-m-d'));
        $this->db->bind(':registered_by', $data['registered_by'] ?? $data['created_by'] ?? null);

        $this->db->bind(':procedure_id', $data['procedure_id'], PDO::PARAM_INT);
        $this->db->bind(':legacy_post_id', $data['legacy_post_id'] ?? null);
        $this->db->bind(':version_number', $data['version_number']);
        $this->db->bind(':document_number', $data['document_number'] ?? null);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':summary_of_change', $data['summary_of_change'] ?? null);
        $this->db->bind(':change_type', $data['change_type']);
        $this->db->bind(':effective_date', $data['effective_date'] ?? null);
        $this->db->bind(':status', PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE'));
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':based_on_version_id', $data['based_on_version_id'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    private function updateVersion($versionId, $data) {
        $this->db->query(
            'UPDATE procedure_versions
             SET procedure_id = :procedure_id,
                 version_number = :version_number,
                 document_number = :document_number,
                 title = :title,
                 summary_of_change = :summary_of_change,
                 change_type = :change_type,
                 effective_date = :effective_date,
                 status = :status,
                 file_path = :file_path,
                 based_on_version_id = :based_on_version_id,
                 registered_by = :registered_by
             WHERE id = :id'
        );
        $this->db->bind(':registered_by', $data['registered_by'] ?? $data['created_by'] ?? null);

        $this->db->bind(':procedure_id', $data['procedure_id'], PDO::PARAM_INT);
        $this->db->bind(':version_number', $data['version_number']);
        $this->db->bind(':document_number', $data['document_number'] ?? null);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':summary_of_change', $data['summary_of_change'] ?? null);
        $this->db->bind(':change_type', $data['change_type']);
        $this->db->bind(':effective_date', $data['effective_date'] ?? null);
        $this->db->bind(':status', PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE'));
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':based_on_version_id', $data['based_on_version_id'] ?? null);
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function setVersionStatus($versionId, $status) {
        $this->db->query(
            'UPDATE procedure_versions
             SET status = :status
             WHERE id = :id'
        );
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function deleteLegacyManagedRelationshipsBySourceVersionId($sourceVersionId) {
        if ($this->hasRelationshipManagementMetadata()) {
            $this->db->query(
                'DELETE FROM document_relationships
                 WHERE source_version_id = :source_version_id
                   AND management_source = :management_source
                   AND relationship_type IN (' . $this->sqlInList(PdmsAuthoringOptions::normalizedRelationshipTypes()) . ')'
            );
            $this->db->bind(':source_version_id', $sourceVersionId, PDO::PARAM_INT);
            $this->db->bind(':management_source', $this->legacySyncManagementSource);
            return $this->db->execute();
        }

        $this->db->query(
            'DELETE FROM document_relationships
             WHERE source_version_id = :source_version_id
               AND (
                    remarks LIKE :sync_marker
                    OR remarks IS NULL
               )
               AND relationship_type IN (' . $this->sqlInList(PdmsAuthoringOptions::normalizedRelationshipTypes()) . ')'
        );
        $this->db->bind(':source_version_id', $sourceVersionId, PDO::PARAM_INT);
        $this->db->bind(':sync_marker', $this->legacySyncRemarkPrefix . '%');
        return $this->db->execute();
    }

    private function createRelationship($data) {
        $remarks = $this->sanitizeLegacySyncRemarks($data['remarks'] ?? null);
        $isLegacyManaged = !empty($data['legacy_managed']);

        if ($isLegacyManaged && !$this->hasRelationshipManagementMetadata()) {
            $remarks = trim($this->legacySyncRemarkPrefix . ' ' . (string) $remarks);
        }

        if ($this->hasRelationshipManagementMetadata()) {
            $this->db->query(
                'INSERT INTO document_relationships
                    (source_version_id, target_version_id, relationship_type, affected_sections, remarks, management_source, created_by)
                 VALUES
                    (:source_version_id, :target_version_id, :relationship_type, :affected_sections, :remarks, :management_source, :created_by)'
            );
            $this->db->bind(':management_source', $isLegacyManaged ? $this->legacySyncManagementSource : null);
        } else {
            $this->db->query(
                'INSERT INTO document_relationships
                    (source_version_id, target_version_id, relationship_type, affected_sections, remarks, created_by)
                 VALUES
                    (:source_version_id, :target_version_id, :relationship_type, :affected_sections, :remarks, :created_by)'
            );
        }

        $this->db->bind(':source_version_id', $data['source_version_id'], PDO::PARAM_INT);
        $this->db->bind(':target_version_id', $data['target_version_id'], PDO::PARAM_INT);
        $this->db->bind(':relationship_type', $data['relationship_type']);
        $this->db->bind(':affected_sections', $data['affected_sections'] ?? null);
        $this->db->bind(':remarks', $remarks !== '' ? $remarks : null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->execute();

        $relationshipId = (int) $this->db->lastInsertId();
        $data['remarks'] = $remarks !== '' ? $remarks : null;
        $this->recordAffectedSectionHistory($relationshipId, $data);

        return $relationshipId;
    }

    private function setCurrentVersion($procedureId, $versionId) {
        $this->db->query(
            'UPDATE procedures
             SET current_version_id = :current_version_id
             WHERE id = :procedure_id'
        );
        $this->db->bind(':current_version_id', $versionId, PDO::PARAM_INT);
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function clearCurrentVersion($procedureId) {
        $this->db->query(
            'UPDATE procedures
             SET current_version_id = NULL
             WHERE id = :procedure_id'
        );
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function recordWorkflowTransition($versionId, $actionType, $fromStatus, $toStatus, $actedBy, $remarks) {
        if (!$this->tableExists('workflow_actions')) {
            return false;
        }

        $this->db->query(
            'INSERT INTO workflow_actions
                (procedure_version_id, lifecycle_action_type, from_status, to_status, acted_by, remarks)
             VALUES
                (:procedure_version_id, :lifecycle_action_type, :from_status, :to_status, :acted_by, :remarks)'
        );
        $this->db->bind(':procedure_version_id', $versionId, PDO::PARAM_INT);
        $this->db->bind(':lifecycle_action_type', strtoupper((string) $actionType));
        $this->db->bind(':from_status', $fromStatus);
        $this->db->bind(':to_status', $toStatus);
        $this->db->bind(':acted_by', $actedBy ?? null);
        $this->db->bind(':remarks', $remarks);
        return $this->db->execute();
    }

    private function logPdmsActivity($userId, $action, $description) {
        if (!$this->tableExists('activity_logs')) {
            return false;
        }

        $this->db->query(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, :action, :description)'
        );
        $this->db->bind(':user_id', $userId ?? null);
        $this->db->bind(':action', $action);
        $this->db->bind(':description', $description);
        return $this->db->execute();
    }

    private function clearSectionHistoryForVersion($versionId) {
        if (!$this->hasSectionHistoryFoundation()) {
            return false;
        }

        $this->db->query(
            'DELETE FROM section_change_log
             WHERE procedure_version_id = :procedure_version_id'
        );
        $this->db->bind(':procedure_version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    private function parseAffectedSectionLabels($affectedSections) {
        $affectedSections = trim((string) $affectedSections);
        if ($affectedSections === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,;]+/', $affectedSections) ?: [];
        $labels = [];

        foreach ($parts as $part) {
            $label = trim((string) $part);
            $label = preg_replace('/^sections?\s+/i', '', $label);
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            $labels[] = $label;
        }

        return array_values(array_unique($labels));
    }

    private function normalizeSectionKey($label) {
        $label = strtoupper(trim((string) $label));
        $label = preg_replace('/\s+/', ' ', $label);
        return preg_replace('/^SECTIONS?\s+/', '', $label);
    }

    private function ensureProcedureSection($procedureId, $label, $userId = null) {
        $sectionKey = $this->normalizeSectionKey($label);

        $this->db->query(
            'SELECT id
             FROM procedure_sections
             WHERE procedure_id = :procedure_id
               AND section_key = :section_key
             LIMIT 1'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        $this->db->bind(':section_key', $sectionKey);
        $existing = $this->db->single();

        if ($existing && !empty($existing->id)) {
            return (int) $existing->id;
        }

        $this->db->query(
            'INSERT INTO procedure_sections
                (procedure_id, section_key, section_title, created_by)
             VALUES
                (:procedure_id, :section_key, :section_title, :created_by)'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        $this->db->bind(':section_key', $sectionKey);
        $this->db->bind(':section_title', $label);
        $this->db->bind(':created_by', $userId ?? null);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    private function recordAffectedSectionHistory($relationshipId, $data) {
        if (!$this->hasSectionHistoryFoundation()) {
            return;
        }

        $relationshipType = strtoupper((string) ($data['relationship_type'] ?? ''));
        if (!in_array($relationshipType, PdmsAuthoringOptions::relationshipTypesWithAffectedSections(), true)) {
            return;
        }

        $labels = $this->parseAffectedSectionLabels($data['affected_sections'] ?? null);
        if (empty($labels)) {
            return;
        }

        $sourceVersion = $this->getVersionById((int) $data['source_version_id']);
        if (!$sourceVersion || empty($sourceVersion->procedure_id)) {
            return;
        }

        foreach ($labels as $label) {
            $procedureSectionId = $this->ensureProcedureSection((int) $sourceVersion->procedure_id, $label, $data['created_by'] ?? null);

            $this->db->query(
                'INSERT INTO section_change_log
                    (procedure_section_id, procedure_version_id, document_relationship_id, change_type, entry_kind, section_label, change_summary, created_by)
                 VALUES
                    (:procedure_section_id, :procedure_version_id, :document_relationship_id, :change_type, :entry_kind, :section_label, :change_summary, :created_by)'
            );
            $this->db->bind(':procedure_section_id', $procedureSectionId, PDO::PARAM_INT);
            $this->db->bind(':procedure_version_id', (int) $data['source_version_id'], PDO::PARAM_INT);
            $this->db->bind(':document_relationship_id', (int) $relationshipId, PDO::PARAM_INT);
            $this->db->bind(':change_type', strtoupper((string) ($sourceVersion->change_type ?? $relationshipType)));
            $this->db->bind(':entry_kind', 'AFFECTED_SECTION');
            $this->db->bind(':section_label', $label);
            $this->db->bind(':change_summary', $data['remarks'] ?? null);
            $this->db->bind(':created_by', $data['created_by'] ?? null);
            $this->db->execute();
        }
    }
}
?>
