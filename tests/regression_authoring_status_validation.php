<?php
require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';
require_once __DIR__ . '/../app/models/ProcedureAuthoringService.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../app/controllers/Procedures.php';

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function newInstanceWithoutConstructor($className) {
    $reflection = new ReflectionClass($className);
    return $reflection->newInstanceWithoutConstructor();
}

function invokePrivateMethod($className, $instance, $methodName, array $args = []) {
    $method = new ReflectionMethod($className, $methodName);
    $method->setAccessible(true);
    return $method->invokeArgs($instance, $args);
}

function testControllerAllowedAuthoringStatuses() {
    $controller = newInstanceWithoutConstructor(Procedures::class);
    $allowed = invokePrivateMethod(Procedures::class, $controller, 'allowedAuthoringStatuses');

    assertTrue(
        $allowed === ['REGISTERED', 'EFFECTIVE'],
        'Procedures controller should only allow registry-oriented pre-terminal authoring states.'
    );
}

function testPdmsControllerOptionListsStayCentralized() {
    $controller = newInstanceWithoutConstructor(Procedures::class);
    $controller->procedureReadModel = new class {
        public function getProcedureDashboard($search = '') {
            return [];
        }
    };

    $createOptions = invokePrivateMethod(Procedures::class, $controller, 'createOptions');
    $issueOptions = invokePrivateMethod(Procedures::class, $controller, 'issueOptions', [77, (object) ['current_version_id' => 15]]);

    assertTrue(
        $createOptions['change_types'] === PdmsAuthoringOptions::createChangeTypes(),
        'PDMS create options should come from the shared authoring change-type list.'
    );
    assertTrue(
        $createOptions['relationship_types'] === PdmsAuthoringOptions::createRelationshipTypes(),
        'PDMS create options should come from the shared authoring relationship list.'
    );
    assertTrue(
        $issueOptions['change_types'] === PdmsAuthoringOptions::issueChangeTypes(),
        'PDMS issue options should come from the shared issuance change-type list.'
    );
    assertTrue(
        $issueOptions['relationship_types'] === PdmsAuthoringOptions::issueRelationshipTypes(),
        'PDMS issue options should come from the shared issuance relationship list.'
    );
}

function testSharedAuthoringRulePredicatesStayAligned() {
    assertTrue(
        PdmsAuthoringOptions::requiresAffectedSections('AMENDMENT', '') === true,
        'Shared authoring rules should keep amendment affected-sections requirements centralized.'
    );
    assertTrue(
        PdmsAuthoringOptions::requiresAffectedSections('FULL_REVISION', 'REVISES') === false,
        'Shared authoring rules should preserve current behavior where revisions allow but do not require affected sections.'
    );
    assertTrue(
        PdmsAuthoringOptions::requiresPdmsTargetVersion('SUPERSEDING_PROCEDURE', '') === true,
        'Shared authoring rules should require a PDMS target for superseding issuances.'
    );
    assertTrue(
        PdmsAuthoringOptions::allowsChangeTypeForAuthoringMode('SUPERSEDING_PROCEDURE', 'create') === true
            && PdmsAuthoringOptions::allowsChangeTypeForAuthoringMode('REFERENCE', 'create') === true,
        'Shared authoring rules should keep PDMS create choices aligned with the current centralized change-type list.'
    );
    assertTrue(
        PdmsAuthoringOptions::allowsRelationshipTypeForAuthoringMode('DERIVED_FROM', 'issue') === true
            && PdmsAuthoringOptions::allowsRelationshipTypeForAuthoringMode('RESCINDS', 'issue') === false,
        'Shared authoring rules should reflect the current centralized issuance relationship subset.'
    );
}

function testSharedAuthoringMessagesStayCentralized() {
    assertTrue(
        PdmsAuthoringOptions::invalidChangeTypeMessage('create') === 'Invalid PDMS change type selected. Allowed values: NEW, AMENDMENT, PARTIAL_REVISION, FULL_REVISION, SUPERSEDING_PROCEDURE, RESCISSION, and REFERENCE.',
        'Shared authoring messages should centralize the PDMS create change-type copy.'
    );
    assertTrue(
        PdmsAuthoringOptions::invalidRelationshipTypeMessage('issue') === 'Invalid PDMS relationship type selected for revision registration. Allowed values: AMENDS, REVISES, SUPERSEDES, REFERENCES, and DERIVED_FROM.',
        'Shared authoring messages should centralize the PDMS revision-registration relationship-type copy.'
    );
    assertTrue(
        PdmsAuthoringOptions::affectedSectionsRequiredMessage() === 'Amendments must capture the affected sections.',
        'Shared authoring messages should centralize the affected-sections requirement copy.'
    );
    assertTrue(
        PdmsAuthoringOptions::pdmsTargetRequiredMessage('SUPERSEDING_PROCEDURE', '', 'issue') === 'Superseding registrations must choose the current procedure/version being replaced',
        'Shared authoring messages should centralize superseding target guidance.'
    );
}

