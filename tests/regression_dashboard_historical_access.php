<?php

function assertContains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function runRegressionSuite() {
    $readModelPath = __DIR__ . '/../app/models/ProcedureReadModel.php';
    $indexViewPath = __DIR__ . '/../app/views/procedures/index.php';
    $versionViewPath = __DIR__ . '/../app/views/procedures/version.php';

    $readModel = file_get_contents($readModelPath);
    $indexView = file_get_contents($indexViewPath);
    $versionView = file_get_contents($versionViewPath);

    assertContains(
        'public function getHistoricalProcedureDashboard($search = \'\', array $filters = [])',
        $readModel,
        'ProcedureReadModel should expose a historical procedure dashboard query.'
    );
    assertContains(
        'public function getProcedureDashboardCounts($search = \'\', array $filters = [])',
        $readModel,
        'ProcedureReadModel should expose dashboard counts that distinguish active and historical procedures.'
    );
    assertContains(
        'public function getDashboardResponsibilityCenters()',
        $readModel,
        'ProcedureReadModel should expose dashboard responsibility-center options for filtering.'
    );
    assertContains(
        'Historical Procedures',
        $indexView,
        'Procedures dashboard should expose a historical procedures panel.'
    );
    assertContains(
        'How to use this page:',
        $indexView,
        'Procedures dashboard should provide plain-language guidance for non-admin readers.'
    );
    assertContains(
        'Visible Active PDMS Procedures',
        $indexView,
        'Procedures dashboard should clarify that the active metric only counts visible operational procedures.'
    );
    assertContains(
        'Responsibility center',
        $indexView,
        'Procedures dashboard should expose a responsibility-center filter.'
    );
    assertContains(
        'Effective from',
        $indexView,
        'Procedures dashboard should expose an effective date-range filter.'
    );
    assertContains(
        'Procedure Master Status',
        $versionView,
        'Procedure version detail should label the master procedure status explicitly.'
    );
    assertContains(
        'Version Change Type',
        $versionView,
        'Procedure version detail should label the version change type explicitly.'
    );
    assertContains(
        'How To Read This Version',
        $versionView,
        'Procedure version detail should explain version status in plain language.'
    );

    echo "Dashboard historical access regression: OK\n";
}

runRegressionSuite();
