<?php

/**
 * Finalizes the application installation:
 * 1. Moves index.php (installer) to /bin/index.php
 * 2. Renames index_main.php (main application) to index.php
 */

header('Content-Type: application/json');
require_once '../includes/init.php';

$binDir = $_SERVER['DOCUMENT_ROOT'] . '/bin';

try
{
    // Move index.php to /bin/index.php
    $setupFile = 'index.php';
    $sourceSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $setupFile;
    $destSetup = $binDir . '/' . $setupFile;

    if (file_exists($sourceSetup))
    {
        if (file_exists($destSetup))
        {
            unlink($destSetup);
        }
        if (!rename($sourceSetup, $destSetup))
        {
            throw new Exception(sprintf(TEXT_MOVE_SETUP_FILE_FAILED, $setupFile));
        }
    }

    // Rename index_main.php to index.php
    $mainIndexSource = $_SERVER['DOCUMENT_ROOT'] . '/index_main.php';
    $mainIndexDest = $_SERVER['DOCUMENT_ROOT'] . '/index.php';

    if (!file_exists($mainIndexSource))
    {
        throw new Exception(sprintf(TEXT_MAIN_INDEX_FILE_NOT_FOUND, $mainIndexSource));
    }

    if (!rename($mainIndexSource, $mainIndexDest))
    {
        throw new Exception(TEXT_RENAME_MAIN_INDEX_FAILED);
    }

    echo json_encode([
        'success' => true,
        'redirect_url' => $seoUrl->generate('index.php'),
        'message' => TEXT_INIT_SUCCESS
    ]);
}
catch (Exception $e)
{
    echo json_encode([
        'success' => false,
        'message' => sprintf(TEXT_FILE_SYSTEM_ERROR, $e->getMessage())
    ]);
}

exit;
