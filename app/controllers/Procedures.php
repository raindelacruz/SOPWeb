<?php
require_once __DIR__ . '/../helpers/pdms_authoring_options.php';

class Procedures extends Controller {
    public function __construct() {
        Middleware::checkLoggedIn();
        $this->procedureModel = $this->model('Procedure');
        $this->postModel = $this->model('Post');
        $this->procedureReadModel = $this->model('ProcedureReadModel');
        $this->procedureSyncService = $this->model('ProcedureSyncService');
        $this->procedureAuthoringService = $this->model('ProcedureAuthoringService');
    }

    private function csrfFailure($url = 'procedures') {
        handle_csrf_failure($url);
    }

    private function uploadsDirectory() {
        return APPROOT . '/uploads';
    }

    private function ensureUploadsDirectory() {
        $directory = $this->uploadsDirectory();
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }

    private function processPdfUpload($file, $isRequired = true) {
        if (empty($file) || !isset($file['error'])) {
            return ['file' => '', 'error' => $isRequired ? 'Please upload a PDF file' : ''];
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['file' => '', 'error' => $isRequired ? 'Please upload a PDF file' : ''];
        }

        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'pdf') {
            return ['file' => '', 'error' => 'Only PDF files are allowed'];
        }

        if ($fileError !== UPLOAD_ERR_OK) {
            return ['file' => '', 'error' => 'There was an error uploading the file'];
        }

        if ($fileSize >= 50000000) {
            return ['file' => '', 'error' => 'Your file is too big (max 50MB)'];
        }

        $fileNameNew = uniqid('', true) . '.pdf';
        $fileDestination = $this->ensureUploadsDirectory() . '/' . $fileNameNew;

        if (!move_uploaded_file($fileTmpName, $fileDestination)) {
            return ['file' => '', 'error' => 'There was an error uploading the file'];
        }

