<?php
class ActivityLogs extends Controller {
    private $activityLogModel;

    private function csrfFailure() {
        handle_csrf_failure('activitylogs');
    }

    public function __construct() {
        Middleware::checkSuperAdmin();

        // Load the model
        $this->activityLogModel = $this->model('ActivityLog');
    }

    public function index() {
        // Get logs from the model
        $logs = $this->activityLogModel->getLogs();

        // Pass the logs to the view
        $data = [
            'logs' => $logs
        ];

        $this->view('activity_logs/index', $data);
    }
    
    public function search() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure();
        }

        // Get the search keyword from the form submission
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';

        // Fetch activity logs with the search keyword using the correct method
        $logs = $this->activityLogModel->searchLogs($keyword); // Change here

        // Pass the logs and keyword to the view
        $data = [
            'logs' => $logs,
            'keyword' => $keyword
        ];

        // Load the view
        $this->view('activity_logs/index', $data);
    }
}
