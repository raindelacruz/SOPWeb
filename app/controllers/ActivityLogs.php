<?php
class ActivityLogs extends Controller {
    private $activityLogModel;

    public function __construct() {
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
		$keyword = trim($_POST['keyword']);
		$logs = $this->activityLogModel->searchLogs($keyword);
		$data = [
			'logs' => $logs,
			'keyword' => $keyword
		];
		$this->view('activity_logs/index', $data);
	}

}
