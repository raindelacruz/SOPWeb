<?php
class PdmsAuthoringOptions {
    public static function versionWorkflowStatuses() {
        return ['REGISTERED', 'EFFECTIVE', 'SUPERSEDED', 'RESCINDED', 'ARCHIVED'];
    }

    public static function authoringWorkflowStatuses() {
        return ['REGISTERED', 'EFFECTIVE'];
    }

    public static function legacyWorkflowStatuses() {
        return ['DRAFT', 'FOR_REVIEW', 'FOR_APPROVAL', 'APPROVED'];
    }

    public static function recognizedWorkflowStatuses() {
        return array_merge(self::versionWorkflowStatuses(), self::legacyWorkflowStatuses());
    }

    public static function normalizeWorkflowStatus($status, $default = 'REGISTERED') {
        $status = strtoupper((string) $status);

        if ($status === '') {
            return $default;
        }

        if (in_array($status, ['DRAFT', 'FOR_REVIEW', 'FOR_APPROVAL', 'REGISTERED'], true)) {
            return 'REGISTERED';
        }

        if (in_array($status, ['APPROVED', 'EFFECTIVE'], true)) {
            return 'EFFECTIVE';
        }

        if (in_array($status, ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            return $status;
        }

        return $default;
    }

    public static function isRecognizedWorkflowStatus($status) {
        return in_array(strtoupper((string) $status), self::recognizedWorkflowStatuses(), true);
    }

    public static function storedControllingWorkflowStatuses() {
        return self::controllingWorkflowStatuses();
    }

    public static function controllingWorkflowStatuses() {
        return ['EFFECTIVE'];
    }

    public static function createChangeTypes() {
        return ['NEW', 'AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION', 'SUPERSEDING_PROCEDURE', 'RESCISSION', 'REFERENCE'];
    }

    public static function issueChangeTypes() {
        return ['AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION', 'SUPERSEDING_PROCEDURE', 'REFERENCE'];
    }

    public static function legacyCompatibilityChangeTypes() {
        return ['NEW', 'AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION', 'SUPERSEDING_PROCEDURE'];
    }

    public static function createRelationshipTypes() {
        return ['AMENDS', 'REVISES', 'SUPERSEDES', 'RESCINDS', 'REFERENCES', 'DERIVED_FROM'];
    }

    public static function issueRelationshipTypes() {
        return ['AMENDS', 'REVISES', 'SUPERSEDES', 'REFERENCES', 'DERIVED_FROM'];
    }

    public static function normalizedRelationshipTypes() {
        return ['AMENDS', 'REVISES', 'SUPERSEDES', 'RESCINDS', 'REFERENCES', 'DERIVED_FROM'];
    }

    public static function legacyCompatibilityRelationshipTypes() {
        return ['AMENDS', 'REVISES', 'SUPERSEDES'];
    }

    public static function defaultRelationshipTypeForChangeType($changeType) {
        $map = [
            'AMENDMENT' => 'AMENDS',
            'PARTIAL_REVISION' => 'REVISES',
            'FULL_REVISION' => 'REVISES',
            'SUPERSEDING_PROCEDURE' => 'SUPERSEDES',
            'RESCISSION' => 'RESCINDS',
            'REFERENCE' => 'REFERENCES'
        ];

        return $map[strtoupper((string) $changeType)] ?? '';
    }

    public static function relationshipTypesWithAffectedSections() {
        return ['AMENDS', 'REVISES'];
    }

    public static function requiresAffectedSections($changeType, $relationshipType = '') {
        $changeType = strtoupper((string) $changeType);
        $relationshipType = strtoupper((string) $relationshipType);

        return $changeType === 'AMENDMENT' || $relationshipType === 'AMENDS';
    }

    public static function changeTypesThatDefaultToCurrentTarget() {
        return ['AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION'];
    }

    public static function changeTypesThatAutoTargetCurrentForIssuance() {
        return ['AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION', 'REFERENCE'];
    }

    public static function pdmsRelationshipMode($changeType, $relationshipType = '') {
        $changeType = strtoupper((string) $changeType);
        $relationshipType = strtoupper((string) $relationshipType);

        if (in_array($relationshipType, ['AMENDS', 'REVISES'], true)) {
            return 'amend';
        }

        if (in_array($relationshipType, ['SUPERSEDES', 'RESCINDS'], true)) {
            return 'supersede';
        }

        if ($relationshipType === 'REFERENCES' || $changeType === 'REFERENCE') {
            return 'reference';
        }

        if ($relationshipType === 'DERIVED_FROM') {
            return 'derived';
        }

        if (in_array($changeType, ['AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION'], true)) {
            return 'amend';
        }

        if (in_array($changeType, ['SUPERSEDING_PROCEDURE', 'RESCISSION'], true)) {
            return 'supersede';
        }

        return 'neutral';
    }

    public static function requiresPdmsTargetVersion($changeType, $relationshipType = '') {
        $changeType = strtoupper((string) $changeType);
        $relationshipType = strtoupper((string) $relationshipType);

        if ($relationshipType !== '') {
            return true;
        }

        return $changeType === 'SUPERSEDING_PROCEDURE';
    }

    public static function requiresLegacyAmendedTarget($changeType, $relationshipType = '') {
        $changeType = strtoupper((string) $changeType);
        $relationshipType = strtoupper((string) $relationshipType);

        return $changeType === 'AMENDMENT' || $relationshipType === 'AMENDS';
    }

    public static function requiresLegacySupersededTarget($changeType, $relationshipType = '') {
        $changeType = strtoupper((string) $changeType);
        $relationshipType = strtoupper((string) $relationshipType);

        return in_array($changeType, ['SUPERSEDING_PROCEDURE', 'RESCISSION'], true)
            || in_array($relationshipType, ['SUPERSEDES', 'RESCINDS'], true);
    }

    public static function clearsRelationshipForNew($changeType) {
        return strtoupper((string) $changeType) === 'NEW';
    }

    public static function currentEligibleWorkflowStatuses() {
        return ['EFFECTIVE'];
    }

    public static function terminalProcedureStatuses() {
        return ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'];
    }

    public static function inferLegacyChangeTypeFromLinks($amendedTargetId, $supersededTargetId) {
        if (!empty($supersededTargetId)) {
            return 'SUPERSEDING_PROCEDURE';
        }

        if (!empty($amendedTargetId)) {
            return 'AMENDMENT';
        }

        return 'NEW';
    }

    public static function changeTypeUsesAmendedLegacyTarget($changeType) {
        return in_array(strtoupper((string) $changeType), ['AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION', 'REFERENCE'], true);
    }

    public static function changeTypeUsesSupersededLegacyTarget($changeType) {
        return in_array(strtoupper((string) $changeType), ['SUPERSEDING_PROCEDURE', 'RESCISSION'], true);
    }

    public static function changeTypePreservesPreviousCurrentAsEffective($changeType) {
        return strtoupper((string) $changeType) === 'AMENDMENT';
    }

    public static function replacementStatusForPreviousCurrent($changeType) {
        return self::changeTypePreservesPreviousCurrentAsEffective($changeType) ? 'EFFECTIVE' : 'SUPERSEDED';
    }

    public static function changeTypeUsesMinorVersionIncrement($changeType) {
        return strtoupper((string) $changeType) === 'AMENDMENT';
    }

    public static function workflowStatusGetsEffectiveMetadata($status) {
        return self::normalizeWorkflowStatus($status, '') === 'EFFECTIVE';
    }

    public static function changeTypePreservesPreviousCurrentAsApproved($changeType) {
        return self::changeTypePreservesPreviousCurrentAsEffective($changeType);
    }

    public static function workflowStatusGetsApprovalDate($status) {
        return self::workflowStatusGetsEffectiveMetadata($status);
    }

    public static function allowsRelationshipTypeForAuthoringMode($relationshipType, $authoringMode = 'create') {
        $relationshipType = strtoupper((string) $relationshipType);
        if ($authoringMode === 'issue') {
            $allowed = self::issueRelationshipTypes();
        } elseif ($authoringMode === 'legacy') {
            $allowed = self::legacyCompatibilityRelationshipTypes();
        } else {
            $allowed = self::createRelationshipTypes();
        }

        return $relationshipType === '' || in_array($relationshipType, $allowed, true);
    }

    public static function allowsChangeTypeForAuthoringMode($changeType, $authoringMode = 'create') {
        $changeType = strtoupper((string) $changeType);
        if ($authoringMode === 'issue') {
            $allowed = self::issueChangeTypes();
        } elseif ($authoringMode === 'legacy') {
            $allowed = self::legacyCompatibilityChangeTypes();
        } else {
            $allowed = self::createChangeTypes();
        }

        return in_array($changeType, $allowed, true);
    }

    public static function formatList(array $values) {
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $values[0];
        }

        if ($count === 2) {
            return $values[0] . ' and ' . $values[1];
        }

        $last = array_pop($values);
        return implode(', ', $values) . ', and ' . $last;
    }

