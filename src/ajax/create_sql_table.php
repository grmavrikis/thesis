<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => TEXT_ONLY_POST_ALLOWED]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$table_name = $input['table_name'] ?? null;
$fields = $input['fields'] ?? null;
$primary_keys = $input['primary_keys'] ?? [];
$unique_keys  = $input['unique_keys'] ?? [];

if (!$table_name || !$fields || !is_array($fields))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => TEXT_MISSING_PARAMETERS]);
    exit;
}

try
{
    // Create the sql syntax for table creation and execute
    $db->createTable($table_name, $fields, $primary_keys, $unique_keys);

    echo json_encode([
        'success' => true,
        'message' => sprintf(TEXT_TABLE_CREATED_SUCCESSFULLY, $table_name)
    ]);
}
catch (Exception $e)
{
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => sprintf(TEXT_TABLE_CREATION_FAILED, $table_name),
        'error_details' => $e->getMessage()
    ]);
}
exit;