function testLegacyPolicySurfaceIsRetired() {
    $helperSource = file_get_contents(__DIR__ . '/../app/helpers/pdms_authoring_options.php');

    assertTrue($helperSource !== false, 'PDMS authoring helper should be readable for retirement checks.');

    assertTrue(
        strpos($helperSource, 'legacyCompatibilityChangeTypes') === false,
        'Legacy compatibility change-type helpers should be removed once posts writes are retired.'
    );
    assertTrue(
        strpos($helperSource, 'legacyCompatibilityRelationshipTypes') === false,
        'Legacy compatibility relationship helpers should be removed once posts writes are retired.'
    );
    assertTrue(
        strpos($helperSource, 'legacyCompatibilityUiRules') === false,
        'Legacy compatibility UI helper rules should be removed once posts writes are retired.'
    );
}

function testSharedLifecycleSemanticsStayCentralized() {
    assertTrue(
        PdmsAuthoringOptions::changeTypeUsesMinorVersionIncrement('AMENDMENT') === true
            && PdmsAuthoringOptions::changeTypeUsesMinorVersionIncrement('FULL_REVISION') === false,
        'Shared authoring policy should centralize which change types keep amendment-style minor version increments.'
    );
    assertTrue(
        PdmsAuthoringOptions::replacementStatusForPreviousCurrent('AMENDMENT') === 'EFFECTIVE'
            && PdmsAuthoringOptions::replacementStatusForPreviousCurrent('FULL_REVISION') === 'SUPERSEDED',
        'Shared authoring policy should centralize how previous controlling versions transition during PDMS revisions.'
    );
    assertTrue(
        PdmsAuthoringOptions::normalizeWorkflowStatus('APPROVED') === 'EFFECTIVE'
            && PdmsAuthoringOptions::normalizeWorkflowStatus('FOR_APPROVAL') === 'REGISTERED',
        'Shared authoring policy should centralize normalization of imported legacy workflow labels into registry states.'
    );
}

function testApprovalEraHelperNamesAreRetired() {
    $helperSource = file_get_contents(__DIR__ . '/../app/helpers/pdms_authoring_options.php');

    assertTrue($helperSource !== false, 'PDMS authoring helper should be readable for naming cleanup checks.');

    assertTrue(
        strpos($helperSource, 'changeTypePreservesPreviousCurrentAsApproved') === false,
        'Approval-era helper names should be retired from the PDMS authoring policy surface.'
    );
    assertTrue(
        strpos($helperSource, 'workflowStatusGetsApprovalDate') === false,
        'Approval-era date helper names should be retired from the PDMS authoring policy surface.'
    );
    assertTrue(
        strpos($helperSource, 'changeTypeKeepsPreviousCurrentEffective') !== false,
        'Registry-native helper names should describe keeping a prior controlling version effective when needed.'
    );
    assertTrue(
        strpos($helperSource, 'workflowStatusUsesEffectiveMetadata') === false,
        'Retired compatibility metadata helpers should be removed from the PDMS-only policy surface.'
    );
}

function testPdmsControllerNormalizesRelationshipInputs() {
    $controller = newInstanceWithoutConstructor(Procedures::class);
    $service = newInstanceWithoutConstructor(ProcedureAuthoringService::class);
    $controller->procedureAuthoringService = $service;

    $newIssuance = [
        'change_type' => 'NEW',
        'relationship_type' => 'AMENDS',
        'target_version_id' => '77',
        'affected_sections' => 'Section 1'
    ];

    $normalizedNew = invokePrivateMethod(Procedures::class, $controller, 'normalizeRelationshipAuthoringInput', [$newIssuance]);

    assertTrue($normalizedNew['relationship_type'] === '', 'NEW procedures should clear relationship_type during normalization.');
    assertTrue($normalizedNew['target_version_id'] === '', 'NEW procedures should clear target_version_id during normalization.');
    assertTrue($normalizedNew['affected_sections'] === '', 'NEW procedures should clear affected_sections during normalization.');

    $revisionIssuance = [
        'change_type' => 'FULL_REVISION',
        'relationship_type' => '',
        'target_version_id' => '',
        'affected_sections' => 'Sections 2-3'
    ];

    $normalizedRevision = invokePrivateMethod(Procedures::class, $controller, 'normalizeRelationshipAuthoringInput', [$revisionIssuance, 55]);

    assertTrue($normalizedRevision['relationship_type'] === 'REVISES', 'Full revisions should default to REVISES during normalization.');
    assertTrue($normalizedRevision['target_version_id'] === '55', 'Amendment-style issuances should default to the current version target during normalization.');
    assertTrue($normalizedRevision['affected_sections'] === 'Sections 2-3', 'Revision normalization should preserve affected sections.');

    $referenceIssuance = [
        'change_type' => 'REFERENCE',
        'relationship_type' => '',
        'target_version_id' => '91',
        'affected_sections' => 'Section 9'
    ];

    $normalizedReference = invokePrivateMethod(Procedures::class, $controller, 'normalizeRelationshipAuthoringInput', [$referenceIssuance]);

    assertTrue($normalizedReference['relationship_type'] === 'REFERENCES', 'Reference issuances should default to REFERENCES during normalization.');
    assertTrue($normalizedReference['affected_sections'] === '', 'Non-amendment relationships should clear affected sections during normalization.');
}