    public static function legacyCompatibilityUiRules() {
        return [
            'change_type_modes' => [
                'AMENDMENT' => 'amend',
                'PARTIAL_REVISION' => 'amend',
                'FULL_REVISION' => 'amend',
                'SUPERSEDING_PROCEDURE' => 'supersede'
            ],
            'relationship_type_modes' => [
                'AMENDS' => 'amend',
                'REVISES' => 'amend',
                'SUPERSEDES' => 'supersede'
            ],
            'helper_messages' => [
                'amend' => 'Bridge-safe amendment or revision mode: select the amended SOP target, leave superseded target unused, and capture affected sections.',
                'supersede' => 'Bridge-safe supersession mode: select the superseded SOP target. Affected sections are not needed for this path.',
                'neutral' => 'Auto-detect mode: use only one target path at a time. Choose amendment or revision intent for amended targets, or superseding intent for superseded targets.'
            ]
        ];
    }

    public static function pdmsAuthoringUiRules() {
        return [
            'change_type_modes' => [
                'AMENDMENT' => 'amend',
                'PARTIAL_REVISION' => 'amend',
                'FULL_REVISION' => 'amend',
                'SUPERSEDING_PROCEDURE' => 'supersede',
                'RESCISSION' => 'supersede',
                'REFERENCE' => 'reference'
            ],
            'relationship_type_modes' => [
                'AMENDS' => 'amend',
                'REVISES' => 'amend',
                'SUPERSEDES' => 'supersede',
                'RESCINDS' => 'supersede',
                'REFERENCES' => 'reference',
                'DERIVED_FROM' => 'derived'
            ],
            'helper_messages' => [
                'amend' => 'Amendment or revision mode: point to the related current version and capture affected sections.',
                'supersede' => 'Supersession mode: choose the current version being replaced. Affected sections are not required for this path.',
                'reference' => 'Reference mode: choose a related current version when this registry record should carry an explicit reference link. Affected sections are usually not needed.',
                'derived' => 'Derivation mode: choose the source current version when this registry record is derived from another procedure. Affected sections are usually not needed.',
                'neutral' => 'Optional relationship mode: choose a target version only when this registry record should link to another current procedure. Amendments require affected sections.'
            ]
        ];
    }

