<?php
require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';

if (!class_exists('Controller')) {
    class Controller {
        public $renderedView = null;
        public $renderedData = null;

        public function model($model) {
            unset($model);
            return null;
        }

        public function view($view, $data = []) {
            $this->renderedView = $view;
            $this->renderedData = $data;
        }
    }
}

if (!class_exists('Middleware')) {
    class Middleware {
        public static function checkAdmin() {
            return true;
        }

        public static function checkLoggedIn() {
            return true;
        }
    }
}

$GLOBALS['controller_test_redirects'] = [];
$GLOBALS['controller_test_flashes'] = [];
$GLOBALS['controller_test_csrf_failures'] = [];

function flash($name = '', $message = '', $class = 'alert alert-success') {
    $GLOBALS['controller_test_flashes'][] = [
        'name' => $name,
        'message' => $message,
        'class' => $class
    ];
}

function redirect($url) {
    $GLOBALS['controller_test_redirects'][] = $url;
}

function verify_csrf_token($token) {
    return $token === 'valid-token';
}

function handle_csrf_failure($url, $message = 'Your session expired. Please try again.') {
    $GLOBALS['controller_test_csrf_failures'][] = [
        'url' => $url,
        'message' => $message
    ];
}

function csrf_input() {
    return '';
}

function loadTestProceduresController() {
    if (class_exists('TestProceduresController')) {
        return;
    }

    $source = file_get_contents(__DIR__ . '/../app/controllers/Procedures.php');
    if ($source === false) {
        throw new RuntimeException('Unable to read Procedures controller source.');
    }

    $source = str_replace("require_once __DIR__ . '/../helpers/pdms_authoring_options.php';", '', $source);
    $source = str_replace('class Procedures extends Controller {', 'class TestProceduresController extends Controller {', $source);
    $source = str_replace('private function processPdfUpload($file, $isRequired = true) {', 'protected function processPdfUpload($file, $isRequired = true) {', $source);
    $source = str_replace("\$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);", '$_POST = $_POST;', $source);
    $source = preg_replace('/\?>\s*$/', '', $source);

    eval('?>' . $source);
}

loadTestProceduresController();

class HarnessProceduresController extends TestProceduresController {
    protected function processPdfUpload($file, $isRequired = true) {
        unset($file, $isRequired);
        return ['file' => 'stub-upload.pdf', 'error' => ''];
    }
}

class StubProcedureReadModel {
    public $hasPdmsFoundation = true;
    public $dashboard = [];
    public $dashboardDetail = null;
    public $versionDetail = null;
    public $existingDocumentNumber = false;

    public function hasPdmsFoundation() {
        return $this->hasPdmsFoundation;
    }

    public function getProcedureDashboard($search = '') {
        unset($search);
        return $this->dashboard;
    }

    public function getDashboardResponsibilityCenters() {
        return ['Operations', 'Finance'];
    }

    public function getProcedureDashboardDetail($id) {
        unset($id);
        return $this->dashboardDetail;
    }

    public function getVersionDetailById($id) {
        unset($id);
        return $this->versionDetail;
    }

    public function documentNumberExists($documentNumber, $excludeVersionId = null) {
        unset($documentNumber, $excludeVersionId);
        return $this->existingDocumentNumber;
    }
}

class StubProcedureModel {
    public $existingByCode = false;

    public function getByCode($code) {
        unset($code);
        return $this->existingByCode;
    }
}

class StubAuthoringService {
    public $registerProcedureCalls = [];
    public $registerRevisionCalls = [];
    public $rescindCalls = [];
    public $archiveCalls = [];
    public $registerProcedureResult = ['procedure_id' => 501];
    public $registerRevisionThrows = null;
    public $archiveThrows = null;