function testProceduresControllerSharedValidationFlow() {
    $controller = newInstanceWithoutConstructor(Procedures::class);
    $controller->procedureReadModel = new class {
        public function hasPdmsFoundation() {
            return false;
        }

        public function documentNumberExists($documentNumber, $excludeVersionId = null) {
            unset($documentNumber, $excludeVersionId);
            return false;
        }
    };
    $controller->procedureModel = new class {
        public function getByCode($code) {
            return false;
        }
    };

    $createData = [
        'procedure_code' => '',
        'title' => '',
        'description' => '',
        'document_number' => '',
        'change_type' => 'AMENDMENT',
        'status' => 'REGISTERED',
        'effective_date' => '',
        'target_version_id' => '',
        'relationship_type' => 'AMENDS',
        'affected_sections' => ''
    ];

    $validatedCreate = invokePrivateMethod(Procedures::class, $controller, 'validateCreateInput', [$createData]);

    assertTrue($validatedCreate['procedure_code_err'] !== '', 'Shared controller validation should preserve create-specific procedure code checks.');
    assertTrue($validatedCreate['title_err'] !== '', 'Shared controller validation should enforce required title checks.');
    assertTrue($validatedCreate['affected_sections_err'] !== '', 'Shared controller validation should enforce shared affected-sections checks.');
    assertTrue(
        invokePrivateMethod(Procedures::class, $controller, 'hasAuthoringValidationErrors', [$validatedCreate, 'create']) === true,
        'Create validation should report errors through the shared controller error gate.'
    );

    $issueData = [
        'title' => 'Issued title',
        'description' => 'Issued description',
        'document_number' => 'DOC-123',
        'change_type' => 'REFERENCE',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-03-13',
        'target_version_id' => '',
        'relationship_type' => '',
        'affected_sections' => '',
        'file_err' => '',
        'pdms_err' => ''
    ];

    $validatedIssue = invokePrivateMethod(Procedures::class, $controller, 'validateIssueInput', [$issueData, 77, 12]);

    assertTrue(
        invokePrivateMethod(Procedures::class, $controller, 'hasAuthoringValidationErrors', [$validatedIssue, 'issue']) === false,
        'Issue validation should allow clean authoring payloads through the shared controller error gate.'
    );
}

function testServiceNormalizesRelationshipInputsDirectly() {
    $service = newInstanceWithoutConstructor(ProcedureAuthoringService::class);

    $rescissionIssuance = [
        'change_type' => 'RESCISSION',
        'relationship_type' => '',
        'target_version_id' => '14',
        'affected_sections' => 'Section 5'
    ];

    $normalizedRescission = $service->normalizeRelationshipAuthoringInput($rescissionIssuance);

    assertTrue($normalizedRescission['relationship_type'] === 'RESCINDS', 'Service normalization should map rescission change type to RESCINDS.');
    assertTrue($normalizedRescission['affected_sections'] === '', 'Service normalization should clear affected sections for rescission inputs.');
}

function testServiceRejectsTerminalAuthoringStatuses() {
    $service = newInstanceWithoutConstructor(ProcedureAuthoringService::class);

    try {
        invokePrivateMethod(ProcedureAuthoringService::class, $service, 'assertAllowedAuthoringStatus', ['RESCINDED']);
        throw new RuntimeException('ProcedureAuthoringService should reject terminal authoring statuses.');
    } catch (ReflectionException $e) {
        throw $e;
    } catch (RuntimeException $e) {
        assertTrue(
            strpos($e->getMessage(), 'Invalid PDMS authoring status') !== false,
            'ProcedureAuthoringService should explain invalid authoring statuses clearly.'
        );
    }

    assertTrue(
        invokePrivateMethod(ProcedureAuthoringService::class, $service, 'assertAllowedAuthoringStatus', ['EFFECTIVE']) === null,
        'ProcedureAuthoringService should accept EFFECTIVE as a valid authoring status.'
    );
}

