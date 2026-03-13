<?php
require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';
require_once __DIR__ . '/../app/models/ProcedureAuthoringService.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../app/controllers/Procedures.php';
require_once __DIR__ . '/../app/controllers/Posts.php';

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
        PdmsAuthoringOptions::requiresLegacyAmendedTarget('AMENDMENT', '') === true,
        'Shared authoring rules should require an amended legacy target for amendment compatibility flows.'
    );
    assertTrue(
        PdmsAuthoringOptions::requiresLegacySupersededTarget('', 'SUPERSEDES') === true,
        'Shared authoring rules should require a superseded legacy target for supersession compatibility flows.'
    );
    assertTrue(
        PdmsAuthoringOptions::allowsChangeTypeForAuthoringMode('SUPERSEDING_PROCEDURE', 'legacy') === true
            && PdmsAuthoringOptions::allowsChangeTypeForAuthoringMode('REFERENCE', 'legacy') === false,
        'Shared authoring rules should keep legacy compatibility choices limited to the bridge-safe change-type subset.'
    );
    assertTrue(
        PdmsAuthoringOptions::allowsRelationshipTypeForAuthoringMode('DERIVED_FROM', 'issue') === true
            && PdmsAuthoringOptions::allowsRelationshipTypeForAuthoringMode('RESCINDS', 'issue') === false,
        'Shared authoring rules should reflect the current centralized issuance relationship subset.'
    );
}

