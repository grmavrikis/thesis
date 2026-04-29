<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/init.php';

$json_file_path = __DIR__ . '/../data/test_data.json';

if (!file_exists($json_file_path))
{
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => sprintf(TEXT_JSON_FILE_NOT_FOUND, $json_file_path)]);
    exit;
}

$json_data = file_get_contents($json_file_path);
$test_data = json_decode($json_data, true);

if ($test_data === null && json_last_error() !== JSON_ERROR_NONE)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => sprintf(TEXT_JSON_READ_ERROR, json_last_error_msg())]);
    exit;
}

// Return the data as JSON
echo $json_data;
exit;