function testServiceRejectsOutOfScopeAuthoringChoices() {
    $service = newInstanceWithoutConstructor(ProcedureAuthoringService::class);

    try {
        invokePrivateMethod(ProcedureAuthoringService::class, $service, 'assertAllowedAuthoringChangeType', ['RESCISSION', 'issue']);
        throw new RuntimeException('ProcedureAuthoringService should reject issue-only change types outside the centralized issuance list.');
    } catch (ReflectionException $e) {
        throw $e;
    } catch (RuntimeException $e) {
        assertTrue(
            strpos($e->getMessage(), 'Invalid PDMS authoring change type') !== false,
            'ProcedureAuthoringService should explain invalid issue change types clearly.'
        );
    }

    try {
        invokePrivateMethod(ProcedureAuthoringService::class, $service, 'assertAllowedAuthoringRelationshipType', ['RESCINDS', 'issue']);
        throw new RuntimeException('ProcedureAuthoringService should reject issue relationship types outside the centralized issuance list.');
    } catch (ReflectionException $e) {
        throw $e;
    } catch (RuntimeException $e) {
        assertTrue(
            strpos($e->getMessage(), 'Invalid PDMS authoring relationship type') !== false,
            'ProcedureAuthoringService should explain invalid issue relationship types clearly.'
        );
    }

    assertTrue(
        invokePrivateMethod(ProcedureAuthoringService::class, $service, 'assertAllowedAuthoringChangeType', ['REFERENCE', 'issue']) === null,
        'ProcedureAuthoringService should accept valid centralized issue change types.'
    );
    assertTrue(
        invokePrivateMethod(ProcedureAuthoringService::class, $service, 'assertAllowedAuthoringRelationshipType', ['DERIVED_FROM', 'issue']) === null,
        'ProcedureAuthoringService should accept valid centralized issue relationship types.'
    );
}

function testLegacyCompatibilityUiHooksAreRetired() {
    $createViewExists = file_exists(__DIR__ . '/../app/views/posts/create.php');
    $editViewExists = file_exists(__DIR__ . '/../app/views/posts/edit.php');
    $mainJs = file_get_contents(__DIR__ . '/../public/js/main.js');

    assertTrue($mainJs !== false, 'Shared main.js should exist for UI helper checks.');

    assertTrue(
        $createViewExists === false && $editViewExists === false,
        'Legacy compatibility create/edit views should be removed once legacy writes are retired.'
    );

    assertTrue(
        strpos($mainJs, 'data-legacy-compat-form') === false,
        'Shared main.js should no longer include retired legacy compatibility form handling.'
    );
}

function testPdmsAuthoringUiHelperHooksExist() {
    $createView = file_get_contents(__DIR__ . '/../app/views/procedures/create.php');
    $issueView = file_get_contents(__DIR__ . '/../app/views/procedures/issue.php');
    $mainJs = file_get_contents(__DIR__ . '/../public/js/main.js');

    assertTrue($createView !== false, 'PDMS create view should be readable for UI helper checks.');
    assertTrue($issueView !== false, 'PDMS issue view should be readable for UI helper checks.');
    assertTrue($mainJs !== false, 'Shared main.js should exist for PDMS UI helper checks.');

    assertTrue(
        strpos($createView, 'data-pdms-authoring-form') !== false,
        'PDMS create view should expose the shared authoring UI helper hook.'
    );

    assertTrue(
        strpos($issueView, 'data-pdms-authoring-form') !== false,
        'PDMS issue view should expose the shared authoring UI helper hook.'
    );

    assertTrue(
        strpos($createView, 'data-authoring-rules') !== false && strpos($issueView, 'data-authoring-rules') !== false,
        'PDMS authoring views should expose shared rule metadata for the UI helper.'
    );

    assertTrue(
        strpos($mainJs, 'data-pdms-authoring-form') !== false && strpos($mainJs, 'resolveMode') !== false,
        'Shared main.js should guide PDMS authoring form state from the shared rule metadata.'
    );
}

function runRegressionSuite() {
    testControllerAllowedAuthoringStatuses();
    testPdmsControllerOptionListsStayCentralized();
    testSharedAuthoringRulePredicatesStayAligned();
    testSharedAuthoringMessagesStayCentralized();
    testSharedLifecycleSemanticsStayCentralized();
    testApprovalEraHelperNamesAreRetired();
    testPdmsControllerNormalizesRelationshipInputs();
    testProceduresControllerSharedValidationFlow();
    testServiceNormalizesRelationshipInputsDirectly();
    testServiceRejectsTerminalAuthoringStatuses();
    testServiceRejectsOutOfScopeAuthoringChoices();
    testLegacyPolicySurfaceIsRetired();
    testLegacyCompatibilityUiHooksAreRetired();
    testPdmsAuthoringUiHelperHooksExist();
    echo "Authoring status validation regression: OK\n";
}

runRegressionSuite();