    public function normalizeRelationshipAuthoringInput($data, $currentVersionId = null, $authoringMode = 'create') {
        $data['change_type'] = strtoupper(trim((string) ($data['change_type'] ?? 'NEW')));
        $data['relationship_type'] = strtoupper(trim((string) ($data['relationship_type'] ?? '')));
        $data['target_version_id'] = trim((string) ($data['target_version_id'] ?? ''));
        $data['affected_sections'] = trim((string) ($data['affected_sections'] ?? ''));

        if ($authoringMode === 'create' && PdmsAuthoringOptions::clearsRelationshipForNew($data['change_type'])) {
            $data['relationship_type'] = '';
            $data['target_version_id'] = '';
            $data['affected_sections'] = '';
        }

        if ($authoringMode === 'issue') {
            if ($data['relationship_type'] === '') {
                $data['relationship_type'] = PdmsAuthoringOptions::defaultRelationshipTypeForChangeType($data['change_type']);
            }

            if ($data['target_version_id'] === '' && $currentVersionId && in_array($data['change_type'], PdmsAuthoringOptions::changeTypesThatAutoTargetCurrentForIssuance(), true)) {
                $data['target_version_id'] = (string) $currentVersionId;
            }
        }

        if (!in_array($data['relationship_type'], PdmsAuthoringOptions::relationshipTypesWithAffectedSections(), true)) {
            $data['affected_sections'] = '';
        }

        return $data;
    }

    public function registerProcedure($data) {
        $this->registerProcedureCalls[] = $data;
        return $this->registerProcedureResult;
    }

    public function registerRevisionForProcedure($procedureId, $data) {
        if ($this->registerRevisionThrows instanceof Throwable) {
            throw $this->registerRevisionThrows;
        }

        $this->registerRevisionCalls[] = [
            'procedure_id' => $procedureId,
            'data' => $data
        ];

        return ['procedure_id' => $procedureId, 'version_id' => 902];
    }

    public function rescindProcedure($procedureId, $remarks = '', $userId = null) {
        $this->rescindCalls[] = [
            'procedure_id' => $procedureId,
            'remarks' => $remarks,
            'user_id' => $userId
        ];
    }

    public function archiveHistoricalVersion($versionId, $userId = null) {
        if ($this->archiveThrows instanceof Throwable) {
            throw $this->archiveThrows;
        }

        $this->archiveCalls[] = [
            'version_id' => $versionId,
            'user_id' => $userId
        ];
    }
}

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function buildController() {
    $reflection = new ReflectionClass(HarnessProceduresController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $controller->procedureReadModel = new StubProcedureReadModel();
    $controller->procedureModel = new StubProcedureModel();
    $controller->procedureAuthoringService = new StubAuthoringService();

    return $controller;
}

function resetControllerTestState() {
    $GLOBALS['controller_test_redirects'] = [];
    $GLOBALS['controller_test_flashes'] = [];
    $GLOBALS['controller_test_csrf_failures'] = [];
    $_POST = [];
    $_FILES = [];
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION = [
        'user_id' => 99,
        'user_role' => 'admin',
        'csrf_token' => 'valid-token'
    ];
}

function makeProcedureOverview(array $overrides = []) {
    return (object) array_merge([
        'id' => 7,
        'procedure_code' => 'PROC-700',
        'title' => 'Stub Procedure',
        'description' => 'Stub Description',
        'category' => 'Ops',
        'owner_office' => 'QA',
        'status' => 'ACTIVE',
        'current_version_id' => 12,
        'current_version_status' => 'EFFECTIVE',
        'current_file_path' => 'current.pdf',
        'current_document_number' => 'DOC-700',
        'current_summary_of_change' => 'Initial issue',
        'current_effective_date' => '2026-03-13'
    ], $overrides);
}

function testCreateFlowCallsRegisterProcedureAndRedirects() {
    resetControllerTestState();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => 'valid-token',
        'procedure_code' => 'PROC-900',
        'title' => 'Created Procedure',
        'description' => 'Created through controller',
        'category' => 'Ops',
        'owner_office' => 'QA',
        'document_number' => 'DOC-900',
        'summary_of_change' => 'Initial issue',
        'change_type' => 'NEW',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-03-13',
        'target_version_id' => '33',
        'relationship_type' => 'AMENDS',
        'affected_sections' => 'Section 1',
        'relationship_remarks' => 'Should clear on NEW'
    ];

    $controller = buildController();
    $controller->procedureAuthoringService->registerProcedureResult = ['procedure_id' => 44];
    $controller->create();

    $call = $controller->procedureAuthoringService->registerProcedureCalls[0] ?? null;

    assertTrue($call !== null, 'Procedures::create should call registerProcedure on successful PDMS authoring.');
    assertTrue($call['procedure_code'] === 'PROC-900', 'Procedures::create should pass the submitted procedure code.');
    assertTrue($call['relationship_type'] === '', 'Procedures::create should clear relationships for NEW registrations before calling the service.');
    assertTrue($call['target_version_id'] === null, 'Procedures::create should clear target version ids for NEW registrations before calling the service.');
    assertTrue($call['affected_sections'] === '', 'Procedures::create should clear affected sections for NEW registrations before calling the service.');
    assertTrue($call['file_path'] === 'stub-upload.pdf', 'Procedures::create should forward the uploaded PDF path to the service.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures/show/44', 'Procedures::create should redirect to the new procedure detail on success.');
}