        return ['file' => $fileNameNew, 'error' => ''];
    }

    private function createDefaults() {
        return [
            'procedure_code' => '',
            'title' => '',
            'description' => '',
            'category' => '',
            'owner_office' => '',
            'document_number' => '',
            'summary_of_change' => '',
            'change_type' => 'NEW',
            'status' => 'EFFECTIVE',
            'effective_date' => '',
            'target_version_id' => '',
            'relationship_type' => '',
            'affected_sections' => '',
            'relationship_remarks' => '',
            'file' => '',
            'procedure_code_err' => '',
            'title_err' => '',
            'description_err' => '',
            'document_number_err' => '',
            'change_type_err' => '',
            'status_err' => '',
            'effective_date_err' => '',
            'target_version_id_err' => '',
            'relationship_type_err' => '',
            'affected_sections_err' => '',
            'file_err' => '',
            'pdms_err' => ''
        ];
    }

    private function createOptions() {
        return [
            'change_types' => PdmsAuthoringOptions::createChangeTypes(),
            'workflow_statuses' => PdmsAuthoringOptions::authoringWorkflowStatuses(),
            'relationship_types' => PdmsAuthoringOptions::createRelationshipTypes(),
            'targets' => $this->procedureReadModel->getProcedureDashboard('')
        ];
    }

    private function allowedAuthoringStatuses() {
        return PdmsAuthoringOptions::authoringWorkflowStatuses();
    }

    private function normalizeRelationshipAuthoringInput($data, $currentVersionId = null, $authoringMode = 'create') {
        return $this->procedureAuthoringService->normalizeRelationshipAuthoringInput($data, $currentVersionId, $authoringMode);
    }

    private function applySharedAuthoringFieldValidation($data, $authoringMode, $options = []) {
        $data['status'] = PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE');
        $currentVersionId = isset($options['current_version_id']) ? (string) $options['current_version_id'] : '';

        if (empty($data['title'])) {
            $data['title_err'] = 'Please enter a title';
        }

        if (empty($data['description'])) {
            $data['description_err'] = 'Please enter a description';
        }

        if (empty($data['document_number'])) {
            $data['document_number_err'] = 'Please enter a document number';
        } elseif ($this->postModel->findPostByReferenceNumber($data['document_number'])) {
            $data['document_number_err'] = 'Document number already exists in the legacy SOP registry';
        }

        if (empty($data['effective_date'])) {
            $data['effective_date_err'] = 'Please select an effectivity date';
        }

        if (!PdmsAuthoringOptions::allowsChangeTypeForAuthoringMode($data['change_type'], $authoringMode)) {
            $data['change_type_err'] = PdmsAuthoringOptions::invalidChangeTypeMessage($authoringMode);
        }

        if (!in_array($data['status'], $this->allowedAuthoringStatuses(), true)) {
            $data['status_err'] = PdmsAuthoringOptions::invalidWorkflowStatusMessage($authoringMode);
        }

        if (!PdmsAuthoringOptions::allowsRelationshipTypeForAuthoringMode($data['relationship_type'], $authoringMode)) {
            $data['relationship_type_err'] = PdmsAuthoringOptions::invalidRelationshipTypeMessage($authoringMode);
        }

        if (PdmsAuthoringOptions::requiresAffectedSections($data['change_type'], $data['relationship_type']) && empty($data['affected_sections'])) {
            $data['affected_sections_err'] = PdmsAuthoringOptions::affectedSectionsRequiredMessage();
        }

        if (
            PdmsAuthoringOptions::requiresPdmsTargetVersion($data['change_type'], $data['relationship_type'])
            && empty($data['target_version_id'])
        ) {
            $data['target_version_id_err'] = PdmsAuthoringOptions::pdmsTargetRequiredMessage($data['change_type'], $data['relationship_type'], $authoringMode);
        }

        if ($authoringMode === 'create' && PdmsAuthoringOptions::clearsRelationshipForNew($data['change_type']) && !empty($data['relationship_type'])) {
            $data['change_type_err'] = PdmsAuthoringOptions::newChangeTypeCannotHaveRelationshipMessage();
        }

        if (
            $authoringMode === 'issue'
            && $data['change_type'] === 'SUPERSEDING_PROCEDURE'
            && $currentVersionId !== ''
            && (string) $data['target_version_id'] === $currentVersionId
        ) {
            $data['target_version_id_err'] = 'Use the new procedure flow when a new procedure supersedes a different current procedure. Existing procedures cannot supersede themselves.';
        }

        return $data;
    }

    private function validateCreateInput($data) {
        if (empty($data['procedure_code'])) {
            $data['procedure_code_err'] = 'Please enter a procedure code';
        } elseif ($this->procedureReadModel->hasPdmsFoundation() && $this->procedureModel->getByCode($data['procedure_code'])) {
            $data['procedure_code_err'] = 'Procedure code already exists';
        }

        return $this->applySharedAuthoringFieldValidation($data, 'create');
    }

    private function validateIssueInput($data, $procedureId, $currentVersionId) {
        unset($procedureId);

        $data = $this->applySharedAuthoringFieldValidation($data, 'issue', [
            'current_version_id' => $currentVersionId
        ]);

        return $data;
    }

    private function issueDefaults($procedure, $overview) {
        return [
            'title' => $procedure->title ?? '',
            'description' => $procedure->description ?? '',
            'category' => $procedure->category ?? '',
            'owner_office' => $procedure->owner_office ?? '',
            'document_number' => '',
            'summary_of_change' => '',
            'change_type' => 'AMENDMENT',
            'status' => 'EFFECTIVE',
            'effective_date' => '',
            'target_version_id' => !empty($overview->current_version_id) ? (string) $overview->current_version_id : '',
            'relationship_type' => 'AMENDS',
            'affected_sections' => '',
            'relationship_remarks' => '',
            'file' => '',
            'title_err' => '',
            'description_err' => '',
            'document_number_err' => '',
            'change_type_err' => '',
            'status_err' => '',
            'effective_date_err' => '',
            'target_version_id_err' => '',
            'relationship_type_err' => '',
            'affected_sections_err' => '',
            'file_err' => '',
            'pdms_err' => ''
        ];
    }

    private function issueOptions($procedureId, $overview) {
        $targets = $this->procedureReadModel->getProcedureDashboard('');

        return [
            'change_types' => PdmsAuthoringOptions::issueChangeTypes(),
            'workflow_statuses' => PdmsAuthoringOptions::authoringWorkflowStatuses(),
            'relationship_types' => PdmsAuthoringOptions::issueRelationshipTypes(),
            'targets' => $targets,
            'current_version_id' => $overview->current_version_id ?? null,
            'procedure_id' => $procedureId
        ];
    }

    private function editDefaults($procedure) {
        return [
            'title' => $procedure->title ?? '',
            'description' => $procedure->description ?? '',
            'category' => $procedure->category ?? '',
            'owner_office' => $procedure->owner_office ?? '',
            'document_number' => $procedure->current_document_number ?? '',
            'summary_of_change' => $procedure->current_summary_of_change ?? '',
            'effective_date' => $procedure->current_effective_date ?? '',
            'file' => $procedure->current_file_path ?? '',
            'title_err' => '',
            'description_err' => '',
            'document_number_err' => '',
            'effective_date_err' => '',
            'file_err' => '',
            'pdms_err' => ''
        ];
    }

    private function validateEditInput($data, $currentLegacyPostId = null) {
        if (empty($data['title'])) {
            $data['title_err'] = 'Please enter a title';
        }

        if (empty($data['description'])) {
            $data['description_err'] = 'Please enter a description';
        }

        if (empty($data['document_number'])) {
            $data['document_number_err'] = 'Please enter a document number';
        } else {
            $existingPost = $this->postModel->findPostByReferenceNumber($data['document_number']);
            if ($existingPost && (int) $existingPost->id !== (int) $currentLegacyPostId) {
                $data['document_number_err'] = 'Document number already exists in the legacy SOP registry';
            }
        }

        if (empty($data['effective_date'])) {
            $data['effective_date_err'] = 'Please select an effectivity date';
        }

        return $data;
    }

    private function hasAuthoringValidationErrors($data, $authoringMode = 'create') {
        $errorFields = [
            'title_err',
            'description_err',
            'document_number_err',
            'change_type_err',
            'status_err',
            'effective_date_err',
            'target_version_id_err',
            'relationship_type_err',
            'affected_sections_err',
            'file_err',
            'pdms_err'
        ];

        if ($authoringMode === 'create') {
            array_unshift($errorFields, 'procedure_code_err');
        }

        foreach ($errorFields as $field) {
            if (!empty($data[$field])) {
                return true;
            }
        }

        return false;
    }

    private function nextWorkflowStatus($status) {
        $map = [
            'REGISTERED' => 'EFFECTIVE'
        ];

        return $map[PdmsAuthoringOptions::normalizeWorkflowStatus($status, '')] ?? null;
    }

    private function rescindDefaults($procedure) {
        return [
            'remarks' => '',
            'remarks_err' => '',
            'pdms_err' => '',
            'procedure' => $procedure
        ];
    }

    private function supersedeDefaults($procedure) {
        return [
            'procedure_code' => '',
            'title' => $procedure->title ?? '',
            'description' => $procedure->description ?? '',
            'category' => $procedure->category ?? '',
            'owner_office' => $procedure->owner_office ?? '',
            'document_number' => '',
            'summary_of_change' => 'Supersedes procedure ' . ($procedure->procedure_code ?? ''),
            'status' => 'EFFECTIVE',
            'effective_date' => date('Y-m-d'),
            'relationship_remarks' => 'Superseding PDMS-native replacement for procedure ' . ($procedure->procedure_code ?? ''),
            'file' => '',
            'procedure_code_err' => '',
            'title_err' => '',
            'description_err' => '',
            'document_number_err' => '',
            'status_err' => '',
            'effective_date_err' => '',
            'relationship_remarks_err' => '',
            'file_err' => '',
            'pdms_err' => '',
            'procedure' => $procedure
        ];
    }

    public function index() {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $procedures = $this->procedureReadModel->getProcedureDashboard($search);

        $data = [
            'search' => $search,
            'procedures' => $procedures,
            'has_pdms' => $this->procedureReadModel->hasPdmsFoundation(),
            'backfill_status' => $this->procedureReadModel->getBackfillStatus()
        ];

        $this->view('procedures/index', $data);
    }

    public function create() {
        Middleware::checkAdmin();

        if (!$this->procedureReadModel->hasPdmsFoundation()) {
            flash('procedures_backfill', 'PDMS tables are required before using PDMS-first authoring.', 'alert alert-warning');
            redirect('procedures');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('procedures/create');
            }

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = $this->createDefaults();
            $data = array_merge($data, [
                'procedure_code' => trim($_POST['procedure_code'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'owner_office' => trim($_POST['owner_office'] ?? ''),
                'document_number' => trim($_POST['document_number'] ?? ''),
                'summary_of_change' => trim($_POST['summary_of_change'] ?? ''),
                'change_type' => trim($_POST['change_type'] ?? 'NEW'),
                'status' => trim($_POST['status'] ?? 'EFFECTIVE'),
                'effective_date' => trim($_POST['effective_date'] ?? ''),
                'target_version_id' => trim($_POST['target_version_id'] ?? ''),
                'relationship_type' => trim($_POST['relationship_type'] ?? ''),
                'affected_sections' => trim($_POST['affected_sections'] ?? ''),
                'relationship_remarks' => trim($_POST['relationship_remarks'] ?? '')
            ]);

            $upload = $this->processPdfUpload($_FILES['file'] ?? null, true);
            $data['file'] = $upload['file'];
            $data['file_err'] = $upload['error'];
            $data = $this->normalizeRelationshipAuthoringInput($data, null, 'create');
            $data = $this->validateCreateInput($data);

            if (!$this->hasAuthoringValidationErrors($data, 'create')) {
                try {
                    $result = $this->procedureAuthoringService->registerProcedure([
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'procedure_code' => $data['procedure_code'],
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'category' => $data['category'],
                        'owner_office' => $data['owner_office'],
                        'document_number' => $data['document_number'],
                        'summary_of_change' => $data['summary_of_change'],
                        'change_type' => $data['change_type'],
                        'status' => $data['status'],
                        'effective_date' => $data['effective_date'],
                        'target_version_id' => $data['target_version_id'] !== '' ? (int) $data['target_version_id'] : null,
                        'relationship_type' => $data['relationship_type'],
                        'affected_sections' => $data['affected_sections'],
                        'relationship_remarks' => $data['relationship_remarks'],
                        'file_path' => $data['file']
                    ]);

                    flash('procedures_backfill', 'PDMS procedure created successfully and mirrored to the legacy SOP registry.', 'alert alert-success');
                    redirect('procedures/show/' . $result['procedure_id']);
                } catch (Throwable $e) {
                    $data['pdms_err'] = 'The procedure could not be created. ' . $e->getMessage();
                }
            }

            $data['options'] = $this->createOptions();
            $this->view('procedures/create', $data);
            return;
        }

        $data = $this->createDefaults();
        $data['options'] = $this->createOptions();
        $this->view('procedures/create', $data);
    }

    public function registerRevision($id) {
        Middleware::checkAdmin();

        if (!ctype_digit((string) $id)) {
            die('Invalid procedure ID');
        }

        $detail = $this->procedureReadModel->getProcedureDashboardDetail((int) $id);
        if (!$detail) {
            die('Procedure not found');
        }

        $procedure = $detail['overview'];
        if (in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            flash('procedures_backfill', 'Historical procedures cannot receive newly registered revisions.', 'alert alert-warning');
            redirect('procedures/show/' . $id);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('procedures/issue/' . $id);
            }

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = $this->issueDefaults($procedure, $procedure);
            $data = array_merge($data, [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'owner_office' => trim($_POST['owner_office'] ?? ''),
                'document_number' => trim($_POST['document_number'] ?? ''),
                'summary_of_change' => trim($_POST['summary_of_change'] ?? ''),
                'change_type' => trim($_POST['change_type'] ?? 'AMENDMENT'),
                'status' => trim($_POST['status'] ?? 'EFFECTIVE'),
                'effective_date' => trim($_POST['effective_date'] ?? ''),
                'target_version_id' => trim($_POST['target_version_id'] ?? ''),
                'relationship_type' => trim($_POST['relationship_type'] ?? ''),
                'affected_sections' => trim($_POST['affected_sections'] ?? ''),
                'relationship_remarks' => trim($_POST['relationship_remarks'] ?? '')
            ]);

            $upload = $this->processPdfUpload($_FILES['file'] ?? null, true);
            $data['file'] = $upload['file'];
            $data['file_err'] = $upload['error'];
            $data = $this->normalizeRelationshipAuthoringInput($data, (int) ($procedure->current_version_id ?? 0), 'issue');
            $data = $this->validateIssueInput($data, (int) $id, (int) ($procedure->current_version_id ?? 0));

            if (!$this->hasAuthoringValidationErrors($data, 'issue')) {
                try {
                    $this->procedureAuthoringService->registerRevisionForProcedure((int) $id, [
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'category' => $data['category'],
                        'owner_office' => $data['owner_office'],
                        'document_number' => $data['document_number'],
                        'summary_of_change' => $data['summary_of_change'],
                        'change_type' => $data['change_type'],
                        'status' => $data['status'],
                        'effective_date' => $data['effective_date'],
                        'target_version_id' => $data['target_version_id'] !== '' ? (int) $data['target_version_id'] : null,
                        'relationship_type' => $data['relationship_type'],
                        'affected_sections' => $data['affected_sections'],
                        'relationship_remarks' => $data['relationship_remarks'],
                        'file_path' => $data['file']
                    ]);

                    flash('procedures_backfill', 'New revision registered successfully.', 'alert alert-success');
                    redirect('procedures/show/' . $id);
                } catch (Throwable $e) {
                    $data['pdms_err'] = 'The revision could not be registered. ' . $e->getMessage();
                }
            }

            $data['procedure'] = $procedure;
            $data['options'] = $this->issueOptions((int) $id, $procedure);
            $this->view('procedures/issue', $data);
            return;
        }

        $data = $this->issueDefaults($procedure, $procedure);
        $data['procedure'] = $procedure;
        $data['options'] = $this->issueOptions((int) $id, $procedure);
        $this->view('procedures/issue', $data);
    }

    public function issue($id) {
        return $this->registerRevision($id);
    }

    public function edit($id) {
        Middleware::checkAdmin();

        if (!ctype_digit((string) $id)) {
            die('Invalid procedure ID');
        }

        $detail = $this->procedureReadModel->getProcedureDashboardDetail((int) $id);
        if (!$detail) {
            die('Procedure not found');
        }

        $procedure = $detail['overview'];
        if (in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)) {
            flash('procedures_backfill', 'Historical procedures cannot be edited.', 'alert alert-warning');
            redirect('procedures/show/' . $id);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('procedures/edit/' . $id);
            }

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = $this->editDefaults($procedure);
            $data = array_merge($data, [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'owner_office' => trim($_POST['owner_office'] ?? ''),
                'document_number' => trim($_POST['document_number'] ?? ''),
                'summary_of_change' => trim($_POST['summary_of_change'] ?? ''),
                'effective_date' => trim($_POST['effective_date'] ?? ''),
                'file' => trim($_POST['existing_file'] ?? ($procedure->current_file_path ?? ''))
            ]);

            if (!empty($_FILES['file']['name'])) {
                $upload = $this->processPdfUpload($_FILES['file'], false);
                $data['file'] = $upload['file'] ?: $data['file'];
                $data['file_err'] = $upload['error'];
            }

            $currentVersion = !empty($detail['history']) ? $detail['history'][0] : null;
            $currentLegacyPostId = $procedure->current_legacy_post_id ?? ($procedure->legacy_post_id ?? null);
            if ($currentVersion && !empty($currentVersion->legacy_post_id)) {
                $currentLegacyPostId = $currentVersion->legacy_post_id;
            }

            $data = $this->validateEditInput($data, $currentLegacyPostId);

            if (
                empty($data['title_err']) &&
                empty($data['description_err']) &&
                empty($data['document_number_err']) &&
                empty($data['effective_date_err']) &&
                empty($data['file_err']) &&
                empty($data['pdms_err'])
            ) {
                try {
                    $this->procedureAuthoringService->updateProcedureAndCurrentVersion((int) $id, [
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'category' => $data['category'],
                        'owner_office' => $data['owner_office'],
                        'document_number' => $data['document_number'],
                        'summary_of_change' => $data['summary_of_change'],
                        'effective_date' => $data['effective_date'],
                        'file_path' => $data['file']
                    ]);

                    flash('procedures_backfill', 'Procedure metadata updated successfully.', 'alert alert-success');
                    redirect('procedures/show/' . $id);
                } catch (Throwable $e) {
                    $data['pdms_err'] = 'The procedure could not be updated. ' . $e->getMessage();
                }
            }

            $data['procedure'] = $procedure;
            $this->view('procedures/edit', $data);
            return;
        }

        $data = $this->editDefaults($procedure);
        $data['procedure'] = $procedure;
        $this->view('procedures/edit', $data);
    }

    public function backfill() {
        Middleware::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('procedures');
        }

        $limit = isset($_POST['limit']) && ctype_digit((string) $_POST['limit'])
            ? max(1, min(250, (int) $_POST['limit']))
            : 25;

        $result = $this->procedureSyncService->backfillLegacyPosts($limit, $_SESSION['user_id'] ?? null);

        if ($result['failed'] > 0) {
            $message = 'Backfill processed ' . $result['attempted'] . ' posts: '
                . $result['synced'] . ' synced, '
                . $result['failed'] . ' failed.';

            if (!empty($result['errors'])) {
                $message .= ' First issue: ' . $result['errors'][0];
            }

            flash('procedures_backfill', $message, 'alert alert-warning');
        } else {
            flash(
                'procedures_backfill',
                'Backfill processed ' . $result['attempted'] . ' posts and synchronized ' . $result['synced'] . ' into the PDMS model.',
                'alert alert-success'
            );
        }

        redirect('procedures');
    }

    public function cleanup() {
        Middleware::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('procedures');
        }

        $limit = isset($_POST['limit']) && ctype_digit((string) $_POST['limit'])
            ? max(1, min(500, (int) $_POST['limit']))
            : 100;

        $result = $this->procedureSyncService->normalizeLegacyManagedRelationships($limit, $_SESSION['user_id'] ?? null);

        if (!empty($result['errors'])) {
            $message = 'Cleanup reviewed ' . $result['attempted'] . ' relationships and normalized '
                . $result['updated'] . '. First issue: ' . $result['errors'][0];
            flash('procedures_cleanup', $message, 'alert alert-warning');
        } else {
            flash(
                'procedures_cleanup',
                'Cleanup reviewed ' . $result['attempted'] . ' relationships and normalized ' . $result['updated'] . ' legacy-managed records.',
                'alert alert-success'
            );
        }

        redirect('procedures');
    }

    public function show($id) {
        if (!ctype_digit((string) $id)) {
            die('Invalid procedure ID');
        }

        $detail = $this->procedureReadModel->getProcedureDashboardDetail((int) $id);

        if (!$detail) {
            die('Procedure not found');
        }

        $isHistoricalProcedure = in_array((string) ($detail['overview']->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true);
        $detail['next_workflow_status'] = null;
        $detail['show_workflow_lane'] = false;
        $detail['can_rescind'] = isset($_SESSION['user_role'])
            && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)
            && !in_array((string) ($detail['overview']->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)
            && PdmsAuthoringOptions::normalizeWorkflowStatus($detail['overview']->current_version_status ?? '', '') === 'EFFECTIVE';
        $detail['can_supersede'] = isset($_SESSION['user_role'])
            && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)
            && !in_array((string) ($detail['overview']->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)
            && PdmsAuthoringOptions::normalizeWorkflowStatus($detail['overview']->current_version_status ?? '', '') === 'EFFECTIVE'
            && !empty($detail['overview']->current_version_id);

        $this->view('procedures/show', $detail);
    }

    public function supersede($id) {
        Middleware::checkAdmin();

        if (!ctype_digit((string) $id)) {
            die('Invalid procedure ID');
        }

        $detail = $this->procedureReadModel->getProcedureDashboardDetail((int) $id);
        if (!$detail) {
            die('Procedure not found');
        }

        $procedure = $detail['overview'];
        $canSupersede = !in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)
            && PdmsAuthoringOptions::normalizeWorkflowStatus($procedure->current_version_status ?? '', '') === 'EFFECTIVE'
            && !empty($procedure->current_version_id);

        if (!$canSupersede) {
            flash('procedures_backfill', 'Only active procedures with an effective current version can be superseded from the PDMS flow.', 'alert alert-warning');
            redirect('procedures/show/' . $id);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('procedures/supersede/' . $id);
            }

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = $this->supersedeDefaults($procedure);
            $data = array_merge($data, [
                'procedure_code' => trim($_POST['procedure_code'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'owner_office' => trim($_POST['owner_office'] ?? ''),
                'document_number' => trim($_POST['document_number'] ?? ''),
                'summary_of_change' => trim($_POST['summary_of_change'] ?? ''),
                'status' => trim($_POST['status'] ?? 'EFFECTIVE'),
                'effective_date' => trim($_POST['effective_date'] ?? ''),
                'relationship_remarks' => trim($_POST['relationship_remarks'] ?? '')
            ]);

            $data['status'] = PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'EFFECTIVE', 'EFFECTIVE');
            if (!in_array($data['status'], ['REGISTERED', 'EFFECTIVE'], true)) {
                $data['status_err'] = PdmsAuthoringOptions::supersedingProcedureStatusMessage();
            }

            if ($data['relationship_remarks'] === '') {
                $data['relationship_remarks_err'] = 'Please capture the supersession note or rationale.';
            }

            $upload = $this->processPdfUpload($_FILES['file'] ?? null, true);
            $data['file'] = $upload['file'];
            $data['file_err'] = $upload['error'];
            $data = $this->validateCreateInput(array_merge($data, [
                'change_type' => 'SUPERSEDING_PROCEDURE',
                'target_version_id' => (string) $procedure->current_version_id,
                'relationship_type' => 'SUPERSEDES',
                'affected_sections' => ''
            ]));

            if (
                empty($data['procedure_code_err']) &&
                empty($data['title_err']) &&
                empty($data['description_err']) &&
                empty($data['document_number_err']) &&
                empty($data['status_err']) &&
                empty($data['effective_date_err']) &&
                empty($data['relationship_remarks_err']) &&
                empty($data['target_version_id_err']) &&
                empty($data['relationship_type_err']) &&
                empty($data['file_err']) &&
                empty($data['pdms_err'])
            ) {
                try {
                    $result = $this->procedureAuthoringService->registerProcedure([
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'procedure_code' => $data['procedure_code'],
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'category' => $data['category'],
                        'owner_office' => $data['owner_office'],
                        'document_number' => $data['document_number'],
                        'summary_of_change' => $data['summary_of_change'],
                        'change_type' => 'SUPERSEDING_PROCEDURE',
                        'status' => $data['status'],
                        'effective_date' => $data['effective_date'],
                        'target_version_id' => (int) $procedure->current_version_id,
                        'relationship_type' => 'SUPERSEDES',
                        'affected_sections' => '',
                        'relationship_remarks' => $data['relationship_remarks'],
                        'file_path' => $data['file']
                    ]);

                    flash('procedures_backfill', 'Superseding procedure created successfully. The previous procedure is now historical.', 'alert alert-success');
                    redirect('procedures/show/' . $result['procedure_id']);
                } catch (Throwable $e) {
                    $data['pdms_err'] = 'The superseding procedure could not be created. ' . $e->getMessage();
                }
            }

            $this->view('procedures/supersede', $data);
            return;
        }

        $data = $this->supersedeDefaults($procedure);
        $this->view('procedures/supersede', $data);
    }

    public function rescind($id) {
        Middleware::checkAdmin();

        if (!ctype_digit((string) $id)) {
            die('Invalid procedure ID');
        }

        $detail = $this->procedureReadModel->getProcedureDashboardDetail((int) $id);
        if (!$detail) {
            die('Procedure not found');
        }

        $procedure = $detail['overview'];
        $canRescind = !in_array((string) ($procedure->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)
            && PdmsAuthoringOptions::normalizeWorkflowStatus($procedure->current_version_status ?? '', '') === 'EFFECTIVE';

        if (!$canRescind) {
            flash('procedures_backfill', 'Only active procedures with an effective current version can be rescinded.', 'alert alert-warning');
            redirect('procedures/show/' . $id);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('procedures/rescind/' . $id);
            }

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = $this->rescindDefaults($procedure);
            $data['remarks'] = trim($_POST['remarks'] ?? '');

            if ($data['remarks'] === '') {
                $data['remarks_err'] = 'Please provide a rescission reason or note.';
            }

            if (empty($data['remarks_err']) && empty($data['pdms_err'])) {
                try {
                    $this->procedureAuthoringService->rescindProcedure((int) $id, $data['remarks'], $_SESSION['user_id'] ?? null);
                    flash('procedures_backfill', 'Procedure rescinded successfully.', 'alert alert-success');
                    redirect('procedures/show/' . $id);
                } catch (Throwable $e) {
                    $data['pdms_err'] = $e->getMessage();
                }
            }

            $this->view('procedures/rescind', $data);
            return;
        }

        $data = $this->rescindDefaults($procedure);
        $this->view('procedures/rescind', $data);
    }

    public function version($id) {
        if (!ctype_digit((string) $id)) {
            die('Invalid version ID');
        }

        $detail = $this->procedureReadModel->getVersionDetailById((int) $id);

        if (!$detail) {
            die('Procedure version not found');
        }

        $version = $detail['version'];
        $detail['can_archive'] = isset($_SESSION['user_role'])
            && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)
            && (int) ($version->current_version_id ?? 0) !== (int) $version->id
            && in_array((string) ($version->status ?? ''), ['SUPERSEDED', 'RESCINDED'], true);

        $this->view('procedures/version', $detail);
    }

    public function archiveVersion($id) {
        Middleware::checkAdmin();

        if (!ctype_digit((string) $id)) {
            die('Invalid version ID');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('procedures/version/' . $id);
        }

        try {
            $detail = $this->procedureReadModel->getVersionDetailById((int) $id);
            if (!$detail) {
                throw new RuntimeException('Procedure version not found.');
            }

            $this->procedureAuthoringService->archiveHistoricalVersion((int) $id, $_SESSION['user_id'] ?? null);
            flash('procedures_backfill', 'Historical version archived successfully.', 'alert alert-success');
            redirect('procedures/version/' . $id);
        } catch (Throwable $e) {
            flash('procedures_backfill', $e->getMessage(), 'alert alert-warning');
            redirect('procedures/version/' . $id);
        }
    }
}
?>