    public static function invalidChangeTypeMessage($authoringMode = 'create') {
        if ($authoringMode === 'issue') {
            return 'Invalid PDMS change type selected for revision registration. Allowed values: ' . self::formatList(self::issueChangeTypes()) . '.';
        }

        if ($authoringMode === 'legacy') {
            return 'Legacy compatibility maintenance only supports ' . self::formatList(self::legacyCompatibilityChangeTypes()) . ' change types.';
        }

        return 'Invalid PDMS change type selected. Allowed values: ' . self::formatList(self::createChangeTypes()) . '.';
    }

    public static function invalidRelationshipTypeMessage($authoringMode = 'create') {
        if ($authoringMode === 'issue') {
            return 'Invalid PDMS relationship type selected for revision registration. Allowed values: ' . self::formatList(self::issueRelationshipTypes()) . '.';
        }

        if ($authoringMode === 'legacy') {
            return 'Legacy compatibility maintenance only supports ' . self::formatList(self::legacyCompatibilityRelationshipTypes()) . ' relationship intent.';
        }

        return 'Invalid PDMS relationship type selected. Allowed values: ' . self::formatList(self::createRelationshipTypes()) . '.';
    }

    public static function invalidWorkflowStatusMessage($authoringMode = 'create') {
        if ($authoringMode === 'legacy') {
            return 'Legacy compatibility maintenance only supports registry-compatible pre-terminal states.';
        }

        return 'Invalid PDMS registry state selected for authoring.';
    }

    public static function affectedSectionsRequiredMessage() {
        return 'Amendments must capture the affected sections.';
    }

    public static function pdmsTargetRequiredMessage($changeType, $relationshipType = '', $authoringMode = 'create') {
        $changeType = strtoupper((string) $changeType);
        $mode = self::pdmsRelationshipMode($changeType, $relationshipType);

        if ($authoringMode === 'issue' && $changeType === 'SUPERSEDING_PROCEDURE') {
            return 'Superseding registrations must choose the current procedure/version being replaced';
        }

        if ($authoringMode === 'issue') {
            return 'Please choose the related procedure version for this registration';
        }

        if ($mode === 'amend') {
            return 'This relationship requires a target procedure version';
        }

        if ($mode === 'supersede') {
            return 'This relationship requires the procedure being replaced or rescinded';
        }

        return 'Please choose the current procedure/version this registry record relates to';
    }

    public static function newChangeTypeCannotHaveRelationshipMessage() {
        return 'Use a non-NEW change type when creating a linked registry record';
    }

    public static function legacyAmendedTargetRequiredMessage($relationshipDriven = false) {
        if ($relationshipDriven) {
            return 'An AMENDS relationship requires an amended target SOP.';
        }

        return 'Amendments must select the SOP being amended.';
    }

    public static function legacySupersededTargetRequiredMessage($relationshipDriven = false) {
        if ($relationshipDriven) {
            return 'This relationship type requires a superseded target SOP.';
        }

        return 'This change type must select the SOP being replaced or rescinded.';
    }

    public static function legacyRelationshipNeedsTargetMessage() {
        return 'Choose a related SOP target before setting a relationship intent.';
    }

    public static function invalidAuthoringStatusExceptionMessage($status) {
        return 'Invalid PDMS authoring status: ' . $status . '.';
    }

    public static function invalidAuthoringChangeTypeExceptionMessage($changeType, array $allowed) {
        return 'Invalid PDMS authoring change type: ' . $changeType . '. Allowed values: ' . self::formatList($allowed) . '.';
    }

    public static function invalidAuthoringRelationshipTypeExceptionMessage($relationshipType, array $allowed) {
        return 'Invalid PDMS authoring relationship type: ' . $relationshipType . '. Allowed values: ' . self::formatList($allowed) . '.';
    }

    public static function supersedingProcedureStatusMessage() {
        return 'A superseding procedure must be created as REGISTERED or EFFECTIVE.';
    }
}