function testIssueFlowCallsRegisterRevisionAndRedirects() {
    resetControllerTestState();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => 'valid-token',
        'title' => 'Referenced Revision',
        'description' => 'Revision through controller',
        'category' => 'Ops',
        'owner_office' => 'QA',
        'document_number' => 'DOC-701',
        'summary_of_change' => 'Reference update',
        'change_type' => 'REFERENCE',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-03-14',
        'target_version_id' => '',
        'relationship_type' => '',
        'affected_sections' => 'Section should clear',
        'relationship_remarks' => 'Reference relation'
    ];

    $controller = buildController();
    $controller->procedureReadModel->dashboardDetail = [
        'overview' => makeProcedureOverview(['id' => 7, 'current_version_id' => 12]),
        'history' => []
    ];

    $controller->registerRevision('7');

    $call = $controller->procedureAuthoringService->registerRevisionCalls[0] ?? null;

    assertTrue($call !== null, 'Procedures::registerRevision should call registerRevisionForProcedure on valid submissions.');
    assertTrue((int) $call['procedure_id'] === 7, 'Procedures::registerRevision should pass the procedure id to the authoring service.');
    assertTrue((int) $call['data']['target_version_id'] === 12, 'Procedures::registerRevision should auto-target the current version for reference registrations.');
    assertTrue($call['data']['relationship_type'] === 'REFERENCES', 'Procedures::registerRevision should normalize the relationship type before calling the service.');
    assertTrue($call['data']['affected_sections'] === '', 'Procedures::registerRevision should clear affected sections when the relationship type does not require them.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures/show/7', 'Procedures::registerRevision should redirect back to the procedure detail on success.');
}

function testSupersedeFlowCreatesSupersedingProcedure() {
    resetControllerTestState();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => 'valid-token',
        'procedure_code' => 'PROC-800',
        'title' => 'Superseding Procedure',
        'description' => 'Replacement',
        'category' => 'Ops',
        'owner_office' => 'QA',
        'document_number' => 'DOC-800',
        'summary_of_change' => 'Supersedes previous procedure',
        'status' => 'REGISTERED',
        'effective_date' => '2026-03-15',
        'relationship_remarks' => 'Reason for replacement'
    ];

    $controller = buildController();
    $controller->procedureReadModel->dashboardDetail = [
        'overview' => makeProcedureOverview(['id' => 8, 'current_version_id' => 88, 'current_version_status' => 'EFFECTIVE']),
        'history' => []
    ];
    $controller->procedureAuthoringService->registerProcedureResult = ['procedure_id' => 81];

    $controller->supersede('8');

    $call = $controller->procedureAuthoringService->registerProcedureCalls[0] ?? null;

    assertTrue($call !== null, 'Procedures::supersede should call registerProcedure for the replacement procedure.');
    assertTrue($call['change_type'] === 'SUPERSEDING_PROCEDURE', 'Procedures::supersede should force the superseding change type.');
    assertTrue((int) $call['target_version_id'] === 88, 'Procedures::supersede should target the current version being replaced.');
    assertTrue($call['relationship_type'] === 'SUPERSEDES', 'Procedures::supersede should record a SUPERSEDES relationship.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures/show/81', 'Procedures::supersede should redirect to the new replacement procedure.');
}

