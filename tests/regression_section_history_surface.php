<?php

function assertContains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function runRegressionSuite() {
    $readModelPath = __DIR__ . '/../app/models/ProcedureReadModel.php';
    $procedureShowPath = __DIR__ . '/../app/views/procedures/show.php';
    $procedureVersionPath = __DIR__ . '/../app/views/procedures/version.php';

    $readModel = file_get_contents($readModelPath);
    $procedureShow = file_get_contents($procedureShowPath);
    $procedureVersion = file_get_contents($procedureVersionPath);

    assertContains(
        'public function getSectionChangeLogByVersionId($versionId)',
        $readModel,
        'ProcedureReadModel should expose version-level section history.'
    );
    assertContains(
        'public function getSectionHistoryByProcedureId($procedureId, $limit = 25)',
        $readModel,
        'ProcedureReadModel should expose procedure-level section history.'
    );
    assertContains(
        "'section_history' => \$sectionHistory",
        $readModel,
        'ProcedureReadModel detail payloads should continue to include section_history.'
    );
    assertContains(
        'Section Lineage',
        $procedureShow,
        'Procedure detail should render a Section Lineage panel.'
    );
    assertContains(
        'Section Lineage',
        $procedureVersion,
        'Procedure version detail should render a Section Lineage panel.'
    );
    if (file_exists(__DIR__ . '/../app/views/posts/show.php')) {
        throw new RuntimeException('Legacy post detail view should be removed during the PDMS-only cleanup sweep.');
    }

    echo "Section history surface regression: OK\n";
}

runRegressionSuite();
