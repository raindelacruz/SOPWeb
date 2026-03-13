<?php

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertFileContains($path, $needle, $message) {
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read file: ' . $path);
    }

    assertTrue(strpos($contents, $needle) !== false, $message);
}

function runRegressionSuite() {
    $postsControllerPath = __DIR__ . '/../app/controllers/Posts.php';

    assertFileContains(
        $postsControllerPath,
        'if ($this->redirectMappedLegacyEditToPdms($id)) {',
        'Posts::edit should continue to gate mapped legacy edits through the PDMS redirect helper.'
    );

    assertFileContains(
        $postsControllerPath,
        "Mapped SOP records are now maintained from the PDMS procedure flow.",
        'The mapped legacy edit redirect should continue to explain that PDMS is the maintenance path.'
    );

    assertFileContains(
        $postsControllerPath,
        "redirect('procedures/edit/' . (int) \$overview->id);",
        'Mapped current legacy records should still redirect to PDMS procedure edit.'
    );

    assertFileContains(
        $postsControllerPath,
        "redirect('procedures/version/' . (int) \$overview->mapped_version_id);",
        'Mapped non-current legacy records should still redirect to PDMS version detail.'
    );

    assertFileContains(
        $postsControllerPath,
        "redirect('procedures/show/' . (int) \$overview->id);",
        'Mapped legacy records without a current editable version should still redirect to PDMS procedure detail.'
    );

    echo "Mapped legacy redirect regression: OK\n";
}

runRegressionSuite();