function testRescindFlowCallsServiceAndRedirects() {
    resetControllerTestState();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => 'valid-token',
        'remarks' => 'No longer valid'
    ];

    $controller = buildController();
    $controller->procedureReadModel->dashboardDetail = [
        'overview' => makeProcedureOverview(['id' => 9, 'current_version_status' => 'EFFECTIVE']),
        'history' => []
    ];

    $controller->rescind('9');

    $call = $controller->procedureAuthoringService->rescindCalls[0] ?? null;

    assertTrue($call !== null, 'Procedures::rescind should call rescindProcedure on valid submissions.');
    assertTrue((int) $call['procedure_id'] === 9, 'Procedures::rescind should pass the procedure id to rescindProcedure.');
    assertTrue($call['remarks'] === 'No longer valid', 'Procedures::rescind should pass the submitted rescission remarks.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures/show/9', 'Procedures::rescind should redirect back to the procedure detail on success.');
}

function testArchiveVersionCallsServiceAndRedirects() {
    resetControllerTestState();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => 'valid-token'
    ];

    $controller = buildController();
    $controller->procedureReadModel->versionDetail = [
        'version' => (object) [
            'id' => 22,
            'procedure_id' => 4,
            'status' => 'SUPERSEDED',
            'current_version_id' => 99
        ]
    ];

    $controller->archiveVersion('22');

    $call = $controller->procedureAuthoringService->archiveCalls[0] ?? null;

    assertTrue($call !== null, 'Procedures::archiveVersion should call archiveHistoricalVersion for valid historical versions.');
    assertTrue((int) $call['version_id'] === 22, 'Procedures::archiveVersion should pass the version id to archiveHistoricalVersion.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures/version/22', 'Procedures::archiveVersion should redirect back to the version detail on success.');
}

function testBackfillRouteIsRetired() {
    resetControllerTestState();
    $controller = buildController();
    $controller->backfill();

    $flash = $GLOBALS['controller_test_flashes'][0] ?? null;

    assertTrue($flash !== null, 'Procedures::backfill should flash a retirement message.');
    assertTrue($flash['name'] === 'procedures_backfill', 'Procedures::backfill should use the procedures_backfill flash slot.');
    assertTrue($flash['class'] === 'alert alert-warning', 'Procedures::backfill should use a warning flash once the route is retired.');
    assertTrue($flash['message'] === 'Legacy backfill has been retired. Use the PDMS procedures area directly.', 'Procedures::backfill should explain that legacy migration tooling is retired.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures', 'Procedures::backfill should return to the dashboard after showing the retirement notice.');
}

function testCleanupRouteIsRetired() {
    resetControllerTestState();
    $controller = buildController();
    $controller->cleanup();

    $flash = $GLOBALS['controller_test_flashes'][0] ?? null;

    assertTrue($flash !== null, 'Procedures::cleanup should flash a retirement message.');
    assertTrue($flash['name'] === 'procedures_cleanup', 'Procedures::cleanup should use the procedures_cleanup flash slot.');
    assertTrue($flash['class'] === 'alert alert-warning', 'Procedures::cleanup should use a warning flash once the route is retired.');
    assertTrue($flash['message'] === 'Legacy cleanup has been retired. Use the PDMS procedures area directly.', 'Procedures::cleanup should explain that legacy cleanup tooling is retired.');
    assertTrue(($GLOBALS['controller_test_redirects'][0] ?? null) === 'procedures', 'Procedures::cleanup should return to the dashboard after showing the retirement notice.');
}

function testCsrfFailureMessageMakesSafetyExplicit() {
    $helperSource = file_get_contents(__DIR__ . '/../app/helpers/session_helper.php');

    assertTrue($helperSource !== false, 'session_helper.php should be readable for CSRF message checks.');
    assertTrue(
        strpos($helperSource, "Your session expired or the form became invalid. No changes were saved. Please try again.") !== false,
        'CSRF failures should explain that the request was rejected safely and no changes were saved.'
    );
}

function runRegressionSuite() {
    testCreateFlowCallsRegisterProcedureAndRedirects();
    testIssueFlowCallsRegisterRevisionAndRedirects();
    testSupersedeFlowCreatesSupersedingProcedure();
    testRescindFlowCallsServiceAndRedirects();
    testArchiveVersionCallsServiceAndRedirects();
    testBackfillRouteIsRetired();
    testCleanupRouteIsRetired();
    testCsrfFailureMessageMakesSafetyExplicit();
    echo "Controller write flow regression: OK\n";
}

runRegressionSuite();
