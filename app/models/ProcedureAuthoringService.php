<?php
require_once __DIR__ . '/../helpers/pdms_authoring_options.php';

class ProcedureAuthoringService {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function registerProcedure($data) {
        $userId = $data['user_id'] ?? null;
        $status = PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE');
        $this->assertAllowedAuthoringStatus($status);
        $this->assertAllowedAuthoringChangeType($data['change_type'] ?? 'NEW', 'create');
        $relationshipType = strtoupper((string) ($data['relationship_type'] ?? ''));
        $this->assertAllowedAuthoringRelationshipType($relationshipType, 'create');
        $targetVersionId = !empty($data['target_version_id']) ? (int) $data['target_version_id'] : null;

        $this->db->beginTransaction();

        try {
            $legacyTargetPostId = null;
            if ($targetVersionId) {
                $targetVersion = $this->getVersionById($targetVersionId);
                $legacyTargetPostId = !empty($targetVersion->legacy_post_id) ? (int) $targetVersion->legacy_post_id : null;
            }

            $legacyPostId = $this->createLegacyPost([
                'title' => $data['title'],
                'description' => $data['description'],
                'reference_number' => $data['document_number'],
                'date_of_effectivity' => $data['effective_date'],
                'upload_date' => date('Y-m-d'),
                'file' => $data['file_path'],
                'amended_post_id' => $this->resolveLegacyAmendedTargetId($relationshipType, $legacyTargetPostId),
                'superseded_post_id' => $this->resolveLegacySupersededTargetId($relationshipType, $legacyTargetPostId)
            ]);

            $procedureId = $this->createProcedure([
                'procedure_code' => $data['procedure_code'],
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? null,
                'owner_office' => $data['owner_office'] ?? null,
                'status' => $this->resolveProcedureStatus($status),
                'legacy_post_id' => $legacyPostId,
                'created_by' => $userId
            ]);

            $versionId = $this->createVersion([
                'procedure_id' => $procedureId,
                'legacy_post_id' => $legacyPostId,
                'version_number' => '1.0',
                'document_number' => $data['document_number'],
                'title' => $data['title'],
                'summary_of_change' => $data['summary_of_change'] ?? null,
                'change_type' => strtoupper((string) ($data['change_type'] ?? 'NEW')),
                'effective_date' => $data['effective_date'] ?? null,
                'registration_date' => $data['registration_date'] ?? date('Y-m-d'),
                'status' => $status,
                'file_path' => $data['file_path'] ?? null,
                'based_on_version_id' => $targetVersionId,
                'created_by' => $userId,
                'registered_by' => $userId
            ]);

            if (in_array($status, PdmsAuthoringOptions::currentEligibleWorkflowStatuses(), true)) {
                $this->setCurrentVersion($procedureId, $versionId);
            }

            if ($targetVersionId && $relationshipType !== '') {
                $this->createRelationship([
                    'source_version_id' => $versionId,
                    'target_version_id' => $targetVersionId,
                    'relationship_type' => $relationshipType,
                    'affected_sections' => $data['affected_sections'] ?? null,
                    'remarks' => $data['relationship_remarks'] ?? null,
                    'created_by' => $userId
                ]);
            }

            if (
                strtoupper((string) ($data['change_type'] ?? 'NEW')) === 'SUPERSEDING_PROCEDURE'
                && $targetVersionId
                && !empty($targetVersion)
            ) {
                $this->markTargetProcedureSuperseded($targetVersion, $userId);
            }

            $this->recordLifecycleAction([
                'procedure_version_id' => $versionId,
                'lifecycle_action_type' => 'PDMS_REGISTER_PROCEDURE',
                'from_status' => null,
                'to_status' => $status,
                'acted_by' => $userId,
                'remarks' => 'PDMS-first registration created the initial procedure record.'
            ]);

            $this->logActivity(
                $userId,
                'PDMS Register Procedure',
                'Procedure "' . $data['procedure_code'] . '" was registered through the PDMS-first procedure flow.'
            );

            $this->db->commit();

            return [
                'procedure_id' => $procedureId,
                'version_id' => $versionId,
                'legacy_post_id' => $legacyPostId
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function registerRevisionForProcedure($procedureId, $data) {
        $procedureId = (int) $procedureId;
        $userId = $data['user_id'] ?? null;
        $status = PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE');
        $this->assertAllowedAuthoringStatus($status);
        $changeType = strtoupper((string) ($data['change_type'] ?? 'AMENDMENT'));
        $this->assertAllowedAuthoringChangeType($changeType, 'issue');
        $relationshipType = strtoupper((string) ($data['relationship_type'] ?? ''));
        $this->assertAllowedAuthoringRelationshipType($relationshipType, 'issue');
        $targetVersionId = !empty($data['target_version_id']) ? (int) $data['target_version_id'] : null;

        $procedure = $this->getProcedureById($procedureId);
        if (!$procedure) {
            throw new RuntimeException('Procedure not found.');
        }

        $currentVersion = $this->getCurrentVersionForProcedure($procedureId);
        if (!$currentVersion) {
            throw new RuntimeException('This procedure does not have a current version to register a revision from.');
        }

        if (in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            throw new RuntimeException('Historical procedures cannot receive new revision registrations.');
        }

        if ($targetVersionId === null && in_array($changeType, PdmsAuthoringOptions::changeTypesThatAutoTargetCurrentForIssuance(), true)) {
            $targetVersionId = (int) $currentVersion->id;
        }

        if ($relationshipType === '') {
            $relationshipType = $this->defaultRelationshipTypeForChangeType($changeType);
        }

        $this->db->beginTransaction();

        try {
            $targetVersion = $targetVersionId ? $this->getVersionById($targetVersionId) : null;
            $legacyTargetPostId = !empty($targetVersion->legacy_post_id) ? (int) $targetVersion->legacy_post_id : null;

            $legacyPostId = $this->createLegacyPost([
                'title' => $data['title'],
                'description' => $data['description'],
                'reference_number' => $data['document_number'],
                'date_of_effectivity' => $data['effective_date'],
                'upload_date' => date('Y-m-d'),
                'file' => $data['file_path'],
                'amended_post_id' => $this->resolveLegacyAmendedTargetId($relationshipType, $legacyTargetPostId),
                'superseded_post_id' => $this->resolveLegacySupersededTargetId($relationshipType, $legacyTargetPostId)
            ]);

            $versionId = $this->createVersion([
                'procedure_id' => $procedureId,
                'legacy_post_id' => $legacyPostId,
                'version_number' => $this->generateNextVersionNumber($procedureId, $changeType),
                'document_number' => $data['document_number'],
                'title' => $data['title'],
                'summary_of_change' => $data['summary_of_change'] ?? null,
                'change_type' => $changeType,
                'effective_date' => $data['effective_date'] ?? null,
                'registration_date' => $data['registration_date'] ?? date('Y-m-d'),
                'status' => $status,
                'file_path' => $data['file_path'] ?? null,
                'based_on_version_id' => $targetVersionId ?: (int) $currentVersion->id,
                'created_by' => $userId,
                'registered_by' => $userId
            ]);

            $this->updateProcedureIdentity($procedureId, [
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? ($procedure->category ?? null),
                'owner_office' => $data['owner_office'] ?? ($procedure->owner_office ?? null),
                'status' => 'ACTIVE'
            ]);

            if ($targetVersionId && $relationshipType !== '') {
                $this->createRelationship([
                    'source_version_id' => $versionId,
                    'target_version_id' => $targetVersionId,
                    'relationship_type' => $relationshipType,
                    'affected_sections' => $data['affected_sections'] ?? null,
                    'remarks' => $data['relationship_remarks'] ?? null,
                    'created_by' => $userId
                ]);
            }

            $this->recordLifecycleAction([
                'procedure_version_id' => $versionId,
                'lifecycle_action_type' => 'PDMS_REGISTER_REVISION',
                'from_status' => null,
                'to_status' => $status,
                'acted_by' => $userId,
                'remarks' => 'PDMS-first registration recorded a new procedure revision.'
            ]);

            if (in_array($status, PdmsAuthoringOptions::currentEligibleWorkflowStatuses(), true)) {
                $this->updatePreviousCurrentVersionStatus($currentVersion, $changeType, $userId);
                $this->setCurrentVersion($procedureId, $versionId);
            }

            if ($changeType === 'SUPERSEDING_PROCEDURE' && $targetVersion) {
                $this->markTargetProcedureSuperseded($targetVersion, $userId);
            }

            $this->logActivity(
                $userId,
                'PDMS Register Revision',
                'Procedure "' . $procedure->procedure_code . '" received a new registered revision.'
            );

            $this->db->commit();

            return [
                'procedure_id' => $procedureId,
                'version_id' => $versionId,
                'legacy_post_id' => $legacyPostId
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateProcedureAndCurrentVersion($procedureId, $data) {
        $procedureId = (int) $procedureId;
        $procedure = $this->getProcedureById($procedureId);
        $currentVersion = $this->getCurrentVersionForProcedure($procedureId);

        if (!$procedure || !$currentVersion) {
            throw new RuntimeException('Procedure or current version not found.');
        }

        if (in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            throw new RuntimeException('Historical procedures cannot be edited.');
        }

        $this->db->beginTransaction();

        try {
            $this->updateProcedureIdentity($procedureId, [
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? null,
                'owner_office' => $data['owner_office'] ?? null,
                'status' => $procedure->status ?? 'ACTIVE'
            ]);

            $this->updateVersion($currentVersion->id, [
                'document_number' => $data['document_number'],
                'title' => $data['title'],
                'summary_of_change' => $data['summary_of_change'] ?? null,
                'effective_date' => $data['effective_date'] ?? null,
                'file_path' => $data['file_path'] ?? $currentVersion->file_path,
                'registered_by' => $data['user_id'] ?? null
            ]);

            if (!empty($currentVersion->legacy_post_id)) {
                $this->updateLegacyPost((int) $currentVersion->legacy_post_id, [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'reference_number' => $data['document_number'],
                    'date_of_effectivity' => $data['effective_date'] ?? null,
                    'file' => $data['file_path'] ?? $currentVersion->file_path
                ]);
            }

            $this->logActivity(
                $data['user_id'] ?? null,
                'PDMS Edit Procedure',
                'Procedure "' . $procedure->procedure_code . '" and its current version metadata were updated.'
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function markCurrentVersionEffective($procedureId, $userId = null) {
        $procedureId = (int) $procedureId;
        $procedure = $this->getProcedureById($procedureId);
        $currentVersion = $this->getCurrentVersionForProcedure($procedureId);

        if (!$procedure || !$currentVersion) {
            throw new RuntimeException('Procedure or current version not found.');
        }

        if (PdmsAuthoringOptions::normalizeWorkflowStatus($currentVersion->status ?? '', '') !== 'REGISTERED') {
            throw new RuntimeException('Only registered current versions can be promoted to effective.');
        }

        $this->db->beginTransaction();

        try {
            $this->setVersionStatus((int) $currentVersion->id, 'EFFECTIVE');
            $this->recordLifecycleAction([
                'procedure_version_id' => (int) $currentVersion->id,
                'lifecycle_action_type' => 'PDMS_MARK_EFFECTIVE',
                'from_status' => 'REGISTERED',
                'to_status' => 'EFFECTIVE',
                'acted_by' => $userId,
                'remarks' => 'Current version was marked effective through PDMS registry maintenance.'
            ]);

            $this->db->query(
                'UPDATE procedures
                 SET status = :status
                 WHERE id = :id'
            );
            $this->db->bind(':status', 'ACTIVE');
            $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
            $this->db->execute();

            $this->logActivity(
                $userId,
                'PDMS Mark Effective',
                'Procedure "' . $procedure->procedure_code . '" current version was marked EFFECTIVE from REGISTERED.'
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function archiveHistoricalVersion($versionId, $userId = null) {
        $versionId = (int) $versionId;
        $version = $this->getVersionWithProcedureContext($versionId);

        if (!$version) {
            throw new RuntimeException('Procedure version not found.');
        }

        if ((int) ($version->current_version_id ?? 0) === $versionId) {
            throw new RuntimeException('The current controlling version cannot be archived from the historical view.');
        }

        if (($version->status ?? '') === 'ARCHIVED') {
            throw new RuntimeException('This historical version is already archived.');
        }

        if (!in_array((string) ($version->status ?? ''), ['SUPERSEDED', 'RESCINDED'], true)) {
            throw new RuntimeException('Only superseded or rescinded historical versions can be archived.');
        }

        $this->db->beginTransaction();

        try {
            $this->setVersionStatus($versionId, 'ARCHIVED');
            $this->recordLifecycleAction([
                'procedure_version_id' => $versionId,
                'lifecycle_action_type' => 'PDMS_ARCHIVE_VERSION',
                'from_status' => $version->status ?? null,
                'to_status' => 'ARCHIVED',
                'acted_by' => $userId,
                'remarks' => 'Historical procedure version was archived through PDMS-native maintenance.'
            ]);

            $this->logActivity(
                $userId,
                'PDMS Archive Version',
                'Procedure "' . $version->procedure_code . '" version "' . $version->version_number . '" was archived.'
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rescindProcedure($procedureId, $remarks = '', $userId = null) {
        $procedureId = (int) $procedureId;
        $procedure = $this->getProcedureById($procedureId);
        $currentVersion = $this->getCurrentVersionForProcedure($procedureId);

        if (!$procedure || !$currentVersion) {
            throw new RuntimeException('Procedure or current version not found.');
        }

        if (in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            throw new RuntimeException('Only active procedures can be rescinded.');
        }

        if (PdmsAuthoringOptions::normalizeWorkflowStatus($currentVersion->status ?? '', '') !== 'EFFECTIVE') {
            throw new RuntimeException('Only effective current versions can be rescinded.');
        }

        $this->db->beginTransaction();

        try {
            $fromStatus = $currentVersion->status ?? null;
            $this->setVersionStatus((int) $currentVersion->id, 'RESCINDED');
            $this->clearCurrentVersion($procedureId);

            $this->recordLifecycleAction([
                'procedure_version_id' => (int) $currentVersion->id,
                'lifecycle_action_type' => 'PDMS_RESCIND',
                'from_status' => $fromStatus,
                'to_status' => 'RESCINDED',
                'acted_by' => $userId,
                'remarks' => $remarks !== '' ? $remarks : 'Procedure was rescinded through PDMS-native terminal-state management.'
            ]);

            $this->db->query(
                'UPDATE procedures
                 SET status = :status
                 WHERE id = :id'
            );
            $this->db->bind(':status', 'RESCINDED');
            $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
            $this->db->execute();

            $this->logActivity(
                $userId,
                'PDMS Rescind Procedure',
                'Procedure "' . $procedure->procedure_code . '" was rescinded.'
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function resolveProcedureStatus($versionStatus) {
        if (in_array($versionStatus, ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            return $versionStatus;
        }

        return 'ACTIVE';
    }

    public function normalizeRelationshipAuthoringInput($data, $currentVersionId = null, $authoringMode = 'create') {
        $data['change_type'] = strtoupper(trim((string) ($data['change_type'] ?? 'NEW')));
        $data['status'] = PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE');
        $data['relationship_type'] = strtoupper(trim((string) ($data['relationship_type'] ?? '')));
        $data['target_version_id'] = trim((string) ($data['target_version_id'] ?? ''));
        $data['affected_sections'] = trim((string) ($data['affected_sections'] ?? ''));

        $allowedChangeTypes = $authoringMode === 'issue'
            ? PdmsAuthoringOptions::issueChangeTypes()
            : PdmsAuthoringOptions::createChangeTypes();
        $allowedRelationshipTypes = $authoringMode === 'issue'
            ? PdmsAuthoringOptions::issueRelationshipTypes()
            : PdmsAuthoringOptions::createRelationshipTypes();

        if (!in_array($data['change_type'], $allowedChangeTypes, true)) {
            return $data;
        }

        if ($data['relationship_type'] !== '' && !in_array($data['relationship_type'], $allowedRelationshipTypes, true)) {
            return $data;
        }

        if ($data['relationship_type'] === '') {
            $data['relationship_type'] = PdmsAuthoringOptions::defaultRelationshipTypeForChangeType($data['change_type']);
        }

        if ($data['change_type'] === 'NEW') {
            $data['relationship_type'] = '';
            $data['target_version_id'] = '';
            $data['affected_sections'] = '';
            return $data;
        }

        if (
            $currentVersionId !== null
            && $data['target_version_id'] === ''
            && in_array($data['change_type'], PdmsAuthoringOptions::changeTypesThatDefaultToCurrentTarget(), true)
        ) {
            $data['target_version_id'] = (string) $currentVersionId;
        }

        if (!in_array($data['relationship_type'], PdmsAuthoringOptions::relationshipTypesWithAffectedSections(), true)) {
            $data['affected_sections'] = '';
        }

        if ($data['relationship_type'] === '') {
            $data['target_version_id'] = '';
        }

        return $data;
    }

    private function assertAllowedAuthoringStatus($status) {
        $status = PdmsAuthoringOptions::normalizeWorkflowStatus($status, '');
        $allowed = PdmsAuthoringOptions::authoringWorkflowStatuses();

        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException(PdmsAuthoringOptions::invalidAuthoringStatusExceptionMessage($status));
        }
    }

    private function assertAllowedAuthoringChangeType($changeType, $authoringMode = 'create') {
        $allowed = $authoringMode === 'issue'
            ? PdmsAuthoringOptions::issueChangeTypes()
            : PdmsAuthoringOptions::createChangeTypes();
        $changeType = strtoupper((string) $changeType);

        if (!in_array($changeType, $allowed, true)) {
            throw new RuntimeException(PdmsAuthoringOptions::invalidAuthoringChangeTypeExceptionMessage($changeType, $allowed));
        }
    }

    private function assertAllowedAuthoringRelationshipType($relationshipType, $authoringMode = 'create') {
        if ($relationshipType === '') {
            return;
        }

        $allowed = $authoringMode === 'issue'
            ? PdmsAuthoringOptions::issueRelationshipTypes()
            : PdmsAuthoringOptions::createRelationshipTypes();
        $relationshipType = strtoupper((string) $relationshipType);

        if (!in_array($relationshipType, $allowed, true)) {
            throw new RuntimeException(PdmsAuthoringOptions::invalidAuthoringRelationshipTypeExceptionMessage($relationshipType, $allowed));
        }
    }

    private function defaultRelationshipTypeForChangeType($changeType) {
        return PdmsAuthoringOptions::defaultRelationshipTypeForChangeType($changeType);
    }

    private function resolveLegacyAmendedTargetId($relationshipType, $legacyTargetPostId) {
        return in_array($relationshipType, PdmsAuthoringOptions::relationshipTypesWithAffectedSections(), true) ? $legacyTargetPostId : null;
    }

    private function resolveLegacySupersededTargetId($relationshipType, $legacyTargetPostId) {
        return PdmsAuthoringOptions::pdmsRelationshipMode('', $relationshipType) === 'supersede' ? $legacyTargetPostId : null;
    }

    private function createLegacyPost($data) {
        $this->db->query(
            'INSERT INTO posts
                (title, description, reference_number, date_of_effectivity, upload_date, file, amended_post_id, superseded_post_id)
             VALUES
                (:title, :description, :reference_number, :date_of_effectivity, :upload_date, :file, :amended_post_id, :superseded_post_id)'
        );
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':reference_number', $data['reference_number']);
        $this->db->bind(':date_of_effectivity', $data['date_of_effectivity']);
        $this->db->bind(':upload_date', $data['upload_date']);
        $this->db->bind(':file', $data['file'] ?? null);
        $this->db->bind(':amended_post_id', $data['amended_post_id'] ?? null);
        $this->db->bind(':superseded_post_id', $data['superseded_post_id'] ?? null);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    private function createProcedure($data) {
        $this->db->query(
            'INSERT INTO procedures
                (procedure_code, title, description, category, owner_office, status, legacy_post_id, created_by)
             VALUES
                (:procedure_code, :title, :description, :category, :owner_office, :status, :legacy_post_id, :created_by)'
        );
        $this->db->bind(':procedure_code', $data['procedure_code']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':category', $data['category'] ?? null);
        $this->db->bind(':owner_office', $data['owner_office'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'ACTIVE');
        $this->db->bind(':legacy_post_id', $data['legacy_post_id'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    private function updateProcedureIdentity($procedureId, $data) {
        $this->db->query(
            'UPDATE procedures
             SET title = :title,
                 description = :description,
                 category = :category,
                 owner_office = :owner_office,
                 status = :status
             WHERE id = :id'
        );
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':category', $data['category'] ?? null);
        $this->db->bind(':owner_office', $data['owner_office'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'ACTIVE');
        $this->db->bind(':id', $procedureId, PDO::PARAM_INT);
        $this->db->execute();
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
        $this->db->bind(':change_type', $data['change_type'] ?? 'NEW');
        $this->db->bind(':effective_date', $data['effective_date'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'REGISTERED');
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':based_on_version_id', $data['based_on_version_id'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->execute();

        return (int) $this->db->lastInsertId();
    }

    private function updateVersion($versionId, $data) {
        $this->db->query(
            'UPDATE procedure_versions
             SET document_number = :document_number,
                 title = :title,
                 summary_of_change = :summary_of_change,
                 effective_date = :effective_date,
                 file_path = :file_path,
                 registered_by = :registered_by
             WHERE id = :id'
        );
        $this->db->bind(':registered_by', $data['registered_by'] ?? $data['created_by'] ?? null);

        $this->db->bind(':document_number', $data['document_number'] ?? null);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':summary_of_change', $data['summary_of_change'] ?? null);
        $this->db->bind(':effective_date', $data['effective_date'] ?? null);
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
        $this->db->execute();
    }

    private function createRelationship($data) {
        $hasManagementSource = $this->columnExists('document_relationships', 'management_source');

        if ($hasManagementSource) {
            $this->db->query(
                'INSERT INTO document_relationships
                    (source_version_id, target_version_id, relationship_type, affected_sections, remarks, management_source, created_by)
                 VALUES
                    (:source_version_id, :target_version_id, :relationship_type, :affected_sections, :remarks, :management_source, :created_by)'
            );
            $this->db->bind(':management_source', 'PDMS_AUTHORING');
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
        $this->db->bind(':relationship_type', strtoupper($data['relationship_type']));
        $this->db->bind(':affected_sections', $data['affected_sections'] ?? null);
        $this->db->bind(':remarks', $data['remarks'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->execute();

        $relationshipId = (int) $this->db->lastInsertId();
        $this->recordAffectedSectionHistory($relationshipId, $data);

        return $relationshipId;
    }

    private function recordLifecycleAction($data) {
        $this->db->query(
            'INSERT INTO workflow_actions
                (procedure_version_id, lifecycle_action_type, from_status, to_status, acted_by, remarks)
             VALUES
                (:procedure_version_id, :lifecycle_action_type, :from_status, :to_status, :acted_by, :remarks)'
        );
        $this->db->bind(':procedure_version_id', $data['procedure_version_id'], PDO::PARAM_INT);
        $this->db->bind(':lifecycle_action_type', strtoupper($data['lifecycle_action_type']));
        $this->db->bind(':from_status', $data['from_status'] ?? null);
        $this->db->bind(':to_status', $data['to_status'] ?? null);
        $this->db->bind(':acted_by', $data['acted_by'] ?? null);
        $this->db->bind(':remarks', $data['remarks'] ?? null);
        $this->db->execute();
    }

    private function logActivity($userId, $action, $description) {
        if (!$this->tableExists('activity_logs')) {
            return;
        }

        $this->db->query(
            'INSERT INTO activity_logs (user_id, action, description)
             VALUES (:user_id, :action, :description)'
        );
        $this->db->bind(':user_id', $userId ?? null);
        $this->db->bind(':action', $action);
        $this->db->bind(':description', $description);
        $this->db->execute();
    }

    private function setCurrentVersion($procedureId, $versionId) {
        $this->db->query(
            'UPDATE procedures
             SET current_version_id = :current_version_id
             WHERE id = :procedure_id'
        );
        $this->db->bind(':current_version_id', $versionId, PDO::PARAM_INT);
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);
        $this->db->execute();
    }

    private function clearCurrentVersion($procedureId) {
        $this->db->query(
            'UPDATE procedures
             SET current_version_id = NULL
             WHERE id = :procedure_id'
        );
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);
        $this->db->execute();
    }

    private function setVersionStatus($versionId, $status) {
        $this->db->query(
            'UPDATE procedure_versions
             SET status = :status
             WHERE id = :id'
        );
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
        $this->db->execute();
    }

    private function updateLegacyPost($postId, $data) {
        $this->db->query(
            'UPDATE posts
             SET title = :title,
                 description = :description,
                 reference_number = :reference_number,
                 date_of_effectivity = :date_of_effectivity,
                 file = :file
             WHERE id = :id'
        );
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':reference_number', $data['reference_number']);
        $this->db->bind(':date_of_effectivity', $data['date_of_effectivity'] ?? null);
        $this->db->bind(':file', $data['file'] ?? null);
        $this->db->bind(':id', $postId, PDO::PARAM_INT);
        $this->db->execute();
    }

    private function getVersionById($versionId) {
        $this->db->query('SELECT * FROM procedure_versions WHERE id = :id');
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function getVersionWithProcedureContext($versionId) {
        $this->db->query(
            'SELECT
                pv.*,
                p.procedure_code,
                p.current_version_id,
                p.status AS procedure_status
             FROM procedure_versions pv
             INNER JOIN procedures p ON p.id = pv.procedure_id
             WHERE pv.id = :id
             LIMIT 1'
        );
        $this->db->bind(':id', $versionId, PDO::PARAM_INT);
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
             WHERE p.id = :procedure_id'
        );
        $this->db->bind(':procedure_id', $procedureId, PDO::PARAM_INT);
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

    private function generateNextVersionNumber($procedureId, $changeType) {
        $latest = $this->getLatestVersionForProcedure($procedureId);

        if (!$latest || empty($latest->version_number)) {
            return '1.0';
        }

        $parts = explode('.', (string) $latest->version_number);
        $major = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : 1;
        $minor = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : 0;

        if ($changeType === 'AMENDMENT') {
            $minor++;
        } else {
            $major++;
            $minor = 0;
        }

        return $major . '.' . $minor;
    }

    private function updatePreviousCurrentVersionStatus($currentVersion, $changeType, $userId) {
        if (!$currentVersion) {
            return;
        }

        $targetStatus = PdmsAuthoringOptions::replacementStatusForPreviousCurrent($changeType);

        if (($currentVersion->status ?? null) === $targetStatus) {
            return;
        }

        $previousStatus = $currentVersion->status ?? null;
        $this->setVersionStatus((int) $currentVersion->id, $targetStatus);
        $this->recordLifecycleAction([
            'procedure_version_id' => (int) $currentVersion->id,
            'lifecycle_action_type' => 'PDMS_REGISTERED_REPLACEMENT',
            'from_status' => $previousStatus,
            'to_status' => $targetStatus,
            'acted_by' => $userId,
            'remarks' => 'A newer registered revision became the controlling version.'
        ]);
    }

    private function markTargetProcedureSuperseded($targetVersion, $userId) {
        $this->setVersionStatus((int) $targetVersion->id, 'SUPERSEDED');
        $this->recordLifecycleAction([
            'procedure_version_id' => (int) $targetVersion->id,
            'lifecycle_action_type' => 'PDMS_REGISTERED_SUPERSESSION',
            'from_status' => $targetVersion->status ?? null,
            'to_status' => 'SUPERSEDED',
            'acted_by' => $userId,
            'remarks' => 'This procedure version was superseded by a newly registered procedure.'
        ]);

        $this->db->query(
            'UPDATE procedures
             SET status = :status,
                 current_version_id = NULL
             WHERE id = :id'
        );
        $this->db->bind(':status', 'SUPERSEDED');
        $this->db->bind(':id', (int) $targetVersion->procedure_id, PDO::PARAM_INT);
        $this->db->execute();
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

    private function hasSectionHistoryFoundation() {
        return $this->tableExists('procedure_sections')
            && $this->tableExists('section_change_log');
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