function testSharedAuthoringMessagesStayCentralized() {
    assertTrue(
        PdmsAuthoringOptions::invalidChangeTypeMessage('legacy') === 'Legacy compatibility maintenance only supports NEW, AMENDMENT, PARTIAL_REVISION, FULL_REVISION, and SUPERSEDING_PROCEDURE change types.',
        'Shared authoring messages should centralize the legacy bridge-safe change-type copy.'
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

function testSharedSyncSemanticsStayCentralized() {
    assertTrue(
        PdmsAuthoringOptions::changeTypeUsesMinorVersionIncrement('AMENDMENT') === true
            && PdmsAuthoringOptions::changeTypeUsesMinorVersionIncrement('FULL_REVISION') === false,
        'Shared authoring policy should centralize which change types keep amendment-style minor version increments.'
    );
    assertTrue(
        PdmsAuthoringOptions::changeTypeUsesAmendedLegacyTarget('REFERENCE') === true
            && PdmsAuthoringOptions::changeTypeUsesSupersededLegacyTarget('REFERENCE') === false,
        'Shared authoring policy should centralize which change types can map to amended legacy targets during sync.'
    );
    assertTrue(
        PdmsAuthoringOptions::changeTypeUsesSupersededLegacyTarget('RESCISSION') === true,
        'Shared authoring policy should centralize which change types can map to superseded legacy targets during sync.'
    );
    assertTrue(
        PdmsAuthoringOptions::workflowStatusGetsEffectiveMetadata('EFFECTIVE') === true
            && PdmsAuthoringOptions::workflowStatusGetsEffectiveMetadata('REGISTERED') === false,
        'Shared authoring policy should centralize EFFECTIVE-state compatibility metadata for synced registry states.'
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
    };
    $controller->procedureModel = new class {
        public function getByCode($code) {
            return false;
        }
    };
    $controller->postModel = new class {
        public function findPostByReferenceNumber($referenceNumber) {
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

function testLegacyCompatibilityWorkflowStatusesStayPreTerminal() {
    $controller = newInstanceWithoutConstructor(Posts::class);
    $allowed = invokePrivateMethod(Posts::class, $controller, 'allowedLegacyCompatibilityWorkflowStatuses');

    assertTrue(
        $allowed === ['REGISTERED', 'EFFECTIVE'],
        'Legacy compatibility workflow metadata should stay limited to registry-oriented pre-terminal states.'
    );

    $data = [
        'change_type' => '',
        'workflow_status' => 'RESCINDED',
        'pdms_relationship_type' => '',
        'affected_sections' => '',
        'amended_post_id' => null,
        'superseded_post_id' => null
    ];

    $errors = invokePrivateMethod(Posts::class, $controller, 'validatePdmsInput', [$data]);

    assertTrue(
        strpos((string) ($errors['workflow_status_err'] ?? ''), 'registry-compatible pre-terminal states') !== false,
        'Legacy compatibility validation should reject terminal workflow statuses clearly.'
    );
}

function testPostsControllerSharedLegacyValidationFlow() {
    $controller = newInstanceWithoutConstructor(Posts::class);
    $controller->postModel = new class {
        public function findPostByReferenceNumber($referenceNumber) {
            return false;
        }

        public function getPostById($id) {
            return null;
        }
    };

    $invalidData = invokePrivateMethod(Posts::class, $controller, 'applySharedLegacyFieldValidation', [[
        'title' => '',
        'description' => '',
        'reference_number' => '',
        'date_of_effectivity' => '',
        'file_err' => '',
        'amended_post_id' => null,
        'superseded_post_id' => null,
        'change_type' => 'AMENDMENT',
        'workflow_status' => 'EFFECTIVE',
        'pdms_relationship_type' => 'AMENDS',
        'affected_sections' => '',
        'pdms_err' => ''
    ]]);

    assertTrue($invalidData['title_err'] !== '', 'Shared legacy validation should enforce required title checks.');
    assertTrue($invalidData['amended_post_id_err'] !== '', 'Shared legacy validation should enforce amended-target requirements.');
    assertTrue(
        invokePrivateMethod(Posts::class, $controller, 'hasLegacyValidationErrors', [$invalidData]) === true,
        'Shared legacy validation should report invalid payloads through the centralized legacy error gate.'
    );

    $validData = invokePrivateMethod(Posts::class, $controller, 'applySharedLegacyFieldValidation', [[
        'title' => 'Legacy title',
        'description' => 'Legacy description',
        'reference_number' => 'LEG-100',
        'date_of_effectivity' => '2026-03-13',
        'file_err' => '',
        'amended_post_id' => null,
        'superseded_post_id' => null,
        'change_type' => '',
        'workflow_status' => 'EFFECTIVE',
        'pdms_relationship_type' => '',
        'affected_sections' => '',
        'pdms_err' => ''
    ]]);

    assertTrue(
        invokePrivateMethod(Posts::class, $controller, 'hasLegacyValidationErrors', [$validData]) === false,
        'Shared legacy validation should allow clean compatibility payloads through the centralized legacy error gate.'
    );
}

function testLegacyTargetNormalizationRejectsUnknownPosts() {
    $controller = newInstanceWithoutConstructor(Posts::class);
    $controller->postModel = new class {
        public function getPostById($id) {
            return null;
        }
    };

    assertTrue(
        invokePrivateMethod(Posts::class, $controller, 'normalizeLegacyTargetId', ['999']) === null,
        'Legacy target normalization should clear unknown related post IDs before save.'
    );
}

function testLegacyCompatibilityChangeTypesStayBridgeSafe() {
    $controller = newInstanceWithoutConstructor(Posts::class);
    $allowed = invokePrivateMethod(Posts::class, $controller, 'allowedLegacyCompatibilityChangeTypes');

    assertTrue(
        $allowed === ['NEW', 'AMENDMENT', 'PARTIAL_REVISION', 'FULL_REVISION', 'SUPERSEDING_PROCEDURE'],
        'Legacy compatibility change types should stay limited to the bridge-safe subset.'
    );

    $data = [
        'change_type' => 'REFERENCE',
        'workflow_status' => 'EFFECTIVE',
        'pdms_relationship_type' => '',
        'affected_sections' => '',
        'amended_post_id' => null,
        'superseded_post_id' => null
    ];

    $errors = invokePrivateMethod(Posts::class, $controller, 'validatePdmsInput', [$data]);

    assertTrue(
        strpos((string) ($errors['change_type_err'] ?? ''), 'NEW, AMENDMENT, PARTIAL_REVISION, FULL_REVISION, and SUPERSEDING_PROCEDURE') !== false,
        'Legacy compatibility validation should reject non-bridge-safe change types clearly.'
    );
}

function testLegacyCompatibilityRelationshipTypesStayBridgeSafe() {
    $controller = newInstanceWithoutConstructor(Posts::class);
    $allowed = invokePrivateMethod(Posts::class, $controller, 'allowedLegacyCompatibilityRelationshipTypes');

    assertTrue(
        $allowed === ['AMENDS', 'REVISES', 'SUPERSEDES'],
        'Legacy compatibility relationship intent should stay limited to the bridge-safe subset.'
    );

    $data = [
        'change_type' => '',
        'workflow_status' => 'EFFECTIVE',
        'pdms_relationship_type' => 'DERIVED_FROM',
        'affected_sections' => '',
        'amended_post_id' => 42,
        'superseded_post_id' => null
    ];

    $errors = invokePrivateMethod(Posts::class, $controller, 'validatePdmsInput', [$data]);

    assertTrue(
        strpos((string) ($errors['pdms_relationship_type_err'] ?? ''), 'AMENDS, REVISES, and SUPERSEDES') !== false,
        'Legacy compatibility validation should reject non-bridge-safe relationship types clearly.'
    );
}

function testLegacyCompatibilityUiHelperHooksExist() {
    $createView = file_get_contents(__DIR__ . '/../app/views/posts/create.php');
    $editView = file_get_contents(__DIR__ . '/../app/views/posts/edit.php');
    $mainJs = file_get_contents(__DIR__ . '/../public/js/main.js');

    assertTrue($createView !== false, 'Legacy create view should be readable for UI helper checks.');
    assertTrue($editView !== false, 'Legacy edit view should be readable for UI helper checks.');
    assertTrue($mainJs !== false, 'Shared main.js should exist for UI helper checks.');

    assertTrue(
        strpos($createView, 'data-legacy-compat-form') !== false,
        'Legacy compatibility create view should expose the shared UI helper hook.'
    );

    assertTrue(
        strpos($editView, 'data-legacy-compat-form') !== false,
        'Legacy compatibility edit view should expose the shared UI helper hook.'
    );

    assertTrue(
        strpos($createView, 'data-authoring-rules') !== false && strpos($editView, 'data-authoring-rules') !== false,
        'Legacy compatibility views should expose shared authoring rule metadata for the UI helper.'
    );

    assertTrue(
        strpos($mainJs, 'data-legacy-compat-form') !== false && strpos($mainJs, 'parseAuthoringRules') !== false,
        'Shared main.js should guide legacy compatibility form state from the shared rule metadata.'
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
    testSharedSyncSemanticsStayCentralized();
    testPdmsControllerNormalizesRelationshipInputs();
    testProceduresControllerSharedValidationFlow();
    testServiceNormalizesRelationshipInputsDirectly();
    testServiceRejectsTerminalAuthoringStatuses();
    testServiceRejectsOutOfScopeAuthoringChoices();
    testLegacyCompatibilityWorkflowStatusesStayPreTerminal();
    testPostsControllerSharedLegacyValidationFlow();
    testLegacyTargetNormalizationRejectsUnknownPosts();
    testLegacyCompatibilityChangeTypesStayBridgeSafe();
    testLegacyCompatibilityRelationshipTypesStayBridgeSafe();
    testLegacyCompatibilityUiHelperHooksExist();
    testPdmsAuthoringUiHelperHooksExist();
    echo "Authoring status validation regression: OK\n";
}

runRegressionSuite();
